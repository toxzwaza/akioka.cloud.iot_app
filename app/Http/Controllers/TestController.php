<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\StockPriceArchive;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TestController extends Controller
{
    //
    public function test()
    {
        return Inertia::render('Stock/Detail');

    }
}
