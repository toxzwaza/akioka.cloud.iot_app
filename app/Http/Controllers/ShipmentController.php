<?php

namespace App\Http\Controllers;

use App\Http\Services\Helper;
use App\Models\InventoryOperationRecord;
use App\Models\NotifyQueue;
use App\Models\NotifyQueueUser;
use App\Models\OrderRequest;
use App\Models\Stock;
use App\Models\StockStorage;
use App\Models\StockSupplier;
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
            $stock_storage = StockStorage::find($stock_storage_address_id);
            $stock = Stock::where(function($query) use ($stock_id) {
                $query->where('id', $stock_id)
                      ->orWhere('jan_code', $stock_id);
            })->where('del_flg', 0)->first();
            if (!$stock) {
                throw new Exception('在庫データが見つかりません。');
            }
            
            $stock_supplier = StockSupplier::where('stock_id', $stock_id)->first();

            // 該当格納先の個数を減算---------------------------------------------------
            $stock_storage->quantity = $stock_storage->quantity - $quantity;
            $stock_storage->save();
            // ---------------------------------------------------

            // 出庫履歴を作成---------------------------------------------------
            $inventory_operation_record = new InventoryOperationRecord();
            $inventory_operation_record->stock_storage_id = $stock_storage_address_id;
            $inventory_operation_record->inventory_operation_id = 2;
            $inventory_operation_record->quantity = $quantity;
            $inventory_operation_record->user_id = $user_id;
            $inventory_operation_record->stock_id = $stock_storage->stock_id;
            $inventory_operation_record->save();
            // ---------------------------------------------------

            // 在庫数が発注点をきった場合、かつ、仕掛かり中の発注依頼がない場合、発注依頼を作成
            if ($stock_storage->quantity <= $stock_storage->reorder_point && !OrderRequest::where('stock_id', $stock->id)->where('status', 0)->first()) {

                // 発注依頼 ---------------------------------------------------
                $order_request = new OrderRequest();
                $order_request->stock_id = $stock->id;
                $order_request->request_user_id = 117;
                $order_request->price = $stock->price;
                $order_request->quantity = 1;

                if ($stock->price !== null) {
                    $order_request->calc_price = $stock->price;
                }
                if ($stock_supplier) {
                    $order_request->supplier_id = $stock_supplier->supplier_id;
                    $order_request->lead_time = $stock_supplier->lead_time;
                    $order_request->postage = $stock_supplier->postage;
                }
                $order_request->save();
                // -----------------------------------------------------------

                // 発注依頼を通知 -----------------------------------------------------------
                Helper::createNotifyQueue("在庫管理システムからの通知です。", "{$stock->name}{$stock->s_name}の物品が発注点を下回りました、以下のURLから発注を完了させてください。", "http://monokanri-manage.local/stock/order-requests",[91, 81, 68, 48]);
                // -----------------------------------------------------------
            }
        } catch (Exception $e) {
            $status = false;
            $msg = $e->getMessage();
        }

        return response()->json(['msg' => $msg, 'status' => $status]);
    }
}
