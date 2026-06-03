<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Table;
use App\Models\OrderDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $thisMonthStart = Carbon::now()->startOfMonth();
        $thisMonthEnd = Carbon::now()->endOfMonth();
        $lastMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        // ১. Today's Sales & vs Yesterday
        $todaySales = Order::whereDate('created_at', $today)->where('status', 'Completed')->sum('grand_total');
        $yesterdaySales = Order::whereDate('created_at', $yesterday)->where('status', 'Completed')->sum('grand_total');
        $salesChange = $yesterdaySales > 0 ? (($todaySales - $yesterdaySales) / $yesterdaySales) * 100 : ($todaySales > 0 ? 100 : 0);

        // ২. Monthly Revenue & vs Last Month
        $monthlySales = Order::whereBetween('created_at', [$thisMonthStart, $thisMonthEnd])->where('status', 'Completed')->sum('grand_total');
        $lastMonthSales = Order::whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->where('status', 'Completed')->sum('grand_total');
        $monthlyChange = $lastMonthSales > 0 ? (($monthlySales - $lastMonthSales) / $lastMonthSales) * 100 : ($monthlySales > 0 ? 100 : 0);

        // ৩. Total Orders (Today) & vs Yesterday
        $todayOrdersCount = Order::whereDate('created_at', $today)->count();
        $yesterdayOrdersCount = Order::whereDate('created_at', $yesterday)->count();
        $ordersChange = $todayOrdersCount - $yesterdayOrdersCount;

        // ৪. Running Tables
        $totalTables = Table::count();
        $runningTables = Table::whereHas('orders', function($q) {
            $q->whereIn('status', ['Pending', 'Cooking']);
        })->count();
        $availableTables = $totalTables - $runningTables;

        // ৫. Revenue Chart Data (Last 7 Days)
        $chartLabels = [];
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $chartLabels[] = $date->format('D'); // Mon, Tue, etc.
            $chartData[] = Order::whereDate('created_at', $date)->where('status', 'Completed')->sum('grand_total');
        }

        // ৬. Order Status Donut Chart (This Month)
        $orderStatuses = Order::whereMonth('created_at', Carbon::now()->month)
                            ->select('status', DB::raw('count(*) as total'))
                            ->groupBy('status')
                            ->pluck('total', 'status')->toArray();

        $statusLabels = ['Pending', 'Cooking', 'Ready', 'Completed', 'Cancelled'];
        $statusData = [
            $orderStatuses['Pending'] ?? 0,
            $orderStatuses['Cooking'] ?? 0,
            $orderStatuses['Ready'] ?? 0,
            $orderStatuses['Completed'] ?? 0,
            $orderStatuses['Cancelled'] ?? 0,
        ];

        // ৭. Top Selling Items Chart (This Year)
        // Food Wise Sales report-এর একই logic: only Completed orders + current year + qty sum.
   // ৭. Top Selling Items Chart (This Year) - ফুড রিপোর্ট পেজের হুবহু একই কুয়েরি লজিক
        // ৭. Top Selling Items Chart (This Year) - ফুড রিপোর্টের হুবহু র-লজিক ফিক্স
        $currentYear = \Carbon\Carbon::now()->year;

        $foodRowsFromReport = OrderDetail::query()
            ->join('orders', 'order_details.order_id', '=', 'orders.id')
            ->where('orders.status', 'Completed')
            ->whereYear('orders.created_at', $currentYear) // ফুড রিপোর্টের মত ডিরেক্ট Year চেক
            ->select(
                'order_details.product_name',
                DB::raw('SUM(order_details.quantity) as total_qty')
            )
            ->groupBy('order_details.product_name')
            ->orderByRaw('SUM(order_details.quantity) DESC') // র-অর্ডার ডিসেন্ডিং
            ->take(5)
            ->get();

        $topItemsLabels = $foodRowsFromReport->pluck('product_name')->toArray();
        $topItemsData = $foodRowsFromReport->pluck('total_qty')->map(function ($qty) {
            return (int) $qty;
        })->toArray();

        //dd($topItemsLabels, $topItemsData);


//dd(1);
        // ৮. Kitchen Queue (Pending / Cooking / Ready)
        $kitchenQueue = Order::with(['table', 'orderDetails'])
            ->whereIn('status', ['Pending', 'Cooking', 'Ready'])
            ->orderBy('id', 'asc')
            ->limit(5)
            ->get();

        // ৯. Recent Orders
        $recentOrders = Order::with(['table', 'waiter'])
            ->orderBy('id', 'desc')
            ->limit(6)
            ->get();

        return view('admin.dashboard.index', compact(
            'todaySales', 'salesChange',
            'monthlySales', 'monthlyChange',
            'todayOrdersCount', 'ordersChange',
            'totalTables', 'runningTables', 'availableTables',
            'chartLabels', 'chartData',
            'statusLabels', 'statusData',
            'topItemsLabels', 'topItemsData',
            'kitchenQueue', 'recentOrders'
        ));
    }
}
