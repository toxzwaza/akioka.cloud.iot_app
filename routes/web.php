<?php

use App\Http\Controllers\AcceptController;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\ARController;
use App\Http\Controllers\CheckOrderRequestController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\DataController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\DeviceMessageController;
use App\Http\Controllers\HeatStrokeController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\LunchController;
use App\Http\Controllers\MainController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NewItemController;
use App\Http\Controllers\NotifyController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderRequestController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuestionnaireController;
use App\Http\Controllers\ReceiveController;
use App\Http\Controllers\RetentionController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ShipmentController;
use App\Http\Controllers\StockAliasController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StockRequestController;
use App\Http\Controllers\TestController;
use App\Models\ProductAlias;
use App\Models\StockAliase;
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
// デバイスメッセージ取得
Route::get('/device-message', [DeviceMessageController::class, 'getDeviceMessages'])->name('device-message.getDeviceMessages');
Route::post('/device-message', [DeviceMessageController::class, 'confirm_message'])->name('device-message.confirm_message');
Route::post('/device-message/send-answer', [DeviceMessageController::class, 'send_answer'])->name('device-message.send_answer');

// 出庫画面
Route::get('/shipment', [ShipmentController::class, 'index'])->name('stock.shipment');
Route::post('/shipment', [ShipmentController::class, 'store'])->name('stock.shipment.store');

// 検索画面
Route::get('/search', [SearchController::class, 'index'])->name('stock.search');
// 検索結果表示画面
Route::get('/search/result', [SearchController::class, 'result'])->name('stock.search.result');

// 格納先・アドレス編集
Route::get('/stock/getLocations', [InventoryController::class, 'getLocations'])->name('stock.getLocations');
Route::get('/stock/getStorageAddresses/{location_id}', [InventoryController::class, 'getStorageAddresses'])->name('stock.getStorageAddresses');
Route::post('/stock/createStockStorage', [InventoryController::class, 'createStockStorage'])->name('stock.createStockStorage');

// 在庫詳細画面
Route::get('/stock/{stock_id}/{stock_storage_id}', [InventoryController::class, 'show'])->name('stock.inventory.show');
// 在庫ファイル更新
Route::post('/stock/updateFile', [InventoryController::class, 'updateFile'])->name('stock.updateFile');
// 在庫数量変更
Route::post('/stock/changeQuantity', [InventoryController::class, 'changeQuantity'])->name('stock.changeQuantity');
// 略名編集
Route::put('/stock/editAlias', [StockAliasController::class, 'edit'])->name('stock.editAlias');
// 略名作成
Route::post('/stock/createAlias', [StockAliasController::class, 'create'])->name('stock.createAlias');
// 略名削除
Route::post('/stock/deleteAlias', [StockAliasController::class, 'delete'])->name('stock.deleteAlias');


// 在庫登録
Route::get('/stock/create', [InventoryController::class, 'create'])->name('stock.inventory.create');
// 在庫追加
Route::post('/stock', [InventoryController::class, 'store'])->name('stock.inventory.store');
// 画像削除
Route::delete('/stock/deleteImage', [InventoryController::class, 'deleteImage'])->name('stock.deleteImage');

// 発注依頼
Route::get('/order/create', [OrderController::class, 'create'])->name('stock.order.create');
// 発注追加
Route::post('/order', [OrderController::class, 'store'])->name('stock.order.store');
// 発注依頼削除
Route::delete('/order/{order_request_id}', [OrderController::class, 'delete'])->name('stock.order.delete');
// 確定発注依頼取得
Route::get('/order/getConfirmOrderRequest', [OrderController::class, 'getConfirmOrderRequest'])->name('stock.order.getConfirmOrderRequest');

// 納品画面
Route::get('/receive', [ReceiveController::class, 'home'])->name('stock.receive.home');
Route::get('/receive/index', [ReceiveController::class, 'index'])->name('stock.receive');
Route::get('/receive/archive', [ReceiveController::class, 'archive'])->name('stock.receive.archive');
Route::get('/receive/receipt', [ReceiveController::class, 'receipt'])->name('stock.receive.receipt');
// 納品書が登録されていいない注文リスト
Route::get('/receive/getInitialOrders', [ReceiveController::class, 'getInitialOrders'])->name('stock.receive.getInitialOrders');
Route::post('/receive/uploadFile', [ ReceiveController::class, 'uploadFile'])->name('stock.receive.uploadFile');
Route::get('/receive/deleteInitialOrder/{order_id}', [ReceiveController::class, 'deleteInitialOrder'])->name('stock.receive.delete.initialOrder');
// 納品書が登録されている注文リスト
Route::get('/receive/getAlreadyDelifileInitialOrders', [ReceiveController::class, 'getAlreadyDelifileInitialOrders'])->name('stock.receive.getAlreadyDelifileInitialOrders');
Route::get('/receive/delivery/{id}', [ReceiveController::class, 'delivery'])->name('stock.receive.delivery');
Route::post('/receive/updateQuantityPerOrg', [ReceiveController::class, 'updateQuantityPerOrg'])->name('stock.receive.updateQuantityPerOrg');
Route::get('/receive/getReceiptOrders', [ReceiveController::class, 'getReceiptOrders'])->name('stock.receive.getReceiptOrders');
Route::get('/receive/updateReceipt/{id}', [ReceiveController::class, 'updateReceipt'])->name('stock.receive.updateReceipt');
Route::post('/receive/store', [ReceiveController::class, 'store'])->name('stock.receive.store');
Route::get('/receive/updateDelivery', [ReceiveController::class, 'updateDelivery'])->name('stock.receive.updateDelivery');
Route::get('/receive/getClassifications', [ReceiveController::class, 'getClassifications'])->name('stock.receive.getClassifications');
Route::get('/receive/getSuppliers', [ReceiveController::class, 'getSuppliers'])->name('stock.receive.getSuppliers');

Route::get('/receive/none_storage/{order_id}', [ReceiveController::class, 'none_storage'])->name('stock.receive.none_storage');

// 承認登録
Route::put('/accept/order-request', [AcceptController::class, 'update'])->name('accept.order-request.update');
// 発注用承認画面
Route::get('/accept/order-request', [AcceptController::class, 'index'])->name('accept.order-request');

// 滞留画面
Route::get('/retentions', [RetentionController::class, 'home'])->name('stock.retention.home');
Route::get('/retentions/stocks', [RetentionController::class, 'index'])->name('stock.retention');
Route::get('/retentions/stocks/getRetentionStocks', [RetentionController::class, 'getRetentionStocks'])->name('stock.retention.getRetentionStocks');

// 現場物品依頼画面
Route::get('/requests', [StockRequestController::class, 'home'])->name('stock.request.home');
Route::post('/requests/store', [StockRequestController::class, 'store'])->name('stock.request.store');
Route::post('/requests/complete', [StockRequestController::class, 'complete'])->name('stock.request.complete');
Route::delete('/requests/delete', [StockRequestController::class, 'delete'])->name('stock.request.delete');
Route::get('/requests/getAdminStockRequestOrders', [StockRequestController::class, 'getAdminStockRequestOrders'])->name('stock.request.getAdminStockRequestOrders');
// 足りない分を発注依頼
Route::post('/requests/order', [StockRequestController::class, 'order'])->name('stock.request.order');
// Route::get('/requests/stocks', [RequestController::class, 'index'])->name('stock.request');
// Route::get('/requests/stocks/getRequestStocks', [RequestController::class, 'getRequestStocks'])->name('stock.request.getRequestStocks');

// 新規品依頼画面
Route::get('/new_item', [NewItemController::class, 'home'])->name('stock.new_item.home');
Route::post('/stock/new-item/store', [NewItemController::class, 'store'])->name('stock.new-item.store');

//////////弁当注文用 //////////
Route::get('/lunch', [LunchController::class, 'index'])->name('lunch.home');
Route::get('/lunch/test', [LunchController::class, 'test'])->name('lunch.test');
// 注文
Route::post('/lunch/order', [LunchController::class, 'order'])->name('lunch.order');
// 受け取り
Route::post('/lunch/receive', [LunchController::class, 'receive'])->name('lunch.receive');
Route::get('/lunch/getOrders', [LunchController::class, 'getOrders'])->name('lunch.getOrders');
Route::get('/lunch/getUsers', [LunchController::class, 'getUsers'])->name('lunch.getUsers');

// 物品依頼確認用
Route::get('/check_order_request', [CheckOrderRequestController::class, 'index'])->name('stock.check_order_request.home');
Route::get('/check_order_request/getOrderRequests', [CheckOrderRequestController::class, 'getOrderRequests'])->name('stock.check_order_request.getOrderRequests');

////////// API用 //////////
Route::get('/getGroups', [ApiController::class, 'getGroups'])->name('getGroups');
Route::get('/getUsersByGroup', [ApiController::class, 'getUsersByGroup'])->name('getUsersByGroup');
Route::get('/getAllStocks', [ApiController::class, 'getAllStocks'])->name('getAllStocks');
Route::get('/getStockByAlias', [ApiController::class, 'getStockByAlias'])->name('getStockByAlias');
Route::get('/getStocks', [ApiController::class, 'getStocks'])->name('getStocks');
Route::get('/getUserAndProcess', [ApiController::class, 'getUserAndProcess'])->name('getUserAndProcess');
Route::get('/getStockByNameAndSName', [ApiController::class, 'getStockByNameAndSName'])->name('getStockByNameAndSName');
// 未通知データ
Route::get('/getUnNotifyData', [NotifyController::class, 'getUnNotifyData'])->name('getUnNotifyData');

// 在庫格納先アドレス取得
Route::get('/getStockStorages', [ApiController::class, 'getStockStorages'])->name('getStockStorages');

////////// 熱中症用 //////////
Route::get('/dashboard', [HeatStrokeController::class, 'dashboard'])->name('dashboard');
Route::get('/download', [HeatStrokeController::class ,'download'])->name('download');
Route::get('/setting', [HeatStrokeController::class ,'setting'])->name('setting');


////////// サイネージ用コンテンツ //////////
// 工場全体の温度・湿度・CO２濃度をマップにプロット
Route::get('/watchData', [ContentController::class, 'watchData'])->name('content.watchData');
// 作業場所ごとの環境データを表示する
Route::get('/factoryEnvMonitor/{place_id}', [ContentController::class, "factoryEnvMonitor"])->name('factoryEnvMonitor');
Route::get('/getData/{place_id}', [ContentController::class, 'getData'])->name('getData');
Route::get('/getWeather', [ContentController::class, 'getWeather'])->name('getWeather');

// 施設使用状況
Route::get('/facilitySchedule', [ContentController::class, 'facilitySchedule'])->name('content.facilitySchedule');
// 納品完了表示画面
// 食堂後ろモニター
Route::get('/receive/complete/back', [ContentController::class, 'receiveCompleteBack'])->name('stock.receive.complete.back');
// 現場モニター
Route::get('/receive/complete', [ContentController::class, 'receiveComplete'])->name('stock.receive.complete');

// 物品依頼データ表示モニター
Route::get('/order-request/complete', [ContentController::class, 'orderRequestComplete'])->name('order-request.complete');


////////// データ登録API //////////
Route::get('/data', [DataController::class, 'store']);

////////// データ取得API //////////
// MACアドレスを取得してplace_idを変換
Route::get('/getPlaceId', [DataController::class, 'getPlaceId']);
Route::get('/getFacilitySchedule', [ContentController::class, 'getFacilitySchedule'])->name('getFacilitySchedule');
Route::get('/getLatestData', [DataController::class, 'getLatestData'])->name('getLatestData');
Route::get('/getTempHumiCo2', [DataController::class, 'getTempHumiCo2'])->name('getTempHumiCo2');



Route::get('/test', [TestController::class, 'test'])->name('test');

Route::post('/device-login', [DeviceController::class, 'store'])->name('device-login');

// OTEX AR用サンプル
Route::get('/ar', [ARController::class, 'index'])->name('ar.index');
Route::get('/questionnaire/{uid}', [QuestionnaireController::class, 'index'])->name('questionnaire.index');
