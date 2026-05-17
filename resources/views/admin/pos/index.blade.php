@extends('admin.pos.master')
@section('title', 'POS System — Progga RMS')

@section('css')
<style>
    /* অপ্রয়োজনীয় Choices.js CSS সরিয়ে ফেলুন যদি না লাগে */
    .modal-backdrop { display: none !important; }
    body.modal-open { overflow: auto !important; padding-right: 0 !important; }

    /* SweetAlert বাটন যেন উপরে থাকে তা নিশ্চিত করুন */
   /* SweetAlert বাটন যেন উপরে থাকে তা নিশ্চিত করুন */
    .swal2-container {
        z-index: 99999 !important;
    }

    /* Order Type Active Background Color Fix */
    .pos-type-wrap:has(#posTypeDineIn:checked) label[for="posTypeDineIn"],
    .pos-type-wrap:has(#posTypeTakeaway:checked) label[for="posTypeTakeaway"],
    .pos-type-wrap:has(#posTypeDelivery:checked) label[for="posTypeDelivery"] {
      background: var(--progga-primary) !important;
      color: var(--progga-secondary) !important;
    }
</style>
@endsection

@section('body')
<div class="progga-pos-wrapper">
    <div class="progga-pos-header">
      <div class="progga-pos-logo">PROGGA RMS</div>
      <div class="progga-pos-step-indicator">
        <div class="progga-pos-step active" id="indStep1"><span class="progga-pos-step-num">1</span> Table</div>
        <span class="progga-pos-step-divider">›</span>
        <div class="progga-pos-step" id="indStep2"><span class="progga-pos-step-num">2</span> Order</div>
      </div>
      <a href="{{ route('home') }}" class="progga-pos-close"><i class="bi bi-x-lg"></i></a>
    </div>

    <div class="progga-pos-body">
        @include('admin.pos.step1_tables')
        @include('admin.pos.step2_order')
    </div>
</div>

@include('admin.pos.modals.new_order')
@include('admin.pos.modals.addon')
@include('admin.pos.modals.payment')
@include('admin.pos.partials.offcanvas_wrapper')

@endsection

@section('script')

<script>
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });



    let currentOrder = {
        order_type: 'dine_in', table_id: null, table_name: '',
        waiter_id: null, waiter_name: '', customer_id: null, customer_name: '',
        customer_phone: '', is_walk_in: 1, order_notes: ''
    };
    let currentCat = '';

    $(document).ready(function() { loadFoods(''); });

    // Table অনুযায়ী কার্ট আইডেন্টিফাই করার জন্য
    function getCartParams() {
        return {
            order_type: currentOrder.order_type,
            table_id: currentOrder.table_id
        };
    }

    // ==========================================
    // STEP NAVIGATION
    // ==========================================
    function showStep(step) {
        $('.progga-pos-screen').removeClass('active');
        $('#posStep' + step).addClass('active');
        $('.progga-pos-step').removeClass('active');
        $('#indStep' + step).addClass('active');

        if(step === 2) {
            // Header-এ Delivery বা Takeaway দেখানোর লজিক
            let tableMetaHtml = currentOrder.table_name;
            if(currentOrder.order_type === 'takeaway') tableMetaHtml = '<span class="text-danger">Takeaway</span>';
            if(currentOrder.order_type === 'delivery') tableMetaHtml = '<span class="text-warning">Delivery</span>';
            $('#posSelectedTableMeta').html(tableMetaHtml);

            let typeText = 'Dine-In';
            if(currentOrder.order_type === 'takeaway') typeText = 'Takeaway';
            if(currentOrder.order_type === 'delivery') typeText = 'Delivery';
            $('#metaType').text(typeText);

            let customerText = currentOrder.is_walk_in === 1 ? 'Walk-in Customer' : (currentOrder.customer_name ? currentOrder.customer_name : 'Registered Customer');
            $('#metaCustomer').text(customerText);

            let waiterText = currentOrder.waiter_id ? currentOrder.waiter_name : 'Unassigned';
            $('#metaWaiter').html('<i class="bi bi-person-badge"></i> ' + waiterText);

            loadFoods('');
            loadCart();
        }
    }

    $('#posBackToTables').click(function() { showStep(1); });

    // ==========================================
    // TABLE SELECTION
    // ==========================================
  $(document).on('click', '.progga-pos-table-card', function() {
        const tId = $(this).data('table-id');
        const tNum = $(this).data('table-num');

        if($(this).hasClass('occupied')) {
            $.get("{{ route('pos.get_table_order', ':id') }}".replace(':id', tId), function(res) {
                $('#ocBody').html(res);
                bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('tableOrderOffcanvas')).show();
            });
        } else {
            currentOrder.table_id = tId;
            currentOrder.table_name = tNum;
            currentOrder.order_type = 'dine_in';

            // Dine-In ইনপুট এবং লেবেল দুটোই আবার শো করানো হলো
            $('#posTypeDineIn, #labelDineIn').show();

            $('#posTypeDineIn').prop('checked', true);
            $('#modalTableDisplaySection').slideDown();
            $('#modalSelectedTableNum').text(tNum);
            bootstrap.Modal.getOrCreateInstance(document.getElementById('newOrderModal')).show();
        }
    });

   $('#modeTakeaway').click(function() {
        currentOrder.table_id = null;
        currentOrder.table_name = 'Takeaway';
        currentOrder.order_type = 'takeaway';

        // Dine-In ইনপুট এবং লেবেল দুটোই হাইড করা হলো
        $('#posTypeDineIn, #labelDineIn').hide();

        $('#posTypeTakeaway').prop('checked', true);
        $('#modalTableDisplaySection').slideUp();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('newOrderModal')).show();
    });

   $('input[name="orderType"]').on('change', function() {
        let type = $(this).val();

        // এখানে takeaway এর সাথে delivery ও যোগ করা হয়েছে
        if(type === 'takeaway' || type === 'delivery') {
            $('#modalTableDisplaySection').slideUp();
            currentOrder.order_type = type;
            currentOrder.table_id = null;
            currentOrder.table_name = type === 'takeaway' ? 'Takeaway' : 'Delivery';
        } else {
            // Dine-in এর লজিক
            if(currentOrder.table_id != null) {
                $('#modalSelectedTableNum').text(currentOrder.table_name);
                $('#modalTableDisplaySection').slideDown();
                currentOrder.order_type = 'dine_in';
            } else {
                Swal.fire('Notice', 'Please go back and select a table first.', 'info');
                $(this).prop('checked', false);
                // যদি টেবিল না থাকে, তবে জোর করে Takeaway সিলেক্ট করে দিবে
                $('#posTypeTakeaway').prop('checked', true);
                currentOrder.order_type = 'takeaway';
            }
        }
    });

    // ==========================================
    // START ORDER BUTTON
    // ==========================================
    $('#posWalkIn').on('change', function() {
        if($(this).is(':checked')) { $('#posCustomerFields').slideUp(); }
        else { $('#posCustomerFields').slideDown(); }
    });

    $('#showNewCustomerFormBtn').on('click', function() {
        let isNewFormVisible = $('#newCustomerForm').is(':hidden');
        if(isNewFormVisible) {
            $('#newCustomerForm').slideDown();
            $('#customerSearchContainer').hide();
            $(this).text('- Cancel New Customer').removeClass('progga-btn progga-btn-primary').addClass('progga-btn progga-btn-danger');
        } else {
            $('#newCustomerForm').slideUp();
            $('#customerSearchContainer').show();
            $(this).text('+ Add New Customer').removeClass('progga-btn progga-btn-danger').addClass('progga-btn progga-btn-primary');
            $('#new_cus_name').val('');
            $('#new_cus_phone').val('');
        }
    });

    $('#posStartOrderBtn').click(function() {
        currentOrder.waiter_id = $('#posWaiterSelect').val();
        currentOrder.waiter_name = currentOrder.waiter_id ? $('#posWaiterSelect option:selected').text() : 'Unassigned';
        currentOrder.is_walk_in = $('#posWalkIn').is(':checked') ? 1 : 0;
        currentOrder.order_notes = $('#order_notes').val() || '';

        if(currentOrder.is_walk_in === 0) {
            currentOrder.customer_id = $('#posCustomerSelect').val();
            let newName = $('#new_cus_name').val();
            if (newName) {
                currentOrder.customer_name = newName;
            } else if (currentOrder.customer_id) {
                currentOrder.customer_name = $('#posCustomerSelect option:selected').text().split(' - ')[0];
            } else {
                currentOrder.customer_name = 'Registered Customer';
            }
            currentOrder.customer_phone = $('#new_cus_phone').val();
        } else {
            currentOrder.customer_name = 'Walk-in Customer';
            currentOrder.customer_id = null;
        }

        bootstrap.Modal.getInstance(document.getElementById('newOrderModal')).hide();
        showStep(2);
    });

    // ==========================================
    // FOODS & CATEGORY
    // ==========================================
    function loadFoods(catId = '', search = '', page = 1) {
        currentCat = catId;
        $.get("{{ route('pos.get_foods') }}?page=" + page, { category_id: catId, search: search }, function(res) {
            $('#posFoodGrid').html(res);
        });
    }

    $(document).on('click', '.progga-pos-cat-item', function() {
        $('.progga-pos-cat-item').removeClass('active');
        $(this).addClass('active');
        loadFoods($(this).data('cat-id'), $('#posFoodSearch').val());
    });
    $('#posFoodSearch').on('keyup', function() { loadFoods(currentCat, $(this).val()); });

    $(document).on('click', '.pagination a', function(e) {
        e.preventDefault();
        let page = $(this).attr('href').split('page=')[1];
        loadFoods(currentCat, $('#posFoodSearch').val(), page);
    });

    // ==========================================
    // ADDONS & CART ACTIONS (Table Specific!)
    // ==========================================
    window.checkAddonAndCart = function(foodId, hasAddon) {
        if(hasAddon == 1) {
            $.get("{{ route('pos.get_addons', ':id') }}".replace(':id', foodId), function(res) {
                $('#addonModalFoodName').text(res.food.name);
                $('#addonModalFoodId').val(foodId);
                let addonHtml = '';
                res.food.addons.forEach(addon => {
                    addonHtml += `<label class="d-block border p-2 mb-2"><input type="checkbox" name="addons[]" value="${addon.id}"> ${addon.name} (+৳${addon.price})</label>`;
                });
                $('#addonListDiv').html(addonHtml);
                bootstrap.Modal.getOrCreateInstance(document.getElementById('addonModal')).show();
            });
        } else {
            addToCart(foodId, []);
        }
    }

    $('#addonForm').on('submit', function(e) {
        e.preventDefault();
        let foodId = $('#addonModalFoodId').val();
        let addons = [];
        $('input[name="addons[]"]:checked').each(function() { addons.push($(this).val()); });
        addToCart(foodId, addons);
        bootstrap.Modal.getInstance(document.getElementById('addonModal')).hide();
    });

    function addToCart(foodId, addons) {
        let payload = getCartParams();
        payload.food_id = foodId;
        payload.addons = addons;
        payload.qty = 1;
        $.post("{{ route('pos.cart.add') }}", payload, function(res) {
            if(res.status === 'success') loadCart();
        });
    }

    function loadCart() {
        $.get("{{ route('pos.cart.get') }}", getCartParams(), function(res) {
            $('#posCartBody').html(res);
        });
    }

    window.removeCartItem = function(cartId) {
        let payload = getCartParams();
        payload.cart_id = cartId;
        $.post("{{ route('pos.cart.remove') }}", payload, function() { loadCart(); });
    }

    window.updateQty = function(cartId, action) {
        let payload = getCartParams();
        payload.cart_id = cartId;
        payload.action = action;
        $.post("{{ route('pos.cart.update') }}", payload, function(res) {
            if(res.status === 'success') loadCart();
        });
    }

    window.updateItemNote = function(cartId, note) {
        let payload = getCartParams();
        payload.cart_id = cartId;
        payload.note = note;
        $.post("{{ route('pos.cart.update_note') }}", payload, function(res) {
            showToast('Info', 'Note updated', 'info');
        });
    }
$(document).on('change', 'input[name="payment_method"]', function() {
    if($(this).val() === 'Card' || $(this).val() === 'Mobile Banking') {
        $('#transactionDiv').slideDown('fast');
    } else {
        $('#transactionDiv').slideUp('fast');
    }
});
    // ==========================================
    // SEND TO KITCHEN (100% Fixed)
    // ==========================================
$(document).on('click', '#btnSendToKitchen', function(e) {
    e.preventDefault();

    // ভ্যালিডেশন
    if (currentOrder.order_type === 'dine_in' && !currentOrder.table_id) {
        window.proggaToast('Please select a table first!', 'danger');
        return;
    }

    // সরাসরি window.Swal ব্যবহার করুন কনফ্লিক্ট এড়াতে
    window.Swal.fire({
        title: 'Send to Kitchen?',
        text: "Are you sure you want to place this order?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#21352a',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, Send!',
        allowOutsideClick: false // যেন ক্লিক মিস না হয়
    }).then(function(result) {
        if (result.isConfirmed) {
            // ডাটা কালেকশন
            var discType = $('#cart_discount_type').val() || 'fixed';
            var discVal = parseFloat($('#cart_discount_value').val()) || 0;

            var payload = {
    order_id: currentOrder.order_id || null, // <--- এই লাইনটি অবশ্যই যোগ করতে হবে
    order_type: currentOrder.order_type,
    table_id: currentOrder.table_id,
    waiter_id: currentOrder.waiter_id,
    is_walk_in: currentOrder.is_walk_in,
    customer_id: currentOrder.customer_id,
    customer_name: currentOrder.customer_name,
    customer_phone: currentOrder.customer_phone,
    order_notes: currentOrder.order_notes,
    discount_type: discType,
    discount_value: discVal,
    preparation_time: $('#cart_prep_time').val() || 20,
    _token: $('meta[name="csrf-token"]').attr('content')
};

            var btn = $('#btnSendToKitchen');
            var originalHtml = btn.html();

            $.ajax({
                url: "{{ route('pos.place_order') }}",
                type: "POST",
                data: payload,
                beforeSend: function() {
                    btn.html('<span class="spinner-border spinner-border-sm"></span>').prop('disabled', true);
                },
                success: function(res) {
                    if(res.status === 'success') {
                        window.Swal.fire('Success!', res.message, 'success').then(function() {
                            window.location.reload();
                        });
                    } else {
                        window.Swal.fire('Error', res.message, 'error');
                        btn.html(originalHtml).prop('disabled', false);
                    }
                },
                error: function(xhr) {
                    console.error("Error Response:", xhr.responseText);
                    window.Swal.fire('Error', 'Server failed to process order.', 'error');
                    btn.html(originalHtml).prop('disabled', false);
                }
            });
        }
    });
});
    // ==========================================
    // PAYMENT
    // ==========================================
    // $(document).on('click', '#btnDirectPay', function() {
    //     let total = $('#display_grand_total').text().replace('৳ ', '');
    //     openPaymentModal(null, total);
    // });

   window.openPaymentModal = function(data) {
    let oc = document.getElementById('tableOrderOffcanvas');
    if(oc) bootstrap.Offcanvas.getInstance(oc)?.hide();

    $('#payOrderId').val(data.order_id || '');
    $('#payTableLabel').text(data.table_no || 'Takeaway');

    // ডাটাবেজ থেকে আসা সাবটোটাল স্টোর করা
    $('#paymentModal').data('subtotal', parseFloat(data.subtotal || 0));

    $('#paySubtotal').text('৳' + parseFloat(data.subtotal || 0).toFixed(2));

    $('#modal_discount_type').val('fixed');
    $('#modal_discount_value').val('');

    // আইটেম লিস্ট মোডালে লোড করা
    let itemsHtml = '';
    if(data.items && data.items.length > 0) {
        data.items.forEach(item => {
            itemsHtml += `
            <div class="progga-pay-summary-item" style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 8px;">
                <span class="text-muted">${item.name} ×${item.qty}</span>
                <span style="font-weight: 600;">৳${parseFloat(item.total).toFixed(2)}</span>
            </div>`;
        });
    } else {
        itemsHtml = '<div class="text-muted text-center" style="font-size:12px;">No items</div>';
    }
    $('#payModalItemsArea').html(itemsHtml);

    calculateModalTotal();
    bootstrap.Modal.getOrCreateInstance(document.getElementById('paymentModal')).show();
}

// master.blade.php এর ভেতরে
window.calculateModalTotal = function() {
    let subtotal = parseFloat($('#paymentModal').data('subtotal')) || 0;
    let vat_rate = parseFloat("{{ $taxSettingVatRate ?? 0 }}");
    let service_rate = parseFloat("{{ $taxSettingServiceCharge ?? 0 }}");

    let disc_type = $('#modal_discount_type').val();
    let disc_val = parseFloat($('#modal_discount_value').val()) || 0;

    let discount_amount = (disc_type === 'percentage') ? (subtotal * disc_val / 100) : disc_val;

    // ভ্যাট ও সার্ভিস চার্জ সরাসরি সাবটোটালের ওপর
    let vat = (subtotal * vat_rate) / 100;
    let service = (subtotal * service_rate) / 100;

    // গ্র্যান্ড টোটাল হিসাব
    let grand = (subtotal + vat + service) - discount_amount;

    $('#payDiscount').text('−৳' + discount_amount.toFixed(2));
    $('#payVat').text('৳' + vat.toFixed(2));
    $('#payService').text('৳' + service.toFixed(2));
    $('#payTotalAmount').text('৳' + grand.toFixed(2));
}

   // পেমেন্ট ফর্ম সাবমিট লজিক
$(document).on('submit', '#payForm', function(e) {
    e.preventDefault();

    let btn = $(this).find('button[type="submit"]');
    let originalHtml = btn.html();
    btn.html('<i class="spinner-border spinner-border-sm"></i> Processing...').prop('disabled', true);

    // ফর্মের ডাটা (পেমেন্ট মেথড, ট্রানজেকশন আইডি এবং ডিসকাউন্ট ডাটা)
    let formData = $(this).serialize();

    $.ajax({
        url: "{{ route('pos.complete_payment') }}",
        type: "POST",
        data: formData + '&_token=' + $('meta[name="csrf-token"]').attr('content'),
        success: function(res) {
            if(res.status === 'success') {
                Swal.fire({ icon: 'success', title: 'Paid!', timer: 1500, showConfirmButton: false }).then(() => {
                    window.location.href = res.redirect_url;
                });
            } else {
                Swal.fire('Error', res.message, 'error');
                btn.html(originalHtml).prop('disabled', false);
            }
        },
        error: function() {
            Swal.fire('Error', 'Server error. Please try again.', 'error');
            btn.html(originalHtml).prop('disabled', false);
        }
    });
});

 $(document).on('click', '#btnContinueOrdering', function() {
    // ১. বাটন থেকে ডাটা সংগ্রহ
    const tId = $(this).data('table-id');
    const orderId = $(this).data('order-id');
    const waiterId = $(this).data('waiter-id');
    const waiterName = $(this).data('waiter-name');
    const customerId = $(this).data('customer-id');
    const customerName = $(this).data('customer-name');

    const tNum = $('#ocTableNum').text(); // অফক্যানভাস থেকে টেবিলের নাম

    // ২. বর্তমান অর্ডারের স্টেট (currentOrder) আপডেট
    currentOrder.table_id = tId;
    currentOrder.table_name = tNum;
    currentOrder.order_id = orderId;
    currentOrder.order_type = 'dine_in';

    // ওয়েটার আপডেট
    currentOrder.waiter_id = waiterId ? waiterId : null;
    currentOrder.waiter_name = waiterName ? waiterName : '';

    // কাস্টমার আপডেট (Walk-in চেক)
    if(customerId) {
        currentOrder.is_walk_in = 0;
        currentOrder.customer_id = customerId;
        currentOrder.customer_name = customerName;
    } else {
        currentOrder.is_walk_in = 1;
        currentOrder.customer_id = null;
        currentOrder.customer_name = '';
    }

    // ৩. অফক্যানভাস বন্ধ করা
    var ocElement = document.getElementById('tableOrderOffcanvas');
    if (ocElement) {
        var ocInstance = bootstrap.Offcanvas.getInstance(ocElement);
        if (ocInstance) ocInstance.hide();
    }

    // ৪. স্টেপ ২ তে নিয়ে যাওয়া এবং কার্ট লোড করা
    showStep(2);
    loadCart();
});

// ==========================================
    // TABLE FILTERING TABS
    // ==========================================
    $(document).on('click', '.progga-pos-filter-btn', function() {
        // সব বাটন থেকে active ক্লাস সরিয়ে ক্লিক করা বাটনে অ্যাড করা
        $('.progga-pos-filter-btn').removeClass('active');
        $(this).addClass('active');

        // ফিল্টারের ভ্যালু নেওয়া (all, available, occupied, reserved)
        let filterValue = $(this).data('table-filter');

        // টেবিল ফিল্টার লজিক
        if(filterValue === 'all') {
            $('.progga-pos-table-card').fadeIn('fast');
        } else {
            $('.progga-pos-table-card').hide();
            $('.progga-pos-table-card[data-status="' + filterValue + '"]').fadeIn('fast');
        }
    });

    // ==========================================
    // MOBILE CART TOGGLE LOGIC
    // ==========================================
    $(document).on('click', '#posCartFab', function() {
        // progga-style.css এর ক্লাস অনুযায়ী কার্ট ওপেন হবে
        $('.progga-pos-cart').addClass('show');
        $('#posMobileBackdrop').fadeIn('fast');
        $('body').addClass('progga-pos-overflow-lock'); // ব্যাকগ্রাউন্ড স্ক্রল অফ
    });

    $(document).on('click', '#posMobileCartClose, #posMobileBackdrop', function() {
        // ক্লোজ বাটনে বা ব্যাকড্রপে ক্লিক করলে কার্ট হাইড হবে
        $('.progga-pos-cart').removeClass('show');
        $('#posMobileBackdrop').fadeOut('fast');
        $('body').removeClass('progga-pos-overflow-lock'); // ব্যাকগ্রাউন্ড স্ক্রল অন
    });
</script>

@endsection
