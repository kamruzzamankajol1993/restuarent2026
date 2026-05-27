@extends('admin.master.master')
@section('title', 'Reports & Analytics — TableTrack RMS')

@section('css')
<style>
    .progga-tab-nav { display: flex; gap: 20px; border-bottom: 2px solid var(--progga-border-light); margin-bottom: 20px; }
    .progga-tab-item { padding: 10px 4px; font-weight: 700; color: var(--progga-text-muted); cursor: pointer; border-bottom: 3px solid transparent; transition: all 0.2s; }
    .progga-tab-item.active { color: var(--progga-primary); border-bottom-color: var(--progga-primary); }
    .progga-chart-container { position: relative; width: 100%; }
</style>
@endsection

@section('body')
<main class="progga-content">
  <div class="progga-page-header">
    <div>
      <h1 class="progga-page-title">Reports &amp; Analytics</h1>
      <div class="progga-breadcrumb">
          <a href="{{ route('home') }}" class="progga-breadcrumb-item">Dashboard</a>
          <span class="progga-breadcrumb-sep">/</span>
          <span class="progga-breadcrumb-item active">Reports</span>
      </div>
    </div>
    <div style="display:flex;gap:8px;">
      <button type="button" class="progga-btn progga-btn-outline progga-btn-sm" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
      <a href="{{ route('reports.export.pdf', request()->all()) }}" target="_blank" class="progga-btn progga-btn-outline progga-btn-sm"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
      <a href="{{ route('reports.export.csv', request()->all()) }}" class="progga-btn progga-btn-outline progga-btn-sm"><i class="bi bi-filetype-csv"></i> CSV</a>
    </div>
  </div>

  <div class="progga-tab-nav">
    <div class="progga-tab-item active" data-report-tab="overview">Overview & Sales</div>
    <div class="progga-tab-item" onclick="document.getElementById('ordersSection').scrollIntoView({behavior: 'smooth'})">Order Report</div>
  </div>

  <div class="progga-card" style="margin-bottom:16px;">
    <form action="{{ route('reports.index') }}" method="GET" class="progga-filters-bar">
      <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
        <div class="progga-form-group" style="margin:0;">
            <label class="progga-form-label" style="margin-bottom:3px;">From</label>
            <input type="date" name="start_date" class="progga-form-control" value="{{ $startDate->format('Y-m-d') }}" style="width:150px;">
        </div>
        <div class="progga-form-group" style="margin:0;">
            <label class="progga-form-label" style="margin-bottom:3px;">To</label>
            <input type="date" name="end_date" class="progga-form-control" value="{{ $endDate->format('Y-m-d') }}" style="width:150px;">
        </div>
        <div style="min-width:160px;margin-top:18px;">
          <select name="payment_method" class="progga-select" data-placeholder="Payment Method">
            <option value="">All Payments</option>
            <option value="Cash" {{ request('payment_method') == 'Cash' ? 'selected' : '' }}>Cash</option>
            <option value="Card" {{ request('payment_method') == 'Card' ? 'selected' : '' }}>Card</option>
            <option value="Mobile Banking" {{ request('payment_method') == 'Mobile Banking' ? 'selected' : '' }}>Mobile Banking</option>
          </select>
        </div>
        <button type="submit" class="progga-btn progga-btn-primary progga-btn-sm" style="margin-top:18px;"><i class="bi bi-funnel"></i> Apply Filter</button>
        <a href="{{ route('reports.index') }}" class="progga-btn progga-btn-outline progga-btn-sm" style="margin-top:18px;"><i class="bi bi-x-circle"></i> Clear</a>
      </div>

      <div style="margin-left:auto;display:flex;gap:6px;align-items:flex-end;padding-bottom:2px;">
        <a href="{{ route('reports.index', ['start_date' => now()->format('Y-m-d'), 'end_date' => now()->format('Y-m-d')]) }}" class="progga-btn progga-btn-outline progga-btn-sm">Today</a>
        <a href="{{ route('reports.index', ['start_date' => now()->startOfWeek()->format('Y-m-d'), 'end_date' => now()->endOfWeek()->format('Y-m-d')]) }}" class="progga-btn progga-btn-outline progga-btn-sm">This Week</a>
        <a href="{{ route('reports.index', ['start_date' => now()->startOfMonth()->format('Y-m-d'), 'end_date' => now()->endOfMonth()->format('Y-m-d')]) }}" class="progga-btn progga-btn-outline progga-btn-sm">This Month</a>
      </div>
    </form>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="progga-stat-card">
            <div class="progga-stat-icon secondary"><i class="bi bi-currency-dollar"></i></div>
            <div class="progga-stat-info">
                <div class="progga-stat-label">Total Revenue</div>
                <div class="progga-stat-value">৳{{ number_format($totalRevenue, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="progga-stat-card">
            <div class="progga-stat-icon primary"><i class="bi bi-receipt"></i></div>
            <div class="progga-stat-info">
                <div class="progga-stat-label">Total Orders</div>
                <div class="progga-stat-value">{{ $totalOrders }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="progga-stat-card">
            <div class="progga-stat-icon success"><i class="bi bi-graph-up"></i></div>
            <div class="progga-stat-info">
                <div class="progga-stat-label">Avg. Order Value</div>
                <div class="progga-stat-value">৳{{ number_format($avgOrderValue, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="progga-stat-card">
            <div class="progga-stat-icon warning"><i class="bi bi-people"></i></div>
            <div class="progga-stat-info">
                <div class="progga-stat-label">Unique Customers</div>
                <div class="progga-stat-value">{{ $uniqueCustomers }}</div>
            </div>
        </div>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-xl-7">
      <div class="progga-card">
        <div class="progga-card-header">
            <div class="progga-card-title">Daily Revenue</div>
            <span class="progga-badge progga-badge-secondary">{{ $startDate->format('M d') }} – {{ $endDate->format('M d, Y') }}</span>
        </div>
        <div class="progga-card-body">
            <div class="progga-chart-container" style="height:280px;">
                <canvas id="dailySalesChart"></canvas>
            </div>
        </div>
      </div>
    </div>
    <div class="col-xl-5">
      <div class="progga-card">
        <div class="progga-card-header"><div class="progga-card-title">Monthly Revenue ({{ now()->year }})</div></div>
        <div class="progga-card-body">
            <div class="progga-chart-container" style="height:280px;">
                <canvas id="monthlySalesChart"></canvas>
            </div>
        </div>
      </div>
    </div>
  </div>

  <div class="progga-card" id="ordersSection">
    <div class="progga-card-header">
      <div class="progga-card-title">Order Report</div>
    </div>
    <div class="progga-table-wrapper" style="border:none;border-radius:0;">
      <table class="progga-table" id="ordersTable">
        <thead>
          <tr>
            <th>#Order</th>
            <th>Date &amp; Time</th>
            <th>Table</th>
            <th>Customer</th>
            <th>Waiter</th>
            <th>Items</th>
            <th>Total</th>
            <th>Payment</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          @forelse($orders as $order)
          <tr>
            <td><strong>#{{ $order->order_number }}</strong></td>
            <td>{{ $order->created_at->format('M d, h:i A') }}</td>
            <td>{{ $order->table->table_number ?? 'Takeaway' }}</td>
            <td>{{ $order->customer->name ?? 'Walk-in' }}</td>
            <td>{{ $order->waiter->name ?? 'N/A' }}</td>
            <td>{{ $order->orderDetails->sum('quantity') }}</td>
            <td><strong>৳{{ number_format($order->grand_total, 2) }}</strong></td>
            <td>
                <span class="progga-badge progga-badge-neutral">{{ $order->payment_type ?? 'N/A' }}</span>
            </td>
            <td>
                @php
                    $statusClass = strtolower($order->status);
                    if($order->status == 'Completed') $statusClass = 'primary';
                    elseif($order->status == 'Pending') $statusClass = 'warning';
                    elseif($order->status == 'Cancelled') $statusClass = 'danger';
                @endphp
                <span class="progga-badge progga-badge-{{ $statusClass }}">{{ $order->status }}</span>
            </td>
          </tr>
          @empty
          <tr><td colspan="9" class="text-center py-4 text-muted">No orders found for the selected date range.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="progga-card-footer" style="display:flex;align-items:center;justify-content:space-between;">
      <span class="progga-page-info">Showing {{ $orders->firstItem() ?? 0 }}–{{ $orders->lastItem() ?? 0 }} of {{ $orders->total() }} orders</span>
      <div class="progga-pagination">
          {{ $orders->appends(request()->query())->links('pagination::bootstrap-4') }}
      </div>
    </div>
  </div>

</main>
@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // ==========================================
    // 1. Daily Sales Chart
    // ==========================================
    const ctxDaily = document.getElementById('dailySalesChart').getContext('2d');
    new Chart(ctxDaily, {
        type: 'line',
        data: {
            labels: @json($chartLabelsDaily),
            datasets: [{
                label: 'Revenue (৳)',
                data: @json($chartDataDaily),
                borderColor: '#d5aa65',
                backgroundColor: 'rgba(213, 170, 101, 0.1)',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#21352a',
                pointBorderColor: '#fff',
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { borderDash: [4, 4] } },
                x: { grid: { display: false } }
            }
        }
    });

    // ==========================================
    // 2. Monthly Sales Chart
    // ==========================================
    const ctxMonthly = document.getElementById('monthlySalesChart').getContext('2d');
    new Chart(ctxMonthly, {
        type: 'bar',
        data: {
            labels: @json($chartLabelsMonthly),
            datasets: [{
                label: 'Revenue (৳)',
                data: @json($chartDataMonthly),
                backgroundColor: '#21352a',
                borderRadius: 4,
                barThickness: 16
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { borderDash: [4, 4] } },
                x: { grid: { display: false } }
            }
        }
    });
});
</script>
@endsection
