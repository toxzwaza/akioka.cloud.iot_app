<?php

use App\Http\Controllers\ContentController;
use App\Http\Controllers\DataController;
use App\Http\Controllers\MainController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use Illuminate\Support\Facades\Response;

////PWS用ルート
Route::get('/manifest.json', function () {
    return Response::file(public_path('manifest.json'), [
        'Content-Type' => 'application/json',
    ]);
});
Route::get('/sw.js', function () {
    return response()->view('sw')->header('Content-Type', 'application/javascript');
});

// 管理画面
Route::get('/', [MainController::class, 'index'])->name('home');
Route::get('/dashboard', [MainController::class, 'dashboard'])->name('dashboard');

Route::get('/download', [MainController::class ,'download'])->name('download');

Route::get('/setting', [MainController::class ,'setting'])->name('setting');


//// サイネージ用コンテンツ
// 工場全体の作業場ごと温度・湿度・CO２濃度をマップにプロット
Route::get('/watchData', [ContentController::class, 'watchData'])->name('content.watchData');
// 施設使用状況
Route::get('/facilitySchedule', [ContentController::class, 'facilitySchedule'])->name('content.facilitySchedule');

//// データ登録API
Route::get('/data', [DataController::class, 'store']);

//// データ取得API
// MACアドレスを取得してplace_idを変換
Route::get('/getPlaceId', [DataController::class, 'getPlaceId']);
Route::get('/getFacilitySchedule', [ContentController::class, 'getFacilitySchedule'])->name('getFacilitySchedule');
