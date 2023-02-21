<?php

namespace App\Http\Resources\V2;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PurchaseHistoryMiniCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'data' => $this->collection->map(function($data) {
                
                $delivery_status = $data->delivery_status == 'pending'? "Order Placed" : ucwords(str_replace('_', ' ',  $data->delivery_status));
                
                if(!($data->tracking_code===null || $data->tracking_code==="")){
                    $response = Http::withHeaders([
                            'Authorization' => 'Token dafc4c5ea79ed44524e74c410673d7291b0b69ed',
                            'Content-Type' => 'application/json'
                        ])->get("https://portal.nepalcanmove.com/api/v1/order?id=$data->tracking_code");
                        
                    if($response->status()==200){
                        
                        $delivery_status = $response["last_delivery_status"];
                        
                    }
                    
                }
                
                
                return [
                    'id' => $data->id,
                    'code' => $data->code,
                    'user_id' => intval($data->user_id),
                    'payment_type' => ucwords(str_replace('_', ' ', $data->payment_type)) ,
                    'payment_status' => $data->payment_status,
                    'payment_status_string' => ucwords(str_replace('_', ' ', $data->payment_status)),
                    'delivery_status' => $data->delivery_status,
                    'delivery_status_string' => $delivery_status,
                    'grand_total' => format_price($data->grand_total) ,
                    'date' => Carbon::createFromTimestamp($data->date)->format('d-m-Y'),
                    'links' => [
                        'details' => ''
                    ]
                ];
            })
        ];
    }

    public function with($request)
    {
        return [
            'success' => true,
            'status' => 200
        ];
    }
}
