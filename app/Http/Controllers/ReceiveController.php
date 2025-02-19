<?php

namespace App\Http\Controllers;

use App\Models\Classification;
use App\Models\InitialOrder;
use App\Models\InventoryOperationRecord;
use App\Models\Location;
use App\Models\SplitOrderQuantity;
use App\Models\Stock;
use App\Models\StockPriceArchive;
use App\Models\StockStorage;
use App\Models\StockSupplier;
use App\Models\StorageAddress;
use App\Models\Supplier;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
                $order->quantity_per_org = $stock->quantity_per_org;

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

        return Inertia::render('Stock/Receive/Delivery', ['order' => $order, 'supplier_id' => $supplier_id, 'locations' => $locations, 'storage_addresses' => $storage_addresses]);
    }
    // 換算値のデフォルトを変更
    public function updateQuantityPerOrg(Request $request)
    {
        $is_success = true;

        $stock_id = $request->stock_id;
        $quantity_per_org = $request->quantity_per_org;
        try {
            $stock = Stock::find($stock_id);
            if ($stock) {
                $stock->quantity_per_org = $quantity_per_org;
                $stock->save();
            }
        } catch (Exception $e) {
            $is_success = false;
        }
        return response()->json(['status' => $is_success]);
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

    ///////////////////////// Receipt.vue /////////////////////////
    public function store(Request $request)
    {
        $order_id = $request->order_id;
        $supplier_id = $request->supplier_id;
        $classification_id = $request->classification_id;
        $deli_location = $request->deli_location ?? '';
        $storage_address_id = $request->storage_address_id;

        $is_success = true;

        $order = null;

        try {
            DB::transaction(function () use ($order_id, $supplier_id, $classification_id, $deli_location, $storage_address_id) {
                if ($order_id && $supplier_id && $classification_id) {
                    $order = InitialOrder::find($order_id);



                    $stock  = new Stock();
                    $stock->name = $order->name;
                    $stock->s_name = $order->s_name;
                    $stock->deli_location = $deli_location;
                    $stock->classification_id = $classification_id;
                    $stock->save();

                    $stock_supplier = new StockSupplier();
                    $stock_supplier->stock_id = $stock->id;
                    $stock_supplier->supplier_id = $supplier_id;
                    $stock_supplier->save();

                    $stock_storage = new StockStorage();
                    $stock_storage->stock_id  = $stock->id;
                    $stock_storage->storage_address_id = $storage_address_id;
                    $stock_storage->quantity = 0;
                    $stock_storage->save();

                    // アドレスが作成されたらフラグを初期化する
                    $order->not_found_flg = null;
                    $order->save();
                }
            });
        } catch (Exception $e) {
            $is_success = false;
            return response()->json(['status' => $is_success, 'msg' => $e->getMessage(), 'order' => $order]);
        }

        return response()->json(['status' => $is_success]);
    }

    // 納品数量登録
    public function updateDelivery(Request $request)
    {
        $id = $request->id;
        // 発注数量分の納入数
        $quantity = $request->quantity;
        // 換算フラグ
        $conversion_flg = $request->conversion_flg;
        // 換算値を反映した実際の納入数
        $calc_quantity = $request->calc_quantity;

        $stock_storage_id = $request->stock_storage_id;
        $stock_id = $request->stock_id;
        $storage_address_id = $request->storage_address_id;

        $order = InitialOrder::find($id);

        // 既にアドレス登録されている場合
        if ($stock_storage_id) {
            // 在庫数量を加算する処理を追加する
            $stock_storage = StockStorage::find($stock_storage_id);
            if ($stock_storage) {

                $quantity_sum = 0;

                // 分納データが存在するかチェック
                $split_order_quantities = SplitOrderQuantity::where('initial_order_id', $id)->get();
                // 存在する場合、合計を取得
                if (!$split_order_quantities->isEmpty()) {
                    $quantity_sum = $split_order_quantities->sum('quantity');
                }



                if ($order->quantity == ($quantity_sum + $quantity)) {
                    // 注文分をすべて納品した場合、受け取りフラグを立てる
                    $order->receive_flg = 1;
                    $order->save();


                    if ($stock_id) {
                        // 納品記録
                        $inventory_operation_records = new InventoryOperationRecord();
                        $inventory_operation_records->stock_id = $stock_id;
                        $inventory_operation_records->stock_storage_id = $stock_storage_id;
                        $inventory_operation_records->inventory_operation_id = 8;
                        if ($conversion_flg) {
                            $inventory_operation_records->quantity = $calc_quantity;
                        } else {
                            $inventory_operation_records->quantity = $order->quantity;
                        }
                        $inventory_operation_records->save();

                        // 単価の変更
                        $stock = Stock::find($stock_id);
                        if ($stock->price != $order->price) {
                            // 単価が異なる場合単価設定を変更
                            $stock->price = $order->price;
                            $stock->save();

                            // 変更履歴作成
                            $stock_price_archive = new StockPriceArchive();
                            $stock_price_archive->stock_id = $stock_id;
                            $stock_price_archive->price = $order->price;
                            $stock_price_archive->save();
                        }
                    }

                    // 納品した分を格納先に追加
                    if ($conversion_flg) {
                        $stock_storage->quantity += $calc_quantity;
                    } else {
                        $stock_storage->quantity += $quantity;
                    }

                    $stock_storage->save();



                    // 一覧へリダイレクト
                    return to_route('stock.receive.archive');
                } else {
                    // 分納の場合、split_order_quantitiesテーブルを作成
                    $split_order_quantity = new SplitOrderQuantity();
                    $split_order_quantity->initial_order_id = $order->id;
                    $split_order_quantity->quantity = $quantity;
                    $split_order_quantity->save();

                    // 分納した分を格納先に追加
                    $stock_storage->quantity += $quantity;
                    $stock_storage->save();
                }
            }
        } else {
            // 新たに作成して、個数を登録
            $stock_storage = new StockStorage();
            $stock_storage->stock_id = $stock_id;
            $stock_storage->storage_address_id = $storage_address_id;
            $stock_storage->quantity = 0;
            $stock_storage->save();

            return redirect()->back();
        }


        return redirect()->back();
    }

    public function getClassifications()
    {
        $classifications = Classification::all();

        return response()->json($classifications);
    }
    public function getSuppliers()
    {
        $suppliers = Supplier::all();
        return response()->json($suppliers);
    }

    public function none_storage($order_id)
    {
        $order = InitialOrder::find($order_id);
        $order->receive_flg = 1;
        $order->none_storage_flg = 1;
        $order->save();

        return to_route('stock.receive.archive');
    }

    // 格納先取得
    public function getLocations()
    {
        $locations = Location::all();

        return response()->json($locations);
    }
    // アドレス取得
    public function getAddresses($location_id)
    {
        $addresses = StorageAddress::where('location_id', $location_id)->orderBy('address', 'asc')->get();

        return response()->json($addresses);
    }
}
