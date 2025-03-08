<?php

namespace App\Http\Controllers;

use App\Models\LunchOrder;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LunchController extends Controller
{
    //
    public function index()
    {
        return Inertia::render('Lunch/Index');
    }

    // 注文
    public function order(Request $request)
    {
        $user_id = $request->user_id;
        $order_flg = $request->order_flg;

        $lunch_order = LunchOrder::where('user_id', $user_id)->whereDate('date', now()->toDateString())->first();
        if ($order_flg) {

            $lunch_order->order_flg = 0;
            $lunch_order->save();
        } else {
            if (!$lunch_order) {
                $lunch_order = new LunchOrder();
                $lunch_order->lunch_id = 1;
                $lunch_order->user_id = $user_id;
                $lunch_order->order_flg = 1;
                $lunch_order->date = now()->toDateString();
                $lunch_order->save();
            }else{
                $lunch_order->order_flg = 1;
                $lunch_order->save();
            }
        }


        return response()->json($lunch_order);
    }

    // 受け取り
    public function receive(Request $request) {
        $status = true;
        $order_id = $request->order_id;
        try{
            $lunch_order = LunchOrder::find($order_id);
            $lunch_order->receive_flg = 1;
            $lunch_order->save();

        }catch(Exception $e){
            $status = false;

        }

        return response()->json($status);
    }

    public function getOrders()
    {
        $lunch_orders = LunchOrder::select('lunch_orders.*', 'users.name as user_name', 'users.duty_flg as duty_flg')->join('users', 'lunch_orders.user_id', '=', 'users.id')
        ->whereDate('date', now()->toDateString())
        ->orderBy('updated_at', 'desc')
        ->get();

        return response()->json($lunch_orders);
    }

    public function getUsers()
    {

        $users = User::select('id', 'name', 'duty_flg')->where('del_flg', 0)->get();

        return response()->json($users);
    }
}
