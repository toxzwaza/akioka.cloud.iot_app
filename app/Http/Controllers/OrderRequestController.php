<?php

namespace App\Http\Controllers;

use App\Models\OrderRequest;
use Illuminate\Http\Request;

class OrderRequestController extends Controller
{
    //
    public function uploadFile(Request $request)
    {
        $status = true;

        $file = $request->file('upload_file');
        $order_request_id = $request->order_request_id;

        try {
            // タイムスタンプでファイル名を再生成（例: 20250409143000.pdf）
            $timestampedFilename = now()->format('YmdHis') . '.pdf';

            // 保存先パス（public/order_request フォルダ内に保存）
            $path = $file->storeAs('public/order_request', $timestampedFilename);

            $fileUrl = asset('storage/' . $path);

            $order_request = OrderRequest::find($order_request_id);
            if ($order_request) {
                $order_request->file_path = $fileUrl;
                $order_request->save();
            }
        } catch (\Exception $e) {
            $status = false;
        }
        return response()->json([
            'status' => $status
        ]);
    }
}
