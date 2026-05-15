<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FoodItem;
use App\Models\FoodCategory;
use App\Models\Table;
use App\Models\Waiter;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderKot;
use App\Models\OrderDetail;
use App\Models\PointHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class PosController extends Controller
{
    public function index()
    {
        $posSetting = DB::table('pos_settings')->first();
        $categories = FoodCategory::whereNull('parent_category_id')->where('status', 1)->orderBy('sort_order', 'asc')->get();
        $tables = Table::with('zone')->get();
        $waiters = Waiter::where('status', 1)->get();
        $customers = Customer::orderBy('name', 'asc')->get();

        return view('admin.pos.index', compact('categories', 'tables', 'waiters', 'customers', 'posSetting'));
    }

    public function getFoods(Request $request)
    {
        $posSetting = DB::table('pos_settings')->first();
        $limit = $posSetting ? ($posSetting->items_per_page ?? 12) : 12;

        $query = FoodItem::with('addons')->where('is_available', 1);

        if ($request->category_id) {
            $query->where('food_category_id', $request->category_id)->orWhere('sub_category_id', $request->category_id);
        }
        if ($request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $foods = $query->paginate($limit);
        return view('admin.pos.partials.food_grid', compact('foods'))->render();
    }

    public function getAddons($id)
    {
        $food = FoodItem::with('addons')->findOrFail($id);
        return response()->json(['status' => 'success', 'food' => $food]);
    }

    // ====================================================
    // টেবিল অনুযায়ী আলাদা কার্ট তৈরি করার হেল্পার মেথড
    // ====================================================
    private function getCartKey(Request $request)
    {
        if ($request->order_type == 'takeaway') {
            return 'pos_cart_takeaway';
        }
        return 'pos_cart_table_' . $request->table_id;
    }

    public function addToCart(Request $request)
    {
        $cartKey = $this->getCartKey($request);
        $cart = Session::get($cartKey, []);

        $food = FoodItem::find($request->food_id);
        $cartId = uniqid();

        $price = $food->discount_price ?? $food->base_price;
        $addonTotal = 0;
        $addons = [];

        if ($request->addons) {
            foreach ($request->addons as $addonId) {
                $addon = \App\Models\FoodAddon::find($addonId);
                if($addon) {
                    $addons[] = ['name' => $addon->name, 'price' => $addon->price];
                    $addonTotal += $addon->price;
                }
            }
        }

        $cart[$cartId] = [
            'food_id' => $food->id,
            'name' => $food->name,
            'qty' => $request->qty ?? 1,
            'price' => $price,
            'addon_total' => $addonTotal,
            'addons' => $addons,
            'note' => ''
        ];

        Session::put($cartKey, $cart);
        return response()->json(['status' => 'success']);
    }

    public function getCart(Request $request)
    {
        $cartKey = $this->getCartKey($request);
        $cart = Session::get($cartKey, []);

        $subtotal = 0;
        foreach($cart as $item) {
            $subtotal += ($item['price'] + $item['addon_total']) * $item['qty'];
        }

        $taxSetting = DB::table('tax_settings')->first();
        $vat_rate = $taxSetting ? $taxSetting->vat_rate : 0;
        $service_charge_rate = $taxSetting ? $taxSetting->service_charge : 0;

        return view('admin.pos.partials.cart_items', compact('cart', 'subtotal', 'vat_rate', 'service_charge_rate'))->render();
    }

    public function updateCart(Request $request)
    {
        $cartKey = $this->getCartKey($request);
        $cart = Session::get($cartKey, []);

        if(isset($cart[$request->cart_id])) {
            if($request->action == 'plus') {
                $cart[$request->cart_id]['qty'] += 1;
            } elseif($request->action == 'minus') {
                if($cart[$request->cart_id]['qty'] > 1) {
                    $cart[$request->cart_id]['qty'] -= 1;
                } else {
                    unset($cart[$request->cart_id]);
                }
            }
            Session::put($cartKey, $cart);
        }
        return response()->json(['status' => 'success']);
    }

    public function updateNote(Request $request)
    {
        $cartKey = $this->getCartKey($request);
        $cart = Session::get($cartKey, []);

        if(isset($cart[$request->cart_id])) {
            $cart[$request->cart_id]['note'] = $request->note;
            Session::put($cartKey, $cart);
        }
        return response()->json(['status' => 'success']);
    }

    public function removeFromCart(Request $request)
    {
        $cartKey = $this->getCartKey($request);
        $cart = Session::get($cartKey, []);

        if(isset($cart[$request->cart_id])) {
            unset($cart[$request->cart_id]);
            Session::put($cartKey, $cart);
        }
        return response()->json(['status' => 'success']);
    }

    public function placeOrder(Request $request)
{
    $cartKey = $this->getCartKey($request);
    $cart = Session::get($cartKey, []);

    if (count($cart) == 0) {
        return response()->json(['status' => 'error', 'message' => 'Cart is empty!']);
    }

    DB::beginTransaction();
    try {
        // ১. কার্টে থাকা নতুন আইটেমের টোটাল হিসাব করা
        $current_cart_subtotal = 0;
        foreach ($cart as $item) {
            $current_cart_subtotal += ($item['price'] + $item['addon_total']) * $item['qty'];
        }

        $taxSetting = DB::table('tax_settings')->first();
        $vat_rate = $taxSetting->vat_rate ?? 0;
        $service_charge_rate = $taxSetting->service_charge ?? 0;

        $discount_value = $request->discount_value ?? 0;
        $discount_type = $request->discount_type ?? 'fixed';

        // ২. চেক করা: এটি কি পুরনো অর্ডার? (Add More Food)
        if ($request->filled('order_id')) {
            $order = Order::findOrFail($request->order_id);

            // পুরনো অর্ডারের সাথে নতুন কার্টের বিল যোগ করা
            $total_subtotal = $order->subtotal + $current_cart_subtotal;

            $discount_amount = ($discount_type == 'percentage')
                ? ($total_subtotal * $discount_value) / 100
                : $discount_value;

            $discounted_subtotal = $total_subtotal - $discount_amount;
            $tax = ($discounted_subtotal * $vat_rate) / 100;
            $service_charge = ($discounted_subtotal * $service_charge_rate) / 100;
            $grand_total = $discounted_subtotal + $tax + $service_charge;

            // শুধু বিল আপডেট হবে, কাস্টমার/ওয়েটার আগেরটাই থাকবে
            $order->update([
                'subtotal' => $total_subtotal,
                'discount_amount' => $discount_amount,
                'discount_type' => $discount_type,
                'vat_tax' => $tax,
                'service_charge' => $service_charge,
                'grand_total' => $grand_total,
                'preparation_time' => $request->preparation_time,
                'due' => $grand_total,
            ]);

        } else {
            // ৩. যদি নতুন অর্ডার হয় (ফ্রেশ কাস্টমার)
            $discount_amount = ($discount_type == 'percentage')
                ? ($current_cart_subtotal * $discount_value) / 100
                : $discount_value;

            $discounted_subtotal = $current_cart_subtotal - $discount_amount;
            $tax = ($discounted_subtotal * $vat_rate) / 100;
            $service_charge = ($discounted_subtotal * $service_charge_rate) / 100;
            $grand_total = $discounted_subtotal + $tax + $service_charge;

            $customerId = null;
            if ($request->is_walk_in == '0') {
                if ($request->customer_id) {
                    $customerId = $request->customer_id;
                } else if ($request->customer_name) {
                    $newCustomer = Customer::create(['name' => $request->customer_name, 'phone' => $request->customer_phone]);
                    $customerId = $newCustomer->id;
                }
            }

            // নতুন অর্ডার তৈরি
            $order = Order::create([
                'customer_id' => $customerId,
                'table_id' => $request->order_type == 'takeaway' ? null : $request->table_id,
                'waiter_id' => $request->waiter_id,
                'user_id' => auth()->id() ?? 1,
                'order_type' => ($request->order_type == 'dine_in') ? 'Dine-In' : 'Takeaway',
                'subtotal' => $current_cart_subtotal,
                'discount_amount' => $discount_amount,
                'discount_type' => $discount_type,
                'vat_tax' => $tax,
                'service_charge' => $service_charge,
                'grand_total' => $grand_total,
                'due' => $grand_total,
                'status' => 'Pending',
                'notes' => $request->order_notes,
                'order_time' => now(),
                'preparation_time' => $request->preparation_time || 20
            ]);

            if ($request->order_type == 'dine_in') {
                Table::where('id', $request->table_id)->update(['initial_status' => 'Occupied']);
            }
        }

        // ৪. এই অর্ডারের আন্ডারে নতুন KOT জেনারেট করা
        $kotCount = OrderKot::where('order_id', $order->id)->count();
        $kot = OrderKot::create([
            'order_id' => $order->id, // একই অর্ডার আইডি
            'kot_number' => 'KOT-' . ($kotCount + 1), // আগে KOT-1 থাকলে এটা KOT-2 হবে
            'kitchen_status' => 'Pending'
        ]);

        // ৫. নতুন আইটেমগুলো শুধুমাত্র নতুন KOT-তে সেভ করা
        foreach ($cart as $item) {
            OrderDetail::create([
                'order_id' => $order->id,       // একই অর্ডার আইডি
                'order_kot_id' => $kot->id,     // নতুন KOT আইডি
                'product_id' => $item['food_id'],
                'product_name' => $item['name'],
                'quantity' => $item['qty'],
                'price' => $item['price'],
                'subtotal' => ($item['price'] + $item['addon_total']) * $item['qty'],
                'addons' => json_encode($item['addons']),
                'food_note' => $item['note'] ?? null
            ]);
        }

        Session::forget($cartKey);
        DB::commit();

        return response()->json([
            'status' => 'success',
            'message' => 'Food Added to Order! (KOT-' . ($kotCount + 1) . ')'
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

    // (getTableOrder এবং completePayment মেথড আগের মতোই থাকবে...)
    public function getTableOrder($table_id)
    {
        $order = Order::with(['kots.orderDetails', 'waiter', 'customer', 'table'])
                      ->where('table_id', $table_id)
                      ->where('status', 'Pending')
                      ->first();

        if(!$order) return response()->json(['status' => 'error', 'message' => 'No active order found.']);

        $kitchenBusy = $order->kots()->whereIn('kitchen_status', ['Pending', 'Cooking'])->exists();
        return view('admin.pos.partials.offcanvas_order', compact('order', 'kitchenBusy'))->render();
    }

    public function completePayment(Request $request)
    {
        DB::beginTransaction();
        try {
            $order = Order::findOrFail($request->order_id);
            $order->update(['status' => 'Completed', 'payment_type' => $request->payment_method, 'transaction_id' => $request->transaction_id, 'due' => 0]);

            if ($order->table_id) {
                Table::where('id', $order->table_id)->update(['initial_status' => 'Available']);
            }

            if ($order->customer_id) {
                $pointSetting = DB::table('reward_point_settings')->first();
                $earnedPoints = 0;
                if ($pointSetting) {
                    if ($pointSetting->reward_type == 'order_based') {
                        $earnedPoints = $pointSetting->points_per_order;
                    } else {
                        $amountToSpend = $pointSetting->amount_to_spend > 0 ? $pointSetting->amount_to_spend : 1;
                        $earnedPoints = floor($order->grand_total / $amountToSpend) * $pointSetting->points_per_amount;
                    }
                }
                if ($earnedPoints > 0) {
                    Customer::where('id', $order->customer_id)->increment('points', $earnedPoints);
                    PointHistory::create(['customer_id' => $order->customer_id, 'point' => $earnedPoints, 'note' => 'Earned from #'.$order->order_number]);
                }
            }

            DB::commit();
            return response()->json(['status' => 'success', 'redirect_url' => url('/pos/invoice/'.$order->id)]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Payment failed! '.$e->getMessage()]);
        }
    }



// ====================================================
    // Invoice Print Method
    // ====================================================
    public function printInvoice($id)
    {
        // ডাটাবেজ থেকে অর্ডার এবং এর সাথে সম্পর্কিত সব ডাটা নিয়ে আসা
        $order = Order::with(['orderDetails', 'customer', 'waiter', 'user'])->findOrFail($id);

        // রেস্টুরেন্টের সেটিংস নিয়ে আসা (ইনভয়েসের হেডার/লোগোর জন্য)
        $restaurant = \App\Models\RestaurantSetting::first();

        // invoice.blade.php ভিউ রিটার্ন করা (ফাইলটি resources/views/admin/pos/ ফোল্ডারে থাকতে হবে)
        return view('admin.pos.invoice', compact('order', 'restaurant'));
    }

}
