<?php

namespace App\Http\Controllers;

use App\Http\Services\ChatGpt;
use Illuminate\Http\Request;

class ChatGptController extends Controller
{
    //
    public function api(Request $request){
        $message = $request->message;
        $response = ChatGpt::getResponse($message);

        return response()->json($response);
    }
}
