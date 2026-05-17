<div class="progga-pos-cart-items" id="posCartItems">
    @forelse($cart as $cartId => $item)
        <div class="progga-pos-cart-item">
            <div class="progga-pos-item-name">
                {{ $item['name'] }}
                @if(count($item['addons']) > 0)
                    <div style="font-size: 10px; color: #777; font-weight: normal; margin-top: 2px;">
                        @foreach($item['addons'] as $addon) +{{ $addon['name'] }} @endforeach
                    </div>
                @endif
            </div>
            <div class="progga-pos-item-controls">
                <button class="progga-qty-btn" type="button" onclick="updateQty('{{ $cartId }}', 'minus')">−</button>
                <span class="progga-qty-display">{{ $item['qty'] }}</span>
                <button class="progga-qty-btn" type="button" onclick="updateQty('{{ $cartId }}', 'plus')">+</button>
                <span class="progga-pos-item-total">৳{{ ($item['price'] + $item['addon_total']) * $item['qty'] }}</span>
                <button class="progga-pos-item-remove" type="button" onclick="removeCartItem('{{ $cartId }}')">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
            <div class="progga-pos-item-note">
                <input class="progga-form-control progga-pos-item-note-input"
                       placeholder="Add note..."
                       value="{{ $item['note'] ?? '' }}"
                       onchange="updateItemNote('{{ $cartId }}', this.value)">
            </div>
        </div>
    @empty
        <div class="text-center text-muted py-5 mt-4" style="font-size: 14px;">🛒 Cart is empty!</div>
    @endforelse
</div>

@php
    $vat_amount = ($subtotal * $vat_rate) / 100;
    $service_amount = ($subtotal * $service_charge_rate) / 100;
    $grand_total = $subtotal + $vat_amount + $service_amount;
@endphp

<div class="progga-pos-cart-totals">
    <div class="progga-pos-total-row">
        <span>Subtotal</span>
        <span>৳{{ number_format($subtotal, 2) }}</span>
    </div>
 @if($service_charge_rate > 0)
    <div class="progga-pos-total-row">
        <span>Service Charge ({{ $taxSettingServiceCharge }}%)</span>
        <span id="display_service">৳{{ number_format($service_amount, 2) }}</span>
    </div>
    @endif
    <div class="progga-pos-total-row">
        <span>{{ $taxSettingTaxLabel }} ({{ $taxSettingVatRate }}%)</span>
        <span id="display_vat">৳{{ number_format($vat_amount, 2) }}</span>
    </div>

    <div class="progga-pos-total-row grand">
        <span>TOTAL</span>
        <span id="display_grand_total">৳{{ number_format($grand_total, 2) }}</span>
    </div>
</div>

<div class="progga-pos-cart-actions p-3">
    <div class="progga-pos-total-row d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom" style="font-size: 13px; font-weight: 700; color: #555;">
        <span><i class="bi bi-clock-history text-warning me-1"></i> Preparation Time (Min)</span>
        <input type="number" class="form-control form-control-sm text-center" id="cart_prep_time" placeholder="20" value="20" min="1" style="width: 70px; font-weight: bold; border: 1.5px solid var(--progga-border);">
    </div>

    <div id="takeawayActions" style="display: none;">
        <div class="d-flex gap-2">
            <button class="progga-btn progga-btn-outline w-50" id="btnHoldOrder"><i class="bi bi-pause-circle"></i> Hold</button>
            <button class="progga-btn progga-btn-primary w-50" id="btnDirectPay"><i class="bi bi-credit-card"></i> Pay Now</button>
        </div>
    </div>

    <button class="progga-btn progga-btn-primary w-100" id="btnSendToKitchen" style="padding:14px; font-weight:800;">
        <i class="bi bi-fire"></i> Send to Kitchen
    </button>
</div>

<script>
    // Toggle Takeaway/Delivery Buttons
    if(currentOrder.order_type === 'takeaway' || currentOrder.order_type === 'delivery') {
        $('#takeawayActions').css('display', 'grid');
        $('#btnSendToKitchen').hide();
    } else {
        $('#takeawayActions').hide();
        $('#btnSendToKitchen').show();
    }

    // Grand Total & Mobile FAB Logic
   function calculateGrandTotal() {
        // ১. ব্লেড/পিএইচপি থেকে আসা বেসিক ভ্যালুগুলো নেওয়া
        let subtotal = parseFloat("{{ $subtotal ?? 0 }}") || 0;
        let vat_rate = parseFloat("{{ $vat_rate ?? 0 }}") || 0;
        let service_rate = parseFloat("{{ $service_charge_rate ?? 0 }}") || 0;

        // ২. ভ্যাট ও সার্ভিস চার্জ সরাসরি সাবটোটালের ওপর হিসাব করা
        let vat = (subtotal * vat_rate) / 100;
        let service = (subtotal * service_rate) / 100;

        // ৩. গ্র্যান্ড টোটাল হিসাব (কার্টে ডিসকাউন্ট নেই, তাই সরাসরি যোগ)
        let grand = subtotal + vat + service;

        // ৪. সাইডবারে UI (Text) আপডেট করা
        $('#display_grand_total').text('৳' + grand.toFixed(2));
        $('#display_vat').text('৳' + vat.toFixed(2));

        if(document.getElementById('display_service')) {
            $('#display_service').text('৳' + service.toFixed(2));
        }

        // ৫. মোবাইল ভিউয়ের Floating Cart Button (FAB) আপডেট করা
        if($('#fabCartTotal').length) {
            $('#fabCartTotal').text('৳' + grand.toFixed(2));
        }

        // ৬. গ্লোবাল currentOrder ভেরিয়েবল আপডেট করা
        if (typeof currentOrder !== 'undefined') {
            currentOrder.discount_type = 'fixed';
            currentOrder.discount_value = 0;
            currentOrder.grand_total = grand;
        }
    }

    // Dynamic Cart Count for Mobile FAB
    $(document).ready(function() {
        let totalQty = 0;
        @foreach($cart as $item)
            totalQty += {{ $item['qty'] }};
        @endforeach

        if(totalQty > 0) {
            $('#posCartFab').css('display', 'flex'); // Show FAB
            $('#fabCartCount').text(totalQty);
            $('#headerCartCount').text(totalQty).show();
        } else {
            $('#posCartFab').hide(); // Hide FAB if empty
            $('#headerCartCount').hide();
        }

        // Initial Calculation
        calculateGrandTotal();
    });

    // Direct Pay Button Logic (For Takeaway & Delivery)
    $('#btnDirectPay').on('click', function() {
        let itemsArr = [
            @forelse($cart as $cartId => $item)
            {
                name: "{!! addslashes($item['name']) !!}",
                qty: {{ $item['qty'] }},
                total: {{ ($item['price'] + $item['addon_total']) * $item['qty'] }}
            }{{ $loop->last ? '' : ',' }}
            @empty
            @endforelse
        ];

        if(itemsArr.length === 0) {
            Swal.fire('Empty Cart', 'Please add some items to the cart first.', 'warning');
            return;
        }

        let subtotal = parseFloat("{{ $subtotal ?? 0 }}");
        let defaultLabel = currentOrder.order_type === 'delivery' ? 'Delivery' : 'Takeaway';

        window.openPaymentModal({
            order_id: "",
            order_type: currentOrder.order_type,
            table_no: defaultLabel,
            subtotal: subtotal,
            items: itemsArr
        });
    });
</script>
