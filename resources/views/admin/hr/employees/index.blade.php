@extends('admin.master.master')
@section('title', 'Employees')

@section('css')
<style>
    .employee-avatar { width: 42px; height: 42px; object-fit: cover; border-radius: 50%; background: #eef1ef; }
    .employee-stat { min-height: 112px; }
    .employee-stat .value { font-size: 25px; font-weight: 800; color: #21352a; }
</style>
@endsection

@section('body')
<main class="progga-content">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h4 class="mb-1 fw-bold">Employees</h4>
            <div class="text-muted small">Employee master data, user login and waiter profile links</div>
        </div>
        @can('employee-create')
            <a href="{{ route('hr.employees.create') }}" class="progga-btn progga-btn-primary"><i class="bi bi-person-plus-fill"></i> Add Employee</a>
        @endcan
    </div>

    @include('admin.hr.partials.alerts')

    <div class="row g-3 mb-4">
        @foreach([
            ['label'=>'Total Employees','value'=>$stats['total'],'icon'=>'bi-people-fill'],
            ['label'=>'Active Employees','value'=>$stats['active'],'icon'=>'bi-person-check-fill'],
            ['label'=>'Login Accounts','value'=>$stats['with_login'],'icon'=>'bi-shield-lock-fill'],
            ['label'=>'Waiter Profiles','value'=>$stats['waiters'],'icon'=>'bi-person-badge-fill'],
        ] as $item)
            <div class="col-6 col-xl-3">
                <div class="progga-card employee-stat h-100">
                    <div class="progga-card-body d-flex justify-content-between align-items-center gap-3">
                        <div><div class="small text-muted mb-2">{{ $item['label'] }}</div><div class="value">{{ number_format($item['value']) }}</div></div>
                        <i class="bi {{ $item['icon'] }} fs-3" style="color:#21352a"></i>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="progga-card mb-4">
        <div class="progga-card-body">
            <form method="GET" action="{{ route('hr.employees.index') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="progga-form-control" placeholder="Name, code, phone or email">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Department</label>
                    <select name="department_id" class="progga-form-control">
                        <option value="">All</option>
                        @foreach($departments as $department)<option value="{{ $department->id }}" @selected((string)request('department_id') === (string)$department->id)>{{ $department->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Designation</label>
                    <select name="designation_id" class="progga-form-control">
                        <option value="">All</option>
                        @foreach($designations as $designation)<option value="{{ $designation->id }}" @selected((string)request('designation_id') === (string)$designation->id)>{{ $designation->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Status</label>
                    <select name="status" class="progga-form-control">
                        <option value="">All</option>
                        @foreach(['active','inactive','probation','on_leave','resigned','terminated'] as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucwords(str_replace('_',' ',$status)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button class="progga-btn progga-btn-primary flex-fill" type="submit"><i class="bi bi-funnel-fill"></i> Filter</button>
                    <a href="{{ route('hr.employees.index') }}" class="progga-btn progga-btn-outline"><i class="bi bi-arrow-counterclockwise"></i></a>
                </div>
            </form>
        </div>
    </div>

    <div class="progga-card">
        <div class="progga-card-body p-0">
            <div class="table-responsive">
                <table class="progga-table mb-0">
                    <thead><tr><th>Employee</th><th>Department / Designation</th><th>Employment</th><th>Shift</th><th>Links</th><th>Status</th><th class="text-end">Action</th></tr></thead>
                    <tbody>
                    @forelse($employees as $employee)
                        @php
                            $statusClass = match($employee->status) {
                                'active'=>'success','probation'=>'warning','on_leave'=>'info','terminated'=>'danger','resigned'=>'secondary',default=>'secondary'
                            };
                        @endphp
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if($employee->image)
                                        <img class="employee-avatar" src="{{ asset('public/'.$employee->image) }}" alt="{{ $employee->full_name }}">
                                    @else
                                        <span class="employee-avatar d-inline-flex justify-content-center align-items-center fw-bold">{{ strtoupper(substr($employee->first_name,0,1)) }}</span>
                                    @endif
                                    <div>
                                        <a class="fw-semibold text-decoration-none" href="{{ route('hr.employees.show', $employee) }}">{{ $employee->full_name }}</a>
                                        <div class="small text-muted">{{ $employee->employee_code }} · {{ $employee->phone ?: 'No phone' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td><div class="fw-semibold">{{ $employee->department?->name ?? '—' }}</div><small class="text-muted">{{ $employee->designation?->name ?? 'No designation' }}</small></td>
                            <td>{{ $employee->employmentType?->name ?? '—' }}<br><small class="text-muted">Joined {{ $employee->join_date?->format('d M Y') }}</small></td>
                            <td>{{ $employee->defaultShift?->name ?? '—' }}</td>
                            <td>
                                @if($employee->user_id)<span class="badge text-bg-primary me-1">User</span>@endif
                                @if($employee->waiter_id)<span class="badge text-bg-dark">Waiter</span>@endif
                                @if(!$employee->user_id && !$employee->waiter_id)<span class="text-muted small">Employee only</span>@endif
                            </td>
                            <td><span class="badge text-bg-{{ $statusClass }} text-capitalize">{{ str_replace('_',' ',$employee->status) }}</span></td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('hr.employees.show', $employee) }}" class="progga-btn progga-btn-outline progga-btn-sm" title="View"><i class="bi bi-eye"></i></a>
                                @can('employee-edit')<a href="{{ route('hr.employees.edit', $employee) }}" class="progga-btn progga-btn-outline progga-btn-sm" title="Edit"><i class="bi bi-pencil-square"></i></a>@endcan
                                @can('employee-delete')
                                    <form action="{{ route('hr.employees.destroy', $employee) }}" method="POST" class="d-inline" onsubmit="return confirm('Archive this employee? User and waiter history will be preserved.');">
                                        @csrf @method('DELETE')
                                        <button class="progga-btn progga-btn-outline progga-btn-sm text-danger" type="submit" title="Archive"><i class="bi bi-archive"></i></button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-5 text-muted">No employees matched your filters.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($employees->hasPages())
            <div class="progga-card-footer">{{ $employees->links() }}</div>
        @endif
    </div>
</main>
@endsection
