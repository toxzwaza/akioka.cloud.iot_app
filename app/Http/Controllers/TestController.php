<?php

namespace App\Http\Controllers;

use App\Http\Services\Helper;
use App\Models\InitialOrder;
use App\Models\Stock;
use App\Models\StockPriceArchive;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TestController extends Controller
{
    //
    public function test()
    {
        $initial_orders = InitialOrder::leftJoin('users', 'users.name', 'initial_orders.order_user')
            ->whereNull('users.name')
            ->select('initial_orders.*')  // この行を追加
            ->get();
        foreach($initial_orders as $initial_order) {
            $user = User::where('name', 'like', '%' . $initial_order->order_user . '%')->first();
            if($user){
                $initial_order->order_user = $user->name;
                $initial_order->save();
                echo $user->name . ' ' . $initial_order->order_user . '<br>';
            } else {
                echo $initial_order->order_user . '<br>';
            }
        }

    }
}
