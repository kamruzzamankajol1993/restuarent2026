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

        // ডাটাবেজের স্ট্যাটাস ছোট/বড় হাতের যাই থাকুক, তা ঠিকভাবে কাউন্ট করার লজিক
        $availCount = $tables->filter(function($table) {
            return strtolower($table->initial_status) === 'available';
        })->count();

        $occCount = $tables->filter(function($table) {
            return strtolower($table->initial_status) === 'occupied';
        })->count();

        $resCount = $tables->filter(function($table) {
            return strtolower($table->initial_status) === 'reserved';
        })->count();

        // compact এর মধ্যে নতুন ভেরিয়েবলগুলো পাস করা হলো
        return view('admin.pos.index', compact('categories', 'tables', 'waiters', 'customers', 'posSetting', 'availCount', 'occCount', 'resCount'));
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
        if ($request->order_type == 'takeaway') return 'pos_cart_takeaway';
        if ($request->order_type == 'delivery') return 'pos_cart_delivery'; // Delivery কার্ট
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

        // নতুন লজিক: শুধু Dine-In হলে সার্ভিস চার্জ পাবে, Takeaway/Delivery তে 0 হবে
        $service_charge_rate = ($request->order_type == 'dine_in') ? ($taxSetting->service_charge ?? 0) : 0;

        return view('admin.pos.partials.cart_items', compact('cart', 'subtotal', 'vat_rate', 'service_charge_rate'))->render();
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

            // নতুন লজিক: শুধু Dine-In এর জন্য সার্ভিস চার্জ
            $service_charge_rate = ($request->order_type == 'dine_in') ? ($taxSetting->service_charge ?? 0) : 0;

            $discount_value = $request->discount_value ?? 0;
            $discount_type = $request->discount_type ?? 'fixed';

            // Order Type সেভ করার লজিক
            $order_type_val = 'Dine-In';
            if($request->order_type == 'takeaway') $order_type_val = 'Takeaway';
            if($request->order_type == 'delivery') $order_type_val = 'Delivery';

            // ২. চেক করা: এটি কি পুরনো অর্ডার? (Add More Food)
            if ($request->filled('order_id')) {
                $order = Order::findOrFail($request->order_id);

                // পুরনো অর্ডারের সাথে নতুন কার্টের বিল যোগ করা
                $total_subtotal = $order->subtotal + $current_cart_subtotal;

                $discount_amount = ($discount_type == 'percentage')
                    ? ($total_subtotal * $discount_value) / 100
                    : $discount_value;

                // নতুন লজিক: সাবটোটালের ওপর ভ্যাট এবং সার্ভিস চার্জ
                $tax = ($total_subtotal * $vat_rate) / 100;
                $service_charge = ($total_subtotal * $service_charge_rate) / 100;
                $grand_total = ($total_subtotal + $tax + $service_charge) - $discount_amount;

                // শুধু বিল আপডেট হবে, কাস্টমার/ওয়েটার আগেরটাই থাকবে
                $order->update([
                    'subtotal' => $total_subtotal,
                    'discount_amount' => $discount_amount,
                    'discount_type' => $discount_type,
                    'vat_tax' => $tax,
                    'service_charge' => $service_charge,
                    'grand_total' => $grand_total,
                    'preparation_time' => $request->preparation_time ?? 20, // ফিক্সড প্রিপারেশন টাইম
                    'due' => $grand_total,
                ]);

            } else {
                // ৩. যদি নতুন অর্ডার হয় (ফ্রেশ কাস্টমার)
                $discount_amount = ($discount_type == 'percentage')
                    ? ($current_cart_subtotal * $discount_value) / 100
                    : $discount_value;

                // নতুন লজিক: সাবটোটালের ওপর ভ্যাট এবং সার্ভিস চার্জ
                $tax = ($current_cart_subtotal * $vat_rate) / 100;
                $service_charge = ($current_cart_subtotal * $service_charge_rate) / 100;
                $grand_total = ($current_cart_subtotal + $tax + $service_charge) - $discount_amount;

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
                    'table_id' => ($request->order_type == 'takeaway' || $request->order_type == 'delivery') ? null : $request->table_id,
                    'waiter_id' => $request->waiter_id,
                    'user_id' => auth()->id() ?? 1,
                    'order_type' => $order_type_val,
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
                    'preparation_time' => $request->preparation_time ?? 20
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
                    'order_id' => $order->id,
                    'order_kot_id' => $kot->id,
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


    public function completePayment(Request $request)
    {
        DB::beginTransaction();
        try {
            $taxSetting = DB::table('tax_settings')->first();
            $vat_rate = $taxSetting->vat_rate ?? 0;

            if ($request->filled('order_id')) {
                $order = Order::findOrFail($request->order_id);
            } else {
                // ডাইরেক্ট পেমেন্টের ক্ষেত্রে Takeaway বা Delivery যাচাই করা
                $order_type = $request->order_type ?? 'takeaway';
                $cartKey = ($order_type == 'delivery') ? 'pos_cart_delivery' : 'pos_cart_takeaway';

                $cart = Session::get($cartKey, []);

                if (count($cart) == 0) {
                    return response()->json(['status' => 'error', 'message' => 'Cart is empty!']);
                }

                $subtotal = 0;
                foreach ($cart as $item) {
                    $subtotal += ($item['price'] + $item['addon_total']) * $item['qty'];
                }

                $order = new Order();
                $order->order_number = time();
                $order->order_type = ucfirst($order_type); // Takeaway or Delivery
                $order->subtotal = $subtotal;
                $order->user_id = auth()->id() ?? 1;
                $order->order_time = now();
            }

            $subtotal = $order->subtotal;

            // বিল ক্যালকুলেশনে সার্ভিস চার্জ চেক (শুধু Dine-In হলে সার্ভিস চার্জ কাটবে)
            $service_charge_rate = (strtolower($order->order_type) == 'dine-in' || strtolower($order->order_type) == 'dine_in') ? ($taxSetting->service_charge ?? 0) : 0;

            $discount_value = $request->discount_value ?? 0;
            $discount_type = $request->discount_type ?? 'fixed';

            $discount_amount = ($discount_type == 'percentage')
                ? ($subtotal * $discount_value) / 100
                : $discount_value;

            $tax = ($subtotal * $vat_rate) / 100;
            $service_charge = ($subtotal * $service_charge_rate) / 100;
            $grand_total = ($subtotal + $tax + $service_charge) - $discount_amount;

            // ===============================================
            // পেমেন্ট স্প্লিট এবং Due ক্যালকুলেশন
            // ===============================================
            $paymentMethod = $request->payment_method;
            $totalPaid = $request->total_paid_amount ?? 0;

            // স্প্লিট পেমেন্ট হলে আলাদা ইনপুট থেকে ডাটা নিবে
            $cash = ($paymentMethod == 'Split') ? ($request->paid_in_cash ?? 0) : (($paymentMethod == 'Cash') ? $totalPaid : 0);
            $card = ($paymentMethod == 'Split') ? ($request->paid_in_card ?? 0) : (($paymentMethod == 'Card') ? $totalPaid : 0);
            $mfc  = ($paymentMethod == 'Split') ? ($request->paid_in_mfc ?? 0)  : (($paymentMethod == 'Mobile Banking') ? $totalPaid : 0);

            // Due হিসাব করা হচ্ছে
            $due = $grand_total - $totalPaid;
            if($due < 0) $due = 0; // যদি কাস্টমার বেশি টাকা দেয়, তবে Due 0 থাকবে।

            $order->update([
                'discount_type'     => $discount_type,
                'discount_amount'   => $discount_amount,
                'vat_tax'           => $tax,
                'service_charge'    => $service_charge,
                'grand_total'       => $grand_total,
                'payment_type'      => $paymentMethod,
                'transaction_id'    => $request->transaction_id,
                'status'            => 'Completed',
                'due'               => $due,
                'total_paid_amount' => $totalPaid,
                'paid_in_cash'      => $cash,
                'paid_in_card'      => $card,
                'paid_in_mfc'       => $mfc
            ]);

            if (!$request->filled('order_id')) {
                // সরাসরি কার্ট থেকে পেমেন্ট হলে OrderDetails এ ডাটা সেভ করা
                $order_type = $request->order_type ?? 'takeaway';
                $cartKey = ($order_type == 'delivery') ? 'pos_cart_delivery' : 'pos_cart_takeaway';

                foreach ($cart as $item) {
                    OrderDetail::create([
                        'order_id'     => $order->id,
                        'product_id'   => $item['food_id'],
                        'product_name' => $item['name'],
                        'quantity'     => $item['qty'],
                        'price'        => $item['price'],
                        'subtotal'     => ($item['price'] + $item['addon_total']) * $item['qty'],
                    ]);
                }
                Session::forget($cartKey); // যে কার্ট থেকে অর্ডার হয়েছে শুধু সেটি ক্লিয়ার হবে
            }

            if ($order->table_id) {
                Table::where('id', $order->table_id)->update(['initial_status' => 'Available']);
            }

            DB::commit();
            return response()->json([
                'status'       => 'success',
                'redirect_url' => url('/pos/invoice/'.$order->id)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Payment failed! '.$e->getMessage()]);
        }
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

 


    // ====================================================
    // Invoice & Pre-Invoice Print Methods
    // ====================================================
    public function printInvoice($id)
    {
        $order = Order::with(['orderDetails', 'customer', 'waiter', 'user'])->findOrFail($id);
        $restaurant = \App\Models\RestaurantSetting::first();
        return view('admin.pos.invoice', compact('order', 'restaurant'));
    }

    public function printPreInvoice(Request $request, $id)
    {
        $order = Order::with(['orderDetails', 'customer', 'waiter', 'user'])->findOrFail($id);
        $restaurant = \App\Models\RestaurantSetting::first();
        $taxSetting = DB::table('tax_settings')->first();
        $invoiceSetting = \App\Models\InvoiceSetting::first();

        // মোডালে দেওয়া লাইভ ডিসকাউন্টটি রিসিভ করে ক্যালকুলেশন করা হচ্ছে
        // (যাতে সেভ না করলেও প্রি-ইনভয়েসে ডিসকাউন্ট দেখায়)
        $subtotal = $order->subtotal;
        $disc_type = $request->disc_type ?? 'fixed';
        $disc_val = $request->disc_val ?? 0;

        $discount_amount = ($disc_type == 'percentage') ? ($subtotal * $disc_val) / 100 : $disc_val;
        $vat_rate = $taxSetting->vat_rate ?? 0;
        $service_rate = $taxSetting->service_charge ?? 0;

        $tax = ($subtotal * $vat_rate) / 100;
        $service = ($subtotal * $service_rate) / 100;
        $grand_total = ($subtotal + $tax + $service) - $discount_amount;

        // শুধু ভিউয়ের জন্য সাময়িকভাবে ডাটাগুলো ওভাররাইড করা হলো (ডাটাবেজে সেভ হবে না)
        $order->discount_type = $disc_type;
        $order->discount_amount = $discount_amount;
        $order->vat_tax = $tax;
        $order->service_charge = $service;
        $order->grand_total = $grand_total;

        return view('admin.pos.pre_invoice', compact('order', 'restaurant', 'invoiceSetting'));
    }

}
