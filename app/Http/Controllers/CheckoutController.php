<?php

namespace App\Http\Controllers;
use App\Models\BusinessSetting;
use App\Utility\PayfastUtility;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Address;
use App\Models\Carrier;
use App\Models\CombinedOrder;
use App\Models\Product;
use App\Utility\PayhereUtility;
use App\Utility\NotificationUtility;
use Session;
use Auth;

class CheckoutController extends Controller
{

    public function __construct()
    {
        //
    }
    
    
    // ================= CODE ADDED BY KITWOSD TEAM =================
    public function esewa_checkout(Request $request) {


        $business_settings = BusinessSetting::where('type', $request->payment_option . '_sandbox')->first();
        if ($business_settings ->value === '1') {
            $checkout_data = [];
            $checkout_data['additional_info'] = $request->additional_info;
            $checkout_data['payment_option'] = $request->payment_option;

            $checkout_data = json_encode($checkout_data);
            Session::put('checkout_data', $checkout_data);
            
            return response([
                'data' => 'start-esewa-processing'
            ], 200);
        } else {
            return response([
                'data' => 'stop-esewa-processing'
            ], 200);
        }


        
    }
    // ================= ************************** =================

    //check the selected payment gateway and redirect to that controller accordingly
    public function checkout(Request $request)
    {
        // Minumum order amount check
        if(get_setting('minimum_order_amount_check') == 1){
            $subtotal = 0;
            foreach (Cart::where('user_id', Auth::user()->id)->get() as $key => $cartItem){ 
                $product = Product::find($cartItem['product_id']);
                $subtotal += cart_product_price($cartItem, $product, false, false) * $cartItem['quantity'];
            }
            if ($subtotal < get_setting('minimum_order_amount')) {
                flash(translate('You order amount is less then the minimum order amount'))->warning();
                return redirect()->route('home');
            }
        }
        // Minumum order amount check end      
        
        if ($request->payment_option != null) {
            (new OrderController)->store($request);

            $request->session()->put('payment_type', 'cart_payment');
            
            $data['combined_order_id'] = $request->session()->get('combined_order_id');
            $request->session()->put('payment_data', $data);

            if ($request->session()->get('combined_order_id') != null) {

                // If block for Online payment, wallet and cash on delivery. Else block for Offline payment
                $decorator = __NAMESPACE__ . '\\Payment\\' . str_replace(' ', '', ucwords(str_replace('_', ' ', $request->payment_option))) . "Controller";
                if (class_exists($decorator)) {
                    return (new $decorator)->pay($request);
                }
                else {
                    $combined_order = CombinedOrder::findOrFail($request->session()->get('combined_order_id'));
                    $manual_payment_data = array(
                        'name'   => $request->payment_option,
                        'amount' => $combined_order->grand_total,
                        'trx_id' => $request->trx_id,
                        'photo'  => $request->photo
                    );
                    foreach ($combined_order->orders as $order) {
                        $order->manual_payment = 1;
                        $order->manual_payment_data = json_encode($manual_payment_data);
                        $order->save();
                    }
                    flash(translate('Your order has been placed successfully. Please submit payment information from purchase history'))->success();
                    return redirect()->route('order_confirmed');
                }
            }
        } else {
            flash(translate('Select Payment Option.'))->warning();
            return back();
        }
    }

    //redirects to this method after a successfull checkout
    public function checkout_done($combined_order_id, $payment)
    {
        $combined_order = CombinedOrder::findOrFail($combined_order_id);

        foreach ($combined_order->orders as $key => $order) {
            $order = Order::findOrFail($order->id);
            $order->payment_status = 'paid';
            $order->payment_details = $payment;
            $order->save();

            calculateCommissionAffilationClubPoint($order);
        }
        Session::put('combined_order_id', $combined_order_id);
        return redirect()->route('order_confirmed');
    }

    public function get_shipping_info(Request $request)
    {
        $carts = Cart::where('user_id', Auth::user()->id)->get();
//        if (Session::has('cart') && count(Session::get('cart')) > 0) {
        if ($carts && count($carts) > 0) {
            $categories = Category::all();
            $products = [];

            foreach($carts as $cart){
                $product = Product::findOrFail($cart->product_id);
                if($product->local_shipping==1){

                    array_push($products, $product);
                }

            }
            return view('frontend.shipping_info', compact('categories', 'carts', 'products'));
        }
        flash(translate('Your cart is empty'))->success();
        return back();
    }

    public function store_shipping_info(Request $request)
    {
        if ($request->address_id == null) {
            flash(translate("Please add shipping address"))->warning();
            return back();
        }

        $carts = Cart::where('user_id', Auth::user()->id)->get();
        if($carts->isEmpty()) {
            flash(translate('Your cart is empty'))->warning();
            return redirect()->route('home');
        }

        foreach ($carts as $key => $cartItem) {
            $cartItem->address_id = $request->address_id;
            $cartItem->save();
        }

        $carrier_list = array();
        if(get_setting('shipping_type') == 'carrier_wise_shipping'){
            $zone = \App\Models\Country::where('id',$carts[0]['address']['country_id'])->first()->zone_id;

            $carrier_query = Carrier::query();
            $carrier_query->whereIn('id',function ($query) use ($zone) {
                $query->select('carrier_id')->from('carrier_range_prices')
                ->where('zone_id', $zone);
            })->orWhere('free_shipping', 1);
            $carrier_list = $carrier_query->get();
        }
        
        return view('frontend.delivery_info', compact('carts','carrier_list'));
    }

    public function store_delivery_info(Request $request)
    {
        $carts = Cart::where('user_id', Auth::user()->id)
                ->get();

        if($carts->isEmpty()) {
            flash(translate('Your cart is empty'))->warning();
            return redirect()->route('home');
        }

        $shipping_info = Address::where('id', $carts[0]['address_id'])->first();
        $total = 0;
        $tax = 0;
        $shipping = 0;
        $subtotal = 0;

        if ($carts && count($carts) > 0) {
            foreach ($carts as $key => $cartItem) {
                $product = Product::find($cartItem['product_id']);
                $tax += cart_product_tax($cartItem, $product,false) * $cartItem['quantity'];
                $subtotal += cart_product_price($cartItem, $product, false, false) * $cartItem['quantity'];

                if(get_setting('shipping_type') != 'carrier_wise_shipping' || $request['shipping_type_' . $product->user_id] == 'pickup_point'){
                    if ($request['shipping_type_' . $product->user_id] == 'pickup_point') {
                        $cartItem['shipping_type'] = 'pickup_point';
                        $cartItem['pickup_point'] = $request['pickup_point_id_' . $product->user_id];
                    } else {
                        $cartItem['shipping_type'] = 'home_delivery';
                    }
                    $cartItem['shipping_cost'] = 0;
                    if ($cartItem['shipping_type'] == 'home_delivery') {
                        $cartItem['shipping_cost'] = getShippingCost($carts, $key);
                    }
                }
                else{
                    $cartItem['shipping_type'] = 'carrier';
                    $cartItem['carrier_id'] = $request['carrier_id_' . $product->user_id];
                    $cartItem['shipping_cost'] = getShippingCost($carts, $key, $cartItem['carrier_id']);
                }

                $shipping += $cartItem['shipping_cost'];
                $cartItem->save();
            }
            $total = $subtotal + $tax + $shipping;

            return view('frontend.payment_select', compact('carts', 'shipping_info', 'total'));

        } else {
            flash(translate('Your Cart was empty'))->warning();
            return redirect()->route('home');
        }
    }

    public function apply_coupon_code(Request $request)
    {
        $coupon = Coupon::where('code', $request->code)->first();
        $response_message = array();

        if ($coupon != null) {
            if (strtotime(date('d-m-Y')) >= $coupon->start_date && strtotime(date('d-m-Y')) <= $coupon->end_date) {
                if (CouponUsage::where('user_id', Auth::user()->id)->where('coupon_id', $coupon->id)->first() == null) {
                    $coupon_details = json_decode($coupon->details);

                    $carts = Cart::where('user_id', Auth::user()->id)
                                    ->where('owner_id', $coupon->user_id)
                                    ->get();

                    $coupon_discount = 0;
                    
                    if ($coupon->type == 'cart_base') {
                        $subtotal = 0;
                        $tax = 0;
                        $shipping = 0;
                        foreach ($carts as $key => $cartItem) { 
                            $product = Product::find($cartItem['product_id']);
                            $subtotal += cart_product_price($cartItem, $product, false, false) * $cartItem['quantity'];
                            $tax += cart_product_tax($cartItem, $product,false) * $cartItem['quantity'];
                            $shipping += $cartItem['shipping_cost'];
                        }
                        $sum = $subtotal + $tax + $shipping;
                        if ($sum >= $coupon_details->min_buy) {
                            if ($coupon->discount_type == 'percent') {
                                $coupon_discount = ($sum * $coupon->discount) / 100;
                                if ($coupon_discount > $coupon_details->max_discount) {
                                    $coupon_discount = $coupon_details->max_discount;
                                }
                            } elseif ($coupon->discount_type == 'amount') {
                                $coupon_discount = $coupon->discount;
                            }

                        }
                    } elseif ($coupon->type == 'product_base') {
                        foreach ($carts as $key => $cartItem) { 
                            $product = Product::find($cartItem['product_id']);
                            foreach ($coupon_details as $key => $coupon_detail) {
                                if ($coupon_detail->product_id == $cartItem['product_id']) {
                                    if ($coupon->discount_type == 'percent') {
                                        $coupon_discount += (cart_product_price($cartItem, $product, false, false) * $coupon->discount / 100) * $cartItem['quantity'];
                                    } elseif ($coupon->discount_type == 'amount') {
                                        $coupon_discount += $coupon->discount * $cartItem['quantity'];
                                    }
                                }
                            }
                        }
                    }

                    if($coupon_discount > 0){
                        Cart::where('user_id', Auth::user()->id)
                            ->where('owner_id', $coupon->user_id)
                            ->update(
                                [
                                    'discount' => $coupon_discount / count($carts),
                                    'coupon_code' => $request->code,
                                    'coupon_applied' => 1
                                ]
                            );
                        $response_message['response'] = 'success';
                        $response_message['message'] = translate('Coupon has been applied');
                    }
                    else{
                        $response_message['response'] = 'warning';
                        $response_message['message'] = translate('This coupon is not applicable to your cart products!');
                    }
                    
                } else {
                    $response_message['response'] = 'warning';
                    $response_message['message'] = translate('You already used this coupon!');
                }
            } else {
                $response_message['response'] = 'warning';
                $response_message['message'] = translate('Coupon expired!');
            }
        } else {
            $response_message['response'] = 'danger';
            $response_message['message'] = translate('Invalid coupon!');
        }

        $carts = Cart::where('user_id', Auth::user()->id)
                ->get();
        $shipping_info = Address::where('id', $carts[0]['address_id'])->first();

        $returnHTML = view('frontend.partials.cart_summary', compact('coupon', 'carts', 'shipping_info'))->render();
        return response()->json(array('response_message' => $response_message, 'html'=>$returnHTML));
    }

    public function remove_coupon_code(Request $request)
    {
        Cart::where('user_id', Auth::user()->id)
                ->update(
                        [
                            'discount' => 0.00,
                            'coupon_code' => '',
                            'coupon_applied' => 0
                        ]
        );

        $coupon = Coupon::where('code', $request->code)->first();
        $carts = Cart::where('user_id', Auth::user()->id)
                ->get();

        $shipping_info = Address::where('id', $carts[0]['address_id'])->first();

        return view('frontend.partials.cart_summary', compact('coupon', 'carts', 'shipping_info'));
    }

    public function apply_club_point(Request $request) {
        if (addon_is_activated('club_point')){

            $point = $request->point;

            if(Auth::user()->point_balance >= $point) {
                $request->session()->put('club_point', $point);
                flash(translate('Point has been redeemed'))->success();
            }
            else {
                flash(translate('Invalid point!'))->warning();
            }
        }
        return back();
    }

    public function remove_club_point(Request $request) {
        $request->session()->forget('club_point');
        return back();
    }

    public function order_confirmed()
    {
        $combined_order = CombinedOrder::findOrFail(Session::get('combined_order_id'));

        Cart::where('user_id', $combined_order->user_id)
                ->delete();

        //Session::forget('club_point');
        //Session::forget('combined_order_id');
        
        foreach($combined_order->orders as $order){
            NotificationUtility::sendOrderPlacedNotification($order);
        }

        return view('frontend.order_confirmed', compact('combined_order'));
    }


    public function order_confirmed_by_esewa(Request $request)
    {

        $subtotal = 0;
        $tax = 0;
        $shipping = 0;
        $product_shipping_cost = 0;

        $carts = Cart::where('user_id', Auth::user()->id)
                ->get();


        foreach ($carts as $key => $cartItem){
            $product = \App\Models\Product::find($cartItem['product_id']);
            $subtotal += cart_product_price($cartItem, $product, false, false) * $cartItem['quantity'];
            $tax += cart_product_tax($cartItem, $product, false) * $cartItem['quantity'];
            $product_shipping_cost = $cartItem['shipping_cost'];
            
            $shipping += $product_shipping_cost;
            
            $product_name_with_choice = $product->getTranslation('name');
            if ($cartItem['variant'] != null) {
                $product_name_with_choice = $product->getTranslation('name') . ' - ' . $cartItem['variant'];
            }
        }

        $total = $subtotal + $tax + $shipping;

        if (Session::has('club_point')) {
            $total -= Session::get('club_point');
        }

        $coupon_discount = 0;
        
        if (Auth::check() && get_setting('coupon_system') == 1){
            $coupon_code = null;
                
            foreach ($carts as $key => $cartItem){
                $product = \App\Models\Product::find($cartItem['product_id']);
                    
                if ($cartItem->coupon_applied == 1){
                    $coupon_code = $cartItem->coupon_code;
                            break;
                }
                    
                
                $coupon_discount = carts_coupon_discount($coupon_code);
            }
        }
          
        if ($coupon_discount > 0) {
            $total -= $coupon_discount;
        }

        $url = "https://uat.esewa.com.np/epay/transrec";
        $data =[
            'amt'=> $total,
            'rid'=> $request->refId,
            'pid'=>$request->oid,
            'scd'=> 'EPAYTEST'
        ];

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curl);
        curl_close($curl);


        $response = json_decode(json_encode(simplexml_load_string($response)), TRUE);
        $response_code = trim(strtolower($response['response_code']));

        if($response_code==='success'){

            $checkout_request = json_decode(Session::get('checkout_data'));

            (new OrderController)->esewa_khalti_order_store($checkout_request);

            Session::put('payment_type', 'cart_payment');
            
            $checkout_data['combined_order_id'] = Session::get('combined_order_id');
            $combined_order_id = Session::get('combined_order_id');
            Session::put('payment_data', $checkout_data);
            
            $combined_order = CombinedOrder::findOrFail($combined_order_id);

            // Session::put('combined_order_id', $combined_order_id);

            // dd($combined_order);



            
            foreach ($combined_order->orders as $order){
                $combinedOrderId = $order->combined_order_id;

                $order = \App\Models\Order::where('combined_order_id', $combinedOrderId)->first();
                
                $order->payment_type = 'esewa';
                $orderDetails = \App\Models\OrderDetail::where('order_id', $order->id)->first();

                
                $order->payment_status = 'paid';
                $orderDetails->payment_status = 'paid';
                $order->save();
                $orderDetails->save();
            }

            Cart::where('user_id', $combined_order->user_id)
                    ->delete();

            //Session::forget('club_point');
            //Session::forget('combined_order_id');
            
            foreach($combined_order->orders as $order){
                NotificationUtility::sendOrderPlacedNotification($order);
            }

            return view('frontend.order_confirmed', compact('combined_order'));
        } else  {
            flash(translate('Esewa Payment Failed'))->error();
            return redirect()->route('home');
        }
 
    }


    public function khalti_verify(Request $request)
    {
        
        

        $subtotal = 0;
        $tax = 0;
        $shipping = 0;
        $product_shipping_cost = 0;

        $carts = Cart::where('user_id', Auth::user()->id)
                ->get();


        foreach ($carts as $key => $cartItem){
            $product = \App\Models\Product::find($cartItem['product_id']);
            $subtotal += cart_product_price($cartItem, $product, false, false) * $cartItem['quantity'];
            $tax += cart_product_tax($cartItem, $product, false) * $cartItem['quantity'];
            $product_shipping_cost = $cartItem['shipping_cost'];
            
            $shipping += $product_shipping_cost;
            
            $product_name_with_choice = $product->getTranslation('name');
            if ($cartItem['variant'] != null) {
                $product_name_with_choice = $product->getTranslation('name') . ' - ' . $cartItem['variant'];
            }
        }

        $total = $subtotal + $tax + $shipping;

        if (Session::has('club_point')) {
            $total -= Session::get('club_point');
        }

        $coupon_discount = 0;
        
        if (Auth::check() && get_setting('coupon_system') == 1){
            $coupon_code = null;
                
            foreach ($carts as $key => $cartItem){
                $product = \App\Models\Product::find($cartItem['product_id']);
                    
                if ($cartItem->coupon_applied == 1){
                    $coupon_code = $cartItem->coupon_code;
                            break;
                }
                    
                
                $coupon_discount = carts_coupon_discount($coupon_code);
            }
        }
          
        if ($coupon_discount > 0) {
            $total -= $coupon_discount;
        }

        $totalamt = intval($total*100);

        $args = http_build_query(array(
            'token' => $request->token,
            'amount'  => $totalamt
        ));
        
        $url = "https://khalti.com/api/v2/payment/verify/";
        
        # Make the call using API.
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$args);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        
        $headers = ['Authorization: Key test_secret_key_f6af6906d99d49ef886501fcdea8a27d'];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        // Response
        $response = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        
        $response = json_decode($response);
        

        if($response->amount===$totalamt){

            $checkout_request = json_decode(Session::get('checkout_data'));

            (new OrderController)->esewa_khalti_order_store($checkout_request);

            Session::put('payment_type', 'cart_payment');
            
            $checkout_data['combined_order_id'] = Session::get('combined_order_id');
            $combined_order_id = Session::get('combined_order_id');
            Session::put('payment_data', $checkout_data);
            
            $combined_order = CombinedOrder::findOrFail($combined_order_id);


            foreach ($combined_order->orders as $order){
                $combinedOrderId = $order->combined_order_id;

                $order = \App\Models\Order::where('combined_order_id', $combinedOrderId)->first();
                
                $order->payment_type = 'khalti';
                $orderDetails = \App\Models\OrderDetail::where('order_id', $order->id)->first();

                
                $order->payment_status = 'paid';
                $orderDetails->payment_status = 'paid';
                $order->save();
                $orderDetails->save();
            }

            Cart::where('user_id', $combined_order->user_id)
                    ->delete();

            //Session::forget('club_point');
            //Session::forget('combined_order_id');
            
            foreach($combined_order->orders as $order){
                NotificationUtility::sendOrderPlacedNotification($order);
            }

            



            return response()->json(array('response_message' => 'Khalti Payment Success', 'response'=>'success'));
        } else  {
            $response_message['response'] = 'warning';
            $response_message['message'] = 'Khalti Payment Failed';
            return response()->json(array('response_message' => $response_message, 'response'=>'error'));
        }
 
    }


    public function order_confirmed_by_khalti(Request $request)
    {
        
        $combined_order = CombinedOrder::findOrFail(Session::get('combined_order_id'));
            foreach ($combined_order->orders as $order){
                $combinedOrderId = $order->combined_order_id;

                $order = \App\Models\Order::where('combined_order_id', $combinedOrderId)->first();
                
                

                
                ;
                
            }

        

        if($order->payment_status === 'paid'){

            

            

            return view('frontend.order_confirmed', compact('combined_order'));
        } else  {
            flash(translate('Khalti Payment Failed'))->error();
            return redirect()->route('home');
        }
 
    }
}
