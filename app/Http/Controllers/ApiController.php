<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\InitialOrder;
use App\Models\Stock;
use App\Models\StockAlias;
use App\Models\StockStorage;
use App\Models\StockSupplier;
use App\Models\Supplier;
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
        $process_id = $request->process_id;


        try {
            if (!$process_id) {
                $query = Stock::select('stocks.*', 'stock_storages.id as stock_storage_id', 'stock_storages.quantity', 'locations.name as location_name', 'storage_addresses.id as storage_address_id', 'storage_addresses.address')
                    ->leftJoin('stock_storages', 'stocks.id',  'stock_storages.stock_id')
                    ->leftJoin('storage_addresses', 'stock_storages.storage_address_id', 'storage_addresses.id')
                    ->leftJoin('locations', 'storage_addresses.location_id', 'locations.id')
                    ->leftJoin('stock_aliases', 'stocks.id', 'stock_aliases.stock_id')
                    ->where('stocks.del_flg', 0)
                    ->distinct()
                    ->orderBy('locations.id', 'desc')
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

            } else {

                $stocks =
                    InitialOrder::select('stocks.*', 'stock_storages.id as stock_storage_id', 'stock_storages.quantity', 'locations.name as location_name', 'storage_addresses.id as storage_address_id', 'storage_addresses.address')
                    ->Join('users', 'users.id', 'initial_orders.order_user_id')
                    ->Join('stocks', 'stocks.id', 'initial_orders.stock_id')
                    ->leftJoin('stock_storages', 'stocks.id',  'stock_storages.stock_id')
                    ->leftJoin('storage_addresses', 'stock_storages.storage_address_id', 'storage_addresses.id')
                    ->leftJoin('locations', 'storage_addresses.location_id', 'locations.id')
                    ->leftJoin('stock_aliases', 'stocks.id', 'stock_aliases.stock_id')
                    ->where('users.process_id', $process_id)
                    ->where('stocks.del_flg', 0)
                    ->distinct()
                    ->orderBy('locations.id', 'desc')
                    ->orderBy('updated_at', 'desc')
                    ->get();
            }



            return response()->json($stocks);
        } catch (Exception $e) {
            return response()->json($e);
        }
    }

    public function getAllStocks()
    {
        $stocks = Stock::select('id', 'name', 's_name')->where('del_flg', 0)->get();
        return response()->json($stocks);
    }

    public function getStockByNameAndSName(Request $request)
    {
        $name = $request->name;
        $s_name = $request->s_name;
        $com_name = $request->com_name;

        $stock = null;
        $supplier = null;

        if ($com_name) {
            $supplier = Supplier::where('name', $com_name)->first();
        }

        if ($supplier) {
            $stock = StockSupplier::select('stocks.id', 'stock_suppliers.supplier_id', 'stock_suppliers.lead_time')
                ->join('stocks', 'stocks.id', 'stock_suppliers.stock_id')
                ->where('stocks.name', $name)
                ->where('stocks.s_name', $s_name)
                ->where('stocks.del_flg', 0)
                ->where('stock_suppliers.supplier_id', $supplier->id)
                ->first();
        } else {
            $stock = Stock::select('id')
                ->where('name', $name)
                ->where('s_name', $s_name)
                ->where('del_flg', 0)
                ->first();
        }

        return response()->json($stock);
    }

    public function getStockByAlias(Request $request)
    {
        $alias = $request->alias;
        $stock_aliases = StockAlias::select('stock_id')->where('alias', $alias)->get();

        return response()->json($stock_aliases);
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


    // 発注依頼ユーザー紐づけ用途
    public function getUserAndProcess()
    {
        $users = User::select('users.id', 'users.name', 'users.process_id', 'processes.name as process_name')->leftJoin('processes', 'processes.id', 'users.process_id')->where('del_flg', 0)->get();

        return response()->json($users);
    }
}
