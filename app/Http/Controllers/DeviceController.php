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
}
