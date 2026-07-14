@if(session('success'))
    <div class="alert alert-success d-flex align-items-center gap-2" role="alert">
        <i class="bi bi-check-circle-fill"></i><span>{{ session('success') }}</span>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger d-flex align-items-center gap-2" role="alert">
        <i class="bi bi-exclamation-triangle-fill"></i><span>{{ session('error') }}</span>
    </div>
@endif
@if($errors->any())
    <div class="alert alert-danger" role="alert">
        <div class="fw-bold mb-1"><i class="bi bi-exclamation-circle-fill me-1"></i>Please correct the following:</div>
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
