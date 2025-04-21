<?php

namespace App\Http\Controllers;

use App\Models\Process;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SearchController extends Controller
{
    //
    public function index(){
        $processes = Process::all();
        return Inertia::render('Stock/Search', [ 'processes' => $processes ]);
    }
    public function search(){
        
    }
}
