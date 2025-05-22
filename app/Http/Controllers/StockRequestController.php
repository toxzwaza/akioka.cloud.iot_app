<?php

namespace App\Http\Controllers;

use App\Http\Services\Helper;
use App\Models\InventoryOperationRecord;
use App\Models\OrderRequest;
use App\Models\Process;
use App\Models\Stock;
use App\Models\StockRequest;
use App\Models\StockRequestOrder;
use App\Models\StockStorage;
use App\Models\StockSupplier;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Inertia\Inertia;
use PhpParser\Node\Stmt\Catch_;

class StockRequestController extends Controller
{
    //
    public function home()
    {

        $processes = Process::select('id', 'name')->whereIn('id', [1, 2, 3, 4, 5, 6, 8])->get();

        // 現場依頼対象物品を取得
        $stock_requests =
            StockRequest::select('stock_requests.id as stock_request_id', 'stock_requests.alias', 'stock_requests.alias', 'stocks.id as stock_id', 'stocks.name', 'stocks.img_path', 'stocks.solo_unit', 'stock_storages.id as stock_storage_id', 'stock_storages.quantity as stock_storage_quantity', 'storage_addresses.address')
            ->join('stocks', 'stocks.id', 'stock_requests.stock_id')
            ->leftJoin('stock_storages', 'stock_storages.stock_id', 'stock_requests.stock_id')
            ->leftJoin('storage_addresses', 'storage_addresses.id', 'stock_storages.storage_address_id')
            ->orderBy('stock_requests.orderNumber', 'asc')->get();


        $users = User::select('id', 'name', 'process_id')->where('process_id', '!=', 0)->get();

        // 物品依頼を取得
        $stock_request_orders = StockRequestOrder::select('stock_request_orders.id', 'stock_request_orders.process_id', 'stock_request_orders.stock_id', 'stock_request_orders.status', 'stock_request_orders.quantity', 'stock_request_orders.order_flg', 'stock_request_orders.created_at', 'users.name as user_name')->join('users', 'users.id', 'stock_request_orders.user_id')
            ->where('stock_request_orders.status', 0)
            ->orderBy('stock_request_orders.created_at', 'desc')->get();

        return Inertia::render('Stock/Request/Home', ['processes' => $processes, 'stock_requests' => $stock_requests, 'users' => $users, 'stock_request_orders' => $stock_request_orders]);
    }

    public function store(Request $request)
    {
        $status = true;

        $process_id = $request->process_id;
        $user_id = $request->user_id;
        $data = $request->data;

        try {
            foreach ($data as $stock_id => $quantity) {
                $stock_request_order = StockRequestOrder::where('process_id', $process_id)->where('stock_id', $stock_id)->where('status', 0)->first();
                if ($stock_request_order) {
                    $stock_request_order->quantity = $quantity;
                    $stock_request_order->save();
                } else {
                    $stock_request_order = new StockRequestOrder();
                    $stock_request_order->user_id = $user_id;
                    $stock_request_order->process_id = $process_id;
                    $stock_request_order->stock_id = $stock_id;
                    $stock_request_order->quantity = $quantity;
                    $stock_request_order->save();
                }
            }
        } catch (Exception $e) {
            $status = false;
        }

        return response()->json(['status' => $status]);
    }

    public function complete(Request $request)
    {
        $status = true;


        $process_id = $request->process_id;
        $stock_id = $request->stock_id;
        $stock_storage_id = $request->stock_storage_id;
        $updateQuantity = $request->updateQuantity;

        try {
            if ($stock_storage_id) {
                $stock_storage = StockStorage::find($stock_storage_id);
                $stock_storage->quantity = $updateQuantity;
                $stock_storage->save();

                $stock_request_order = StockRequestOrder::where('process_id', $process_id)->where('stock_id', $stock_id)->where('status', 0)->first();
                $stock_request_order->status = 1;
                $stock_request_order->save();

                // 出庫を記録
                $inventory_operation_record = new InventoryOperationRecord();
                $inventory_operation_record->inventory_operation_id = 2;
                $inventory_operation_record->stock_id = $stock_id;
                $inventory_operation_record->stock_storage_id = $stock_storage_id;
                $inventory_operation_record->quantity = $stock_request_order->quantity;
                $inventory_operation_record->user_id = 81; //三谷
                $inventory_operation_record->save();
            }
        } catch (Exception $e) {
            $status = false;
        }

        return response()->json(['status' => $status]);
    }

    public function delete(Request $request)
    {
        $status = true;
        $stock_request_order_id = $request->stock_request_order_id;

        try {
            if (!$stock_request_order_id) {
                $status = false;
                throw new Exception('Stock request order ID is missing.');
            }

            $stock_request_order = StockRequestOrder::find($stock_request_order_id);
            $stock_request_order->delete();
        } catch (Exception $e) {
            $status = false;
        }

        return response()->json(['status' => $status]);
    }

    public function order(Request $request)
    {
        $status = true;
        $msg = '';
        $stock_id = $request->stock_id;
        $request_user_id = $request->request_user_id;
        $stock_storage_id = $request->stock_storage_id;

        $stock = null;

        if (!$stock_id) {
            $status = false;
            return response()->json(['status' => $status, 'msg' => '在庫が選択されていません。']);
        }


        try {
            $stock = Stock::find($stock_id);
            $stock_supplier = StockSupplier::where('stock_id', $stock_id)->first();

            // 発注依頼 ---------------------------------------------------
            $order_request = new OrderRequest();
            $order_request->stock_id = $stock_id;
            $order_request->request_user_id = $request_user_id == 0 ? 117 : $request_user_id;
            $order_request->price = $stock->price;
            $order_request->quantity = 1;

            if ($stock->price !== null) {
                $order_request->calc_price = $stock->price;
            }
            if ($stock_supplier) {
                $order_request->supplier_id = $stock_supplier->supplier_id;
                $order_request->lead_time = $stock_supplier->lead_time;
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

            // 完了しておらず、stock_idが一致する全てのorder_flgを1にする
            $stock_request_orders = StockRequestOrder::where('stock_id', $stock_id)
                ->where('status', 0)
                ->get();
            foreach ($stock_request_orders as $stock_request_order) {
                $stock_request_order->order_flg = 1;
                $stock_request_order->save();
            }

            // 発注依頼を通知 -----------------------------------------------------------
            Helper::createNotifyQueue("在庫管理システムからの通知です。", "{$stock->name}{$stock->s_name}のの物品依頼を受付ました。以下のURLから発注を完了させてください。", "http://monokanri-manage.local/stock/order-requests", [91, 81, 68, 48]);
            // -----------------------------------------------------------

        } catch (Exception $e) {
            $status = false;
            $msg = $e->getMessage();
        }

        return response()->json(['status' => $status, 'msg' => $msg]);
    }
}
