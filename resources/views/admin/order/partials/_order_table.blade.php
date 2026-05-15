<div class="progga-table-wrapper" style="border:none;border-radius:0;">
  <table class="progga-table" id="ordersTable">
    <thead>
      <tr>
        <th>Order #</th>
        <th>Customer</th>
        <th>Items</th>
        <th>Total</th>
        <th>Payment</th>
        <th>Status</th>
        <th>Time</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      @forelse($orders as $order)
          @php
              $statusClass = strtolower($order->status);
              $badgeClass = 'neutral';
              $iconClass = 'clock-fill';
              if($order->status == 'Completed') { $badgeClass = 'primary'; $iconClass = 'check2-all'; }
              elseif($order->status == 'Pending') { $badgeClass = 'warning'; $iconClass = 'clock-fill'; }
              elseif($order->status == 'Cooking') { $badgeClass = 'info'; $iconClass = 'fire'; }
              elseif($order->status == 'Ready') { $badgeClass = 'success'; $iconClass = 'check-circle-fill'; }
              elseif($order->status == 'Cancelled') { $badgeClass = 'danger'; $iconClass = 'x-circle-fill'; }
          @endphp
          <tr class="status-{{ $statusClass }}">
            <td><strong>#{{ $order->order_number }}</strong></td>
            <td>
              <div class="progga-order-customer">
                <span class="progga-order-customer-name">{{ $order->customer->name ?? 'Walk-in Customer' }}</span>
                <span class="progga-order-customer-table">
                  @if($order->order_type == 'Takeaway')
                    <i class="bi bi-bag"></i> Takeaway
                  @else
                    <i class="bi bi-layout-wtf"></i> Table T-{{ $order->table->table_number ?? 'N/A' }}
                  @endif
                </span>
              </div>
            </td>
            <td>
              <div class="progga-order-items-preview">
                @php
                    $previewItems = $order->orderDetails->take(2)->pluck('product_name')->implode(', ');
                    $remaining = $order->orderDetails->count() - 2;
                @endphp
                <span class="progga-order-items-main">{{ $previewItems }}</span>
                @if($remaining > 0)
                    <span class="progga-order-items-more">+{{ $remaining }} more item(s)</span>
                @endif
              </div>
            </td>
            <td><strong>৳{{ number_format($order->grand_total, 2) }}</strong></td>
            <td>
              <span class="progga-badge progga-badge-neutral">
                <i class="bi {{ $order->payment_type == 'Cash' ? 'bi-cash' : ($order->payment_type == 'Card' ? 'bi-credit-card' : 'bi-phone') }}"></i>
                {{ $order->payment_type }}
              </span>
            </td>
            <td>
              <span class="progga-badge progga-badge-{{ $badgeClass }}"><i class="bi {{ $iconClass }}"></i> {{ $order->status }}</span>
            </td>
            <td><span class="progga-order-time">{{ $order->created_at->diffForHumans() }}</span></td>
            <td>
              <div class="progga-table-actions">
                <button class="progga-btn progga-btn-outline progga-btn-icon progga-btn-sm" title="View Order" onclick="viewOrder({{ $order->id }})"><i class="bi bi-eye"></i></button>
                <a href="{{ route('pos.invoice', $order->id) }}" target="_blank" class="progga-btn progga-btn-outline progga-btn-icon progga-btn-sm" title="Print Receipt"><i class="bi bi-printer"></i></a>
              </div>
            </td>
          </tr>
      @empty
          <tr><td colspan="8" class="text-center py-4">No orders found.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>

<div class="progga-card-footer" style="display:flex;align-items:center;justify-content:space-between;">
  <span class="progga-page-info">Showing {{ $orders->firstItem() ?? 0 }}–{{ $orders->lastItem() ?? 0 }} of {{ $orders->total() }} orders</span>
  <div class="progga-pagination">
      {{ $orders->links('pagination::bootstrap-4') }}
  </div>
</div>
