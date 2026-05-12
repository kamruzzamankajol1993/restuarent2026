<table class="progga-table table-sm">
    <thead>
        <tr>
            <th style="width:50px;">#</th>
            <th>Course Type</th>
            <th>Status</th>
            <th style="width:90px; text-align:right;">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($courseTypes as $key => $item)
        <tr>
            <td>{{ $courseTypes->firstItem() + $key }}</td>
            <td><strong>{{ $item->name }}</strong></td>
            <td>
                <label class="progga-toggle">
                    <input type="checkbox" onchange="toggleStatus({{ $item->id }}, this, 'course-type')" {{ $item->status == 1 ? 'checked' : '' }} data-on="Active" data-off="Inactive">
                    <span class="progga-toggle-track"><span class="progga-toggle-thumb"></span></span>
                </label>
            </td>
            <td style="text-align:right;">
                <button class="progga-btn progga-btn-outline progga-btn-icon progga-btn-sm" onclick="editModal('course-type', {{ $item->id }}, '{{ addslashes($item->name) }}', {{ $item->status }})"><i class="bi bi-pencil"></i></button>
                <button class="progga-btn progga-btn-danger progga-btn-icon progga-btn-sm" onclick="normalDelete({{ $item->id }}, '{{ addslashes($item->name) }}', 'course-type')"><i class="bi bi-trash"></i></button>
            </td>
        </tr>
        @empty
        <tr><td colspan="4" class="text-center py-3">No course types found.</td></tr>
        @endforelse
    </tbody>
</table>
<div class="mt-2 course-pagination d-flex justify-content-end">{{ $courseTypes->links('pagination::bootstrap-4') }}</div>
