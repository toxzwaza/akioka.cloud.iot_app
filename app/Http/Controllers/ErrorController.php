<?php

namespace App\Http\Controllers;

use App\Models\NotifyQueue;
use App\Models\NotifyQueueUser;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ErrorController extends Controller
{
    //
    public function deviceError()
    {
        $notify_queue =new NotifyQueue();
        $notify_queue->title = '端末IDエラー';
        $notify_queue->msg = '端末IDが登録されていません。';
        $notify_queue->url = '';
        $notify_queue->save();

        $notify_queue_user = new NotifyQueueUser();
        $notify_queue_user->notify_queue_id = $notify_queue->id;
        $notify_queue_user->user_id = 91; //管理者村上
        $notify_queue_user->save();

        return Inertia::render('Stock/DeviceError');
    }
}
