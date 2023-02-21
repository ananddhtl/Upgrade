<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\City;
use App\Models\Area;
use App\Models\CityTranslation;
use App\Models\AreaTranslation;
use App\Models\State;

class AreaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $sort_area = $request->sort_area;
        $sort_city = $request->sort_city;
        $areas_queries = Area::query();
        if($request->sort_area) {
            $areas_queries->where('name', 'like', "%$sort_area%");
        }
        if($request->sort_city) {
            $areas_queries->where('city_id', $request->sort_city);
        }
        $areas = $areas_queries->orderBy('status', 'desc')->paginate(15);
        $cities = City::where('status', 1)->get();

        return view('backend.setup_configurations.areas.index', compact('areas', 'cities', 'sort_area', 'sort_city'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $area = new Area;

        $area->name = $request->name;
        $area->cost = $request->cost;
        $area->city_id = $request->city_id;

        $area->save();

        flash(translate('Area has been inserted successfully'))->success();

        return back();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
     public function edit(Request $request, $id)
     {
         
         
         $lang  = $request->lang;
         $area  = Area::findOrFail($id);
         $cities = City::where('status', 1)->get();
         
         
         return view('backend.setup_configurations.areas.edit', compact('area', 'lang', 'cities'));
     }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        
        
        $area = Area::findOrFail($id);
        if($request->lang == env("DEFAULT_LANGUAGE")){
            $area->name = $request->name;
        }

        $area->city_id = $request->city_id;
        $area->cost = $request->cost;

        $area->save();

        

        flash(translate('Area has been updated successfully'))->success();
        return back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // $area = Area::findOrFail($id);

        // foreach ($area->area_translations as $key => $area_translation) {
        //     $area_translation->delete();
        // }

        Area::destroy($id);

        flash(translate('Area has been deleted successfully'))->success();
        return redirect()->route('areas.index');
    }

    public function updateStatus(Request $request){
        $area = Area::findOrFail($request->id);
        $area->status = $request->status;
        $area->save();

        return 1;
    }
}
