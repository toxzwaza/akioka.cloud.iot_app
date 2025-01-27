<?php

use App\Http\Controllers\ContentController;
use App\Http\Controllers\DataController;
use App\Http\Controllers\HeatStrokeController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\MainController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ShipmentController;
use App\Http\Controllers\StockController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use Illuminate\Support\Facades\Response;

////////// PWS用ルート //////////
Route::get('/manifest.json', function () {
    return Response::file(public_path('manifest.json'), [
        'Content-Type' => 'application/json',
    ]);
});
Route::get('/sw.js', function () {
    return response()->view('sw')->header('Content-Type', 'application/javascript');
});

//////////在庫管理システム （タブレット） //////////
// メイン画面
Route::get('/', [MainController::class, 'index'])->name('stock.home');
// 出庫画面
Route::get('/shipment', [ShipmentController::class, 'index'])->name('stock.shipment');
// 検索画面
Route::get('/search', [SearchController::class, 'index'])->name('stock.search');
// 在庫登録
Route::get('/stock/create', [InventoryController::class, 'create'])->name('stock.inventory.create');
// 在庫追加
Route::post('/stock', [InventoryController::class, 'store'])->name('stock.inventory.store');

// 発注依頼
Route::get('/order/create', [OrderController::class, 'create'])->name('stock.order.create');
// 発注追加
Route::post('/order', [OrderController::class, 'store'])->name('stock.order.store');




////////// 熱中症用 //////////
Route::get('/dashboard', [HeatStrokeController::class, 'dashboard'])->name('dashboard');
Route::get('/download', [HeatStrokeController::class ,'download'])->name('download');
Route::get('/setting', [HeatStrokeController::class ,'setting'])->name('setting');


////////// サイネージ用コンテンツ //////////
// 工場全体の作業場ごと温度・湿度・CO２濃度をマップにプロット
Route::get('/watchData', [ContentController::class, 'watchData'])->name('content.watchData');
// 施設使用状況
Route::get('/facilitySchedule', [ContentController::class, 'facilitySchedule'])->name('content.facilitySchedule');

////////// データ登録API //////////
Route::get('/data', [DataController::class, 'store']);

////////// データ取得API //////////
// MACアドレスを取得してplace_idを変換
Route::get('/getPlaceId', [DataController::class, 'getPlaceId']);
Route::get('/getFacilitySchedule', [ContentController::class, 'getFacilitySchedule'])->name('getFacilitySchedule');
Route::get('/getLatestData', [DataController::class, 'getLatestData'])->name('getLatestData');
Route::get('/getTempHumiCo2', [DataController::class, 'getTempHumiCo2'])->name('getTempHumiCo2');
