<?php

namespace App\Http\Controllers;

use App\Models\InitialOrder;
use App\Models\InventoryOperationRecord;
use App\Models\OrderRequest;
use App\Models\Stock;
use App\Models\StockStorage;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class InventoryController extends Controller
{
    //
    public function create() {}
    public function store(Request $request) {}
    public function show($id)
    {
        $stock = Stock::find($id);
        $stock_storage = StockStorage::select('stock_storages.*', 'locations.name as location_name', 'storage_addresses.address')->join('storage_addresses', 'stock_storages.storage_address_id', 'storage_addresses.id')
            ->join('locations', 'storage_addresses.location_id', 'locations.id')->where('stock_id', $id)->get();

        $stock->stock_storage = $stock_storage;

        // 発注依頼を取得
        $order_requests = OrderRequest::where('stock_id', $id)->get();
        $stock->order_requests = $order_requests;

        // 発注履歴データ
        $initial_orders = InitialOrder::where('name', $stock->name)->where('s_name', $stock->s_name)->orderBy('order_date', 'desc')->get();
        $stock->initial_orders = $initial_orders;
        return Inertia::render('Stock/Inventory', ['stock' => $stock]);
    }


    public function updateFile(Request $request)
    {
        $status = true;
        $msg = '';

        try {
            if ($request->hasFile('file')) {
                $stock_id = $request->stock_id;
                $file = $request->file('file');

                // 画像を保存する
                $timestamp = now()->timestamp;
                $filename = $timestamp . '.' . $file->getClientOriginalExtension();
                $file->storeAs('public/stock', $filename);

                // 保存した画像のパスに更新
                $stock = Stock::find($stock_id);
                $stock->img_path = 'storage/stock/' . $filename;
                $stock->save();

                $msg = "ファイルアップロードが完了しました";
            }
        } catch (Exception $e) {
            $status = false;
            $msg = $e->getMessage();
        }

        return response()->json(['status' => $status, 'msg' => $msg], 200);
    }

    public function changeQuantity(Request $request)
    {
        $status = true;
        $msg = '';

        $stock_id = $request->stock_id;
        $stock_storage_id = $request->stock_storage_id;
        $quantity = $request->quantity;
        $bef_quantity = 0;

        try {

            // 数量を変更
            $stock_storage = StockStorage::find($stock_storage_id);
            $bef_quantity = $stock_storage->quantity;
            $stock_storage->quantity = $quantity;
            $stock_storage->save();


            // 編集履歴を保存
            $inventory_operation_record = new InventoryOperationRecord();
            $inventory_operation_record->stock_id = $stock_id;
            $inventory_operation_record->stock_storage_id = $stock_storage_id;
            $inventory_operation_record->quantity = $quantity;
            $inventory_operation_record->inventory_operation_id = 9;
            $inventory_operation_record->bef_quantity = $bef_quantity;
            $inventory_operation_record->save();
        } catch (Exception $e) {
            $msg = $e->getMessage();
            $status = false;
        }



        return response()->json(['status' => $status, 'msg' => $msg]);
    }
}
