<div class="progga-oc-header">
    <div>
        <div class="progga-oc-table-label">Occupied Table</div>
        <div class="progga-oc-table-num" id="ocTableNum">{{ $order->table->table_number ?? 'Takeaway' }}</div>
        <div class="progga-oc-table-meta" id="ocTableMeta">{{ $order->table->zone->name ?? 'Main' }} · {{ $order->table->seating_capacity ?? 0 }} seats</div>
        <div class="progga-oc-chips" id="ocChips">
            <span class="progga-oc-chip"><i class="bi bi-receipt"></i> #{{ $order->order_number }}</span>
            <span class="progga-oc-chip"><i class="bi bi-person"></i> <span id="ocWaiterName">{{ $order->waiter->name ?? 'Unassigned' }}</span></span>
            <span class="progga-oc-chip"><i class="bi bi-person-check"></i> <span id="ocCustomerName">{{ $order->customer->name ?? 'Walk-in' }}</span></span>
        </div>
    </div>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
</div>

<div class="progga-oc-body offcanvas-body" id="ocBody">
    <div class="progga-oc-section-label" style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #888; margin-bottom: 12px; letter-spacing: 0.5px;">Current Order Items</div>

    @foreach($order->kots as $kot)
        <div class="progga-oc-kot">
            <div class="progga-oc-kot-head">
                <div>
                    <span class="progga-oc-kot-label">{{ $kot->kot_number }}</span>
                    <span class="progga-oc-kot-time"><i class="bi bi-clock me-1"></i>{{ $kot->created_at->format('h:i A') }}</span>
                </div>
                @if($kot->kitchen_status == 'Pending')
                    <span class="badge bg-warning text-dark" style="font-size: 10px;">Pending</span>
                @elseif($kot->kitchen_status == 'Cooking')
                    <span class="badge bg-info text-white" style="font-size: 10px;">Cooking</span>
                @else
                    <span class="badge bg-success" style="font-size: 10px;">Ready</span>
                @endif
            </div>

           @foreach($kot->orderDetails as $item)
                @php $addons = json_decode($item->addons, true) ?? []; @endphp

                {{-- Unavailable হলে opacity কমিয়ে দেওয়া হবে --}}
                <div class="progga-oc-item {{ $item->is_unavailable ? 'opacity-50' : '' }}">
                    <span class="progga-oc-item-name">

                        @if($item->is_unavailable)
                            <span class="badge bg-danger" style="font-size: 9px; margin-right: 5px;">Unavailable</span>
                            <del class="text-muted">{{ $item->product_name }}</del>
                        @else
                            {{ $item->product_name }}
                        @endif

                        @if(count($addons) > 0)
                            <div style="font-size: 10px; color: #777; font-weight: normal; margin-top: 2px;">
                                + @foreach($addons as $addon) {{ $addon['name'] }}{{ !$loop->last ? ', ' : '' }} @endforeach
                            </div>
                        @endif
                        @if($item->food_note)
                            <div style="font-size: 10px; color: #d33; font-style: italic; margin-top: 2px;">* {{ $item->food_note }}</div>
                        @endif
                    </span>
                    <span class="progga-oc-item-qty">×{{ $item->quantity }}</span>

                    <span class="progga-oc-item-price">
                        @if($item->is_unavailable)
                            <del class="text-danger">৳{{ round($item->subtotal) }}</del>
                        @else
                            ৳{{ round($item->subtotal) }}
                        @endif
                    </span>
                </div>
            @endforeach
        </div>
    @endforeach
</div>

<div class="progga-oc-footer">
    <div id="ocTotals" style="margin-bottom: 15px;">
        <div class="progga-oc-total-row">
            <span>Subtotal</span><span>৳{{ number_format($order->subtotal, 0) }}</span>
        </div>

 @if($order->service_charge > 0)
        <div class="progga-oc-total-row">
            <span>Service Charge ({{ $taxSettingServiceCharge }}%)</span><span>৳{{ number_format($order->service_charge, 0) }}</span>
        </div>
        @endif

        <div class="progga-oc-total-row">
            <span>{{ $taxSettingTaxLabel }} ({{ $taxSettingVatRate }}%)</span><span>৳{{ number_format($order->vat_tax, 0) }}</span>
        </div>


@if($order->discount_amount > 0)
        <div class="progga-oc-total-row" style="color: #d33;">
            <span>Discount ({{ ucfirst($order->discount_type) }})</span>
            <span>−৳{{ number_format($order->discount_amount, 0) }}</span>
        </div>
        @endif
        <div class="progga-oc-total-row grand">
            <span>TOTAL</span><span>৳{{ number_format($order->grand_total, 0) }}</span>
        </div>
    </div>

    <div class="progga-oc-actions">
        <button class="progga-btn progga-btn-outline" id="btnContinueOrdering" style="flex: 1;"
                data-order-id="{{ $order->id }}"
                data-table-id="{{ $order->table_id }}"
                data-waiter-id="{{ $order->waiter_id }}"
                data-waiter-name="{{ $order->waiter->name ?? '' }}"
                data-customer-id="{{ $order->customer_id }}"
                data-customer-name="{{ $order->customer->name ?? '' }}">
            <i class="bi bi-plus-circle"></i> Add More Food
        </button>

        @php
            $payItems = [];
            foreach($order->kots as $kot) {
                foreach($kot->orderDetails as $item) {
                    // শুধুমাত্র Available আইটেমগুলো পেমেন্ট মোডালে যাবে
                    if(!$item->is_unavailable) {
                        $payItems[] = [
                            'name' => $item->product_name,
                            'qty' => $item->quantity,
                            'total' => $item->subtotal
                        ];
                    }
                }
            }
        @endphp

        @if($kitchenBusy)
            <button class="progga-btn progga-btn-secondary" disabled style="flex: 1; opacity: 0.6;">
                <i class="bi bi-hourglass-split"></i> Kitchen Busy
            </button>
        @else
            <button class="progga-btn progga-btn-primary" id="ocPayBtn" style="flex: 1;"
       onclick='openPaymentModal({
           order_id: "{{ $order->id }}",
           order_type: "dine_in",
           table_no: "{{ $order->table->table_number ?? "Takeaway" }}",
           subtotal: {{ $order->subtotal ?? 0 }},
           items: @json($payItems)
       })'>
    <i class="bi bi-credit-card"></i> Payment
</button>
        @endif
    </div>
</div>
