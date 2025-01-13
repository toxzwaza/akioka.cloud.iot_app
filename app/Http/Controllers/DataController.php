<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Computer;
use App\Models\Data;
use Illuminate\Http\Request;

class DataController extends Controller
{
    //
    public function store(Request $request)
    {
        $status = false;

        $temperature = $request->temperature;
        $humidity = $request->humidity;
        $co2 = $request->co2;
        $placeNo = $request->placeNo;

        if($temperature && $humidity && $co2 && $placeNo){
            $data = new Data();
            $data->temperature = $temperature;
            $data->humidity = $humidity;
            $data->co2 = $co2;
            $data->place_id = $placeNo;
            $data->save();
            $status = true;
        }

        return response()->json(['status' => $status]);

    }

    public function getPlaceId(Request $request){
        $mac_address = $request->mac_address ?? '';

        $place = Computer::where('mac_address', $mac_address)->first();
        if($place){
            return response()->json(['placeId' => $place->id]);
        }else{
            return response()->json(['placeId' => 0]);
        }
    }
}
