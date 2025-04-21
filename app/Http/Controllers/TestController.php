<?php

namespace App\Http\Controllers;

use App\Http\Services\Helper;
use App\Models\InitialOrder;
use App\Models\LunchOrder;
use App\Models\OrderRequest;
use App\Models\Stock;
use App\Models\StockPriceArchive;
use App\Models\StockSupplier;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Method;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TestController extends Controller
{
    //
    public function test()
    {

    }
}
