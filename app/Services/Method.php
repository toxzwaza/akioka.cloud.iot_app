<?php

namespace App\Services;

use App\Models\InitialOrder;
use App\Models\StockSupplier;
use Carbon\Carbon;

class Method
{
    // 納品日セットトリードタイム再計算
    public static function setDeliveryDateAndUpdateLeadTime($initial_order_id, $delivery_date = null)
    {
        if ($delivery_date === null) {
            $delivery_date = Carbon::today();
        }
        
        $initial_order = InitialOrder::find($initial_order_id);
        $initial_order->delivery_date = $delivery_date;

        // リードタイムを代入
        if ($initial_order->order_date && $delivery_date) {
            $order_date = \Carbon\Carbon::parse($initial_order->order_date);
            $lead_time = $delivery_date->diffInDays($order_date);
            $initial_order->lead_time = $lead_time;
        }

        // 平均リードタイムを再計算
        $average_lead_time = InitialOrder::where('stock_id', $initial_order->stock_id)->where('supplier_id', $initial_order->supplier_id)->avg('lead_time');
        $new_lead_time = round($average_lead_time);
        $initial_order->save();


        $stock_supplier = StockSupplier::where('stock_id', $initial_order->stock_id)->where('supplier_id', $initial_order->supplier_id)->first();

        if ($stock_supplier) {
            $stock_supplier->lead_time = $new_lead_time;
            $stock_supplier->save();
        }
    }
}
