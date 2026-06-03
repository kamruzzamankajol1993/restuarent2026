<div class="progga-pos-screen active progga-pos-table-screen" id="posStep1">
    <div class="pos-s1-top">
      <div class="pos-s1-heading-wrap">
        <h2 class="pos-s1-title">Select a Table</h2>
        <p class="pos-s1-sub">Tap a table for dine-in or use New Order for any order type</p>
      </div>
      <button class="pos-s1-takeaway-btn" id="modeTakeaway" type="button">
        <i class="bi bi-plus-circle-fill"></i> New Order
      </button>
    </div>

    <div class="pos-table-stats">
      <span class="pos-tstat avail"><span class="pos-tstat-dot"></span>{{ $availCount }} Available</span>
      <span class="pos-tstat-sep">·</span>
      <span class="pos-tstat occ"><span class="pos-tstat-dot"></span>{{ $occCount }} Occupied</span>
      <span class="pos-tstat-sep">·</span>
      <span class="pos-tstat res"><span class="pos-tstat-dot"></span>{{ $resCount }} Reserved</span>
    </div>

    <div class="pos-s1-filters">
      <div class="progga-pos-table-filters" id="posTableSection">
        <button class="progga-pos-filter-btn active" data-table-filter="all">All Tables</button>
        <button class="progga-pos-filter-btn" data-table-filter="available">Available</button>
        <button class="progga-pos-filter-btn" data-table-filter="occupied">Occupied</button>
        <button class="progga-pos-filter-btn" data-table-filter="reserved">Reserved</button>
      </div>
    </div>

    <div class="pos-s1-grid" style="margin-top:20px;">
      <div class="progga-pos-table-grid" id="posTableGrid">
        @foreach($tables as $table)
            @php $statusClass = strtolower($table->initial_status); @endphp
            <div class="progga-pos-table-card {{ $statusClass }}" data-table-id="{{ $table->id }}" data-table-num="{{ $table->table_number }}" data-status="{{ $statusClass }}">
              <span class="progga-pos-table-icon">🪑</span>
              <div class="progga-pos-table-num">{{ $table->table_number }}</div>
              <div class="progga-pos-table-zone">{{ $table->zone->name ?? 'Main' }}</div>
              <div class="progga-pos-table-info"><i class="bi bi-people-fill"></i> {{ $table->seating_capacity }} seats</div>
              <span class="progga-badge progga-status-{{ $statusClass }}">{{ $table->initial_status }}</span>
            </div>
        @endforeach
      </div>
    </div>
</div>
