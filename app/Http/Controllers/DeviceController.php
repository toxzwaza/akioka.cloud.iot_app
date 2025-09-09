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
            if ($name && $token) {
                $device = new Device();
                $device->name = $name;
                $device->token = $token;
                $device->save();
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
