<?php

namespace App\Http\Controllers;

use App\Models\NotifyQueue;
use App\Models\NotifyQueueUser;
use Illuminate\Http\Request;

class NotifyController extends Controller
{
    //
    public function getUnNotifyData()
    {
        $notifyQueue = NotifyQueue::
        select('id', 'title', 'msg', 'url')
        ->where('comp_flg', 0)
        ->get();

        foreach ($notifyQueue as $notify) {
            $notify->users = NotifyQueueUser::join('users', 'notify_queue_users.user_id', 'users.id')->where('notify_queue_id', $notify->id)->pluck('email');
        }

        return response()->json($notifyQueue);
    }

    public function completeNotifyQueue(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:notify_queues,id'],
        ]);

        $updated = NotifyQueue::where('id', $validated['id'])->update([
            'comp_flg' => 1,
        ]);

        return response()->json([
            'success' => $updated > 0,
            'id' => $validated['id'],
            'updated' => $updated,
        ]);
    }
}
