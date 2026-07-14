@extends('admin.master.master')
@section('title', 'Edit Employee')
@section('body')
<main class="progga-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div><h4 class="mb-1 fw-bold">Edit Employee</h4><div class="text-muted small">{{ $employee->full_name }} · {{ $employee->employee_code }}</div></div>
        <a href="{{ route('hr.employees.show', $employee) }}" class="progga-btn progga-btn-outline"><i class="bi bi-arrow-left"></i> Profile</a>
    </div>
    @include('admin.hr.partials.alerts')
    <form method="POST" action="{{ route('hr.employees.update', $employee) }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        @include('admin.hr.employees._form')
    </form>
</main>
@endsection
@section('script')
<script>
(function(){
    const userToggle = document.getElementById('createUserAccount');
    const waiterToggle = document.getElementById('createWaiterProfile');
    const userFields = document.getElementById('userAccountFields');
    const waiterFields = document.getElementById('waiterFields');
    function sync(){ if(userToggle && userFields) userFields.classList.toggle('d-none', !userToggle.checked); if(waiterToggle && waiterFields) waiterFields.classList.toggle('d-none', !waiterToggle.checked); }
    userToggle?.addEventListener('change', sync); waiterToggle?.addEventListener('change', sync); sync();
})();
</script>
@endsection
