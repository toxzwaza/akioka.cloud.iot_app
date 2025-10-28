<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\ChatGptController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\OrderRequestController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// 物品依頼 稟議書アップロードAPI
Route::post('/order_request/upload_file', [OrderRequestController::class, 'uploadFile']);
// 納品書 アップロードAPI
Route::post('/file_upload/deli_file', [ApiController::class, 'deliFileUpload'])->name('deliFileUpload');

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::get('/stock/uploadFile', [ApiController::class, 'uploadFile'])->name('stock.uploadFile');

// メッセージAPI
Route::post('/sendMessage', [MessageController::class, 'sendMessage'])->name('sendMessage');

// ChatGPT API
Route::post('/chatgpt', [ChatGptController::class, 'api'])->name('chatgpt.api');

// 承認用API
Route::get('/order_request/approval_requests', [ApiController::class, 'approvalRequests'])->name('order_request.approval_requests');
