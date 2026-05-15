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

            <div id="payModalItemsArea" style="max-height: 200px; overflow-y: auto;">
              <div class="progga-pay-summary-item" style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 8px;">
                <span class="progga-pay-summary-name text-muted">Items will be loaded here</span>
              </div>
            </div>

            <div style="margin-top:14px; padding-top:10px; border-top:2px solid var(--progga-border-light);">
              <div class="progga-pos-total-row" style="display: flex; justify-content: space-between; font-size: 13px; color: #666; margin-bottom: 4px;">
                <span>Subtotal</span><span id="paySubtotal">৳0.00</span>
              </div>
              <div class="progga-pos-total-row" style="display: flex; justify-content: space-between; font-size: 13px; color: #d33; margin-bottom: 4px;">
                <span>Discount</span><span id="payDiscount">−৳0.00</span>
              </div>
              <div class="progga-pos-total-row" style="display: flex; justify-content: space-between; font-size: 13px; color: #666; margin-bottom: 4px;">
                <span>Tax/Service</span><span id="payTax">৳0.00</span>
              </div>
              <div class="progga-pos-total-row grand" style="display: flex; justify-content: space-between; font-size: 18px; font-weight: 900; color: var(--progga-primary); margin-top: 8px; border-top: 2px solid #f1f1f1; padding-top: 8px;">
                <span>GRAND TOTAL</span><span id="payTotalAmount">৳0.00</span>
              </div>
            </div>
          </div>

          <div class="col-md-7">
            <div class="progga-form-label" style="font-weight:700; margin-bottom:12px; font-size: 14px; color: var(--progga-primary);">
              Payment Method
            </div>

            <form class="progga-pay-form" id="payForm">
              <input type="hidden" id="payOrderId" name="order_id">

              <div class="progga-pay-method-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 15px;">
                <input type="radio" id="payCash" name="payment_method" value="Cash" style="display: none;" checked>
                <label for="payCash" class="progga-pay-method-btn" style="border: 2px solid var(--progga-border); border-radius: 10px; padding: 12px; text-align: center; cursor: pointer; transition: 0.2s;">
                  <i class="bi bi-cash-coin d-block" style="font-size: 20px; color: var(--progga-primary);"></i>
                  <span style="font-size: 12px; font-weight: 700;">Cash</span>
                </label>

                <input type="radio" id="payCard" name="payment_method" value="Card" style="display: none;">
                <label for="payCard" class="progga-pay-method-btn" style="border: 2px solid var(--progga-border); border-radius: 10px; padding: 12px; text-align: center; cursor: pointer; transition: 0.2s;">
                  <i class="bi bi-credit-card d-block" style="font-size: 20px; color: var(--progga-primary);"></i>
                  <span style="font-size: 12px; font-weight: 700;">Card</span>
                </label>

                <input type="radio" id="payBkash" name="payment_method" value="Mobile Banking" style="display: none;">
                <label for="payBkash" class="progga-pay-method-btn" style="border: 2px solid var(--progga-border); border-radius: 10px; padding: 12px; text-align: center; cursor: pointer; transition: 0.2s;">
                  <i class="bi bi-phone d-block" style="font-size: 20px; color: var(--progga-primary);"></i>
                  <span style="font-size: 12px; font-weight: 700;">Mobile</span>
                </label>
              </div>

              <div class="progga-pm-ref" id="transactionDiv" style="display: none;">
                <div class="progga-form-group">
                  <label class="progga-form-label" style="font-size: 12px; font-weight: 700; color: #555;">Transaction / Reference Number <span class="text-danger">*</span></label>
                  <input type="text" name="transaction_id" class="form-control" placeholder="e.g. TXN-8473920" style="background: #fff; border: 1.5px solid var(--progga-border); border-radius: 8px;">
                  <div class="progga-form-hint" style="font-size: 10.5px; color: #888; margin-top: 4px;">Enter the reference number from the payment terminal or mobile banking app.</div>
                </div>
              </div>

              <button type="submit" class="progga-btn progga-btn-secondary progga-btn-lg w-100" style="margin-top:20px; padding: 12px; font-size: 14px; font-weight: 700; border-radius: 10px; border: none;">
                <i class="bi bi-check-circle-fill"></i> Confirm Payment &amp; Print
              </button>
            </form>
          </div>

        </div>
      </div>

    </div>
  </div>
</div>

<style>
/* Payment Radio Button CSS */
input[type="radio"]:checked + .progga-pay-method-btn {
    border-color: var(--progga-primary) !important;
    background: rgba(33, 53, 42, 0.05) !important;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}
</style>
