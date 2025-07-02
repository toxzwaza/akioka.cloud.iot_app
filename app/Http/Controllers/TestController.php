<?php

namespace App\Http\Controllers;

use App\Http\Services\ChatGpt;
use App\Http\Services\Helper;
use App\Models\DeviceMessage;
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

        $initial_orders = InitialOrder::whereNull('name')->where('receipt_flg', 0)->get();
        

        // サンプルメッセージを作成

        // Helper::createDeviceMessage(
        //     0,
        //     21,
        //     21,
        //     91,
        //     91,
        //     "改行をテスト\n物品依頼の件ですが、○○は間違いではありませんか？\n確認をお願いします。"
        // );

        // $message = new DeviceMessage();
        // $message->priority = 0;
        // $message->to_device_id = 21;
        // $message->from_device_id = 21;
        // $message->to_user_id = 91;
        // $message->from_user_id = 91;
        // $message->read_flg = 0;
        // $message->del_flg = 0;
        // $message->message = "改行をテスト\n物品依頼の件ですが、○○は間違いではありませんか？\n確認をお願いします。";
        // $message->save();


        // $res = Helper::sendNotification('ePV0Lrqp-hld0Szo9Sopc3:APA91bHU_fEgOB_9YV4iGxHT86jh1WeV9Oj5KL_hRugg2yJryR7KnDDLIukouqW5ze7ec-NQYBiyiAo9WNqkEAG7daGo23_cSny0z8iqLKvWo-6hSx4N5LM', 'test', 'test');

        // return $res;

        // $stocks = Stock::select(
        //     'stocks.id', 
        //     'suppliers.name as supplier_name', 
        //     'stocks.name', 
        //     'stocks.s_name', 
        //     'stocks.memo', 
        //     'stocks.price', 
        //     'stocks.solo_unit', 
        //     'stocks.org_unit', 
        //     'stocks.quantity_per_org',
        //     'stocks.updated_at'
        // )
        // ->join('stock_suppliers', 'stock_suppliers.stock_id', '=', 'stocks.id')
        // ->join('suppliers', 'suppliers.id', '=', 'stock_suppliers.supplier_id')
        // ->where('stocks.del_flg', '=', 0)
        // ->whereNotNull('stocks.price')
        // ->get();

        // $csvData = $stocks->map(function($stock) {
        //     return [
        //         $stock->supplier_name,
        //         $stock->name,
        //         $stock->s_name,
        //         $stock->memo,
        //         $stock->price,
        //         $stock->solo_unit,
        //         $stock->org_unit,
        //         $stock->quantity_per_org,
        //         $stock->updated_at
        //     ];
        // });

        // $fileName = 'stocks.csv';
        // $file = fopen($fileName, 'w');
        // fputcsv($file, ['発注先名', '品名', '品番', 'メモ', '価格', '単位１', '単位２', '換算値', '更新日時']);

        // foreach ($csvData as $line) {
        //     fputcsv($file, $line);
        // }

        // fclose($file);

        // return response()->download($fileName)->deleteFileAfterSend(true);

    }
}
