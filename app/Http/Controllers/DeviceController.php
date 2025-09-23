<?php

namespace App\Http\Controllers;

use App\Models\Device;
use Exception;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    //
    public function store(Request $request)
    {
        $status = true;
        $msg = '';

        $name = $request->name;
        $token = $request->token;

        try {
            if (!$name) {
                $status = false;
                $msg = 'デバイス名が指定されていません。';
            } else {
                // 既存のデバイスを検索
                $device = Device::where('name', $name)->first();

                if ($device) {
                    // 既存のデバイスが存在する場合、最終アクセスを更新し、トークンが来ていれば更新
                    if ($token) {
                        $device->token = $token;
                        $msg = 'デバイスのトークンを更新しました。';
                    } else {
                        $msg = 'デバイスに最終アクセスを記録しました。';
                    }
                    $device->last_access_date = now();
                    $device->save();
                } else {
                    // 新しいデバイスを作成（トークンは任意）
                    $device = new Device();
                    $device->name = $name;
                    if ($token) {
                        $device->token = $token;
                    }
                    $device->last_access_date = now();
                    $device->save();
                    $msg = '新しいデバイスを登録しました。';
                }
            }
        } catch (Exception $e) {
            $status = false;
            $msg = $e->getMessage();
        }

        return response()->json(['status' => $status, 'msg' => $msg]);
    }

    public function updateLastAccess(Request $request)
    {
        $status = true;
        $msg = '';

        $device_name = $request->device_name;

        try {
            if ($device_name) {
                $device = Device::where('name', $device_name)->first();
                
                if ($device) {
                    $device->last_access_date = now();
                    $device->save();
                    $msg = '最終アクセス日を更新しました。';
                } else {
                    $status = false;
                    $msg = 'デバイスが見つかりませんでした。';
                }
            } else {
                $status = false;
                $msg = 'デバイス名が指定されていません。';
            }
        } catch (Exception $e) {
            $status = false;
            $msg = $e->getMessage();
        }

        return response()->json(['status' => $status, 'msg' => $msg]);
    }
}
