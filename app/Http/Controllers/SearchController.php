<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class SearchController extends Controller
{
    //
    public function index(){

        

        return Inertia::render('Stock/Search');
    }
    public function search(){
        
    }
}
