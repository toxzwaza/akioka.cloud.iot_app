<?php

namespace App\Http\Controllers;

use App\Models\OrderRequest;
use Exception;
use Illuminate\Http\Request;
use App\Http\Services\Helper;
use App\Models\Stock;

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


        if ($status) {
            $stock = Stock::find($stock_id);
            // 発注依頼を通知
            $message = "{$stock->name}{$stock->s_name}の発注依頼を受付ました。以下のURLから発注を完了させてください。";
            Helper::sendNotify(['to-murakami@akioka-ltd.jp'], $message, 'http://monokanri-manage.local/stock/stocks/order_requests?user_id=91');
        }

        return response()->json(['status' => $status, 'msg' => $msg]);
    }

    public function delete($order_request_id)
    {
        $status = true;
        $stock_id = null;

        try {
            $order_request = OrderRequest::find($order_request_id);
            $stock_id = $order_request->stock_id;
            $order_request->delete();
        } catch (Exception $e) {
            $status = false;
        }

        if ($status) {
            $stock = Stock::find($stock_id);
            $message = "{$stock->name}{$stock->s_name}の発注依頼を削除しました。";
            Helper::sendNotify(['to-murakami@akioka-ltd.jp'], $message);
        }

        return response()->json(['status' => $status]);
    }

    // 確定発注依頼
    public function getConfirmOrderRequest()
    {
        $order_requests = OrderRequest::select('users.name as user_name', 'stocks.name', 'stocks.s_name', 'stocks.stock_no', 'stocks.price as stock_price','stocks.solo_unit', 'org_unit' , 'stocks.quantity_per_org', 'deli_location' ,'suppliers.supplier_no as com_no', 'suppliers.name as com_name', 'stock_suppliers.lead_time', 'stock_suppliers.memo' ,'order_requests.quantity', 'order_requests.updated_at')->leftJoin('users', 'users.id', 'order_requests.user_id')->leftJoin('stock_suppliers', 'stock_suppliers.stock_id', 'order_requests.stock_id')->join('suppliers', 'suppliers.id', 'stock_suppliers.supplier_id')->join('stocks', 'stocks.id', 'order_requests.stock_id')->where('status', 1)
            ->whereDate('order_requests.updated_at', now()->toDateString())
            ->get();
        return response()->json($order_requests);
    }
}
