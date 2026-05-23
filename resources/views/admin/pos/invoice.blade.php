<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Customer Invoice #{{ $order->order_number }} — {{ $restaurantSettingName ?? 'Progga RMS' }}</title>
  <style>
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

    body {
      font-family: 'Segoe UI', system-ui, sans-serif;
      background: #c8c8c8;
      background-image:
        repeating-linear-gradient(45deg, rgba(0,0,0,.02) 0, rgba(0,0,0,.02) 1px,
        transparent 0, transparent 50%);
      background-size: 12px 12px;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 36px 16px 70px;
    }

    /* ── Page label ── */
    .page-label {
      font-size: 13px; font-weight: 700;
      color: var(--primary);
      background: #fff;
      padding: 6px 20px;
      border-radius: 20px;
      box-shadow: 0 2px 8px rgba(0,0,0,.12);
      margin-bottom: 28px;
      letter-spacing: .4px;
    }

    /* ── Receipt card ── */
    .receipt-card {
      width: 340px;
      background: #fff;
      border-radius: 6px 6px 0 0;
      box-shadow: 0 8px 32px rgba(0,0,0,.18);
      position: relative;
      overflow: visible;
    }
    .receipt-card::after {
      content: '';
      position: absolute;
      bottom: -14px; left: 0; right: 0; height: 14px;
      background: radial-gradient(circle at 7px -1px, #c8c8c8 7px, transparent 0) 0 0 / 14px 14px repeat-x;
    }

    /* ── Header ── */
    .bill-header {
      background: var(--primary);
      padding: 22px 20px 18px;
      text-align: center;
      border-radius: 6px 6px 0 0;
    }
    .bill-logo-circle {
      width: 52px; height: 52px; border-radius: 50%;
      background: var(--gold);
      display: flex; align-items: center; justify-content: center;
      font-size: 26px; font-weight: 900; color: var(--primary);
      margin: 0 auto 10px;
      box-shadow: 0 4px 14px rgba(0,0,0,.3);
    }
    .bill-restaurant-name {
      font-size: 18px; font-weight: 900; color: #fff;
      letter-spacing: 2px; text-transform: uppercase; margin-bottom: 6px;
    }
    .bill-restaurant-addr {
      font-size: 11px; color: rgba(255,255,255,.7);
      line-height: 1.6; margin-bottom: 2px;
    }
    .bill-restaurant-phone {
      font-size: 11.5px; color: var(--gold); font-weight: 700; margin-top: 4px;
    }

    /* ── Title bar ── */
    .bill-title-bar {
      background: var(--gold);
      padding: 7px 20px;
      display: flex; align-items: center; justify-content: space-between;
    }
    .bill-title-text {
      font-size: 12px; font-weight: 900; color: var(--primary);
      letter-spacing: 1.5px; text-transform: uppercase;
    }
    .bill-musak { font-size: 10.5px; font-weight: 700; color: var(--primary-lt); }

    /* ── VAT line ── */
    .bill-vat-line {
      text-align: center; font-size: 10px; color: var(--muted);
      padding: 6px 20px; border-bottom: 1px dashed var(--border);
      font-family: var(--mono);
    }

    /* ── Bill body ── */
    .bill-body { padding: 14px 20px; }

    /* ── Meta grid ── */
    .bill-meta-grid {
      display: grid; grid-template-columns: 1fr 1fr;
      gap: 4px 12px; margin-bottom: 14px;
    }
    .bill-meta-row {
      display: flex; gap: 4px;
      font-size: 11.5px; font-family: var(--mono);
    }
    .bill-meta-label { color: var(--muted); min-width: 76px; }
    .bill-meta-val   { color: var(--text); font-weight: 700; }

    /* ── Separators ── */
    .dashed-sep       { border: none; border-top: 1.5px dashed var(--border); margin: 10px 0; }
    .dashed-sep-thick { border: none; border-top: 2px dashed #aaa; margin: 12px 0; }

    /* ── Items table ── */
    .bill-items-table {
      width: 100%; border-collapse: collapse;
      font-family: var(--mono); font-size: 12px;
    }
    .bill-items-table thead th {
      font-size: 10.5px; font-weight: 800; color: var(--muted);
      text-transform: uppercase; letter-spacing: .5px;
      padding: 4px 0 8px; border-bottom: 1.5px dashed var(--border);
    }
    .bill-items-table thead th:first-child { width: 32px; text-align: center; }
    .bill-items-table thead th:last-child  { text-align: right; width: 60px; }
    .bill-items-table tbody td {
      padding: 7px 0; vertical-align: top;
      border-bottom: 1px dotted #eee;
    }
    .bill-items-table tbody tr:last-child td { border-bottom: none; }
    .bill-items-table td:first-child { text-align: center; color: var(--muted); font-weight: 700; }
    .bill-items-table td:last-child  { text-align: right; font-weight: 700; }
    .bill-item-name { font-weight: 700; color: var(--text); line-height: 1.3; }
    .bill-item-note { font-size: 10px; color: var(--muted); margin-top: 2px; font-style: italic; }

    /* ── Totals ── */
    .bill-totals { padding: 0 2px; }
    .bill-total-row {
      display: flex; justify-content: space-between; align-items: center;
      padding: 4px 0; font-family: var(--mono); font-size: 12px; color: var(--muted);
    }
    .bill-total-row.discount span:last-child { color: var(--success); }
    .bill-total-row.grand {
      font-size: 15px; font-weight: 900; color: var(--primary);
      padding: 10px 0 6px;
      border-top: 2px solid var(--primary); margin-top: 6px;
    }
    .bill-total-row.grand span:last-child { font-size: 17px; }

    /* ── Payment ── */
    .bill-payment {
      display: flex; align-items: center; justify-content: space-between;
      background: var(--cream); border-radius: 8px;
      padding: 10px 14px; margin-top: 12px;
    }
    .bill-payment-label {
      font-size: 11px; color: var(--muted); font-weight: 700;
      text-transform: uppercase; letter-spacing: .5px;
    }
    .bill-payment-val { font-size: 13px; font-weight: 900; color: var(--primary); }

    /* ── Server ── */
    .bill-server {
      text-align: center; font-size: 11.5px; color: var(--muted);
      margin-top: 12px; font-family: var(--mono);
    }
    .bill-server strong { color: var(--text); }

    /* ── Footer ── */
    .bill-footer {
      text-align: center; padding: 16px 20px 22px;
      border-top: 1.5px dashed var(--border); margin-top: 14px;
    }
    .bill-thankyou {
      font-size: 15px; font-weight: 900; color: var(--primary);
      letter-spacing: 1px; margin-bottom: 10px;
    }
    .bill-footer-links {
      font-size: 10px; color: var(--muted);
      line-height: 1.8; font-family: var(--mono);
    }
    .bill-footer-links a { color: var(--muted); text-decoration: none; }
    .bill-partner { font-size: 9.5px; color: #bbb; margin-top: 6px; font-family: var(--mono); }

    /* ── Print button ── */
    .btn-print-wrap {
      margin-top: 28px;
      display: flex; justify-content: center; gap: 10px;
    }
    .btn-print {
      display: inline-flex; align-items: center; gap: 7px;
      padding: 11px 20px; border: none; cursor: pointer;
      border-radius: 24px; font-size: 13px; font-weight: 700;
      background: var(--primary); color: var(--gold);
      box-shadow: 0 4px 16px rgba(33,53,42,.35);
      text-decoration: none;
      transition: transform .15s, box-shadow .15s;
    }
    .btn-print.outline {
      background: #fff; color: var(--primary); border: 2px solid var(--primary); box-shadow: none;
    }
    .btn-print:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(33,53,42,.4); }
    .btn-print svg { width: 15px; height: 15px; }

    /* ── Print media ── */
    @media print {
      body { background: none !important; padding: 0 !important; display: block; }
      .no-print { display: none !important; }
      .receipt-card { box-shadow: none !important; width: 100% !important; max-width: 320px; margin: 0 auto; }
      .receipt-card::after { display: none; }
      .page-label { display: none; }
    }
  </style>
</head>
<body>

  <div class="page-label no-print">🧾 Customer Invoice</div>

  <div class="receipt-card">

    <div class="bill-header">
      <div class="bill-logo-circle">{{ substr($restaurantSettingName ?? 'P', 0, 1) }}</div>
      <div class="bill-restaurant-name">{{ $restaurantSettingName ?? 'Progga RMS' }}</div>
      <div class="bill-restaurant-addr">
        {!! nl2br(e($restaurantSettingAddress ?? 'Block I, House 52, Road No. 01\nBanani, Dhaka 1213')) !!}
      </div>
      <div class="bill-restaurant-phone">📞 {{ $restaurantSettingPhone ?? '01755 898 542' }}</div>
    </div>

    <div class="bill-title-bar">
      <span class="bill-title-text">Guest Bill</span>
      <span class="bill-musak">MUSAK – 6.3</span>
    </div>

    <div class="bill-vat-line">Bin No – {{ $taxSettingTaxRegistrationNo ?? '006334813-0101' }}</div>

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
          <span class="bill-meta-label">Date</span>
          <span class="bill-meta-val">: {{ $order->created_at->format('d/m/y') }}</span>
        </div>
        <div class="bill-meta-row">
          <span class="bill-meta-label">Customer</span>
          <span class="bill-meta-val">: {{ $order->customer->name ?? 'Walk-in' }}</span>
        </div>
        <div class="bill-meta-row" style="grid-column:1/-1;">
          <span class="bill-meta-label">Time</span>
          <span class="bill-meta-val">: {{ $order->created_at->format('h:i:s A') }}</span>
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

              {{-- যদি Unavailable না হয়, তবেই ইনভয়েসে প্রিন্ট হবে --}}
              @if(!$item->is_unavailable)
                  @php $addons = json_decode($item->addons, true) ?? []; @endphp
                  <tr>
                    <td>{{ $item->quantity }}</td>
                    <td>
                      <div class="bill-item-name">{{ $item->product_name }}</div>
                      @if($item->food_note)
                        <div class="bill-item-note">{{ $item->food_note }}</div>
                      @endif
                      @if(count($addons) > 0)
                        <div class="bill-item-note">
                            @foreach($addons as $addon) +{{ $addon['name'] }} @endforeach
                        </div>
                      @endif
                    </td>
                    <td>{{ round($item->subtotal) }}</td>
                  </tr>
              @endif

          @endforeach

        </tbody>
      </table>

      <hr class="dashed-sep-thick">

      <div class="bill-totals">
        <div class="bill-total-row">
          <span>Sub Total</span>
          <span>{{ number_format($order->subtotal, 0) }}</span>
        </div>



        @if($order->service_charge > 0)
        <div class="bill-total-row">
          <span>Service Charge ({{ $taxSettingServiceCharge }}%)</span>
          <span>+ {{ number_format($order->service_charge, 0) }}</span>
        </div>
        @endif

        @if($order->vat_tax > 0)
        <div class="bill-total-row">
          <span>{{ $taxSettingTaxLabel }} ({{ $taxSettingVatRate }}%)</span>
          <span>+ {{ number_format($order->vat_tax, 0) }}</span>
        </div>
        @endif
 @if($order->discount_amount > 0)
        <div class="bill-total-row discount">
          <span>Discount ({{ ucfirst($order->discount_type) }})</span>
          <span>− {{ number_format($order->discount_amount, 0) }}</span>
        </div>
        @endif
        <div class="bill-total-row grand">
          <span>Total Payable</span>
          <span>{{ $restaurantSettingCurrency ?? '৳' }} {{ number_format($order->grand_total, 0) }}</span>
        </div>
      </div>

      <div class="bill-payment">
        <div>
          <div class="bill-payment-label">Paid By</div>
          <div class="bill-payment-val">{{ $order->payment_type ?? 'Cash' }}</div>
        </div>
        <div style="text-align:right;">
          <div class="bill-payment-label">Change</div>
          <div class="bill-payment-val">{{ $restaurantSettingCurrency ?? '৳' }} 0</div>
        </div>
      </div>

      <div class="bill-server">
        You Have Been Served By: <strong>{{ $order->waiter->name ?? $order->user->name ?? 'N/A' }}</strong>
      </div>

    </div><div class="bill-footer">
      {{-- <div class="bill-thankyou">✦ Thank You ✦</div> --}}
      <div class="bill-footer-links">
        {!! nl2br(e($invoiceSettingFooterNote ?? "Tech Partner — Progga RMS\nVisit our website to know more!")) !!}
      </div>
      <div class="bill-partner">::::::::::::::::::::::::::::::::::::::::::::</div>
    </div>

  </div><div class="btn-print-wrap no-print">
    <a href="{{ route('pos.index') }}" class="btn-print outline">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:16px;height:16px;">
          <path d="M19 12H5M12 19l-7-7 7-7"/>
      </svg>
      Back to POS
    </a>
    <button class="btn-print" onclick="window.print()">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <polyline points="6 9 6 2 18 2 18 9"/>
        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
        <rect x="6" y="14" width="12" height="8"/>
      </svg>
      Print Invoice
    </button>
  </div>

</body>
</html>
