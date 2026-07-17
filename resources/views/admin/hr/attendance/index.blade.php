@extends('admin.master.master')
@section('title', 'Attendance')
@section('body')
<main class="progga-content">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div><h4 class="fw-bold mb-1">Attendance</h4><div class="text-muted small">Manual attendance, Excel import and daily attendance monitoring</div></div>
        <a href="{{ route('hr.attendance.template') }}" class="progga-btn progga-btn-outline"><i class="bi bi-file-earmark-excel"></i> Download Import Template</a>
    </div>
    @include('admin.hr.partials.alerts')
    @if(session('attendance_import_errors'))
        <div class="alert alert-warning"><strong>Skipped rows:</strong><ul class="mb-0 mt-2">@foreach(session('attendance_import_errors') as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <div class="row g-3 mb-4">
        @foreach([
            ['Present', $summary['present'], 'bi-person-check'], ['Late', $summary['late'], 'bi-clock-history'],
            ['Absent', $summary['absent'], 'bi-person-x'], ['Leave', $summary['leave'], 'bi-calendar2-minus'],
            ['Overtime', round($summary['overtime_minutes']/60, 2).' hrs', 'bi-stopwatch']
        ] as $card)
        <div class="col-6 col-lg"><div class="progga-card h-100"><div class="progga-card-body d-flex justify-content-between align-items-center"><div><small class="text-muted">{{ $card[0] }}</small><h4 class="mb-0 mt-1">{{ $card[1] }}</h4></div><i class="bi {{ $card[2] }} fs-3"></i></div></div></div>
        @endforeach
    </div>

    @can('hr-setting-manage')
    <div class="row g-3 mb-4">
        <div class="col-xl-7"><div class="progga-card h-100"><div class="progga-card-header"><div class="progga-card-title">Add / Update Attendance</div></div><div class="progga-card-body">
            <form method="POST" action="{{ route('hr.attendance.store') }}" class="row g-3">@csrf
                <div class="col-md-4"><label class="form-label">Employee *</label><select name="employee_id" class="form-select" required><option value="">Select employee</option>@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->employee_code }} - {{ $employee->full_name }}</option>@endforeach</select></div>
                <div class="col-md-3"><label class="form-label">Date *</label><input type="text" name="attendance_date" class="form-control hr-datepicker" value="{{ now()->toDateString() }}" required autocomplete="off" placeholder="DD-MM-YYYY"></div>
                <div class="col-md-3"><label class="form-label">Shift</label><select name="shift_id" class="form-select"><option value="">Auto detect</option>@foreach($shifts as $shift)<option value="{{ $shift->id }}">{{ $shift->name }}</option>@endforeach</select></div>
                <div class="col-md-2"><label class="form-label">Status</label><select name="status" class="form-select"><option value="">Auto</option>@foreach(['present','late','absent','half_day','leave','holiday','off','missing_checkout'] as $status)<option value="{{ $status }}">{{ ucwords(str_replace('_',' ',$status)) }}</option>@endforeach</select></div>
                <div class="col-md-3"><label class="form-label">Check In</label><input type="time" name="check_in" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">Check Out</label><input type="time" name="check_out" class="form-control"></div>
                <div class="col-md-2"><label class="form-label">Break (min)</label><input type="number" min="0" name="break_minutes" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Remarks</label><input type="text" name="remarks" class="form-control"></div>
                <div class="col-12"><button class="progga-btn progga-btn-primary"><i class="bi bi-check2-circle"></i> Save Attendance</button></div>
            </form>
        </div></div></div>
        <div class="col-xl-5"><div class="progga-card mb-3"><div class="progga-card-header"><div class="progga-card-title">Import Excel Attendance</div></div><div class="progga-card-body">
            <form method="POST" action="{{ route('hr.attendance.import') }}" enctype="multipart/form-data">@csrf
                <input type="file" name="attendance_file" class="form-control mb-3" accept=".xlsx,.xls,.csv" required>
                <div class="small text-muted mb-3">Required headings: employee_code, attendance_date. Optional: check_in, check_out, shift_code, status, remarks.</div>
                <button class="progga-btn progga-btn-primary"><i class="bi bi-upload"></i> Import Attendance</button>
            </form>
        </div></div>
        <div class="progga-card"><div class="progga-card-header"><div class="progga-card-title">Bulk Mark Absent</div></div><div class="progga-card-body"><form method="POST" action="{{ route('hr.attendance.mark-absent') }}" class="row g-2">@csrf
            <div class="col-sm-6"><input type="text" name="attendance_date" class="form-control hr-datepicker" value="{{ now()->toDateString() }}" required autocomplete="off" placeholder="DD-MM-YYYY"></div>
            <div class="col-sm-6"><select name="department_id" class="form-select"><option value="">All departments</option>@foreach($departments as $department)<option value="{{ $department->id }}">{{ $department->name }}</option>@endforeach</select></div>
            <div class="col-12"><button class="progga-btn progga-btn-outline" onclick="return confirm('Mark all employees without records as absent?')">Mark Missing Employees Absent</button></div>
        </form></div></div></div>
    </div>
    @endcan

    <div class="progga-card">
        <div class="progga-card-header"><div class="progga-card-title">Attendance Records</div></div>
        <div class="progga-card-body border-bottom"><form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2"><label class="form-label">From</label><input type="text" name="from_date" class="form-control hr-datepicker" value="{{ request('from_date', $from->toDateString()) }}" autocomplete="off" placeholder="DD-MM-YYYY"></div>
            <div class="col-md-2"><label class="form-label">To</label><input type="text" name="to_date" class="form-control hr-datepicker" value="{{ request('to_date', $to->toDateString()) }}" autocomplete="off" placeholder="DD-MM-YYYY"></div>
            <div class="col-md-3"><label class="form-label">Employee</label><select name="employee_id" class="form-select"><option value="">All employees</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected(request('employee_id')==$employee->id)>{{ $employee->employee_code }} - {{ $employee->full_name }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label">Department</label><select name="department_id" class="form-select"><option value="">All</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected(request('department_id')==$department->id)>{{ $department->name }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label">Status</label><select name="status" class="form-select"><option value="">All</option>@foreach(['present','late','absent','half_day','leave','holiday','off','missing_checkout'] as $status)<option value="{{ $status }}" @selected(request('status')==$status)>{{ ucwords(str_replace('_',' ',$status)) }}</option>@endforeach</select></div>
            <div class="col-md-1"><button class="progga-btn progga-btn-primary w-100">Filter</button></div>
        </form></div>
        <div class="table-responsive"><table class="progga-table mb-0"><thead><tr><th>Date</th><th>Employee</th><th>Shift</th><th>In / Out</th><th>Worked</th><th>Late</th><th>OT</th><th>Status</th><th>Source</th><th>Action</th></tr></thead><tbody>
        @forelse($records as $record)
            <tr><td>{{ $record->attendance_date->format('d-m-Y') }}</td><td><strong>{{ $record->employee?->full_name }}</strong><br><small class="text-muted">{{ $record->employee?->employee_code }} · {{ $record->employee?->department?->name }}</small></td><td>{{ $record->shift?->name ?? '—' }}</td><td>{{ $record->check_in?->format('h:i A') ?? '—' }}<br>{{ $record->check_out?->format('h:i A') ?? '—' }}</td><td>{{ intdiv($record->worked_minutes,60) }}h {{ $record->worked_minutes%60 }}m</td><td>{{ $record->late_minutes }}m</td><td>{{ $record->overtime_minutes }}m</td><td><span class="badge text-bg-{{ in_array($record->status,['present'])?'success':($record->status==='late'?'warning':($record->status==='absent'?'danger':'secondary')) }}">{{ ucwords(str_replace('_',' ',$record->status)) }}</span></td><td>{{ ucwords(str_replace('_',' ',$record->source)) }}</td><td>
                @can('hr-setting-manage')
                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editAttendance{{ $record->id }}"><i class="bi bi-pencil"></i></button>
                <form method="POST" action="{{ route('hr.attendance.destroy',$record) }}" class="d-inline">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this attendance record?')"><i class="bi bi-trash"></i></button></form>
                @endcan
            </td></tr>
            @can('hr-setting-manage')
            <div class="modal fade" id="editAttendance{{ $record->id }}" tabindex="-1"><div class="modal-dialog modal-lg"><form method="POST" action="{{ route('hr.attendance.update',$record) }}" class="modal-content">@csrf @method('PUT')<div class="modal-header"><h5 class="modal-title">Edit Attendance - {{ $record->employee?->full_name }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="row g-3">
                <div class="col-md-4"><label class="form-label">Date</label><input type="text" name="attendance_date" value="{{ $record->attendance_date->toDateString() }}" class="form-control hr-datepicker" required autocomplete="off" placeholder="DD-MM-YYYY"></div>
                <div class="col-md-4"><label class="form-label">Shift</label><select name="shift_id" class="form-select"><option value="">Auto detect</option>@foreach($shifts as $shift)<option value="{{ $shift->id }}" @selected($record->shift_id==$shift->id)>{{ $shift->name }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label">Status</label><select name="status" class="form-select">@foreach(['present','late','absent','half_day','leave','holiday','off','missing_checkout'] as $status)<option value="{{ $status }}" @selected($record->status==$status)>{{ ucwords(str_replace('_',' ',$status)) }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label">Check In</label><input type="time" name="check_in" value="{{ $record->check_in?->format('H:i') }}" class="form-control"></div><div class="col-md-4"><label class="form-label">Check Out</label><input type="time" name="check_out" value="{{ $record->check_out?->format('H:i') }}" class="form-control"></div><div class="col-md-4"><label class="form-label">Break Minutes</label><input type="number" name="break_minutes" value="{{ $record->break_minutes }}" class="form-control"></div><div class="col-12"><label class="form-label">Remarks</label><textarea name="remarks" class="form-control">{{ $record->remarks }}</textarea></div>
            </div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button><button class="progga-btn progga-btn-primary">Update</button></div></form></div></div>
            @endcan
        @empty<tr><td colspan="10" class="text-center py-5 text-muted">No attendance records found.</td></tr>@endforelse
        </tbody></table></div><div class="progga-card-body">{{ $records->links() }}</div>
    </div>
</main>
@endsection
