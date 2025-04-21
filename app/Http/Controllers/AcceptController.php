<?php

namespace App\Http\Controllers;

use App\Http\Services\Helper;
use App\Models\OrderRequest;
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

        // user_idによって処理を振り分ける
        $query = DB::table('order_requests')
        ->select('order_requests.id', 'order_requests.stock_id', 'order_requests.id as order_request_id', 'order_requests.accept_flg', 'stocks.img_path', 'stocks.name', 'stocks.s_name', 'stocks.price as stock_price', 'stocks.url', 'order_requests.quantity', 'order_requests.created_at', 'users.name as request_user_name', 'order_requests.postage', 'order_requests.calc_price', 'order_requests.price', DB::raw('CONCAT("/storage/", SUBSTRING_INDEX(order_requests.file_path, "storage/", -1)) as file_path'), 'suppliers.name as supplier_name', 'users.name as request_user_name')
            ->join('stocks', 'stocks.id',  'order_requests.stock_id')
            ->leftJoin('users', 'users.id', 'order_requests.request_user_id')
            ->where('status', 0)
            ->join('suppliers', 'suppliers.id', 'order_requests.supplier_id')
            ->where('order_requests.del_flg', 0)
            ->where('accept_flg', 1);

        if ($user_id == 63) { // 常務
            $query->where('new_stock_flg', 1);
        } elseif ($user_id == 36) { // 部長
            $query->where('new_stock_flg', 0);
        } else {
            echo '<p>承認権限はありません。</p>';
            return;
        }

        $order_requests = $query->get();



        $user = User::select('id', 'name')->find($user_id);

        return Inertia::render('Accept/OrderRequest', ['user' => $user, 'order_requests' => $order_requests]);
    }

    public function update(Request $request)
    {

        $order_request_id = $request->order_request_id;
        $accept_flg = $request->accept_flg;

        $status = true;


        try {
            $order_request = OrderRequest::find($order_request_id);
            $order_request->accept_flg = $accept_flg;
            // $order_request->status = 1;
            $order_request->save();

            $stock = Stock::find($order_request->stock_id);

            $title = "在庫管理システムからの通知です。";
            $msg = "{$stock->name}{$stock->s_name}が承認されました。";
            $url = "";
            $users = [$order_request->user_id];

            // 承認完了通知
            Helper::createNotifyQueue($title, $msg, $url, $users);
        } catch (Exception $e) {
            $status = false;
        }

        return response()->json(['status' => $status]);
    }
}
