<?php

namespace App\Http\Controllers;

use App\Models\OrderRequest;
use Exception;
use Illuminate\Http\Request;
use App\Http\Services\Helper;
use App\Models\InventoryOperationRecord;
use App\Models\NotifyGroup;
use App\Models\NotifyGroupUser;
use App\Models\NotifyQueue;
use App\Models\NotifyQueueUser;
use App\Models\Stock;
use App\Models\StockStorage;
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

        if (!$stock_id) {
            $status = false;
            return response()->json(['status' => $status, 'msg' => '在庫が選択されていません。']);
        }


        try {

            $order_request = new OrderRequest();
            $order_request->stock_id = $stock_id;
            $order_request->request_user_id = $request_user_id;
            $order_request->save();
        } catch (Exception $e) {
            $status = false;
            $msg = $e->getMessage();
        }


        if ($status) {
            // $stock = Stock::find($stock_id);
            $stock = StockStorage::select(
                'stocks.*',
                'stock_storages.reorder_point',
                'stock_storages.quantity'
            )
                ->join('stocks', 'stocks.id', 'stock_storages.stock_id')
                ->where('stock_storages.id', $stock_storage_id)->first();

            // 発注依頼を記録
            $inventoryOperationRecord = new InventoryOperationRecord();
            $inventoryOperationRecord->inventory_operation_id = 7;
            $inventoryOperationRecord->stock_id = $stock_id;
            $inventoryOperationRecord->stock_storage_id = $stock_storage_id;
            $inventoryOperationRecord->bef_quantity = $stock->quantity;
            $inventoryOperationRecord->save();

            // 発注点再計算
            $reorder_point_avg = InventoryOperationRecord::where('stock_storage_id', $stock_storage_id)
                ->where('inventory_operation_id', 7)
                ->avg('bef_quantity');

            // 発注点を更新
            $stock_storage = StockStorage::find($stock_storage_id);
            $stock_storage->reorder_point = $reorder_point_avg;
            $stock_storage->save();

            // 通知用
            // 発注依頼を通知
            $title = "在庫管理システムからの通知です。";
            $message = "{$stock->name}{$stock->s_name}の発注依頼を受付ました。以下のURLから発注を完了させてください。";
            // 通知者リスト
            $notify_users = [91, 81, 68, 48];
            $url = "http://monokanri-manage.local/stock/order-requests";

            try {
                DB::beginTransaction();
                try {
                    $notifyQueue = new NotifyQueue();
                    $notifyQueue->title = $title;
                    $notifyQueue->msg = $message;
                    $notifyQueue->url = $url;
                    $notifyQueue->save();

                    foreach ($notify_users as $user) {
                        $notifyQueueUser = new NotifyQueueUser();
                        $notifyQueueUser->notify_queue_id = $notifyQueue->id;
                        $notifyQueueUser->user_id = $user;
                        $notifyQueueUser->save();
                    }

                    DB::commit();
                } catch (Exception $e) {
                    $msg = $e->getMessage();
                    DB::rollBack();
                }
            } catch (Exception $e) {
                $status = false;
            }
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
