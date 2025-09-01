<?php

namespace App\Http\Controllers;

use App\Models\OrderRequest;
use Exception;
use Illuminate\Http\Request;
use App\Http\Services\Helper;
use App\Models\Device;
use App\Models\InventoryOperationRecord;
use App\Models\NotifyGroup;
use App\Models\NotifyGroupUser;
use App\Models\NotifyQueue;
use App\Models\NotifyQueueUser;
use App\Models\Stock;
use App\Models\StockStorage;
use App\Models\StockSupplier;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    //
    public function create(Request $request) {}
    public function store(Request $request)
    {
        $status = true;
        $msg = '';
        $stock_id = $request->stock_id;
        $request_user_id = $request->request_user_id;
        $stock_storage_id = $request->stock_storage_id;
        $desire_delivery_date = $request->desire_delivery_date;
        $quantity = $request->quantity;
        $quantity_unit = $request->quantity_unit;
        $digest_date = $request->digest_date;
        $now_quantity = $request->now_quantity;
        $now_quantity_unit = $request->now_quantity_unit;
        $description = $request->description;
        $device_name = $request->device_name;
        $device_id = null;

        $stock = null;

        if (!$stock_id) {
            $status = false;
            return response()->json(['status' => $status, 'msg' => '在庫が選択されていません。']);
        }


        try {
            $stock = Stock::find($stock_id);
            $stock_supplier = StockSupplier::where('stock_id', $stock_id)->first();


            if ($device_name) {
                $device = Device::where('name', $device_name)->first();
                if ($device) {
                    $device_id = $device->id;
                }
            }

            // 発注依頼 ---------------------------------------------------
            $order_request = new OrderRequest();
            $order_request->stock_id = $stock_id;
            $order_request->request_user_id = $request_user_id == 0 ? 117 : $request_user_id;
            if ($stock->classification_id == 34) {
                $order_request->user_id = 39;
            }
            $order_request->price = $stock->price;
            $order_request->quantity =  $quantity;
            $order_request->unit = $quantity_unit;
            $order_request->now_quantity = $now_quantity;
            $order_request->now_quantity_unit = $now_quantity_unit;
            $order_request->digest_date = $digest_date;
            $order_request->desire_delivery_date = $desire_delivery_date;
            $order_request->description = $description;
            $order_request->device_id = $device_id;

            if ($stock->price !== null) {
                $order_request->calc_price = $stock->price * $quantity;
            }
            if ($stock_supplier) {
                $order_request->supplier_id = $stock_supplier->supplier_id;
                $order_request->lead_time = $stock_supplier->lead_time;
                $order_request->postage = $stock_supplier->postage;
            }
            $order_request->save();
            // -----------------------------------------------------------

            if (!$stock_storage_id) {
                $stock_storage = StockStorage::where('stock_id', $stock_id)->first();
                if ($stock_storage) {
                    $stock_storage_id = $stock_storage->id;
                }
            }
            if ($stock_storage_id) {
                // 発注点を更新 -----------------------------------------------------------
                Helper::updateReOrderPoint($stock_storage_id, $stock_id, $stock->quantity);
                // -----------------------------------------------------------------------
            }

            $notify_user_ids = [];
            
            if ($stock->classification_id == 34) { //原材料・副資材の場合
                $notify_user_ids = [39];
            }else{
                $notify_user_ids = [ 68, 48 ]; //岡堂・中村
            }

            // 発注依頼を通知 -----------------------------------------------------------
            Helper::createNotifyQueue("在庫管理システムからの通知です。", "{$stock->name}{$stock->s_name}の物品依頼を受付ました。以下のURLから発注を完了させてください。", "http://monokanri-manage.local/stock/order-requests", $notify_user_ids);
            // -----------------------------------------------------------

        } catch (Exception $e) {
            $status = false;
            $msg = $e->getMessage();
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


        return response()->json(['status' => $status]);
    }

    // 確定発注依頼
    public function getConfirmOrderRequest()
    {
        $order_requests = OrderRequest::select('users.name as user_name', 'stocks.name', 'stocks.s_name', 'stocks.stock_no', 'stocks.solo_unit', 'org_unit', 'stocks.quantity_per_org', 'deli_location', 'suppliers.supplier_no as com_no', 'suppliers.name as com_name', 'stock_suppliers.lead_time', 'stock_suppliers.memo', 'order_requests.quantity', 'order_requests.price', 'order_requests.updated_at')->leftJoin('users', 'users.id', 'order_requests.user_id')->leftJoin('stock_suppliers', 'stock_suppliers.stock_id', 'order_requests.stock_id')->join('suppliers', 'suppliers.id', 'stock_suppliers.supplier_id')->join('stocks', 'stocks.id', 'order_requests.stock_id')->where('status', 1)
            ->whereDate('order_requests.updated_at', now()->toDateString())
            ->where('order_requests.del_flg', 0)
            ->get();
        return response()->json($order_requests);
    }
}
