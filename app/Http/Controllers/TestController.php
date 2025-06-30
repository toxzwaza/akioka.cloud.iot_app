<?php

namespace App\Http\Controllers;

use App\Http\Services\ChatGpt;
use App\Models\InitialOrder;
use App\Models\LunchOrder;
use App\Models\OrderRequest;
use App\Models\Stock;
use App\Models\StockPriceArchive;
use App\Models\StockSupplier;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Helper as ServicesHelper;
use App\Services\Method;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Google\Auth\Credentials\ServiceAccountCredentials;

class TestController extends Controller
{
    //
    public function test()
    {
        $stocks = Stock::select(
            'stocks.id', 
            'suppliers.name as supplier_name', 
            'stocks.name', 
            'stocks.s_name', 
            'stocks.memo', 
            'stocks.price', 
            'stocks.solo_unit', 
            'stocks.org_unit', 
            'stocks.quantity_per_org',
            'stocks.updated_at'
        )
        ->join('stock_suppliers', 'stock_suppliers.stock_id', '=', 'stocks.id')
        ->join('suppliers', 'suppliers.id', '=', 'stock_suppliers.supplier_id')
        ->where('stocks.del_flg', '=', 0)
        ->whereNotNull('stocks.price')
        ->get();

        $csvData = $stocks->map(function($stock) {
            return [
                $stock->supplier_name,
                $stock->name,
                $stock->s_name,
                $stock->memo,
                $stock->price,
                $stock->solo_unit,
                $stock->org_unit,
                $stock->quantity_per_org,
                $stock->updated_at
            ];
        });

        $fileName = 'stocks.csv';
        $file = fopen($fileName, 'w');
        fputcsv($file, ['発注先名', '品名', '品番', 'メモ', '価格', '単位１', '単位２', '換算値', '更新日時']);

        foreach ($csvData as $line) {
            fputcsv($file, $line);
        }

        fclose($file);

        return response()->download($fileName)->deleteFileAfterSend(true);

        // $res = Helper::sendNotification('e0YdX65c7qwXcCobPK_kfj:APA91bFrQE-w-R61OhUVpJatZVBFYmbESL83iJ2aYZaO8IFF1AmV1HHOoJdKedhdybiMV71PrNdgT8TH_HjiQFUDDKGWBcDcSNrSjMHx43k4D3wISmr6Nhw', 'test', 'test');

        // return $res;
    }
}
