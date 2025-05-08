<?php

namespace App\Http\Controllers;

use App\Http\Services\Helper;
use App\Models\OrderRequest;
use App\Models\OrderRequestApproval;
use App\Models\Stock;
use App\Models\StockSupplier;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AcceptController extends Controller
{
    //
    public function index(Request $request)
    {
        $user_id = $request->user_id;

        $query = DB::table('order_request_approvals')
            ->select(
                'order_requests.id',
                'order_requests.stock_id',
                'order_requests.id as order_request_id',
                'order_requests.accept_flg',
                'stocks.img_path',
                'stocks.name',
                'stocks.s_name',
                'stocks.price as stock_price',
                'stocks.url',
                'order_requests.quantity', //必要数量
                'order_requests.unit', //必要数量単位
                'order_requests.now_quantity', //現在数量
                'order_requests.now_quantity_unit', //現在数量単位
                'order_requests.digest_date', //消化予定日
                'order_requests.desire_delivery_date', //希望納期
                'order_requests.description', //備考
                'order_requests.created_at',
                'request_users.name as request_user_name',
                'users.name as user_name',
                'order_requests.postage',
                'order_requests.calc_price',
                'order_requests.price',
                DB::raw('CONCAT("/storage/", SUBSTRING_INDEX(order_requests.file_path, "storage/", -1)) as file_path'),
                'suppliers.name as supplier_name',
                'order_request_approvals.id as order_request_approval_id',
                'order_request_approvals.final_flg',
                'order_request_approvals.user_id as approval_user_id'
            )
            ->where('order_request_approvals.status', 0)
            ->join('order_requests', 'order_requests.id', '=', 'order_request_approvals.order_request_id')
            ->join('stocks', 'stocks.id', '=', 'order_requests.stock_id')
            ->leftJoin('users as request_users', 'request_users.id', '=', 'order_requests.request_user_id')
            ->leftJoin('users as users', 'users.id', '=', 'order_requests.user_id')
            ->join('suppliers', 'suppliers.id', '=', 'order_requests.supplier_id')
            ->where('order_requests.status', '=', 0)
            ->where('order_requests.del_flg', '=', 0)
            ->where('order_requests.accept_flg', '=', 1);

        if ($user_id == 63) { // 常務
            $query->where('order_request_approvals.user_id', 63);
        } else if ($user_id == 36) { // 部長
            $query->where('order_request_approvals.user_id', 36);
        } else if ($user_id == 2) { // 社長
            $query->where('order_request_approvals.user_id', 2);
        } else if ($user_id == 16) { //梶谷
            $query->where('order_request_approvals.user_id', 16);
        } else if ($user_id == 37) { //長谷川
            $query->where('order_request_approvals.user_id', 37);
        } else if ($user_id == 84) { //宮原
            $query->where('order_request_approvals.user_id', 84);
        }

        $order_requests = $query->get();



        $user = User::select('id', 'name')->find($user_id);

        return Inertia::render('Accept/OrderRequest', ['user' => $user, 'order_requests' => $order_requests]);
    }

    public function update(Request $request)
    {
        // 承認ID
        $order_request_approval_id = $request->order_request_approval_id;
        $flg = $request->status;
        $comment = $request->comment;

        $status = true;
        $msg = "";


        try {
            $order_request_approval = OrderRequestApproval::find($order_request_approval_id);
            $order_request_approval->status = $flg;
            $order_request_approval->comment = $comment;
            $order_request_approval->save();

            $order_request = OrderRequest::find($order_request_approval->order_request_id);
            $stock = Stock::find($order_request->stock_id);


            switch ($flg) {


                case 1: //承認
                    if ($order_request_approval->final_flg) 
                    {
                        $order_request->accept_flg = 2;
                        $order_request->save();

                        Helper::createNotifyQueue("在庫管理システムからの通知です。", "{$stock->name}{$stock->s_name}が承認されました。", "", [$order_request->user_id]);
                    } else { //次の承認を有効化
                        $new_order_request_approval = OrderRequestApproval::where('order_request_id', $order_request_approval->order_request_id)
                            ->where('id', '>', $order_request_approval->id)
                            ->where('order_request_id', $order_request_approval->order_request_id)
                            ->whereNull('status')
                            ->first();
                        $new_order_request_approval->status = 0;
                        $new_order_request_approval->save();

                        Helper::createNotifyQueue("在庫管理システムからの通知です。", "承認依頼を受け付けました。\n\n以下のURLから承認を行ってください。", "https://akioka.cloud/accept/order-request?user_id=" . $new_order_request_approval->user_id, [$new_order_request_approval->user_id]);
                    }

                    break;
                case 2: //非承認
                    $order_request->accept_flg = 3;
                    $order_request->save();

                    //  コメントを取得してコメントも送信
                    Helper::createNotifyQueue("在庫管理システムからの通知です。", "{$stock->name}{$stock->s_name}の承認が却下されました。", "", [$order_request->user_id]);
                    Helper::createNotifyQueue("在庫管理システムからの通知です。", "{$stock->name}{$stock->s_name}の承認が却下されました。", "", [$order_request->request_user_id]);

                    break;
            }
            $order_request->save();
        } catch (Exception $e) {
            $status = false;
            $msg = $e->getMessage();
        }

        return response()->json(['status' => $status, 'msg' => $msg]);
    }
}
