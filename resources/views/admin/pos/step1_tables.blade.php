<div class="progga-pos-screen active progga-pos-table-screen" id="posStep1">
    <div class="pos-s1-top">
      <div class="pos-s1-heading-wrap">
        <h2 class="pos-s1-title">Select a Table</h2>
        <p class="pos-s1-sub">Tap an available table to begin a new dine-in order</p>
      </div>
      <button class="pos-s1-takeaway-btn" id="modeTakeaway" type="button">
        <i class="bi bi-bag-fill"></i> New Takeaway
      </button>
    </div>

    <div class="pos-s1-grid" style="margin-top:20px;">
      <div class="progga-pos-table-grid" id="posTableGrid">
        @foreach($tables as $table)
            @php $statusClass = strtolower($table->initial_status); @endphp
            <div class="progga-pos-table-card {{ $statusClass }}" data-table-id="{{ $table->id }}" data-table-num="{{ $table->table_number }}">
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
