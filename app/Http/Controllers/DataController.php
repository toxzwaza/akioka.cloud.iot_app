<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Computer;
use App\Models\Data;
use Exception;
use Illuminate\Http\Request;

class DataController extends Controller
{
    //
    public function store(Request $request)
    {
        $status = false;
        $msg = '';

        $temperature = $request->temperature;
        $humidity = $request->humidity;
        $co2 = $request->co2;
        $place_id = $request->place_id;

        try {
            if ($temperature && $humidity && $co2 && $place_id) {
                $data = new Data();
                $data->temperature = $temperature;
                $data->humidity = $humidity;
                $data->co2 = $co2;
                $data->place_id = $place_id;
                $data->save();
                $status = true;
            }
        } catch (Exception $e) {
            $msg = $e->getMessage();
        }

        return response()->json(['status' => $status, 'msg' => $msg ]);
    }

    public function getPlaceId(Request $request)
    {
        $mac_address = $request->mac_address ?? '';

        $place = Computer::where('mac_address', $mac_address)->first();
        if ($place) {
            return response()->json(['placeId' => $place->id]);
        } else {
            return response()->json(['placeId' => 0]);
        }
    }
}
