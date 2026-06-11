@extends('admin.master.master')
@section('title', 'Sales & Order Summary Report — TableTrack RMS')
@section('body')
<main class="progga-content">
  <div class="progga-page-header">
    <div>
        <h1 class="progga-page-title">Sales &amp; Order Report</h1>
        <div class="progga-breadcrumb"><a href="{{ route('home') }}" class="progga-breadcrumb-item">Dashboard</a><span class="progga-breadcrumb-sep">/</span><span class="progga-breadcrumb-item active">Reports</span></div>
    </div>
    <div style="display:flex;gap:8px;">
        <button type="button" onclick="exportReport('pdf', 'sales_order')" class="progga-btn progga-btn-outline progga-btn-sm"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
        <button type="button" onclick="exportReport('excel', 'sales_order')" class="progga-btn progga-btn-outline progga-btn-sm"><i class="bi bi-file-earmark-excel"></i> Excel</button>
    </div>
  </div>

  <div class="progga-card" style="margin-bottom:16px;">
      @include('admin.reports.partials.filter_component')
  </div>

  <div class="row g-3 mb-4">
    <div class="col-md-3"><div class="progga-stat-card"><div class="progga-stat-icon secondary"><i class="bi bi-currency-dollar"></i></div><div class="progga-stat-info"><div class="progga-stat-label">Total Revenue</div><div class="progga-stat-value" id="cardRev">৳{{ number_format($totalRevenue, 2) }}</div></div></div></div>
    <div class="col-md-3"><div class="progga-stat-card"><div class="progga-stat-icon primary"><i class="bi bi-receipt"></i></div><div class="progga-stat-info"><div class="progga-stat-label">Total Orders</div><div class="progga-stat-value" id="cardOrders">{{ $totalOrders }}</div></div></div></div>
    <div class="col-md-3"><div class="progga-stat-card"><div class="progga-stat-icon success"><i class="bi bi-graph-up"></i></div><div class="progga-stat-info"><div class="progga-stat-label">Avg. Order Value</div><div class="progga-stat-value" id="cardAvg">৳{{ number_format($avgOrderValue, 2) }}</div></div></div></div>
    <div class="col-md-3"><div class="progga-stat-card"><div class="progga-stat-icon warning"><i class="bi bi-people"></i></div><div class="progga-stat-info"><div class="progga-stat-label">Unique Customers</div><div class="progga-stat-value" id="cardCust">{{ $uniqueCustomers }}</div></div></div></div>
  </div>

  <div class="progga-card">
    <div class="progga-table-wrapper" style="border:none;border-radius:0;">
      <table class="progga-table">
        <thead>
          <tr>
            <th>Period</th>
            <th>Total Orders</th>
            <th>Total Sales</th>
            <th>Discount Report</th>
          </tr>
        </thead>
        <tbody id="salesReportContainer">
            @include('admin.reports.partials.sales_table_rows')
        </tbody>
      </table>
    </div>
  </div>
</main>
<script>
function updateReportDOM(data) {
    document.getElementById('salesReportContainer').innerHTML = data.html;
    document.getElementById('cardRev').innerText = data.summary.revenue;
    document.getElementById('cardOrders').innerText = data.summary.orders;
    document.getElementById('cardAvg').innerText = data.summary.avg;
    document.getElementById('cardCust').innerText = data.summary.customers;
}
</script>
@endsection
