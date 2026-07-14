<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Table;
use App\Models\OrderDetail;
use App\Support\OrderVisibility;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    private function dashboardChartPayload(string $period = '7', ?array $visibleOrderIds = null): array
    {
        $period = in_array($period, ['7', '30', '12m'], true) ? $period : '7';

        $chartLabels = [];
        $chartData = [];

        if ($period === '12m') {
            $startMonth = Carbon::now()->subMonths(11)->startOfMonth();
            $salesRows = OrderVisibility::constrain(Order::query(), $visibleOrderIds)
                ->where('status', 'Completed')
                ->whereBetween('created_at', [$startMonth, Carbon::now()->endOfMonth()])
                ->select(
                    DB::raw('YEAR(created_at) as sales_year'),
                    DB::raw('MONTH(created_at) as sales_month'),
                    DB::raw('SUM(grand_total) as total_revenue')
                )
                ->groupBy('sales_year', 'sales_month')
                ->get();

            for ($i = 0; $i < 12; $i++) {
                $date = $startMonth->copy()->addMonths($i);
                $row = $salesRows
                    ->where('sales_year', (int) $date->format('Y'))
                    ->where('sales_month', (int) $date->format('m'))
                    ->first();

                $chartLabels[] = $date->format('M y');
                $chartData[] = $row ? round((float) $row->total_revenue, 2) : 0;
            }
        } else {
            $days = (int) $period;
            $startDate = Carbon::today()->subDays($days - 1);
            $salesRows = OrderVisibility::constrain(Order::query(), $visibleOrderIds)
                ->where('status', 'Completed')
                ->whereBetween('created_at', [$startDate->copy()->startOfDay(), Carbon::today()->endOfDay()])
                ->select(DB::raw('DATE(created_at) as sales_date'), DB::raw('SUM(grand_total) as total_revenue'))
                ->groupBy('sales_date')
                ->get();

            for ($i = $days - 1; $i >= 0; $i--) {
                $date = Carbon::today()->subDays($i);
                $row = $salesRows->where('sales_date', $date->format('Y-m-d'))->first();

                $chartLabels[] = $days <= 7 ? $date->format('D') : $date->format('d M');
                $chartData[] = $row ? round((float) $row->total_revenue, 2) : 0;
            }
        }

        // Order Status chart data must come from the current month's orders.
        // Preferred statuses are shown first, and any custom DB status is also included.
        $orderStatuses = OrderVisibility::constrain(Order::query(), $visibleOrderIds)
            ->whereYear('created_at', Carbon::now()->year)
            ->whereMonth('created_at', Carbon::now()->month)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $preferredStatusLabels = ['Pending', 'Processing', 'Cooking', 'Ready', 'Completed', 'Delivered', 'Cancelled'];
        $extraStatusLabels = collect(array_keys($orderStatuses))
            ->filter(fn ($status) => $status && !in_array($status, $preferredStatusLabels, true))
            ->values()
            ->toArray();

        $statusLabels = collect(array_merge($preferredStatusLabels, $extraStatusLabels))
            ->filter(fn ($status) => (int) ($orderStatuses[$status] ?? 0) > 0)
            ->values()
            ->toArray();

        if (empty($statusLabels)) {
            $statusLabels = ['Pending', 'Cooking', 'Ready', 'Completed', 'Cancelled'];
        }

        $statusData = array_map(fn ($status) => (int) ($orderStatuses[$status] ?? 0), $statusLabels);

        $currentYear = Carbon::now()->year;
        $topItems = OrderVisibility::constrain(
            OrderDetail::query()->join('orders', 'order_details.order_id', '=', 'orders.id'),
            $visibleOrderIds
        )
            ->where('orders.status', 'Completed')
            ->whereYear('orders.created_at', $currentYear)
            ->select('order_details.product_name', DB::raw('SUM(order_details.quantity) as total_qty'))
            ->groupBy('order_details.product_name')
            ->orderByDesc('total_qty')
            ->take(5)
            ->get();

        return [
            'period' => $period,
            'chartLabels' => $chartLabels,
            'chartData' => $chartData,
            'statusLabels' => $statusLabels,
            'statusData' => $statusData,
            'topItemsLabels' => $topItems->pluck('product_name')->toArray(),
            'topItemsData' => $topItems->pluck('total_qty')->map(fn ($qty) => (int) $qty)->toArray(),
        ];
    }

    public function chartData(Request $request)
    {
        $visibleOrderIds = OrderVisibility::globalVisibleIds();

        return response()->json(
            $this->dashboardChartPayload((string) $request->get('period', '7'), $visibleOrderIds)
        );
    }

    public function index()
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $thisMonthStart = Carbon::now()->startOfMonth();
        $thisMonthEnd = Carbon::now()->endOfMonth();
        $lastMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();
        $visibleOrderIds = OrderVisibility::globalVisibleIds();

        $todaySales = OrderVisibility::constrain(Order::query(), $visibleOrderIds)
            ->whereDate('created_at', $today)
            ->where('status', 'Completed')
            ->sum('grand_total');
        $yesterdaySales = OrderVisibility::constrain(Order::query(), $visibleOrderIds)
            ->whereDate('created_at', $yesterday)
            ->where('status', 'Completed')
            ->sum('grand_total');
        $salesChange = $yesterdaySales > 0 ? (($todaySales - $yesterdaySales) / $yesterdaySales) * 100 : ($todaySales > 0 ? 100 : 0);

        // Monthly Revenue must be calculated from that month's own random-half dataset,
        // rather than from the global all-time sample. Today's orders remain fully visible.
        $thisMonthVisibleIds = null;
        $lastMonthVisibleIds = null;

        if (OrderVisibility::isRandomHalfEnabled()) {
            $thisMonthVisibleIds = OrderVisibility::visibleIds(
                Order::query()->whereBetween('orders.created_at', [$thisMonthStart, $thisMonthEnd]),
                [
                    'dashboard_metric' => 'monthly_revenue',
                    'period' => $thisMonthStart->format('Y-m'),
                ]
            );

            $lastMonthVisibleIds = OrderVisibility::visibleIds(
                Order::query()->whereBetween('orders.created_at', [$lastMonthStart, $lastMonthEnd]),
                [
                    'dashboard_metric' => 'monthly_revenue',
                    'period' => $lastMonthStart->format('Y-m'),
                ]
            );
        }

        $monthlySales = OrderVisibility::constrain(Order::query(), $thisMonthVisibleIds)
            ->whereBetween('created_at', [$thisMonthStart, $thisMonthEnd])
            ->where('status', 'Completed')
            ->sum('grand_total');
        $lastMonthSales = OrderVisibility::constrain(Order::query(), $lastMonthVisibleIds)
            ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
            ->where('status', 'Completed')
            ->sum('grand_total');
        $monthlyChange = $lastMonthSales > 0 ? (($monthlySales - $lastMonthSales) / $lastMonthSales) * 100 : ($monthlySales > 0 ? 100 : 0);

        $todayOrdersCount = OrderVisibility::constrain(Order::query(), $visibleOrderIds)
            ->whereDate('created_at', $today)
            ->count();
        $yesterdayOrdersCount = OrderVisibility::constrain(Order::query(), $visibleOrderIds)
            ->whereDate('created_at', $yesterday)
            ->count();
        $ordersChange = $todayOrdersCount - $yesterdayOrdersCount;

        $totalTables = Table::count();
        $runningTables = Table::whereHas('orders', function ($query) {
            $query->whereIn('status', ['Pending', 'Cooking']);
        })->count();
        $availableTables = $totalTables - $runningTables;

        $chartPayload = $this->dashboardChartPayload('7', $visibleOrderIds);
        extract($chartPayload);

        $kitchenQueue = Order::with(['table', 'orderDetails'])
            ->whereIn('status', ['Pending', 'Cooking', 'Ready'])
            ->orderBy('id', 'asc')
            ->limit(5)
            ->get();

        $recentOrders = Order::with(['table', 'waiter', 'orderDetails'])
            ->orderBy('id', 'desc')
            ->limit(6)
            ->get();

        return view('admin.dashboard.index', compact(
            'todaySales',
            'salesChange',
            'monthlySales',
            'monthlyChange',
            'todayOrdersCount',
            'ordersChange',
            'totalTables',
            'runningTables',
            'availableTables',
            'chartLabels',
            'chartData',
            'statusLabels',
            'statusData',
            'topItemsLabels',
            'topItemsData',
            'kitchenQueue',
            'recentOrders'
        ));
    }
}
