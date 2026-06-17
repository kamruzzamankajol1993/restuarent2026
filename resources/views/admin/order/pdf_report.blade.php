<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Order Report</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; color: #333; }
        .header { text-align: center; margin-bottom: 10px; border-bottom: 2px solid #21352a; padding-bottom: 8px; }
        .header h2 { margin: 0 0 4px 0; color: #21352a; font-size: 18px; }
        .header p { margin: 0; color: #666; font-size: 10px; }
        .header h3 { margin: 6px 0 0 0; color: #444; font-size: 13px; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #d9d9d9; padding: 5px 6px; vertical-align: top; }
        th { background-color: #21352a; color: #ffffff; font-size: 9px; font-weight: bold; text-align: left; }
        td { font-size: 9px; }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-danger { color: #dc3545; font-weight: bold; }
        .text-success { color: #198754; font-weight: bold; }
        .muted { color: #777; font-size: 8px; }
        .strong { font-weight: bold; }
        .footer { margin-top: 12px; font-size: 9px; text-align: center; border-top: 1px dashed #ddd; padding-top: 7px; color: #888; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ optional($restaurant)->name ?? 'Progga RMS' }}</h2>
        <p>{{ optional($restaurant)->address ?? 'Dhaka, Bangladesh' }} | Phone: {{ optional($restaurant)->phone ?? 'N/A' }}</p>
        <h3>Order Report — {{ $dateFilterLabel ?? now()->format('d M, Y') }}</h3>
    </div>

    <table>
        <thead>
            <tr>
                <th>Order #</th>
                <th>Customer</th>
                <th>Items</th>
                <th class="text-right">Subtotal</th>
                <th class="text-right">Discount</th>
                <th class="text-right">Service Charge</th>
                <th class="text-right">Tips</th>
                <th class="text-right">Given</th>
                <th class="text-right">Change</th>
                <th class="text-right">Grand Total</th>
                <th>Payment</th>
                <th class="text-center">Status</th>
                <th>Time</th>
                <th>KOT to Pay</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalSubtotal = 0;
                $totalRevenue = 0;
                $totalDiscount = 0;
                $totalServiceCharge = 0;
                $totalTips = 0;
                $totalGiven = 0;
                $totalChange = 0;
            @endphp
            @forelse($orders as $order)
                @php
                    $previewItems = $order->orderDetails->take(2)->pluck('product_name')->implode(', ');
                    $remaining = $order->orderDetails->count() - 2;

                    $discountAmount = max(0, (float)($order->discount_amount ?? 0));
                    $serviceCharge = max(0, (float)($order->service_charge ?? 0));
                    $tipsAmount = max(0, (float)($order->tips_amount ?? ((float)($order->total_paid_amount ?? 0) - (float)($order->grand_total ?? 0))));
                    $givenMoney = max(0, (float)($order->given_money ?? 0));
                    $changeAmount = max(0, (float)($order->change_amount ?? 0));

                    $orderType = strtolower((string) $order->order_type);
                    $tableText = in_array($orderType, ['takeaway', 'takeaway']) ? 'Takeaway' : 'Table T-' . (optional($order->table)->table_number ?? 'N/A');

                    if($order->status == 'Completed') {
                        $totalSubtotal += (float) $order->subtotal;
                        $totalRevenue += (float) $order->grand_total;
                    }
                    $totalDiscount += $discountAmount;
                    $totalServiceCharge += $serviceCharge;
                    $totalTips += $tipsAmount;
                    $totalGiven += $givenMoney;
                    $totalChange += $changeAmount;

                    // Split Payment Formatting
                    $paymentText = $order->payment_type ?? 'N/A';
                    if ($paymentText === 'Split') {
                        $splits = [];
                        if((float)$order->paid_in_cash > 0) $splits[] = 'Cash: ' . $order->paid_in_cash;
                        if((float)$order->paid_in_card > 0) $splits[] = 'Card: ' . $order->paid_in_card;
                        if((float)$order->paid_in_mfc > 0) $splits[] = 'MFS: ' . $order->paid_in_mfc;
                        $paymentText .= '<br><span class="muted">(' . implode(', ', $splits) . ')</span>';
                    }
                @endphp
                <tr>
                    <td><span class="strong">#{{ $order->order_number }}</span></td>
                    <td>
                        <span class="strong">{{ optional($order->customer)->name ?? 'Walk-in Customer' }}</span><br>
                        <span class="muted">{{ $tableText }}</span>
                    </td>
                    <td>
                        {{ $previewItems ?: '—' }}
                        @if($remaining > 0)
                            <br><span class="muted">+{{ $remaining }} more item(s)</span>
                        @endif
                    </td>
                    <td class="text-right"><span class="strong">{{ number_format($order->subtotal, 0) }}</span></td>
                    <td class="text-right text-danger">{{ number_format($discountAmount, 0) }}</td>
                    <td class="text-right">{{ number_format($serviceCharge, 0) }}</td>
                    <td class="text-right text-success">{{ number_format($tipsAmount, 0) }}</td>
                    <td class="text-right">{{ number_format($givenMoney, 0) }}</td>
                    <td class="text-right text-success">{{ number_format($changeAmount, 0) }}</td>
                    <td class="text-right"><span class="strong" style="color: #21352a;">{{ number_format($order->grand_total, 0) }}</span></td>
                    <td>{!! $paymentText !!}</td>
                    <td class="text-center"><span class="strong">{{ $order->status }}</span></td>
                    <td>{{ $order->created_at ? $order->created_at->format('d M Y, h:i A') : '—' }}</td>
                    <td>{{ is_null($order->kitchen_to_payment_minutes) ? '—' : $order->kitchen_to_payment_minutes . ' min' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="14" class="text-center">No orders found for the selected criteria.</td>
                </tr>
            @endforelse
        </tbody>
        @if($orders->count() > 0)
            <tfoot>
                <tr>
                    <th colspan="3" class="text-right">Total (Completed Orders):</th>
                    <th class="text-right">{{ number_format($totalSubtotal, 0) }}</th>
                    <th class="text-right">{{ number_format($totalDiscount, 0) }}</th>
                    <th class="text-right">{{ number_format($totalServiceCharge, 0) }}</th>
                    <th class="text-right">{{ number_format($totalTips, 0) }}</th>
                    <th class="text-right">{{ number_format($totalGiven, 0) }}</th>
                    <th class="text-right">{{ number_format($totalChange, 0) }}</th>
                    <th class="text-right">{{ number_format($totalRevenue, 0) }}</th>
                    <th colspan="4"></th>
                </tr>
            </tfoot>
        @endif
    </table>

    <div class="footer">
        Generated on: {{ now()->format('d M, Y h:i A') }} | System: Table Track RMS
    </div>
</body>
</html>
