@extends('admin.master.master')
@section('title', 'HR Dashboard')

@section('css')
<style>
    .hr-stat-card { border: 0; min-height: 126px; }
    .hr-stat-icon { width: 46px; height: 46px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; font-size: 20px; background: rgba(33,53,42,.09); color: #21352a; }
    .hr-stat-value { font-size: 28px; line-height: 1; font-weight: 800; color: #21352a; }
    .hr-muted { color: #6c757d; font-size: 12px; }
    .hr-progress { height: 8px; background: rgba(33,53,42,.08); border-radius: 99px; overflow: hidden; }
    .hr-progress > span { display: block; height: 100%; background: #21352a; border-radius: inherit; }
    .avatar-sm { width: 38px; height: 38px; object-fit: cover; border-radius: 50%; background: #eef1ef; }
</style>
@endsection

@section('body')
<main class="progga-content">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h4 class="mb-1 fw-bold">HR Dashboard</h4>
            <div class="text-muted small">Workforce overview for {{ $today->format('d M Y') }}</div>
        </div>
        @can('employee-create')
            <a href="{{ route('hr.employees.create') }}" class="progga-btn progga-btn-primary">
                <i class="bi bi-person-plus-fill"></i> Add Employee
            </a>
        @endcan
    </div>

    @include('admin.hr.partials.alerts')

    <div class="row g-3 mb-4">
        @php
            $cards = [
                ['label' => 'Total Employees', 'value' => $employeeStats['total'], 'icon' => 'bi-people-fill', 'hint' => 'All employee records'],
                ['label' => 'Active Employees', 'value' => $employeeStats['active'], 'icon' => 'bi-person-check-fill', 'hint' => 'Currently active'],
                ['label' => 'Present Today', 'value' => $attendanceStats['present'] + $attendanceStats['late'], 'icon' => 'bi-calendar2-check-fill', 'hint' => $attendanceStats['late'].' late today'],
                ['label' => 'Pending Leave', 'value' => $pendingLeaveCount, 'icon' => 'bi-hourglass-split', 'hint' => 'Awaiting approval'],
            ];
        @endphp
        @foreach($cards as $card)
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="progga-card hr-stat-card h-100">
                    <div class="progga-card-body d-flex align-items-center justify-content-between gap-3">
                        <div>
                            <div class="hr-muted mb-2">{{ $card['label'] }}</div>
                            <div class="hr-stat-value">{{ number_format($card['value']) }}</div>
                            <div class="hr-muted mt-2">{{ $card['hint'] }}</div>
                        </div>
                        <span class="hr-stat-icon"><i class="bi {{ $card['icon'] }}"></i></span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-8">
            <div class="progga-card h-100">
                <div class="progga-card-header">
                    <div>
                        <div class="progga-card-title">Today's Attendance</div>
                        <div class="progga-card-subtitle">Latest check-in and status records</div>
                    </div>
                </div>
                <div class="progga-card-body p-0">
                    <div class="table-responsive">
                        <table class="progga-table mb-0">
                            <thead><tr><th>Employee</th><th>Department</th><th>Shift</th><th>Check In</th><th>Check Out</th><th>Status</th></tr></thead>
                            <tbody>
                            @forelse($todayAttendance as $attendance)
                                @php
                                    $statusClass = match($attendance->status) {
                                        'present' => 'success', 'late' => 'warning', 'absent' => 'danger', 'leave' => 'info', default => 'secondary'
                                    };
                                @endphp
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            @if($attendance->employee?->image)
                                                <img class="avatar-sm" src="{{ asset('public/'.$attendance->employee->image) }}" alt="Employee">
                                            @else
                                                <span class="avatar-sm d-inline-flex align-items-center justify-content-center fw-bold">{{ strtoupper(substr($attendance->employee?->first_name ?? 'E', 0, 1)) }}</span>
                                            @endif
                                            <div><div class="fw-semibold">{{ $attendance->employee?->full_name ?? 'N/A' }}</div><small class="text-muted">{{ $attendance->employee?->employee_code }}</small></div>
                                        </div>
                                    </td>
                                    <td>{{ $attendance->employee?->department?->name ?? '—' }}</td>
                                    <td>{{ $attendance->shift?->name ?? $attendance->employee?->defaultShift?->name ?? '—' }}</td>
                                    <td>{{ $attendance->check_in?->format('h:i A') ?? '—' }}</td>
                                    <td>{{ $attendance->check_out?->format('h:i A') ?? '—' }}</td>
                                    <td><span class="badge text-bg-{{ $statusClass }} text-capitalize">{{ str_replace('_', ' ', $attendance->status) }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center py-5 text-muted">No attendance records found for today.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="progga-card h-100">
                <div class="progga-card-header"><div class="progga-card-title">Attendance Summary</div></div>
                <div class="progga-card-body">
                    @php
                        $attendanceTotal = max(1, array_sum($attendanceStats));
                        $summaryItems = [
                            ['label'=>'Present','value'=>$attendanceStats['present']],
                            ['label'=>'Late','value'=>$attendanceStats['late']],
                            ['label'=>'Absent','value'=>$attendanceStats['absent']],
                            ['label'=>'Leave','value'=>$attendanceStats['leave']],
                        ];
                    @endphp
                    @foreach($summaryItems as $item)
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1"><span>{{ $item['label'] }}</span><strong>{{ $item['value'] }}</strong></div>
                            <div class="hr-progress"><span style="width: {{ min(100, ($item['value'] / $attendanceTotal) * 100) }}%"></span></div>
                        </div>
                    @endforeach
                    <hr>
                    <div class="d-flex justify-content-between"><span class="text-muted">Payroll period</span><strong>{{ $currentPayrollPeriod?->name ?? 'Not configured' }}</strong></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-6">
            <div class="progga-card h-100">
                <div class="progga-card-header"><div class="progga-card-title">Department Strength</div></div>
                <div class="progga-card-body">
                    @php $maxDepartment = max(1, (int) $departmentSummary->max('total_employees')); @endphp
                    @forelse($departmentSummary as $department)
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="fw-semibold">{{ $department->name }}</span>
                                <span class="small text-muted">{{ $department->active_employees }}/{{ $department->total_employees }} active</span>
                            </div>
                            <div class="hr-progress"><span style="width: {{ ($department->total_employees / $maxDepartment) * 100 }}%"></span></div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-4">No departments configured.</div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="progga-card h-100">
                <div class="progga-card-header"><div class="progga-card-title">Upcoming Holidays</div></div>
                <div class="progga-card-body p-0">
                    <div class="table-responsive">
                        <table class="progga-table mb-0">
                            <thead><tr><th>Date</th><th>Holiday</th><th>Type</th><th>Paid</th></tr></thead>
                            <tbody>
                            @forelse($upcomingHolidays as $holiday)
                                <tr>
                                    <td>{{ $holiday->holiday_date->format('d M Y') }}</td>
                                    <td class="fw-semibold">{{ $holiday->name }}</td>
                                    <td class="text-capitalize">{{ $holiday->holiday_type }}</td>
                                    <td>{{ $holiday->is_paid ? 'Yes' : 'No' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center py-4 text-muted">No upcoming holidays.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-7">
            <div class="progga-card h-100">
                <div class="progga-card-header">
                    <div class="progga-card-title">Recent Employees</div>
                    <a href="{{ route('hr.employees.index') }}" class="progga-btn progga-btn-outline progga-btn-sm">View All</a>
                </div>
                <div class="progga-card-body p-0">
                    <div class="table-responsive">
                        <table class="progga-table mb-0">
                            <thead><tr><th>Employee</th><th>Designation</th><th>Department</th><th>Joined</th><th>Status</th></tr></thead>
                            <tbody>
                            @forelse($recentEmployees as $employee)
                                <tr>
                                    <td><a class="text-decoration-none fw-semibold" href="{{ route('hr.employees.show', $employee) }}">{{ $employee->full_name }}</a><br><small class="text-muted">{{ $employee->employee_code }}</small></td>
                                    <td>{{ $employee->designation?->name ?? '—' }}</td>
                                    <td>{{ $employee->department?->name ?? '—' }}</td>
                                    <td>{{ $employee->join_date?->format('d M Y') }}</td>
                                    <td><span class="badge text-bg-{{ $employee->status === 'active' ? 'success' : 'secondary' }} text-capitalize">{{ str_replace('_', ' ', $employee->status) }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center py-4 text-muted">No employees found.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-5">
            <div class="progga-card h-100">
                <div class="progga-card-header"><div class="progga-card-title">Pending Leave Requests</div></div>
                <div class="progga-card-body">
                    @forelse($pendingLeaves as $leave)
                        <div class="d-flex justify-content-between gap-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                            <div>
                                <div class="fw-semibold">{{ $leave->employee?->full_name }}</div>
                                <div class="small text-muted">{{ $leave->leaveType?->name }} · {{ $leave->total_days }} day(s)</div>
                            </div>
                            <div class="text-end small">
                                <div>{{ $leave->start_date->format('d M') }}</div>
                                <span class="badge text-bg-warning">Pending</span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-4">No pending leave requests.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
