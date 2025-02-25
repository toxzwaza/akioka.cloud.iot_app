<?php

namespace App\Http\Controllers;

use App\Http\Services\Helper;
use App\Models\Stock;
use App\Models\StockPriceArchive;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TestController extends Controller
{
    //
    public function test()
    {
        $client = new Client();
        $url = 'http://127.0.0.1:8000/api/sendMessage';

        $notify_users = ['to-murakami@akioka-ltd.jp'];
        $message = 'aaa';

        $response = $client->post($url, [
            'form_params' => [
                'notify_users' => $notify_users,
                'message' => $message
            ]
        ]);

        // return Inertia::render('Stock/Detail');

    }
}
