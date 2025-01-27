<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class HeatStrokeController extends Controller
{
    //
    public function index(){
        return to_route('dashboard');
    }
    public function dashboard(){
        return Inertia::render('Dashboard');
    }
    public function download(){
        return Inertia::render('Download');
    }
    public function Setting(){
        return Inertia::render('Setting');
    }

}
