<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\ChatGptController;
use App\Http\Controllers\ConservationApiController;
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

// 別システム連携用 API（ConservationApiController）
Route::get('/stocks', [ConservationApiController::class, 'stockIndex']);
Route::get('/stocks/{id}', [ConservationApiController::class, 'stockShow']);
Route::post('/stocks', [ConservationApiController::class, 'stockStore']);
Route::put('/stocks/{id}', [ConservationApiController::class, 'stockUpdate']);
Route::patch('/stocks/{id}', [ConservationApiController::class, 'stockUpdate']);
Route::delete('/stocks/{id}', [ConservationApiController::class, 'stockDestroy']);

// 在庫格納先数量の減算（単体・配列対応）※ 固定パスのため parameter より前に定義
Route::post('/stock-storages/subtract', [ConservationApiController::class, 'stockStorageSubtract'])
    ->name('api.stock-storages.subtract');

// 棚卸用：在庫格納先の数量を上書き
Route::put('/stock-storages/{id}', [ConservationApiController::class, 'stockStorageUpdateQuantity'])
    ->name('api.stock-storages.update-quantity');

Route::get('/users', [ConservationApiController::class, 'userIndex']);
Route::get('/users/{id}', [ConservationApiController::class, 'userShow']);
