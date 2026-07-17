<?php

namespace App\Http\Controllers\Admin;

use App\Exports\AttendanceTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\AttendanceImport;
use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Shift;
use App\Services\Hr\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:hr-dashboard-view')->only(['index']);
        $this->middleware('permission:hr-setting-manage')->except(['index']);
    }

    public function index(Request $request)
    {
        $from = $request->filled('from_date') ? Carbon::parse($request->from_date) : now()->startOfMonth();
        $to = $request->filled('to_date') ? Carbon::parse($request->to_date) : now();

        $records = AttendanceRecord::query()
            ->with(['employee.department', 'employee.designation', 'shift'])
            ->whereBetween('attendance_date', [$from->toDateString(), $to->toDateString()])
            ->when($request->employee_id, fn ($q, $id) => $q->where('employee_id', $id))
            ->when($request->department_id, fn ($q, $id) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $id)))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->latest('attendance_date')
            ->latest('check_in')
            ->paginate(25)
            ->withQueryString();

        $summaryQuery = AttendanceRecord::query()
            ->whereBetween('attendance_date', [$from->toDateString(), $to->toDateString()])
            ->when($request->employee_id, fn ($q, $id) => $q->where('employee_id', $id))
            ->when($request->department_id, fn ($q, $id) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $id)));

        $summary = [
            'present' => (clone $summaryQuery)->whereIn('status', ['present', 'late'])->count(),
            'late' => (clone $summaryQuery)->where('status', 'late')->count(),
            'absent' => (clone $summaryQuery)->where('status', 'absent')->count(),
            'leave' => (clone $summaryQuery)->where('status', 'leave')->count(),
            'overtime_minutes' => (int) (clone $summaryQuery)->sum('overtime_minutes'),
        ];

        $employees = Employee::query()->where('status', 'active')->orderBy('first_name')->get(['id', 'employee_code', 'first_name', 'last_name']);
        $departments = Department::query()->where('status', true)->orderBy('name')->get();
        $shifts = Shift::query()->where('status', true)->orderBy('name')->get();

        return view('admin.hr.attendance.index', compact('records', 'employees', 'departments', 'shifts', 'summary', 'from', 'to'));
    }

    public function store(Request $request, AttendanceService $service)
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'attendance_date' => 'required|date',
            'shift_id' => 'nullable|exists:shifts,id',
            'check_in' => 'nullable|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i',
            'break_minutes' => 'nullable|integer|min:0|max:1440',
            'status' => 'nullable|in:present,late,absent,half_day,leave,holiday,off,missing_checkout',
            'remarks' => 'nullable|string|max:2000',
        ]);

        $employee = Employee::findOrFail($data['employee_id']);
        $service->save($employee, Carbon::parse($data['attendance_date']), [
            ...$data,
            'source' => 'manual',
        ], auth()->id());

        return back()->with('success', 'Attendance saved successfully.');
    }

    public function update(Request $request, AttendanceRecord $attendance, AttendanceService $service)
    {
        $data = $request->validate([
            'attendance_date' => 'required|date',
            'shift_id' => 'nullable|exists:shifts,id',
            'check_in' => 'nullable|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i',
            'break_minutes' => 'nullable|integer|min:0|max:1440',
            'status' => 'nullable|in:present,late,absent,half_day,leave,holiday,off,missing_checkout',
            'remarks' => 'nullable|string|max:2000',
        ]);

        DB::transaction(function () use ($attendance, $service, $data) {
            $employee = $attendance->employee;
            $saved = $service->save($employee, Carbon::parse($data['attendance_date']), [
                ...$data,
                'source' => 'manual',
            ], auth()->id());
            if ($saved->id !== $attendance->id) {
                $attendance->delete();
            }
        });

        return back()->with('success', 'Attendance updated successfully.');
    }

    public function destroy(AttendanceRecord $attendance)
    {
        $attendance->delete();
        return back()->with('success', 'Attendance deleted successfully.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'attendance_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $import = new AttendanceImport(auth()->id());
        Excel::import($import, $request->file('attendance_file'));

        $message = "Attendance import completed: {$import->imported} created, {$import->updated} updated, {$import->skipped} skipped.";
        return back()
            ->with($import->skipped ? 'warning' : 'success', $message)
            ->with('attendance_import_errors', $import->errors);
    }

    public function template()
    {
        return Excel::download(new AttendanceTemplateExport(), 'attendance-import-template.xlsx');
    }

    public function markAbsent(Request $request, AttendanceService $service)
    {
        $data = $request->validate([
            'attendance_date' => 'required|date',
            'department_id' => 'nullable|exists:departments,id',
        ]);
        $date = Carbon::parse($data['attendance_date']);

        $employees = Employee::query()
            ->where('status', 'active')
            ->when($data['department_id'] ?? null, fn ($q, $id) => $q->where('department_id', $id))
            ->whereDoesntHave('attendanceRecords', fn ($q) => $q->whereDate('attendance_date', $date))
            ->get();

        foreach ($employees as $employee) {
            $service->save($employee, $date, [
                'status' => 'absent',
                'source' => 'auto_absent',
                'remarks' => 'Bulk absent marking',
            ], auth()->id());
        }

        return back()->with('success', $employees->count().' employee(s) marked absent.');
    }
}
