<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\PayrollPeriod;
use Carbon\Carbon;

class HrDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:hr-dashboard-view');
    }

    public function index()
    {
        $today = Carbon::today();

        $employeeStats = [
            'total' => Employee::count(),
            'active' => Employee::where('status', 'active')->count(),
            'inactive' => Employee::whereIn('status', ['inactive', 'resigned', 'terminated'])->count(),
            'on_leave' => Employee::where('status', 'on_leave')->count(),
        ];

        $attendanceStats = [
            'present' => AttendanceRecord::whereDate('attendance_date', $today)
                ->whereIn('status', ['present', 'half_day'])->count(),
            'late' => AttendanceRecord::whereDate('attendance_date', $today)
                ->where('status', 'late')->count(),
            'absent' => AttendanceRecord::whereDate('attendance_date', $today)
                ->where('status', 'absent')->count(),
            'leave' => AttendanceRecord::whereDate('attendance_date', $today)
                ->where('status', 'leave')->count(),
        ];

        $pendingLeaveCount = LeaveRequest::where('status', 'pending')->count();

        $departmentSummary = Department::withCount([
            'employees as total_employees',
            'employees as active_employees' => fn ($query) => $query->where('status', 'active'),
        ])->where('status', true)->orderBy('name')->get();

        $recentEmployees = Employee::with(['department', 'designation', 'defaultShift'])
            ->latest('id')->limit(8)->get();

        $todayAttendance = AttendanceRecord::with(['employee.department', 'employee.designation', 'shift'])
            ->whereDate('attendance_date', $today)
            ->latest('check_in')->limit(10)->get();

        $pendingLeaves = LeaveRequest::with(['employee.department', 'leaveType'])
            ->where('status', 'pending')
            ->orderBy('start_date')
            ->limit(8)
            ->get();

        $upcomingHolidays = Holiday::where('status', true)
            ->whereDate('holiday_date', '>=', $today)
            ->orderBy('holiday_date')
            ->limit(6)
            ->get();

        $currentPayrollPeriod = PayrollPeriod::whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->latest('id')
            ->first();

        return view('admin.hr.dashboard.index', compact(
            'today',
            'employeeStats',
            'attendanceStats',
            'pendingLeaveCount',
            'departmentSummary',
            'recentEmployees',
            'todayAttendance',
            'pendingLeaves',
            'upcomingHolidays',
            'currentPayrollPeriod'
        ));
    }
}
