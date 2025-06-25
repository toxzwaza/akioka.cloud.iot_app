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

        // $res = Helper::sendNotification('e0YdX65c7qwXcCobPK_kfj:APA91bFrQE-w-R61OhUVpJatZVBFYmbESL83iJ2aYZaO8IFF1AmV1HHOoJdKedhdybiMV71PrNdgT8TH_HjiQFUDDKGWBcDcSNrSjMHx43k4D3wISmr6Nhw', 'test', 'test');

        // return $res;
    }
}
