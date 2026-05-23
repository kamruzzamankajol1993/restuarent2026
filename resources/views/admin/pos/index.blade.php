@extends('admin.pos.master')
@section('title', 'POS System — Progga RMS')

@section('css')
<style>
    .modal-backdrop { display: none !important; }
    body.modal-open { overflow: auto !important; padding-right: 0 !important; }

    .swal2-container {
        z-index: 99999 !important;
    }

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
  <div class="progga-pos-logo">
    @if(!empty($restaurantSettingIconName))
        <img src="{{ asset('public/'.$restaurantSettingIconName) }}" alt="Icon" style="width: 60px; height: 60px; object-fit: contain;">
    @else
        {{ strtoupper(substr($restaurantSettingName ?? 'P', 0, 1)) }}
    @endif
    <span>GOLPO KHANA</span>
  </div>

  <div class="progga-pos-step-indicator">
    <div class="progga-pos-step active" id="indStep1"><span class="progga-pos-step-num">1</span> Table</div>
    <span class="progga-pos-step-divider">›</span>
    <div class="progga-pos-step" id="indStep2"><span class="progga-pos-step-num">2</span> Order</div>
  </div>

  <div class="d-flex align-items-center" style="gap: 15px;">
    @if($activeSession)
    <button type="button" class="progga-btn progga-btn-danger progga-btn-sm" onclick="confirmEndSession({{ $activeSession->id }})" title="End Shift & Print Report">
        <i class="bi bi-stop-circle"></i> End Shift
    </button>
@endif

    <a href="{{ url('/clear') }}" class="progga-btn progga-btn-secondary progga-btn-sm text-decoration-none" title="Clear System Cache">
        <i class="bi bi-arrow-clockwise"></i> Clear Cache
    </a>
    <button type="button" class="progga-btn progga-btn-secondary progga-btn-sm text-decoration-none" data-bs-toggle="modal" data-bs-target="#sessionHistoryModal">
    <i class="bi bi-history"></i> Session History
</button>
    <a href="{{ route('home') }}" class="progga-pos-close m-0"><i class="bi bi-x-lg"></i></a>
  </div>
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
<div class="modal fade progga-modal" id="startSessionModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-play-circle-fill me-2"></i>Start Work Period</h5>
            </div>
            <div class="modal-body text-center p-4">
                <p class="text-muted mb-4">You have no active session. Start a new work period to process orders.</p>
                <button type="button" class="btn btn-success w-100 fw-bold py-2" id="btnStartSession">
                    <i class="bi bi-check-circle"></i> Start Shift Now
                </button>
            </div>
        </div>
    </div>
</div>

@if(isset($requirePreviousSessionClose) && $requirePreviousSessionClose)
<div class="modal fade progga-modal" id="previousSessionModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; border: 2px solid #dc3545;">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i>Pending Session Alert</h5>
            </div>
            <div class="modal-body text-center p-4">
                <h5 class="text-danger fw-bold">You have an unfinished session from yesterday!</h5>
                <p class="text-muted mt-2 mb-4">Started on: <strong>{{ $activeSession->start_time->format('l, M d, Y h:i A') }}</strong><br>Please end the previous session to start working today.</p>
                <button type="button" class="btn btn-danger w-100 fw-bold py-2" onclick="processEndSession({{ $activeSession->id }})">
                    <i class="bi bi-stop-circle-fill"></i> End Previous Session & Print Report
                </button>
            </div>
        </div>
    </div>
</div>
@endif

<div class="modal fade progga-modal" id="sessionHistoryModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px;">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-table me-2"></i>Work Period Sessions</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3" style="max-height: 500px; overflow-y: auto;">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered align-middle text-center" style="font-size: 13px;">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Employee</th>
                                <th>Day</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Duration</th>
                                <th>Grand Total</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sessions as $key => $sess)
                                <tr>
                                    <td><strong>#{{ $key+1 }}</strong></td>
                                    <td>{{ $sess->user->name ?? 'N/A' }}</td>
                                    <td><span class="badge bg-secondary">{{ $sess->weekday }}</span></td>
                                    <td>{{ $sess->start_time->format('d M y - h:i A') }}</td>
                                    <td>{{ $sess->end_time ? $sess->end_time->format('d M y - h:i A') : '—' }}</td>
                                    <td>{{ $sess->duration ?? 'Running' }}</td>
                                    <td><strong>৳{{ round($sess->grand_total) }}</strong></td>
                                    <td>
                                        <span class="badge {{ $sess->status == 'Open' ? 'bg-success' : 'bg-danger' }}">
                                            {{ $sess->status }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1 justify-content-center">
                                           <button type="button"
        class="btn btn-sm btn-primary btnEditSession"
        data-id="{{ $sess->id }}"
        data-start="{{ $sess->start_time ? $sess->start_time->format('Y-m-d\TH:i') : '' }}"
        data-end="{{ $sess->end_time ? $sess->end_time->format('Y-m-d\TH:i') : '' }}"
        data-status="{{ $sess->status }}">
    <i class="bi bi-pencil-square"></i> Edit
</button>
                                            @if($sess->status == 'Closed')
                                                <a href="{{ route('pos.session.report', $sess->id) }}" target="_blank" class="btn btn-sm btn-warning fw-bold">
                                                    <i class="bi bi-printer"></i> Print
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-muted py-4">No sessions found!</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade progga-modal" id="editSessionModal" tabindex="-1" style="z-index: 1056;">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Session #<span id="editSessIdLabel"></span></h5>
                <button type="button" class="btn-close btn-close-white" onclick="$('#editSessionModal').modal('hide')"></button>
            </div>
            <form id="editSessionForm">
                <div class="modal-body p-3">
                    <input type="hidden" id="editSessionId" name="session_id">

                    <div class="mb-3">
                        <label class="form-label fw-bold" style="font-size: 12px;">Start Time</label>
                        <input type="datetime-local" id="editStartTime" name="start_time" class="form-control" required style="font-size: 13px;">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold" style="font-size: 12px;">End Time</label>
                        <input type="datetime-local" id="editEndTime" name="end_time" class="form-control" style="font-size: 13px;">
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-bold" style="font-size: 12px;">Status</label>
                        <select id="editStatus" name="status" class="form-control" style="font-size: 13px;">
                            <option value="Open">Open</option>
                            <option value="Closed">Closed</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer p-2">
                    <button type="submit" class="btn btn-primary w-100 fw-bold">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
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

    function getCartParams() {
        return {
            order_type: currentOrder.order_type,
            table_id: currentOrder.table_id
        };
    }

    function showStep(step) {
        $('.progga-pos-screen').removeClass('active');
        $('#posStep' + step).addClass('active');
        $('.progga-pos-step').removeClass('active');
        $('#indStep' + step).addClass('active');

        if(step === 2) {
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
            currentOrder.order_id = null;

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
        currentOrder.order_id = null;

        $('#posTypeDineIn, #labelDineIn').hide();
        $('#posTypeTakeaway').prop('checked', true);
        $('#modalTableDisplaySection').slideUp();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('newOrderModal')).show();
    });

   $('input[name="orderType"]').on('change', function() {
        let type = $(this).val();

        if(type === 'takeaway' || type === 'delivery') {
            $('#modalTableDisplaySection').slideUp();
            currentOrder.order_type = type;
            currentOrder.table_id = null;
            currentOrder.table_name = type === 'takeaway' ? 'Takeaway' : 'Delivery';
            currentOrder.order_id = null;
        } else {
            if(currentOrder.table_id != null) {
                $('#modalSelectedTableNum').text(currentOrder.table_name);
                $('#modalTableDisplaySection').slideDown();
                currentOrder.order_type = 'dine_in';
            } else {
                Swal.fire('Notice', 'Please go back and select a table first.', 'info');
                $(this).prop('checked', false);
                $('#posTypeTakeaway').prop('checked', true);
                currentOrder.order_type = 'takeaway';
            }
        }
    });

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

    function loadFoods(catId = '', search = '') {
        currentCat = catId;
        $.get("{{ route('pos.get_foods') }}", { category_id: catId, search: search }, function(res) {
            $('#posFoodGrid').html(res);
        });
    }

    $(document).on('click', '.progga-pos-cat-item', function() {
        $('.progga-pos-cat-item').removeClass('active');
        $(this).addClass('active');
        loadFoods($(this).data('cat-id'), $('#posFoodSearch').val());
    });
    $('#posFoodSearch').on('keyup', function() { loadFoods(currentCat, $(this).val()); });

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

    window.loadHeldQrOrderToPos = function(data) {
        currentOrder.order_id = data.order_id || null;
        currentOrder.order_type = 'dine_in';
        currentOrder.table_id = data.table_id || null;
        currentOrder.table_name = data.table_number || ('Table ' + (data.table_id || ''));
        currentOrder.waiter_id = data.waiter_id || null;
        currentOrder.waiter_name = data.waiter_name || 'Unassigned';
        currentOrder.customer_id = data.customer_id || null;
        currentOrder.customer_name = data.customer_name || 'Walk-in Customer';
        currentOrder.customer_phone = data.customer_phone || '';
        currentOrder.is_walk_in = parseInt(typeof data.is_walk_in !== 'undefined' ? data.is_walk_in : (data.customer_id ? 0 : 1));
        currentOrder.order_notes = data.notes || '';

        let tableCard = $('.progga-pos-table-card[data-table-id="' + currentOrder.table_id + '"]');
        if(tableCard.length) {
            tableCard.removeClass('available reserved').addClass('occupied');
            tableCard.attr('data-status', 'occupied');
            tableCard.find('.progga-badge')
                .removeClass('progga-status-available progga-status-reserved')
                .addClass('progga-status-occupied')
                .text('Occupied');
        }

        showStep(2);
        loadCart();
    };

    // If Hold for Waiter is clicked from the main admin master page, POS is opened
    // with held order data stored in sessionStorage. Load it directly into cart screen.
    $(document).ready(function() {
        const params = new URLSearchParams(window.location.search);
        const shouldOpenHeldQr = params.get('open_held_qr') === '1';
        const storedHeldQr = sessionStorage.getItem('openHeldQrOrderInPos');

        if (shouldOpenHeldQr && storedHeldQr) {
            try {
                const heldOrderData = JSON.parse(storedHeldQr);
                sessionStorage.removeItem('openHeldQrOrderInPos');

                if (typeof window.loadHeldQrOrderToPos === 'function') {
                    setTimeout(function() {
                        window.loadHeldQrOrderToPos(heldOrderData);
                    }, 300);
                }
            } catch (e) {
                console.error('Failed to open held QR order in POS cart:', e);
                sessionStorage.removeItem('openHeldQrOrderInPos');
            }
        }
    });


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

$(document).on('click', '#btnSendToKitchen', function(e) {
    e.preventDefault();

    if (currentOrder.order_type === 'dine_in' && !currentOrder.table_id) {
        window.proggaToast('Please select a table first!', 'danger');
        return;
    }

    window.Swal.fire({
        title: 'Send to Kitchen?',
        text: "Are you sure you want to place this order?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#21352a',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, Send!',
        allowOutsideClick: false
    }).then(function(result) {
        if (result.isConfirmed) {
            var discType = $('#cart_discount_type').val() || 'fixed';
            var discVal = parseFloat($('#cart_discount_value').val()) || 0;

            var payload = {
                order_id: currentOrder.order_id || null,
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
                        window.Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: res.message,
                            confirmButtonText: 'OK',
                            allowOutsideClick: false
                        }).then(function() {
                            if (res.redirect_url) {
                                window.location.href = res.redirect_url;
                            } else {
                                window.location.href = "{{ route('pos.index') }}";
                            }
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

window.openPaymentModal = function(data) {
    let oc = document.getElementById('tableOrderOffcanvas');
    if(oc) bootstrap.Offcanvas.getInstance(oc)?.hide();

    $('#payOrderId').val(data.order_id || '');
    $('#payOrderType').val(data.order_type || 'takeaway');

    let defaultLabel = data.order_type === 'delivery' ? 'Delivery' : 'Takeaway';
    $('#payTableLabel').text(data.table_no || defaultLabel);

    $('#paymentModal').data('subtotal', parseFloat(data.subtotal || 0));
    $('#paySubtotal').text('৳' + Math.round(data.subtotal || 0));

    $('#modal_discount_type').val('fixed');
    $('#modal_discount_value').val('');

    let itemsHtml = '';
    if(data.items && data.items.length > 0) {
        data.items.forEach(item => {
            itemsHtml += `
            <div class="progga-pay-summary-item" style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 8px;">
                <span class="text-muted">${item.name} ×${item.qty}</span>
                <span style="font-weight: 600;">৳${Math.round(item.total)}</span>
            </div>`;
        });
    } else {
        itemsHtml = '<div class="text-muted text-center" style="font-size:12px;">No items</div>';
    }
    $('#payModalItemsArea').html(itemsHtml);

    calculateModalTotal();
    bootstrap.Modal.getOrCreateInstance(document.getElementById('paymentModal')).show();
}

window.calculateModalTotal = function() {
    let subtotal = parseFloat($('#paymentModal').data('subtotal')) || 0;
    let vat_rate = parseFloat("{{ $taxSettingVatRate ?? 0 }}");

    let orderType = $('#payOrderType').val();
    let service_rate = (orderType === 'dine_in' || orderType === 'Dine-In') ? parseFloat("{{ $taxSettingServiceCharge ?? 0 }}") : 0;

    let disc_type = $('#modal_discount_type').val();
    let disc_val = parseFloat($('#modal_discount_value').val()) || 0;

    let service = Math.round((subtotal * service_rate) / 100);
    let vat = Math.round(((subtotal + service) * vat_rate) / 100);
    let discount_amount = Math.round((disc_type === 'percentage') ? (subtotal * disc_val / 100) : disc_val);

    let grand = Math.round((subtotal + vat + service) - discount_amount);

    $('#payDiscount').text('−৳' + discount_amount);
    $('#payVat').text('৳' + vat);
    $('#payService').text('৳' + service);
    $('#payTotalAmount').text('৳' + grand);

    if(service === 0) {
        $('#payServiceRow').hide();
    } else {
        $('#payServiceRow').show();
    }

    if ($('input[name="payment_method"]:checked').val() !== 'Split') {
        $('#payTotalPaidAmount').val(grand);
    }

    if(typeof window.updateDueAmount === 'function') {
        window.updateDueAmount();
    }
}

$(document).on('submit', '#payForm', function(e) {
    e.preventDefault();

    let btn = $(this).find('button[type="submit"]');
    let originalHtml = btn.html();
    btn.html('<i class="spinner-border spinner-border-sm"></i> Processing...').prop('disabled', true);

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
    const tId = $(this).data('table-id');
    const orderId = $(this).data('order-id');
    const waiterId = $(this).data('waiter-id');
    const waiterName = $(this).data('waiter-name');
    const customerId = $(this).data('customer-id');
    const customerName = $(this).data('customer-name');

    const tNum = $('#ocTableNum').text();

    currentOrder.table_id = tId;
    currentOrder.table_name = tNum;
    currentOrder.order_id = orderId;
    currentOrder.order_type = 'dine_in';

    currentOrder.waiter_id = waiterId ? waiterId : null;
    currentOrder.waiter_name = waiterName ? waiterName : '';

    if(customerId) {
        currentOrder.is_walk_in = 0;
        currentOrder.customer_id = customerId;
        currentOrder.customer_name = customerName;
    } else {
        currentOrder.is_walk_in = 1;
        currentOrder.customer_id = null;
        currentOrder.customer_name = '';
    }

    var ocElement = document.getElementById('tableOrderOffcanvas');
    if (ocElement) {
        var ocInstance = bootstrap.Offcanvas.getInstance(ocElement);
        if (ocInstance) ocInstance.hide();
    }

    showStep(2);
    loadCart();
});

    $(document).on('click', '.progga-pos-filter-btn', function() {
        $('.progga-pos-filter-btn').removeClass('active');
        $(this).addClass('active');

        let filterValue = $(this).data('table-filter');

        if(filterValue === 'all') {
            $('.progga-pos-table-card').fadeIn('fast');
        } else {
            $('.progga-pos-table-card').hide();
            $('.progga-pos-table-card[data-status="' + filterValue + '"]').fadeIn('fast');
        }
    });

    $(document).on('click', '#posCartFab', function() {
        $('.progga-pos-cart').addClass('show');
        $('#posMobileBackdrop').fadeIn('fast');
        $('body').addClass('progga-pos-overflow-lock');
    });

    $(document).on('click', '#posMobileCartClose, #posMobileBackdrop', function() {
        $('.progga-pos-cart').removeClass('show');
        $('#posMobileBackdrop').fadeOut('fast');
        $('body').removeClass('progga-pos-overflow-lock');
    });

    $(document).ready(function() {
    let hasActiveSession = "{{ $activeSession ? 'yes' : 'no' }}";
    let requiresClose = "{{ $requirePreviousSessionClose ?? false }}";

    // লজিক: যদি কোনো সেশন না থাকে, তাহলে Start Session মোডাল দেখাবে
    if (hasActiveSession === 'no') {
        $('#startSessionModal').modal('show');
    }
    // লজিক: যদি আগের দিনের সেশন ওপেন থাকে, তাহলে End Session 모ডাল দেখাবে
    else if (requiresClose === '1') {
        $('#previousSessionModal').modal('show');
    }

    // Start Session Button Click
    $('#btnStartSession').click(function() {
        let btn = $(this);
        btn.prop('disabled', true).html('<i class="spinner-border spinner-border-sm"></i> Starting...');

        $.post("{{ route('pos.session.start') }}", { _token: "{{ csrf_token() }}" }, function(res) {
            if(res.status === 'success') {
                Swal.fire({ icon: 'success', title: 'Shift Started!', timer: 1500, showConfirmButton: false }).then(() => {
                    location.reload();
                });
            }
        });
    });
});

// ম্যানুয়ালি End Session বাটন ক্লিক
window.confirmEndSession = function(sessionId) {
    Swal.fire({
        title: 'End Current Shift?',
        text: "This will calculate your sales and print the closing report.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, End Shift!'
    }).then((result) => {
        if (result.isConfirmed) {
            processEndSession(sessionId);
        }
    });
}

// End Session প্রসেসিং লজিক
window.processEndSession = function(sessionId) {
    Swal.fire({ title: 'Processing...', text: 'Calculating reports, please wait.', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); }});

    $.post("{{ route('pos.session.end') }}", {
        _token: "{{ csrf_token() }}",
        session_id: sessionId
    }, function(res) {
        if(res.status === 'success') {
            Swal.fire({ icon: 'success', title: 'Shift Ended!', text: 'Your closing report is ready.', confirmButtonText: 'Print & Reload' }).then(() => {
                // ইনভয়েস প্রিন্টের জন্য নতুন উইন্ডো ওপেন করবে (রাউটটা আমরা পরে বানাবো)
                // window.open("/pos/session/report/" + res.session_id, "_blank");
                location.reload();
            });
        }
    });
}

// সেশন এডিট মোডাল ওপেন লজিক
// সেশন এডিট মোডাল ওপেন লজিক
$(document).on('click', '.btnEditSession', function(e) {
    e.preventDefault();

    $('#editSessIdLabel').text($(this).attr('data-id'));
    $('#editSessionId').val($(this).attr('data-id'));
    $('#editStartTime').val($(this).attr('data-start'));
    $('#editEndTime').val($(this).attr('data-end'));
    $('#editStatus').val($(this).attr('data-status'));

    const editModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('editSessionModal'));
    editModal.show();
});

// সেশন এডিট ফর্ম সাবমিট
$('#editSessionForm').on('submit', function(e) {
    e.preventDefault();
    let formData = $(this).serialize();

    $.post("{{ route('pos.session.update') }}", formData, function(res) {
        if(res.status === 'success') {
            $('#editSessionModal').modal('hide');
            Swal.fire('Updated!', res.message, 'success').then(() => {
                location.reload();
            });
        }
    });
});

// পুরনো এন্ড সেশন মেথডে প্রিন্ট লিংক আনকমেন্ট করুন
window.processEndSession = function(sessionId) {
    Swal.fire({ title: 'Processing...', text: 'Calculating reports, please wait.', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); }});

    $.post("{{ route('pos.session.end') }}", {
        _token: "{{ csrf_token() }}",
        session_id: sessionId
    }, function(res) {
        if(res.status === 'success') {
            Swal.fire({ icon: 'success', title: 'Shift Ended!', text: 'Your closing report is ready.', confirmButtonText: 'Print & Reload' }).then(() => {
                window.open("{{ url('/pos/session/report') }}/" + res.session_id, "_blank");
                location.reload();
            });
        }
    });
}
</script>

@endsection
