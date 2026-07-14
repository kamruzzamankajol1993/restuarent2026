@can('hr-setting-manage')
<form method="POST" action="{{ $action }}" class="d-inline" onsubmit="return confirm('Delete this {{ $label }}?');">
    @csrf @method('DELETE')
    <button type="submit" class="progga-btn progga-btn-outline progga-btn-sm text-danger"><i class="bi bi-trash"></i></button>
</form>
@endcan
