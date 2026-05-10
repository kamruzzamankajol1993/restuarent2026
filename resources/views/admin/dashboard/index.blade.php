@extends('admin.master.master')

@section('title')
Dashboard — Progga RMS
@endsection

@section('css')
@endsection

@section('body')

<main class="progga-content">

        <!-- Page Header -->
        <div class="progga-page-header">
          <div>
            <h1 class="progga-page-title">Dashboard</h1>
            <div class="progga-breadcrumb"><span class="progga-breadcrumb-item active">Home</span></div>
          </div>
          <div style="display:flex;gap:8px;">
            <span class="progga-live-indicator"><span class="progga-live-dot"></span> Live</span>
            <button class="progga-btn progga-btn-outline progga-btn-sm" type="button">
              <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
          </div>
        </div>

        <!-- Stat Cards -->
        <div class="row g-3 mb-4">
          <div class="col-xl-3 col-md-6">
            <div class="progga-stat-card">
              <div class="progga-stat-icon secondary"><i class="bi bi-currency-dollar"></i></div>
              <div class="progga-stat-info">
                <div class="progga-stat-label">Today's Sales</div>
                <div class="progga-stat-value">৳24,580</div>
                <div class="progga-stat-change up"><i class="bi bi-arrow-up-right"></i> +12.4% vs yesterday</div>
              </div>
            </div>
          </div>
          <div class="col-xl-3 col-md-6">
            <div class="progga-stat-card">
              <div class="progga-stat-icon success"><i class="bi bi-graph-up-arrow"></i></div>
              <div class="progga-stat-info">
                <div class="progga-stat-label">Monthly Revenue</div>
                <div class="progga-stat-value">৳3.1L</div>
                <div class="progga-stat-change up"><i class="bi bi-arrow-up-right"></i> +8.2% this month</div>
              </div>
            </div>
          </div>
          <div class="col-xl-3 col-md-6">
            <div class="progga-stat-card">
              <div class="progga-stat-icon primary"><i class="bi bi-receipt"></i></div>
              <div class="progga-stat-info">
                <div class="progga-stat-label">Total Orders</div>
                <div class="progga-stat-value">148</div>
                <div class="progga-stat-change up"><i class="bi bi-arrow-up-right"></i> +18 today</div>
              </div>
            </div>
          </div>
          <div class="col-xl-3 col-md-6">
            <div class="progga-stat-card">
              <div class="progga-stat-icon warning"><i class="bi bi-layout-wtf"></i></div>
              <div class="progga-stat-info">
                <div class="progga-stat-label">Running Tables</div>
                <div class="progga-stat-value">7 / 18</div>
                <div class="progga-stat-change neutral"><i class="bi bi-dash"></i> 11 available</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Charts Row -->
        <div class="row g-3 mb-4">
          <!-- Revenue Chart -->
          <div class="col-xl-8">
            <div class="progga-card">
              <div class="progga-card-header">
                <div>
                  <div class="progga-card-title">Revenue Overview</div>
                  <div class="progga-card-subtitle">Daily revenue trend</div>
                </div>
                <div class="progga-chart-toggle">
                  <button class="progga-chart-toggle-btn active" data-revenue-period="7">7 Days</button>
                  <button class="progga-chart-toggle-btn" data-revenue-period="30">30 Days</button>
                </div>
              </div>
              <div class="progga-card-body">
                <div class="progga-chart-container" style="height:260px;">
                  <canvas id="revenueChart"></canvas>
                </div>
              </div>
            </div>
          </div>

          <!-- Order Status Donut -->
          <div class="col-xl-4">
            <div class="progga-card" style="height:100%;">
              <div class="progga-card-header">
                <div class="progga-card-title">Order Status</div>
              </div>
              <div class="progga-card-body" style="display:flex;flex-direction:column;align-items:center;">
                <div class="progga-chart-container" style="height:220px;width:100%;">
                  <canvas id="orderStatusChart"></canvas>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Top Items + Kitchen Queue -->
        <div class="row g-3 mb-4">
          <!-- Top Items -->
          <div class="col-xl-5">
            <div class="progga-card">
              <div class="progga-card-header">
                <div class="progga-card-title">Top Selling Items</div>
                <span class="progga-badge progga-badge-secondary">This Week</span>
              </div>
              <div class="progga-card-body">
                <div class="progga-chart-container" style="height:220px;">
                  <canvas id="topItemsChart"></canvas>
                </div>
              </div>
            </div>
          </div>

          <!-- Kitchen Pending -->
          <div class="col-xl-7">
            <div class="progga-card">
              <div class="progga-card-header">
                <div class="progga-card-title">
                  Kitchen Queue
                  <span class="progga-badge progga-badge-danger" style="margin-left:8px;">5 Pending</span>
                </div>
                <span class="progga-live-indicator"><span class="progga-live-dot"></span> Live</span>
              </div>
              <div class="progga-card-body" style="padding:0;">
                <div class="progga-table-wrapper" style="border:none;border-radius:0;">
                  <table class="progga-table">
                    <thead>
                      <tr>
                        <th>Order</th><th>Table</th><th>Items</th><th>Time</th><th>Status</th><th>Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td>#128</td><td>T-03</td><td>3 items</td>
                        <td><span style="color:var(--progga-warning);font-weight:700;">18 min</span></td>
                        <td><span class="progga-badge progga-status-cooking">Cooking</span></td>
                        <td><button class="progga-btn progga-btn-success progga-btn-sm">Ready</button></td>
                      </tr>
                      <tr>
                        <td>#129</td><td>T-06</td><td>2 items</td>
                        <td>8 min</td>
                        <td><span class="progga-badge progga-status-pending">Pending</span></td>
                        <td><button class="progga-btn progga-btn-outline progga-btn-sm">Start</button></td>
                      </tr>
                      <tr>
                        <td>#130</td><td>T-09</td><td>5 items</td>
                        <td>3 min</td>
                        <td><span class="progga-badge progga-status-pending">Pending</span></td>
                        <td><button class="progga-btn progga-btn-outline progga-btn-sm">Start</button></td>
                      </tr>
                      <tr>
                        <td>#126</td><td>T-01</td><td>4 items</td>
                        <td>25 min</td>
                        <td><span class="progga-badge progga-status-ready">Ready</span></td>
                        <td><button class="progga-btn progga-btn-primary progga-btn-sm">Deliver</button></td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
              <div class="progga-card-footer" style="text-align:right;">
                <a href="kitchen.php" class="progga-btn progga-btn-outline progga-btn-sm">
                  View Full Kitchen Board <i class="bi bi-arrow-right"></i>
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Orders -->
        <div class="progga-card">
          <div class="progga-card-header">
            <div class="progga-card-title">Recent Orders</div>
            <a href="reports.php" class="progga-btn progga-btn-outline progga-btn-sm">View All</a>
          </div>
          <div class="progga-card-body" style="padding:0;">
            <div class="progga-table-wrapper" style="border:none;border-radius:0;">
              <table class="progga-table">
                <thead>
                  <tr>
                    <th>#</th><th>Table</th><th>Items</th><th>Waiter</th><th>Amount</th><th>Payment</th><th>Status</th><th>Time</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>#130</td><td>T-09</td><td>5</td><td>Rafiq</td><td>৳1,840</td>
                    <td><span class="progga-badge progga-badge-primary">Cash</span></td>
                    <td><span class="progga-badge progga-status-pending">Pending</span></td>
                    <td>3 min ago</td>
                  </tr>
                  <tr>
                    <td>#129</td><td>T-06</td><td>2</td><td>Salma</td><td>৳620</td>
                    <td><span class="progga-badge progga-badge-info">Card</span></td>
                    <td><span class="progga-badge progga-status-cooking">Cooking</span></td>
                    <td>8 min ago</td>
                  </tr>
                  <tr>
                    <td>#128</td><td>T-03</td><td>3</td><td>Rafiq</td><td>৳1,120</td>
                    <td><span class="progga-badge progga-badge-primary">Cash</span></td>
                    <td><span class="progga-badge progga-status-cooking">Cooking</span></td>
                    <td>18 min ago</td>
                  </tr>
                  <tr>
                    <td>#127</td><td>T-11</td><td>6</td><td>Jamal</td><td>৳2,680</td>
                    <td><span class="progga-badge progga-badge-warning">bKash</span></td>
                    <td><span class="progga-badge progga-status-delivered">Delivered</span></td>
                    <td>32 min ago</td>
                  </tr>
                  <tr>
                    <td>#126</td><td>T-01</td><td>4</td><td>Salma</td><td>৳1,560</td>
                    <td><span class="progga-badge progga-badge-primary">Cash</span></td>
                    <td><span class="progga-badge progga-status-ready">Ready</span></td>
                    <td>25 min ago</td>
                  </tr>
                  <tr>
                    <td>#125</td><td>T-05</td><td>2</td><td>Jamal</td><td>৳760</td>
                    <td><span class="progga-badge progga-badge-info">Card</span></td>
                    <td><span class="progga-badge progga-status-delivered">Delivered</span></td>
                    <td>45 min ago</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

      </main>

@endsection

@section('script')
@endsection
