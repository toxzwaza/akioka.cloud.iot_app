<?php

namespace App\Http\Controllers;

use App\Http\Services\Helper;
use App\Models\Document;
use App\Models\DocumentImage;
use App\Models\OrderRequest;
use App\Models\Process;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class NewItemController extends Controller
{
    //
    public function home()
    {

        $processes = Process::all();
        $users = User::where('del_flg', 0)->orderBy('process_id', 'desc')->orderBy('position_id', 'asc')->get();
        $suppliers = Supplier::all();

        return Inertia::render('Stock/NewItem/Home', ['processes' => $processes, 'users' => $users, 'suppliers' => $suppliers]);
    }

    public function store(Request $request)
    {
        $status = true;
        $msg = '';

        $new_approval = $request->new_approval; //新規品フラグ

        $user_id = $request->user_id;
        $evaluation_date = $request->evaluation_date;
        $desire_delivery_date = $request->desire_delivery_date;
        $supplier_name = $request->supplier_name;
        $price = $request->price;
        $quantity = $request->quantity;
        $calc_price = $request->calc_price;
        $title = $request->title;
        $content = $request->content;
        $main_reason = $request->main_reason;
        $sub_reason = $request->sub_reason;
        $name = $request->name;
        $s_name = $request->s_name;

        // 既存品
        $now_quantity = $request->now_quantity; //現在個数
        $now_quantity_unit = $request->now_quantity_unit; //現在個数単位
        $digest_date = $request->digest_date; //消化予定日
        $quantity_unit = $request->quantity_unit; //必要個数単位
        $description = $request->description; //備考


        try {
            if ($new_approval) {
                // 物品データを作成
                $stock = new Stock();
                $stock->name = $name;
                $stock->s_name = $s_name;
                $stock->price = $price;
                $stock->save();


                // ここでデータベースに保存する処理を追加
                $document = new Document();
                $document->user_id = $user_id;
                $document->evalution_date = $evaluation_date;
                $document->supplier_name = $supplier_name;
                $document->price = $price;
                $document->title = $title;
                $document->content = $content;
                $document->main_reason = $main_reason;
                $document->sub_reason = $sub_reason;
                $document->save();

                // ファイルの保存処理
                $filePaths = [];
                if ($request->hasFile('files')) {
                    foreach ($request->file('files') as $file) {
                        $path = $file->store('public/attachments');
                        $filePaths[] = Storage::url($path);
                    }
                }

                foreach ($filePaths as $key => $filePath) {
                    $documentImage = new DocumentImage();
                    $documentImage->document_id = $document->id;
                    $documentImage->image_path = $filePath;
                    $documentImage->extension = $file->getClientOriginalExtension();
                    $documentImage->save();

                    // 最初のファイルが画像の場合、stock->img_pathに設定
                    if ($key === 0 && $documentImage->extension !== 'pdf') {
                        $stock->img_path = $filePath;
                        $stock->save();
                    }
                }


                // 物品依頼データ作成
                $order_request = new OrderRequest();
                $order_request->stock_id = $stock->id;
                $order_request->request_user_id = $user_id;
                $order_request->quantity = $quantity;
                $order_request->price = $price;
                $order_request->calc_price = $calc_price;
                $order_request->new_stock_flg = 1;
                $order_request->document_id = $document->id;
                $order_request->desire_delivery_date = $desire_delivery_date;
                $order_request->save();

                $msg = '稟議書が正常に保存されました';
            } else {
                // 既存品
                // 発注依頼 ---------------------------------------------------
                $order_request = new OrderRequest();
                $order_request->name = $name;
                $order_request->s_name = $s_name;
                $order_request->request_user_id = $user_id;
                $order_request->quantity =  $quantity; //必要個数
                $order_request->unit = $quantity_unit; //必要個数単位
                $order_request->now_quantity = $now_quantity; //現在個数
                $order_request->now_quantity_unit = $now_quantity_unit; //現在個数単位
                $order_request->digest_date = $digest_date; //消化予定日
                $order_request->desire_delivery_date = $desire_delivery_date; //希望納入日
                $order_request->description = $description; //備考
                $order_request->save();

                // 発注依頼を通知 -----------------------------------------------------------
                Helper::createNotifyQueue("在庫管理システムからの通知です。", "{$name}{$s_name}の物品依頼を受付ました。以下のURLから発注を完了させてください。", "http://monokanri-manage.local/stock/order-requests", [91, 81, 68, 48]);
                // -----------------------------------------------------------

                $msg = '既存品物品依頼が完了しました。';
            }
        } catch (Exception $e) {
            $msg = $e->getMessage();
            $status = false;
        }

        return response()->json(['status' => $status, 'msg' => $msg]);
    }
}
