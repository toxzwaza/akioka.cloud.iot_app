<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DataController extends Controller
{
    //
    public function store(Request $request)
    {
        $temperature = $request->temprature;
        $humidity = $request->humidity;
        $co2 = $request->co2;
        $placeNo = $request->placeNo;

    }
}
