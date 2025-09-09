<?php

namespace App\Http\Controllers;

use App\Http\Services\Helper;
use App\Models\DeviceMessage;
use Exception;
use Illuminate\Http\Request;

class DeviceMessageController extends Controller
{
    //
    public function getDeviceMessages(Request $request)
    {
        $device_name = $request->device_name;

        try {
            $device_messages = DeviceMessage::select(
                'device_messages.id',
                'device_messages.priority',
                'to_users.name as to_user_name',
                'from_users.name as from_user_name',
                'to_devices.name as to_device_name',
                'from_devices.name as from_device_name',
                'device_messages.message',
                'device_messages.answer',
                'device_messages.created_at',
                'device_messages.link',
                'device_messages.read_flg'
            )
                ->join('users as to_users', 'to_users.id', '=', 'device_messages.to_user_id')
                ->join('users as from_users', 'from_users.id', '=', 'device_messages.from_user_id')
                ->join('devices as to_devices', 'to_devices.id', '=', 'device_messages.to_device_id')
                ->leftJoin('devices as from_devices', 'from_devices.id', '=', 'device_messages.from_device_id')
                ->where('to_devices.name', '=', $device_name) //自端末宛て
                // ->where('device_messages.read_flg', '=', 0) //未読
                ->where('device_messages.del_flg', '=', 0) //未削除
                ->orderBy('device_messages.priority', 'desc')
                ->orderBy('device_messages.created_at', 'desc')
                ->get();
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }


        return response()->json($device_messages);
    }

    public function confirm_message(Request $request)
    {
        $status = true;
        $msg = '';

        $device_message_ids = $request->input('device_massage_ids', []);

        try {
            if (!empty($device_message_ids)) {
                foreach ($device_message_ids as $device_message_id) {
                    $device_message = DeviceMessage::select('device_messages.*', 'to_users.name as to_user_name', 'from_users.name as from_user_name')
                    ->join('users as to_users', 'to_users.id', '=', 'device_messages.to_user_id')
                    ->join('users as from_users', 'from_users.id', '=', 'device_messages.from_user_id')
                    ->where('device_messages.id', '=', $device_message_id)
                    ->first();
                    if ($device_message) {
                        $device_message->read_flg = 1;
                        $device_message->save();

                        $title = "{$device_message->to_user_name}が確認されました。";
                        $msg = "送信メッセージ: {$device_message->message}\n\n\n確認者: {$device_message->to_user_name}\n\n";

                        // 送信元へ通知
                        Helper::createNotifyQueue(
                            $title,
                            $msg, // msg
                            "", // url
                            [$device_message->from_user_id]
                        );
                    }
                }
            }
        } catch (Exception $e) {
            $msg = $e->getMessage();
            $status = false;
        }

        return response()->json(['status' => $status, 'msg' => $msg]);
    }

    public function send_answer(Request $request)
    {
        $status = true;
        $msg = '';

        $device_message_id = $request->input('device_message_id');
        $answer = $request->input('answer');

        try {
            $device_message = DeviceMessage::select('device_messages.*', 'to_users.name as to_user_name', 'from_users.name as from_user_name')
                ->join('users as to_users', 'to_users.id', '=', 'device_messages.to_user_id')
                ->join('users as from_users', 'from_users.id', '=', 'device_messages.from_user_id')
                ->where('device_messages.id', '=', $device_message_id)
                ->first();

            if ($device_message) {
                $device_message->answer = $answer;
                $device_message->read_flg = 1;
                $device_message->save();

                $title = "{$device_message->to_user_name}から返信がありました。";
                $msg = "送信者: {$device_message->from_user_name}\n\n送信メッセージ: {$device_message->message}\n\n\n回答者: {$device_message->to_user_name}\n\n回答: {$device_message->answer}";

                // 送信元へ通知
                Helper::createNotifyQueue(
                    $title,
                    $msg, // msg
                    "", // url
                    [$device_message->from_user_id]
                );
            }
        } catch (Exception $e) {
            $msg = $e->getMessage();
            $status = false;
        }

        return response()->json(['status' => $status]);
    }
}
