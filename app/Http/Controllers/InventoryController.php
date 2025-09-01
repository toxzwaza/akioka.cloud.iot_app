<?php

namespace App\Http\Controllers;

use App\Models\InitialOrder;
use App\Models\InventoryOperationRecord;
use App\Models\Location;
use App\Models\OrderRequest;
use App\Models\Process;
use App\Models\Stock;
use App\Models\StockAlias;
use App\Models\StockImage;
use App\Models\StockStorage;
use App\Models\StockSupplier;
use App\Models\StorageAddress;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class InventoryController extends Controller
{
    //
    public function create() {}
    public function store(Request $request) {}
    public function show($stock_id, $stock_storage_id, Request $request)
    {
        $request_user_id = $request->request_user_id;
        $request_user = null;

        $stock = Stock::find($stock_id);

        $stock_storage = null;
        $stock->shipments = null;
        $stock->receives = null;
        $stock->aliases = null;

        // 物品依頼用ユーザーデータ
        $processes = Process::all();
        $users = User::where('del_flg', 0)->get();

        // 発注依頼を取得
        $order_requests = OrderRequest::select('order_requests.*', 'users.name as user_name', 'request_users.name as request_user_name')
            ->leftJoin('users', 'order_requests.user_id', '=', 'users.id')
            ->leftJoin('users as request_users', 'order_requests.request_user_id', 'request_users.id')
            ->where('stock_id', $stock_id)
            ->where('order_requests.del_flg', 0)
            // ->where('status', 0)
            ->orderBy('created_at', 'desc')
            ->get();
        $stock->order_requests = $order_requests;

        // 発注データ
        $initial_orders = InitialOrder::select('initial_orders.*', 'users.name as user_name', 'order_users.name as order_user_name')
            ->leftJoin('users', 'users.id', 'initial_orders.user_id')
            ->leftJoin('users as order_users', 'initial_orders.order_user_id', 'order_users.id')
            ->where('stock_id', $stock->id)
            ->orderBy('initial_orders.order_date', 'desc')
            ->get();
        $stock->initial_orders = $initial_orders;

        // サムネイル以外の画像取得
        $stock_images = StockImage::select('id', 'img_path')->where('stock_id', $stock_id)->where('del_flg', 0)->get();
        $stock->stock_images = $stock_images;

        if ($stock_storage_id) {
            $stock_storage = StockStorage::select('stock_storages.*', 'locations.name as location_name', 'storage_addresses.address')->join('storage_addresses', 'stock_storages.storage_address_id', 'storage_addresses.id')
                ->join('locations', 'storage_addresses.location_id', 'locations.id')->where('stock_storages.id', $stock_storage_id)->first();
            $stock->stock_storage = $stock_storage;

            // 過去12ヶ月間の出庫データ取得
            $shipments = InventoryOperationRecord::where('stock_id', $stock_id)
                ->where('inventory_operation_id', 2)
                ->orderBy('created_at', 'desc')
                ->get()
                ->groupBy(function ($date) {
                    return Carbon::parse($date->created_at)->format('Y-m');
                })
                ->map(function ($group) {
                    return $group->sum('quantity');
                });

            $currentMonth = Carbon::now()->startOfMonth();
            $shipmentData = [];

            for ($i = 11; $i >= 0; $i--) {
                $month = $currentMonth->copy()->subMonths($i)->format('Y-m');
                $shipmentData[] = $shipments->get($month, 0);
            }

            // 過去12ヶ月間の入庫データ取得
            $receives = InventoryOperationRecord::where('stock_id', $stock_id)
                ->where('inventory_operation_id', 8)
                ->orderBy('created_at', 'desc')
                ->get()
                ->groupBy(function ($date) {
                    return Carbon::parse($date->created_at)->format('Y-m');
                })
                ->map(function ($group) {
                    return $group->sum('quantity');
                });

            $receiveData = [];

            for ($i = 11; $i >= 0; $i--) {
                $month = $currentMonth->copy()->subMonths($i)->format('Y-m');
                $receiveData[] = $receives->get($month, 0); // 修正: $shipmentsから$receivesに変更
            }

            $stock->shipments = $shipmentData;
            $stock->receives = $receiveData;

            // 略名を取得
            $aliases = StockAlias::where('stock_id', $stock_id)->get();
            $stock->aliases = $aliases;

            // 発注依頼者を取得
            if ($request_user_id) {
                $request_user = User::select('id', 'name', 'process_id')->find($request_user_id);
            }
        }

        // 手配先情報を取得
        $stock_supplier = StockSupplier::where('stock_id', $stock_id)->first();
        $stock->stock_supplier = $stock_supplier;
        return Inertia::render('Stock/Inventory', ['stock' => $stock, 'request_user' => $request_user, 'processes' => $processes, 'users' => $users]);
    }


    public function updateFile(Request $request)
    {
        $status = true;
        $msg = '';



        try {
            if ($request->hasFile('files')) {
                $stock_id = $request->stock_id;
                $image_type = $request->image_type;
                $files = $request->file('files');

                if ($image_type == 'thumbnail') {
                    // 画像を保存する
                    $timestamp = now()->timestamp;
                    $filename = $timestamp . '.' . $files[0]->getClientOriginalExtension();
                    $files[0]->storeAs('public/stock', $filename);

                    // 保存した画像のパスに更新
                    $stock = Stock::find($stock_id);
                    $stock->img_path = 'storage/stock/' . $filename;
                    $stock->save();
                } else {
                    foreach ($files as $file) {
                        // 画像を保存する
                        $timestamp = now()->timestamp;
                        $filename = $timestamp . '.' . $file->getClientOriginalExtension();
                        $file->storeAs('public/stock', $filename);

                        $stock_image = new StockImage();
                        $stock_image->stock_id = $stock_id;
                        $stock_image->img_path = 'storage/stock/' . $filename;
                        $stock_image->del_flg = 0;
                        $stock_image->save();
                    }
                }

                $msg = "ファイルアップロードが完了しました";
            }
        } catch (Exception $e) {
            $status = false;
            $msg = $e->getMessage();
        }

        return response()->json(['status' => $status, 'msg' => $msg], 200);
    }

    public function changeQuantity(Request $request)
    {
        $status = true;
        $msg = '';

        $stock_id = $request->stock_id;
        $stock_storage_id = $request->stock_storage_id;
        $quantity = $request->quantity;
        $bef_quantity = 0;

        try {

            // 数量を変更
            $stock_storage = StockStorage::find($stock_storage_id);
            $bef_quantity = $stock_storage->quantity;
            $stock_storage->quantity = $quantity;
            $stock_storage->save();


            // 編集履歴を保存
            $inventory_operation_record = new InventoryOperationRecord();
            $inventory_operation_record->stock_id = $stock_id;
            $inventory_operation_record->stock_storage_id = $stock_storage_id;
            $inventory_operation_record->quantity = $quantity;
            $inventory_operation_record->inventory_operation_id = 9;
            $inventory_operation_record->bef_quantity = $bef_quantity;
            $inventory_operation_record->save();
        } catch (Exception $e) {
            $msg = $e->getMessage();
            $status = false;
        }



        return response()->json(['status' => $status, 'msg' => $msg]);
    }

    // 格納先アドレス新規作成
    public function createStockStorage(Request $request)
    {
        $status = true;
        $msg = '';

        $storage_address_id = $request->storage_address_id;
        $quantity = $request->quantity;
        $stock_id = $request->stock_id;
        $stock_storage_id = $request->stock_storage_id ?? 0;


        try {
            $stock_storage = StockStorage::where('stock_id', $stock_id)->where('storage_address_id', $storage_address_id)->first();

            if (!$stock_storage) {

                $stock_storage = new StockStorage();
                $stock_storage->stock_id = $stock_id;
                $stock_storage->storage_address_id = $storage_address_id;
                $stock_storage->quantity = $quantity;
                $stock_storage->save();
            } else {
                $stock_storage->quantity = $stock_storage->quantity + $quantity;
                $stock_storage->save();
            }

            // 既存の格納先から数量を減らす
            if ($stock_storage_id) {
                $already_stock_storage = StockStorage::find($stock_storage_id);
                $already_stock_storage->quantity = $already_stock_storage->quantity - $quantity;
                $already_stock_storage->save();
            }
        } catch (Exception $e) {
            $status = false;
            $msg = $e->getMessage();
        }

        return response()->json(['status' => $status, 'msg' => $msg, 'stock_storage_id' => $stock_storage->id]);
    }

    // 格納先アドレス削除
    public function deleteStockStorage(Request $request){
        $status = true;
        $msg = '';

        $stock_storage_id = $request->stock_storage_id;

        $stock_storage = StockStorage::find($stock_storage_id);
        $stock_storage->delete();

        return response()->json(['status' => $status, 'msg' => $msg]);
    }

    // 格納先・アドレス編集
    public function getLocations()
    {
        $locations = Location::where('del_flg', 0)->get();

        return response()->json($locations);
    }

    public function getStorageAddresses($location_id)
    {

        $storage_addresses = StorageAddress::where('location_id', $location_id)->orderBy('address', 'asc')->get();
        return response()->json($storage_addresses);
    }

    // 画像削除
    public function deleteImage(Request $request)
    {
        $status = true;
        $msg = '';

        $image_path = $request->image_path;

        try {
            $stock_image = StockImage::where('img_path', $image_path)->first();
            $stock_image->del_flg = 1;
            $stock_image->save();
        } catch (Exception $e) {
            $status = false;
            $msg = $e->getMessage();
        }

        return response()->json(['status' => $status, 'msg' => $msg]);
    }
}
