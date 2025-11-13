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
use App\Models\StockSupplierPrice;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

            $order_request->quantity =  $quantity;
            $order_request->unit = $quantity_unit;
            $order_request->now_quantity = $now_quantity;
            $order_request->now_quantity_unit = $now_quantity_unit;
            $order_request->digest_date = $digest_date;
            $order_request->desire_delivery_date = $desire_delivery_date;
            $order_request->description = $description;
            $order_request->device_id = $device_id;

            // 単価・金額設定
            // 希望納期時点で有効な価格を取得（希望納期がない場合は現在日時を使用）
            $target_date = $desire_delivery_date ?? now();

            // デバッグ用ログ
            Log::info('価格取得デバッグ', [
                'stock_id' => $stock_id,
                'desire_delivery_date' => $desire_delivery_date,
                'target_date' => $target_date,
                'stock_price' => $stock->price,
            ]);

            //適用中を優先的に取得
            $stock_supplier = StockSupplier::where('stock_id', $stock_id)
                ->orderBy('main_flg', 'desc')
                ->orderBy('updated_at', 'desc')
                ->first();

            Log::info('適用中のサプライヤー取得', [
                'stock_id' => $stock_id,
                'stock_supplier' => $stock_supplier ? [
                    'id' => $stock_supplier->id,
                    'supplier_id' => $stock_supplier->supplier_id,
                    'main_flg' => $stock_supplier->main_flg,
                    'updated_at' => $stock_supplier->updated_at,
                ] : null,
            ]);

            $stock_supplier_price = null;
            if ($stock_supplier) {
                $stock_supplier_price = StockSupplierPrice::where('stock_supplier_id', $stock_supplier->id)
                    ->where('active_flg', 1)
                    ->validAt($target_date)
                    ->orderBy('start_date', 'desc')
                    ->first();

                Log::info('サプライヤー価格取得', [
                    'stock_supplier_id' => $stock_supplier->id,
                    'target_date' => $target_date,
                    'stock_supplier_price' => $stock_supplier_price ? [
                        'id' => $stock_supplier_price->id,
                        'price' => $stock_supplier_price->price,
                        'active_flg' => $stock_supplier_price->active_flg,
                        'start_date' => $stock_supplier_price->start_date,
                        'end_date' => $stock_supplier_price->end_date,
                    ] : null,
                ]);

                if ($stock_supplier_price) {
                    $old_active_flg = $stock_supplier_price->active_flg;
                    $old_stock_price = $stock->price;

                    $stock_supplier_price->active_flg = 0;
                    $stock_supplier_price->save();
                    
                    //マスタの価格を更新
                    $stock->price = $stock_supplier_price->price;
                    $stock->save();

                    Log::info('価格情報更新完了', [
                        'stock_supplier_price_id' => $stock_supplier_price->id,
                        'active_flg_変更' => [
                            '更新前' => $old_active_flg,
                            '更新後' => 0,
                        ],
                        'stock_id' => $stock->id,
                        'stock_price_変更' => [
                            '更新前' => $old_stock_price,
                            '更新後' => $stock->price,
                        ],
                    ]);
                }
            }

            // デバッグ用ログ
            Log::info('取得された価格情報', [
                'stock_supplier_price' => $stock_supplier_price ? [
                    'id' => $stock_supplier_price->id,
                    'price' => $stock_supplier_price->price,
                    'start_date' => $stock_supplier_price->start_date,
                    'end_date' => $stock_supplier_price->end_date,
                ] : null,
            ]);

            // 価格の優先順位: StockSupplierPrice > Stock の基本価格
            $order_request->price = $stock_supplier_price ? $stock_supplier_price->price : $stock->price;
            if ($order_request->price !== null) {
                $order_request->calc_price = $order_request->price * $quantity;
            }

            // デバッグ用ログ
            Log::info('最終適用価格', [
                'order_request_price' => $order_request->price,
                'order_request_calc_price' => $order_request->calc_price,
            ]);


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
            } else {
                $notify_user_ids = [68, 48]; //岡堂・中村
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
