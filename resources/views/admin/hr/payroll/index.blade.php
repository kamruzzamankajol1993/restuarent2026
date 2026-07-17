@extends('admin.master.master')
@section('title', 'Payroll')
@section('body')
<main class="progga-content">
    <div class="mb-4"><h4 class="fw-bold mb-1">Payroll</h4><div class="text-muted small">Create payroll periods, calculate salaries, approve payroll and track payments</div></div>
    @include('admin.hr.partials.alerts')

    @can('hr-setting-manage')
    <div class="progga-card mb-4"><div class="progga-card-header"><div class="progga-card-title">Create Payroll Period</div></div><div class="progga-card-body"><form method="POST" action="{{ route('hr.payroll.periods.store') }}" class="row g-3 align-items-end">@csrf
        <div class="col-md-3"><label class="form-label">Period Name *</label><input type="text" name="name" class="form-control" value="{{ now()->format('F Y') }}" required></div>
        <div class="col-md-2"><label class="form-label">Start Date *</label><input type="text" name="start_date" class="form-control hr-datepicker" value="{{ now()->startOfMonth()->toDateString() }}" required autocomplete="off" placeholder="DD-MM-YYYY"></div>
        <div class="col-md-2"><label class="form-label">End Date *</label><input type="text" name="end_date" class="form-control hr-datepicker" value="{{ now()->endOfMonth()->toDateString() }}" required autocomplete="off" placeholder="DD-MM-YYYY"></div>
        <div class="col-md-2"><label class="form-label">Payment Date</label><input type="text" name="payment_date" class="form-control hr-datepicker" value="{{ now()->endOfMonth()->addDays($settings->payment_day ?? 7)->toDateString() }}" autocomplete="off" placeholder="DD-MM-YYYY"></div>
        <div class="col-md-3"><button class="progga-btn progga-btn-primary"><i class="bi bi-calendar-plus"></i> Create Period</button></div>
    </form></div></div>
    @endcan

    <div class="progga-card"><div class="progga-card-header"><div><div class="progga-card-title">Payroll Periods</div><div class="progga-card-subtitle">Currency: {{ $settings->currency }} · Calculation: {{ ucwords(str_replace('_',' ',$settings->salary_calculation_basis)) }}</div></div></div>
        <div class="progga-card-body border-bottom"><form method="GET" class="row g-2"><div class="col-md-3"><select name="status" class="form-select"><option value="">All statuses</option>@foreach(['draft','generated','approved','locked'] as $status)<option value="{{ $status }}" @selected(request('status')==$status)>{{ ucfirst($status) }}</option>@endforeach</select></div><div class="col-md-2"><button class="progga-btn progga-btn-primary w-100">Filter</button></div></form></div>
        <div class="table-responsive"><table class="progga-table mb-0"><thead><tr><th>Period</th><th>Date Range</th><th>Payment Date</th><th>Employees</th><th>Total Net</th><th>Paid</th><th>Status</th><th>Action</th></tr></thead><tbody>
            @forelse($periods as $period)<tr><td><strong>{{ $period->name }}</strong></td><td>{{ $period->start_date->format('d-m-Y') }} - {{ $period->end_date->format('d-m-Y') }}</td><td>{{ $period->payment_date?->format('d-m-Y') ?? '—' }}</td><td>{{ $period->payrolls_count }}</td><td>{{ number_format($period->payrolls_sum_net_salary ?? 0,2) }}</td><td>{{ number_format($period->payrolls_sum_paid_amount ?? 0,2) }}</td><td><span class="badge text-bg-{{ in_array($period->status,['approved','locked'])?'success':($period->status==='generated'?'info':'secondary') }}">{{ ucfirst($period->status) }}</span></td><td><a href="{{ route('hr.payroll.show',$period) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i> Open</a></td></tr>
            @empty<tr><td colspan="8" class="text-center py-5 text-muted">No payroll periods found.</td></tr>@endforelse
        </tbody></table></div><div class="progga-card-body">{{ $periods->links() }}</div>
    </div>
</main>
@endsection
