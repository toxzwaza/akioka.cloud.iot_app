<?php

namespace App\Http\Controllers;

use App\Models\Classification;
use App\Models\InitialOrder;
use App\Models\Process;
use App\Models\Stock;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SearchController extends Controller
{
    //
    public function index(Request $request)
    {
        $search = $request->search;

        $processes = Process::all();
        $users = User::where('del_flg', 0)->get();
        $classifications = Classification::all();
        return Inertia::render('Stock/Search', ['processes' => $processes, 'users' => $users, 'search' => $search, 'classifications' => $classifications ]);
    }
    public function result(Request $request)
    {
        $stock_name = $request->stock_name;
        $stock_s_name = $request->stock_s_name;
        $address_id = $request->address_id;
        $stock_id = $request->stock_id;
        $alias = $request->alias;
        $process_id = $request->process_id;
        $user_id = $request->user_id;
        $classification_id = $request->classification_id;

        $search = [
            'stock_name' => $stock_name,
            'stock_s_name' => $stock_s_name,
            'address_id' => $address_id,
            'stock_id' => $stock_id,
            'alias' => $alias,
            'process_id' => $process_id,
            'user_id' => $user_id,
            'classification_id' => $classification_id
        ];


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
            } else {

                $query =
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
                    ->orderBy('updated_at', 'desc');

                if ($user_id) {
                    $query->where('initial_orders.order_user_id', $user_id);
                }
            }


            if ($stock_name) {
                $query->where('stocks.name', 'like', '%' . $stock_name . '%');
            }

            if ($stock_s_name) {
                $query->where('stocks.s_name', 'like', '%' . $stock_s_name . '%');
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

            if($classification_id){
                $query->where('stocks.classification_id', $request->classification_id);
            }

            $stocks = $query->get();


        } catch (Exception $e) {

            
        }

        return Inertia::render('Stock/SearchResult', ['stocks' => $stocks, 'search' => $search]);
    }

    public function search() {}
}
