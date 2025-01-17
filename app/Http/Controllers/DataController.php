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

        return response()->json(['status' => $status, 'msg' => $msg]);
    }

    public function getPlaceId(Request $request)
    {
        $mac_address = $request->mac_address ?? '';

        $place = Computer::where('mac_address', $mac_address)->first();
        if ($place) {
            return response()->json([$place->id]);
        } else {
            return response()->json([0]);
        }
    }


    // 最新のデータを取得
    public function getLatestData()
    {
        $computers = Computer::select('computers.name as computer_name', 'computers.place_id', 'places.name as place_name')->join('places', 'computers.place_id', 'places.id')->get();

        $data = [];

        foreach ($computers as $computer) {
            $latest_data = Data::where('place_id', $computer->place_id)->orderBy('id', 'desc')->first();
            if ($latest_data) {
                $latest_data->computer_name = $computer->computer_name;
                $latest_data->place_id = $computer->place_id;
                $latest_data->place_name = $computer->place_name;
                $data[] = $latest_data;
            }
        }

        return response()->json($data);
    }

    public function getTempHumiCo2()
    {
        try {
            $computers = Computer::select('computers.name as computer_name', 'computers.place_id', 'places.name as place_name')
                ->join('places', 'computers.place_id', 'places.id')
                ->get();

            $result = [
                'temperature' => [],
                'humidity' => [],
                'co2' => []
            ];

            foreach ($computers as $computer) {
                $hourlyData = Data::selectRaw('
                    place_id,
                    DATE_FORMAT(created_at, "%Y-%m-%d %H:00:00") as hour,
                    AVG(temperature) as avg_temperature,
                    AVG(humidity) as avg_humidity, 
                    AVG(co2) as avg_co2
                ')
                    ->where('place_id', $computer->place_id)
                    ->groupBy('place_id', 'hour')
                    ->orderBy('hour', 'asc')
                    ->get();

                if ($hourlyData->count() > 0) {
                    $result['temperature'][] = [
                        'computer_name' => $computer->computer_name,
                        'place_id' => $computer->place_id,
                        'place_name' => $computer->place_name,
                        'data' => $hourlyData->pluck('avg_temperature')
                    ];
                    
                    $result['humidity'][] = [
                        'computer_name' => $computer->computer_name,
                        'place_id' => $computer->place_id,
                        'place_name' => $computer->place_name,
                        'data' => $hourlyData->pluck('avg_humidity')
                    ];
                    
                    $result['co2'][] = [
                        'computer_name' => $computer->computer_name,
                        'place_id' => $computer->place_id,
                        'place_name' => $computer->place_name,
                        'data' => $hourlyData->pluck('avg_co2')
                    ];
                }
            }
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }

        return response()->json($result);
    }
}
