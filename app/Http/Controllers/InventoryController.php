<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\StockStorage;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InventoryController extends Controller
{
    //
    public function create(){

    }
    public function store(Request $request){

    }
    public function show($id){
        $stock = Stock::find($id);
        $stock_storage = StockStorage::select('stock_storages.*', 'locations.name as location_name', 'storage_addresses.address')->join('storage_addresses', 'stock_storages.storage_address_id', 'storage_addresses.id')
        ->join('locations', 'storage_addresses.location_id', 'locations.id')->where('stock_id', $id)->get();
        
        $stock->stock_storage = $stock_storage;

        return Inertia::render('Stock/Inventory', ['stock' => $stock ]);
    }

}
