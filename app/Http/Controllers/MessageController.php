<?php

namespace App\Http\Controllers;

use App\Http\Services\Helper;
use Exception;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    //
    public function sendMessage(Request $request)
    {
        $status  = true;
        $msg ="";


        // 送信先ユーザID 配列
        $notify_users = $request->notify_user ?? [];

        // メッセージ
        $message = $request->message ?? '';

        if (!is_array($notify_users) || count($notify_users) === 0 || !$message) {
            return response()->json(['status' => false, 'msg' => 'メッセージを入力してください。']);
        }

        try {
            foreach ($notify_users as $notify_user) {
                
                // メッセージ送信
                Helper::sendNotify($notify_user, $message);
            }
        } catch (Exception $e) {
            $status = false;
            $msg = $e->getMessage();
        }

        return response()->json(['status' => $status, 'msg' => $msg]);
    }
}
