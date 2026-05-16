<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pre-Payment Invoice #{{ $order->order_number }}</title>
  <style>
    /* invoice.blade.php এর সমস্ত CSS হুবহু এখানে রাখুন */
    :root {
      --primary:    #21352a;
      --primary-lt: #2e4a3c;
      --gold:       #d5aa65;
      --gold-dk:    #b8903f;
      --cream:      #f9f6f2;
      --border:     #d8d8d8;
      --text:       #1a1a1a;
      --muted:      #666;
      --success:    #1e7a4a;
      --mono:       'Courier New', Courier, monospace;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Segoe UI', system-ui, sans-serif; background: #c8c8c8; padding: 36px 16px 70px; display: flex; flex-direction: column; align-items: center; }
    .page-label { font-size: 13px; font-weight: 700; color: var(--primary); background: #fff; padding: 6px 20px; border-radius: 20px; margin-bottom: 28px; }
    .receipt-card { width: 340px; background: #fff; border-radius: 6px 6px 0 0; box-shadow: 0 8px 32px rgba(0,0,0,.18); position: relative; }
    .bill-header { background: var(--primary); padding: 22px 20px 18px; text-align: center; border-radius: 6px 6px 0 0; }
    .bill-logo-circle { width: 52px; height: 52px; border-radius: 50%; background: var(--gold); display: flex; align-items: center; justify-content: center; font-size: 26px; font-weight: 900; color: var(--primary); margin: 0 auto 10px; }
    .bill-restaurant-name { font-size: 18px; font-weight: 900; color: #fff; text-transform: uppercase; margin-bottom: 6px; }
    .bill-restaurant-addr { font-size: 11px; color: rgba(255,255,255,.7); line-height: 1.6; margin-bottom: 2px; }
    .bill-restaurant-phone { font-size: 11.5px; color: var(--gold); font-weight: 700; margin-top: 4px; }
    .bill-title-bar { background: #eee; padding: 7px 20px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px dashed var(--border); }
    .bill-title-text { font-size: 12px; font-weight: 900; color: var(--primary); letter-spacing: 1.5px; text-transform: uppercase; }
    .bill-body { padding: 14px 20px; }
    .bill-meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 4px 12px; margin-bottom: 14px; }
    .bill-meta-row { display: flex; gap: 4px; font-size: 11.5px; font-family: var(--mono); }
    .bill-meta-label { color: var(--muted); min-width: 76px; }
    .bill-meta-val   { color: var(--text); font-weight: 700; }
    .dashed-sep-thick { border: none; border-top: 2px dashed #aaa; margin: 12px 0; }
    .bill-items-table { width: 100%; border-collapse: collapse; font-family: var(--mono); font-size: 12px; }
    .bill-items-table thead th { font-size: 10.5px; color: var(--muted); padding: 4px 0 8px; border-bottom: 1.5px dashed var(--border); }
    .bill-items-table td { padding: 7px 0; border-bottom: 1px dotted #eee; }
    .bill-items-table td:first-child { text-align: center; font-weight: 700; }
    .bill-items-table td:last-child  { text-align: right; font-weight: 700; }
    .bill-item-name { font-weight: 700; color: var(--text); }
    .bill-item-note { font-size: 10px; color: var(--muted); font-style: italic; }
    .bill-totals { padding: 0 2px; }
    .bill-total-row { display: flex; justify-content: space-between; padding: 4px 0; font-family: var(--mono); font-size: 12px; color: var(--muted); }
    .bill-total-row.discount span:last-child { color: var(--success); }
    .bill-total-row.grand { font-size: 15px; font-weight: 900; color: var(--primary); padding: 10px 0 6px; border-top: 2px solid var(--primary); margin-top: 6px; }
    .bill-footer { text-align: center; padding: 16px 20px 22px; border-top: 1.5px dashed var(--border); margin-top: 14px; }
    .bill-server { text-align: center; font-size: 11.5px; color: var(--muted); margin-top: 12px; font-family: var(--mono); }
    .btn-print-wrap { margin-top: 28px; display: flex; justify-content: center; gap: 10px; }
    .btn-print { display: inline-flex; align-items: center; gap: 7px; padding: 11px 20px; border: none; cursor: pointer; border-radius: 24px; font-size: 13px; font-weight: 700; background: var(--primary); color: var(--gold); }
    @media print {
      body { background: none !important; padding: 0 !important; }
      .no-print { display: none !important; }
      .receipt-card { box-shadow: none !important; width: 100% !important; max-width: 320px; margin: 0 auto; }
    }
  </style>
</head>
<body>

  <div class="page-label no-print">📋 Pre-Payment Bill</div>

  <div class="receipt-card">

    <div class="bill-header">
      <div class="bill-logo-circle">{{ substr($restaurantSettingName ?? 'P', 0, 1) }}</div>
      <div class="bill-restaurant-name">{{ $restaurantSettingName ?? 'Progga RMS' }}</div>
      <div class="bill-restaurant-addr">
        {!! nl2br(e($restaurantSettingAddress ?? 'Banani, Dhaka')) !!}
      </div>
    </div>

    <div class="bill-title-bar">
      <span class="bill-title-text" style="color:#555;">GUEST BILL (UNPAID)</span>
    </div>

    <div class="bill-body">
      <div class="bill-meta-grid">
        <div class="bill-meta-row">
          <span class="bill-meta-label">Order ID</span>
          <span class="bill-meta-val">: {{ $invoiceSettingPrefix ?? 'INV-' }}{{ $order->order_number }}</span>
        </div>
        <div class="bill-meta-row">
          <span class="bill-meta-label">Table No</span>
          <span class="bill-meta-val">: {{ $order->table->table_number ?? 'Takeaway' }}</span>
        </div>
        <div class="bill-meta-row">
          <span class="bill-meta-label">Customer</span>
          <span class="bill-meta-val">: {{ $order->customer->name ?? 'Walk-in' }}</span>
        </div>
      </div>

      <hr class="dashed-sep-thick">

      <table class="bill-items-table">
        <thead>
          <tr>
            <th>QTY</th>
            <th>Item</th>
            <th>Price</th>
          </tr>
        </thead>
        <tbody>
          @foreach($order->orderDetails as $item)
              @php $addons = json_decode($item->addons, true) ?? []; @endphp
              <tr>
                <td>{{ $item->quantity }}</td>
                <td>
                  <div class="bill-item-name">{{ $item->product_name }}</div>
                  @if($item->food_note) <div class="bill-item-note">{{ $item->food_note }}</div> @endif
                  @if(count($addons) > 0)
                    <div class="bill-item-note">@foreach($addons as $addon) +{{ $addon['name'] }} @endforeach</div>
                  @endif
                </td>
                <td>{{ number_format($item->subtotal, 2) }}</td>
              </tr>
          @endforeach
        </tbody>
      </table>

      <hr class="dashed-sep-thick">

      <div class="bill-totals">
        <div class="bill-total-row">
          <span>Order Total</span>
          <span>{{ number_format($order->subtotal, 2) }}</span>
        </div>

        @if($order->discount_amount > 0)
        <div class="bill-total-row discount">
          <span>Discount ({{ ucfirst($order->discount_type) }})</span>
          <span>− {{ number_format($order->discount_amount, 2) }}</span>
        </div>
        @endif

        @if($order->service_charge > 0)
        <div class="bill-total-row">
          <span>Service Charge</span>
          <span>+ {{ number_format($order->service_charge, 2) }}</span>
        </div>
        @endif

        @if($order->vat_tax > 0)
        <div class="bill-total-row">
          <span>VAT/Tax</span>
          <span>+ {{ number_format($order->vat_tax, 2) }}</span>
        </div>
        @endif

        <div class="bill-total-row grand">
          <span>Total Payable</span>
          <span>{{ $restaurantSettingCurrency ?? '৳' }} {{ number_format($order->grand_total, 2) }}</span>
        </div>
      </div>

      <div class="bill-server" style="margin-top:20px; font-weight:bold;">
        *** PLEASE PAY AT THE COUNTER ***
      </div>
      <div class="bill-server">
        Served By: <strong>{{ $order->waiter->name ?? 'N/A' }}</strong>
      </div>

    </div>
  </div>

  <div class="btn-print-wrap no-print">
    <a href="{{ route('pos.index') }}" class="btn-print outline" style="text-decoration: none; display: inline-flex; align-items: center; gap: 7px;">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:16px;height:16px;">
          <path d="M19 12H5M12 19l-7-7 7-7"/>
      </svg>
      Back to POS
    </a>

    <button class="btn-print" onclick="window.print()" style="display: inline-flex; align-items: center; gap: 7px;">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:16px;height:16px;">
        <polyline points="6 9 6 2 18 2 18 9"/>
        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
        <rect x="6" y="14" width="12" height="8"/>
      </svg>
      Print Bill
    </button>
  </div>

</body>
</html>
