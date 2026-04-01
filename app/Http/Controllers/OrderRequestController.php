<?php

namespace App\Http\Controllers;

use App\Models\OrderRequest;
use App\Models\StockStorage;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OrderRequestController extends Controller
{
    //
    public function uploadFile(Request $request)
    {
        $status = true;
        $message = '';
        $fileUrl = null;

        $file = $request->file('upload_file');
        $order_request_id = $request->order_request_id;

        Log::debug('[OrderRequest uploadFile] 開始', [
            'order_request_id' => $order_request_id,
            'has_upload_file' => $file !== null,
            'original_name' => $file?->getClientOriginalName(),
            'mime' => $file?->getMimeType(),
            'size' => $file?->getSize(),
            'is_valid' => $file?->isValid(),
            'upload_error' => $file ? $file->getError() : null,
        ]);

        try {
            // ファイル存在＆有効性チェック
            if (!$file || !$file->isValid()) {
                Log::warning('[OrderRequest uploadFile] ファイル無効', [
                    'order_request_id' => $order_request_id,
                    'has_file' => $file !== null,
                    'is_valid' => $file?->isValid(),
                    'error' => $file?->getError(),
                    'error_message' => $file?->getErrorMessage(),
                ]);
                throw new \Exception('有効なファイルがアップロードされていません。');
            }

            // タイムスタンプでファイル名を生成（例: 20250409143000.pdf）
            $timestampedFilename = now()->format('YmdHis') . '.pdf';

            $publicDisk = Storage::disk('public');
            $orderRequestDir = storage_path('app/public/order_request');

            if (! $publicDisk->exists('order_request')) {
                $made = $publicDisk->makeDirectory('order_request');
                if (! $made) {
                    Log::error('[OrderRequest uploadFile] order_request ディレクトリ作成失敗', [
                        'order_request_dir' => $orderRequestDir,
                        'public_root_writable' => is_writable(storage_path('app/public')),
                    ]);
                    throw new \Exception('保存用フォルダを作成できませんでした。storage/app/public の権限を確認してください。');
                }
            }

            // 保存（storage/app/public/order_request に保存）
            $path = $file->storeAs('order_request', $timestampedFilename, 'public');

            if ($path === false) {
                Log::error('[OrderRequest uploadFile] storeAs が false（保存失敗）', [
                    'order_request_id' => $order_request_id,
                    'timestamped_filename' => $timestampedFilename,
                    'order_request_dir' => $orderRequestDir,
                    'dir_exists' => is_dir($orderRequestDir),
                    'public_root' => storage_path('app/public'),
                    'public_root_writable' => is_writable(storage_path('app/public')),
                    'dir_writable' => is_dir($orderRequestDir) ? is_writable($orderRequestDir) : null,
                    'temp_path' => $file->getRealPath(),
                    'temp_readable' => $file->getRealPath() ? is_readable($file->getRealPath()) : null,
                ]);
                throw new \Exception('ファイルの保存に失敗しました。storage/app/public の書き込み権限やディスク容量を確認してください。');
            }

            $fileUrl = 'storage/' . $path;

            Log::debug('[OrderRequest uploadFile] 保存完了', [
                'order_request_id' => $order_request_id,
                'stored_path' => $path,
                'file_url' => $fileUrl,
            ]);

            // 該当データを取得し、パスを保存
            $order_request = OrderRequest::find($order_request_id);
            if (!$order_request) {
                Log::warning('[OrderRequest uploadFile] OrderRequest 未検出', [
                    'order_request_id' => $order_request_id,
                    'file_url' => $fileUrl,
                ]);
                throw new \Exception('指定されたIDの稟議書が見つかりません。');
            }

            $order_request->file_path = $fileUrl;
            $order_request->save();

            $message = 'ファイルアップロード成功';

            Log::info('[OrderRequest uploadFile] 成功', [
                'order_request_id' => $order_request_id,
                'file_url' => $fileUrl,
            ]);
        } catch (\Exception $e) {
            $status = false;
            $message = $e->getMessage();

            Log::error('[OrderRequest uploadFile] 失敗', [
                'order_request_id' => $order_request_id,
                'message' => $message,
                'exception' => $e::class,
                'trace' => $e->getTraceAsString(),
            ]);
        }

        Log::debug('[OrderRequest uploadFile] レスポンス', [
            'status' => $status,
            'message' => $message,
            'file_url' => $fileUrl,
        ]);

        return response()->json([
            'status' => $status,
            'message' => $message,
            'file_url' => $fileUrl ?? null,
        ]);
    }

    public function deleteFile(Request $request)
    {
        $status = true;
        $message = '';

        $file_path = $request->input('file_path');

        try {
            // ファイルパスの存在チェック
            if (!$file_path) {
                throw new \Exception('ファイルパスが指定されていません。');
            }

            // セキュリティ対策: order_requestディレクトリ内のファイルのみ削除可能にする
            if (!str_starts_with($file_path, 'storage/order_request/')) {
                throw new \Exception('不正なファイルパスです。');
            }

            // パストラバーサル攻撃対策: 危険な文字が含まれていないか確認
            if (str_contains($file_path, '..') || str_contains($file_path, '//')) {
                throw new \Exception('不正なファイルパスです。');
            }

            // storage/プレフィックスを削除してストレージパスに変換
            $storage_path = str_replace('storage/', '', $file_path);

            // ファイル存在チェック
            if (!Storage::disk('public')->exists($storage_path)) {
                throw new \Exception('ファイルが見つかりません。');
            }

            // ファイル削除
            Storage::disk('public')->delete($storage_path);

            $message = 'ファイル削除成功';
        } catch (\Exception $e) {
            $status = false;
            $message = $e->getMessage();
        }

        return response()->json([
            'status' => $status,
            'message' => $message,
        ]);
    }

    public function reorder(Request $request)
    {
        $status = true;
        $msg = "";
        $order_request_id = $request->order_request_id;

        try {
            // 元の発注依頼を取得
            $originalOrderRequest = OrderRequest::find($order_request_id);
            

            if(!$originalOrderRequest->new_stock_flg){ //既存品
                $stock_storage_id = 0;
                $stock_storage = StockStorage::where('stock_id', $originalOrderRequest->stock_id)->first();
                if($stock_storage){
                    $stock_storage_id = $stock_storage->id;
                }

                return redirect()->route('stock.inventory.show', ['stock_id' => $originalOrderRequest->stock_id, 'stock_storage_id' => $stock_storage_id, 'order_request_id' => $order_request_id]);
                

            }else{ //新規品

                return redirect()->route('stock.new_item.home', ['order_request_id' => $order_request_id]);
            }



            $msg = "再依頼を作成しました。新しい承認プロセスが開始されます。";

        } catch (Exception $e) {
            $status = false;
            $msg = $e->getMessage();
        }

        return response()->json(['status' => $status, 'msg' => $msg]);
    }
}
