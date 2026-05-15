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

    <div class="progga-pos-total-row">
        <span class="progga-pos-discount-label" style="display: flex; align-items: center; gap: 5px;">
            Discount
            <select class="progga-form-control" id="cart_discount_type" onchange="calculateGrandTotal()" style="width: 50px; padding: 2px 4px; height: 28px; font-size: 12px;">
                <option value="fixed">৳</option>
                <option value="percentage">%</option>
            </select>
            <input type="number" class="progga-form-control progga-pos-discount-input" id="cart_discount_value" placeholder="0" min="0" onkeyup="calculateGrandTotal()">
        </span>
        <span id="display_discount_amount">−৳0.00</span>
    </div>

    <div class="progga-pos-total-row">
        <span>Tax ({{ $vat_rate }}%)</span>
        <span id="display_vat">৳{{ number_format($vat_amount, 2) }}</span>
    </div>

    @if($service_charge_rate > 0)
    <div class="progga-pos-total-row">
        <span>Service Charge ({{ $service_charge_rate }}%)</span>
        <span id="display_service">৳{{ number_format($service_amount, 2) }}</span>
    </div>
    @endif

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
    // Toggle Takeaway Buttons
    if(currentOrder.order_type === 'takeaway') {
        $('#takeawayActions').css('display', 'grid'); // ডিজাইনের grid সিস্টেম
        $('#btnSendToKitchen').hide();
    } else {
        $('#takeawayActions').hide();
        $('#btnSendToKitchen').show();
    }

    // Grand Total Logic
    function calculateGrandTotal() {
        let subtotal = parseFloat("{{ $subtotal }}") || 0;
        let vat_rate = parseFloat("{{ $vat_rate }}") || 0;
        let service_rate = parseFloat("{{ $service_charge_rate }}") || 0;

        let disc_type = $('#cart_discount_type').val();
        let disc_val = parseFloat($('#cart_discount_value').val()) || 0;

        let discount_amount = (disc_type === 'percentage') ? (subtotal * disc_val / 100) : disc_val;
        let discounted_subtotal = subtotal - discount_amount;

        let vat = (discounted_subtotal * vat_rate) / 100;
        let service = (discounted_subtotal * service_rate) / 100;
        let grand = discounted_subtotal + vat + service;

        // UI Update
        $('#display_discount_amount').text('−৳' + discount_amount.toFixed(2));
        $('#display_grand_total').text('৳' + grand.toFixed(2));
        $('#display_vat').text('৳' + vat.toFixed(2));

        if(document.getElementById('display_service')) {
            $('#display_service').text('৳' + service.toFixed(2));
        }

        // Global Variable Update for Place Order
        currentOrder.discount_type = disc_type;
        currentOrder.discount_value = disc_val;
        currentOrder.grand_total = grand;
    }

    // Direct Pay Button Logic (For Takeaway)
    $('#btnDirectPay').on('click', function() {

        // ১. কার্ট থেকে সমস্ত আইটেম একটি জাভাস্ক্রিপ্ট অ্যারেতে নেওয়া
        let itemsArr = [
            @forelse($cart as $cartId => $item)
            {
                name: "{!! addslashes($item['name']) !!}",
                qty: {{ $item['qty'] }},
                total: {{ ($item['price'] + $item['addon_total']) * $item['qty'] }}
            }{{ $loop->last ? '' : ',' }}
            @empty
            // কার্ট ফাঁকা থাকলে কিছু হবে না
            @endforelse
        ];

        // ২. যদি কার্টে কোনো আইটেম না থাকে তবে পেমেন্ট ওপেন হবে না
        if(itemsArr.length === 0) {
            Swal.fire('Empty Cart', 'Please add some items to the cart first.', 'warning');
            return;
        }

        // ৩. পিএইচপি ভেরিয়েবল থেকে বেসিক হিসাবগুলো নেওয়া
        let subtotal = parseFloat("{{ $subtotal ?? 0 }}");
        let vatAmount = parseFloat("{{ $vat_amount ?? 0 }}");
        let serviceAmount = parseFloat("{{ $service_amount ?? 0 }}");
        let initialGrandTotal = parseFloat("{{ $grand_total ?? 0 }}");

        // ৪. জাভাস্ক্রিপ্টের currentOrder অবজেক্ট থেকে লেটেস্ট ডিসকাউন্ট ও গ্র্যান্ড টোটাল নেওয়া
        // (কারণ ইউজার ডিসকাউন্ট টাইপ/ভ্যালু পরিবর্তন করে থাকতে পারে যা এখনো ডাটাবেজে যায়নি)
        let finalDiscount = currentOrder.discount_value || 0;
        let finalGrandTotal = currentOrder.grand_total || initialGrandTotal;
        let totalTaxAndService = vatAmount + serviceAmount;

        // ৫. নতুন পেমেন্ট মোডাল ফাংশন কল করা
        window.openPaymentModal({
            order_id: "", // টেক-অ্যাওয়ে এখনো প্লেস হয়নি, তাই ID ফাঁকা থাকবে
            table_no: "Takeaway",
            subtotal: subtotal,
            discount: finalDiscount,
            tax: totalTaxAndService,
            grand_total: finalGrandTotal,
            items: itemsArr
        });
    });
</script>
