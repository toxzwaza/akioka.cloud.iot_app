<?php

namespace App\Http\Controllers;

use App\Models\InventoryOperationRecord;
use App\Models\StockStorage;
use Exception;
use Illuminate\Http\Request;
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

            // 出庫履歴を作製
            $inventory_operation_record = new InventoryOperationRecord();
            $inventory_operation_record->stock_storage_id = $stock_storage_address_id;
            $inventory_operation_record->inventory_operation_id = 2;
            $inventory_operation_record->quantity = $quantity;
            $inventory_operation_record->user_id = $user_id;
            $inventory_operation_record->stock_id = $stock_storage->stock_id;
            $inventory_operation_record->save();
        } catch (Exception $e) {
            $status = false;
            $msg = $e->getMessage();
        }

        return response()->json(['msg' => $msg, 'status' => $status]);
    }
}
