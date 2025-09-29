<?php

namespace App\Http\Controllers;

use App\Http\Services\Helper;
use App\Models\Device;
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
                'order_requests.description', //依頼者備考
                'order_requests.sub_description', //発注者備考
                'order_requests.new_stock_flg', //備考
                'order_requests.created_at',
                'request_users.name as request_user_name',
                'request_user_processes.name as request_user_process_name',
                'users.name as user_name',
                'order_requests.postage',
                'order_requests.calc_price',
                'order_requests.price',
                'order_requests.document_id',
                'documents.evalution_date',
                'documents.title',
                'documents.content',
                'documents.main_reason',
                'documents.sub_reason',
                DB::raw('CONCAT("/storage/", SUBSTRING_INDEX(order_requests.file_path, "storage/", -1)) as file_path'),
                'suppliers.name as supplier_name',
                'order_request_approvals.id as order_request_approval_id',
                'order_request_approvals.final_flg',
                'order_request_approvals.user_id as approval_user_id',
                DB::raw('(
                    SELECT MAX(initial_orders.order_date)
                    FROM initial_orders
                    WHERE initial_orders.stock_id = order_requests.stock_id
                ) as last_order_date')
            )
            ->join('order_requests', 'order_requests.id', '=', 'order_request_approvals.order_request_id')
            ->join('stocks', 'stocks.id', '=', 'order_requests.stock_id')
            ->leftJoin('users as request_users', 'request_users.id', '=', 'order_requests.request_user_id')
            ->leftJoin('processes as request_user_processes', 'request_user_processes.id', '=', 'request_users.process_id')
            ->leftJoin('users as users', 'users.id', '=', 'order_requests.user_id')
            ->join('suppliers', 'suppliers.id', '=', 'order_requests.supplier_id')
            ->leftJoin('documents', 'documents.id', '=', 'order_requests.document_id')
            ->where([
                ['order_requests.status', '=', 0],
                ['order_requests.del_flg', '=', 0]
            ])
            ->whereIn('order_request_approvals.status', [0, 2])
            ->whereIn('order_requests.accept_flg', [1, 6]);

        if ($user_id == 63) { // 常務
            $query->where('order_request_approvals.user_id', 63);
        } else if ($user_id == 94) { // 部長
            $query->where('order_request_approvals.user_id', 94);
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
        } else {
            return response("アクセス権限がありません", 403);
        }

        $order_requests = $query->get();

        // documents_imagesを配列として取得
        foreach ($order_requests as $order_request) {
            $order_request_approvals = OrderRequestApproval::select('users.id as user_id', 'users.name', 'ora.status', 'ora.final_flg', 'ora.comment', 'ora.updated_at')
                ->where('order_request_id', $order_request->id)
                ->join('users', 'users.id', '=', 'ora.user_id')
                ->from('order_request_approvals as ora')
                ->get();
            $order_request->order_request_approvals = $order_request_approvals;


            $document_images = DB::table('document_images')
                ->select(DB::raw('CONCAT("/storage/", SUBSTRING_INDEX(image_path, "storage/", -1)) as image_path'))
                ->where('document_id', $order_request->document_id)
                ->pluck('image_path')
                ->toArray();

            $order_request->document_images = $document_images;
        }

        $user = User::select('id', 'name')->find($user_id);
        // 承認者の情報を取得
        // order_requests.created_atが今年のデータのみ取得するように修正
        // order_requests.calc_priceの合計値と件数を取得
        // users.process_idごとの合計金額と件数を取得
        $order_request_approvals_query = OrderRequestApproval::
            where('order_request_approvals.user_id', $user_id)
            ->where('order_request_approvals.final_flg', 1)
            ->where('order_request_approvals.status', 1) //自分が承認したもの
            ->join('order_requests', 'order_requests.id', '=', 'order_request_approvals.order_request_id')
            ->join('users', 'users.id', '=', 'order_requests.request_user_id')
            ->join('processes', 'processes.id', '=', 'users.process_id')
            ->whereYear('order_requests.created_at', date('Y'))
            ->where('order_requests.del_flg', 0)
            ->select('users.process_id',
            'processes.name as process_name',
                DB::raw('SUM(order_requests.calc_price) as total_calc_price'), 
                DB::raw('COUNT(order_requests.id) as order_request_count'))
            ->groupBy('users.process_id', 'processes.name');

        $process_stats = $order_request_approvals_query->get();



        return Inertia::render('Accept/OrderRequest', ['user' => $user, 'order_requests' => $order_requests, 'process_stats' => $process_stats]);
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
            
            // コメントの履歴管理
            if ($comment && trim($comment) !== "") {
                // 現在の日付を取得
                $dateString = date('y/m/d');
                
                // ステータスに基づいて承認・非承認を判定
                $statusText = ($flg == 1) ? '承認' : '非承認';
                
                // 新しいコメント形式
                $newComment = $dateString . " [" . $statusText . "]\n" . $comment . "\n----------------------";
                
                // 既存のコメントがある場合は追加、ない場合は新規作成
                if ($order_request_approval->comment && trim($order_request_approval->comment) !== "") {
                    $order_request_approval->comment = $order_request_approval->comment . "\n" . $newComment;
                } else {
                    $order_request_approval->comment = $newComment;
                }
            }
            
            $order_request_approval->save();
            $order_request_approval_user = User::find($order_request_approval->user_id);

            $order_request = OrderRequest::find($order_request_approval->order_request_id);
            $stock = Stock::find($order_request->stock_id);


            switch ($flg) {


                case 1: //承認

                    if ($order_request_approval->final_flg) {
                        $order_request->accept_flg = 2;
                        $order_request->save();

                        Helper::createNotifyQueue("在庫管理システムからの通知です。", "{$stock->name}{$stock->s_name}が承認されました。", "", [$order_request->user_id]);

                        // 端末への通知
                        if ($order_request->device_id) {
                            $device = Device::find($order_request->device_id);
                            Helper::sendNotification($device->token, "在庫管理システムからの通知です。", "{$stock->name}{$stock->s_name}が承認されました。");
                        }
                    } else { //次の承認を有効化
                        if($order_request->accept_flg === 6){
                            $order_request->accept_flg = 1;
                            $order_request->save();
                        }

                        $new_order_request_approval = OrderRequestApproval::
                        where('order_request_id', $order_request_approval->order_request_id)
                            ->where('id', '>', $order_request_approval->id)
                            ->where('order_request_id', $order_request_approval->order_request_id)
                            ->first();
                        $new_order_request_approval->status = 0;
                        $new_order_request_approval->save();

                        Helper::createNotifyQueue("在庫管理システムからの通知です。", "承認依頼を受け付けました。\n\n以下のURLから承認を行ってください。", "https://akioka.cloud/accept/order-request?user_id=" . $new_order_request_approval->user_id, [$new_order_request_approval->user_id]);
                    }

                    break;
                case 2: //非承認
                    // 一つ前の承認者を探す
                    $previous_order_request_approval = OrderRequestApproval::where('order_request_id', $order_request_approval->order_request_id)
                        ->where('id', '<', $order_request_approval->id)
                        ->orderBy('id', 'desc')
                        ->first();

                    if ($previous_order_request_approval) {
                        // 一つ前の承認者が存在する場合、その承認者に戻す

                        // 現在の承認者より後の承認者のステータスをリセット
                        // OrderRequestApproval::where('order_request_id', $order_request_approval->order_request_id)
                        //     ->where('id', '>', $order_request_approval->id)
                        //     ->update(['status' => null, 'comment' => null]);

                        // 一つ前の承認者を承認待ち状態に戻す
                        $previous_order_request_approval->status = 0;
                        // $previous_order_request_approval->comment = null;
                        $previous_order_request_approval->save();

                        // 発注依頼のステータスを差し戻しにする
                        $order_request->accept_flg = 6;

                        // 一つ前の承認者への通知
                        Helper::createNotifyQueue("在庫管理システムからの通知です。", "承認依頼が差し戻されました。\n\n差し戻し者:" . $order_request_approval_user->name . "\nコメント：" . $comment . "\n\n以下のURLからコメントの回答及び、再承認を行ってください。", "https://akioka.cloud/accept/order-request?user_id=" . $previous_order_request_approval->user_id, [$previous_order_request_approval->user_id]);
                    } else {
                        // 一つ前の承認者が存在しない場合（第一承認者が非承認）
                        $order_request->accept_flg = 3;

                        // 発注担当者への通知
                        $user = User::find($order_request->user_id);
                        if ($user && $user->email) {
                            Helper::createNotifyQueue("在庫管理システムからの通知です。", "{$stock->name}{$stock->s_name}の承認が却下されました。\n\n却下者:" . $order_request_approval_user->name . "\nコメント：" . $comment, "", [$order_request->user_id]);
                        }

                        // 依頼者への通知
                        $request_user = User::find($order_request->request_user_id);
                        if ($request_user && $request_user->email) {
                            Helper::createNotifyQueue("在庫管理システムからの通知です。", "{$stock->name}{$stock->s_name}の承認が却下されました。\n\n却下者:" . $order_request_approval_user->name . "\nコメント：" . $comment, "", [$order_request->request_user_id]);
                        }

                        // 依頼者の端末への通知
                        if ($order_request->device_id) {
                            $device = Device::find($order_request->device_id);
                            Helper::createDeviceMessage(
                                2,
                                $order_request->device_id,
                                null,
                                $order_request->user_id,
                                $order_request_approval_user->id,
                                "{$stock->name}{$stock->s_name}の承認が却下されました。\n\n却下者:" . $order_request_approval_user->name . "\nコメント：" . $comment . "\n以下のボタンをクリックして稟議書を修正してください。",
                                'https://akioka.cloud/check_order_request?order_request_id=' . $order_request->id
                            );

                            Helper::sendNotification($device->token, "在庫管理システムからの通知です。", "{$stock->name}{$stock->s_name}の承認が却下されました。\n\n却下者:" . $order_request_approval_user->name . "\nコメント：" . $comment);
                        }
                    }

                    $order_request->save();
                    break;
            }
            $order_request->save();
        } catch (Exception $e) {
            $status = false;
            $msg = $e->getMessage();
        }

        return response()->json(['status' => $status, 'msg' => $msg]);
    }

    /**
     * 部署別承認済み物品一覧取得
     */
    public function getDepartmentApprovedItems(Request $request)
    {
        $user_id = $request->user_id;
        $process_id = $request->process_id;

        // 権限チェック
        if (!in_array($user_id, [63, 94, 36, 2, 16, 37, 84])) {
            return response("アクセス権限がありません", 403);
        }

        // 承認済み物品を取得
        $approved_items = DB::table('order_request_approvals')
            ->select(
                'order_requests.id',
                'order_requests.stock_id',
                'stocks.img_path',
                'stocks.name',
                'stocks.s_name',
                'order_requests.quantity',
                'order_requests.unit',
                'order_requests.calc_price',
                'order_requests.price',
                'suppliers.name as supplier_name',
                'order_requests.updated_at',
                'request_users.name as request_user_name',
                'classifications.id as classification_id',
                'classifications.name as classification_name',
                'request_users.name as request_user_name'
            )
            ->leftJoin('order_requests', 'order_requests.id', '=', 'order_request_approvals.order_request_id')
            ->leftJoin('stocks', 'stocks.id', '=', 'order_requests.stock_id')
            ->leftJoin('classifications', 'classifications.id', '=', 'stocks.classification_id')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'order_requests.supplier_id')
            ->leftJoin('users as request_users', 'request_users.id', '=', 'order_requests.request_user_id')
            ->leftJoin('processes', 'processes.id', '=', 'request_users.process_id')
            ->where([
                ['order_requests.del_flg', '=', 0],
                ['processes.id', '=', $process_id], // 指定された部署
                ['order_request_approvals.final_flg', '=', 1], // 最終承認
                ['order_request_approvals.status', '=', 1], // 承認済み
                ['order_request_approvals.user_id', '=', $user_id] // 現在のユーザーが承認したもの
            ])
            ->whereYear('order_requests.created_at', date('Y')) // 今年のデータのみ
            ->orderBy('order_requests.updated_at', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'items' => $approved_items
        ]);
    }
}
