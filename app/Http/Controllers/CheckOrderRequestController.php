<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\OrderRequest;
use App\Models\OrderRequestApproval;
use App\Models\Process;
use App\Models\StockSupplier;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CheckOrderRequestController extends Controller
{
    //
    public function index(Request $request)
    {
        $order_request_id = $request->order_request_id;

        $processes = Process::all();
        $users = User::where('del_flg', 0)->get();

        return Inertia::render('Stock/CheckOrderRequest/Index', ['processes' => $processes, 'users' => $users, 'order_request_id' => $order_request_id]);
    }
    public function show($order_request_id)
    {
        return Inertia::render('Stock/CheckOrderRequest/Show', ['order_request_id' => $order_request_id]);
    }

    public function getOrderRequestDetail($order_request_id)
    {
        $status = true;
        $msg = "";
        $order_request = null;

        try {
            // 特定の注文依頼の詳細情報を取得
            $order_request = OrderRequest::select(
                'order_requests.id',
                'order_requests.stock_id',
                'order_requests.id as order_request_id',
                'order_requests.accept_flg',
                'order_requests.status',
                'stocks.img_path',
                'stocks.name',
                'stocks.s_name',
                'order_requests.name as order_request_name',
                'order_requests.s_name as order_request_s_name',
                'stocks.url',
                'order_requests.now_quantity', //現在個数
                'order_requests.quantity',
                'order_requests.price',
                'order_requests.unit',
                'order_requests.created_at', //依頼日
                'order_requests.digest_date', //消化予定日
                'order_requests.desire_delivery_date', //希望納期
                DB::raw('CASE 
                    WHEN order_requests.file_path IS NOT NULL AND order_requests.file_path != "" 
                    THEN CONCAT("https://akioka.cloud/storage/", SUBSTRING_INDEX(order_requests.file_path, "storage/", -1))
                    ELSE order_requests.file_path 
                END as file_path'),
                'order_requests.description',
                'order_requests.sub_description',
                'order_requests.document_id',  //稟議書
                'users.name as request_user_name',
                'users.id as request_user_id',
                'order_users.name as order_user_name',
                'order_users.id as order_user_id',
                'order_requests.postage',
                'order_requests.calc_price',
                'order_requests.new_stock_flg',
                'suppliers.name as supplier_name',
                'order_requests.supplier_id',
                'stock_suppliers.lead_time as stock_supplier_lead_time',
                'max_stock_storages.max_quantity as stock_storage_quantity',
                'max_stock_storages.reorder_point as reorder_point',
                'device_messages.message as message',
                'device_messages.answer as answer',
                'users.process_id as user_process_id',
                'processes.name as process_name',
                'initial_orders.order_complete_flg',
                'initial_orders.id as initial_order_id',
                'initial_orders.receive_flg',
                'documents.title',
                'documents.content',
                'documents.main_reason',
                'documents.sub_reason',
            )
                ->leftJoin('stocks', 'stocks.id', '=', 'order_requests.stock_id')
                ->leftJoin('users', 'users.id', '=', 'order_requests.request_user_id')
                ->leftJoin('users as order_users', 'order_users.id', '=', 'order_requests.user_id')
                ->leftJoin('processes', 'processes.id', '=', 'users.process_id')
                ->leftJoin('suppliers', 'suppliers.id', '=', 'order_requests.supplier_id')
                ->leftJoin('stock_suppliers', function ($join) {
                    $join->on('stock_suppliers.stock_id', '=', 'stocks.id')
                        ->on('stock_suppliers.supplier_id', '=', 'suppliers.id');
                })
                ->leftJoin(DB::raw('(SELECT stock_id, MAX(quantity) as max_quantity, MAX(reorder_point) as reorder_point FROM stock_storages GROUP BY stock_id) as max_stock_storages'), 'max_stock_storages.stock_id', '=', 'stocks.id')
                ->leftJoin('device_messages', 'device_messages.id', '=', 'order_requests.device_message_id')
                ->leftJoin('initial_orders', 'initial_orders.order_request_id', '=', 'order_requests.id')
                ->leftJoin('documents', 'documents.id', '=', 'order_requests.document_id')
                ->where('order_requests.id', '=', $order_request_id)
                ->where('order_requests.del_flg', '=', 0)
                ->first();

            if (!$order_request) {
                $status = false;
                $msg = "指定された発注依頼が見つかりません。";
            } else {
                // 承認状況を取得（AcceptControllerと同様の処理）
                $order_request_approvals = OrderRequestApproval::select('users.id as user_id', 'users.name', 'ora.status', 'ora.final_flg', 'ora.comment', 'ora.updated_at')
                    ->where('order_request_id', $order_request->id)
                    ->join('users', 'users.id', '=', 'ora.user_id')
                    ->from('order_request_approvals as ora')
                    ->get();
                $order_request->order_request_approvals = $order_request_approvals;

                // 稟議書の画像を取得
                if ($order_request->document_id) {
                    $document_images = DB::table('document_images')
                        ->select(DB::raw('CONCAT("/storage/", SUBSTRING_INDEX(image_path, "storage/", -1)) as image_path'))
                        ->where('document_id', $order_request->document_id)
                        ->pluck('image_path')
                        ->toArray();
                    $order_request->document_images = $document_images;
                }
            }
        } catch (Exception $e) {
            $status = false;
            $msg = $e->getMessage();
        }

        return response()->json(['status' => $status, 'msg' => $msg, 'order_request' => $order_request]);
    }

    public function getOrderRequests(Request $request)
    {
        $status = true;
        $msg = "";

        $user_id = $request->user_id ?? null; //依頼者
        $process_id = $request->process_id ?? null; //
        $name = $request->name ?? null; //品名
        $s_name = $request->s_name ?? null; //品番

        $page = $request->page ?? 1; //ページ番号
        $perPage = $request->per_page ?? 20; //1ページあたりの件数

        try {
            // 未受理の注文依頼のみ取得
            // 最大のquantityを取得
            $query = OrderRequest::select(
                'order_requests.id',
                'order_requests.stock_id',
                'order_requests.id as order_request_id',
                'order_requests.accept_flg',
                'order_requests.status',
                'stocks.img_path',
                'stocks.name',
                'stocks.s_name',
                'order_requests.name as order_request_name',
                'order_requests.s_name as order_request_s_name',
                'stocks.url',
                'order_requests.now_quantity', //現在個数
                'order_requests.quantity',
                'order_requests.price',
                'order_requests.unit',
                'order_requests.created_at', //依頼日
                'order_requests.digest_date', //消化予定日
                'order_requests.desire_delivery_date', //希望納期
                'order_requests.file_path',
                'order_requests.description',
                'order_requests.sub_description',
                'order_requests.document_id',  //稟議書
                'users.name as request_user_name',
                'users.id as request_user_id',
                'order_users.name as order_user_name',
                'order_users.id as order_user_id',
                'order_requests.postage',
                'order_requests.calc_price',
                'order_requests.new_stock_flg',
                'suppliers.name as supplier_name',
                'order_requests.supplier_id',
                'stock_suppliers.lead_time as stock_supplier_lead_time',
                'max_stock_storages.max_quantity as stock_storage_quantity',
                'max_stock_storages.reorder_point as reorder_point',
                'device_messages.message as message',
                'device_messages.answer as answer',
                'users.process_id as user_process_id',
                'initial_orders.order_complete_flg',
                'initial_orders.id as initial_order_id',
                'initial_orders.receive_flg',
            )
                ->leftJoin('stocks', 'stocks.id', '=', 'order_requests.stock_id')
                ->leftJoin('users', 'users.id', '=', 'order_requests.request_user_id')
                ->leftJoin('users as order_users', 'order_users.id', '=', 'order_requests.user_id')
                ->leftJoin('suppliers', 'suppliers.id', '=', 'order_requests.supplier_id')
                ->leftJoin('stock_suppliers', function ($join) {
                    $join->on('stock_suppliers.stock_id', '=', 'stocks.id')
                        ->on('stock_suppliers.supplier_id', '=', 'suppliers.id');
                })
                ->leftJoin(DB::raw('(SELECT stock_id, MAX(quantity) as max_quantity, MAX(reorder_point) as reorder_point FROM stock_storages GROUP BY stock_id) as max_stock_storages'), 'max_stock_storages.stock_id', '=', 'stocks.id')
                ->leftJoin('device_messages', 'device_messages.id', '=', 'order_requests.device_message_id')
                ->leftJoin('initial_orders', 'initial_orders.order_request_id', '=', 'order_requests.id')
                ->where('order_requests.del_flg', '=', 0);
            // ->where('order_requests.status', '=', 0)

            // ユーザーIDによる絞り込み
            if ($user_id) {
                $query->where(function ($q) use ($user_id) {
                    $q->where('order_requests.request_user_id', '=', $user_id);
                });
            }


            // グループIDによる絞り込み
            if ($process_id) {
                $query->where('users.process_id', '=', $process_id);
            }

            // 品名による絞り込み
            if ($name) {
                $query->where(function ($q) use ($name) {
                    $q->where('stocks.name', 'LIKE', '%' . $name . '%')
                        ->orWhere('order_requests.name', 'LIKE', '%' . $name . '%');
                });
            }

            // 品番による絞り込み
            if ($s_name) {
                $query->where(function ($q) use ($s_name) {
                    $q->where('stocks.s_name', 'LIKE', '%' . $s_name . '%')
                        ->orWhere('order_requests.s_name', 'LIKE', '%' . $s_name . '%');
                });
            }

            // ソートとデータ取得（ページネーション）
            $order_requests = $query
                ->orderBy('order_requests.created_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);
        } catch (Exception $e) {
            $status = false;
            $msg = $e->getMessage();
        }



        return response()->json(['status' => $status, 'msg' => $msg, 'order_requests' => $order_requests]);
    }
}
