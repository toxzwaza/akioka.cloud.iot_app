<?php

namespace App\Http\Controllers;

use App\Models\LunchOrder;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LunchController extends Controller
{
    //
    public function index(){
        return Inertia::render('Lunch/Index'); 
    }

    // 注文
    public function order(){

    }

    // 受け取り
    public function receive(){

    }

    public function getOrders(){
        $lunch_orders = LunchOrder::whereDate('date', now()->toDateString())->get();

        return response()->json($lunch_orders);
    }

    public function getUsers(){

        $users = User::select('id', 'name', 'duty_flg')->where('del_flg', 0)->get();

        return response()->json($users);
    }
}
