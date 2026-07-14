@php
    $isEdit = isset($employee);
    $selectedRole = old('account_role', $isEdit ? optional($employee->user?->roles->first())->name : 'Employee');
@endphp

<div class="row g-4">
    <div class="col-xl-8">
        <div class="progga-card mb-4">
            <div class="progga-card-header"><div><div class="progga-card-title">Basic Information</div><div class="progga-card-subtitle">Primary employee identity and job assignment</div></div></div>
            <div class="progga-card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Employee Code</label>
                        <input type="text" name="employee_code" class="progga-form-control" value="{{ old('employee_code', $isEdit ? $employee->employee_code : $suggestedEmployeeCode) }}" {{ $isEdit ? 'readonly' : '' }}>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">First Name <span class="text-danger">*</span></label>
                        <input type="text" name="first_name" class="progga-form-control" value="{{ old('first_name', $employee->first_name ?? '') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Last Name</label>
                        <input type="text" name="last_name" class="progga-form-control" value="{{ old('last_name', $employee->last_name ?? '') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Phone</label>
                        <input type="text" name="phone" class="progga-form-control" value="{{ old('phone', $employee->phone ?? '') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" name="email" class="progga-form-control" value="{{ old('email', $employee->email ?? '') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Photo</label>
                        <input type="file" name="image" class="progga-form-control" accept="image/*">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Department</label>
                        <select name="department_id" class="progga-form-control">
                            <option value="">Select department</option>
                            @foreach($departments as $department)<option value="{{ $department->id }}" @selected((string)old('department_id', $employee->department_id ?? '') === (string)$department->id)>{{ $department->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Designation</label>
                        <select name="designation_id" class="progga-form-control">
                            <option value="">Select designation</option>
                            @foreach($designations as $designation)<option value="{{ $designation->id }}" @selected((string)old('designation_id', $employee->designation_id ?? '') === (string)$designation->id)>{{ $designation->name }}{{ $designation->department ? ' · '.$designation->department->name : '' }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Employment Type</label>
                        <select name="employment_type_id" class="progga-form-control">
                            <option value="">Select type</option>
                            @foreach($employmentTypes as $type)<option value="{{ $type->id }}" @selected((string)old('employment_type_id', $employee->employment_type_id ?? '') === (string)$type->id)>{{ $type->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Default Shift</label>
                        <select name="default_shift_id" class="progga-form-control">
                            <option value="">Select shift</option>
                            @foreach($shifts as $shift)<option value="{{ $shift->id }}" @selected((string)old('default_shift_id', $employee->default_shift_id ?? '') === (string)$shift->id)>{{ $shift->name }} @if($shift->start_time)({{ \Carbon\Carbon::parse($shift->start_time)->format('h:i A') }}–{{ \Carbon\Carbon::parse($shift->end_time)->format('h:i A') }})@endif</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Supervisor</label>
                        <select name="supervisor_id" class="progga-form-control">
                            <option value="">No supervisor</option>
                            @foreach($supervisors as $supervisor)<option value="{{ $supervisor->id }}" @selected((string)old('supervisor_id', $employee->supervisor_id ?? '') === (string)$supervisor->id)>{{ $supervisor->full_name }} ({{ $supervisor->employee_code }})</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Join Date <span class="text-danger">*</span></label>
                        <input type="date" name="join_date" class="progga-form-control" value="{{ old('join_date', isset($employee) && $employee->join_date ? $employee->join_date->format('Y-m-d') : now()->format('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Confirmation Date</label>
                        <input type="date" name="confirmation_date" class="progga-form-control" value="{{ old('confirmation_date', isset($employee) && $employee->confirmation_date ? $employee->confirmation_date->format('Y-m-d') : '') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Resignation Date</label>
                        <input type="date" name="resignation_date" class="progga-form-control" value="{{ old('resignation_date', isset($employee) && $employee->resignation_date ? $employee->resignation_date->format('Y-m-d') : '') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Termination Date</label>
                        <input type="date" name="termination_date" class="progga-form-control" value="{{ old('termination_date', isset($employee) && $employee->termination_date ? $employee->termination_date->format('Y-m-d') : '') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                        <select name="status" class="progga-form-control" required>
                            @foreach(['active','inactive','probation','on_leave','resigned','terminated'] as $status)<option value="{{ $status }}" @selected(old('status', $employee->status ?? 'active') === $status)>{{ ucwords(str_replace('_',' ',$status)) }}</option>@endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="progga-card mb-4">
            <div class="progga-card-header"><div class="progga-card-title">Personal & Contact Information</div></div>
            <div class="progga-card-body">
                <div class="row g-3">
                    <div class="col-md-3"><label class="form-label fw-semibold">Date of Birth</label><input type="date" name="date_of_birth" class="progga-form-control" value="{{ old('date_of_birth', isset($employee) && $employee->date_of_birth ? $employee->date_of_birth->format('Y-m-d') : '') }}"></div>
                    <div class="col-md-3"><label class="form-label fw-semibold">Gender</label><select name="gender" class="progga-form-control"><option value="">Select</option>@foreach(['male','female','other'] as $gender)<option value="{{ $gender }}" @selected(old('gender', $employee->gender ?? '') === $gender)>{{ ucfirst($gender) }}</option>@endforeach</select></div>
                    <div class="col-md-3"><label class="form-label fw-semibold">Blood Group</label><input type="text" name="blood_group" class="progga-form-control" value="{{ old('blood_group', $employee->blood_group ?? '') }}" placeholder="e.g. A+"></div>
                    <div class="col-md-3"><label class="form-label fw-semibold">NID Number</label><input type="text" name="nid_number" class="progga-form-control" value="{{ old('nid_number', $employee->nid_number ?? '') }}"></div>
                    <div class="col-md-3"><label class="form-label fw-semibold">Passport Number</label><input type="text" name="passport_number" class="progga-form-control" value="{{ old('passport_number', $employee->passport_number ?? '') }}"></div>
                    <div class="col-md-6"><label class="form-label fw-semibold">Present Address</label><textarea name="present_address" class="progga-form-control" rows="3">{{ old('present_address', $employee->present_address ?? '') }}</textarea></div>
                    <div class="col-md-6"><label class="form-label fw-semibold">Permanent Address</label><textarea name="permanent_address" class="progga-form-control" rows="3">{{ old('permanent_address', $employee->permanent_address ?? '') }}</textarea></div>
                    <div class="col-md-4"><label class="form-label fw-semibold">Emergency Contact Name</label><input type="text" name="emergency_contact_name" class="progga-form-control" value="{{ old('emergency_contact_name', $employee->emergency_contact_name ?? '') }}"></div>
                    <div class="col-md-4"><label class="form-label fw-semibold">Emergency Phone</label><input type="text" name="emergency_contact_phone" class="progga-form-control" value="{{ old('emergency_contact_phone', $employee->emergency_contact_phone ?? '') }}"></div>
                    <div class="col-md-4"><label class="form-label fw-semibold">Relationship</label><input type="text" name="emergency_contact_relation" class="progga-form-control" value="{{ old('emergency_contact_relation', $employee->emergency_contact_relation ?? '') }}"></div>
                </div>
            </div>
        </div>

        <div class="progga-card mb-4">
            <div class="progga-card-header"><div class="progga-card-title">Payment Information</div></div>
            <div class="progga-card-body">
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label fw-semibold">Payment Method</label><select name="payment_method" class="progga-form-control"><option value="">Select</option>@foreach(['cash'=>'Cash','bank'=>'Bank','mobile_banking'=>'Mobile Banking','cheque'=>'Cheque'] as $value=>$label)<option value="{{ $value }}" @selected(old('payment_method', $employee->payment_method ?? '') === $value)>{{ $label }}</option>@endforeach</select></div>
                    <div class="col-md-4"><label class="form-label fw-semibold">Bank Name</label><input type="text" name="bank_name" class="progga-form-control" value="{{ old('bank_name', $employee->bank_name ?? '') }}"></div>
                    <div class="col-md-4"><label class="form-label fw-semibold">Account Name</label><input type="text" name="bank_account_name" class="progga-form-control" value="{{ old('bank_account_name', $employee->bank_account_name ?? '') }}"></div>
                    <div class="col-md-4"><label class="form-label fw-semibold">Account Number</label><input type="text" name="bank_account_number" class="progga-form-control" value="{{ old('bank_account_number', $employee->bank_account_number ?? '') }}"></div>
                    <div class="col-md-4"><label class="form-label fw-semibold">Mobile Banking Type</label><input type="text" name="mobile_banking_type" class="progga-form-control" value="{{ old('mobile_banking_type', $employee->mobile_banking_type ?? '') }}" placeholder="bKash / Nagad / Rocket"></div>
                    <div class="col-md-4"><label class="form-label fw-semibold">Mobile Banking Number</label><input type="text" name="mobile_banking_number" class="progga-form-control" value="{{ old('mobile_banking_number', $employee->mobile_banking_number ?? '') }}"></div>
                    <div class="col-12"><label class="form-label fw-semibold">Notes</label><textarea name="notes" class="progga-form-control" rows="3">{{ old('notes', $employee->notes ?? '') }}</textarea></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="progga-card mb-4">
            <div class="progga-card-header"><div><div class="progga-card-title">System Integration</div><div class="progga-card-subtitle">Create related login and waiter records</div></div></div>
            <div class="progga-card-body">
                @if($isEdit && $employee->user_id)
                    <div class="alert alert-success py-2 small"><i class="bi bi-check-circle-fill me-1"></i>User login linked: {{ $employee->user?->email }}</div>
                    <input type="hidden" name="create_user_account" value="0">
                @else
                    <input type="hidden" name="create_user_account" value="0">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="createUserAccount" name="create_user_account" value="1" @checked(old('create_user_account'))>
                        <label class="form-check-label fw-semibold" for="createUserAccount">Create User Login</label>
                    </div>
                @endif

                <div id="userAccountFields" class="{{ ($isEdit && $employee->user_id) || old('create_user_account') ? '' : 'd-none' }}">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Role</label>
                        <select name="account_role" class="progga-form-control">
                            <option value="">Select role</option>
                            @foreach($roles as $role)<option value="{{ $role->name }}" @selected($selectedRole === $role->name)>{{ $role->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label fw-semibold">{{ $isEdit && $employee->user_id ? 'New Password (optional)' : 'Password' }}</label><input type="password" name="password" class="progga-form-control" autocomplete="new-password"></div>
                    <div class="mb-3"><label class="form-label fw-semibold">Confirm Password</label><input type="password" name="password_confirmation" class="progga-form-control" autocomplete="new-password"></div>
                    <div class="small text-muted mb-3">The employee email becomes the login email.</div>
                </div>

                <hr>

                @if($isEdit && $employee->waiter_id)
                    <div class="alert alert-success py-2 small"><i class="bi bi-check-circle-fill me-1"></i>Waiter profile linked: {{ $employee->waiter?->employee_id }}</div>
                    <input type="hidden" name="create_waiter_profile" value="0">
                @else
                    <input type="hidden" name="create_waiter_profile" value="0">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="createWaiterProfile" name="create_waiter_profile" value="1" @checked(old('create_waiter_profile'))>
                        <label class="form-check-label fw-semibold" for="createWaiterProfile">Create Waiter Profile</label>
                    </div>
                @endif

                <div id="waiterFields" class="{{ ($isEdit && $employee->waiter_id) || old('create_waiter_profile') ? '' : 'd-none' }}">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Waiter Zone</label>
                        <select name="zone_id" class="progga-form-control">
                            <option value="">Select zone</option>
                            @foreach($zones as $zone)<option value="{{ $zone->id }}" @selected((string)old('zone_id', $employee->waiter?->zone_id ?? '') === (string)$zone->id)>{{ $zone->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="small text-muted">Waiter shift uses the employee's Default Shift. Name, phone, email, image, join date and status remain synchronized.</div>
                </div>
            </div>
        </div>

        <div class="progga-card">
            <div class="progga-card-body">
                <button type="submit" class="progga-btn progga-btn-primary w-100 mb-2"><i class="bi bi-check2-circle"></i> {{ $isEdit ? 'Update Employee' : 'Create Employee' }}</button>
                <a href="{{ $isEdit ? route('hr.employees.show', $employee) : route('hr.employees.index') }}" class="progga-btn progga-btn-outline w-100">Cancel</a>
            </div>
        </div>
    </div>
</div>
