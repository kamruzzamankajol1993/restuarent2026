@extends('admin.master.master')
@section('title', 'HR Reports')
@section('body')
<main class="progga-content">
    <div class="d-flex justify-content-between align-items-center gap-2 mb-4"><div><h4 class="fw-bold mb-1">HR Reports</h4><div class="text-muted small">Employee, attendance, roster, leave, payroll and loan reports</div></div><a href="{{ route('hr.reports.export', request()->query()) }}" class="progga-btn progga-btn-outline"><i class="bi bi-file-earmark-excel"></i> Export Excel</a></div>
    @include('admin.hr.partials.alerts')
    <div class="progga-card mb-4"><div class="progga-card-body"><form method="GET" class="row g-3 align-items-end">
        <div class="col-md-3"><label class="form-label">Report Type</label><select name="report_type" class="form-select">@foreach(['attendance'=>'Attendance','employee'=>'Employee','roster'=>'Duty Roster','leave'=>'Leave','payroll'=>'Payroll','loan'=>'Salary Advance / Loan'] as $value=>$label)<option value="{{ $value }}" @selected(request('report_type','attendance')===$value)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label">From Date</label><input type="text" name="from_date" class="form-control hr-datepicker" value="{{ request('from_date',now()->startOfMonth()->toDateString()) }}" autocomplete="off" placeholder="DD-MM-YYYY"></div><div class="col-md-2"><label class="form-label">To Date</label><input type="text" name="to_date" class="form-control hr-datepicker" value="{{ request('to_date',now()->toDateString()) }}" autocomplete="off" placeholder="DD-MM-YYYY"></div>
        <div class="col-md-2"><label class="form-label">Employee</label><select name="employee_id" class="form-select"><option value="">All employees</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected(request('employee_id')==$employee->id)>{{ $employee->employee_code }} - {{ $employee->full_name }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label">Department</label><select name="department_id" class="form-select"><option value="">All departments</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected(request('department_id')==$department->id)>{{ $department->name }}</option>@endforeach</select></div>
        <div class="col-md-1"><button class="progga-btn progga-btn-primary w-100">View</button></div>
    </form></div></div>

    <div class="progga-card"><div class="progga-card-header"><div><div class="progga-card-title">{{ $title }}</div><div class="progga-card-subtitle">{{ count($rows) }} record(s)</div></div><button class="progga-btn progga-btn-outline progga-btn-sm" onclick="window.print()"><i class="bi bi-printer"></i> Print</button></div><div class="table-responsive"><table class="progga-table mb-0"><thead><tr>@foreach($headings as $heading)<th>{{ $heading }}</th>@endforeach</tr></thead><tbody>@forelse($rows as $row)<tr>@foreach($row as $value)<td>{{ $value === null || $value === '' ? '—' : $value }}</td>@endforeach</tr>@empty<tr><td colspan="{{ count($headings) }}" class="text-center py-5 text-muted">No report data found for the selected filters.</td></tr>@endforelse</tbody></table></div></div>
</main>
@endsection
