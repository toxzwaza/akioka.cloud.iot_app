<?php

namespace App\Http\Controllers;

use App\Models\InitialOrder;
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

        // 発注データ
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
}
