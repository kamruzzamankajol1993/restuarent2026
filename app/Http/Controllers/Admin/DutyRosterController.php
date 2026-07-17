<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\DutyRoster;
use App\Models\Employee;
use App\Models\EmployeeShiftAssignment;
use App\Models\Shift;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DutyRosterController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:hr-dashboard-view')->only(['index']);
        $this->middleware('permission:hr-setting-manage')->except(['index']);
    }

    public function index(Request $request)
    {
        $month = $request->filled('month') ? Carbon::createFromFormat('Y-m', $request->month) : now();
        $from = $month->copy()->startOfMonth();
        $to = $month->copy()->endOfMonth();

        $rosters = DutyRoster::query()
            ->with(['employee.department', 'employee.designation', 'shift'])
            ->whereBetween('duty_date', [$from->toDateString(), $to->toDateString()])
            ->when($request->employee_id, fn ($q, $id) => $q->where('employee_id', $id))
            ->when($request->department_id, fn ($q, $id) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $id)))
            ->orderBy('duty_date')
            ->orderBy('employee_id')
            ->paginate(31)
            ->withQueryString();

        $assignments = EmployeeShiftAssignment::query()
            ->with(['employee.department', 'shift'])
            ->when($request->employee_id, fn ($q, $id) => $q->where('employee_id', $id))
            ->latest('effective_from')
            ->paginate(15, ['*'], 'assignment_page')
            ->withQueryString();

        $employees = Employee::query()->where('status', 'active')->orderBy('first_name')->get(['id', 'employee_code', 'first_name', 'last_name', 'department_id']);
        $departments = Department::query()->where('status', true)->orderBy('name')->get();
        $shifts = Shift::query()->where('status', true)->orderBy('name')->get();

        return view('admin.hr.roster.index', compact('rosters', 'assignments', 'employees', 'departments', 'shifts', 'month', 'from', 'to'));
    }

    public function storeRoster(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'shift_id' => 'nullable|exists:shifts,id',
            'duty_date' => 'required|date',
            'roster_status' => 'required|in:scheduled,off,holiday,leave,cancelled',
            'notes' => 'nullable|string|max:2000',
        ]);

        DutyRoster::query()->updateOrCreate(
            ['employee_id' => $data['employee_id'], 'duty_date' => $data['duty_date']],
            [...$data, 'created_by' => auth()->id()]
        );

        return back()->with('success', 'Duty roster saved successfully.');
    }

    public function bulkGenerate(Request $request)
    {
        $data = $request->validate([
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'exists:employees,id',
            'shift_id' => 'nullable|exists:shifts,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'weekly_off_days' => 'nullable|array',
            'weekly_off_days.*' => 'integer|min:0|max:6',
            'notes' => 'nullable|string|max:2000',
        ]);

        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);
        abort_if($start->diffInDays($end) > 366, 422, 'Roster range cannot exceed 366 days.');

        $offDays = array_map('intval', $data['weekly_off_days'] ?? []);
        $count = 0;

        DB::transaction(function () use ($data, $start, $end, $offDays, &$count) {
            foreach ($data['employee_ids'] as $employeeId) {
                foreach (CarbonPeriod::create($start, $end) as $date) {
                    $status = in_array($date->dayOfWeek, $offDays, true) ? 'off' : 'scheduled';
                    DutyRoster::query()->updateOrCreate(
                        ['employee_id' => $employeeId, 'duty_date' => $date->toDateString()],
                        [
                            'shift_id' => $status === 'off' ? null : ($data['shift_id'] ?? null),
                            'roster_status' => $status,
                            'notes' => $data['notes'] ?? null,
                            'created_by' => auth()->id(),
                        ]
                    );
                    $count++;
                }
            }
        });

        return back()->with('success', "{$count} roster entry/entries generated or updated.");
    }

    public function destroyRoster(DutyRoster $roster)
    {
        $roster->delete();
        return back()->with('success', 'Roster entry deleted.');
    }

    public function storeAssignment(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'shift_id' => 'required|exists:shifts,id',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'weekly_off_days' => 'nullable|array',
            'weekly_off_days.*' => 'integer|min:0|max:6',
            'status' => 'nullable|boolean',
        ]);

        EmployeeShiftAssignment::create([
            ...$data,
            'weekly_off_days' => array_map('intval', $data['weekly_off_days'] ?? []),
            'status' => $request->boolean('status', true),
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Shift assignment created successfully.');
    }

    public function updateAssignment(Request $request, EmployeeShiftAssignment $assignment)
    {
        $data = $request->validate([
            'shift_id' => 'required|exists:shifts,id',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'weekly_off_days' => 'nullable|array',
            'weekly_off_days.*' => 'integer|min:0|max:6',
            'status' => 'nullable|boolean',
        ]);

        $assignment->update([
            ...$data,
            'weekly_off_days' => array_map('intval', $data['weekly_off_days'] ?? []),
            'status' => $request->boolean('status'),
        ]);

        return back()->with('success', 'Shift assignment updated successfully.');
    }

    public function destroyAssignment(EmployeeShiftAssignment $assignment)
    {
        $assignment->delete();
        return back()->with('success', 'Shift assignment deleted.');
    }
}
