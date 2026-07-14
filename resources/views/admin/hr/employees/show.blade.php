@extends('admin.master.master')
@section('title', 'Employee Profile')

@section('css')
<style>
    .profile-photo { width: 120px; height: 120px; object-fit: cover; border-radius: 18px; background: #eef1ef; }
    .info-label { color: #6c757d; font-size: 12px; margin-bottom: 3px; }
    .info-value { font-weight: 600; color: #26332c; word-break: break-word; }
</style>
@endsection

@section('body')
<main class="progga-content">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div><h4 class="mb-1 fw-bold">Employee Profile</h4><div class="text-muted small">{{ $employee->employee_code }}</div></div>
        <div class="d-flex gap-2">
            <a href="{{ route('hr.employees.index') }}" class="progga-btn progga-btn-outline"><i class="bi bi-arrow-left"></i> Employees</a>
            @can('employee-edit')<a href="{{ route('hr.employees.edit', $employee) }}" class="progga-btn progga-btn-primary"><i class="bi bi-pencil-square"></i> Edit</a>@endcan
        </div>
    </div>

    @include('admin.hr.partials.alerts')

    <div class="row g-4">
        <div class="col-xl-4">
            <div class="progga-card mb-4">
                <div class="progga-card-body text-center p-4">
                    @if($employee->image)
                        <img class="profile-photo mb-3" src="{{ asset('public/'.$employee->image) }}" alt="{{ $employee->full_name }}">
                    @else
                        <span class="profile-photo d-inline-flex align-items-center justify-content-center fs-1 fw-bold mb-3">{{ strtoupper(substr($employee->first_name, 0, 1)) }}</span>
                    @endif
                    <h4 class="mb-1">{{ $employee->full_name }}</h4>
                    <div class="text-muted">{{ $employee->designation?->name ?? 'No designation' }}</div>
                    <div class="mt-3"><span class="badge text-bg-{{ $employee->status === 'active' ? 'success' : 'secondary' }} text-capitalize">{{ str_replace('_',' ',$employee->status) }}</span></div>
                </div>
                <div class="progga-card-body border-top">
                    <div class="row g-3">
                        <div class="col-6"><div class="info-label">Department</div><div class="info-value">{{ $employee->department?->name ?? '—' }}</div></div>
                        <div class="col-6"><div class="info-label">Employment Type</div><div class="info-value">{{ $employee->employmentType?->name ?? '—' }}</div></div>
                        <div class="col-6"><div class="info-label">Default Shift</div><div class="info-value">{{ $employee->defaultShift?->name ?? '—' }}</div></div>
                        <div class="col-6"><div class="info-label">Supervisor</div><div class="info-value">{{ $employee->supervisor?->full_name ?? '—' }}</div></div>
                    </div>
                </div>
            </div>

            @can('employee-edit')
            <div class="progga-card mb-4">
                <div class="progga-card-header"><div class="progga-card-title">Update Status</div></div>
                <div class="progga-card-body">
                    <form method="POST" action="{{ route('hr.employees.status', $employee) }}">
                        @csrf @method('PATCH')
                        <div class="d-flex gap-2">
                            <select name="status" class="progga-form-control">
                                @foreach(['active','inactive','probation','on_leave','resigned','terminated'] as $status)<option value="{{ $status }}" @selected($employee->status === $status)>{{ ucwords(str_replace('_',' ',$status)) }}</option>@endforeach
                            </select>
                            <button class="progga-btn progga-btn-primary" type="submit">Save</button>
                        </div>
                    </form>
                </div>
            </div>
            @endcan

            <div class="progga-card">
                <div class="progga-card-header"><div class="progga-card-title">System Links</div></div>
                <div class="progga-card-body">
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span><i class="bi bi-shield-lock me-2"></i>User Login</span>
                        @if($employee->user)<span class="badge text-bg-success">Linked</span>@else<span class="badge text-bg-secondary">Not linked</span>@endif
                    </div>
                    @if($employee->user)
                        <div class="small text-muted py-2 border-bottom">{{ $employee->user->email }}<br>Role: {{ $employee->user->roles->pluck('name')->join(', ') ?: 'No role' }}</div>
                    @endif
                    <div class="d-flex justify-content-between align-items-center py-2">
                        <span><i class="bi bi-person-badge me-2"></i>Waiter Profile</span>
                        @if($employee->waiter)<span class="badge text-bg-success">Linked</span>@else<span class="badge text-bg-secondary">Not linked</span>@endif
                    </div>
                    @if($employee->waiter)<div class="small text-muted">Zone: {{ $employee->waiter->zone?->name ?? '—' }}</div>@endif
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="progga-card mb-4">
                <div class="progga-card-header"><div class="progga-card-title">Personal Details</div></div>
                <div class="progga-card-body">
                    <div class="row g-4">
                        @foreach([
                            ['Phone',$employee->phone],['Email',$employee->email],['Date of Birth',$employee->date_of_birth?->format('d M Y')],['Gender',$employee->gender ? ucfirst($employee->gender) : null],
                            ['Blood Group',$employee->blood_group],['NID Number',$employee->nid_number],['Passport Number',$employee->passport_number],['Join Date',$employee->join_date?->format('d M Y')],['Confirmation Date',$employee->confirmation_date?->format('d M Y')],['Resignation Date',$employee->resignation_date?->format('d M Y')],['Termination Date',$employee->termination_date?->format('d M Y')],
                            ['Emergency Contact',$employee->emergency_contact_name],['Emergency Phone',$employee->emergency_contact_phone],['Relationship',$employee->emergency_contact_relation],['Payment Method',$employee->payment_method ? ucwords(str_replace('_',' ',$employee->payment_method)) : null],
                        ] as [$label,$value])
                            <div class="col-md-4"><div class="info-label">{{ $label }}</div><div class="info-value">{{ $value ?: '—' }}</div></div>
                        @endforeach
                        <div class="col-md-6"><div class="info-label">Present Address</div><div class="info-value">{{ $employee->present_address ?: '—' }}</div></div>
                        <div class="col-md-6"><div class="info-label">Permanent Address</div><div class="info-value">{{ $employee->permanent_address ?: '—' }}</div></div>
                    </div>
                </div>
            </div>

            <div class="progga-card mb-4">
                <div class="progga-card-header"><div><div class="progga-card-title">Employee Documents</div><div class="progga-card-subtitle">NID, contract, certificate and other files</div></div></div>
                @can('employee-edit')
                <div class="progga-card-body border-bottom">
                    <form method="POST" action="{{ route('hr.employees.documents.store', $employee) }}" enctype="multipart/form-data" class="row g-3 align-items-end">
                        @csrf
                        <div class="col-md-3"><label class="form-label small fw-semibold">Document Type</label><input type="text" name="document_type" class="progga-form-control" placeholder="NID / Contract" required></div>
                        <div class="col-md-3"><label class="form-label small fw-semibold">Document Number</label><input type="text" name="document_number" class="progga-form-control"></div>
                        <div class="col-md-3"><label class="form-label small fw-semibold">Issue Date</label><input type="date" name="issue_date" class="progga-form-control"></div>
                        <div class="col-md-3"><label class="form-label small fw-semibold">Expiry Date</label><input type="date" name="expiry_date" class="progga-form-control"></div>
                        <div class="col-md-5"><label class="form-label small fw-semibold">File</label><input type="file" name="document_file" class="progga-form-control" required></div>
                        <div class="col-md-5"><label class="form-label small fw-semibold">Notes</label><input type="text" name="document_notes" class="progga-form-control"></div>
                        <div class="col-md-2"><button class="progga-btn progga-btn-primary w-100" type="submit"><i class="bi bi-upload"></i> Upload</button></div>
                    </form>
                </div>
                @endcan
                <div class="progga-card-body p-0">
                    <div class="table-responsive">
                        <table class="progga-table mb-0">
                            <thead><tr><th>Type</th><th>Number</th><th>Issue</th><th>Expiry</th><th>File</th><th class="text-end">Action</th></tr></thead>
                            <tbody>
                            @forelse($employee->documents as $document)
                                <tr>
                                    <td class="fw-semibold">{{ $document->document_type }}</td>
                                    <td>{{ $document->document_number ?: '—' }}</td>
                                    <td>{{ $document->issue_date?->format('d M Y') ?? '—' }}</td>
                                    <td>{{ $document->expiry_date?->format('d M Y') ?? '—' }}</td>
                                    <td><a href="{{ asset('public/'.$document->file_path) }}" target="_blank" class="text-decoration-none"><i class="bi bi-box-arrow-up-right"></i> Open</a></td>
                                    <td class="text-end">
                                        @can('employee-delete')
                                            <form method="POST" action="{{ route('hr.employees.documents.destroy', [$employee, $document]) }}" class="d-inline" onsubmit="return confirm('Delete this document?');">@csrf @method('DELETE')<button class="progga-btn progga-btn-outline progga-btn-sm text-danger"><i class="bi bi-trash"></i></button></form>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center py-4 text-muted">No documents uploaded.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="progga-card">
                <div class="progga-card-header"><div><div class="progga-card-title">Salary Components</div><div class="progga-card-subtitle">Employee-specific earnings and deductions</div></div></div>
                @can('employee-edit')
                <div class="progga-card-body border-bottom">
                    <form id="employeeSalaryForm" method="POST" action="{{ route('hr.employees.salary-components.store', $employee) }}" class="row g-3 align-items-end">
                        @csrf
                        <input type="hidden" name="_method" id="employeeSalaryMethod" value="POST">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Salary Component *</label>
                            <select id="employeeSalaryComponent" name="salary_component_id" class="progga-form-control" required>
                                <option value="">Select component</option>
                                @foreach($salaryComponents as $salaryComponent)
                                    <option value="{{ $salaryComponent->id }}">{{ $salaryComponent->name }} · {{ ucfirst($salaryComponent->component_type) }} ({{ ucfirst($salaryComponent->calculation_type) }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2"><label class="form-label small fw-semibold">Amount</label><input id="employeeSalaryAmount" type="number" step="0.01" min="0" name="amount" class="progga-form-control"></div>
                        <div class="col-md-2"><label class="form-label small fw-semibold">Percentage</label><input id="employeeSalaryPercentage" type="number" step="0.0001" min="0" max="100" name="percentage" class="progga-form-control"></div>
                        <div class="col-md-2"><label class="form-label small fw-semibold">Effective From *</label><input id="employeeSalaryFrom" type="date" name="effective_from" class="progga-form-control" value="{{ now()->format('Y-m-d') }}" required></div>
                        <div class="col-md-2"><label class="form-label small fw-semibold">Effective To</label><input id="employeeSalaryTo" type="date" name="effective_to" class="progga-form-control"></div>
                        <div class="col-md-8 d-flex flex-wrap align-items-center gap-3">
                            <label class="form-check mb-0"><input id="employeeSalaryStatus" class="form-check-input" type="checkbox" name="status" value="1" checked> Active</label>
                            <span class="small text-muted">Fixed components require an amount; percentage components require a percentage.</span>
                        </div>
                        <div class="col-md-4 d-flex gap-2">
                            <button id="employeeSalarySubmit" class="progga-btn progga-btn-primary flex-fill" type="submit"><i class="bi bi-plus-circle"></i> Assign</button>
                            <button id="employeeSalaryCancel" type="button" class="progga-btn progga-btn-outline d-none" onclick="resetEmployeeSalaryForm()">Cancel</button>
                        </div>
                    </form>
                </div>
                @endcan
                <div class="progga-card-body p-0">
                    <div class="table-responsive">
                        <table class="progga-table mb-0">
                            <thead><tr><th>Component</th><th>Type</th><th>Amount</th><th>Percentage</th><th>Effective Period</th><th>Status</th><th class="text-end">Action</th></tr></thead>
                            <tbody>
                            @forelse($employee->salaryComponents->sortByDesc('effective_from') as $component)
                                <tr>
                                    <td class="fw-semibold">{{ $component->salaryComponent?->name ?? '—' }}</td>
                                    <td class="text-capitalize">{{ $component->salaryComponent?->component_type ?? '—' }}</td>
                                    <td>{{ $component->amount !== null ? number_format((float)$component->amount,2) : '—' }}</td>
                                    <td>{{ $component->percentage !== null ? rtrim(rtrim(number_format((float)$component->percentage,4,'.',''),'0'),'.').'%' : '—' }}</td>
                                    <td>{{ $component->effective_from?->format('d M Y') ?? '—' }} – {{ $component->effective_to?->format('d M Y') ?? 'Ongoing' }}</td>
                                    <td><span class="badge text-bg-{{ $component->status ? 'success' : 'secondary' }}">{{ $component->status ? 'Active' : 'Inactive' }}</span></td>
                                    <td class="text-end">
                                        @can('employee-edit')
                                            <button type="button" class="progga-btn progga-btn-outline progga-btn-sm" data-item='{{ json_encode(["id"=>$component->id,"salary_component_id"=>$component->salary_component_id,"amount"=>$component->amount,"percentage"=>$component->percentage,"effective_from"=>$component->effective_from?->format("Y-m-d"),"effective_to"=>$component->effective_to?->format("Y-m-d"),"status"=>(int)$component->status]) }}' onclick="editEmployeeSalaryComponent(JSON.parse(this.dataset.item))"><i class="bi bi-pencil"></i></button>
                                            <form method="POST" action="{{ route('hr.employees.salary-components.destroy', [$employee, $component]) }}" class="d-inline" onsubmit="return confirm('Delete this salary component assignment?');">@csrf @method('DELETE')<button type="submit" class="progga-btn progga-btn-outline progga-btn-sm text-danger"><i class="bi bi-trash"></i></button></form>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center py-4 text-muted">No salary components assigned.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection

@section('script')
<script>
function editEmployeeSalaryComponent(item) {
    const form = document.getElementById('employeeSalaryForm');
    if (!form) return;
    form.action = @json(route('hr.employees.salary-components.store', $employee)) + '/' + item.id;
    document.getElementById('employeeSalaryMethod').value = 'PUT';
    document.getElementById('employeeSalaryComponent').value = item.salary_component_id || '';
    document.getElementById('employeeSalaryAmount').value = item.amount ?? '';
    document.getElementById('employeeSalaryPercentage').value = item.percentage ?? '';
    document.getElementById('employeeSalaryFrom').value = item.effective_from || '';
    document.getElementById('employeeSalaryTo').value = item.effective_to || '';
    document.getElementById('employeeSalaryStatus').checked = !!item.status;
    document.getElementById('employeeSalarySubmit').innerHTML = '<i class="bi bi-check-circle"></i> Update';
    document.getElementById('employeeSalaryCancel').classList.remove('d-none');
    form.scrollIntoView({ behavior: 'smooth', block: 'center' });
}
function resetEmployeeSalaryForm() {
    const form = document.getElementById('employeeSalaryForm');
    if (!form) return;
    form.reset();
    form.action = @json(route('hr.employees.salary-components.store', $employee));
    document.getElementById('employeeSalaryMethod').value = 'POST';
    document.getElementById('employeeSalaryFrom').value = @json(now()->format('Y-m-d'));
    document.getElementById('employeeSalaryStatus').checked = true;
    document.getElementById('employeeSalarySubmit').innerHTML = '<i class="bi bi-plus-circle"></i> Assign';
    document.getElementById('employeeSalaryCancel').classList.add('d-none');
}
</script>
@endsection
