<?php

namespace App\Http\Controllers;

use App\Models\OrderRequest;
use App\Models\StockStorage;
use Exception;
use Illuminate\Http\Request;
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


        try {
            // ファイル存在＆有効性チェック
            if (!$file || !$file->isValid()) {
                throw new \Exception('有効なファイルがアップロードされていません。');
            }

            // タイムスタンプでファイル名を生成（例: 20250409143000.pdf）
            $timestampedFilename = now()->format('YmdHis') . '.pdf';

            // 保存（storage/app/public/order_request に保存）
            $path = $file->storeAs('order_request', $timestampedFilename, 'public');

            $fileUrl = 'storage/' . $path;

            // 該当データを取得し、パスを保存
            $order_request = OrderRequest::find($order_request_id);
            if (!$order_request) {
                throw new \Exception('指定されたIDの稟議書が見つかりません。');
            }

            $order_request->file_path = $fileUrl;
            $order_request->save();

            $message = 'ファイルアップロード成功';
        } catch (\Exception $e) {
            $status = false;
            $message = $e->getMessage();
        }

        return response()->json([
            'status' => $status,
            'message' => $message,
            'file_url' => $fileUrl ?? null,
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

            }



            $msg = "再依頼を作成しました。新しい承認プロセスが開始されます。";

        } catch (Exception $e) {
            $status = false;
            $msg = $e->getMessage();
        }

        return response()->json(['status' => $status, 'msg' => $msg]);
    }
}
