<?php

use App\Http\Controllers\DataController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;


// 管理画面

// サイネージ用コンテンツ

// データ登録API
Route::post('/data', [DataController::class, 'store']);

