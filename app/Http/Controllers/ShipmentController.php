<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class ShipmentController extends Controller
{
    //
    public function index(){


        // 部署一覧
        

        return Inertia::render('Stock/Shipment');
    }   
    public function store(){
        
    }
}
