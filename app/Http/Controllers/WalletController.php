<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Wallet;
use App\Models\BusinessSetting;
use Auth;
use Session;

class WalletController extends Controller
{
    public function __construct() {
        // Staff Permission Check
        $this->middleware(['permission:view_all_offline_wallet_recharges'])->only('offline_recharge_request');
    }

    public function index()
    {
        $wallets = Wallet::where('user_id', Auth::user()->id)->latest()->paginate(9);
        return view('frontend.user.wallet.index', compact('wallets'));
    }
    public function online_recharge(Request $request)
    {
        $business_settings = BusinessSetting::where('type', $request->payment_option . '_sandbox')->first();
        if ($business_settings ->value === '1') {
            $data['amount'] = $request->amount;
            $data['payment_method'] = $request->payment_option;
            Session::put('payment_data', $data);
            
            return response([
                'data' => 'start-online-recharge-processing'
            ], 200);
        } else {
            return response([
                'data' => 'stop-online-recharge-processing'
            ], 200);
        }
    }

    public function recharge_confirmed_by_esewa(Request $request)
    {

        $data = Session::get('payment_data');
        
        $payment_method = $data['payment_method'];
        $total = $data['amount'];

        $url = env('ESEWA_VERIFICATION_URL');
        $data =[
            'amt'=> $total,
            'rid'=> $request->refId,
            'pid'=>$request->oid,
            'scd'=> env('ESEWA_CLIENT_ID')
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

            $user = Auth::user();
            $user->balance = $user->balance + $total;
            $user->save();

            $wallet = new Wallet;
            $wallet->user_id = $user->id;
            $wallet->amount = $total;
            $wallet->payment_method = $payment_method;
            $wallet->payment_details = 'recharge done by esewa';
            $wallet->approval = true;
            $wallet->save();

            Session::forget('payment_data');

            flash(translate('Esewa Payment completed'))->success();
            return redirect()->route('wallet.index');
        } else  {
            flash(translate('Esewa Payment Failed'))->error();
            return redirect()->route('home');
        }
 
    }

    public function khalti_verify_recharge(Request $request)
    {
        
        $data = Session::get('payment_data');
        
        $payment_method = $data['payment_method'];
        $total = $data['amount'];

        

        $totalamt = intval($total*100);

        $args = http_build_query(array(
            'token' => $request->token,
            'amount'  => $totalamt
        ));
        
        $url = env('KHALTI_VERIFICTION_URL');
        
        # Make the call using API.
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$args);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        
        $headers = ['Authorization: Key '.env('KHALTI_SECRET_KEY')];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        // Response
        $response = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        
        $response = json_decode($response);
        

        if($response->amount===$totalamt){

            $user = Auth::user();
            $user->balance = $user->balance + $total;
            $user->save();

            $wallet = new Wallet;
            $wallet->user_id = $user->id;
            $wallet->amount = $total;
            $wallet->payment_method = $payment_method;
            $wallet->payment_details = 'recharge done by khalti';
            $wallet->approval = true;
            $wallet->save();

            Session::forget('payment_data');

            



            return response()->json(array('response_message' => 'Khalti Payment Success', 'response'=>'success'));
        } else  {
            $response_message['response'] = 'warning';
            $response_message['message'] = 'Khalti Payment Failed';
            return response()->json(array('response_message' => $response_message, 'response'=>'error'));
        }
 
    }

    public function recharge_confirmed_by_khalti(Request $request)
    {
        
        
        

        Session::forget('payment_data');

            flash(translate('Khalti Payment completed'))->success();
            return redirect()->route('wallet.index');
 
    }

    public function recharge(Request $request)
    {
        $data['amount'] = $request->amount;
        $data['payment_method'] = $request->payment_option;

        $request->session()->put('payment_type', 'wallet_payment');
        $request->session()->put('payment_data', $data);

        $request->session()->put('payment_type', 'wallet_payment');
        $request->session()->put('payment_data', $data);

        $decorator = __NAMESPACE__ . '\\Payment\\' . str_replace(' ', '', ucwords(str_replace('_', ' ', $request->payment_option))) . "Controller";
        if (class_exists($decorator)) {
            return (new $decorator)->pay($request);
        }
    }

    public function wallet_payment_done($payment_data, $payment_details)
    {
        $user = Auth::user();
        $user->balance = $user->balance + $payment_data['amount'];
        $user->save();

        $wallet = new Wallet;
        $wallet->user_id = $user->id;
        $wallet->amount = $payment_data['amount'];
        $wallet->payment_method = $payment_data['payment_method'];
        $wallet->payment_details = $payment_details;
        $wallet->save();

        Session::forget('payment_data');
        Session::forget('payment_type');

        flash(translate('Payment completed'))->success();
        return redirect()->route('wallet.index');
    }

    public function offline_recharge(Request $request)
    {
        $wallet = new Wallet;
        $wallet->user_id = Auth::user()->id;
        $wallet->amount = $request->amount;
        $wallet->payment_method = $request->payment_option;
        $wallet->payment_details = $request->trx_id;
        $wallet->approval = 0;
        $wallet->offline_payment = 1;
        $wallet->reciept = $request->photo;
        $wallet->save();
        flash(translate('Offline Recharge has been done. Please wait for response.'))->success();
        return redirect()->route('wallet.index');
    }

    public function offline_recharge_request()
    {
        $wallets = Wallet::where('offline_payment', 1)->paginate(10);
        return view('manual_payment_methods.wallet_request', compact('wallets'));
    }

    public function updateApproved(Request $request)
    {
        $wallet = Wallet::findOrFail($request->id);
        $wallet->approval = $request->status;
        if ($request->status == 1) {
            $user = $wallet->user;
            $user->balance = $user->balance + $wallet->amount;
            $user->save();
        } else {
            $user = $wallet->user;
            $user->balance = $user->balance - $wallet->amount;
            $user->save();
        }
        if ($wallet->save()) {
            return 1;
        }
        return 0;
    }
}
