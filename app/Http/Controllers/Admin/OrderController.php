<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use Carbon\Carbon;
use Mpdf\Mpdf;
class OrderController extends Controller
{
    public function index(Request $request)
    {
        // 1. Stats Calculation
        $today = Carbon::today();
        $stats = [
            'today_orders' => Order::whereDate('created_at', $today)->count(),
            'active_orders' => Order::whereIn('status', ['Pending', 'Cooking', 'Processing'])->count(),
            'completed_orders' => Order::where('status', 'Completed')->count(),
            'revenue_today' => Order::whereDate('created_at', $today)->where('status', 'Completed')->sum('grand_total'),
        ];

        // 2. Query Builder
        $query = Order::with(['customer', 'table', 'orderDetails']);

        // Filters
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('order_number', 'like', "%{$request->search}%")
                  ->orWhereHas('customer', function($c) use ($request) {
                      $c->where('name', 'like', "%{$request->search}%");
                  });
            });
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->payment) {
            $query->where('payment_type', $request->payment);
        }
        if ($request->date_range) {
            if ($request->date_range == 'Today') {
                $query->whereDate('created_at', Carbon::today());
            } elseif ($request->date_range == 'Yesterday') {
                $query->whereDate('created_at', Carbon::yesterday());
            } elseif ($request->date_range == 'This Week') {
                $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
            } elseif ($request->date_range == 'This Month') {
                $query->whereMonth('created_at', Carbon::now()->month)->whereYear('created_at', Carbon::now()->year);
            }
        }

        $orders = $query->orderBy('id', 'desc')->paginate(10);

        // AJAX Response for table
        if ($request->ajax()) {
            return view('admin.order.partials._order_table', compact('orders'))->render();
        }

        return view('admin.order.index', compact('orders', 'stats'));
    }

    public function show($id)
    {
        $order = Order::with(['customer', 'table', 'waiter', 'orderDetails', 'user'])->findOrFail($id);
        return view('admin.order.partials._order_details', compact('order'))->render();
    }

    public function exportPDF(Request $request)
    {
        $query = Order::with(['customer', 'table', 'orderDetails']);

        // ফিল্টার লজিক
        if ($request->search) {
            $query->where('order_number', 'like', "%{$request->search}%");
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->date_range) {
            if ($request->date_range == 'Today') {
                $query->whereDate('created_at', Carbon::today());
            } elseif ($request->date_range == 'Yesterday') {
                $query->whereDate('created_at', Carbon::yesterday());
            } elseif ($request->date_range == 'This Week') {
                $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
            } elseif ($request->date_range == 'This Month') {
                $query->whereMonth('created_at', Carbon::now()->month)->whereYear('created_at', Carbon::now()->year);
            }
        }

        $orders = $query->orderBy('id', 'desc')->get();
        $restaurant = \App\Models\RestaurantSetting::first();

        // ব্লেড ফাইল থেকে HTML রেন্ডার করা
        $html = view('admin.order.pdf_report', compact('orders', 'restaurant'))->render();

        // mPDF কনফিগারেশন এবং ইনিশিয়ালাইজেশন
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P', // P for Portrait, L for Landscape
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 16,
            'margin_bottom' => 16,
            'margin_header' => 9,
            'margin_footer' => 9
        ]);

        // ডকুমেন্টের টাইটেল সেট করা
        $mpdf->SetTitle('Order Report - ' . now()->format('d M, Y'));

        // HTML থেকে PDF তৈরি করা
        $mpdf->WriteHTML($html);

        // PDF ফাইলটি ডাউনলোড করতে 'D' (Download) প্যারামিটার ব্যবহার করা হলো
        $fileName = 'Order_Report_' . now()->format('d_M_Y') . '.pdf';
        return response($mpdf->Output($fileName, 'I'))->header('Content-Type', 'application/pdf');
    }

}
