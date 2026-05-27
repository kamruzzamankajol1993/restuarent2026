<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use Carbon\Carbon;
use Mpdf\Mpdf;
use Illuminate\Support\Facades\DB;
use App\Models\OrderDetail;
use App\Models\OrderKot;
use App\Models\Table;
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


    // ==========================================
    // Real-Time Notification Logic
    // ==========================================
  public function checkNotifications()
    {
        // orderDetails সহ ডাটা আনা হচ্ছে যাতে মোডালে আইটেম দেখানো যায়
        $newOrder = \App\Models\Order::with(['table', 'orderDetails'])
            ->where('status', 'QR_Pending')
            ->orderBy('id', 'asc')
            ->first();

        $waiterCall = \Illuminate\Support\Facades\DB::table('waiter_calls')
            ->join('tables', 'waiter_calls.table_id', '=', 'tables.id')
            ->where('waiter_calls.status', 'pending')
            ->select('waiter_calls.*', 'tables.table_number')
            ->orderBy('waiter_calls.id', 'asc')
            ->first();

        return response()->json([
            'status'      => 'success',
            'order'       => $newOrder,
            'waiter_call' => $waiterCall
        ]);
    }

   public function acceptQrOrder(Request $request)
{
    \Illuminate\Support\Facades\DB::beginTransaction();
    try {
        $order = \App\Models\Order::findOrFail($request->id);

        // ১. কাস্টমার সেটআপ
        $customerId = null;
        if ($request->customer_type == 'existing') {
            $customerId = $request->customer_id;
        } elseif ($request->customer_type == 'new') {
            $newCustomer = \App\Models\Customer::create([
                'name' => $request->customer_name,
                'phone' => $request->customer_phone
            ]);
            $customerId = $newCustomer->id;
        }

        // ২. অর্ডার আপডেট (স্ট্যাটাস, ওয়েটার, কাস্টমার এবং প্রিপারেশন টাইম)
        $order->status = 'Pending';
        $order->customer_id = $customerId;
        $order->waiter_id = $request->waiter_id;
        // প্রিপারেশন টাইম ডাটাবেজে সেভ করা হচ্ছে
        $order->preparation_time = $request->preparation_time ?? 20;
        $order->save();

        // ৩. কিচেন KOT জেনারেট করা
        $kotCount = \App\Models\OrderKot::where('order_id', $order->id)->count();
        $kotId = \Illuminate\Support\Facades\DB::table('order_kots')->insertGetId([
            'order_id' => $order->id,
            'kot_number' => 'KOT-' . ($kotCount + 1),
            'kitchen_status' => 'Pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // ৪. আইটেমগুলোকে KOT এর সাথে লিংক করা
        \Illuminate\Support\Facades\DB::table('order_details')
            ->where('order_id', $order->id)
            ->update(['order_kot_id' => $kotId]);

        // ৫. টেবিল Occupied করা
        if ($order->table_id) {
            \App\Models\Table::where('id', $order->table_id)->update(['initial_status' => 'Occupied']);
        }

        \Illuminate\Support\Facades\DB::commit();
        return response()->json(['status' => 'success']);

    } catch (\Exception $e) {
        \Illuminate\Support\Facades\DB::rollBack();
        return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

    public function resolveWaiterCall(Request $request)
    {
        \Illuminate\Support\Facades\DB::table('waiter_calls')
            ->where('id', $request->id)
            ->update(['status' => 'resolved']);

        return response()->json(['status' => 'success']);
    }

    public function details($id)
    {
        // 'review' রিলেশনটি যুক্ত করা হয়েছে
        $order = Order::with(['customer', 'table', 'waiter', 'orderDetails', 'user', 'review'])->findOrFail($id);

        // ফুল-পেজ ব্লেড ফাইল রিটার্ন করা হচ্ছে
        return view('admin.order.show', compact('order'));
    }

    public function destroy($id)
{
    if (!auth()->user()->can('order-delete')) {
        return response()->json([
            'status' => 'error',
            'message' => 'You do not have permission to delete this order.'
        ], 403);
    }

    \Illuminate\Support\Facades\DB::beginTransaction();

    try {
        $order = Order::with(['orderDetails', 'kots'])->findOrFail($id);

        // যদি dine-in order হয়, table available করে দেওয়া
        if ($order->table_id) {
            \App\Models\Table::where('id', $order->table_id)->update([
                'initial_status' => 'Available'
            ]);
        }

        // Related data delete
        \App\Models\OrderDetail::where('order_id', $order->id)->delete();
        \App\Models\OrderKot::where('order_id', $order->id)->delete();

        // Main order delete
        $order->delete();

        \Illuminate\Support\Facades\DB::commit();

        return response()->json([
            'status' => 'success',
            'message' => 'Order deleted successfully!'
        ]);

    } catch (\Exception $e) {
        \Illuminate\Support\Facades\DB::rollBack();

        return response()->json([
            'status' => 'error',
            'message' => 'Order delete failed! ' . $e->getMessage()
        ], 500);
    }
}
}
