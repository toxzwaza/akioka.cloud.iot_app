<?php

namespace App\Http\Controllers;

use App\Models\InventoryOperationRecord;
use App\Models\StockStorage;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RetentionController extends Controller
{
    public function home()
    {
        return Inertia::render('Stock/Retention');
    }
    //
    public function index()
    {
        return Inertia::render('Stock/Retention/Index');
    }

    public function getRetentionStocks()
    {
        $stocks = StockStorage::select(
            'stocks.id as stock_id',
            'stocks.name',
            'stocks.s_name',
            'stocks.img_path',
            'stock_storages.id as stock_storage_id',
            'stock_storages.created_at as initial_date',
            'storage_addresses.address',
            'locations.id as location_id',
            'locations.name as location_name'
        )
            ->join('stocks', 'stocks.id', '=', 'stock_storages.stock_id')
            ->join('storage_addresses', 'storage_addresses.id', '=', 'stock_storages.storage_address_id')
            ->join('locations', 'locations.id', '=', 'storage_addresses.location_id')
            ->where('stocks.del_flg', 0)
            ->where('locations.id', 2)
            ->orderBy('storage_addresses.address', 'asc')
            ->get()
            ->map(function ($stock) {
                $stock->retention_code = 0;
                $stock->last_shipment_date = null;

                $latest_shipment_date = InventoryOperationRecord::select('created_at')
                    ->where('stock_id', $stock->stock_id)
                    ->where('inventory_operation_id', 2)
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($latest_shipment_date) {
                    if ($latest_shipment_date->created_at < now()->subMonths(6)) {
                        $stock->last_shipment_date = $latest_shipment_date->created_at;
                        $stock->retention_code = $latest_shipment_date->created_at <= now()->subMonths(12) ? 2 : 1;
                    }
                } else {
                    if ($stock->initial_date <= now()->subMonths(12)) {
                        $stock->retention_code = 2;
                    } elseif ($stock->initial_date <= now()->subMonths(6)) {
                        $stock->retention_code = 1;
                    }
                }

                // retention_codeが取得できない場合、nullを返すことで削除
                return $stock->retention_code ? $stock : null;
            })
            ->filter(); // nullの要素を削除

        return  response()->json($stocks);
    }
}
