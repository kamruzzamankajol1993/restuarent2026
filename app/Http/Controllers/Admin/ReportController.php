<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Customer;
use App\Models\RestaurantSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Mpdf\Mpdf;

class ReportController extends Controller
{
    /**
     * Report Index Page: Summary, Charts and Paginated Table
     */
    public function index(Request $request)
    {
        // ডিফল্ট ডেট রেঞ্জ: আজ থেকে গত ৭ দিন
        $startDate = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : Carbon::today()->subDays(6)->startOfDay();
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : Carbon::today()->endOfDay();
        $paymentMethod = $request->payment_method;

        // ====================================================
        // ১. Summary Stats Calculation
        // ====================================================

        // টোটাল রেভিনিউ (শুধুমাত্র Completed অর্ডার)
        $totalRevenueQuery = Order::where('status', 'Completed')->whereBetween('created_at', [$startDate, $endDate]);
        if ($paymentMethod) $totalRevenueQuery->where('payment_type', $paymentMethod);
        $totalRevenue = $totalRevenueQuery->sum('grand_total');

        // টোটাল অর্ডার
        $totalOrdersQuery = Order::whereBetween('created_at', [$startDate, $endDate]);
        if ($paymentMethod) $totalOrdersQuery->where('payment_type', $paymentMethod);
        $totalOrders = $totalOrdersQuery->count();

        // এভারেজ অর্ডার ভ্যালু (AOV)
        $avgOrderValue = $totalOrders > 0 ? ($totalRevenue / $totalOrders) : 0;

        // ইউনিক কাস্টমার (যারা এই সময়ের মধ্যে অর্ডার করেছে)
        $uniqueCustomers = Order::whereBetween('created_at', [$startDate, $endDate])
                            ->whereNotNull('customer_id')
                            ->distinct('customer_id')
                            ->count('customer_id');

        // ====================================================
        // ২. Chart Data: Daily Sales (For selected date range)
        // ====================================================
        $dailySales = Order::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(grand_total) as total')
            )
            ->where('status', 'Completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        $chartLabelsDaily = [];
        $chartDataDaily = [];

        // ডেট রেঞ্জের সব দিনের ডাটা জেনারেট করা (যাতে কোনো দিন গ্যাপ না থাকে)
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dateString = $date->format('Y-m-d');
            $chartLabelsDaily[] = $date->format('d M');

            $saleInfo = $dailySales->firstWhere('date', $dateString);
            $chartDataDaily[] = $saleInfo ? $saleInfo->total : 0;
        }

        // ====================================================
        // ৩. Chart Data: Monthly Sales (For Current Year)
        // ====================================================
        $currentYear = Carbon::now()->year;
        $monthlySales = Order::select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('SUM(grand_total) as total')
            )
            ->where('status', 'Completed')
            ->whereYear('created_at', $currentYear)
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();

        $chartLabelsMonthly = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $chartDataMonthly = array_fill(0, 12, 0); // ১২ মাসের জন্য 0 সেট করা

        foreach ($monthlySales as $sale) {
            $chartDataMonthly[$sale->month - 1] = $sale->total; // Array index 0 থেকে শুরু হয়
        }

        // ====================================================
        // ৪. Orders Table Data (Pagination)
        // ====================================================
        $ordersQuery = Order::with(['customer', 'waiter', 'table', 'orderDetails'])
                        ->whereBetween('created_at', [$startDate, $endDate]);

        if ($paymentMethod) {
            $ordersQuery->where('payment_type', $paymentMethod);
        }

        $orders = $ordersQuery->orderBy('id', 'desc')->paginate(15);

        return view('admin.reports.index', compact(
            'startDate', 'endDate', 'paymentMethod',
            'totalRevenue', 'totalOrders', 'avgOrderValue', 'uniqueCustomers',
            'chartLabelsDaily', 'chartDataDaily',
            'chartLabelsMonthly', 'chartDataMonthly',
            'orders'
        ));
    }

    /**
     * Export Filtered Report to PDF using mPDF
     */
    public function exportPdf(Request $request)
    {
        $startDate = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : Carbon::today()->subDays(6)->startOfDay();
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : Carbon::today()->endOfDay();
        $paymentMethod = $request->payment_method;

        $query = Order::with(['customer', 'table', 'orderDetails'])
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($paymentMethod) {
            $query->where('payment_type', $paymentMethod);
        }

        $orders = $query->orderBy('id', 'desc')->get();
        $restaurant = RestaurantSetting::first();

        // PDF View Load (ভিউটি পরবর্তীতে দেওয়া হবে)
        $html = view('admin.reports.pdf_export', compact('orders', 'restaurant', 'startDate', 'endDate'))->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'L', // Landscape for wide tables
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 16,
            'margin_bottom' => 16,
            'margin_header' => 9,
            'margin_footer' => 9
        ]);

        $mpdf->SetTitle('Sales Report - ' . now()->format('d M, Y'));
        $mpdf->WriteHTML($html);

        $fileName = 'Sales_Report_' . now()->format('d_M_Y') . '.pdf';

        // নতুন ট্যাবে (inline) ওপেন করার জন্য 'S' ব্যবহার করা হয়েছে
        $pdfContent = $mpdf->Output($fileName, 'S');

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"'
        ]);
    }

    /**
     * Export Filtered Report to CSV
     */
    public function exportCsv(Request $request)
    {
        $startDate = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : Carbon::today()->subDays(6)->startOfDay();
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : Carbon::today()->endOfDay();
        $paymentMethod = $request->payment_method;

        $query = Order::with(['customer', 'table', 'orderDetails'])
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($paymentMethod) {
            $query->where('payment_type', $paymentMethod);
        }

        $orders = $query->orderBy('id', 'desc')->get();
        $fileName = 'Sales_Report_' . now()->format('Y_m_d') . '.csv';

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Order #', 'Date & Time', 'Table', 'Customer', 'Items Count', 'Payment Type', 'Total (BDT)', 'Status'];

        $callback = function() use($orders, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($orders as $order) {
                $row['Order #']  = '#' . $order->order_number;
                $row['Date & Time']    = $order->created_at->format('d/m/Y h:i A');
                $row['Table']    = $order->table ? $order->table->table_number : 'Takeaway';
                $row['Customer']  = $order->customer ? $order->customer->name : 'Walk-in';
                $row['Items Count'] = $order->orderDetails->sum('quantity');
                $row['Payment Type']  = $order->payment_type ?? 'N/A';
                $row['Total']  = $order->grand_total;
                $row['Status']  = $order->status;

                fputcsv($file, array($row['Order #'], $row['Date & Time'], $row['Table'], $row['Customer'], $row['Items Count'], $row['Payment Type'], $row['Total'], $row['Status']));
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
