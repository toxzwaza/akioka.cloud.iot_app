<?php

namespace App\Http\Controllers;

use App\Models\FacilitySchedule;
use App\Models\FacilityScheduleParticipant;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ContentController extends Controller
{
    //
    public function watchData(Request $request){
        $place_id = $request->place_id;
        if(!$place_id){
            // place_idが指定されていない場合、全てを表示する

            return Inertia::render('Content/WatchData');
        }

    }
    public function facilitySchedule(){

        return Inertia::render('Content/FacilitySchedule');
    }

    public function getFacilitySchedule(){
        $facility_schedules_1 = FacilitySchedule::select('facility_schedules.*','facilities.name as facility_name')->join('facilities','facilities.id','facility_schedules.facility_id')->whereDate('start_date', now()->toDateString())->where('facility_id', 1)->get();
        foreach($facility_schedules_1 as $schedule){
            $participants = FacilityScheduleParticipant::where('facility_schedule_id', $schedule->id)->get();
            $schedule->participants =  $participants;
        }

        $facility_schedules_2 = FacilitySchedule::select('facility_schedules.*','facilities.name as facility_name')->join('facilities','facilities.id','facility_schedules.facility_id')->whereDate('start_date', now()->toDateString())->where('facility_id', 2)->get();

        foreach($facility_schedules_2 as $schedule){
            $participants = FacilityScheduleParticipant::where('facility_schedule_id', $schedule->id)->get();
            $schedule->participants =  $participants;
        }

        return response()->json(['facility_schedules_1' => $facility_schedules_1, 'facility_schedules_2' => $facility_schedules_2]);

    }

}
