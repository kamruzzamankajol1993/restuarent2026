<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Sales Report</title>
    <style>
        body { font-family: sans-serif; font-size: 13px; color: #333; }
        .header { text-align: center; margin-bottom: 25px; border-bottom: 2px solid #21352a; padding-bottom: 15px; }
        .header h2 { margin: 0 0 5px 0; color: #21352a; font-size: 26px; text-transform: uppercase; letter-spacing: 1px; }
        .header p { margin: 0; color: #666; font-size: 13px; }
        .report-title { margin-top: 15px; color: #444; font-size: 16px; font-weight: bold; }

        /* Summary Box */
        .summary-box { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
        .summary-box td { border: 1px solid #ddd; padding: 12px; text-align: center; width: 25%; background: #f9f9f9; }
        .summary-box .s-label { display: block; font-size: 11px; color: #666; text-transform: uppercase; margin-bottom: 5px; }
        .summary-box .s-value { display: block; font-size: 18px; font-weight: bold; color: #21352a; }

        /* Data Table */
        .table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table th, .table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        .table th { background-color: #21352a; color: #ffffff; font-size: 13px; font-weight: bold; }
        .table td { font-size: 12px; }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .footer { margin-top: 30px; font-size: 11px; text-align: center; border-top: 1px dashed #ddd; padding-top: 10px; color: #888; }

        .status-badge { font-weight: bold; }
        .status-completed { color: #1e7a4a; }
        .status-pending { color: #d5aa65; }
        .status-cancelled { color: #dc3545; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $restaurant->name ?? 'Progga RMS' }}</h2>
        <p>{{ $restaurant->address ?? '' }} | Phone: {{ $restaurant->phone ?? 'N/A' }}</p>
        <div class="report-title">Sales & Order Report</div>
        <p>Period: {{ $startDate->format('d M, Y') }} to {{ $endDate->format('d M, Y') }}</p>
    </div>

    @php
        $totalRev = $orders->where('status', 'Completed')->sum('grand_total');
        $totalOrd = $orders->count();
        $avgVal = $totalOrd > 0 ? ($totalRev / $totalOrd) : 0;
    @endphp
    <table class="summary-box">
        <tr>
            <td>
                <span class="s-label">Total Revenue</span>
                <span class="s-value">৳{{ number_format($totalRev, 2) }}</span>
            </td>
            <td>
                <span class="s-label">Total Orders</span>
                <span class="s-value">{{ $totalOrd }}</span>
            </td>
            <td>
                <span class="s-label">Avg. Order Value</span>
                <span class="s-value">৳{{ number_format($avgVal, 2) }}</span>
            </td>
        </tr>
    </table>

    <table class="table">
        <thead>
            <tr>
                <th class="text-center">Sl</th>
                <th>Order #</th>
                <th>Date & Time</th>
                <th>Customer</th>
                <th>Table</th>
                <th>Payment</th>
                <th class="text-right">Total (৳)</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $key => $order)
            <tr>
                <td class="text-center">{{ $key + 1 }}</td>
                <td><strong>#{{ $order->order_number }}</strong></td>
                <td>{{ $order->created_at->format('d/m/Y h:i A') }}</td>
                <td>{{ $order->customer->name ?? 'Walk-in' }}</td>
                <td>{{ $order->table->table_number ?? 'Takeaway' }}</td>
                <td>{{ $order->payment_type ?? 'N/A' }}</td>
                <td class="text-right">{{ number_format($order->grand_total, 2) }}</td>
                <td class="text-center status-badge status-{{ strtolower($order->status) }}">
                    {{ $order->status }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center" style="padding: 20px;">No orders found for the selected criteria.</td>
            </tr>
            @endforelse
        </tbody>
        @if($orders->count() > 0)
        <tfoot>
            <tr>
                <th colspan="6" class="text-right" style="background: #f1f1f1; color: #333;">Total Revenue (Completed):</th>
                <th class="text-right" style="background: #f1f1f1; color: #21352a; font-size: 14px;">৳{{ number_format($totalRev, 2) }}</th>
                <th style="background: #f1f1f1;"></th>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="footer">
        Generated on: {{ now()->format('d M, Y h:i A') }} | Tech Partner: Progga RMS
    </div>
</body>
</html>
