<?php

namespace App\Http\Controllers\Admin;

use App\Exports\HrReportExport;
use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\DutyRoster;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\SalaryAdvance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class HrReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:hr-dashboard-view');
    }

    public function index(Request $request)
    {
        [$headings, $rows, $title] = $this->buildReport($request);
        $employees = Employee::query()->orderBy('first_name')->get();
        $departments = Department::query()->where('status', true)->orderBy('name')->get();

        return view('admin.hr.reports.index', compact('headings', 'rows', 'title', 'employees', 'departments'));
    }

    public function export(Request $request)
    {
        [$headings, $rows, $title] = $this->buildReport($request);
        $filename = strtolower(str_replace(' ', '-', $title)).'-'.now()->format('Ymd-His').'.xlsx';
        return Excel::download(new HrReportExport($headings, $rows, $title), $filename);
    }

    private function buildReport(Request $request): array
    {
        $type = $request->get('report_type', 'attendance');
        $from = $request->filled('from_date') ? Carbon::parse($request->from_date)->toDateString() : now()->startOfMonth()->toDateString();
        $to = $request->filled('to_date') ? Carbon::parse($request->to_date)->toDateString() : now()->toDateString();
        $employeeId = $request->employee_id;
        $departmentId = $request->department_id;

        return match ($type) {
            'employee' => $this->employeeReport($employeeId, $departmentId),
            'leave' => $this->leaveReport($from, $to, $employeeId, $departmentId),
            'payroll' => $this->payrollReport($from, $to, $employeeId, $departmentId),
            'loan' => $this->loanReport($from, $to, $employeeId, $departmentId),
            'roster' => $this->rosterReport($from, $to, $employeeId, $departmentId),
            default => $this->attendanceReport($from, $to, $employeeId, $departmentId),
        };
    }

    private function attendanceReport($from, $to, $employeeId, $departmentId): array
    {
        $rows = AttendanceRecord::with(['employee.department', 'shift'])
            ->whereBetween('attendance_date', [$from, $to])
            ->when($employeeId, fn ($q, $id) => $q->where('employee_id', $id))
            ->when($departmentId, fn ($q, $id) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $id)))
            ->orderBy('attendance_date')->get()->map(fn ($r) => [
                $r->attendance_date->format('d-m-Y'), $r->employee?->employee_code, $r->employee?->full_name,
                $r->employee?->department?->name, $r->shift?->name, $r->check_in?->format('h:i A'), $r->check_out?->format('h:i A'),
                $r->worked_minutes, $r->late_minutes, $r->overtime_minutes, ucwords(str_replace('_', ' ', $r->status)), $r->remarks,
            ])->all();
        return [['Date', 'Employee Code', 'Employee', 'Department', 'Shift', 'Check In', 'Check Out', 'Worked Minutes', 'Late Minutes', 'OT Minutes', 'Status', 'Remarks'], $rows, 'Attendance Report'];
    }

    private function employeeReport($employeeId, $departmentId): array
    {
        $rows = Employee::with(['department', 'designation', 'employmentType', 'defaultShift'])
            ->when($employeeId, fn ($q, $id) => $q->where('id', $id))
            ->when($departmentId, fn ($q, $id) => $q->where('department_id', $id))
            ->orderBy('employee_code')->get()->map(fn ($e) => [
                $e->employee_code, $e->full_name, $e->phone, $e->email, $e->department?->name, $e->designation?->name,
                $e->employmentType?->name, $e->defaultShift?->name, $e->join_date?->format('d-m-Y'), ucfirst($e->status),
            ])->all();
        return [['Employee Code', 'Employee', 'Phone', 'Email', 'Department', 'Designation', 'Employment Type', 'Default Shift', 'Join Date', 'Status'], $rows, 'Employee Report'];
    }

    private function leaveReport($from, $to, $employeeId, $departmentId): array
    {
        $rows = LeaveRequest::with(['employee.department', 'leaveType'])
            ->whereDate('start_date', '<=', $to)->whereDate('end_date', '>=', $from)
            ->when($employeeId, fn ($q, $id) => $q->where('employee_id', $id))
            ->when($departmentId, fn ($q, $id) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $id)))
            ->orderBy('start_date')->get()->map(fn ($r) => [
                $r->employee?->employee_code, $r->employee?->full_name, $r->employee?->department?->name, $r->leaveType?->name,
                $r->start_date->format('d-m-Y'), $r->end_date->format('d-m-Y'), $r->total_days, ucfirst($r->status), $r->reason,
            ])->all();
        return [['Employee Code', 'Employee', 'Department', 'Leave Type', 'Start Date', 'End Date', 'Days', 'Status', 'Reason'], $rows, 'Leave Report'];
    }

    private function payrollReport($from, $to, $employeeId, $departmentId): array
    {
        $rows = Payroll::with(['employee.department', 'period'])
            ->whereHas('period', fn ($q) => $q->whereDate('start_date', '<=', $to)->whereDate('end_date', '>=', $from))
            ->when($employeeId, fn ($q, $id) => $q->where('employee_id', $id))
            ->when($departmentId, fn ($q, $id) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $id)))
            ->orderBy('payroll_period_id')->get()->map(fn ($r) => [
                $r->period?->name, $r->employee?->employee_code, $r->employee?->full_name, $r->employee?->department?->name,
                $r->working_days, $r->present_days, $r->absent_days, $r->basic_salary, $r->total_earnings, $r->total_deductions,
                $r->net_salary, $r->paid_amount, $r->due_amount, ucfirst($r->payment_status), ucfirst($r->payroll_status),
            ])->all();
        return [['Period', 'Employee Code', 'Employee', 'Department', 'Working Days', 'Present', 'Absent', 'Basic', 'Earnings', 'Deductions', 'Net Salary', 'Paid', 'Due', 'Payment Status', 'Payroll Status'], $rows, 'Payroll Report'];
    }

    private function loanReport($from, $to, $employeeId, $departmentId): array
    {
        $rows = SalaryAdvance::with(['employee.department'])
            ->whereBetween('request_date', [$from, $to])
            ->when($employeeId, fn ($q, $id) => $q->where('employee_id', $id))
            ->when($departmentId, fn ($q, $id) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $id)))
            ->orderBy('request_date')->get()->map(fn ($r) => [
                $r->employee?->employee_code, $r->employee?->full_name, $r->employee?->department?->name,
                ucwords(str_replace('_', ' ', $r->advance_type ?? 'salary_advance')), $r->request_date->format('d-m-Y'), $r->amount,
                $r->number_of_installments, $r->installment_amount, $r->paid_amount, $r->remaining_amount, ucfirst($r->status), $r->reason,
            ])->all();
        return [['Employee Code', 'Employee', 'Department', 'Type', 'Request Date', 'Amount', 'Installments', 'Installment Amount', 'Paid', 'Remaining', 'Status', 'Reason'], $rows, 'Salary Advance and Loan Report'];
    }

    private function rosterReport($from, $to, $employeeId, $departmentId): array
    {
        $rows = DutyRoster::with(['employee.department', 'shift'])
            ->whereBetween('duty_date', [$from, $to])
            ->when($employeeId, fn ($q, $id) => $q->where('employee_id', $id))
            ->when($departmentId, fn ($q, $id) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $id)))
            ->orderBy('duty_date')->get()->map(fn ($r) => [
                $r->duty_date->format('d-m-Y'), $r->employee?->employee_code, $r->employee?->full_name, $r->employee?->department?->name,
                $r->shift?->name, ucfirst($r->roster_status), $r->notes,
            ])->all();
        return [['Date', 'Employee Code', 'Employee', 'Department', 'Shift', 'Roster Status', 'Notes'], $rows, 'Duty Roster Report'];
    }
}
