<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class ARController extends Controller
{
    //
    public function index(){

        return Inertia::render('AR/Viewer');
    }
}
