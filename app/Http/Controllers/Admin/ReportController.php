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
            'Cash' => $query->where(function ($q) {
                $q->where('paid_in_cash', '>', 0)
                  ->orWhere(function ($nested) {
                      $nested->where('payment_type', 'Cash')
                             ->where('total_paid_amount', '>', 0);
                  });
            }),
            'Card' => $query->where(function ($q) {
                $q->where('paid_in_card', '>', 0)
                  ->orWhere(function ($nested) {
                      $nested->where('payment_type', 'Card')
                             ->where('total_paid_amount', '>', 0);
                  });
            }),
            'Mobile Banking' => $query->where(function ($q) {
                $q->where('paid_in_mfc', '>', 0)
                  ->orWhere(function ($nested) {
                      $nested->where('payment_type', 'Mobile Banking')
                             ->where('total_paid_amount', '>', 0);
                  });
            }),
            'Split' => $query->where('payment_type', 'Split'),
            default => $query->where('payment_type', $paymentMethod),
        };
    }

    /** ১. সেলস ও অর্ডার রিপোর্ট — completed order details with custom pagination. */
    public function salesOrder(Request $request)
    {
        $filters = $this->resolveReportFilters($request);
        extract($filters);

        // Same filtered completed order query will drive summary cards, table, AJAX and export filters.
        $baseQuery = Order::with(['customer', 'table', 'orderDetails'])
            ->where('status', 'Completed')
            ->whereBetween('created_at', [$startDate, $endDate]);

        // Summary cards.
        $totalRevenue = (clone $baseQuery)->sum('grand_total');
        $totalDiscount = (clone $baseQuery)->sum('discount_amount');
        $totalOrders = (clone $baseQuery)->count();
        $avgOrderValue = $totalOrders > 0 ? ($totalRevenue / $totalOrders) : 0;
        $uniqueCustomers = (clone $baseQuery)
            ->whereNotNull('customer_id')
            ->distinct('customer_id')
            ->count('customer_id');

        // Detailed order rows used by resources/views/admin/reports/partials/sales_table_rows.blade.php.
        $orders = (clone $baseQuery)
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->appends($request->query());

        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.reports.partials.sales_table_rows', compact('orders'))->render(),
                'pagination' => view('admin.reports.partials.custom_pagination', ['paginator' => $orders])->render(),
                'summary' => [
                    'revenue' => '৳' . number_format($totalRevenue, 0),
                    'discount' => '৳' . number_format($totalDiscount, 0),
                    'orders' => $totalOrders,
                    'avg' => '৳' . number_format($avgOrderValue, 0),
                    'customers' => $uniqueCustomers,
                ],
            ]);
        }

        return view('admin.reports.sales_order', compact(
            'filterType', 'year', 'month', 'startDate', 'endDate', 'yearOptions',
            'totalRevenue', 'totalDiscount', 'totalOrders', 'avgOrderValue', 'uniqueCustomers', 'orders'
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

        // Legacy-safe collection breakdown. New payments use paid_in_* columns; old payments may have only total_paid_amount.
        $cashAmount = 0;
        $cardAmount = 0;
        $mfcAmount = 0;
        $cashOrders = 0;
        $cardOrders = 0;
        $mfcOrders = 0;

        foreach ($orders as $order) {
            $cash = (float) ($order->paid_in_cash ?? 0);
            $card = (float) ($order->paid_in_card ?? 0);
            $mfc = (float) ($order->paid_in_mfc ?? 0);

            if (($cash + $card + $mfc) <= 0 && (float) ($order->total_paid_amount ?? 0) > 0) {
                if ($order->payment_type === 'Cash') {
                    $cash = (float) $order->total_paid_amount;
                } elseif ($order->payment_type === 'Card') {
                    $card = (float) $order->total_paid_amount;
                } elseif ($order->payment_type === 'Mobile Banking') {
                    $mfc = (float) $order->total_paid_amount;
                }
            }

            $cashAmount += (!$paymentMethod || $paymentMethod === 'Cash') ? $cash : 0;
            $cardAmount += (!$paymentMethod || $paymentMethod === 'Card') ? $card : 0;
            $mfcAmount += (!$paymentMethod || $paymentMethod === 'Mobile Banking') ? $mfc : 0;

            if ($cash > 0 && (!$paymentMethod || $paymentMethod === 'Cash')) $cashOrders++;
            if ($card > 0 && (!$paymentMethod || $paymentMethod === 'Card')) $cardOrders++;
            if ($mfc > 0 && (!$paymentMethod || $paymentMethod === 'Mobile Banking')) $mfcOrders++;
        }

        $totalCollected = $cashAmount + $cardAmount + $mfcAmount;

        $paymentRows = [];
        if (!$paymentMethod || $paymentMethod == 'Cash') {
            $paymentRows[] = ['label' => 'Cash', 'icon' => 'bi-cash-coin', 'amount' => $cashAmount, 'orders_count' => $cashOrders, 'percentage' => $totalCollected > 0 ? ($cashAmount / $totalCollected) * 100 : 0];
        }
        if (!$paymentMethod || $paymentMethod == 'Card') {
            $paymentRows[] = ['label' => 'Card', 'icon' => 'bi-credit-card', 'amount' => $cardAmount, 'orders_count' => $cardOrders, 'percentage' => $totalCollected > 0 ? ($cardAmount / $totalCollected) * 100 : 0];
        }
        if (!$paymentMethod || $paymentMethod == 'Mobile Banking') {
            $paymentRows[] = ['label' => 'Mobile Banking / MFC', 'icon' => 'bi-phone', 'amount' => $mfcAmount, 'orders_count' => $mfcOrders, 'percentage' => $totalCollected > 0 ? ($mfcAmount / $totalCollected) * 100 : 0];
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
        $ordersQuery = Order::with(['customer', 'table'])
            ->where('status', 'Completed')
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($paymentMethod) {
            $this->applyPaymentCollectionFilter($ordersQuery, $paymentMethod);
        }

        return $ordersQuery->orderBy('id', 'desc')->get()->map(function ($order) use ($paymentMethod) {
            $cashAmount = (float) ($order->paid_in_cash ?? 0);
            $cardAmount = (float) ($order->paid_in_card ?? 0);
            $mfcAmount = (float) ($order->paid_in_mfc ?? 0);

            // Legacy order fallback: old records may have only payment_type + total_paid_amount.
            if (($cashAmount + $cardAmount + $mfcAmount) <= 0 && (float) ($order->total_paid_amount ?? 0) > 0) {
                if ($order->payment_type === 'Cash') {
                    $cashAmount = (float) $order->total_paid_amount;
                } elseif ($order->payment_type === 'Card') {
                    $cardAmount = (float) $order->total_paid_amount;
                } elseif ($order->payment_type === 'Mobile Banking') {
                    $mfcAmount = (float) $order->total_paid_amount;
                }
            }

            if ($paymentMethod === 'Cash') {
                $paymentText = 'Cash';
                $rowTotal = $cashAmount;
            } elseif ($paymentMethod === 'Card') {
                $paymentText = 'Card';
                $rowTotal = $cardAmount;
            } elseif ($paymentMethod === 'Mobile Banking') {
                $paymentText = 'Mobile Banking';
                $rowTotal = $mfcAmount;
            } else {
                $paymentParts = [];
                if ($cashAmount > 0) $paymentParts[] = 'Cash';
                if ($cardAmount > 0) $paymentParts[] = 'Card';
                if ($mfcAmount > 0) $paymentParts[] = 'Mobile Banking';

                $paymentText = count($paymentParts) > 0
                    ? implode(' + ', $paymentParts)
                    : ($order->payment_type ?? 'N/A');

                $rowTotal = (float) ($order->total_paid_amount ?? ($cashAmount + $cardAmount + $mfcAmount));
            }

            return [
                'order_number' => $order->order_number,
                'date' => optional($order->created_at)->format('d M, h:i A'),
                'customer' => optional($order->customer)->name ?? 'Walk-in',
                'table' => optional($order->table)->table_number ?? 'Takeaway',
                'payment_type' => $paymentText,
                'cash' => $cashAmount,
                'card' => $cardAmount,
                'mfc' => $mfcAmount,
                'total_paid' => $rowTotal,
            ];
        });
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
            // Updated for exact blade table matching
            $dataRows = Order::with(['customer', 'table', 'orderDetails'])
                ->where('status', 'Completed')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->orderBy('id', 'desc')
                ->get();

            $periodTotalSale = $dataRows->sum('grand_total');
            $periodTotalDiscount = $dataRows->sum('discount_amount');
            $periodTotalOrder = $dataRows->count();
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
            'orientation' => in_array($viewData['report'], ['payment_type_sales', 'sales_order'], true) ? 'L' : 'P',
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
