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
            'summaryCount' => $totalSummaryItems
        ]);
    }

    // AJAX: Update Status
    public function updateStatus(Request $request)
    {
        $kot = OrderKot::findOrFail($request->kot_id);
        $kot->kitchen_status = $request->status; // Pending -> Cooking -> Ready -> Delivered
        $kot->save();

        return response()->json(['status' => 'success']);
    }

    // KOT Print View
    public function printKot($id)
    {
        $kot = OrderKot::with(['order.table', 'order.waiter', 'orderDetails'])->findOrFail($id);
        return view('admin.kitchen.print_kot', compact('kot'));
    }
}
