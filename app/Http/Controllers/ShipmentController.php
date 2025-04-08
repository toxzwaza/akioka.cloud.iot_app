<?php

namespace App\Http\Controllers;

use App\Models\InventoryOperationRecord;
use App\Models\NotifyQueue;
use App\Models\NotifyQueueUser;
use App\Models\OrderRequest;
use App\Models\Stock;
use App\Models\StockStorage;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ShipmentController extends Controller
{
    //
    public function index(Request $request)
    {
        $stock_storage_address_id = $request->stock_storage_address_id;

        $stock_storage = null;

        if ($stock_storage_address_id) {
            $stock_storage = StockStorage::find($stock_storage_address_id);
        }

        return Inertia::render('Stock/Shipment', ['stock_storage' => $stock_storage]);
    }
    public function store(Request $request)
    {
        $msg = '';
        $status = true;

        $stock_id = $request->stock_id;
        $stock_storage_address_id = $request->address_id;
        $quantity = $request->quantity;
        $user_id = $request->user_id;

        if (!($stock_storage_address_id && $quantity && $user_id)) {
            $status = false;
            $msg = 'データが不足しています。';
        }

        try {
            // 該当格納先の個数を減算
            $stock_storage = StockStorage::find($stock_storage_address_id);
            $stock_storage->quantity = $stock_storage->quantity - $quantity;
            $stock_storage->save();

            // 出庫履歴を作成
            $inventory_operation_record = new InventoryOperationRecord();
            $inventory_operation_record->stock_storage_id = $stock_storage_address_id;
            $inventory_operation_record->inventory_operation_id = 2;
            $inventory_operation_record->quantity = $quantity;
            $inventory_operation_record->user_id = $user_id;
            $inventory_operation_record->stock_id = $stock_storage->stock_id;
            $inventory_operation_record->save();


            // 在庫数が発注点をきった場合、かつ、仕掛かり中の発注依頼がない場合、発注依頼を作成
            if ($stock_storage->quantity <= $stock_storage->reorder_point && !OrderRequest::where('stock_id', $stock_id)->where('status', 0)->first()) {

                $order_request = new OrderRequest();
                $order_request->stock_id = $stock_id;
                $order_request->request_user_id = 117;
                $order_request->save();

                $stock = Stock::find($stock_id);

                // 発注依頼を通知
                $title = "在庫管理システムからの通知です。";
                $message = "{$stock->name}{$stock->s_name}の物品が発注点を下回りました、以下のURLから発注を完了させてください。";
                // 通知者リスト
                $notify_users = [91, 81, 68, 48];
                $url = "http://monokanri-manage.local/stock/order-requests";


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
                    DB::rollBack();
                }
            }
        } catch (Exception $e) {
            $status = false;
            $msg = $e->getMessage();
        }

        return response()->json(['msg' => $msg, 'status' => $status]);
    }
}
