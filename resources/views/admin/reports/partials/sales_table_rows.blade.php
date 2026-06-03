@php $sumOrders = 0; $sumSales = 0; @endphp
@foreach($periodData as $row)
    @php $sumOrders += $row['total_orders']; $sumSales += $row['total_sales']; @endphp
    <tr>
        <td><strong>{{ $row['period'] }}</strong></td>
        <td>{{ number_format($row['total_orders']) }}</td>
        <td>৳{{ number_format($row['total_sales'], 2) }}</td>
    </tr>
@endforeach
<tr style="background-color: rgba(33, 53, 42, 0.05); font-weight: 800;">
    <td><span style="text-transform: uppercase;">Total</span></td>
    <td>{{ number_format($sumOrders) }}</td>
    <td>৳{{ number_format($sumSales, 2) }}</td>
</tr>
