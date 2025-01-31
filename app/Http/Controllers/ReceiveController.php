<?php

namespace App\Http\Controllers;

use App\Models\InitialOrder;
use App\Models\Location;
use App\Models\SplitOrderQuantity;
use App\Models\Stock;
use App\Models\StockStorage;
use App\Models\StorageAddress;
use App\Models\Supplier;
use Exception;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReceiveController extends Controller
{
    public function home()
    {
        return Inertia::render('Stock/Receive');
    }

    //
    public function index()
    {
        return Inertia::render('Stock/Receive/Index');
    }
    public function archive()
    {
        return Inertia::render('Stock/Receive/Archive');
    }
    public function receipt()
    {
        return Inertia::render('Stock/Receive/Receipt');
    }

    ///////////////////////// Index.vue /////////////////////////
    // 納品書が登録されていないリスト
    // 品名が一致かつ、品番が一致又は含まれているモノ
    public function getInitialOrders()
    {
        $initial_orders = InitialOrder::where(function ($query) {
            $query->whereNull('receive_flg')
                ->orWhere('receive_flg', 0);
        })->whereNull('delifile_path')->get();

        foreach ($initial_orders as $order) {
            $stock = Stock::where('name', $order->name)
                ->where(function ($query) use ($order) {
                    $query->where('s_name', 'like', "$order->s_name")
                        ->orWhere('s_name', $order->s_name);
                })->first();
            if ($stock) {
                $order->img_path = $stock->img_path;
                $order->stock_id = $stock->id;
            } else {
                $order->not_found_flg = 1;
            }
        }

        return response()->json($initial_orders);
    }
    // 納品書アプロード
    public function uploadFile(Request $request)
    {
        $is_success = true;

        // $id = $request->id;
        // idのリストを取得
        $select_list = $request->select_list;
        $file = $request->file('file');


        foreach ($select_list as $id) {
            try {
                if ($id && $file) {
                    $timestamp = time();
                    $filename = $timestamp . '.' . $file->getClientOriginalExtension();
                    $file->storeAs('public/deli_file', $filename);
                }
                $order = InitialOrder::find($id);
                if ($order) {
                    $order->delifile_path = '/deli_file/' . $filename;
                    $order->save();
                }

                // 品名・品番が一致する在庫データを取得
                // 見つからない場合は、フラグを立てる
                $stock = Stock::where('name', $order->name)
                    ->where('s_name', $order->s_name)->first();

                if (!$stock) {
                    $order->not_found_flg = 1;
                    $order->save();
                }
            } catch (Exception $e) {
                $is_success = false;
            }
        }

        if ($is_success) {
            return response()->json(['status' => $is_success]);
        }
    }
    public function deleteInitialOrder($order_id)
    {
        $status = 'ng';
        $msg = '';

        if (!$order_id) {
            return response()->json(['status' => $status]);
        }

        try {
            $initial_order = InitialOrder::find($order_id);
            $initial_order->delete();
            $status = "ok";
            $msg = '削除処理が完了しました。';
        } catch (Exception $e) {

            $msg = $e->getMessage();
        }

        return response()->json(['status' => $status, 'msg' => $msg]);
    }

    ///////////////////////// Archive.vue /////////////////////////
    // 既に納品書を登録しているモノのリスト
    public function getAlreadyDelifileInitialOrders()
    {
        // 未引き渡し
        // 納品書登録済み
        // 在庫を所持している

        $initial_orders = InitialOrder::where(function ($query) {
            $query->whereNull('receive_flg')
                ->orWhere('receive_flg', 0);
        })->whereNotNull('delifile_path')
            ->whereNull('none_storage_flg')
            ->orderby('updated_at', 'desc')->get();

        foreach ($initial_orders as $order) {
            $stock = Stock::where('name', $order->name)
                ->where(function ($query) use ($order) {
                    $query->where('s_name', 'like', "$order->s_name")
                        ->orWhere('s_name', $order->s_name);
                })->first();

            if ($stock) {
                $order->img_path = $stock->img_path;
                $order->stock_id = $stock->id;
            } else {
                $order->not_found_flg = 1;
            }
        }

        return response()->json($initial_orders);
    }

    // 納品登録画面
    public function delivery($id)
    {
        $order = InitialOrder::find($id);
        if (!$order) {

            return redirect()->back();
        }
        $supplier_id = null;
        $locations = [];
        $storage_addresses = [];

        // 在庫データが見つかった場合
        if (!$order->not_found_flg) {
            // 分納個数を取得
            $quantity_sum = 0;

            // 分納データが存在するかチェック
            $split_order_quantities = SplitOrderQuantity::where('initial_order_id', $id)->get();
            // 存在する場合、合計を取得
            if (!$split_order_quantities->isEmpty()) {
                $quantity_sum = $split_order_quantities->sum('quantity');
            }
            $order->split_quantity_sum = $quantity_sum;

            // 物品データ取得
            $stock = Stock::where('name', $order->name)
                ->where('s_name', $order->s_name)->first();


            if ($stock) {

                // 画像を取得
                $order->img_path = $stock->img_path;
                $order->stock_id = $stock->id;

                // 現在の格納先アドレスリストを取得
                $stock_storages = StockStorage::select('stock_storages.id as stock_storage_id', 'stock_storages.quantity as storage_quantity', 'storage_addresses.id as address_id', 'storage_addresses.address', 'storage_addresses.location_id', 'stock_storages.quantity', 'locations.name')->join('storage_addresses', 'storage_addresses.id', 'stock_storages.storage_address_id')->join('locations', 'locations.id', 'location_id')->where('stock_id', $stock->id)->get();
                if ($stock_storages->count() == 0) {
                    // 格納先と格納先アドレス
                    $locations = Location::all();
                    $storage_addresses = StorageAddress::orderBy('address', 'asc')->get();
                }

                $order->stock_storages = $stock_storages;
            }
        } else {
            $supplier = Supplier::where('name', $order->com_name)->first();
            if ($supplier) {
                $supplier_id = $supplier->id;
            }
            // 格納先と格納先アドレス
            $locations = Location::all();
            $storage_addresses = StorageAddress::orderBy('address', 'asc')->get();
        }

        return Inertia::render('Stock/Tablet/Delivery', ['order' => $order, 'supplier_id' => $supplier_id, 'locations' => $locations, 'storage_addresses' => $storage_addresses]);
    }

    ///////////////////////// Receipt.vue /////////////////////////
    // 納品確定済みで受領されていないもののリスト
    public function getReceiptOrders()
    {
        $initial_orders = InitialOrder::where(function ($query) {
            $query->where('receive_flg', 1)
                ->orWhere('none_storage_flg', 1);
        })->where('receipt_flg', 0)->orderby('updated_at', 'desc')->get();

        foreach ($initial_orders as $order) {
            $stock = Stock::where('name', $order->name)
                ->where(function ($query) use ($order) {
                    $query->where('s_name', 'like', "$order->s_name")
                        ->orWhere('s_name', $order->s_name);
                })->first();

            if ($stock) {
                $order->img_path = $stock->img_path;
            } else {
                $order->found_flg = 1;
            }
        }

        return response()->json($initial_orders);
    }

    // 引き渡し登録
    public function updateReceipt($id)
    {
        $order = InitialOrder::find($id);

        $order->receipt_flg = 1;
        $order->save();

        return redirect()->back();
    }
}
