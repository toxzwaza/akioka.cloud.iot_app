<?php

use App\Http\Controllers\DataController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use Illuminate\Support\Facades\Response;

//PWS用ルート
Route::get('/manifest.json', function () {
    return Response::file(public_path('manifest.json'), [
        'Content-Type' => 'application/json',
    ]);
});

Route::get('/sw.js', function () {
    return Response::file(public_path('sw.js'), [
        'Content-Type' => 'application/javascript',
    ]);
});

// 管理画面

// サイネージ用コンテンツ

// データ登録API
Route::get('/data', [DataController::class, 'store']);
// MACアドレスを取得してplace_idを変換
Route::get('/getPlaceId', [DataController::class, 'getPlaceId']);

