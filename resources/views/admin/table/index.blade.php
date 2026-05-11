@extends('admin.master.master')
@section('title', 'Table Management — Progga RMS')

@section('body')
<main class="progga-content">
    <div class="progga-page-header">
        <div>
            <h1 class="progga-page-title">Table Management</h1>
            <div class="progga-breadcrumb">
                <a href="{{ route('home') }}" class="progga-breadcrumb-item">Dashboard</a>
                <span class="progga-breadcrumb-sep">/</span>
                <span class="progga-breadcrumb-item active">Tables</span>
            </div>
        </div>
        @can('table-create')
        <button class="progga-btn progga-btn-primary" data-bs-toggle="modal" data-bs-target="#addTableModal">
            <i class="bi bi-plus-lg"></i> Add Table
        </button>
        @endcan
    </div>

    @if(session('success'))
        <div class="alert alert-success" style="font-size:13px;"><i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger" style="font-size:13px;"><i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}</div>
    @endif

    <div style="display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap;">
        <div class="progga-stat-card" style="flex:1;min-width:140px;padding:14px 18px;">
            <div class="progga-stat-icon success" style="width:36px;height:36px;font-size:16px;"><i class="bi bi-check-circle-fill"></i></div>
            <div class="progga-stat-info"><div class="progga-stat-label">Available</div><div class="progga-stat-value" style="font-size:20px;">{{ $availableCount }}</div></div>
        </div>
        <div class="progga-stat-card" style="flex:1;min-width:140px;padding:14px 18px;">
            <div class="progga-stat-icon danger" style="width:36px;height:36px;font-size:16px;"><i class="bi bi-people-fill"></i></div>
            <div class="progga-stat-info"><div class="progga-stat-label">Occupied</div><div class="progga-stat-value" style="font-size:20px;">{{ $occupiedCount }}</div></div>
        </div>
        <div class="progga-stat-card" style="flex:1;min-width:140px;padding:14px 18px;">
            <div class="progga-stat-icon warning" style="width:36px;height:36px;font-size:16px;"><i class="bi bi-calendar-check"></i></div>
            <div class="progga-stat-info"><div class="progga-stat-label">Reserved</div><div class="progga-stat-value" style="font-size:20px;">{{ $reservedCount }}</div></div>
        </div>
        <div class="progga-stat-card" style="flex:1;min-width:140px;padding:14px 18px;">
            <div class="progga-stat-icon primary" style="width:36px;height:36px;font-size:16px;"><i class="bi bi-table"></i></div>
            <div class="progga-stat-info"><div class="progga-stat-label">Total Tables</div><div class="progga-stat-value" style="font-size:20px;">{{ $totalTables }}</div></div>
        </div>
    </div>

    <div class="progga-card" style="margin-bottom:16px;">
        <div class="progga-filters-bar">
            <div class="progga-pos-table-filters" style="display:flex;gap:8px;flex-wrap:wrap;">
                <button class="progga-pos-filter-btn active" data-view-filter="all">All Tables</button>
                <button class="progga-pos-filter-btn" data-view-filter="available">Available</button>
                <button class="progga-pos-filter-btn" data-view-filter="occupied">Occupied</button>
                <button class="progga-pos-filter-btn" data-view-filter="reserved">Reserved</button>
            </div>
            <div style="margin-left:auto;display:flex;gap:8px;">
                <button class="progga-btn progga-btn-outline progga-btn-sm" id="viewGrid" title="Grid View"><i class="bi bi-grid"></i></button>
                {{-- <button class="progga-btn progga-btn-outline progga-btn-sm" id="viewList" title="List View"><i class="bi bi-list-ul"></i></button> --}}
            </div>
        </div>
    </div>

    <div id="tableGridView">
        <div class="progga-table-grid" id="tableCardGrid">
            @foreach($tables as $table)
                <div class="progga-table-card {{ $table->dynamic_status }}"
                     data-status="{{ $table->dynamic_status }}"
                     data-table-num="{{ $table->table_number }}"
                     data-zone="{{ $table->zone->name ?? 'N/A' }}"
                     data-capacity="{{ $table->seating_capacity }}"
                     @if($table->dynamic_status == 'occupied')
                     data-order='{"orderId":"#1042","waiter":"Karim Ahmed","elapsed":45,"kots":[{"id":"KOT-01","time":"12:30 PM","items":[{"name":"Chicken Biryani","qty":2,"price":280},{"name":"Plain Naan","qty":4,"price":30}]}]}'
                     @endif>

                    <div class="progga-table-card-actions">
                        @can('table-edit')
                        <button class="progga-btn progga-btn-outline progga-btn-icon progga-btn-sm" onclick="editTableData({{ $table->id }}, '{{ $table->table_number }}', {{ $table->seating_capacity }}, '{{ $table->zone_id }}', '{{ $table->initial_status }}', '{{ $table->notes }}')">
                            <i class="bi bi-pencil"></i>
                        </button>
                        @endcan

                        @can('table-delete')
                        <form action="{{ route('table.destroy', $table->id) }}" method="POST" style="display:inline;">
                            @csrf @method('DELETE')
                            <button type="button" class="progga-btn progga-btn-danger progga-btn-icon progga-btn-sm progga-delete-btn">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                        @endcan
                    </div>

                    <div class="progga-table-card-icon"><i class="bi bi-layout-wtf"></i></div>
                    <div class="progga-table-card-num">{{ $table->table_number }}</div>
                    <div class="progga-table-card-info"><i class="bi bi-people"></i> {{ $table->seating_capacity }} seats · {{ $table->zone->name ?? 'N/A' }}</div>
                    <span class="progga-badge progga-status-{{ $table->dynamic_status }}">{{ ucfirst($table->dynamic_status) }}</span>
                </div>
            @endforeach
        </div>
    </div>
</main>

@include('admin.table.modals.add_table')
@include('admin.table.modals.edit_table')
@include('admin.table.modals.table_offcanvas')

@endsection

@section('script')
<script>
    /* ─── View filter ─── */
    document.querySelectorAll('[data-view-filter]').forEach(function(btn){
        btn.addEventListener('click', function(){
            document.querySelectorAll('[data-view-filter]').forEach(b=>b.classList.remove('active'));
            btn.classList.add('active');
            var filter = btn.dataset.viewFilter;
            document.querySelectorAll('.progga-table-card').forEach(function(card){
                card.style.display = (filter === 'all' || card.dataset.status === filter) ? '' : 'none';
            });
        });
    });

    // Edit Modal Data Pass
    window.editTableData = function(id, table_number, capacity, zone_id, status, notes) {
        let formAction = "{{ route('table.update', ':id') }}".replace(':id', id);
        $('#editTableForm').attr('action', formAction);

        $('#edit_table_number').val(table_number);
        $('#edit_capacity').val(capacity);
        $('#edit_zone_id').val(zone_id).trigger('change');
        $('#edit_initial_status').val(status).trigger('change');
        $('#edit_notes').val(notes);

        $('#editTableModal').modal('show');
    }

    /* ─── Occupied table card click (Static JS kept intact) ─── */
    document.querySelectorAll('.progga-table-card.occupied').forEach(function(card) {
        card.addEventListener('click', function(e) {
            if (e.target.closest('.progga-table-card-actions')) return;
            var order = JSON.parse(card.dataset.order);
            renderTableOffcanvas(card.dataset.tableNum, card.dataset.zone, card.dataset.capacity, order);
            bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('tableOrderOffcanvas')).show();
        });
    });

    function fmt(n) { return '৳' + parseFloat(n).toFixed(2); }

    function renderTableOffcanvas(tableNum, zone, capacity, order) {
        document.getElementById('ocTableNum').textContent  = tableNum;
        document.getElementById('ocTableMeta').textContent = zone + ' · ' + capacity + ' seats';
        document.getElementById('ocChips').innerHTML =
            '<span class="progga-oc-chip"><i class="bi bi-receipt"></i> ' + order.orderId + '</span>' +
            '<span class="progga-oc-chip"><i class="bi bi-person"></i> ' + order.waiter + '</span>' +
            '<span class="progga-oc-chip"><i class="bi bi-clock"></i> ' + order.elapsed + ' min</span>';

        var bodyHtml = '<div class="progga-oc-section-label">Current Order</div>';
        var subtotal = 0;
        order.kots.forEach(function(kot) {
            bodyHtml += '<div class="progga-oc-kot"><div class="progga-oc-kot-head"><span class="progga-oc-kot-label">' + kot.id + '</span><span class="progga-oc-kot-time"><i class="bi bi-clock me-1"></i>' + kot.time + '</span></div>';
            kot.items.forEach(function(item) {
                var lineTotal = item.qty * item.price;
                subtotal += lineTotal;
                bodyHtml += '<div class="progga-oc-item"><span class="progga-oc-item-name">' + item.name + '</span><span class="progga-oc-item-qty">×' + item.qty + '</span><span class="progga-oc-item-price">' + fmt(lineTotal) + '</span></div>';
            });
            bodyHtml += '</div>';
        });
        document.getElementById('ocBody').innerHTML = bodyHtml;

        var tax   = subtotal * 0.05;
        var total = subtotal + tax;
        document.getElementById('ocTotals').innerHTML =
            '<div class="progga-oc-total-row"><span>Subtotal</span><span>' + fmt(subtotal) + '</span></div>' +
            '<div class="progga-oc-total-row"><span>Tax (5%)</span><span>' + fmt(tax) + '</span></div>' +
            '<div class="progga-oc-total-row grand"><span>Total</span><span>' + fmt(total) + '</span></div>';
    }
</script>
@endsection
