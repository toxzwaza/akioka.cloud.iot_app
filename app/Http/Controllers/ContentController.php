<?php

namespace App\Http\Controllers;

use App\Models\Data;
use App\Models\FacilitySchedule;
use App\Models\FacilityScheduleParticipant;
use App\Models\InitialOrder;
use App\Models\Place;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Http;

class ContentController extends Controller
{
    //
    public function watchData(Request $request)
    {
        $place_id = $request->place_id;
        if (!$place_id) {
            // place_idが指定されていない場合、全てを表示する

            return Inertia::render('Content/WatchData');
        }
    }
    public function facilitySchedule()
    {

        return Inertia::render('Content/FacilitySchedule');
    }

    public function getFacilitySchedule()
    {
        $facility_schedules_1 = FacilitySchedule::select('facility_schedules.*', 'facilities.name as facility_name')->join('facilities', 'facilities.id', 'facility_schedules.facility_id')->whereDate('start_date', now()->toDateString())->where('facility_id', 1)->get();
        foreach ($facility_schedules_1 as $schedule) {
            $participants = FacilityScheduleParticipant::where('facility_schedule_id', $schedule->id)->get();
            $schedule->participants =  $participants;
        }

        $facility_schedules_2 = FacilitySchedule::select('facility_schedules.*', 'facilities.name as facility_name')->join('facilities', 'facilities.id', 'facility_schedules.facility_id')->whereDate('start_date', now()->toDateString())->where('facility_id', 2)->get();

        foreach ($facility_schedules_2 as $schedule) {
            $participants = FacilityScheduleParticipant::where('facility_schedule_id', $schedule->id)->get();
            $schedule->participants =  $participants;
        }

        return response()->json(['facility_schedules_1' => $facility_schedules_1, 'facility_schedules_2' => $facility_schedules_2]);
    }

    public function receiveComplete(Request $request)
    {

        $place_id = $request->place_id;

        $place_name = $place_id ? Place::find($place_id)->name : '全て';

        $query = InitialOrder::select(
            'initial_orders.name',
            'initial_orders.s_name',
            'initial_orders.deli_location',
            'initial_orders.delivery_date',
            'initial_orders.order_user',
            'initial_orders.quantity',
            'stocks.img_path',
            'processes.name as process_name',
            'places.id as place_id',
            'places.name as place_name'
        )
            ->leftJoin('stocks', 'stocks.id', 'initial_orders.stock_id')
            ->leftJoin('users', 'users.name', 'initial_orders.order_user')
            ->leftJoin('processes', 'processes.id', 'users.process_id')
            ->leftJoin('places', 'places.id', 'processes.place_id')
            ->where('receive_flg', 1)
            ->where('receipt_flg', 0)
            ->where('initial_orders.del_flg', 0)
            ->orderby('initial_orders.updated_at', 'desc');

        if ($place_id) {
            $query->where('places.id', $place_id);
        }

        $initial_orders = $query->get();

        return Inertia::render('Content/ReceiveComplete', ['initial_orders' => $initial_orders, 'place_name' => $place_name]);
    }
    public function receiveCompleteBack(Request $request)
    {

        $place_id = $request->place_id;

        $place_name = $place_id ? Place::find($place_id)->name : '全て';

        $query = InitialOrder::select(
            'initial_orders.name',
            'initial_orders.s_name',
            'initial_orders.deli_location',
            'initial_orders.delivery_date',
            'initial_orders.order_user',
            'initial_orders.quantity',
            'stocks.img_path',
            'processes.name as process_name',
            'places.id as place_id',
            'places.name as place_name'
        )
            ->leftJoin('stocks', 'stocks.id', 'initial_orders.stock_id')
            ->leftJoin('users', 'users.name', 'initial_orders.order_user')
            ->leftJoin('processes', 'processes.id', 'users.process_id')
            ->leftJoin('places', 'places.id', 'processes.place_id')
            ->where(function ($query) {
                $query->where('receive_flg', 1)
                    ->Where('none_storage_flg', 1);
            })
            ->where('receipt_flg', 0)
            ->where('initial_orders.del_flg', 0)
            ->orderby('initial_orders.updated_at', 'desc');

        if ($place_id) {
            $query->where('places.id', $place_id);
        }

        $initial_orders = $query->get();

        return Inertia::render('Content/ReceiveCompleteBack', ['initial_orders' => $initial_orders, 'place_name' => $place_name]);
    }

    public function factoryEnvMonitor($place_id)
    {

        return Inertia::render('Content/FactoryEnvMonitor', ['place_id' => $place_id]);
    }

    public function getData($place_id)
    {
        $data = Data::select('data.*', 'places.name as place_name')->join('places', 'places.id', 'data.place_id')->where('place_id', $place_id)->orderBy('id', 'desc')->first();

        return response()->json($data);
    }
    public function getWeather()
    {
        // 天気予報APIのエンドポイント
        $cityCode = "330010"; // 東京の都市コード
        $apiUrl = "https://weather.tsukumijima.net/api/forecast/city/{$cityCode}";

        // APIリクエスト
        $response = Http::get($apiUrl);

        // データ取得の確認
        if ($response->successful()) {
            $data = $response->json();

            // 今日の降水確率データ
            $chanceOfRain = $data['forecasts'][0]['chanceOfRain'] ?? [];

            return response()->json($chanceOfRain);
        }
    }
}
