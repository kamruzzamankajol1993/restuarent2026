<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Order Report</title>
    <style>
        body { font-family: sans-serif; font-size: 13px; color: #333; }
        .header { text-align: center; margin-bottom: 25px; border-bottom: 2px solid #21352a; padding-bottom: 15px; }
        .header h2 { margin: 0 0 5px 0; color: #21352a; font-size: 24px; }
        .header p { margin: 0; color: #666; font-size: 12px; }

        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #21352a; color: #ffffff; font-size: 13px; font-weight: bold; }
        td { font-size: 12px; }

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
        <p>{{ $restaurant->address ?? 'Dhaka, Bangladesh' }} | Phone: {{ $restaurant->phone ?? 'N/A' }}</p>
        <h3 style="margin-top: 10px; color: #444;">Order Report — {{ now()->format('d M, Y') }}</h3>
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-center">Sl</th>
                <th>Order #</th>
                <th>Date & Time</th>
                <th>Customer</th>
                <th>Type</th>
                <th class="text-right">Total</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @php $totalRevenue = 0; @endphp
            @forelse($orders as $key => $order)
            <tr>
                <td class="text-center">{{ $key + 1 }}</td>
                <td><strong>#{{ $order->order_number }}</strong></td>
                <td>{{ $order->created_at->format('d/m/y h:i A') }}</td>
                <td>{{ $order->customer->name ?? 'Walk-in' }}</td>
                <td>{{ $order->order_type }}</td>
                <td class="text-right">{{ number_format($order->grand_total, 0) }}</td>
                <td class="text-center status-badge status-{{ strtolower($order->status) }}">
                    {{ $order->status }}
                </td>
            </tr>
            @php
                if($order->status == 'Completed') {
                    $totalRevenue += $order->grand_total;
                }
            @endphp
            @empty
            <tr>
                <td colspan="7" class="text-center">No orders found for the selected criteria.</td>
            </tr>
            @endforelse
        </tbody>
        @if($orders->count() > 0)
        <tfoot>
            <tr>
                <th colspan="5" class="text-right" style="background: #f8f9fa; color: #333;">Total Revenue (Completed Orders):</th>
                <th class="text-right" style="background: #f8f9fa; color: #21352a; font-size: 14px;">{{ number_format($totalRevenue, 0) }}</th>
                <th style="background: #f8f9fa;"></th>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="footer">
        Generated on: {{ now()->format('d M, Y h:i A') }} | System: Progga RMS
    </div>
</body>
</html>
