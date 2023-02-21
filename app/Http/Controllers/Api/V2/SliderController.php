<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Resources\V2\SliderCollection;
use App\Models\Category;
use Cache;

class SliderController extends Controller
{
    public function sliders()
    {
        $array = [];
            $key = 0;
            $images = json_decode(get_setting('home_slider_images'), true);
            $types = json_decode(get_setting('home_slider_type'), true);
            $ids = json_decode(get_setting('home_slider_item_id'), true);
            foreach($images as $image){
                $data = [];
                $data['image'] = $image;
                $data['type'] = $types[$key];
                $data['id'] = $ids[$key];
                if($types[$key]=='1'){

                    $name = Category::where('id', $ids[$key])->first()->name;
                } else {
                    $name = '';
                }

                $data['name'] = $name;
                array_push($array, $data);
                $key = $key +1;
            }

            // dd($array);
            
            return new SliderCollection($array);
    }

    public function bannerOne()
    {
        return Cache::remember('app.home_banner1_images', 86400, function(){
            
            return new SliderCollection(json_decode(get_setting('home_banner1_images'), true));
        });
    }

    public function bannerTwo()
    {
        return Cache::remember('app.home_banner2_images', 86400, function(){
            return new SliderCollection(json_decode(get_setting('home_banner2_images'), true));
        });
    }

    public function bannerThree()
    {
        return Cache::remember('app.home_banner3_images', 86400, function(){
            return new SliderCollection(json_decode(get_setting('home_banner3_images'), true));
        });
    }
}
