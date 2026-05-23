<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OrderKot;

class KitchenController extends Controller
{
    public function index()
    {
        return view('admin.kitchen.index');
    }

    // AJAX: Get Live Orders & Food Summary
    public function getLiveOrders()
    {
        // Pending, Cooking এবং Ready অবস্থায় থাকা KOT গুলো নিয়ে আসছি
        $kots = OrderKot::with(['order.table', 'order.waiter', 'orderDetails'])
            ->whereIn('kitchen_status', ['Pending', 'Cooking', 'Ready'])
            ->orderBy('created_at', 'asc')
            ->get();

        $pendingCount = $kots->where('kitchen_status', 'Pending')->count();
        $cookingCount = $kots->where('kitchen_status', 'Cooking')->count();
        $readyCount   = $kots->where('kitchen_status', 'Ready')->count();

        // Food Summary ক্যালকুলেশন (শুধুমাত্র Pending ও Cooking আইটেমের জন্য)
        $foodSummary = [];
        $totalSummaryItems = 0;
        foreach($kots as $kot) {
            if(in_array($kot->kitchen_status, ['Pending', 'Cooking'])) {
                foreach($kot->orderDetails as $item) {
                    if(!isset($foodSummary[$item->product_name])) {
                        $foodSummary[$item->product_name] = 0;
                    }
                    $foodSummary[$item->product_name] += $item->quantity;
                    $totalSummaryItems += $item->quantity;
                }
            }
        }
        arsort($foodSummary); // সবচেয়ে বেশি অর্ডার হওয়া খাবারগুলো উপরে থাকবে

        // ভিউ রেন্ডার করা
        $html = view('admin.kitchen.partials._board_content', compact('kots', 'foodSummary'))->render();

        return response()->json([
            'html' => $html,
            'pendingCount' => $pendingCount,
            'cookingCount' => $cookingCount,
            'readyCount' => $readyCount,
            'summaryCount' => $totalSummaryItems,
            'csrfToken' => csrf_token()
        ]);
    }

    // AJAX: Update Status
    public function updateStatus(Request $request)
    {
        $kot = OrderKot::findOrFail($request->kot_id);
        $kot->kitchen_status = $request->status; // Pending -> Cooking -> Ready -> Delivered
        $kot->save();

        return response()->json([
            'status' => 'success',
            'csrfToken' => csrf_token()
        ]);
    }

    // KOT Print View
    public function printKot($id)
    {
        $kot = OrderKot::with(['order.table', 'order.waiter', 'orderDetails'])->findOrFail($id);
        return view('admin.kitchen.print_kot', compact('kot'));
    }

    // AJAX: Mark Item as Unavailable & Recalculate Bill
    public function markItemUnavailable(Request $request)
    {
        $detail = \App\Models\OrderDetail::findOrFail($request->detail_id);
        $detail->is_unavailable = 1;
        $detail->save();

        $order = \App\Models\Order::findOrFail($detail->order_id);

        // শুধুমাত্র Available আইটেমগুলোর সাবটোটাল পুনরায় যোগ করা
        $newSubtotal = \App\Models\OrderDetail::where('order_id', $order->id)
                                              ->where('is_unavailable', 0)
                                              ->sum('subtotal');

        // ভ্যাট ও সার্ভিস চার্জ সেটিং
        $taxSetting = \Illuminate\Support\Facades\DB::table('tax_settings')->first();
        $vat_rate = $taxSetting->vat_rate ?? 0;
        $service_charge_rate = (strtolower($order->order_type) == 'dine-in' || strtolower($order->order_type) == 'dine_in') ? ($taxSetting->service_charge ?? 0) : 0;

        $discount_amount = $order->discount_amount;

        // নতুন ক্যালকুলেশন (রাউন্ড ফিগার সহ)
        $service_charge = round(($newSubtotal * $service_charge_rate) / 100);
        $tax = round((($newSubtotal + $service_charge) * $vat_rate) / 100);
        $grand_total = round(($newSubtotal + $tax + $service_charge) - $discount_amount);

        // ডাটাবেজে অর্ডারের নতুন বিল আপডেট করা
        $order->update([
            'subtotal' => $newSubtotal,
            'service_charge' => $service_charge,
            'vat_tax' => $tax,
            'grand_total' => $grand_total,
            'due' => $grand_total - $order->total_paid_amount
        ]);

        return response()->json([
            'status' => 'success',
            'csrfToken' => csrf_token()
        ]);
    }
}
