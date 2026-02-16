<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\StockStorage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 別システム連携用 API コントローラー
 * 物品（stocks）とユーザー（users）の REST API を提供する。
 */
class ConservationApiController extends Controller
{
    /**
     * 物品一覧・検索（有効のみ。検索は name / s_name の部分一致、または ids で複数ID指定）
     */
    public function stockIndex(Request $request): JsonResponse
    {
        $query = Stock::query()->where('del_flg', 0);

        if ($request->filled('ids')) {
            $ids = $request->ids;
            $ids = is_array($ids) ? $ids : array_map('intval', array_filter(array_map('trim', explode(',', $ids))));
            if ($ids !== []) {
                $query->whereIn('id', $ids);
            }
        }
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }
        if ($request->filled('s_name')) {
            $query->where('s_name', 'like', '%' . $request->s_name . '%');
        }

        $query->orderBy('updated_at', 'desc');

        $perPage = (int) $request->get('per_page', 15);
        $perPage = $perPage >= 1 && $perPage <= 100 ? $perPage : 15;
        $stocks = $query->with([
            'stockStorages.storageAddress.location',
            'stockSuppliers.supplier',
            'aliases',
            'stockImages' => fn ($q) => $q->where('del_flg', 0),
        ])->paginate($perPage);

        return response()->json($stocks);
    }

    /**
     * 物品1件取得
     */
    public function stockShow(int $id): JsonResponse
    {
        $stock = Stock::with([
            'aliases',
            'stockStorages.storageAddress.location',
            'stockSuppliers.supplier',
            'stockImages' => fn ($q) => $q->where('del_flg', 0),
        ])->findOrFail($id);

        return response()->json($stock);
    }

    /**
     * 物品新規登録
     */
    public function stockStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            's_name' => 'nullable|string|max:255',
            'stock_no' => 'nullable|string|max:255',
            'img_path' => 'nullable|string|max:255',
            'url' => 'nullable|string|max:255',
            'stock_process_id' => 'nullable|string|max:255',
            'tax_included' => 'nullable|boolean',
            'price' => 'nullable|numeric',
            'solo_unit' => 'nullable|string|max:255',
            'org_unit' => 'nullable|string|max:255',
            'quantity_per_org' => 'nullable|integer|min:0',
            'deli_location' => 'nullable|string|max:255',
            'memo' => 'nullable|string',
            'classification_id' => 'nullable|integer',
            'not_stock_flg' => 'nullable|boolean',
            'purchase_identification_number' => 'nullable|string|max:255',
            'jan_code' => 'nullable|string|max:255',
            'main_unit_flg' => 'nullable|integer',
            'price_check_flg' => 'nullable|integer',
            'approval_supplier_name' => 'nullable|string|max:255',
            'special_area_cd' => 'nullable|integer',
            'desc_memo' => 'nullable|string|max:255',
            'show_price_on_invoice' => 'nullable|integer',
        ]);

        $validated['del_flg'] = 0;
        $stock = Stock::create($validated);
        $stock->load(['stockStorages.storageAddress.location', 'stockSuppliers.supplier', 'aliases', 'stockImages']);

        return response()->json($stock, 201);
    }

    /**
     * 物品更新
     */
    public function stockUpdate(Request $request, int $id): JsonResponse
    {
        $stock = Stock::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            's_name' => 'nullable|string|max:255',
            'stock_no' => 'nullable|string|max:255',
            'img_path' => 'nullable|string|max:255',
            'url' => 'nullable|string|max:255',
            'stock_process_id' => 'nullable|string|max:255',
            'tax_included' => 'nullable|boolean',
            'price' => 'nullable|numeric',
            'solo_unit' => 'nullable|string|max:255',
            'org_unit' => 'nullable|string|max:255',
            'quantity_per_org' => 'nullable|integer|min:0',
            'deli_location' => 'nullable|string|max:255',
            'memo' => 'nullable|string',
            'del_flg' => 'nullable|integer|in:0,1',
            'classification_id' => 'nullable|integer',
            'not_stock_flg' => 'nullable|boolean',
            'purchase_identification_number' => 'nullable|string|max:255',
            'jan_code' => 'nullable|string|max:255',
            'main_unit_flg' => 'nullable|integer',
            'price_check_flg' => 'nullable|integer',
            'approval_supplier_name' => 'nullable|string|max:255',
            'special_area_cd' => 'nullable|integer',
            'desc_memo' => 'nullable|string|max:255',
            'show_price_on_invoice' => 'nullable|integer',
        ]);

        $stock->update($validated);
        $stock->load(['stockStorages.storageAddress.location', 'stockSuppliers.supplier', 'aliases', 'stockImages']);

        return response()->json($stock);
    }

    /**
     * 物品論理削除
     */
    public function stockDestroy(int $id): JsonResponse
    {
        $stock = Stock::findOrFail($id);
        $stock->update(['del_flg' => 1]);

        return response()->json(['message' => 'Deleted'], 200);
    }

    /**
     * 在庫格納先数量（stock_storages）の減算
     * 単体または配列で複数件を指定可能。
     * POST body: { "stock_storage_id": 1, "quantity": 5 }
     * または [ { "stock_storage_id": 1, "quantity": 5 }, ... ]
     */
    public function stockStorageSubtract(Request $request): JsonResponse
    {
        $items = $request->input();
        if (! is_array($items)) {
            return response()->json(['message' => 'Invalid request body'], 422);
        }
        // 単体オブジェクトの場合は配列に統一
        if (array_key_exists('stock_storage_id', $items) && array_key_exists('quantity', $items)) {
            $items = [$items];
        }

        $results = [];
        $hasError = false;

        foreach ($items as $index => $item) {
            $validator = validator($item, [
                'stock_storage_id' => 'required|integer|exists:stock_storages,id',
                'quantity' => 'required|integer|min:1',
            ], [], [
                'stock_storage_id' => 'stock_storage_id',
                'quantity' => 'quantity',
            ]);

            if ($validator->fails()) {
                $results[] = [
                    'index' => $index,
                    'success' => false,
                    'error' => $validator->errors()->first(),
                ];
                $hasError = true;
                continue;
            }

            $stockStorageId = (int) $item['stock_storage_id'];
            $subtractQty = (int) $item['quantity'];

            try {
                $result = DB::transaction(function () use ($stockStorageId, $subtractQty) {
                    $storage = StockStorage::where('id', $stockStorageId)->lockForUpdate()->first();
                    if (! $storage) {
                        return ['success' => false, 'error' => 'Stock storage not found.'];
                    }
                    $current = (int) $storage->quantity;
                    if ($current < $subtractQty) {
                        return [
                            'success' => false,
                            'error' => 'Insufficient quantity.',
                            'stock_storage_id' => $stockStorageId,
                            'current_quantity' => $current,
                            'requested_subtract' => $subtractQty,
                        ];
                    }
                    $newQuantity = $current - $subtractQty;
                    $storage->quantity = $newQuantity;
                    $storage->save();

                    return [
                        'success' => true,
                        'stock_storage_id' => $stockStorageId,
                        'previous_quantity' => $current,
                        'subtracted' => $subtractQty,
                        'new_quantity' => $newQuantity,
                    ];
                });
                $results[] = array_merge(['index' => $index], $result);
                if (! ($result['success'] ?? true)) {
                    $hasError = true;
                }
            } catch (\Throwable $e) {
                $results[] = [
                    'index' => $index,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
                $hasError = true;
            }
        }

        $status = $hasError ? 207 : 200;
        return response()->json(['results' => $results], $status);
    }

    /**
     * 棚卸用：在庫格納先（stock_storages）の数量を上書き
     * PUT /api/stock-storages/{id}  body: { "quantity": 数値 }
     */
    public function stockStorageUpdateQuantity(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:0',
        ]);

        $storage = StockStorage::findOrFail($id);
        $storage->quantity = (int) $validated['quantity'];
        $storage->save();

        return response()->json([
            'stock_storage_id' => $storage->id,
            'quantity' => $storage->quantity,
        ]);
    }

    /**
     * ユーザー一覧・検索（有効のみ。検索は name の部分一致のみ）
     */
    public function userIndex(Request $request): JsonResponse
    {
        $query = User::query()->where('del_flg', 0);

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        $query->orderBy('id', 'asc');

        $perPage = (int) $request->get('per_page', 15);
        $perPage = $perPage >= 1 && $perPage <= 100 ? $perPage : 15;
        $users = $query->with(['group', 'position', 'process'])->paginate($perPage)->through(function ($user) {
            return $user->makeHidden(['password', 'remember_token']);
        });

        return response()->json($users);
    }

    /**
     * ユーザー1件取得（パスワード・トークンは返却しない。group / position / process を紐づけて返す）
     */
    public function userShow(int $id): JsonResponse
    {
        $user = User::with(['group', 'position', 'process'])->findOrFail($id);
        $data = $user->makeHidden(['password', 'remember_token'])->toArray();

        return response()->json($data);
    }
}
