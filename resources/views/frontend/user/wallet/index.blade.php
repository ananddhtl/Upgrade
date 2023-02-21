@extends('frontend.layouts.user_panel')

@section('panel_content')

<?php
use App\Models\BusinessSetting;

$esewa_settings = BusinessSetting::where('type', 'esewa_sandbox')->first();
$khalti_settings = BusinessSetting::where('type', 'khalti_sandbox')->first();

$esewa_client_id=env('ESEWA_CLIENT_ID');
$esewa_transaction_url=env('ESEWA_TRANSACTION_URL');

$khalti_public_key = env('KHALTI_PUBLIC_KEY');






?>
    <div class="aiz-titlebar mt-2 mb-4">
    <div class="row align-items-center">
      <div class="col-md-6">
          <h1 class="h3">{{ translate('My Wallet') }}</h1>
      </div>
    </div>
    </div>
    <div class="row gutters-10">
      <div class="col-md-4 mx-auto mb-3" >
          <div class="bg-grad-1 text-white rounded-lg overflow-hidden">
            <span class="size-30px rounded-circle mx-auto bg-soft-primary d-flex align-items-center justify-content-center mt-3">
                <i class="las la-dollar-sign la-2x text-white"></i>
            </span>
            <div class="px-3 pt-3 pb-3">
                <div class="h4 fw-700 text-center">{{ single_price(Auth::user()->balance) }}</div>
                <div class="opacity-50 text-center">{{ translate('Wallet Balance') }}</div>
            </div>
          </div>
      </div>
      <div class="col-md-4 mx-auto mb-3" >
        <div class="p-3 rounded mb-3 c-pointer text-center bg-white shadow-sm hov-shadow-lg has-transition" onclick="show_wallet_modal()">
            <span class="size-60px rounded-circle mx-auto bg-secondary d-flex align-items-center justify-content-center mb-3">
                <i class="las la-plus la-3x text-white"></i>
            </span>
            <div class="fs-18 text-primary">{{ translate('Recharge Wallet') }}</div>
        </div>
      </div>
      @if (addon_is_activated('offline_payment'))
          <div class="col-md-4 mx-auto mb-3" >
              <div class="p-3 rounded mb-3 c-pointer text-center bg-white shadow-sm hov-shadow-lg has-transition" onclick="show_make_wallet_recharge_modal()">
                  <span class="size-60px rounded-circle mx-auto bg-secondary d-flex align-items-center justify-content-center mb-3">
                      <i class="las la-plus la-3x text-white"></i>
                  </span>
                  <div class="fs-18 text-primary">{{ translate('Offline Recharge Wallet') }}</div>
              </div>
          </div>
      @endif
    </div>
    <div class="card">
      <div class="card-header">
          <h5 class="mb-0 h6">{{ translate('Wallet recharge history')}}</h5>
      </div>
        <div class="card-body">
            <table class="table aiz-table mb-0">
                <thead>
                  <tr>
                      <th>#</th>
                      <th data-breakpoints="lg">{{  translate('Date') }}</th>
                      <th>{{ translate('Amount')}}</th>
                      <th data-breakpoints="lg">{{ translate('Payment Method')}}</th>
                      <th class="text-right">{{ translate('Approval')}}</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($wallets as $key => $wallet)
                   
                      <tr>
                          <td>{{ $key+1 }}</td>
                          <td>{{ date('d-m-Y', strtotime($wallet->created_at)) }}</td>
                          <td>{{ single_price($wallet->amount) }}</td>
                          <td>{{ ucfirst(str_replace('_', ' ', $wallet ->payment_method)) }}</td>
                          <td class="text-right">
                              
                                  @if ($wallet->approval)
                                      <span class="badge badge-inline badge-success">{{translate('Approved')}}</span>
                                  @else
                                      <span class="badge badge-inline badge-info">{{translate('Pending')}}</span>
                                  @endif
                              
                          </td>
                      </tr>
                  @endforeach

                </tbody>
            </table>
            <div class="aiz-pagination">
                {{ $wallets->links() }}
            </div>
        </div>
    </div>
@endsection

@section('modal')

  <div class="modal fade" id="wallet_modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" role="document">
          <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">{{ translate('Recharge Wallet') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
              </div>
              <form class="form-default" role="form" action="{{ route('wallet.recharge') }}" method="post">
                  @csrf
                  <div class="modal-body gry-bg px-3 pt-3">
                      <div class="row">
                          <div class="col-md-4">
                              <label>{{ translate('Amount')}} <span class="text-danger">*</span></label>
                          </div>
                          <div class="col-md-8">
                              <input type="number" lang="en" class="form-control mb-3" name="amount" placeholder="{{ translate('Amount')}}" required>
                          </div>
                      </div>
                      <div class="row">
                          <div class="col-md-4">
                              <label>{{ translate('Payment Method')}} <span class="text-danger">*</span></label>
                          </div>
                          <div class="col-md-8">
                              <div class="mb-3">
                                  <select class="form-control selectpicker" data-minimum-results-for-search="Infinity" name="payment_option" data-live-search="true">
                                    @if (get_setting('paypal_payment') == 1)
                                        <option value="paypal">{{ translate('Paypal')}}</option>
                                    @endif
                                    @if (get_setting('stripe_payment') == 1)
                                        <option value="stripe">{{ translate('Stripe')}}</option>
                                    @endif
                                    @if (get_setting('mercadopago_payment') == 1)
                                        <option value="mercadopago">{{ translate('Mercadopago')}}</option>
                                    @endif
                                    @if(get_setting('toyyibpay_payment') == 1)
                                        <option value="toyyibpay">{{ translate('ToyyibPay')}}</option>
                                    @endif
                                    @if (get_setting('sslcommerz_payment') == 1)
                                        <option value="sslcommerz">{{ translate('SSLCommerz')}}</option>
                                    @endif
                                    @if (get_setting('instamojo_payment') == 1)
                                        <option value="instamojo">{{ translate('Instamojo')}}</option>
                                    @endif
                                    @if (get_setting('paystack') == 1)
                                        <option value="paystack">{{ translate('Paystack')}}</option>
                                    @endif
                                    @if (get_setting('voguepay') == 1)
                                        <option value="voguepay">{{ translate('VoguePay')}}</option>
                                    @endif
                                    @if (get_setting('payhere') == 1)
                                        <option value="payhere">{{ translate('Payhere')}}</option>
                                    @endif
                                    @if (get_setting('ngenius') == 1)
                                        <option value="ngenius">{{ translate('Ngenius')}}</option>
                                    @endif
                                    @if (get_setting('razorpay') == 1)
                                        <option value="razorpay">{{ translate('Razorpay')}}</option>
                                    @endif
                                    @if (get_setting('iyzico') == 1)
                                        <option value="iyzico">{{ translate('Iyzico')}}</option>
                                    @endif
                                    @if (get_setting('bkash') == 1)
                                        <option value="bkash">{{ translate('Bkash')}}</option>
                                    @endif
                                    @if (get_setting('nagad') == 1)
                                        <option value="nagad">{{ translate('Nagad')}}</option>
                                    @endif
                                    @if (get_setting('payku') == 1)
                                        <option value="payku">{{ translate('Payku')}}</option>
                                    @endif
                                    @if (get_setting('esewa_sandbox') == 1)
                                        <option value="esewa">{{ translate('Esewa')}}</option>
                                    @endif
                                    @if (get_setting('khalti_sandbox') == 1)
                                        <option value="khalti">{{ translate('Khalti')}}</option>
                                    @endif
                                    @if(addon_is_activated('african_pg'))
                                        @if (get_setting('mpesa') == 1)
                                            <option value="mpesa">{{ translate('Mpesa')}}</option>
                                        @endif
                                        @if (get_setting('flutterwave') == 1)
                                            <option value="flutterwave">{{ translate('Flutterwave')}}</option>
                                        @endif
                                        @if (get_setting('payfast') == 1)
                                            <option value="payfast">{{ translate('PayFast')}}</option>
                                        @endif
                                    @endif
                                    @if (addon_is_activated('paytm') && get_setting('paytm_payment'))
                                        <option value="paytm">{{ translate('Paytm')}}</option>
                                    @endif
                                    @if(get_setting('authorizenet') == 1)
                                        <option value="authorizenet">{{ translate('Authorize Net')}}</option>
                                    @endif
                                  </select>
                              </div>
                          </div>
                      </div>
                      <div class="form-group text-right">
                          <button  onclick="submitOrder(this)" class="btn btn-sm btn-primary transition-3d-hover mr-1">{{translate('Confirm')}}</button>
                      </div>
                  </div>
              </form>
          </div>
      </div>
  </div>


  <!-- offline payment Modal -->
  <div class="modal fade" id="offline_wallet_recharge_modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
          <div class="modal-content">
              <div class="modal-header">
                  <h5 class="modal-title" id="exampleModalLabel">{{ translate('Offline Recharge Wallet') }}</h5>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
              </div>
              <div id="offline_wallet_recharge_modal_body"></div>
          </div>
      </div>
  </div>

@endsection

@section('script')
<script src="https://khalti.s3.ap-south-1.amazonaws.com/KPG/dist/2020.12.17.0.0.0/khalti-checkout.iffe.js"></script>
    <script type="text/javascript">
        function show_wallet_modal(){
            $('#wallet_modal').modal('show');
        }

        function show_make_wallet_recharge_modal(){
            $.post('{{ route('offline_wallet_recharge_modal') }}', {_token:'{{ csrf_token() }}'}, function(data){
                $('#offline_wallet_recharge_modal_body').html(data);
                $('#offline_wallet_recharge_modal').modal('show');
            });
        }
        function submitOrder(el) {
            $(el).prop('disabled', true);
            if ($("input[name='amount']").val() < 10) {
                    $(el).prop('disabled', false);
                    AIZ.plugins.notify('danger',
                        '{{ translate('You Recharge amount is less than the minimum recharge  amount') }}');
            } else {
                

                
                if ($("select[name='payment_option']").val()=='esewa') {
                        $(document).ready(function() {
                            var data = new FormData();


                            
                            data.append('amount', $("input[name='amount']").val());
                            data.append('payment_option', $("select[name='payment_option']").val());

                            
                
                
                            $.ajax({
                                headers: {
                                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                },
                                method: "POST",
                                url: "{{ route('recharge.online') }}",
                                data: data,
                                cache: false,
                                contentType: false,
                                processData: false,
                                success: function(data, textStatus, jqXHR) {
                                    if (data['data'] == 'start-online-recharge-processing') {
                                        
                                        
                                        var amount = $("input[name='amount']").val();
                                        
                                    
                                        var path= "<?php echo $esewa_transaction_url; ?>";
                                        var params= {
                                            amt: amount,
                                            psc: 0,
                                            pdc: 0,
                                            txAmt: 0,
                                            tAmt: amount,
                                            pid: parseInt(Math.random() * 1000000) + "-"+ parseInt(Math.random() * 1000) +"-"+ parseInt(Math.random() * 1000) +"-"+ parseInt(Math.random() * 1000) +"-"+ parseInt(Math.random() * 1000000),
                                            scd: "<?php echo $esewa_client_id; ?>",
                                            su: "{{ route('recharge_confirmed_by_esewa') }}",
                                            fu: "{{ route('home') }}"
                                        }
                                        
                                        function post(path, params) {
                                            var form = document.createElement("form");
                                            form.setAttribute("method", "POST");
                                            form.setAttribute("action", path);
                                        
                                            for(var key in params) {
                                                var hiddenField = document.createElement("input");
                                                hiddenField.setAttribute("type", "hidden");
                                                hiddenField.setAttribute("name", key);
                                                hiddenField.setAttribute("value", params[key]);
                                                form.appendChild(hiddenField);
                                            }
                                        
                                            document.body.appendChild(form);
                                            form.submit();
                                        }
                                        
                                        post(path, params);
                                        
                                        
                                        
                                    }
                                }
                            });
                            
                        });
                    } else if ($("select[name='payment_option']").val()=='khalti') {
                        $(document).ready(function() {
                            var data = new FormData();
                            
                            data.append('amount', $("input[name='amount']").val());
                            data.append('payment_option', $("select[name='payment_option']").val());
                
                
                            $.ajax({
                                headers: {
                                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                },
                                method: "POST",
                                url: "{{ route('recharge.online') }}",
                                data: data,
                                cache: false,
                                contentType: false,
                                processData: false,
                                success: function(data, textStatus, jqXHR) {
                                    if (data['data'] == 'start-online-recharge-processing') {
                                        
                                        
                                        var amount = $("input[name='amount']").val();
                        
                                        var config = {
                                            // replace the publicKey with yours
                                            "publicKey": "<?php echo $khalti_public_key; ?>",
                                            "productIdentity": parseInt(Math.random() * 1000000) + "-"+ parseInt(Math.random() * 1000) +"-"+ parseInt(Math.random() * 1000) +"-"+ parseInt(Math.random() * 1000) +"-"+ parseInt(Math.random() * 1000000),
                                            "productName": "Sabxa Product",
                                            "productUrl": "https://sabxa.com",
                                            "paymentPreference": [
                                                "KHALTI",
                                                "EBANKING",
                                                "MOBILE_BANKING",
                                                "CONNECT_IPS",
                                                "SCT",
                                                ],
                                            "eventHandler": {
                                                onSuccess (payload) {

                                                    $('#loadingModal').modal('show');
                                                    $.ajax({
                                                        headers: {
                                                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                                        },
                                                        method: "POST",
                                                        url: "{{ route('recharge_verify.khalti') }}",
                                                        data:{
                                                            token:payload.token,
                                                            amount:payload.amount
                                                        },
                                                        success: function(data, textStatus, jqXHR) {
                                                            if(data.response=='error'){

                                                                AIZ.plugins.notify(data.response_message.response, data.response_message.message);
                                                            }
                                                            if(data.response=='success'){
                                                                location.href = "{{ route('recharge_confirmed_by_khalti') }}";
                                                            }
                                                            
                                                        }
                                                    });
                                                    
                                                    
                                                },
                                                onError (error) {
                                                    location.href = "{{ route('home') }}";
                                                },
                                                onClose () {
                                                    console.log('widget is closing');
                                                }
                                            }
                                        };
                                
                                        var checkout = new KhaltiCheckout(config);
                                        checkout.show({amount: amount*100});
                                        
                                        
                                        
                                    }
                                }
                            });
                            
                        });
                    }
            }

        }
    </script>
@endsection
