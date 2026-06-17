@extends('admin.master.master')
@section('title', 'Order List — TableTrack RMS')

@section('css')
<style>
    /* ── Order List page-specific ── */
    .progga-order-items-preview { display: flex; flex-direction: column; gap: 2px; }
    .progga-order-items-main {
      font-size: 13px; font-weight: 600; color: var(--progga-text);
      white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 200px;
    }
    .progga-order-items-more { font-size: 11px; color: var(--progga-text-muted); font-weight: 600; }
    .progga-order-customer { display: flex; flex-direction: column; gap: 2px; }
    .progga-order-customer-name { font-size: 13px; font-weight: 700; color: var(--progga-text); }
    .progga-order-customer-table {
      display: inline-flex; align-items: center; gap: 4px; font-size: 11px; color: var(--progga-text-muted); font-weight: 600;
    }
    .progga-order-time { font-size: 12px; color: var(--progga-text-muted); font-weight: 600; }

    /* Status row left accent */
    .progga-table tbody tr.status-pending   td:first-child { border-left: 3px solid var(--progga-warning); }
    .progga-table tbody tr.status-cooking td:first-child { border-left: 3px solid var(--progga-info); }
    .progga-table tbody tr.status-ready     td:first-child { border-left: 3px solid var(--progga-success); }
    .progga-table tbody tr.status-completed td:first-child { border-left: 3px solid var(--progga-primary); }
    .progga-table tbody tr.status-cancelled td:first-child { border-left: 3px solid var(--progga-danger); }

    /* Order detail modal items table */
    .progga-order-detail-items td { padding: 10px 12px; }
    .progga-order-summary-row {
      display: flex; justify-content: space-between; padding: 6px 0; font-size: 13.5px; color: var(--progga-text-muted);
      border-bottom: 1px solid var(--progga-border-light);
    }
    .progga-order-summary-row:last-child { border-bottom: none; }
    .progga-order-summary-row.grand { font-size: 15px; font-weight: 800; color: var(--progga-primary); padding-top: 10px; }
    .progga-order-summary-row span:last-child { font-weight: 700; color: var(--progga-text); }
    .progga-order-summary-row.grand span:last-child { color: var(--progga-primary); }

    .progga-order-pagination-footer {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      padding: 14px 16px;
      border-top: 1px solid var(--progga-border-light);
      flex-wrap: wrap;
    }
    .progga-pagination-wrap { display: flex; justify-content: flex-end; }
    .progga-pagination {
      display: flex;
      align-items: center;
      gap: 6px;
      flex-wrap: wrap;
    }
    .progga-page-btn,
    .progga-page-num {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 5px;
      min-height: 34px;
      padding: 7px 11px;
      border: 1px solid var(--progga-border-light);
      border-radius: 8px;
      background: #fff;
      color: var(--progga-text);
      font-size: 12px;
      font-weight: 800;
      text-decoration: none;
      transition: all .15s ease;
    }
    .progga-page-num { min-width: 34px; padding-left: 9px; padding-right: 9px; }
    .progga-page-btn:hover,
    .progga-page-num:hover {
      border-color: var(--progga-primary);
      color: var(--progga-primary);
      background: rgba(33, 53, 42, 0.05);
    }
    .progga-page-num.active {
      background: var(--progga-primary);
      border-color: var(--progga-primary);
      color: #fff;
      cursor: default;
    }
    .progga-page-btn.disabled {
      opacity: .45;
      pointer-events: none;
      cursor: not-allowed;
      background: #f8f9fa;
    }
    .progga-page-ellipsis {
      padding: 0 4px;
      color: var(--progga-text-muted);
      font-weight: 800;
    }
    @media (max-width: 767.98px) {
      .progga-order-pagination-footer {
        justify-content: center;
        text-align: center;
      }
      .progga-pagination {
        justify-content: center;
      }
    }

</style>
@endsection

@section('body')
<main class="progga-content">
  <div class="progga-page-header">
    <div>
      <h1 class="progga-page-title">Order List</h1>
      <div class="progga-breadcrumb">
        <a href="{{ route('home') }}" class="progga-breadcrumb-item">Dashboard</a>
        <span class="progga-breadcrumb-sep">/</span>
        <span class="progga-breadcrumb-item active">Order List</span>
      </div>
    </div>
    <div style="display:flex; gap:8px;">
        <a href="{{ route('reviews.index') }}" class="progga-btn progga-btn-outline progga-btn-sm" style="border-color: #ffc107; color: #997300; font-weight: 700; background: #fffdf5;">
            <i class="bi bi-star-fill text-warning"></i> View Reviews
        </a>
    <button type="button" onclick="exportOrderPDF()" class="progga-btn progga-btn-outline progga-btn-sm">
        <i class="bi bi-file-earmark-pdf"></i> Export PDF
    </button>
    <button type="button" onclick="exportOrderExcel()" class="progga-btn progga-btn-outline progga-btn-sm" style="border-color:#198754;color:#198754;background:#f8fff9;">
        <i class="bi bi-file-earmark-excel"></i> Export Excel
    </button>
    <a href="{{ route('pos.index') }}" class="progga-btn progga-btn-primary progga-btn-sm"><i class="bi bi-display"></i> Open POS</a>
</div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="progga-stat-card">
        <div class="progga-stat-icon primary"><i class="bi bi-receipt-cutoff"></i></div>
        <div class="progga-stat-info">
          <div class="progga-stat-label">Today's Orders</div>
          <div class="progga-stat-value">{{ $stats['today_orders'] }}</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="progga-stat-card">
        <div class="progga-stat-icon warning"><i class="bi bi-hourglass-split"></i></div>
        <div class="progga-stat-info">
          <div class="progga-stat-label">Active (Pending/Cooking)</div>
          <div class="progga-stat-value">{{ $stats['active_orders'] }}</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="progga-stat-card">
        <div class="progga-stat-icon success"><i class="bi bi-check-circle-fill"></i></div>
        <div class="progga-stat-info">
          <div class="progga-stat-label">Completed</div>
          <div class="progga-stat-value">{{ $stats['completed_orders'] }}</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="progga-stat-card">
        <div class="progga-stat-icon secondary"><i class="bi bi-currency-exchange"></i></div>
        <div class="progga-stat-info">
          <div class="progga-stat-label">Revenue Today</div>
          <div class="progga-stat-value">৳{{ number_format($stats['revenue_today'], 0) }}</div>
        </div>
      </div>
    </div>
  </div>

  <div class="progga-card" style="margin-bottom:16px;">
    <div class="progga-filters-bar">
      <div class="progga-search" style="flex:1;min-width:200px;max-width:300px;">
        <i class="bi bi-search progga-search-icon"></i>
        <input type="text" class="progga-form-control" id="searchOrder" placeholder="Search order #, customer…">
      </div>
      <div style="min-width:160px;">
        <select class="progga-select filter-trigger" id="filterStatus" data-placeholder="All Status">
          <option value="">All Status</option>
          <option value="Pending">Pending</option>
          <option value="Cooking">Cooking</option>
          <option value="Ready">Ready</option>
          <option value="Completed">Completed</option>
          <option value="Cancelled">Cancelled</option>
        </select>
      </div>
      <div style="min-width:150px;">
        <select class="progga-select filter-trigger" id="filterDate" data-placeholder="Date Range">
          <option value="">All Time</option>
          <option value="Today">Today</option>
          <option value="Yesterday">Yesterday</option>
          <option value="This Week">This Week</option>
          <option value="This Month">This Month</option>
        </select>
      </div>
      <div style="min-width:140px;">
        <select class="progga-select filter-trigger" id="filterPayment" data-placeholder="Payment">
          <option value="">All Payments</option>
          <option value="Cash">Cash</option>
          <option value="Card">Card</option>
          <option value="Mobile Banking">Mobile Banking</option>
          <option value="Split">Split</option>
        </select>
      </div>
    </div>
  </div>

  <div class="progga-card" id="order_data_container">
      @include('admin.order.partials._order_table')
  </div>

</main>

<div class="modal fade progga-modal" id="orderDetailModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content" id="modalContentArea">
        </div>
  </div>
</div>

<div class="modal fade progga-modal" id="deleteHistoryModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content" id="deleteHistoryModalContent">
        </div>
  </div>
</div>

@endsection

@section('script')
<script>
    function fetchOrders(url) {
        let search = $('#searchOrder').val();
        let status = $('#filterStatus').val();
        let date = $('#filterDate').val();
        let payment = $('#filterPayment').val();

        $('#order_data_container').css('opacity', '0.5');
        $.ajax({
            url: url || "{{ route('order.index') }}",
            data: { search: search, status: status, date_range: date, payment: payment },
            success: function(data) {
                $('#order_data_container').html(data).css('opacity', '1');
            }
        });
    }

    $('.filter-trigger').on('change', function() { fetchOrders(); });

    let timer;
    $('#searchOrder').on('keyup', function() {
        clearTimeout(timer);
        timer = setTimeout(() => { fetchOrders(); }, 500);
    });

    $(document).on('click', '.progga-pagination a', function(e) {
        e.preventDefault();
        fetchOrders($(this).attr('href'));
    });

    // Load Modal Data
    window.viewOrder = function(id) {
        $('#modalContentArea').html('<div class="text-center p-5"><div class="spinner-border text-primary"></div></div>');
        new bootstrap.Modal(document.getElementById('orderDetailModal')).show();

        $.get("{{ url('orders') }}/" + id, function(data) {
            $('#modalContentArea').html(data);
        }).fail(function() {
            $('#modalContentArea').html('<div class="p-4 text-danger text-center">Failed to load data.</div>');
        });
    };


    window.viewDeleteHistory = function(id) {
        $('#deleteHistoryModalContent').html('<div class="text-center p-5"><div class="spinner-border text-primary"></div></div>');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteHistoryModal')).show();

        $.get("{{ url('orders') }}/" + id + "/delete-history", function(data) {
            $('#deleteHistoryModalContent').html(data);
        }).fail(function() {
            $('#deleteHistoryModalContent').html('<div class="p-4 text-danger text-center">Failed to load delete history.</div>');
        });
    };

    window.deleteOrder = function(id) {
    Swal.fire({
        title: 'Delete this order?',
        text: 'This action cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        allowOutsideClick: false
    }).then(function(result) {
        if (result.isConfirmed) {
            $.ajax({
                url: "{{ url('orders') }}/" + id,
                type: "DELETE",
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                beforeSend: function() {
                    $('#order_data_container').css('opacity', '0.5');
                },
                success: function(res) {
                    if (res.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: res.message,
                            timer: 1500,
                            showConfirmButton: false
                        });

                        fetchOrders();
                    } else {
                        Swal.fire('Error', res.message || 'Delete failed.', 'error');
                        $('#order_data_container').css('opacity', '1');
                    }
                },
                error: function(xhr) {
                    let message = 'Server failed to delete order.';

                    if (xhr.status === 403) {
                        message = 'You do not have permission to delete this order.';
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }

                    Swal.fire('Error', message, 'error');
                    $('#order_data_container').css('opacity', '1');
                }
            });
        }
    });
};

   // PDF এবং Excel export একই current filter ব্যবহার করবে।
   function buildOrderExportQueryString() {
        let search = $('#searchOrder').val() || '';
        let status = $('#filterStatus').val() || '';
        let date = $('#filterDate').val() || '';
        let payment = $('#filterPayment').val() || '';

        return "search=" + encodeURIComponent(search) +
               "&status=" + encodeURIComponent(status) +
               "&date_range=" + encodeURIComponent(date) +
               "&payment=" + encodeURIComponent(payment);
    }

   function exportOrderPDF() {
        // নতুন ট্যাবে filtered PDF ওপেন করা।
        window.open("{{ route('order.export_pdf') }}?" + buildOrderExportQueryString(), '_blank');
    }

   function exportOrderExcel() {
        // একই filtered data Excel file হিসেবে download হবে।
        window.location.href = "{{ route('order.export_excel') }}?" + buildOrderExportQueryString();
    }
</script>
@endsection
