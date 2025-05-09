<?php

namespace App\Http\Controllers;

use App\Models\Process;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SearchController extends Controller
{
    //
    public function index(){
        $processes = Process::all();
        $users = User::where('del_flg', 0)->get();
        return Inertia::render('Stock/Search', [ 'processes' => $processes, 'users' => $users ]);
    }
    public function search(){
        
    }
}
