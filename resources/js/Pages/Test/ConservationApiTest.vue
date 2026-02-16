<script setup>
import { ref, reactive, computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import axios from "axios";

const page = usePage();
const subtractApiUrl = computed(() => page.props.apiUrls?.stockStoragesSubtract ?? "/api/stock-storages/subtract");
const inventoryApiBase = computed(() => page.props.apiUrls?.stockStoragesUpdateQuantity ?? "/api/stock-storages");

const activeTab = ref("stocks");

// 物品 API
const stockListParams = reactive({
  name: "",
  s_name: "",
  per_page: "15",
});
const stockIdsParam = ref(""); // 複数ID検索用（カンマまたは改行区切り）
const stockListData = ref(null); // 一覧レスポンス全体（data.data が配列）
const selectedStock = ref(null);
const stockId = ref("");
const stockBody = ref("");
const stockResponse = ref({ status: null, data: null, error: null });

// 更新用 body に含めるキー（API が受け付ける項目のみ）
const STOCK_BODY_KEYS = [
  "name", "s_name", "stock_no", "img_path", "url", "stock_process_id",
  "tax_included", "price", "solo_unit", "org_unit", "quantity_per_org",
  "deli_location", "memo", "del_flg", "classification_id", "not_stock_flg",
  "purchase_identification_number", "jan_code", "main_unit_flg", "price_check_flg",
  "approval_supplier_name", "special_area_cd", "desc_memo", "show_price_on_invoice",
];

// 在庫減算（選択中の物品の在庫から減算する用）
const subtractQtyByStorageId = reactive({});
const subtractResponse = ref({ status: null, data: null, error: null });

// 棚卸（数量上書き用）
const inventoryQtyByStorageId = reactive({});

// ユーザー API
const userListParams = reactive({
  name: "",
  per_page: "15",
});
const userListData = ref(null);
const selectedUser = ref(null);
const userId = ref("");
const userResponse = ref({ status: null, data: null, error: null });

const loading = ref(false);

function buildQuery(params) {
  const q = new URLSearchParams();
  Object.entries(params).forEach(([k, v]) => {
    if (v !== "" && v !== null && v !== undefined) q.set(k, v);
  });
  const s = q.toString();
  return s ? "?" + s : "";
}

function buildStockBodyFromItem(item) {
  const o = {};
  STOCK_BODY_KEYS.forEach((k) => {
    if (Object.prototype.hasOwnProperty.call(item, k)) o[k] = item[k];
  });
  return JSON.stringify(o, null, 2);
}

function selectStock(item) {
  selectedStock.value = item;
  stockId.value = String(item.id);
  stockBody.value = buildStockBodyFromItem(item);
  subtractResponse.value = { status: null, data: null, error: null };
  (item.stock_storages || []).forEach((s) => {
    subtractQtyByStorageId[s.id] = subtractQtyByStorageId[s.id] ?? 1;
    inventoryQtyByStorageId[s.id] = s.quantity ?? 0;
  });
}

function selectUser(item) {
  selectedUser.value = item;
  userId.value = String(item.id);
}

function stockListItems() {
  const d = stockListData.value?.data;
  return Array.isArray(d) ? d : [];
}

function userListItems() {
  const d = userListData.value?.data;
  return Array.isArray(d) ? d : [];
}

async function runStockIndex() {
  loading.value = true;
  stockResponse.value = { status: null, data: null, error: null };
  stockListData.value = null;
  selectedStock.value = null;
  try {
    const query = buildQuery(stockListParams);
    const res = await axios.get("/api/stocks" + query);
    stockListData.value = res.data;
    stockResponse.value = { status: res.status, data: res.data, error: null };
  } catch (e) {
    stockResponse.value = {
      status: e.response?.status ?? null,
      data: e.response?.data ?? null,
      error: e.message,
    };
  }
  loading.value = false;
}

async function runStockIndexByIds() {
  const raw = stockIdsParam.value.trim().replace(/\s+/g, ",").replace(/,+/g, ",");
  if (!raw) return;
  loading.value = true;
  stockResponse.value = { status: null, data: null, error: null };
  stockListData.value = null;
  selectedStock.value = null;
  try {
    const res = await axios.get("/api/stocks?ids=" + encodeURIComponent(raw));
    stockListData.value = res.data;
    stockResponse.value = { status: res.status, data: res.data, error: null };
  } catch (e) {
    stockResponse.value = {
      status: e.response?.status ?? null,
      data: e.response?.data ?? null,
      error: e.message,
    };
  }
  loading.value = false;
}

async function runStockShow() {
  if (!stockId.value.trim()) return;
  loading.value = true;
  stockResponse.value = { status: null, data: null, error: null };
  try {
    const res = await axios.get("/api/stocks/" + encodeURIComponent(stockId.value.trim()));
    stockResponse.value = { status: res.status, data: res.data, error: null };
    selectedStock.value = res.data;
    stockBody.value = buildStockBodyFromItem(res.data);
    (res.data.stock_storages || []).forEach((s) => {
      subtractQtyByStorageId[s.id] = subtractQtyByStorageId[s.id] ?? 1;
      inventoryQtyByStorageId[s.id] = s.quantity ?? 0;
    });
  } catch (e) {
    stockResponse.value = {
      status: e.response?.status ?? null,
      data: e.response?.data ?? null,
      error: e.message,
    };
  }
  loading.value = false;
}

async function runStockStore() {
  loading.value = true;
  stockResponse.value = { status: null, data: null, error: null };
  try {
    const body = JSON.parse(stockBody.value);
    const res = await axios.post("/api/stocks", body);
    stockResponse.value = { status: res.status, data: res.data, error: null };
  } catch (e) {
    stockResponse.value = {
      status: e.response?.status ?? null,
      data: e.response?.data ?? null,
      error: e.message,
    };
  }
  loading.value = false;
}

async function runStockUpdate() {
  if (!stockId.value.trim()) return;
  loading.value = true;
  stockResponse.value = { status: null, data: null, error: null };
  try {
    const body = JSON.parse(stockBody.value);
    const res = await axios.put("/api/stocks/" + encodeURIComponent(stockId.value.trim()), body);
    stockResponse.value = { status: res.status, data: res.data, error: null };
    selectedStock.value = res.data;
    stockBody.value = buildStockBodyFromItem(res.data);
  } catch (e) {
    stockResponse.value = {
      status: e.response?.status ?? null,
      data: e.response?.data ?? null,
      error: e.message,
    };
  }
  loading.value = false;
}

async function runStockDestroy() {
  if (!stockId.value.trim()) return;
  if (!confirm("この物品を論理削除しますか？")) return;
  loading.value = true;
  stockResponse.value = { status: null, data: null, error: null };
  try {
    const res = await axios.delete("/api/stocks/" + encodeURIComponent(stockId.value.trim()));
    stockResponse.value = { status: res.status, data: res.data, error: null };
    selectedStock.value = null;
    stockId.value = "";
    stockBody.value = "";
  } catch (e) {
    stockResponse.value = {
      status: e.response?.status ?? null,
      data: e.response?.data ?? null,
      error: e.message,
    };
  }
  loading.value = false;
}

async function runStockStorageSubtractOne(storageId, quantity) {
  const id = Number(storageId);
  const qty = parseInt(quantity, 10);
  if (!id || !Number.isInteger(qty) || qty < 1) {
    subtractResponse.value = { status: null, data: null, error: "減算数量を1以上の整数で入力してください。" };
    return;
  }
  loading.value = true;
  subtractResponse.value = { status: null, data: null, error: null };
  try {
    const res = await axios.post(subtractApiUrl.value, {
      stock_storage_id: id,
      quantity: qty,
    });
    subtractResponse.value = { status: res.status, data: res.data, error: null };
    const newQty = res.data?.results?.[0]?.new_quantity;
    if (selectedStock.value?.stock_storages != null && newQty !== undefined) {
      selectedStock.value.stock_storages = selectedStock.value.stock_storages.map((s) =>
        Number(s.id) === id ? { ...s, quantity: newQty } : s
      );
    }
  } catch (e) {
    subtractResponse.value = {
      status: e.response?.status ?? null,
      data: e.response?.data ?? null,
      error: e.message,
    };
  }
  loading.value = false;
}

async function runStockStorageSubtractBatch() {
  const items = (selectedStock.value?.stock_storages || [])
    .map((s) => ({ id: s.id, qty: parseInt(subtractQtyByStorageId[s.id], 10) }))
    .filter((x) => Number.isInteger(x.qty) && x.qty > 0);
  if (items.length === 0) {
    subtractResponse.value = { status: null, data: null, error: "減算する在庫を1件以上、数量を1以上にしてください。" };
    return;
  }
  loading.value = true;
  subtractResponse.value = { status: null, data: null, error: null };
  try {
    const body = items.map((x) => ({ stock_storage_id: x.id, quantity: x.qty }));
    const res = await axios.post(subtractApiUrl.value, body);
    subtractResponse.value = { status: res.status, data: res.data, error: null };
    const updates = new Map((res.data?.results || []).filter((r) => r.success && r.stock_storage_id != null && r.new_quantity !== undefined).map((r) => [Number(r.stock_storage_id), r.new_quantity]));
    if (selectedStock.value?.stock_storages && updates.size > 0) {
      selectedStock.value.stock_storages = selectedStock.value.stock_storages.map((s) =>
        updates.has(Number(s.id)) ? { ...s, quantity: updates.get(Number(s.id)) } : s
      );
    }
  } catch (e) {
    subtractResponse.value = {
      status: e.response?.status ?? null,
      data: e.response?.data ?? null,
      error: e.message,
    };
  }
  loading.value = false;
}

async function runStockStorageUpdateQuantity(storageId, quantity) {
  const id = Number(storageId);
  const qty = parseInt(quantity, 10);
  if (!id || !Number.isInteger(qty) || qty < 0) {
    subtractResponse.value = { status: null, data: null, error: "棚卸数量を0以上の整数で入力してください。" };
    return;
  }
  loading.value = true;
  subtractResponse.value = { status: null, data: null, error: null };
  try {
    const res = await axios.put(`${inventoryApiBase.value}/${id}`, { quantity: qty });
    subtractResponse.value = { status: res.status, data: res.data, error: null };
    if (selectedStock.value?.stock_storages != null && res.data?.quantity !== undefined) {
      selectedStock.value.stock_storages = selectedStock.value.stock_storages.map((s) =>
        Number(s.id) === id ? { ...s, quantity: res.data.quantity } : s
      );
    }
  } catch (e) {
    subtractResponse.value = {
      status: e.response?.status ?? null,
      data: e.response?.data ?? null,
      error: e.message,
    };
  }
  loading.value = false;
}

async function runUserIndex() {
  loading.value = true;
  userResponse.value = { status: null, data: null, error: null };
  userListData.value = null;
  selectedUser.value = null;
  try {
    const query = buildQuery(userListParams);
    const res = await axios.get("/api/users" + query);
    userListData.value = res.data;
    userResponse.value = { status: res.status, data: res.data, error: null };
  } catch (e) {
    userResponse.value = {
      status: e.response?.status ?? null,
      data: e.response?.data ?? null,
      error: e.message,
    };
  }
  loading.value = false;
}

async function runUserShow() {
  if (!userId.value.trim()) return;
  loading.value = true;
  userResponse.value = { status: null, data: null, error: null };
  try {
    const res = await axios.get("/api/users/" + encodeURIComponent(userId.value.trim()));
    userResponse.value = { status: res.status, data: res.data, error: null };
    selectedUser.value = res.data;
  } catch (e) {
    userResponse.value = {
      status: e.response?.status ?? null,
      data: e.response?.data ?? null,
      error: e.message,
    };
  }
  loading.value = false;
}

function formatJson(val) {
  if (val === null || val === undefined) return "null";
  try {
    return JSON.stringify(val, null, 2);
  } catch {
    return String(val);
  }
}

function storageAddressLabel(storage) {
  const addr = storage?.storage_address?.address ?? "-";
  const loc = storage?.storage_address?.location?.name;
  return loc ? `${loc} / ${addr}` : addr;
}
</script>

<template>
  <div class="min-h-screen bg-gray-100 p-4">
    <div class="max-w-6xl mx-auto">
      <h1 class="text-2xl font-bold text-gray-800 mb-2">別システム連携API テスト</h1>
      <p class="text-gray-600 text-sm mb-6">検索結果の行を選択すると、更新・削除・在庫減算に値が自動で入ります。確定ボタンで実行してください。</p>

      <div class="flex gap-2 mb-6">
        <button
          type="button"
          :class="[
            'px-4 py-2 rounded-lg font-medium transition-colors',
            activeTab === 'stocks'
              ? 'bg-blue-600 text-white'
              : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-300'
          ]"
          @click="activeTab = 'stocks'"
        >
          物品 (Stocks)
        </button>
        <button
          type="button"
          :class="[
            'px-4 py-2 rounded-lg font-medium transition-colors',
            activeTab === 'users'
              ? 'bg-blue-600 text-white'
              : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-300'
          ]"
          @click="activeTab = 'users'"
        >
          ユーザー (Users)
        </button>
      </div>

      <!-- 物品 -->
      <div v-show="activeTab === 'stocks'" class="space-y-6">
        <!-- 一覧検索 -->
        <section class="bg-white rounded-lg shadow p-4">
          <h2 class="text-lg font-semibold text-gray-800 mb-3">GET /api/stocks（一覧・検索・有効のみ）</h2>
          <p class="text-sm text-gray-600 mb-2">検索は name / s_name の部分一致のみ。有効（del_flg=0）の物品のみ取得されます。</p>
          <div class="grid grid-cols-2 md:grid-cols-3 gap-2 mb-3 text-sm">
            <div>
              <label class="block text-gray-600 mb-1">name</label>
              <input v-model="stockListParams.name" type="text" class="w-full border rounded px-2 py-1" placeholder="部分一致" />
            </div>
            <div>
              <label class="block text-gray-600 mb-1">s_name</label>
              <input v-model="stockListParams.s_name" type="text" class="w-full border rounded px-2 py-1" placeholder="部分一致" />
            </div>
            <div>
              <label class="block text-gray-600 mb-1">per_page</label>
              <input v-model="stockListParams.per_page" type="text" class="w-full border rounded px-2 py-1" />
            </div>
          </div>
          <button
            type="button"
            :disabled="loading"
            class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50"
            @click="runStockIndex"
          >
            検索
          </button>
        </section>

        <!-- stocks.idから検索 -->
        <section class="bg-white rounded-lg shadow p-4">
          <h2 class="text-lg font-semibold text-gray-800 mb-3">stocks.id から検索（複数ID取得）</h2>
          <p class="text-sm text-gray-600 mb-2">複数の物品IDを指定して取得。カンマまたは改行で区切って入力。有効（del_flg=0）のもののみ返却されます。</p>
          <div class="mb-3">
            <label class="block text-gray-600 text-sm mb-1">物品ID（例: 1,2,3 または 1 2 3）</label>
            <textarea
              v-model="stockIdsParam"
              rows="3"
              class="w-full border rounded px-2 py-1 font-mono text-sm"
              placeholder="1, 2, 3"
            ></textarea>
          </div>
          <button
            type="button"
            :disabled="loading"
            class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50"
            @click="runStockIndexByIds"
          >
            検索
          </button>
        </section>

        <!-- 検索結果テーブル -->
        <section v-if="stockListItems().length > 0" class="bg-white rounded-lg shadow overflow-hidden">
          <h2 class="text-lg font-semibold text-gray-800 p-4 pb-2">検索結果（行を選択すると下の操作に値が入ります）</h2>
          <div class="overflow-x-auto max-h-80 overflow-y-auto">
            <table class="w-full text-sm">
              <thead class="bg-gray-100 sticky top-0">
                <tr>
                  <th class="text-left p-2 border-b">ID</th>
                  <th class="text-left p-2 border-b">name</th>
                  <th class="text-left p-2 border-b">s_name</th>
                  <th class="text-left p-2 border-b">jan_code</th>
                  <th class="text-left p-2 border-b">在庫数</th>
                  <th class="text-left p-2 border-b w-24">操作</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="row in stockListItems()"
                  :key="row.id"
                  :class="selectedStock?.id === row.id ? 'bg-blue-50' : 'hover:bg-gray-50'"
                  class="border-b"
                >
                  <td class="p-2">{{ row.id }}</td>
                  <td class="p-2">{{ row.name }}</td>
                  <td class="p-2">{{ row.s_name }}</td>
                  <td class="p-2">{{ row.jan_code ?? "-" }}</td>
                  <td class="p-2">{{ (row.stock_storages || []).length }} 箇所</td>
                  <td class="p-2">
                    <button
                      type="button"
                      class="px-2 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700"
                      @click="selectStock(row)"
                    >
                      選択
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <!-- 選択中の物品：1件取得・更新・削除 -->
        <section v-if="selectedStock" class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
          <h2 class="text-lg font-semibold text-gray-800 mb-3">選択中の物品（ID: {{ selectedStock.id }}）</h2>
          <div class="flex flex-wrap gap-3 mb-4">
            <button
              type="button"
              :disabled="loading"
              class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50"
              @click="runStockShow"
            >
              1件取得
            </button>
            <button
              type="button"
              :disabled="loading"
              class="px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700 disabled:opacity-50"
              @click="runStockUpdate"
            >
              更新（確定）
            </button>
            <button
              type="button"
              :disabled="loading"
              class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 disabled:opacity-50"
              @click="runStockDestroy"
            >
              論理削除（確定）
            </button>
          </div>
          <div class="mb-3">
            <label class="block text-gray-600 text-sm mb-1">更新用 JSON（編集して「更新（確定）」で反映）</label>
            <textarea
              v-model="stockBody"
              rows="10"
              class="w-full border rounded px-2 py-1 font-mono text-sm"
              spellcheck="false"
            ></textarea>
          </div>

          <!-- 在庫一覧：減算・棚卸 -->
          <div v-if="(selectedStock.stock_storages || []).length > 0" class="mt-4 pt-4 border-t">
            <h3 class="font-semibold text-gray-800 mb-2">在庫格納先（減算・棚卸）</h3>
            <div class="overflow-x-auto">
              <table class="w-full text-sm border">
                <thead class="bg-gray-100">
                  <tr>
                    <th class="text-left p-2 border">格納先ID</th>
                    <th class="text-left p-2 border">保管場所</th>
                    <th class="text-right p-2 border">現在数</th>
                    <th class="text-right p-2 border w-28">減算数</th>
                    <th class="text-left p-2 border w-24">操作</th>
                    <th class="text-right p-2 border w-28">棚卸（上書き数）</th>
                    <th class="text-left p-2 border w-20">操作</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="s in selectedStock.stock_storages" :key="s.id" class="border-b">
                    <td class="p-2 border">{{ s.id }}</td>
                    <td class="p-2 border">{{ storageAddressLabel(s) }}</td>
                    <td class="p-2 border text-right">{{ s.quantity }}</td>
                    <td class="p-2 border">
                      <input
                        v-model.number="subtractQtyByStorageId[s.id]"
                        type="number"
                        min="1"
                        class="w-full border rounded px-2 py-1 text-right"
                      />
                    </td>
                    <td class="p-2 border">
                      <button
                        type="button"
                        :disabled="loading"
                        class="px-2 py-1 bg-amber-600 text-white text-xs rounded hover:bg-amber-700 disabled:opacity-50"
                        @click="runStockStorageSubtractOne(s.id, subtractQtyByStorageId[s.id])"
                      >
                        減算
                      </button>
                    </td>
                    <td class="p-2 border">
                      <input
                        v-model.number="inventoryQtyByStorageId[s.id]"
                        type="number"
                        min="0"
                        class="w-full border rounded px-2 py-1 text-right"
                        placeholder="0"
                      />
                    </td>
                    <td class="p-2 border">
                      <button
                        type="button"
                        :disabled="loading"
                        class="px-2 py-1 bg-teal-600 text-white text-xs rounded hover:bg-teal-700 disabled:opacity-50"
                        @click="runStockStorageUpdateQuantity(s.id, inventoryQtyByStorageId[s.id])"
                      >
                        棚卸
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <button
              type="button"
              :disabled="loading"
              class="mt-2 px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700 disabled:opacity-50"
              @click="runStockStorageSubtractBatch"
            >
              上記の減算数を一括で減算（確定）
            </button>
          </div>
          <p v-else class="text-gray-500 text-sm mt-2">在庫格納先がありません。1件取得で再取得するか、別の物品を選択してください。</p>
        </section>

        <!-- 新規登録（JSON のみ） -->
        <section class="bg-white rounded-lg shadow p-4">
          <h2 class="text-lg font-semibold text-gray-800 mb-3">POST /api/stocks（新規登録）</h2>
          <div class="mb-3">
            <label class="block text-gray-600 text-sm mb-1">JSON Body（name 必須）</label>
            <textarea
              v-model="stockBody"
              rows="6"
              class="w-full border rounded px-2 py-1 font-mono text-sm"
              spellcheck="false"
              placeholder='{"name": "テスト品名", "s_name": "テスト品番"}'
            ></textarea>
          </div>
          <button
            type="button"
            :disabled="loading"
            class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50"
            @click="runStockStore"
          >
            新規登録
          </button>
        </section>

        <!-- レスポンス（物品・在庫減算） -->
        <section v-if="stockResponse.status !== null || stockResponse.error" class="bg-white rounded-lg shadow p-4">
          <h2 class="text-lg font-semibold text-gray-800 mb-2">レスポンス（物品）</h2>
          <div class="mb-2">
            <span v-if="stockResponse.status" :class="stockResponse.status < 400 ? 'text-green-600' : 'text-red-600'" class="font-mono">Status: {{ stockResponse.status }}</span>
            <span v-if="stockResponse.error" class="text-red-600 ml-2">{{ stockResponse.error }}</span>
          </div>
          <pre class="bg-gray-50 p-3 rounded overflow-auto text-sm font-mono max-h-80">{{ formatJson(stockResponse.data) }}</pre>
        </section>
        <section v-if="subtractResponse.status !== null || subtractResponse.error" class="bg-white rounded-lg shadow p-4">
          <h2 class="text-lg font-semibold text-gray-800 mb-2">レスポンス（在庫減算・棚卸）</h2>
          <div class="mb-2">
            <span v-if="subtractResponse.status" :class="subtractResponse.status < 400 ? 'text-green-600' : 'text-red-600'" class="font-mono">Status: {{ subtractResponse.status }}</span>
            <span v-if="subtractResponse.error" class="text-red-600 ml-2">{{ subtractResponse.error }}</span>
          </div>
          <pre class="bg-gray-50 p-3 rounded overflow-auto text-sm font-mono max-h-80">{{ formatJson(subtractResponse.data) }}</pre>
        </section>
      </div>

      <!-- ユーザー -->
      <div v-show="activeTab === 'users'" class="space-y-6">
        <section class="bg-white rounded-lg shadow p-4">
          <h2 class="text-lg font-semibold text-gray-800 mb-3">GET /api/users（一覧・検索・有効のみ）</h2>
          <p class="text-sm text-gray-600 mb-2">検索は name（氏名）の部分一致のみ。有効（del_flg=0）のユーザーのみ取得されます。</p>
          <div class="grid grid-cols-2 md:grid-cols-3 gap-2 mb-3 text-sm">
            <div>
              <label class="block text-gray-600 mb-1">name</label>
              <input v-model="userListParams.name" type="text" class="w-full border rounded px-2 py-1" placeholder="部分一致" />
            </div>
            <div>
              <label class="block text-gray-600 mb-1">per_page</label>
              <input v-model="userListParams.per_page" type="text" class="w-full border rounded px-2 py-1" />
            </div>
          </div>
          <button
            type="button"
            :disabled="loading"
            class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50"
            @click="runUserIndex"
          >
            検索
          </button>
        </section>

        <section v-if="userListItems().length > 0" class="bg-white rounded-lg shadow overflow-hidden">
          <h2 class="text-lg font-semibold text-gray-800 p-4 pb-2">検索結果（選択で1件取得にIDが入ります）</h2>
          <div class="overflow-x-auto max-h-80 overflow-y-auto">
            <table class="w-full text-sm">
              <thead class="bg-gray-100 sticky top-0">
                <tr>
                  <th class="text-left p-2 border-b">ID</th>
                  <th class="text-left p-2 border-b">name</th>
                  <th class="text-left p-2 border-b">group_id</th>
                  <th class="text-left p-2 border-b">process_id</th>
                  <th class="text-left p-2 border-b w-24">操作</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="row in userListItems()"
                  :key="row.id"
                  :class="selectedUser?.id === row.id ? 'bg-blue-50' : 'hover:bg-gray-50'"
                  class="border-b"
                >
                  <td class="p-2">{{ row.id }}</td>
                  <td class="p-2">{{ row.name }}</td>
                  <td class="p-2">{{ row.group_id ?? "-" }}</td>
                  <td class="p-2">{{ row.process_id ?? "-" }}</td>
                  <td class="p-2">
                    <button
                      type="button"
                      class="px-2 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700"
                      @click="selectUser(row)"
                    >
                      選択
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <section v-if="selectedUser" class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
          <h2 class="text-lg font-semibold text-gray-800 mb-3">選択中のユーザー（ID: {{ selectedUser.id }}）</h2>
          <button
            type="button"
            :disabled="loading"
            class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50"
            @click="runUserShow"
          >
            1件取得（確定）
          </button>
        </section>

        <section v-if="userResponse.status !== null || userResponse.error" class="bg-white rounded-lg shadow p-4">
          <h2 class="text-lg font-semibold text-gray-800 mb-2">レスポンス（ユーザー）</h2>
          <div class="mb-2">
            <span v-if="userResponse.status" :class="userResponse.status < 400 ? 'text-green-600' : 'text-red-600'" class="font-mono">Status: {{ userResponse.status }}</span>
            <span v-if="userResponse.error" class="text-red-600 ml-2">{{ userResponse.error }}</span>
          </div>
          <pre class="bg-gray-50 p-3 rounded overflow-auto text-sm font-mono max-h-96">{{ formatJson(userResponse.data) }}</pre>
        </section>
      </div>

      <div v-if="loading" class="fixed inset-0 bg-black/20 flex items-center justify-center z-10">
        <span class="bg-white px-4 py-2 rounded shadow">送信中...</span>
      </div>
    </div>
  </div>
</template>
