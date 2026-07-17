@extends('admin.master.master')
@section('title', 'HR Settings')

@section('css')
<style>
    .hr-settings-nav .nav-link { color:#435047; font-weight:700; border-radius:8px; padding:10px 14px; }
    .hr-settings-nav .nav-link.active { background:#21352a; color:#fff; }
    .settings-form-box { background:rgba(33,53,42,.035); border:1px solid rgba(33,53,42,.09); border-radius:10px; padding:18px; }
</style>
@endsection

@section('body')
<main class="progga-content">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div><h4 class="mb-1 fw-bold">HR Settings</h4><div class="text-muted small">Organization, attendance, leave and salary master configuration</div></div>
        <a href="{{ route('hr.dashboard') }}" class="progga-btn progga-btn-outline"><i class="bi bi-speedometer2"></i> HR Dashboard</a>
    </div>

    @include('admin.hr.partials.alerts')

    @cannot('hr-setting-manage')
        <div class="alert alert-info"><i class="bi bi-info-circle-fill me-2"></i>You have read-only access to HR Settings.</div>
    @endcannot

    <div class="progga-card">
        <div class="progga-card-body border-bottom">
            <ul class="nav nav-pills hr-settings-nav gap-2" role="tablist">
                @foreach([
                    'departments'=>'Departments','designations'=>'Designations','employment-types'=>'Employment Types','shifts'=>'Shifts','leave-types'=>'Leave Types','holidays'=>'Holidays','salary-components'=>'Salary Components','attendance-rules'=>'Attendance Rules','payroll-settings'=>'Payroll Settings'
                ] as $key=>$label)
                    <li class="nav-item"><a class="nav-link {{ $activeTab === $key ? 'active' : '' }}" href="{{ route('hr.settings.index', ['tab'=>$key]) }}">{{ $label }}</a></li>
                @endforeach
            </ul>
        </div>

        <div class="progga-card-body">
            @if($activeTab === 'departments')
                <div class="settings-form-box mb-4 {{ auth()->user()->can('hr-setting-manage') ? '' : 'd-none' }}">
                    <h6 class="fw-bold mb-3" id="departmentFormTitle">Add Department</h6>
                    <form id="departmentForm" method="POST" action="{{ route('hr.settings.departments.store') }}" class="row g-3 align-items-end">
                        @csrf <input type="hidden" name="_method" id="departmentMethod" value="POST">
                        <div class="col-md-3"><label class="form-label small fw-semibold">Name *</label><input id="departmentName" name="name" class="progga-form-control" required></div>
                        <div class="col-md-2"><label class="form-label small fw-semibold">Code</label><input id="departmentCode" name="code" class="progga-form-control" placeholder="Auto if blank"></div>
                        <div class="col-md-3"><label class="form-label small fw-semibold">Manager</label><select id="departmentManager" name="manager_employee_id" class="progga-form-control"><option value="">No manager</option>@foreach($activeEmployees as $employee)<option value="{{ $employee->id }}">{{ $employee->full_name }}</option>@endforeach</select></div>
                        <div class="col-md-2"><label class="form-label small fw-semibold">Status</label><select id="departmentStatus" name="status" class="progga-form-control"><option value="1">Active</option><option value="0">Inactive</option></select></div>
                        <div class="col-md-2 d-flex gap-2"><button id="departmentSubmit" class="progga-btn progga-btn-primary flex-fill">Save</button><button type="button" id="departmentCancel" class="progga-btn progga-btn-outline d-none" onclick="resetDepartmentForm()">Cancel</button></div>
                        <div class="col-12"><label class="form-label small fw-semibold">Description</label><textarea id="departmentDescription" name="description" class="progga-form-control" rows="2"></textarea></div>
                    </form>
                </div>
                <div class="table-responsive"><table class="progga-table"><thead><tr><th>Name</th><th>Code</th><th>Manager</th><th>Employees</th><th>Status</th><th class="text-end">Action</th></tr></thead><tbody>
                    @forelse($departments as $item)<tr><td class="fw-semibold">{{ $item->name }}</td><td>{{ $item->code }}</td><td>{{ $item->manager?->full_name ?? '—' }}</td><td>{{ $item->employees_count }}</td><td><span class="badge text-bg-{{ $item->status?'success':'secondary' }}">{{ $item->status?'Active':'Inactive' }}</span></td><td class="text-end"><button type="button" class="progga-btn progga-btn-outline progga-btn-sm {{ auth()->user()->can('hr-setting-manage') ? '' : 'd-none' }}" data-item='{{ json_encode(["id"=>$item->id,"name"=>$item->name,"code"=>$item->code,"manager"=>$item->manager_employee_id,"description"=>$item->description,"status"=>(int)$item->status]) }}' onclick="editDepartment(JSON.parse(this.dataset.item))"><i class="bi bi-pencil"></i></button> @include('admin.hr.settings.partials.delete-button',['action'=>route('hr.settings.departments.destroy',$item),'label'=>'department'])</td></tr>@empty<tr><td colspan="6" class="text-center py-4 text-muted">No departments configured.</td></tr>@endforelse
                </tbody></table></div>

            @elseif($activeTab === 'designations')
                <div class="settings-form-box mb-4 {{ auth()->user()->can('hr-setting-manage') ? '' : 'd-none' }}">
                    <h6 class="fw-bold mb-3" id="designationFormTitle">Add Designation</h6>
                    <form id="designationForm" method="POST" action="{{ route('hr.settings.designations.store') }}" class="row g-3 align-items-end">
                        @csrf <input type="hidden" name="_method" id="designationMethod" value="POST">
                        <div class="col-md-3"><label class="form-label small fw-semibold">Name *</label><input id="designationName" name="name" class="progga-form-control" required></div>
                        <div class="col-md-2"><label class="form-label small fw-semibold">Code</label><input id="designationCode" name="code" class="progga-form-control" placeholder="Auto if blank"></div>
                        <div class="col-md-3"><label class="form-label small fw-semibold">Department</label><select id="designationDepartment" name="department_id" class="progga-form-control"><option value="">Any department</option>@foreach($departments as $department)<option value="{{ $department->id }}">{{ $department->name }}</option>@endforeach</select></div>
                        <div class="col-md-1"><label class="form-label small fw-semibold">Level</label><input id="designationLevel" type="number" min="1" name="level" class="progga-form-control"></div>
                        <div class="col-md-1"><label class="form-label small fw-semibold">Status</label><select id="designationStatus" name="status" class="progga-form-control"><option value="1">Active</option><option value="0">Inactive</option></select></div>
                        <div class="col-md-2 d-flex gap-2"><button id="designationSubmit" class="progga-btn progga-btn-primary flex-fill">Save</button><button type="button" id="designationCancel" class="progga-btn progga-btn-outline d-none" onclick="resetDesignationForm()">Cancel</button></div>
                        <div class="col-12"><label class="form-label small fw-semibold">Description</label><textarea id="designationDescription" name="description" class="progga-form-control" rows="2"></textarea></div>
                    </form>
                </div>
                <div class="table-responsive"><table class="progga-table"><thead><tr><th>Name</th><th>Code</th><th>Department</th><th>Level</th><th>Employees</th><th>Status</th><th class="text-end">Action</th></tr></thead><tbody>
                    @forelse($designations as $item)<tr><td class="fw-semibold">{{ $item->name }}</td><td>{{ $item->code }}</td><td>{{ $item->department?->name ?? 'Any' }}</td><td>{{ $item->level ?? '—' }}</td><td>{{ $item->employees_count }}</td><td><span class="badge text-bg-{{ $item->status?'success':'secondary' }}">{{ $item->status?'Active':'Inactive' }}</span></td><td class="text-end"><button type="button" class="progga-btn progga-btn-outline progga-btn-sm {{ auth()->user()->can('hr-setting-manage') ? '' : 'd-none' }}" data-item='{{ json_encode(["id"=>$item->id,"name"=>$item->name,"code"=>$item->code,"department"=>$item->department_id,"level"=>$item->level,"description"=>$item->description,"status"=>(int)$item->status]) }}' onclick="editDesignation(JSON.parse(this.dataset.item))"><i class="bi bi-pencil"></i></button> @include('admin.hr.settings.partials.delete-button',['action'=>route('hr.settings.designations.destroy',$item),'label'=>'designation'])</td></tr>@empty<tr><td colspan="7" class="text-center py-4 text-muted">No designations configured.</td></tr>@endforelse
                </tbody></table></div>

            @elseif($activeTab === 'employment-types')
                <div class="settings-form-box mb-4 {{ auth()->user()->can('hr-setting-manage') ? '' : 'd-none' }}">
                    <h6 class="fw-bold mb-3" id="employmentTypeFormTitle">Add Employment Type</h6>
                    <form id="employmentTypeForm" method="POST" action="{{ route('hr.settings.employment-types.store') }}" class="row g-3 align-items-end">
                        @csrf <input type="hidden" name="_method" id="employmentTypeMethod" value="POST">
                        <div class="col-md-3"><label class="form-label small fw-semibold">Name *</label><input id="employmentTypeName" name="name" class="progga-form-control" required></div>
                        <div class="col-md-2"><label class="form-label small fw-semibold">Code</label><input id="employmentTypeCode" name="code" class="progga-form-control" placeholder="Auto if blank"></div>
                        <div class="col-md-2"><label class="form-label small fw-semibold">Status</label><select id="employmentTypeStatus" name="status" class="progga-form-control"><option value="1">Active</option><option value="0">Inactive</option></select></div>
                        <div class="col-md-3"><label class="form-label small fw-semibold">Description</label><input id="employmentTypeDescription" name="description" class="progga-form-control"></div>
                        <div class="col-md-2 d-flex gap-2"><button id="employmentTypeSubmit" class="progga-btn progga-btn-primary flex-fill">Save</button><button type="button" id="employmentTypeCancel" class="progga-btn progga-btn-outline d-none" onclick="resetEmploymentTypeForm()">Cancel</button></div>
                    </form>
                </div>
                <div class="table-responsive"><table class="progga-table"><thead><tr><th>Name</th><th>Code</th><th>Description</th><th>Employees</th><th>Status</th><th class="text-end">Action</th></tr></thead><tbody>
                    @forelse($employmentTypes as $item)<tr><td class="fw-semibold">{{ $item->name }}</td><td>{{ $item->code }}</td><td>{{ $item->description ?: '—' }}</td><td>{{ $item->employees_count }}</td><td><span class="badge text-bg-{{ $item->status?'success':'secondary' }}">{{ $item->status?'Active':'Inactive' }}</span></td><td class="text-end"><button type="button" class="progga-btn progga-btn-outline progga-btn-sm {{ auth()->user()->can('hr-setting-manage') ? '' : 'd-none' }}" data-item='{{ json_encode(["id"=>$item->id,"name"=>$item->name,"code"=>$item->code,"description"=>$item->description,"status"=>(int)$item->status]) }}' onclick="editEmploymentType(JSON.parse(this.dataset.item))"><i class="bi bi-pencil"></i></button> @include('admin.hr.settings.partials.delete-button',['action'=>route('hr.settings.employment-types.destroy',$item),'label'=>'employment type'])</td></tr>@empty<tr><td colspan="6" class="text-center py-4 text-muted">No employment types configured.</td></tr>@endforelse
                </tbody></table></div>

            @elseif($activeTab === 'shifts')
                <div class="settings-form-box mb-4 {{ auth()->user()->can('hr-setting-manage') ? '' : 'd-none' }}">
                    <h6 class="fw-bold mb-3" id="shiftFormTitle">Add Shift</h6>
                    <form id="shiftForm" method="POST" action="{{ route('hr.settings.shifts.store') }}" class="row g-3 align-items-end">
                        @csrf <input type="hidden" name="_method" id="shiftMethod" value="POST">
                        <div class="col-md-3"><label class="form-label small fw-semibold">Name *</label><input id="shiftName" name="name" class="progga-form-control" required></div>
                        <div class="col-md-2"><label class="form-label small fw-semibold">Code</label><input id="shiftCode" name="code" class="progga-form-control"></div>
                        <div class="col-md-2"><label class="form-label small fw-semibold">Start *</label><input id="shiftStart" type="time" name="start_time" class="progga-form-control" required></div>
                        <div class="col-md-2"><label class="form-label small fw-semibold">End *</label><input id="shiftEnd" type="time" name="end_time" class="progga-form-control" required></div>
                        <div class="col-md-1"><label class="form-label small fw-semibold">Break</label><input id="shiftBreak" type="number" min="0" name="break_minutes" value="0" class="progga-form-control"></div>
                        <div class="col-md-1"><label class="form-label small fw-semibold">Grace</label><input id="shiftGrace" type="number" min="0" name="grace_minutes" value="0" class="progga-form-control"></div>
                        <div class="col-md-1"><label class="form-label small fw-semibold">Status</label><select id="shiftStatus" name="status" class="progga-form-control"><option value="1">Active</option><option value="0">Inactive</option></select></div>
                        <div class="col-md-3"><label class="form-label small fw-semibold">Minimum Work Minutes</label><input id="shiftMinimum" type="number" min="0" name="minimum_work_minutes" class="progga-form-control"></div>
                        <div class="col-md-3"><label class="form-label small fw-semibold">Overtime After Minutes</label><input id="shiftOvertime" type="number" min="0" name="overtime_after_minutes" class="progga-form-control"></div>
                        <div class="col-md-2"><div class="form-check mt-4"><input id="shiftOvernight" class="form-check-input" type="checkbox" name="is_overnight" value="1"><label class="form-check-label">Overnight</label></div></div>
                        <div class="col-md-4 d-flex gap-2"><button id="shiftSubmit" class="progga-btn progga-btn-primary flex-fill">Save</button><button type="button" id="shiftCancel" class="progga-btn progga-btn-outline d-none" onclick="resetShiftForm()">Cancel</button></div>
                    </form>
                </div>
                <div class="table-responsive"><table class="progga-table"><thead><tr><th>Name</th><th>Time</th><th>Break / Grace</th><th>Overtime After</th><th>Employees</th><th>Status</th><th class="text-end">Action</th></tr></thead><tbody>
                    @forelse($shifts as $item)<tr><td class="fw-semibold">{{ $item->name }}<br><small class="text-muted">{{ $item->code }}</small></td><td>{{ $item->start_time ? \Carbon\Carbon::parse($item->start_time)->format('h:i A') : '—' }} – {{ $item->end_time ? \Carbon\Carbon::parse($item->end_time)->format('h:i A') : '—' }} @if($item->is_overnight)<span class="badge text-bg-dark">Overnight</span>@endif</td><td>{{ $item->break_minutes }} / {{ $item->grace_minutes }} min</td><td>{{ $item->overtime_after_minutes ?? '—' }}</td><td>{{ $item->default_employees_count }}</td><td><span class="badge text-bg-{{ $item->status?'success':'secondary' }}">{{ $item->status?'Active':'Inactive' }}</span></td><td class="text-end"><button type="button" class="progga-btn progga-btn-outline progga-btn-sm {{ auth()->user()->can('hr-setting-manage') ? '' : 'd-none' }}" data-item='{{ json_encode(["id"=>$item->id,"name"=>$item->name,"code"=>$item->code,"start"=>$item->start_time,"end"=>$item->end_time,"break"=>$item->break_minutes,"grace"=>$item->grace_minutes,"minimum"=>$item->minimum_work_minutes,"overtime"=>$item->overtime_after_minutes,"overnight"=>(int)$item->is_overnight,"status"=>(int)$item->status]) }}' onclick="editShift(JSON.parse(this.dataset.item))"><i class="bi bi-pencil"></i></button> @include('admin.hr.settings.partials.delete-button',['action'=>route('hr.settings.shifts.destroy',$item),'label'=>'shift'])</td></tr>@empty<tr><td colspan="7" class="text-center py-4 text-muted">No shifts configured.</td></tr>@endforelse
                </tbody></table></div>

            @elseif($activeTab === 'leave-types')
                <div class="settings-form-box mb-4 {{ auth()->user()->can('hr-setting-manage') ? '' : 'd-none' }}">
                    <h6 class="fw-bold mb-3" id="leaveTypeFormTitle">Add Leave Type</h6>
                    <form id="leaveTypeForm" method="POST" action="{{ route('hr.settings.leave-types.store') }}" class="row g-3 align-items-end">
                        @csrf <input type="hidden" name="_method" id="leaveTypeMethod" value="POST">
                        <div class="col-md-3"><label class="form-label small fw-semibold">Name *</label><input id="leaveTypeName" name="name" class="progga-form-control" required></div>
                        <div class="col-md-2"><label class="form-label small fw-semibold">Code</label><input id="leaveTypeCode" name="code" class="progga-form-control"></div>
                        <div class="col-md-2"><label class="form-label small fw-semibold">Annual Limit *</label><input id="leaveTypeLimit" type="number" step="0.5" min="0" name="annual_limit" value="0" class="progga-form-control" required></div>
                        <div class="col-md-2"><label class="form-label small fw-semibold">Max Carry</label><input id="leaveTypeMaxCarry" type="number" step="0.5" min="0" name="maximum_carry_forward" class="progga-form-control"></div>
                        <div class="col-md-3 d-flex gap-2"><button id="leaveTypeSubmit" class="progga-btn progga-btn-primary flex-fill">Save</button><button type="button" id="leaveTypeCancel" class="progga-btn progga-btn-outline d-none" onclick="resetLeaveTypeForm()">Cancel</button></div>
                        <div class="col-12 d-flex flex-wrap gap-4"><label class="form-check"><input id="leaveTypePaid" class="form-check-input" type="checkbox" name="is_paid" value="1" checked> Paid Leave</label><label class="form-check"><input id="leaveTypeCarry" class="form-check-input" type="checkbox" name="carry_forward_allowed" value="1"> Carry Forward</label><label class="form-check"><input id="leaveTypeDocument" class="form-check-input" type="checkbox" name="requires_document" value="1"> Requires Document</label><label class="form-check"><input id="leaveTypeStatus" class="form-check-input" type="checkbox" name="status" value="1" checked> Active</label></div>
                    </form>
                </div>
                <div class="table-responsive"><table class="progga-table"><thead><tr><th>Name</th><th>Code</th><th>Annual Limit</th><th>Paid</th><th>Carry Forward</th><th>Document</th><th>Status</th><th class="text-end">Action</th></tr></thead><tbody>
                    @forelse($leaveTypes as $item)<tr><td class="fw-semibold">{{ $item->name }}</td><td>{{ $item->code }}</td><td>{{ $item->annual_limit }}</td><td>{{ $item->is_paid?'Yes':'No' }}</td><td>{{ $item->carry_forward_allowed ? 'Yes ('.$item->maximum_carry_forward.')' : 'No' }}</td><td>{{ $item->requires_document?'Yes':'No' }}</td><td><span class="badge text-bg-{{ $item->status?'success':'secondary' }}">{{ $item->status?'Active':'Inactive' }}</span></td><td class="text-end"><button type="button" class="progga-btn progga-btn-outline progga-btn-sm {{ auth()->user()->can('hr-setting-manage') ? '' : 'd-none' }}" data-item='{{ json_encode(["id"=>$item->id,"name"=>$item->name,"code"=>$item->code,"limit"=>$item->annual_limit,"maxCarry"=>$item->maximum_carry_forward,"paid"=>(int)$item->is_paid,"carry"=>(int)$item->carry_forward_allowed,"document"=>(int)$item->requires_document,"status"=>(int)$item->status]) }}' onclick="editLeaveType(JSON.parse(this.dataset.item))"><i class="bi bi-pencil"></i></button> @include('admin.hr.settings.partials.delete-button',['action'=>route('hr.settings.leave-types.destroy',$item),'label'=>'leave type'])</td></tr>@empty<tr><td colspan="8" class="text-center py-4 text-muted">No leave types configured.</td></tr>@endforelse
                </tbody></table></div>

            @elseif($activeTab === 'holidays')
                <div class="settings-form-box mb-4 {{ auth()->user()->can('hr-setting-manage') ? '' : 'd-none' }}">
                    <h6 class="fw-bold mb-3" id="holidayFormTitle">Add Holiday</h6>
                    <form id="holidayForm" method="POST" action="{{ route('hr.settings.holidays.store') }}" class="row g-3 align-items-end">
                        @csrf <input type="hidden" name="_method" id="holidayMethod" value="POST">
                        <div class="col-md-3"><label class="form-label small fw-semibold">Name *</label><input id="holidayName" name="name" class="progga-form-control" required></div>
                        <div class="col-md-2"><label class="form-label small fw-semibold">Date *</label><input id="holidayDate" type="text" name="holiday_date" class="progga-form-control hr-datepicker" required autocomplete="off" placeholder="DD-MM-YYYY"></div>
                        <div class="col-md-2"><label class="form-label small fw-semibold">Type *</label><select id="holidayType" name="holiday_type" class="progga-form-control">@foreach(['government','festival','restaurant','special'] as $type)<option value="{{ $type }}">{{ ucfirst($type) }}</option>@endforeach</select></div>
                        <div class="col-md-3"><label class="form-label small fw-semibold">Description</label><input id="holidayDescription" name="description" class="progga-form-control"></div>
                        <div class="col-md-2 d-flex gap-2"><button id="holidaySubmit" class="progga-btn progga-btn-primary flex-fill">Save</button><button type="button" id="holidayCancel" class="progga-btn progga-btn-outline d-none" onclick="resetHolidayForm()">Cancel</button></div>
                        <div class="col-12 d-flex gap-4"><label class="form-check"><input id="holidayPaid" class="form-check-input" type="checkbox" name="is_paid" value="1" checked> Paid Holiday</label><label class="form-check"><input id="holidayStatus" class="form-check-input" type="checkbox" name="status" value="1" checked> Active</label></div>
                    </form>
                </div>
                <div class="table-responsive"><table class="progga-table"><thead><tr><th>Date</th><th>Name</th><th>Type</th><th>Paid</th><th>Description</th><th>Status</th><th class="text-end">Action</th></tr></thead><tbody>
                    @forelse($holidays as $item)<tr><td>{{ $item->holiday_date->format('d M Y') }}</td><td class="fw-semibold">{{ $item->name }}</td><td class="text-capitalize">{{ $item->holiday_type }}</td><td>{{ $item->is_paid?'Yes':'No' }}</td><td>{{ $item->description ?: '—' }}</td><td><span class="badge text-bg-{{ $item->status?'success':'secondary' }}">{{ $item->status?'Active':'Inactive' }}</span></td><td class="text-end"><button type="button" class="progga-btn progga-btn-outline progga-btn-sm {{ auth()->user()->can('hr-setting-manage') ? '' : 'd-none' }}" data-item='{{ json_encode(["id"=>$item->id,"name"=>$item->name,"date"=>$item->holiday_date->format("Y-m-d"),"type"=>$item->holiday_type,"paid"=>(int)$item->is_paid,"description"=>$item->description,"status"=>(int)$item->status]) }}' onclick="editHoliday(JSON.parse(this.dataset.item))"><i class="bi bi-pencil"></i></button> @include('admin.hr.settings.partials.delete-button',['action'=>route('hr.settings.holidays.destroy',$item),'label'=>'holiday'])</td></tr>@empty<tr><td colspan="7" class="text-center py-4 text-muted">No holidays configured.</td></tr>@endforelse
                </tbody></table></div>

            @elseif($activeTab === 'salary-components')
                <div class="settings-form-box mb-4 {{ auth()->user()->can('hr-setting-manage') ? '' : 'd-none' }}">
                    <h6 class="fw-bold mb-3" id="salaryComponentFormTitle">Add Salary Component</h6>
                    <form id="salaryComponentForm" method="POST" action="{{ route('hr.settings.salary-components.store') }}" class="row g-3 align-items-end">
                        @csrf <input type="hidden" name="_method" id="salaryComponentMethod" value="POST">
                        <div class="col-md-3"><label class="form-label small fw-semibold">Name *</label><input id="salaryComponentName" name="name" class="progga-form-control" required></div>
                        <div class="col-md-2"><label class="form-label small fw-semibold">Code</label><input id="salaryComponentCode" name="code" class="progga-form-control"></div>
                        <div class="col-md-2"><label class="form-label small fw-semibold">Type *</label><select id="salaryComponentType" name="component_type" class="progga-form-control"><option value="earning">Earning</option><option value="deduction">Deduction</option></select></div>
                        <div class="col-md-2"><label class="form-label small fw-semibold">Calculation *</label><select id="salaryComponentCalculation" name="calculation_type" class="progga-form-control"><option value="fixed">Fixed</option><option value="percentage">Percentage</option><option value="formula">Formula</option><option value="manual">Manual</option></select></div>
                        <div class="col-md-1"><label class="form-label small fw-semibold">Amount</label><input id="salaryComponentAmount" type="number" step="0.01" min="0" name="default_amount" class="progga-form-control"></div>
                        <div class="col-md-1"><label class="form-label small fw-semibold">%</label><input id="salaryComponentPercentage" type="number" step="0.01" min="0" max="100" name="default_percentage" class="progga-form-control"></div>
                        <div class="col-md-1"><button id="salaryComponentSubmit" class="progga-btn progga-btn-primary w-100">Save</button></div>
                        <div class="col-12 d-flex flex-wrap gap-4"><label class="form-check"><input id="salaryComponentTaxable" class="form-check-input" type="checkbox" name="is_taxable" value="1"> Taxable</label><label class="form-check"><input id="salaryComponentAttendance" class="form-check-input" type="checkbox" name="is_attendance_based" value="1"> Attendance Based</label><label class="form-check"><input id="salaryComponentOvertime" class="form-check-input" type="checkbox" name="is_overtime_component" value="1"> Overtime Component</label><label class="form-check"><input id="salaryComponentStatus" class="form-check-input" type="checkbox" name="status" value="1" checked> Active</label><button type="button" id="salaryComponentCancel" class="btn btn-sm btn-outline-secondary d-none" onclick="resetSalaryComponentForm()">Cancel Edit</button></div>
                    </form>
                </div>
                <div class="table-responsive"><table class="progga-table"><thead><tr><th>Name</th><th>Code</th><th>Type</th><th>Calculation</th><th>Default</th><th>Rules</th><th>Status</th><th class="text-end">Action</th></tr></thead><tbody>
                    @forelse($salaryComponents as $item)<tr><td class="fw-semibold">{{ $item->name }}</td><td>{{ $item->code }}</td><td><span class="badge text-bg-{{ $item->component_type==='earning'?'success':'danger' }} text-capitalize">{{ $item->component_type }}</span></td><td class="text-capitalize">{{ $item->calculation_type }}</td><td>{{ $item->default_amount !== null ? number_format($item->default_amount,2) : '—' }}{{ $item->default_percentage !== null ? ' / '.$item->default_percentage.'%' : '' }}</td><td class="small">{{ $item->is_taxable?'Taxable · ':'' }}{{ $item->is_attendance_based?'Attendance · ':'' }}{{ $item->is_overtime_component?'Overtime':'' }}</td><td><span class="badge text-bg-{{ $item->status?'success':'secondary' }}">{{ $item->status?'Active':'Inactive' }}</span></td><td class="text-end"><button type="button" class="progga-btn progga-btn-outline progga-btn-sm {{ auth()->user()->can('hr-setting-manage') ? '' : 'd-none' }}" data-item='{{ json_encode(["id"=>$item->id,"name"=>$item->name,"code"=>$item->code,"type"=>$item->component_type,"calculation"=>$item->calculation_type,"amount"=>$item->default_amount,"percentage"=>$item->default_percentage,"taxable"=>(int)$item->is_taxable,"attendance"=>(int)$item->is_attendance_based,"overtime"=>(int)$item->is_overtime_component,"status"=>(int)$item->status]) }}' onclick="editSalaryComponent(JSON.parse(this.dataset.item))"><i class="bi bi-pencil"></i></button> @include('admin.hr.settings.partials.delete-button',['action'=>route('hr.settings.salary-components.destroy',$item),'label'=>'salary component'])</td></tr>@empty<tr><td colspan="8" class="text-center py-4 text-muted">No salary components configured.</td></tr>@endforelse
                </tbody></table></div>

            @elseif($activeTab === 'attendance-rules')
                <form method="POST" action="{{ route('hr.settings.attendance-rules.update') }}">
                    @csrf
                    <div class="settings-form-box mb-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <div>
                                <h6 class="fw-bold mb-1">Attendance Calculation Rules</h6>
                                <div class="small text-muted">These values act as global defaults when processing daily attendance.</div>
                            </div>
                            <span class="badge text-bg-light border">Minutes based</span>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Default Grace Minutes *</label>
                                <input type="number" min="0" max="180" name="default_grace_minutes" value="{{ old('default_grace_minutes', $attendanceRules->default_grace_minutes) }}" class="progga-form-control" required @disabled(auth()->user()->cannot('hr-setting-manage'))>
                                <div class="form-text">Used when a shift-specific grace period is unavailable.</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Full Day Minimum Minutes *</label>
                                <input type="number" min="1" max="1440" name="full_day_minimum_minutes" value="{{ old('full_day_minimum_minutes', $attendanceRules->full_day_minimum_minutes) }}" class="progga-form-control" required @disabled(auth()->user()->cannot('hr-setting-manage'))>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Half Day Minimum Minutes *</label>
                                <input type="number" min="1" max="1440" name="half_day_minimum_minutes" value="{{ old('half_day_minimum_minutes', $attendanceRules->half_day_minimum_minutes) }}" class="progga-form-control" required @disabled(auth()->user()->cannot('hr-setting-manage'))>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Missing Checkout Action *</label>
                                <select name="missing_checkout_action" class="progga-form-control" required @disabled(auth()->user()->cannot('hr-setting-manage'))>
                                    <option value="missing_checkout" @selected(old('missing_checkout_action', $attendanceRules->missing_checkout_action) === 'missing_checkout')>Mark Missing Checkout</option>
                                    <option value="half_day" @selected(old('missing_checkout_action', $attendanceRules->missing_checkout_action) === 'half_day')>Mark Half Day</option>
                                    <option value="absent" @selected(old('missing_checkout_action', $attendanceRules->missing_checkout_action) === 'absent')>Mark Absent</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Minimum Overtime Minutes *</label>
                                <input type="number" min="0" max="1440" name="minimum_overtime_minutes" value="{{ old('minimum_overtime_minutes', $attendanceRules->minimum_overtime_minutes) }}" class="progga-form-control" required @disabled(auth()->user()->cannot('hr-setting-manage'))>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Maximum Overtime Minutes</label>
                                <input type="number" min="0" max="1440" name="maximum_overtime_minutes" value="{{ old('maximum_overtime_minutes', $attendanceRules->maximum_overtime_minutes) }}" class="progga-form-control" placeholder="No limit" @disabled(auth()->user()->cannot('hr-setting-manage'))>
                            </div>
                        </div>
                    </div>

                    <div class="settings-form-box mb-4">
                        <h6 class="fw-bold mb-3">Attendance Policies</h6>
                        <div class="row g-3">
                            @foreach([
                                'auto_mark_absent' => ['Auto Mark Absent', 'Create an absent status when no valid attendance is found.'],
                                'allow_manual_attendance' => ['Allow Manual Attendance', 'Authorized users can enter attendance manually.'],
                                'allow_attendance_adjustment' => ['Allow Attendance Adjustment', 'Authorized users can request or approve corrections.'],
                                'require_checkout' => ['Require Checkout', 'A check-out punch is required to complete attendance.'],
                                'overtime_requires_approval' => ['Overtime Requires Approval', 'Only approved overtime will be considered for payroll.'],
                            ] as $field => [$label, $help])
                                <div class="col-md-6 col-xl-4">
                                    <div class="border rounded-3 p-3 h-100 bg-white">
                                        <input type="hidden" name="{{ $field }}" value="0">
                                        <label class="form-check mb-0">
                                            <input type="checkbox" class="form-check-input" name="{{ $field }}" value="1" @checked((bool) old($field, $attendanceRules->{$field})) @disabled(auth()->user()->cannot('hr-setting-manage'))>
                                            <span class="fw-semibold">{{ $label }}</span>
                                        </label>
                                        <div class="small text-muted mt-2">{{ $help }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    @can('hr-setting-manage')
                        <div class="text-end"><button class="progga-btn progga-btn-primary"><i class="bi bi-check2-circle"></i> Save Attendance Rules</button></div>
                    @endcan
                </form>

            @elseif($activeTab === 'payroll-settings')
                <form method="POST" action="{{ route('hr.settings.payroll-settings.update') }}">
                    @csrf
                    <div class="settings-form-box mb-4">
                        <div class="mb-3">
                            <h6 class="fw-bold mb-1">Payroll Cycle & Salary Calculation</h6>
                            <div class="small text-muted">Configure the defaults used when payroll periods and employee salary sheets are generated.</div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Payroll Frequency *</label>
                                <select name="payroll_frequency" class="progga-form-control" required @disabled(auth()->user()->cannot('hr-setting-manage'))>
                                    <option value="monthly" @selected(old('payroll_frequency', $payrollSettings->payroll_frequency) === 'monthly')>Monthly</option>
                                    <option value="biweekly" @selected(old('payroll_frequency', $payrollSettings->payroll_frequency) === 'biweekly')>Biweekly</option>
                                    <option value="weekly" @selected(old('payroll_frequency', $payrollSettings->payroll_frequency) === 'weekly')>Weekly</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Period Start Day *</label>
                                <input type="number" min="1" max="28" name="period_start_day" value="{{ old('period_start_day', $payrollSettings->period_start_day) }}" class="progga-form-control" required @disabled(auth()->user()->cannot('hr-setting-manage'))>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Default Payment Day</label>
                                <input type="number" min="1" max="31" name="payment_day" value="{{ old('payment_day', $payrollSettings->payment_day) }}" class="progga-form-control" placeholder="e.g. 7" @disabled(auth()->user()->cannot('hr-setting-manage'))>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Salary Calculation Basis *</label>
                                <select name="salary_calculation_basis" class="progga-form-control" required @disabled(auth()->user()->cannot('hr-setting-manage'))>
                                    <option value="working_days" @selected(old('salary_calculation_basis', $payrollSettings->salary_calculation_basis) === 'working_days')>Working Days</option>
                                    <option value="calendar_days" @selected(old('salary_calculation_basis', $payrollSettings->salary_calculation_basis) === 'calendar_days')>Calendar Days</option>
                                    <option value="fixed_30_days" @selected(old('salary_calculation_basis', $payrollSettings->salary_calculation_basis) === 'fixed_30_days')>Fixed 30 Days</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Currency *</label>
                                <input name="currency" maxlength="10" value="{{ old('currency', $payrollSettings->currency) }}" class="progga-form-control text-uppercase" required @disabled(auth()->user()->cannot('hr-setting-manage'))>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Payslip Prefix *</label>
                                <input name="payslip_prefix" maxlength="30" value="{{ old('payslip_prefix', $payrollSettings->payslip_prefix) }}" class="progga-form-control text-uppercase" required @disabled(auth()->user()->cannot('hr-setting-manage'))>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Net Salary Rounding *</label>
                                <select name="net_salary_rounding" class="progga-form-control" required @disabled(auth()->user()->cannot('hr-setting-manage'))>
                                    <option value="none" @selected(old('net_salary_rounding', $payrollSettings->net_salary_rounding) === 'none')>No Rounding</option>
                                    <option value="nearest" @selected(old('net_salary_rounding', $payrollSettings->net_salary_rounding) === 'nearest')>Nearest Whole Amount</option>
                                    <option value="up" @selected(old('net_salary_rounding', $payrollSettings->net_salary_rounding) === 'up')>Always Round Up</option>
                                    <option value="down" @selected(old('net_salary_rounding', $payrollSettings->net_salary_rounding) === 'down')>Always Round Down</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="settings-form-box mb-4">
                        <h6 class="fw-bold mb-3">Overtime & Attendance Deduction</h6>
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Overtime Rate Multiplier *</label>
                                <input type="number" min="0" max="10" step="0.01" name="overtime_rate_multiplier" value="{{ old('overtime_rate_multiplier', $payrollSettings->overtime_rate_multiplier) }}" class="progga-form-control" required @disabled(auth()->user()->cannot('hr-setting-manage'))>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Absent Deduction Method *</label>
                                <select name="absent_deduction_method" class="progga-form-control" required @disabled(auth()->user()->cannot('hr-setting-manage'))>
                                    <option value="per_day" @selected(old('absent_deduction_method', $payrollSettings->absent_deduction_method) === 'per_day')>Per Day Salary</option>
                                    <option value="fixed_amount" @selected(old('absent_deduction_method', $payrollSettings->absent_deduction_method) === 'fixed_amount')>Fixed Amount Component</option>
                                    <option value="manual" @selected(old('absent_deduction_method', $payrollSettings->absent_deduction_method) === 'manual')>Manual</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Late Count = One Day Deduction *</label>
                                <input type="number" min="1" max="31" name="late_count_for_one_day_deduction" value="{{ old('late_count_for_one_day_deduction', $payrollSettings->late_count_for_one_day_deduction) }}" class="progga-form-control" required @disabled(auth()->user()->cannot('hr-setting-manage'))>
                            </div>
                        </div>
                    </div>

                    <div class="settings-form-box mb-4">
                        <h6 class="fw-bold mb-3">Payroll Policies</h6>
                        <div class="row g-3">
                            @foreach([
                                'overtime_enabled' => ['Enable Overtime Calculation', 'Include approved overtime in generated payroll.'],
                                'attendance_deduction_enabled' => ['Enable Attendance Deduction', 'Calculate deductions from absence and unpaid attendance.'],
                                'late_deduction_enabled' => ['Enable Late Deduction', 'Convert configured late counts into salary deduction.'],
                                'salary_advance_auto_deduction' => ['Auto Deduct Salary Advance', 'Add due salary advance installments during payroll generation.'],
                                'require_payroll_approval' => ['Require Payroll Approval', 'Generated payroll must be approved before payment.'],
                                'lock_after_approval' => ['Lock After Approval', 'Prevent payroll values from changing after approval.'],
                                'include_paid_holidays' => ['Include Paid Holidays', 'Treat active paid holidays as payable days.'],
                            ] as $field => [$label, $help])
                                <div class="col-md-6 col-xl-4">
                                    <div class="border rounded-3 p-3 h-100 bg-white">
                                        <input type="hidden" name="{{ $field }}" value="0">
                                        <label class="form-check mb-0">
                                            <input type="checkbox" class="form-check-input" name="{{ $field }}" value="1" @checked((bool) old($field, $payrollSettings->{$field})) @disabled(auth()->user()->cannot('hr-setting-manage'))>
                                            <span class="fw-semibold">{{ $label }}</span>
                                        </label>
                                        <div class="small text-muted mt-2">{{ $help }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    @can('hr-setting-manage')
                        <div class="text-end"><button class="progga-btn progga-btn-primary"><i class="bi bi-check2-circle"></i> Save Payroll Settings</button></div>
                    @endcan
                </form>
            @endif
        </div>
    </div>
</main>
@endsection

@section('script')
<script>
document.querySelectorAll('[id]').forEach(function(node){ if (!(node.id in window)) window[node.id] = node; });
const routeBases = {
    departments: @json(url('/hr/settings/departments')),
    designations: @json(url('/hr/settings/designations')),
    employmentTypes: @json(url('/hr/settings/employment-types')),
    shifts: @json(url('/hr/settings/shifts')),
    leaveTypes: @json(url('/hr/settings/leave-types')),
    holidays: @json(url('/hr/settings/holidays')),
    salaryComponents: @json(url('/hr/settings/salary-components'))
};
function editBase(prefix, base, item) { document.getElementById(prefix+'Form').action = base+'/'+item.id; document.getElementById(prefix+'Method').value='PUT'; document.getElementById(prefix+'FormTitle').textContent='Edit '+document.getElementById(prefix+'FormTitle').textContent.replace('Add ','').replace('Edit ',''); document.getElementById(prefix+'Submit').textContent='Update'; document.getElementById(prefix+'Cancel').classList.remove('d-none'); window.scrollTo({top:0,behavior:'smooth'}); }
function resetBase(prefix, storeUrl, title) { const form=document.getElementById(prefix+'Form'); form.reset(); form.action=storeUrl; document.getElementById(prefix+'Method').value='POST'; document.getElementById(prefix+'FormTitle').textContent='Add '+title; document.getElementById(prefix+'Submit').textContent='Save'; document.getElementById(prefix+'Cancel').classList.add('d-none'); }

function editDepartment(i){ editBase('department',routeBases.departments,i); departmentName.value=i.name; departmentCode.value=i.code||''; departmentManager.value=i.manager||''; departmentDescription.value=i.description||''; departmentStatus.value=i.status; }
function resetDepartmentForm(){ resetBase('department',@json(route('hr.settings.departments.store')),'Department'); }
function editDesignation(i){ editBase('designation',routeBases.designations,i); designationName.value=i.name; designationCode.value=i.code||''; designationDepartment.value=i.department||''; designationLevel.value=i.level||''; designationDescription.value=i.description||''; designationStatus.value=i.status; }
function resetDesignationForm(){ resetBase('designation',@json(route('hr.settings.designations.store')),'Designation'); }
function editEmploymentType(i){ editBase('employmentType',routeBases.employmentTypes,i); employmentTypeName.value=i.name; employmentTypeCode.value=i.code||''; employmentTypeDescription.value=i.description||''; employmentTypeStatus.value=i.status; }
function resetEmploymentTypeForm(){ resetBase('employmentType',@json(route('hr.settings.employment-types.store')),'Employment Type'); }
function editShift(i){ editBase('shift',routeBases.shifts,i); shiftName.value=i.name; shiftCode.value=i.code||''; shiftStart.value=(i.start||'').substring(0,5); shiftEnd.value=(i.end||'').substring(0,5); shiftBreak.value=i.break||0; shiftGrace.value=i.grace||0; shiftMinimum.value=i.minimum||''; shiftOvertime.value=i.overtime||''; shiftOvernight.checked=!!i.overnight; shiftStatus.value=i.status; }
function resetShiftForm(){ resetBase('shift',@json(route('hr.settings.shifts.store')),'Shift'); shiftBreak.value=0; shiftGrace.value=0; }
function editLeaveType(i){ editBase('leaveType',routeBases.leaveTypes,i); leaveTypeName.value=i.name; leaveTypeCode.value=i.code||''; leaveTypeLimit.value=i.limit||0; leaveTypeMaxCarry.value=i.maxCarry||''; leaveTypePaid.checked=!!i.paid; leaveTypeCarry.checked=!!i.carry; leaveTypeDocument.checked=!!i.document; leaveTypeStatus.checked=!!i.status; }
function resetLeaveTypeForm(){ resetBase('leaveType',@json(route('hr.settings.leave-types.store')),'Leave Type'); leaveTypePaid.checked=true; leaveTypeStatus.checked=true; leaveTypeLimit.value=0; }
function editHoliday(i){ editBase('holiday',routeBases.holidays,i); holidayName.value=i.name; setHrDatePickerValue('holidayDate', i.date); holidayType.value=i.type; holidayPaid.checked=!!i.paid; holidayDescription.value=i.description||''; holidayStatus.checked=!!i.status; }
function resetHolidayForm(){ resetBase('holiday',@json(route('hr.settings.holidays.store')),'Holiday'); setHrDatePickerValue('holidayDate', ''); holidayPaid.checked=true; holidayStatus.checked=true; }
function editSalaryComponent(i){ editBase('salaryComponent',routeBases.salaryComponents,i); salaryComponentName.value=i.name; salaryComponentCode.value=i.code||''; salaryComponentType.value=i.type; salaryComponentCalculation.value=i.calculation; salaryComponentAmount.value=i.amount||''; salaryComponentPercentage.value=i.percentage||''; salaryComponentTaxable.checked=!!i.taxable; salaryComponentAttendance.checked=!!i.attendance; salaryComponentOvertime.checked=!!i.overtime; salaryComponentStatus.checked=!!i.status; }
function resetSalaryComponentForm(){ resetBase('salaryComponent',@json(route('hr.settings.salary-components.store')),'Salary Component'); salaryComponentStatus.checked=true; }
</script>
@endsection
