<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeLeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class LeaveManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:hr-dashboard-view')->only(['index']);
        $this->middleware('permission:hr-setting-manage')->except(['index']);
    }

    public function index(Request $request)
    {
        $requests = LeaveRequest::query()
            ->with(['employee.department', 'leaveType', 'approver'])
            ->when($request->employee_id, fn ($q, $id) => $q->where('employee_id', $id))
            ->when($request->department_id, fn ($q, $id) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $id)))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->from_date, fn ($q, $date) => $q->whereDate('end_date', '>=', $date))
            ->when($request->to_date, fn ($q, $date) => $q->whereDate('start_date', '<=', $date))
            ->latest('id')->paginate(20)->withQueryString();

        $balances = EmployeeLeaveBalance::query()
            ->with(['employee.department', 'leaveType'])
            ->where('year', (int) ($request->balance_year ?: now()->year))
            ->when($request->employee_id, fn ($q, $id) => $q->where('employee_id', $id))
            ->orderBy('employee_id')->paginate(20, ['*'], 'balance_page')->withQueryString();

        $employees = Employee::query()->where('status', 'active')->orderBy('first_name')->get();
        $departments = Department::query()->where('status', true)->orderBy('name')->get();
        $leaveTypes = LeaveType::query()->where('status', true)->orderBy('name')->get();

        return view('admin.hr.leave.index', compact('requests', 'balances', 'employees', 'departments', 'leaveTypes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:3000',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120',
        ]);

        $overlap = LeaveRequest::query()->where('employee_id', $data['employee_id'])
            ->whereIn('status', ['pending', 'approved'])
            ->whereDate('start_date', '<=', $data['end_date'])
            ->whereDate('end_date', '>=', $data['start_date'])->exists();
        if ($overlap) {
            return back()->withInput()->with('error', 'An overlapping pending or approved leave request already exists.');
        }

        $totalDays = Carbon::parse($data['start_date'])->diffInDays(Carbon::parse($data['end_date'])) + 1;
        $leaveType = LeaveType::findOrFail($data['leave_type_id']);
        if ($leaveType->requires_document && !$request->hasFile('attachment')) {
            return back()->withInput()->with('error', 'This leave type requires an attachment.');
        }

        $path = null;
        if ($request->hasFile('attachment')) {
            $directory = public_path('uploads/hr/leave');
            File::ensureDirectoryExists($directory);
            $name = uniqid('leave_', true).'.'.$request->file('attachment')->getClientOriginalExtension();
            $request->file('attachment')->move($directory, $name);
            $path = 'uploads/hr/leave/'.$name;
        }

        LeaveRequest::create([...$data, 'total_days' => $totalDays, 'attachment' => $path, 'status' => 'pending']);
        return back()->with('success', 'Leave request submitted successfully.');
    }

    public function approve(LeaveRequest $leaveRequest)
    {
        if ($leaveRequest->status !== 'pending') {
            return back()->with('error', 'Only pending leave requests can be approved.');
        }

        try {
            DB::transaction(function () use ($leaveRequest) {
                $year = $leaveRequest->start_date->year;
                $balance = EmployeeLeaveBalance::query()->lockForUpdate()->firstOrCreate(
                    ['employee_id' => $leaveRequest->employee_id, 'leave_type_id' => $leaveRequest->leave_type_id, 'year' => $year],
                    ['allocated_days' => $leaveRequest->leaveType->annual_limit, 'remaining_days' => $leaveRequest->leaveType->annual_limit]
                );

                if ($leaveRequest->leaveType->is_paid && (float) $balance->remaining_days < (float) $leaveRequest->total_days) {
                    throw new \RuntimeException('Insufficient leave balance for this request.');
                }

                $balance->used_days = (float) $balance->used_days + (float) $leaveRequest->total_days;
                $balance->remaining_days = (float) $balance->opening_balance + (float) $balance->allocated_days + (float) $balance->adjusted_days - (float) $balance->used_days;
                $balance->save();

                $leaveRequest->update(['status' => 'approved', 'approved_by' => auth()->id(), 'approved_at' => now(), 'rejection_reason' => null]);

                foreach (CarbonPeriod::create($leaveRequest->start_date, $leaveRequest->end_date) as $date) {
                    $record = AttendanceRecord::query()->where('employee_id', $leaveRequest->employee_id)->whereDate('attendance_date', $date)->first();
                    if (!$record) {
                        AttendanceRecord::create([
                            'employee_id' => $leaveRequest->employee_id,
                            'attendance_date' => $date->toDateString(),
                            'shift_id' => null,
                            'status' => 'leave',
                            'source' => 'leave',
                            'remarks' => $leaveRequest->leaveType->name,
                            'created_by' => auth()->id(),
                            'updated_by' => auth()->id(),
                        ]);
                    } elseif (!$record->check_in && !$record->check_out) {
                        $record->update([
                            'status' => 'leave',
                            'source' => 'leave',
                            'remarks' => $leaveRequest->leaveType->name,
                            'updated_by' => auth()->id(),
                        ]);
                    }
                }
            });
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Leave request approved.');
    }

    public function reject(Request $request, LeaveRequest $leaveRequest)
    {
        $data = $request->validate(['rejection_reason' => 'required|string|max:2000']);
        if ($leaveRequest->status !== 'pending') return back()->with('error', 'Only pending requests can be rejected.');
        $leaveRequest->update(['status' => 'rejected', 'approved_by' => auth()->id(), 'approved_at' => now(), 'rejection_reason' => $data['rejection_reason']]);
        return back()->with('success', 'Leave request rejected.');
    }

    public function cancel(LeaveRequest $leaveRequest)
    {
        if (!in_array($leaveRequest->status, ['pending', 'approved'], true)) return back()->with('error', 'This leave request cannot be cancelled.');
        DB::transaction(function () use ($leaveRequest) {
            if ($leaveRequest->status === 'approved') {
                $balance = EmployeeLeaveBalance::query()->where('employee_id', $leaveRequest->employee_id)->where('leave_type_id', $leaveRequest->leave_type_id)->where('year', $leaveRequest->start_date->year)->first();
                if ($balance) {
                    $balance->used_days = max(0, (float) $balance->used_days - (float) $leaveRequest->total_days);
                    $balance->remaining_days = (float) $balance->opening_balance + (float) $balance->allocated_days + (float) $balance->adjusted_days - (float) $balance->used_days;
                    $balance->save();
                }
                AttendanceRecord::query()->where('employee_id', $leaveRequest->employee_id)->whereBetween('attendance_date', [$leaveRequest->start_date, $leaveRequest->end_date])->where('source', 'leave')->delete();
            }
            $leaveRequest->update(['status' => 'cancelled']);
        });
        return back()->with('success', 'Leave request cancelled.');
    }

    public function saveBalance(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id', 'leave_type_id' => 'required|exists:leave_types,id', 'year' => 'required|integer|min:2000|max:2100',
            'opening_balance' => 'nullable|numeric|min:0', 'allocated_days' => 'nullable|numeric|min:0', 'adjusted_days' => 'nullable|numeric',
        ]);
        $balance = EmployeeLeaveBalance::firstOrNew(['employee_id' => $data['employee_id'], 'leave_type_id' => $data['leave_type_id'], 'year' => $data['year']]);
        $balance->opening_balance = $data['opening_balance'] ?? 0;
        $balance->allocated_days = $data['allocated_days'] ?? LeaveType::find($data['leave_type_id'])->annual_limit;
        $balance->adjusted_days = $data['adjusted_days'] ?? 0;
        $balance->used_days = $balance->used_days ?? 0;
        $balance->remaining_days = (float) $balance->opening_balance + (float) $balance->allocated_days + (float) $balance->adjusted_days - (float) $balance->used_days;
        $balance->save();
        return back()->with('success', 'Leave balance saved successfully.');
    }
}
