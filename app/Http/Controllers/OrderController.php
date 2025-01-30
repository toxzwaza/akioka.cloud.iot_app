<?php

namespace App\Http\Controllers;

use App\Models\OrderRequest;
use Exception;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    //
    public function create(Request $request) {}
    public function store(Request $request)
    {
        $status = true;
        $msg = '';

        $stock_id = $request->stock_id;
        if (!$stock_id) {
            $status = false;
            return response()->json(['status' => $status, 'msg' => '在庫が選択されていません。']);
        }
        try {
            $order_request = new OrderRequest();
            $order_request->stock_id = $stock_id;
            $order_request->save();
        } catch (Exception $e) {
            $status = false;
            $msg = $e->getMessage();
        }

        return response()->json(['status' => $status, 'msg' => $msg]);
    }
}
