<?php

namespace App\Http\Controllers;

use App\Models\StockAlias;
use Exception;
use Illuminate\Http\Request;

class StockAliasController extends Controller
{
    //
    public function edit(Request $request)
    {
        $status = true;
        try {
            $stock_alias_id = $request->stock_alias_id;
            $alias = $request->alias;
            $stock_alias = StockAlias::find($stock_alias_id);
            $stock_alias->alias = $alias;
            $stock_alias->save();
        } catch (Exception $e) {
            $status = false;
        }

        return response()->json($status);
    }
    public function create(Request $request)
    {
        $status = true;

        try {
            $stock_id = $request->stock_id;
            $alias = $request->alias;
            $stock_alias = new StockAlias();
            $stock_alias->stock_id = $stock_id;
            $stock_alias->alias = $alias;
            $stock_alias->save();
        } catch (Exception $e) {
            $status = false;
        }

        return response()->json($status);
    }

    public function delete(Request $request) {
        $status = true;

        try{
            $deleteAliasId = $request->deleteAliasId;

            foreach($deleteAliasId as $stock_alias_id){
                $stock_alias = StockAlias::find($stock_alias_id);
                $stock_alias->delete();
            }

        }catch(Exception $e){
            $status = false;
        }

        return response()->json($status);
            
    }
}
