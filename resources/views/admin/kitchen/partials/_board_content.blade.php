<div class="progga-kitchen-summary-col">
    <div class="progga-kitchen-col-head progga-kitchen-col-head--summary">
      <i class="bi bi-list-columns-reverse"></i>
      <span>Food Summary</span>
      <span class="progga-kitchen-badge progga-kitchen-badge--gold progga-kitchen-col-count">{{ array_sum($foodSummary) }}</span>
    </div>
    <div class="progga-kitchen-summary-body" id="kitchenSummaryBody">
        @forelse($foodSummary as $name => $qty)
            <div class="progga-kitchen-summary-item d-flex justify-content-between border-bottom pb-2 mb-2">
                <span class="fw-bold" style="font-size:13px;color:white !important;">{{ $name }}</span>
                <span class="badge bg-warning text-dark fs-6">{{ $qty }}</span>
            </div>
        @empty
            <div class="text-muted text-center mt-4">No active foods</div>
        @endforelse
    </div>
</div>

<div class="progga-kitchen-col" id="pending-col">
    <div class="progga-kitchen-col-head progga-kitchen-col-head--pending">
      <i class="bi bi-hourglass-split"></i>
      <span>Pending</span>
      <span class="progga-kitchen-badge progga-kitchen-badge--warning progga-kitchen-col-count">{{ $kots->where('kitchen_status', 'Pending')->count() }}</span>
    </div>
    <div class="progga-kitchen-col-body">
        @foreach($kots->where('kitchen_status', 'Pending') as $kot)
            @include('admin.kitchen.partials._single_kot_card', ['kot' => $kot, 'actionLabel' => 'Start Cooking', 'nextStatus' => 'Cooking', 'btnClass' => 'start', 'icon' => 'play-fill'])
        @endforeach
    </div>
</div>

<div class="progga-kitchen-col" id="cooking-col">
    <div class="progga-kitchen-col-head progga-kitchen-col-head--cooking">
      <i class="bi bi-fire"></i>
      <span>Cooking</span>
      <span class="progga-kitchen-badge progga-kitchen-badge--info progga-kitchen-col-count">{{ $kots->where('kitchen_status', 'Cooking')->count() }}</span>
    </div>
    <div class="progga-kitchen-col-body">
        @foreach($kots->where('kitchen_status', 'Cooking') as $kot)
            @include('admin.kitchen.partials._single_kot_card', ['kot' => $kot, 'actionLabel' => 'Mark Ready', 'nextStatus' => 'Ready', 'btnClass' => 'ready', 'icon' => 'check-circle-fill'])
        @endforeach
    </div>
</div>

<div class="progga-kitchen-col" id="ready-col">
    <div class="progga-kitchen-col-head progga-kitchen-col-head--ready">
      <i class="bi bi-bell-fill"></i>
      <span>Ready to Serve</span>
      <span class="progga-kitchen-badge progga-kitchen-badge--success progga-kitchen-col-count">{{ $kots->where('kitchen_status', 'Ready')->count() }}</span>
    </div>
    <div class="progga-kitchen-col-body">
        @foreach($kots->where('kitchen_status', 'Ready') as $kot)
            @include('admin.kitchen.partials._single_kot_card', ['kot' => $kot, 'actionLabel' => 'Mark Delivered', 'nextStatus' => 'Delivered', 'btnClass' => 'deliver', 'icon' => 'bag-check-fill'])
        @endforeach
    </div>
</div>
