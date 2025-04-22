<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\ChatGptController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\OrderRequestController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// 物品依頼 稟議書アップロードAPI
Route::post('/order_request/upload_file', [OrderRequestController::class, 'uploadFile']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::get('/stock/uploadFile', [ApiController::class, 'uploadFile'])->name('stock.uploadFile');

// メッセージAPI
Route::post('/sendMessage', [MessageController::class, 'sendMessage'])->name('sendMessage');

// ChatGPT API
Route::post('/chatgpt', [ChatGptController::class, 'api'])->name('chatgpt.api');
