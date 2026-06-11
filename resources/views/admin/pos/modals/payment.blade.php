<div class="modal fade progga-modal" id="paymentModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">

      <div class="modal-header" style="background: var(--progga-primary); padding: 16px 20px;">
        <h5 class="modal-title" style="color: #fff; font-size: 15px; font-weight: 800;">
          <i class="bi bi-credit-card me-2"></i>Checkout &amp; Payment — Table <span id="payTableLabel">—</span>
        </h5>
        <button type="button" class="btn-close" style="filter: invert(1) brightness(2);" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body" style="padding: 20px; background: var(--progga-bg);">
        <div class="row g-4">

          <div class="col-md-5">
            <div class="progga-form-label" style="font-weight:700; margin-bottom:12px; font-size: 14px; color: var(--progga-primary);">
              Order Summary
            </div>

            <div id="payModalItemsArea" style="max-height: 200px; overflow-y: auto;"></div>

            <div style="margin-top:14px; padding-top:10px; border-top:2px solid var(--progga-border-light);">
              <div class="progga-pos-total-row" style="display: flex; justify-content: space-between; font-size: 13px; color: #666; margin-bottom: 4px;">
                <span>Subtotal</span><span id="paySubtotal">৳0</span>
              </div>
              <div class="progga-pos-total-row" id="payServiceRow" style="display: flex; justify-content: space-between; font-size: 13px; color: #666; margin-bottom: 4px;">
    <span>Service Charge ({{ $taxSettingServiceCharge }}%)</span><span id="payService">৳0</span>
</div>
              <div class="progga-pos-total-row" style="display: flex; justify-content: space-between; font-size: 13px; color: #666; margin-bottom: 4px;">
                <span>{{ $taxSettingTaxLabel }} ({{ $taxSettingVatRate }}%)</span><span id="payVat">৳0</span>
              </div>

               <div class="progga-pos-total-row" style="display: flex; justify-content: space-between; font-size: 13px; color: #d33; margin-bottom: 4px;">
                <span>Discount</span><span id="payDiscount">−৳0</span>
              </div>
              <div class="progga-pos-total-row grand" style="display: flex; justify-content: space-between; font-size: 16px; font-weight: 900; color: var(--progga-primary); margin-top: 8px; border-top: 2px solid #f1f1f1; padding-top: 8px;">
                <span>GRAND TOTAL</span><span id="payTotalAmount">৳0</span>
              </div>

              <div class="progga-pos-total-row mt-2" style="display: flex; justify-content: space-between; align-items: center; font-size: 14px; font-weight: 700; color: #333;">
                <span>Total Paid</span>
                <input type="number" id="payTotalPaidAmount" name="total_paid_amount" form="payForm" class="form-control form-control-sm text-end" style="width: 120px; font-weight:bold; border: 1.5px solid var(--progga-border);" value="0">
              </div>
              <div class="progga-pos-total-row" style="display: flex; justify-content: space-between; font-size: 15px; font-weight: 900; color: #d33; margin-top: 6px;">
                <span>DUE AMOUNT</span><span id="payDueAmount">৳0</span>
              </div>

            </div>
          </div>

          <div class="col-md-7">
            <form class="progga-pay-form" id="payForm">
              <input type="hidden" id="payOrderId" name="order_id">
              <input type="hidden" id="payOrderType" name="order_type">
              <div class="row mb-3">
                <div class="col-6">
                    <label style="font-size: 11px; font-weight: 700; color: #777; margin-bottom: 4px;">Discount Type</label>
                    <select name="discount_type" id="modal_discount_type" class="form-control" style="border: 1.5px solid var(--progga-border); border-radius: 8px; font-size: 13px;" onchange="calculateModalTotal()">
                        <option value="fixed">Fixed (৳)</option>
                        <option value="percentage">Percentage (%)</option>
                    </select>
                </div>
                <div class="col-6">

                    <label style="font-size: 11px; font-weight: 700; color: #777; margin-bottom: 4px;">Discount Amount</label>
                    <input type="number" name="discount_value" id="modal_discount_value" class="form-control" placeholder="0" min="0" style="border: 1.5px solid var(--progga-border); border-radius: 8px; font-size: 13px;" onkeyup="calculateModalTotal()">
                </div>
              </div>

              <div class="progga-form-label" style="font-weight:700; margin-bottom:10px; font-size: 14px; color: var(--progga-primary);">
                Payment Method
              </div>

              <div class="progga-pay-method-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-bottom: 12px;">
                <input type="radio" id="payCash" name="payment_method" value="Cash" style="display: none;" checked>
                <label for="payCash" class="progga-pay-method-btn" style="border: 2px solid var(--progga-border); border-radius: 8px; padding: 10px; text-align: center; cursor: pointer;">
                  <i class="bi bi-cash-coin d-block" style="font-size: 18px; color: var(--progga-primary);"></i>
                  <span style="font-size: 11px; font-weight: 700;">Cash</span>
                </label>

                <input type="radio" id="payCard" name="payment_method" value="Card" style="display: none;">
                <label for="payCard" class="progga-pay-method-btn" style="border: 2px solid var(--progga-border); border-radius: 8px; padding: 10px; text-align: center; cursor: pointer;">
                  <i class="bi bi-credit-card d-block" style="font-size: 18px; color: var(--progga-primary);"></i>
                  <span style="font-size: 11px; font-weight: 700;">Card</span>
                </label>

                <input type="radio" id="payBkash" name="payment_method" value="Mobile Banking" style="display: none;">
                <label for="payBkash" class="progga-pay-method-btn" style="border: 2px solid var(--progga-border); border-radius: 8px; padding: 10px; text-align: center; cursor: pointer;">
                  <i class="bi bi-phone d-block" style="font-size: 18px; color: var(--progga-primary);"></i>
                  <span style="font-size: 11px; font-weight: 700;">Mobile</span>
                </label>

                <input type="radio" id="paySplit" name="payment_method" value="Split" style="display: none;">
                <label for="paySplit" class="progga-pay-method-btn" style="border: 2px solid var(--progga-border); border-radius: 8px; padding: 10px; text-align: center; cursor: pointer;">
                  <i class="bi bi-pie-chart-fill d-block" style="font-size: 18px; color: var(--progga-primary);"></i>
                  <span style="font-size: 11px; font-weight: 700;">Split</span>
                </label>
              </div>

              <div id="splitPaymentDiv" style="display: none; background: #f8f9fa; padding: 12px; border-radius: 8px; margin-bottom: 15px; border: 1px dashed #ccc;">
                  <div class="row g-2">
                      <div class="col-4">
                          <label style="font-size: 11px; font-weight: 700; color: #555;">Cash</label>
                          <input type="number" name="paid_in_cash" id="splitCash" class="form-control split-input p-1 text-center" value="0">
                      </div>
                      <div class="col-4">
                          <label style="font-size: 11px; font-weight: 700; color: #555;">Card</label>
                          <input type="number" name="paid_in_card" id="splitCard" class="form-control split-input p-1 text-center" value="0">
                      </div>
                      <div class="col-4">
                          <label style="font-size: 11px; font-weight: 700; color: #555;">MFC (Mobile)</label>
                          <input type="number" name="paid_in_mfc" id="splitMfc" class="form-control split-input p-1 text-center" value="0">
                      </div>
                  </div>
              </div>

              <div class="progga-pm-ref" id="transactionDiv" style="display: none; margin-bottom: 15px;">
                  <label style="font-size: 12px; font-weight: 700; color: #555;">Transaction / Reference No</label>
                  <input type="text" name="transaction_id" class="form-control" placeholder="e.g. TXN-8473920" style="border: 1.5px solid var(--progga-border); border-radius: 8px;">
              </div>

              <div class="d-flex gap-2" style="margin-top:20px;">
                <button type="button" id="btnPreInvoice" class="progga-btn progga-btn-outline w-50" style="padding: 12px; font-size: 13px; font-weight: 700; border-radius: 10px;">
                  <i class="bi bi-printer"></i> Print Pre-Invoice
                </button>
                <button type="submit" class="progga-btn progga-btn-secondary w-50" style="padding: 12px; font-size: 13px; font-weight: 700; border-radius: 10px; border: none;">
                  <i class="bi bi-check-circle-fill"></i> Confirm Payment
                </button>
              </div>

            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
input[type="radio"]:checked + .progga-pay-method-btn {
    border-color: var(--progga-primary) !important;
    background: rgba(33, 53, 42, 0.05) !important;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}
</style>

<script>
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

window.updateDueAmount = function() {
    let grand = parseFloat($('#payTotalAmount').text().replace('৳', '')) || 0;
    let method = $('input[name="payment_method"]:checked').val();
    let totalPaid = 0;

    if (method === 'Split') {
        let cash = parseFloat($('#splitCash').val()) || 0;
        let card = parseFloat($('#splitCard').val()) || 0;
        let mfc = parseFloat($('#splitMfc').val()) || 0;
        totalPaid = cash + card + mfc;

        $('#payTotalPaidAmount').val(totalPaid).prop('readonly', true);
    } else {
        $('#payTotalPaidAmount').prop('readonly', false);
        totalPaid = parseFloat($('#payTotalPaidAmount').val()) || 0;
    }

    let due = Math.round(grand - totalPaid);
    let tips = 0;
    if(due < 0) {
        tips = Math.abs(due);
        due = 0;
    }

    $('#payDueAmount').text('৳' + due);
};

$(document).on('keyup change', '#payTotalPaidAmount, .split-input', window.updateDueAmount);

$(document).on('change', 'input[name="payment_method"]', function() {
    let method = $(this).val();

    if(method === 'Split') {
        $('#splitPaymentDiv').slideDown('fast');
        $('#transactionDiv').slideUp('fast');
    } else if(method === 'Card' || method === 'Mobile Banking') {
        $('#splitPaymentDiv').slideUp('fast');
        $('#transactionDiv').slideDown('fast');
        $('#payTotalPaidAmount').val(Math.round(parseFloat($('#payTotalAmount').text().replace('৳', ''))));
    } else {
        $('#splitPaymentDiv').slideUp('fast');
        $('#transactionDiv').slideUp('fast');
        $('#payTotalPaidAmount').val(Math.round(parseFloat($('#payTotalAmount').text().replace('৳', ''))));
    }
    window.updateDueAmount();
});

$(document).on('click', '#btnPreInvoice', function() {
    let orderId = $('#payOrderId').val();
    if(!orderId) {
        Swal.fire('Info', 'For Takeaway or Delivery without table, please place the order first to generate a pre-invoice.', 'info');
        return;
    }

    let discType = $('#modal_discount_type').val();
    let discVal = $('#modal_discount_value').val() || 0;

    let url = "{{ url('/pos/pre-invoice') }}/" + orderId + "?disc_type=" + discType + "&disc_val=" + discVal;
    window.open(url, '_blank');
});

</script>
