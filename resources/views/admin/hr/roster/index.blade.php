@extends('admin.master.master')
@section('title', 'Shift & Duty Roster')
@section('body')
<main class="progga-content">
    <div class="mb-4"><h4 class="fw-bold mb-1">Shift & Duty Roster</h4><div class="text-muted small">Assign employee shifts and generate daily duty rosters</div></div>
    @include('admin.hr.partials.alerts')

    @can('hr-setting-manage')
    <div class="row g-3 mb-4">
        <div class="col-xl-5"><div class="progga-card h-100"><div class="progga-card-header"><div class="progga-card-title">Shift Assignment</div></div><div class="progga-card-body"><form method="POST" action="{{ route('hr.roster.assignments.store') }}" class="row g-3">@csrf
            <div class="col-12"><label class="form-label">Employee *</label><select name="employee_id" class="form-select" required><option value="">Select employee</option>@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->employee_code }} - {{ $employee->full_name }}</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label">Shift *</label><select name="shift_id" class="form-select" required><option value="">Select shift</option>@foreach($shifts as $shift)<option value="{{ $shift->id }}">{{ $shift->name }} ({{ $shift->start_time }}-{{ $shift->end_time }})</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">From *</label><input type="text" name="effective_from" class="form-control hr-datepicker" value="{{ now()->toDateString() }}" required autocomplete="off" placeholder="DD-MM-YYYY"></div><div class="col-md-3"><label class="form-label">To</label><input type="text" name="effective_to" class="form-control hr-datepicker" autocomplete="off" placeholder="DD-MM-YYYY"></div>
            <div class="col-12"><label class="form-label d-block">Weekly Off Days</label>@foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $i=>$day)<label class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="weekly_off_days[]" value="{{ $i }}" @checked($i===5)><span class="form-check-label">{{ $day }}</span></label>@endforeach</div>
            <div class="col-12"><input type="hidden" name="status" value="1"><button class="progga-btn progga-btn-primary">Save Assignment</button></div>
        </form></div></div></div>
        <div class="col-xl-7"><div class="progga-card h-100"><div class="progga-card-header"><div class="progga-card-title">Bulk Roster Generator</div></div><div class="progga-card-body"><form method="POST" action="{{ route('hr.roster.bulk') }}" class="row g-3">@csrf
            <div class="col-md-7"><label class="form-label">Employees *</label><select name="employee_ids[]" class="form-select" multiple size="5" required>@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->employee_code }} - {{ $employee->full_name }}</option>@endforeach</select><small class="text-muted">Hold Ctrl/Cmd to select multiple employees.</small></div>
            <div class="col-md-5"><label class="form-label">Shift</label><select name="shift_id" class="form-select"><option value="">Use assigned/default shift</option>@foreach($shifts as $shift)<option value="{{ $shift->id }}">{{ $shift->name }}</option>@endforeach</select><label class="form-label mt-3">Notes</label><input type="text" name="notes" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Start Date *</label><input type="text" name="start_date" class="form-control hr-datepicker" value="{{ $from->toDateString() }}" required autocomplete="off" placeholder="DD-MM-YYYY"></div><div class="col-md-3"><label class="form-label">End Date *</label><input type="text" name="end_date" class="form-control hr-datepicker" value="{{ $to->toDateString() }}" required autocomplete="off" placeholder="DD-MM-YYYY"></div>
            <div class="col-md-6"><label class="form-label d-block">Weekly Off Days</label>@foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $i=>$day)<label class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="weekly_off_days[]" value="{{ $i }}" @checked($i===5)><span class="form-check-label">{{ $day }}</span></label>@endforeach</div>
            <div class="col-12"><button class="progga-btn progga-btn-primary" onclick="return confirm('Generate/update roster for selected employees?')"><i class="bi bi-calendar2-week"></i> Generate Roster</button></div>
        </form></div></div></div>
    </div>
    @endcan


    @can('hr-setting-manage')
    <div class="progga-card mb-4"><div class="progga-card-header"><div class="progga-card-title">Single Roster Entry</div></div><div class="progga-card-body"><form method="POST" action="{{ route('hr.roster.store') }}" class="row g-3 align-items-end">@csrf
        <div class="col-md-3"><label class="form-label">Employee *</label><select name="employee_id" class="form-select" required><option value="">Select employee</option>@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->employee_code }} - {{ $employee->full_name }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label">Date *</label><input type="text" name="duty_date" class="form-control hr-datepicker" value="{{ now()->toDateString() }}" required autocomplete="off" placeholder="DD-MM-YYYY"></div>
        <div class="col-md-2"><label class="form-label">Shift</label><select name="shift_id" class="form-select"><option value="">No shift / auto</option>@foreach($shifts as $shift)<option value="{{ $shift->id }}">{{ $shift->name }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label">Status *</label><select name="roster_status" class="form-select" required>@foreach(['scheduled','off','holiday','leave','cancelled'] as $status)<option value="{{ $status }}">{{ ucfirst($status) }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label">Notes</label><input type="text" name="notes" class="form-control"></div>
        <div class="col-md-1"><button class="progga-btn progga-btn-primary w-100">Save</button></div>
    </form></div></div>
    @endcan

    <div class="progga-card mb-4"><div class="progga-card-header"><div class="progga-card-title">Duty Roster</div></div><div class="progga-card-body border-bottom"><form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3"><label class="form-label">Month</label><input type="text" name="month" class="form-control hr-monthpicker" value="{{ request('month',$month->format('Y-m')) }}" autocomplete="off" placeholder="Select month"></div><div class="col-md-3"><label class="form-label">Employee</label><select name="employee_id" class="form-select"><option value="">All employees</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected(request('employee_id')==$employee->id)>{{ $employee->employee_code }} - {{ $employee->full_name }}</option>@endforeach</select></div><div class="col-md-3"><label class="form-label">Department</label><select name="department_id" class="form-select"><option value="">All departments</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected(request('department_id')==$department->id)>{{ $department->name }}</option>@endforeach</select></div><div class="col-md-2"><button class="progga-btn progga-btn-primary w-100">Filter</button></div>
    </form></div><div class="table-responsive"><table class="progga-table mb-0"><thead><tr><th>Date</th><th>Employee</th><th>Department</th><th>Shift</th><th>Status</th><th>Notes</th><th>Action</th></tr></thead><tbody>@forelse($rosters as $roster)<tr><td>{{ $roster->duty_date->format('D, d-m-Y') }}</td><td><strong>{{ $roster->employee?->full_name }}</strong><br><small class="text-muted">{{ $roster->employee?->employee_code }}</small></td><td>{{ $roster->employee?->department?->name }}</td><td>{{ $roster->shift?->name ?? '—' }}</td><td><span class="badge text-bg-{{ $roster->roster_status==='scheduled'?'success':'secondary' }}">{{ ucfirst($roster->roster_status) }}</span></td><td>{{ $roster->notes }}</td><td>@can('hr-setting-manage')<form method="POST" action="{{ route('hr.roster.destroy',$roster) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this roster entry?')"><i class="bi bi-trash"></i></button></form>@endcan</td></tr>@empty<tr><td colspan="7" class="text-center py-5 text-muted">No roster entries found.</td></tr>@endforelse</tbody></table></div><div class="progga-card-body">{{ $rosters->links() }}</div></div>

    <div class="progga-card">
        <div class="progga-card-header">
            <div class="progga-card-title">Employee Shift Assignments</div>
        </div>
        <div class="table-responsive">
            <table class="progga-table mb-0">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Shift</th>
                        <th>Effective Period</th>
                        <th>Weekly Off</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($assignments as $assignment)
                        <tr>
                            <td>
                                <strong>{{ $assignment->employee?->full_name }}</strong><br>
                                <small class="text-muted">{{ $assignment->employee?->employee_code }}</small>
                            </td>
                            <td>{{ $assignment->shift?->name }}</td>
                            <td>
                                {{ $assignment->effective_from->format('d-m-Y') }} -
                                {{ $assignment->effective_to?->format('d-m-Y') ?? 'Ongoing' }}
                            </td>
                            <td>
                                @foreach(($assignment->weekly_off_days ?? []) as $day)
                                    <span class="badge text-bg-light">
                                        {{ ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'][$day] ?? $day }}
                                    </span>
                                @endforeach
                            </td>
                            <td>{{ $assignment->status ? 'Active' : 'Inactive' }}</td>
                            <td>
                                @can('hr-setting-manage')
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-secondary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editAssignment{{ $assignment->id }}"
                                    >
                                        <i class="bi bi-pencil"></i>
                                    </button>

                                    <form
                                        method="POST"
                                        action="{{ route('hr.roster.assignments.destroy', $assignment) }}"
                                        class="d-inline"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Delete assignment?')"
                                        >
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                No shift assignments found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="progga-card-body">{{ $assignments->links() }}</div>
    </div>

    @can('hr-setting-manage')
        @foreach($assignments as $assignment)
            <div class="modal fade" id="editAssignment{{ $assignment->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <form
                        method="POST"
                        action="{{ route('hr.roster.assignments.update', $assignment) }}"
                        class="modal-content"
                    >
                        @csrf
                        @method('PUT')

                        <div class="modal-header">
                            <h5 class="modal-title">Edit Shift Assignment</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Shift *</label>
                                    <select name="shift_id" class="form-select" required>
                                        @foreach($shifts as $shift)
                                            <option
                                                value="{{ $shift->id }}"
                                                @selected($assignment->shift_id == $shift->id)
                                            >
                                                {{ $shift->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-6">
                                    <label class="form-label">Effective From *</label>
                                    <input
                                        type="text"
                                        name="effective_from"
                                        class="form-control hr-datepicker"
                                        value="{{ $assignment->effective_from->toDateString() }}"
                                        required
                                     autocomplete="off" placeholder="DD-MM-YYYY">
                                </div>

                                <div class="col-6">
                                    <label class="form-label">Effective To</label>
                                    <input
                                        type="text"
                                        name="effective_to"
                                        class="form-control hr-datepicker"
                                        value="{{ $assignment->effective_to?->toDateString() }}"
                                     autocomplete="off" placeholder="DD-MM-YYYY">
                                </div>

                                <div class="col-12">
                                    <label class="form-label d-block">Weekly Off Days</label>
                                    @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $i => $day)
                                        <label class="form-check form-check-inline">
                                            <input
                                                class="form-check-input"
                                                type="checkbox"
                                                name="weekly_off_days[]"
                                                value="{{ $i }}"
                                                @checked(in_array($i, $assignment->weekly_off_days ?? []))
                                            >
                                            <span class="form-check-label">{{ $day }}</span>
                                        </label>
                                    @endforeach
                                </div>

                                <div class="col-12">
                                    <label class="form-check">
                                        <input
                                            type="checkbox"
                                            name="status"
                                            value="1"
                                            class="form-check-input"
                                            @checked($assignment->status)
                                        >
                                        <span class="form-check-label">Active</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="submit" class="progga-btn progga-btn-primary">
                                Update Assignment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endforeach
    @endcan
</main>
@endsection
