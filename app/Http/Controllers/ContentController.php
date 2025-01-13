<?php

namespace App\Http\Controllers;

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
}
