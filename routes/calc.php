<?php

use App\Http\Controllers\CalcController;
use App\Http\Controllers\OcrController;
use Illuminate\Support\Facades\Route;

// 新規時検索
Route::get('/getFigures', [CalcController::class, 'getFigures'])->name('calc.getFigures');

// 棚卸
Route::get('', [CalcController::class, 'index'])->name('calc.home');
Route::get('/search', [CalcController::class, 'search'])->name('calc.search');
Route::get('/new', [CalcController::class, 'new'])->name('calc.new');

// 棚卸登録画面
Route::get('/show/{id}', [CalcController::class, 'show'])->name('calc.show');
// 新規品棚卸画面
Route::get('/show/new/{id}', [CalcController::class, 'create_new_data'])->name('calc.show.new');
Route::post('/store', [CalcController::class, 'store'])->name('calc.store');
Route::get('/destroy', [CalcController::class, 'destroy'])->name('calc.destroy');
Route::get('/api/getProducts', [CalcController::class, 'getProducts'])->name('api.getProducts');
// 完了登録
Route::get('/complete/{calc_product_data_id}', [CalcController::class, 'calc_complete'])->name('calc.complete');
Route::get('/cancel_complete/{calc_product_data_id}', [CalcController::class, 'calc_cancel_complete'])->name('calc.cancel_complete');

Route::get('/api/getCalcProductStatements', [CalcController::class, 'getCalcProductStatements'])->name('api.getCalcProductStatements');
Route::get('/api/exportJson', [CalcController::class, 'exportJson'])->name('api.exportJson');

Route::post('/login', [CalcController::class, 'login'])->name('calc.login');
Route::get('/logout', [CalcController::class, 'logout'])->name('calc.logout');


// OCR
Route::get('/ocr', [OcrController::class, 'index'])->name('ocr.index');