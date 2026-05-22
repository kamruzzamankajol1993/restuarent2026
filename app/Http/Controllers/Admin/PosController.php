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
use App\Models\PosSession; // ফাইলের উপরে এটি যুক্ত করতে ভুলবেন না
use Carbon\Carbon;
class PosController extends Controller
{
  public function index()
{
    $posSetting = DB::table('pos_settings')->first();
    $categories = FoodCategory::whereNull('parent_category_id')->where('status', 1)->orderBy('sort_order', 'asc')->get();
    $tables = Table::with('zone')->get();
    $waiters = Waiter::where('status', 1)->get();
    $customers = Customer::orderBy('name', 'asc')->get();

    $availCount = $tables->filter(function($table) { return strtolower($table->initial_status) === 'available'; })->count();
    $occCount = $tables->filter(function($table) { return strtolower($table->initial_status) === 'occupied'; })->count();
    $resCount = $tables->filter(function($table) { return strtolower($table->initial_status) === 'reserved'; })->count();

    $activeSession = PosSession::where('user_id', auth()->id())->where('status', 'Open')->first();
    $requirePreviousSessionClose = false;

    if ($activeSession) {
        if ($activeSession->start_time->format('Y-m-d') !== Carbon::now()->format('Y-m-d')) {
            $requirePreviousSessionClose = true;
        }
    }

    // সেশন হিস্ট্রি টেবিলের জন্য সব সেশন ডেটা নিয়ে আসা হলো
    $sessions = PosSession::with('user')->orderBy('id', 'desc')->get();

    return view('admin.pos.index', compact('categories', 'tables', 'waiters', 'customers', 'posSetting', 'availCount', 'occCount', 'resCount', 'activeSession', 'requirePreviousSessionClose', 'sessions'));
}

public function startSession(Request $request)
    {
        PosSession::create([
            'user_id' => auth()->id(),
            'weekday' => Carbon::now()->format('l'), // Monday, Tuesday ইত্যাদি
            'start_time' => Carbon::now(),
            'status' => 'Open'
        ]);

        return response()->json(['status' => 'success', 'message' => 'Work period started successfully!']);
    }

   public function endSession(Request $request)
    {
        $session = PosSession::where('id', $request->session_id)
                             ->where('status', 'Open')
                             ->firstOrFail();

        $endTime = Carbon::now();

        // শুধু কমপ্লিট হওয়া অর্ডারগুলোর হিসাব বের করা হবে
        $orders = Order::where('created_at', '>=', $session->start_time)
                       ->where('created_at', '<=', $endTime)
                       ->where('status', 'Completed')
                       ->get();

        $sales_total = $orders->sum('subtotal');
        $service_charge = $orders->sum('service_charge');
        $vat_total = $orders->sum('vat_tax');
        $grand_total = $orders->sum('grand_total');

        // ==========================================
        // পেমেন্ট মেথড অনুযায়ী Cash, Card এবং MFC হিসাব
        // ==========================================
        $cash = 0; $card = 0; $mfc = 0;

        foreach($orders as $order) {
            if ($order->payment_type == 'Split') {
                $cash += $order->paid_in_cash;
                $card += $order->paid_in_card;
                $mfc += $order->paid_in_mfc;
            } else {
                if ($order->payment_type == 'Cash') $cash += $order->total_paid_amount;
                if ($order->payment_type == 'Card') $card += $order->total_paid_amount;
                // মোডালে ভ্যালু "Mobile Banking" দেওয়া আছে, কিন্তু আমরা MFC হিসেবে কাউন্ট করছি
                if ($order->payment_type == 'Mobile Banking') $mfc += $order->total_paid_amount;
            }
        }

        // JSON এ সেভ করার জন্য Array তৈরি (একদম আপনার রিকোয়ারমেন্ট অনুযায়ী)
        $incomes = [
            'Cash' => $cash,
            'Card' => $card,
            'MFC'  => $mfc
        ];

        // ডিউরেশন ক্যালকুলেশন
        $durationDiff = $endTime->diffAsCarbonInterval($session->start_time);
        $duration = $durationDiff->cascade()->forHumans(['short' => true]); // যেমন: 10h 49m

        $session->update([
            'end_time' => $endTime,
            'duration' => $duration,
            'status' => 'Closed',
            'sales_total' => $sales_total,
            'service_charge' => $service_charge,
            'vat_total' => $vat_total,
            'grand_total' => $grand_total,
            'incomes_summary' => $incomes // এখানে JSON আপডেট হলো
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Work period ended successfully!',
            'session_id' => $session->id // প্রিন্টের জন্য
        ]);
    }

public function printSessionReport($id)
    {
        $session = PosSession::with('user')->findOrFail($id);
        $restaurant = \App\Models\RestaurantSetting::first();
        $taxSetting = DB::table('tax_settings')->first();
        return view('admin.pos.session_report', compact('session', 'restaurant', 'taxSetting'));
    }

    public function updateSession(Request $request)
    {
        $request->validate([
            'session_id' => 'required',
            'start_time' => 'required',
            'status' => 'required'
        ]);

        $session = PosSession::findOrFail($request->session_id);

        $startTime = Carbon::parse($request->start_time);
        $endTime = $request->end_time ? Carbon::parse($request->end_time) : null;
        $duration = null;

        // ডিফল্ট ভ্যালু সেট করা হচ্ছে
        $sales_total = 0;
        $service_charge = 0;
        $vat_total = 0;
        $grand_total = 0;
        $incomes = ['Cash' => 0, 'Card' => 0, 'MFC' => 0];

        // যদি সেশন Closed থাকে অথবা এন্ড টাইম দেওয়া হয়, তবে নতুন সময় অনুযায়ী হিসাব রি-ক্যালকুলেট হবে
        if ($endTime && $request->status == 'Closed') {
            // ডিউরেশন ক্যালকুলেশন
            $durationDiff = $endTime->diffAsCarbonInterval($startTime);
            $duration = $durationDiff->cascade()->forHumans(['short' => true]);

            // নতুন এডিট করা সময় সীমার ভেতরের Completed অর্ডারগুলো নেওয়া হচ্ছে
            $orders = Order::where('created_at', '>=', $startTime)
                           ->where('created_at', '<=', $endTime)
                           ->where('status', 'Completed')
                           ->get();

            $sales_total = $orders->sum('subtotal');
            $service_charge = $orders->sum('service_charge');
            $vat_total = $orders->sum('vat_tax');
            $grand_total = $orders->sum('grand_total');

            // নতুন করে পেমেন্ট মেথড সামারি তৈরি করা হচ্ছে
            $cash = 0; $card = 0; $mfc = 0;
            foreach($orders as $order) {
                if ($order->payment_type == 'Split') {
                    $cash += $order->paid_in_cash;
                    $card += $order->paid_in_card;
                    $mfc += $order->paid_in_mfc;
                } else {
                    if ($order->payment_type == 'Cash') $cash += $order->total_paid_amount;
                    if ($order->payment_type == 'Card') $card += $order->total_paid_amount;
                    if ($order->payment_type == 'Mobile Banking') $mfc += $order->total_paid_amount;
                }
            }

            $incomes = [
                'Cash' => $cash,
                'Card' => $card,
                'MFC'  => $mfc
            ];
        }

        // ডাটাবেজে আপডেট
        $session->update([
            'start_time' => $startTime,
            'end_time' => $endTime,
            'weekday' => $startTime->format('l'),
            'status' => $request->status,
            'duration' => $duration,
            'sales_total' => $request->status == 'Closed' ? $sales_total : 0,
            'service_charge' => $request->status == 'Closed' ? $service_charge : 0,
            'vat_total' => $request->status == 'Closed' ? $vat_total : 0,
            'grand_total' => $request->status == 'Closed' ? $grand_total : 0,
            'incomes_summary' => $request->status == 'Closed' ? $incomes : null
        ]);

        return response()->json(['status' => 'success', 'message' => 'Session updated and report recalculated successfully!']);
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

        $foods = $query->get();
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

                // নতুন লজিক: সাবটোটালের ওপর ভ্যাট এবং সার্ভিস চার্জ (রাউন্ড ফিগার সহ)
                $service_charge = round(($total_subtotal * $service_charge_rate) / 100);
                $tax = round((($total_subtotal + $service_charge) * $vat_rate) / 100);
                $discount_amount = round(($discount_type == 'percentage') ? ($total_subtotal * $discount_value) / 100 : $discount_value);
                $grand_total = round(($total_subtotal + $tax + $service_charge) - $discount_amount);

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
                // নতুন লজিক: সাবটোটালের ওপর ভ্যাট এবং সার্ভিস চার্জ (রাউন্ড ফিগার সহ)
                $service_charge = round(($current_cart_subtotal * $service_charge_rate) / 100);
                $tax = round((($current_cart_subtotal + $service_charge) * $vat_rate) / 100);
                $discount_amount = round(($discount_type == 'percentage') ? ($current_cart_subtotal * $discount_value) / 100 : $discount_value);
                $grand_total = round(($current_cart_subtotal + $tax + $service_charge) - $discount_amount);

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
                $order->order_type = ucfirst($order_type); // Takeaway or Delivery
                $order->subtotal = $subtotal;
                $order->user_id = auth()->id() ?? 1;
                $order->order_time = now();

                // অর্ডার নাম্বার আগে সেভ করতে হবে, যাতে OrderDetails এর জন্য Order ID পাওয়া যায়
                $order->save();
            }

            $subtotal = $order->subtotal;

            // বিল ক্যালকুলেশনে সার্ভিস চার্জ চেক (শুধু Dine-In হলে সার্ভিস চার্জ কাটবে)
            $service_charge_rate = (strtolower($order->order_type) == 'dine-in' || strtolower($order->order_type) == 'dine_in') ? ($taxSetting->service_charge ?? 0) : 0;

            $discount_value = $request->discount_value ?? 0;
            $discount_type = $request->discount_type ?? 'fixed';

            // ভ্যাট, সার্ভিস চার্জ এবং ডিসকাউন্ট ক্যালকুলেশন (রাউন্ড ফিগার সহ)
            $service_charge = round(($subtotal * $service_charge_rate) / 100);
            $tax = round((($subtotal + $service_charge) * $vat_rate) / 100);
            $discount_amount = round(($discount_type == 'percentage') ? ($subtotal * $discount_value) / 100 : $discount_value);
            $grand_total = round(($subtotal + $tax + $service_charge) - $discount_amount);

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

            // ===============================================
            // মডেলের মাস অ্যাসাইনমেন্ট রেসট্রিকশন এড়াতে সরাসরি প্রপার্টি সেট করে সেভ করা
            // ===============================================
            $order->discount_type     = $discount_type;
            $order->discount_amount   = $discount_amount;
            $order->vat_tax           = $tax;
            $order->service_charge    = $service_charge;
            $order->grand_total       = $grand_total;
            $order->payment_type      = $paymentMethod;
            $order->transaction_id    = $request->transaction_id;
            $order->status            = 'Completed'; // স্ট্যাটাস ১০০% আপডেট হবে
            $order->due               = $due;
            $order->total_paid_amount = $totalPaid;
            $order->paid_in_cash      = $cash;
            $order->paid_in_card      = $card;
            $order->paid_in_mfc       = $mfc;

            $order->save();

            if (!$request->filled('order_id')) {
                // সরাসরি কার্ট থেকে পেমেন্ট হলে OrderDetails এ ডাটা সেভ করা
                $order_type = $request->order_type ?? 'takeaway';
                $cartKey = ($order_type == 'delivery') ? 'pos_cart_delivery' : 'pos_cart_takeaway';

                $cart = Session::get($cartKey, []);
                foreach ($cart as $item) {
                    OrderDetail::create([
                        'order_id'     => $order->id, // এখন $order->id ঠিকমতো পাবে
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

        $service = round(($subtotal * $service_rate) / 100);
$tax = round((($subtotal + $service) * $vat_rate) / 100);
$discount_amount = round(($disc_type == 'percentage') ? ($subtotal * $disc_val) / 100 : $disc_val);
$grand_total = round(($subtotal + $tax + $service) - $discount_amount);

        // শুধু ভিউয়ের জন্য সাময়িকভাবে ডাটাগুলো ওভাররাইড করা হলো (ডাটাবেজে সেভ হবে না)
        $order->discount_type = $disc_type;
        $order->discount_amount = $discount_amount;
        $order->vat_tax = $tax;
        $order->service_charge = $service;
        $order->grand_total = $grand_total;

        return view('admin.pos.pre_invoice', compact('order', 'restaurant', 'invoiceSetting'));
    }

}
