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
        $order_requests = OrderRequest::select('order_requests.stock_id','stock_storages.quantity')->join('stock_storages', 'stock_storages.stock_id', 'order_requests.stock_id')->where('order_requests.created_at', '>=', '2025-03-26 00:00:00')->get();
        foreach ($order_requests as $order_request) {
            echo $order_request->stock_id . ' ' . $order_request->quantity . '<br>';
        }
        
    }
}
