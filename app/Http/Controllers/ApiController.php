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

        try {
            $query = Stock::select('stocks.*', 'stock_storages.quantity', 'locations.name as location_name', 'storage_addresses.address')
                ->leftJoin('stock_storages', 'stocks.id',  'stock_storages.stock_id')
                ->leftJoin('storage_addresses', 'stock_storages.storage_address_id', 'storage_addresses.id')
                ->leftJoin('locations', 'storage_addresses.location_id', 'locations.id')->where('stocks.del_flg', 0);



            if ($stock_name) {
                $query->where('stocks.name', 'like', '%' . $stock_name . '%')->orWhere('stocks.s_name', 'like', '%' . $stock_name . '%');
            }

            if ($address_id) {
                $query->where('storage_address_id', $request->address_id);
            }

            if ($stock_id) {
                $query->where('stocks.id', $request->stock_id);
            }

            $stocks = $query->get();


            return response()->json($stocks);
        } catch (Exception $e) {
            return response()->json($e);
        }
    }

    public function getStockStorages(Request $request){
        $stock_id = $request->stock_id;
        $stock = Stock::where('id', $stock_id)->orWhere('jan_code', $stock_id)->first();

        $stock_storages = StockStorage::select('stock_storages.*', 'locations.name as location_name', 'storage_addresses.address')->join('storage_addresses', 'stock_storages.storage_address_id', 'storage_addresses.id')
        ->join('locations', 'storage_addresses.location_id', 'locations.id')->where('stock_id', $stock->id)->get();
        $stock->stock_storages = $stock_storages;

        return response()->json($stock);
    }
}
