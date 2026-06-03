@php
    $filterMethod = request('payment_method');
@endphp

@forelse($paymentOrders as $order)
@php
    // ফিল্টার অনুযায়ী ব্যাজ দেখানোর লজিক
    $showCash = $order->paid_in_cash > 0 && (!$filterMethod || $filterMethod == 'Cash');
    $showCard = $order->paid_in_card > 0 && (!$filterMethod || $filterMethod == 'Card');
    $showMfc = $order->paid_in_mfc > 0 && (!$filterMethod || $filterMethod == 'Mobile Banking');

    // ফিল্টার অনুযায়ী ওই স্পেসিফিক রো এর টোটাল পেইড এমাউন্ট দেখানোর লজিক
    if ($filterMethod == 'Cash') {
        $rowTotal = $order->paid_in_cash;
    } elseif ($filterMethod == 'Card') {
        $rowTotal = $order->paid_in_card;
    } elseif ($filterMethod == 'Mobile Banking') {
        $rowTotal = $order->paid_in_mfc;
    } else {
        $rowTotal = $order->total_paid_amount;
    }
@endphp
<tr>
    <td><strong>#{{ $order->order_number }}</strong></td>
    <td>{{ $order->created_at->format('d M, h:i A') }}</td>
    <td>{{ $order->customer->name ?? 'Walk-in' }}</td>
    <td>{{ $order->table->table_number ?? 'Takeaway' }}</td>
    <td>
        <div style="display: flex; gap: 4px; flex-wrap: wrap;">
            @if($showCash) <span class="progga-badge progga-badge-neutral">Cash</span> @endif
            @if($showCard) <span class="progga-badge progga-badge-neutral">Card</span> @endif
            @if($showMfc) <span class="progga-badge progga-badge-neutral">Mobile Banking</span> @endif
        </div>
    </td>
    <td>৳{{ number_format($order->paid_in_cash, 2) }}</td>
    <td>৳{{ number_format($order->paid_in_card, 2) }}</td>
    <td>৳{{ number_format($order->paid_in_mfc, 2) }}</td>
    <td class="text-primary"><strong>৳{{ number_format($rowTotal, 2) }}</strong></td>
</tr>
@empty
<tr><td colspan="9" class="text-center py-4 text-muted">No data found.</td></tr>
@endforelse
