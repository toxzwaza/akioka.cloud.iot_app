<?php

namespace App\Http\Controllers;

use App\Http\Services\Helper;
use App\Models\InitialOrder;
use App\Models\OrderRequest;
use App\Models\Stock;
use App\Models\StockPriceArchive;
use App\Models\StockSupplier;
use App\Models\Supplier;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TestController extends Controller
{
    //
    public function test()
    {
        $initial_orders = InitialOrder::select('id', 'stock_id', 'supplier_id', 'name', 's_name', 'com_name', 'order_date', 'delivery_date', 'lead_time')
            ->whereNotNull('stock_id')
            ->where('price', '!=', 0)
            ->whereNotNull('lead_time')
            ->where('receive_flg', 1)
            ->get();

        foreach ($initial_orders as $initial_order) {
            $stock_supplier = StockSupplier::where('stock_id', $initial_order->stock_id)
                ->where('supplier_id', $initial_order->supplier_id)
                ->first();

            if ($stock_supplier) {
                $stock_supplier->lead_time = $initial_order->lead_time;
                $stock_supplier->save();
            }

            // // 注文日
            // $order_date = $initial_order->order_date;
            // // 納品日
            // $delivery_date = $initial_order->delivery_date;
            // 実際のリードタイムを計算
            // if ($delivery_date && $order_date) {
            //     $delivery_date = \Carbon\Carbon::parse($delivery_date);
            //     $order_date = \Carbon\Carbon::parse($order_date);
            //     $lead_time = $delivery_date->diffInDays($order_date);

            //     $initial_order->lead_time = $lead_time;
            //     $initial_order->save();

            //     echo $initial_order->name . ' ' . $initial_order->s_name . ' ' . $initial_order->com_name . ' ' . $lead_time . '<br>';


            // } 


        }
    }
}
