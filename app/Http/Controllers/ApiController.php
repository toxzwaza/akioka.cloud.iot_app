<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Stock;
use App\Models\StockStorage;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    //
    public function getGroups()
    {
        $groups = Group::all();
        return response()->json($groups);
    }
    public function getUsersByGroup(Request $request)
    {
        $group_id = $request->group_id;
        $users = User::where('group_id', $group_id)->orderBy('id', 'asc')->get();
        return response()->json($users);
    }

    // 検索画面の在庫取得
    public function getStocks(Request $request)
    {
        $stock_name = $request->stock_name;
        $address_id = $request->address_id;
        $stock_id = $request->stock_id;
        $alias = $request->alias;

        try {
            $query = Stock::select('stocks.*', 'stock_storages.id as stock_storage_id', 'stock_storages.quantity', 'locations.name as location_name', 'storage_addresses.id as storage_address_id', 'storage_addresses.address')
                ->distinct()
                ->leftJoin('stock_storages', 'stocks.id',  'stock_storages.stock_id')
                ->leftJoin('storage_addresses', 'stock_storages.storage_address_id', 'storage_addresses.id')
                ->leftJoin('locations', 'storage_addresses.location_id', 'locations.id')
                ->leftJoin('stock_aliases', 'stocks.id', 'stock_aliases.stock_id')
                ->where('stocks.del_flg', 0)
                ->orderBy('updated_at', 'desc');



            if ($stock_name) {
                $query->where('stocks.name', 'like', '%' . $stock_name . '%')->orWhere('stocks.s_name', 'like', '%' . $stock_name . '%');
            }

            if ($alias) {
                $query->where('stock_aliases.alias', 'like', '%' . $alias . '%');
            }

            if ($address_id) {
                $query->where('storage_address_id', $request->address_id);
            }

            // 在庫IDもしくはJANコードから検索
            if ($stock_id) {
                $query->where('stocks.id', $request->stock_id)->orWhere('stocks.jan_code', $stock_id);
            }

            $stocks = $query->get();


            return response()->json($stocks);
        } catch (Exception $e) {
            return response()->json($e);
        }
    }

    public function getStockStorages(Request $request)
    {
        $stock_id = $request->stock_id;
        $stock = Stock::where('id', $stock_id)->orWhere('jan_code', $stock_id)->first();

        $stock_storages = StockStorage::select('stock_storages.*', 'locations.name as location_name', 'storage_addresses.address')->join('storage_addresses', 'stock_storages.storage_address_id', 'storage_addresses.id')
            ->join('locations', 'storage_addresses.location_id', 'locations.id')->where('stock_id', $stock->id)->get();
        $stock->stock_storages = $stock_storages;

        return response()->json($stock);
    }

    // 外部からファイルをアップロード
    public function uploadFile(Request $request)
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
