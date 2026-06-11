<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\RestaurantSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

class ReportController extends Controller
{
    private function resolveReportFilters(Request $request): array
    {
        $currentYear = Carbon::now()->year;
        $filterType = $request->filter_type ?: 'year';
        $year = (int) ($request->year ?: $currentYear);
        $month = (int) ($request->month ?: Carbon::now()->month);

        if ($filterType === 'date' && $request->start_date && $request->end_date) {
            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = Carbon::parse($request->end_date)->endOfDay();
        } elseif ($filterType === 'month') {
            $startDate = Carbon::create($year, $month, 1)->startOfMonth()->startOfDay();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();
        } else {
            $filterType = 'year';
            $startDate = Carbon::create($year, 1, 1)->startOfYear()->startOfDay();
            $endDate = Carbon::create($year, 12, 31)->endOfYear()->endOfDay();
        }

        return [
            'filterType' => $filterType,
            'year' => $year,
            'month' => $month,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'paymentMethod' => $request->payment_method,
            'yearOptions' => range($currentYear + 1, $currentYear - 10),
        ];
    }

    private function applyPaymentCollectionFilter($query, ?string $paymentMethod)
    {
        if (!$paymentMethod) return $query;
        return match ($paymentMethod) {
            'Cash' => $query->where('paid_in_cash', '>', 0),
            'Card' => $query->where('paid_in_card', '>', 0),
            'Mobile Banking' => $query->where('paid_in_mfc', '>', 0),
            'Split' => $query->where('payment_type', 'Split'),
            default => $query->where('payment_type', $paymentMethod),
        };
    }

    /** ১. ওভারভিউ: সেলস ও অর্ডার রিপোর্ট (গ্রাফ ছাড়া পিরিয়ডিক টেবিল) */
    public function salesOrder(Request $request)
    {
        $filters = $this->resolveReportFilters($request);
        extract($filters);

        $query = Order::where('status', 'Completed')->whereBetween('created_at', [$startDate, $endDate]);

        // টোটাল সামারি কার্ডের জন্য ডেটা
        $totalRevenue = (clone $query)->sum('grand_total');
        $totalDiscount = (clone $query)->sum('discount_amount');
        $totalOrders = (clone $query)->count();
        $avgOrderValue = $totalOrders > 0 ? ($totalRevenue / $totalOrders) : 0;
        $uniqueCustomers = (clone $query)->whereNotNull('customer_id')->distinct('customer_id')->count('customer_id');

        // পিরিয়ড কলাম এবং ডেটা প্রিপারেশন
        $periodData = [];
        if ($filterType === 'year') {
            // মাস ভিত্তিক গ্রুপ (Jan 2026, Feb 2026)
            $sales = (clone $query)
                ->select(DB::raw('MONTH(created_at) as m'), DB::raw('YEAR(created_at) as y'), DB::raw('SUM(grand_total) as total_sales'), DB::raw('SUM(discount_amount) as total_discount'), DB::raw('COUNT(id) as total_orders'))
                ->groupBy('y', 'm')
                ->get();

            for ($m = 1; $m <= 12; $m++) {
                $carbonObj = Carbon::create($year, $m, 1);
                $row = $sales->where('m', $m)->first();
                $periodData[] = [
                    'period' => $carbonObj->format('M Y'),
                    'total_orders' => $row ? $row->total_orders : 0,
                    'total_sales' => $row ? (float)$row->total_sales : 0,
                    'total_discount' => $row ? (float)$row->total_discount : 0
                ];
            }
        } else {
            // মাস বা নির্দিষ্ট ডেট রেঞ্জের জন্য দিন ভিত্তিক গ্রুপ (01/12/2025)
            $sales = (clone $query)
                ->select(DB::raw('DATE(created_at) as d'), DB::raw('SUM(grand_total) as total_sales'), DB::raw('SUM(discount_amount) as total_discount'), DB::raw('COUNT(id) as total_orders'))
                ->groupBy('d')
                ->get();

            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                $dateStr = $date->format('Y-m-d');
                $row = $sales->where('d', $dateStr)->first();
                $periodData[] = [
                    'period' => $date->format('d/m/Y'),
                    'total_orders' => $row ? $row->total_orders : 0,
                    'total_sales' => $row ? (float)$row->total_sales : 0,
                    'total_discount' => $row ? (float)$row->total_discount : 0
                ];
            }
        }

        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.reports.partials.sales_table_rows', compact('periodData', 'totalRevenue', 'totalDiscount', 'totalOrders'))->render(),
                'summary' => [
                    'revenue' => '৳' . number_format($totalRevenue, 0),
                    'discount' => '৳' . number_format($totalDiscount, 0),
                    'orders' => $totalOrders,
                    'avg' => '৳' . number_format($avgOrderValue, 0),
                    'customers' => $uniqueCustomers
                ]
            ]);
        }

        return view('admin.reports.sales_order', compact(
            'filterType', 'year', 'month', 'startDate', 'endDate', 'yearOptions',
            'totalRevenue', 'totalDiscount', 'totalOrders', 'avgOrderValue', 'uniqueCustomers', 'periodData'
        ));
    }

    public function index(Request $request)
    {
        return $this->salesOrder($request);
    }

    /** ২. পেমেন্ট টাইপ ওয়াইজ রিপোর্ট */
    public function paymentTypeSales(Request $request)
    {
        $filters = $this->resolveReportFilters($request);
        extract($filters);

        $ordersQuery = Order::where('status', 'Completed')->whereBetween('created_at', [$startDate, $endDate]);
        if ($paymentMethod) {
            $this->applyPaymentCollectionFilter($ordersQuery, $paymentMethod);
        }

        $orders = $ordersQuery->get();

        // এখানে শুধু ফিল্টার করা মেথডের টাকাই ক্যালকুলেট হবে
        $cashAmount = (!$paymentMethod || $paymentMethod == 'Cash') ? (float) $orders->sum('paid_in_cash') : 0;
        $cardAmount = (!$paymentMethod || $paymentMethod == 'Card') ? (float) $orders->sum('paid_in_card') : 0;
        $mfcAmount = (!$paymentMethod || $paymentMethod == 'Mobile Banking') ? (float) $orders->sum('paid_in_mfc') : 0;

        $totalCollected = $cashAmount + $cardAmount + $mfcAmount;

        // যেই মেথড দিয়ে সার্চ করা হবে, উপরের কার্ডে শুধু সেটাই দেখাবে
        $paymentRows = [];
        if (!$paymentMethod || $paymentMethod == 'Cash') {
            $paymentRows[] = ['label' => 'Cash', 'icon' => 'bi-cash-coin', 'amount' => $cashAmount, 'orders_count' => $orders->filter(fn($o) => $o->paid_in_cash > 0)->count(), 'percentage' => $totalCollected > 0 ? ($cashAmount / $totalCollected) * 100 : 0];
        }
        if (!$paymentMethod || $paymentMethod == 'Card') {
            $paymentRows[] = ['label' => 'Card', 'icon' => 'bi-credit-card', 'amount' => $cardAmount, 'orders_count' => $orders->filter(fn($o) => $o->paid_in_card > 0)->count(), 'percentage' => $totalCollected > 0 ? ($cardAmount / $totalCollected) * 100 : 0];
        }
        if (!$paymentMethod || $paymentMethod == 'Mobile Banking') {
            $paymentRows[] = ['label' => 'Mobile Banking / MFC', 'icon' => 'bi-phone', 'amount' => $mfcAmount, 'orders_count' => $orders->filter(fn($o) => $o->paid_in_mfc > 0)->count(), 'percentage' => $totalCollected > 0 ? ($mfcAmount / $totalCollected) * 100 : 0];
        }

        $paymentOrders = Order::with(['customer', 'table'])
            ->where('status', 'Completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when($paymentMethod, fn($q) => $this->applyPaymentCollectionFilter($q, $paymentMethod))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->appends($request->query());

        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.reports.partials.payment_table_rows', compact('paymentOrders'))->render(),
                'cards' => view('admin.reports.partials.payment_cards', compact('paymentRows', 'totalCollected'))->render(),
                'pagination' => view('admin.reports.partials.custom_pagination', ['paginator' => $paymentOrders])->render()
            ]);
        }

        return view('admin.reports.payment_type_sales', compact(
            'filterType', 'year', 'month', 'startDate', 'endDate', 'paymentMethod', 'yearOptions',
            'paymentRows', 'totalCollected', 'paymentOrders'
        ));
    }

    /** ৩. ফুড ওয়াইজ সেলস রিপোর্ট */
    public function foodSales(Request $request)
    {
        $filters = $this->resolveReportFilters($request);
        extract($filters);

        $foodRows = OrderDetail::query()
            ->join('orders', 'order_details.order_id', '=', 'orders.id')
            ->where('orders.status', 'Completed')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->select('order_details.product_id', 'order_details.product_name', DB::raw('SUM(order_details.quantity) as total_qty'), DB::raw('COUNT(DISTINCT order_details.order_id) as orders_count'), DB::raw('SUM(order_details.subtotal) as total_sales'))
            ->groupBy('order_details.product_id', 'order_details.product_name')
            ->orderByDesc('total_qty')
            ->paginate(20)
            ->appends($request->query());

        $totalFoodQty = OrderDetail::query()->join('orders', 'order_details.order_id', '=', 'orders.id')->where('orders.status', 'Completed')->whereBetween('orders.created_at', [$startDate, $endDate])->sum('order_details.quantity');
        $totalFoodSales = OrderDetail::query()->join('orders', 'order_details.order_id', '=', 'orders.id')->where('orders.status', 'Completed')->whereBetween('orders.created_at', [$startDate, $endDate])->sum('order_details.subtotal');

        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.reports.partials.food_table_rows', compact('foodRows'))->render(),
                'qty' => number_format($totalFoodQty),
                'sales' => '৳' . number_format($totalFoodSales, 2),
                'pagination' => view('admin.reports.partials.custom_pagination', ['paginator' => $foodRows])->render()
            ]);
        }

        return view('admin.reports.food_sales', compact(
            'filterType', 'year', 'month', 'startDate', 'endDate', 'yearOptions',
            'foodRows', 'totalFoodQty', 'totalFoodSales'
        ));
    }

    private function salesOrderExportRows(string $filterType, int $year, Carbon $startDate, Carbon $endDate)
    {
        $query = Order::where('status', 'Completed')->whereBetween('created_at', [$startDate, $endDate]);
        $periodRows = [];

        if ($filterType === 'year') {
            $sales = (clone $query)
                ->select(DB::raw('MONTH(created_at) as m'), DB::raw('YEAR(created_at) as y'), DB::raw('SUM(grand_total) as total_sale'), DB::raw('SUM(discount_amount) as total_discount'), DB::raw('COUNT(id) as total_order'))
                ->groupBy('y', 'm')
                ->get();

            for ($m = 1; $m <= 12; $m++) {
                $carbonObj = Carbon::create($year, $m, 1);
                $row = $sales->where('m', $m)->first();
                $periodRows[] = [
                    'period' => $carbonObj->format('M Y'),
                    'total_sale' => $row ? (float) $row->total_sale : 0,
                    'total_discount' => $row ? (float) $row->total_discount : 0,
                    'total_order' => $row ? (int) $row->total_order : 0,
                ];
            }
        } else {
            $sales = (clone $query)
                ->select(DB::raw('DATE(created_at) as d'), DB::raw('SUM(grand_total) as total_sale'), DB::raw('SUM(discount_amount) as total_discount'), DB::raw('COUNT(id) as total_order'))
                ->groupBy('d')
                ->get();

            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                $dateStr = $date->format('Y-m-d');
                $row = $sales->where('d', $dateStr)->first();
                $periodRows[] = [
                    'period' => $date->format('d/m/Y'),
                    'total_sale' => $row ? (float) $row->total_sale : 0,
                    'total_discount' => $row ? (float) $row->total_discount : 0,
                    'total_order' => $row ? (int) $row->total_order : 0,
                ];
            }
        }

        return collect($periodRows);
    }

    private function paymentExportRows(Carbon $startDate, Carbon $endDate, ?string $paymentMethod)
    {
        $ordersQuery = Order::where('status', 'Completed')->whereBetween('created_at', [$startDate, $endDate]);
        if ($paymentMethod) {
            $this->applyPaymentCollectionFilter($ordersQuery, $paymentMethod);
        }

        $orders = $ordersQuery->get();

        $cashAmount = (!$paymentMethod || $paymentMethod == 'Cash') ? (float) $orders->sum('paid_in_cash') : 0;
        $cardAmount = (!$paymentMethod || $paymentMethod == 'Card') ? (float) $orders->sum('paid_in_card') : 0;
        $mfcAmount = (!$paymentMethod || $paymentMethod == 'Mobile Banking') ? (float) $orders->sum('paid_in_mfc') : 0;

        $totalCollected = $cashAmount + $cardAmount + $mfcAmount;

        $rows = [];
        if (!$paymentMethod || $paymentMethod == 'Cash') {
            $rows[] = ['label' => 'Cash', 'amount' => $cashAmount, 'orders_count' => $orders->filter(fn($o) => $o->paid_in_cash > 0)->count(), 'percentage' => $totalCollected > 0 ? ($cashAmount / $totalCollected) * 100 : 0];
        }
        if (!$paymentMethod || $paymentMethod == 'Card') {
            $rows[] = ['label' => 'Card', 'amount' => $cardAmount, 'orders_count' => $orders->filter(fn($o) => $o->paid_in_card > 0)->count(), 'percentage' => $totalCollected > 0 ? ($cardAmount / $totalCollected) * 100 : 0];
        }
        if (!$paymentMethod || $paymentMethod == 'Mobile Banking') {
            $rows[] = ['label' => 'Mobile Banking / MFC', 'amount' => $mfcAmount, 'orders_count' => $orders->filter(fn($o) => $o->paid_in_mfc > 0)->count(), 'percentage' => $totalCollected > 0 ? ($mfcAmount / $totalCollected) * 100 : 0];
        }

        return collect($rows);
    }

    private function foodExportRows(Carbon $startDate, Carbon $endDate)
    {
        return OrderDetail::query()
            ->join('orders', 'order_details.order_id', '=', 'orders.id')
            ->where('orders.status', 'Completed')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->select('order_details.product_id', 'order_details.product_name', DB::raw('SUM(order_details.quantity) as total_qty'), DB::raw('COUNT(DISTINCT order_details.order_id) as orders_count'), DB::raw('SUM(order_details.subtotal) as total_sales'))
            ->groupBy('order_details.product_id', 'order_details.product_name')
            ->orderByDesc('total_qty')
            ->get();
    }

    private function reportFileName(string $report, string $extension): string
    {
        $name = match ($report) {
            'payment_type_sales' => 'payment-type-wise-sales',
            'food_sales' => 'food-wise-sales',
            default => 'sales-order-report',
        };

        return $name . '-' . now()->format('Y-m-d-His') . '.' . $extension;
    }

    private function exportViewData(Request $request): array
    {
        $filters = $this->resolveReportFilters($request);
        extract($filters);

        $report = $request->get('report', 'sales_order');
        if (!in_array($report, ['sales_order', 'payment_type_sales', 'food_sales'], true)) {
            $report = 'sales_order';
        }

        $periodTotalSale = 0;
        $periodTotalDiscount = 0;
        $periodTotalOrder = 0;

        if ($report === 'payment_type_sales') {
            $dataRows = $this->paymentExportRows($startDate, $endDate, $paymentMethod);
        } elseif ($report === 'food_sales') {
            $dataRows = $this->foodExportRows($startDate, $endDate);
        } else {
            $dataRows = $this->salesOrderExportRows($filterType, $year, $startDate, $endDate);
            $periodTotalSale = $dataRows->sum('total_sale');
            $periodTotalDiscount = $dataRows->sum('total_discount');
            $periodTotalOrder = $dataRows->sum('total_order');
        }

        return compact('report', 'dataRows', 'startDate', 'endDate', 'periodTotalSale', 'periodTotalDiscount', 'periodTotalOrder') + [
            'restaurant' => RestaurantSetting::first(),
        ];
    }

    public function exportPdf(Request $request)
    {
        $viewData = $this->exportViewData($request);
        $html = view('admin.reports.pdf_export', $viewData)->render();
        $fileName = $this->reportFileName($viewData['report'], 'pdf');
        $tempDir = storage_path('app/mpdf-temp');

        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0775, true);
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'margin_left' => 8,
            'margin_right' => 8,
            'margin_top' => 10,
            'margin_bottom' => 10,
            'tempDir' => $tempDir,
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
        ]);

        $mpdf->SetTitle($fileName);
        $mpdf->WriteHTML($html);

        return response($mpdf->Output($fileName, Destination::STRING_RETURN), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function exportExcel(Request $request)
    {
        $viewData = $this->exportViewData($request);
        $html = view('admin.reports.pdf_export', $viewData)->render();

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $this->reportFileName($viewData['report'], 'xls') . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function exportCsv(Request $request)
    {
        return $this->exportExcel($request);
    }

}
