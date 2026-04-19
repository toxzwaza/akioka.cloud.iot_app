<script setup>
import { onMounted, reactive, ref } from "vue";
import { Link } from "@inertiajs/vue3";
import axios from "axios";
import StockLayout from "@/Layouts/StockLayout.vue";

const props = defineProps({
  processes: Array,
  users: Array,
  order_request_id: Number,
  initial_order_requests: Object,
});

const form = reactive({
  process_id: 0,
  user_id: 0,
  name: null,
  s_name: null,
});

const order_requests = ref([]);
const pagination = ref({});
const currentPage = ref(1);
const perPage = ref(20);
const process_users = ref([]);
const isSearchOpen = ref(false);
const isLoading = ref(false);

// モーダル関連
const showModal = ref(false);
const selectedOrderRequest = ref({});
const modalLoading = ref(false);
const modalError = ref("");

const changeProcess = (process_id) => {
  console.log(process_id);

  process_users.value = props.users.filter(
    (user) => user.process_id == process_id
  );
};

const toggleSearch = () => {
  isSearchOpen.value = !isSearchOpen.value;
};

const applyResult = (result, page) => {
  order_requests.value = result.data;
  pagination.value = {
    current_page: result.current_page,
    last_page: result.last_page,
    per_page: result.per_page,
    total: result.total,
    from: result.from,
    to: result.to,
  };
  currentPage.value = page;
};

const getOrderRequest = (page = 1, reset_flg) => {
  if (isLoading.value) return;
  if (reset_flg) {
    form.process_id = 0;
    form.user_id = 0;
    form.name = null;
    form.s_name = null;
  }

  isLoading.value = true;
  axios
    .get(route("stock.check_order_request.getOrderRequests"), {
      params: {
        page: page,
        per_page: perPage.value,
        user_id: form.user_id,
        process_id: form.process_id,
        name: form.name,
        s_name: form.s_name,
      },
    })
    .then((res) => {
      applyResult(res.data.order_requests, page);
    })
    .catch((error) => {
      console.log(error);
    })
    .finally(() => {
      isLoading.value = false;
    });
};

const changePage = (page) => {
  if (isLoading.value) return;
  if (page < 1 || page > (pagination.value.last_page || 1)) return;
  getOrderRequest(page);
};

// モーダル関連のメソッド（Show.vueから移植）
const getStatusText = (
  acceptFlg,
  receiveFlg,
  initialOrderId,
  orderCompleteFlg
) => {
  if (receiveFlg) return { text: "納品済", class: "bg-green-900 text-white" };
  if (initialOrderId && orderCompleteFlg)
    return { text: "発注済", class: "bg-blue-700 text-white" };
  if (initialOrderId && !orderCompleteFlg)
    return { text: "未発注", class: "bg-yellow-600 text-white" };
  if (acceptFlg === 0)
    return { text: "依頼済", class: "bg-blue-500 text-white" };
  if (acceptFlg === 1 || acceptFlg === 6)
    return { text: "承認待ち", class: "bg-orange-500 text-white" };
  if (acceptFlg === 2)
    return { text: "承認済", class: "bg-green-500 text-white" };
  if (acceptFlg === 3) return { text: "却下", class: "bg-red-500 text-white" };
  if (acceptFlg === 4)
    return { text: "却下再依頼待ち", class: "bg-gray-500 text-white" };
  if (acceptFlg === 5)
    return { text: "確認中", class: "bg-purple-500 text-white" };
  return { text: "不明", class: "bg-gray-400 text-white" };
};

const formatDate = (dateString) => {
  if (!dateString) return "未設定";
  return new Date(dateString).toLocaleDateString("ja-JP");
};

const formatDateTime = (dateString) => {
  if (!dateString) return "未設定";
  return new Date(dateString).toLocaleString("ja-JP", {
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
    hour12: false,
  });
};

const getFilePreviewUrl = (filePath) => {
  if (!filePath) return "";

  // ファイル拡張子を取得
  const extension = filePath.split(".").pop().toLowerCase();

  // PDFファイルの場合はGoogle Docs Viewerを使用
  if (extension === "pdf") {
    return `https://docs.google.com/viewer?url=${encodeURIComponent(
      filePath
    )}&embedded=true`;
  }

  // その他のファイルは直接表示
  return filePath;
};

const handleIframeError = (event) => {
  console.warn("iframe読み込みエラー:", event);
  const iframe = event.target;
  const fallback = document.getElementById("iframe-fallback");
  if (iframe && fallback) {
    iframe.style.display = "none";
    fallback.style.display = "block";
  }
};

const showOrderRequestDetail = async (orderRequestId) => {
  try {
    modalLoading.value = true;
    modalError.value = "";
    showModal.value = true;

    const response = await axios.get(
      `/check_order_request/detail/${orderRequestId}`
    );

    if (response.data.status) {
      selectedOrderRequest.value = response.data.order_request;
      console.log(selectedOrderRequest.value);
    } else {
      modalError.value = response.data.msg || "データの取得に失敗しました。";
    }
  } catch (err) {
    modalError.value = "サーバーエラーが発生しました。";
    console.error(err);
  } finally {
    modalLoading.value = false;
  }
};

const closeModal = () => {
  showModal.value = false;
  selectedOrderRequest.value = {};
  modalError.value = "";
};

onMounted(() => {
  if (props.order_request_id) {
    showOrderRequestDetail(props.order_request_id);
  }

  // サーバーから渡された初期データを使用（初期AJAXを発行しない）
  if (props.initial_order_requests) {
    applyResult(props.initial_order_requests, props.initial_order_requests.current_page ?? 1);
  } else {
    getOrderRequest();
  }

  process_users.value = props.users;
});
</script>
<template>
  <StockLayout :title="'在庫管理システム'">
    <template #content>
      <!-- ページネーション情報（右上） -->
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3 mb-6">
        <div></div>
        <div class="flex flex-wrap items-center gap-4">
          <div class="text-base text-slate-600">
            全 {{ pagination.total || 0 }} 件中 {{ pagination.from || 0 }}-{{
              pagination.to || 0
            }}
            件を表示
          </div>
          <div class="flex items-center gap-2">
            <button
              @click="changePage(currentPage - 1)"
              :disabled="currentPage <= 1 || isLoading"
              class="btn-ghost px-5 py-3 text-base disabled:opacity-50 disabled:cursor-not-allowed"
            >
              前へ
            </button>
            <span class="text-base text-slate-600 px-2 inline-flex items-center gap-2">
              <span
                v-if="isLoading"
                class="animate-spin rounded-full h-4 w-4 border-2 border-slate-300 border-t-primary-600"
              ></span>
              {{ currentPage }} / {{ pagination.last_page || 1 }}
            </span>
            <button
              @click="changePage(currentPage + 1)"
              :disabled="currentPage >= (pagination.last_page || 1) || isLoading"
              class="btn-ghost px-5 py-3 text-base disabled:opacity-50 disabled:cursor-not-allowed"
            >
              次へ
            </button>
          </div>
        </div>
      </div>

      <!-- 検索ボタン -->
      <div class="flex justify-end mb-5">
        <button
          @click="toggleSearch"
          class="btn-primary flex items-center py-3 px-5 text-base"
        >
          <i class="fas fa-search mr-2"></i>
          {{ isSearchOpen ? "検索を閉じる" : "検索を開く" }}
        </button>
      </div>

      <!-- 検索コンテナ -->
      <div
        id="search_container"
        class="mb-12 transition-all duration-300 ease-in-out"
        :class="
          isSearchOpen
            ? 'opacity-100 max-h-screen'
            : 'opacity-0 max-h-0 overflow-hidden'
        "
      >
        <div class="card mb-6 p-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
            <div>
              <label class="form-label text-base">依頼者</label>
              <select
                class="form-select-modern mt-2 text-base py-4"
                v-model="form.process_id"
                @change="changeProcess($event.target.value)"
              >
                <option value="0">工程を選択</option>
                <option
                  v-for="process in props.processes"
                  :key="process.id"
                  :value="process.id"
                >
                  {{ process.name }}
                </option>
              </select>
            </div>
            <div>
              <label class="form-label text-base invisible md:visible">&nbsp;</label>
              <select
                v-model="form.user_id"
                class="form-select-modern mt-2 text-base py-4"
              >
                <option value="0">依頼者でさらに絞り込み</option>
                <option
                  v-for="user in process_users"
                  :key="user.id"
                  :value="user.id"
                >
                  {{ user.name }}
                </option>
              </select>
            </div>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
            <div>
              <label class="form-label text-base">品名・品番</label>
              <input
                type="text"
                class="form-input-modern mt-2 text-base py-4"
                placeholder="品名"
                v-model="form.name"
              />
            </div>
            <div>
              <label class="form-label text-base invisible md:visible">&nbsp;</label>
              <input
                type="text"
                class="form-input-modern mt-2 text-base py-4"
                placeholder="品番"
                v-model="form.s_name"
              />
            </div>
          </div>

          <div class="flex items-center justify-center mt-6 gap-4">
            <button
              @click="getOrderRequest(null, 1)"
              class="btn-danger py-3 px-6 text-base"
            >
              リセット
            </button>
            <button
              @click="getOrderRequest(null, 0)"
              class="btn-primary py-3 px-8 text-base"
            >
              検索
            </button>
          </div>
        </div>
      </div>

      <section id="table_container" class="text-slate-700">
        <div class="mb-8 flex justify-center">
          <img class="w-1/2" src="/images/stocks/approval_flow.png" alt="" />
        </div>
        <div class="card overflow-hidden">
          <div class="w-full overflow-x-auto">
            <table
              id="table_container"
              class="table-modern"
            >
              <thead>
                <tr>
                  <th></th>
                  <th>発注依頼日時</th>
                  <th>依頼者</th>
                  <th class="whitespace-nowrap text-center">承認</th>
                  <th class="whitespace-nowrap">依頼品</th>
                  <th class="whitespace-nowrap">画像</th>
                  <th>品名</th>
                  <th>品番</th>
                  <th>希望納期</th>
                  <th class="whitespace-nowrap">現在個数</th>
                  <th class="whitespace-nowrap">発注点</th>
                  <th>単価</th>
                  <th class="whitespace-nowrap">発注数量</th>
                  <th class="whitespace-nowrap">発注単位</th>
                  <th>金額</th>
                  <th>消化予定日</th>
                  <th class="whitespace-nowrap">発注者</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="order_request in order_requests"
                  :key="order_request.id"
                  class="transition duration-300"
                >
                  <td>
                    <button
                      @click="showOrderRequestDetail(order_request.id)"
                      class="btn-secondary text-base py-3 px-4 whitespace-nowrap"
                    >
                      詳細確認
                    </button>
                  </td>
                  <td class="text-sm">
                    {{
                      new Date(order_request.created_at).toLocaleString(
                        "ja-JP",
                        {
                          year: "numeric",
                          month: "2-digit",
                          day: "2-digit",
                          hour: "2-digit",
                          minute: "2-digit",
                          hour12: false,
                        }
                      )
                    }}
                  </td>

                  <td class="text-slate-800">
                    {{ order_request.request_user_name }}
                  </td>
                  <td>
                    <div class="flex items-center justify-center">
                      <span
                        v-if="order_request.receive_flg"
                        class="badge-success"
                      >
                        納品済
                      </span>
                      <span
                        v-else-if="
                          order_request.initial_order_id &&
                          order_request.order_complete_flg
                        "
                        class="badge-primary"
                      >
                        発注済
                      </span>
                      <span
                        v-else-if="
                          order_request.initial_order_id &&
                          !order_request.order_complete_flg
                        "
                        class="badge-warning"
                      >
                        未発注
                      </span>
                      <span
                        v-else-if="order_request.accept_flg === 0"
                        class="bg-indigo-100 text-indigo-700 px-2.5 py-1 rounded-full text-xs font-medium"
                      >
                        依頼済
                      </span>
                      <span
                        class="badge-warning"
                        v-else-if="order_request.accept_flg === 1 || order_request.accept_flg === 6"
                        >承認待ち</span
                      >
                      <span
                        v-else-if="order_request.accept_flg === 2"
                        class="badge-success"
                      >
                        承認済
                      </span>
                      <span
                        class="badge-danger"
                        v-else-if="order_request.accept_flg === 3"
                        >却下</span
                      >
                      <span
                        class="bg-slate-200 text-slate-700 px-2.5 py-1 rounded-full text-xs font-medium"
                        v-else-if="order_request.accept_flg === 4"
                        >却下再依頼待ち</span
                      >
                      <span
                        v-else-if="order_request.accept_flg === 5"
                        class="bg-indigo-100 text-indigo-700 px-2.5 py-1 rounded-full text-xs font-medium"
                      >
                        確認中
                      </span>
                    </div>
                  </td>

                  <td>
                    <span
                      v-if="order_request.new_stock_flg"
                      class="badge-primary"
                      >新規品</span
                    >
                    <span
                      v-else
                      class="badge-warning"
                      >既存品
                    </span>
                  </td>

                  <td class="img_container">
                    <img
                      :src="
                        order_request.img_path &&
                        order_request.img_path.includes('storage')
                          ? 'https://akioka.cloud/' + order_request.img_path
                          : order_request.img_path
                      "
                      alt=""
                    />
                  </td>
                  <td class="name text-slate-800">
                    {{
                      order_request.stock_id
                        ? order_request.name
                        : order_request.order_request_name
                    }}
                  </td>
                  <td class="s_name text-slate-800">
                    {{
                      order_request.s_name
                        ? order_request.s_name
                        : order_request.order_request_s_name
                    }}
                  </td>
                  <td>
                    {{
                      new Date(
                        order_request.desire_delivery_date
                      ).toLocaleDateString("ja-JP")
                    }}
                  </td>
                  <td>
                    {{ order_request.now_quantity }}
                  </td>
                  <td>
                    {{ order_request.reorder_point }}
                  </td>
                  <td>
                    {{ order_request.price }}
                  </td>
                  <td>
                    {{ order_request.quantity }}
                  </td>
                  <td class="w-32">
                    {{ order_request.unit }}
                  </td>

                  <td>
                    {{ order_request.calc_price }}
                  </td>

                  <td>
                    {{
                      new Date(order_request.digest_date).toLocaleDateString(
                        "ja-JP"
                      )
                    }}
                  </td>

                  <td>
                    {{ order_request.order_user_name }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- モーダルダイアログ -->
      <div
        v-if="showModal"
        class="modal-overlay"
        @click="closeModal"
      >
        <div
          class="modal-content relative top-5 mx-auto w-11/12 max-w-7xl"
          @click.stop
        >
          <!-- モーダルヘッダー -->
          <div class="flex items-center justify-between pb-3 border-b border-slate-200">
            <h3 class="section-title">
              発注依頼詳細情報
            </h3>
            <button
              @click="closeModal"
              class="btn-icon text-slate-400 hover:text-slate-600"
            >
              <svg
                class="w-6 h-6"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M6 18L18 6M6 6l12 12"
                ></path>
              </svg>
            </button>
          </div>

          <!-- モーダルコンテンツ -->
          <div
            class="mt-4 overflow-y-auto"
            style="max-height: 80vh"
          >
            <!-- ローディング表示 -->
            <div
              v-if="modalLoading"
              class="flex flex-col justify-center items-center py-16"
            >
              <div
                class="animate-spin rounded-full h-12 w-12 border-4 border-indigo-200 border-t-indigo-600 mb-4"
              ></div>
              <span class="text-slate-600">読み込み中...</span>
            </div>

            <!-- エラー表示 -->
            <div
              v-else-if="modalError"
              class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-xl mb-4"
            >
              <strong class="font-bold">エラー:</strong>
              <span class="block sm:inline">{{ modalError }}</span>
            </div>

            <!-- 詳細情報表示 -->
            <div v-else-if="selectedOrderRequest.id" class="space-y-6">
              <!-- 基本情報セクション -->
              <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- 左側 -->
                <div>
                  <h4
                    class="section-subtitle border-b border-slate-200 pb-2 mb-4"
                  >
                    基本情報
                  </h4>

                  <div class="space-y-3">
                    <div class="flex">
                      <span class="font-medium text-slate-500 w-32"
                        >依頼ID:</span
                      >
                      <span class="text-slate-800">{{
                        selectedOrderRequest.id
                      }}</span>
                    </div>

                    <div class="flex">
                      <span class="font-medium text-slate-500 w-32"
                        >依頼日時:</span
                      >
                      <span class="text-slate-800">{{
                        formatDateTime(selectedOrderRequest.created_at)
                      }}</span>
                    </div>

                    <div class="flex">
                      <span class="font-medium text-slate-500 w-32"
                        >依頼者:</span
                      >
                      <span class="text-slate-800"
                        >{{ selectedOrderRequest.request_user_name }} ({{
                          selectedOrderRequest.process_name
                        }})</span
                      >
                    </div>

                    <div class="flex">
                      <span class="font-medium text-slate-500 w-32"
                        >発注者:</span
                      >
                      <span class="text-slate-800">{{
                        selectedOrderRequest.order_user_name || "未設定"
                      }}</span>
                    </div>

                    <div class="flex items-center">
                      <span class="font-medium text-slate-500 w-32"
                        >ステータス:</span
                      >
                      <span
                        :class="
                          getStatusText(
                            selectedOrderRequest.accept_flg,
                            selectedOrderRequest.receive_flg,
                            selectedOrderRequest.initial_order_id,
                            selectedOrderRequest.order_complete_flg
                          ).class
                        "
                        class="px-3 py-1 rounded-full text-sm font-medium"
                      >
                        {{
                          getStatusText(
                            selectedOrderRequest.accept_flg,
                            selectedOrderRequest.receive_flg,
                            selectedOrderRequest.initial_order_id,
                            selectedOrderRequest.order_complete_flg
                          ).text
                        }}
                      </span>
                    </div>
                  </div>
                </div>

                <!-- 右側 -->
                <div>
                  <h4
                    class="section-subtitle border-b border-slate-200 pb-2 mb-4"
                  >
                    商品情報
                  </h4>

                  <div class="space-y-3">
                    <div class="flex">
                      <span class="font-medium text-slate-500 w-32"
                        >依頼品:</span
                      >
                      <span
                        :class="
                          selectedOrderRequest.new_stock_flg
                            ? 'badge-primary'
                            : 'badge-warning'
                        "
                      >
                        {{
                          selectedOrderRequest.new_stock_flg
                            ? "新規品"
                            : "既存品"
                        }}
                      </span>
                    </div>

                    <div class="flex">
                      <span class="font-medium text-slate-500 w-32">品名:</span>
                      <span class="text-slate-800">{{
                        selectedOrderRequest.stock_id
                          ? selectedOrderRequest.name
                          : selectedOrderRequest.order_request_name
                      }}</span>
                    </div>

                    <div class="flex">
                      <span class="font-medium text-slate-500 w-32">品番:</span>
                      <span class="text-slate-800">{{
                        selectedOrderRequest.s_name ||
                        selectedOrderRequest.order_request_s_name ||
                        "未設定"
                      }}</span>
                    </div>

                    <div class="flex">
                      <span class="font-medium text-slate-500 w-32"
                        >仕入先:</span
                      >
                      <span class="text-slate-800">{{
                        selectedOrderRequest.supplier_name || "未設定"
                      }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- 商品画像 -->
              <div v-if="selectedOrderRequest.img_path" class="mb-6">
                <h4
                  class="section-subtitle border-b border-slate-200 pb-2 mb-4"
                >
                  商品画像
                </h4>
                <div class="flex justify-center">
                  <img
                    :src="
                      selectedOrderRequest.img_path &&
                      selectedOrderRequest.img_path.includes('storage')
                        ? 'https://akioka.cloud/' +
                          selectedOrderRequest.img_path
                        : selectedOrderRequest.img_path
                    "
                    alt="商品画像"
                    class="max-w-md h-auto border border-slate-200 rounded-2xl shadow-card"
                  />
                </div>
              </div>

              <!-- 数量・価格情報 -->
              <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- 左側 -->
                <div>
                  <h4
                    class="section-subtitle border-b border-slate-200 pb-2 mb-4"
                  >
                    数量情報
                  </h4>

                  <div class="space-y-3">
                    <div class="flex">
                      <span class="font-medium text-slate-500 w-32"
                        >現在個数:</span
                      >
                      <span class="text-slate-800">{{
                        selectedOrderRequest.now_quantity || "0"
                      }}</span>
                    </div>

                    <div class="flex">
                      <span class="font-medium text-slate-500 w-32"
                        >発注点:</span
                      >
                      <span class="text-slate-800">{{
                        selectedOrderRequest.reorder_point || "未設定"
                      }}</span>
                    </div>

                    <div class="flex">
                      <span class="font-medium text-slate-500 w-32"
                        >発注数量:</span
                      >
                      <span class="text-slate-800">{{
                        selectedOrderRequest.quantity
                      }}</span>
                    </div>

                    <div class="flex">
                      <span class="font-medium text-slate-500 w-32"
                        >発注単位:</span
                      >
                      <span class="text-slate-800">{{
                        selectedOrderRequest.unit || "未設定"
                      }}</span>
                    </div>
                  </div>
                </div>

                <!-- 右側 -->
                <div>
                  <h4
                    class="section-subtitle border-b border-slate-200 pb-2 mb-4"
                  >
                    価格情報
                  </h4>

                  <div class="space-y-3">
                    <div class="flex">
                      <span class="font-medium text-slate-500 w-32">単価:</span>
                      <span class="text-slate-800"
                        >¥{{
                          selectedOrderRequest.price
                            ? Number(
                                selectedOrderRequest.price
                              ).toLocaleString()
                            : "未設定"
                        }}</span
                      >
                    </div>

                    <div class="flex">
                      <span class="font-medium text-slate-500 w-32">送料:</span>
                      <span class="text-slate-800"
                        >¥{{
                          selectedOrderRequest.postage
                            ? Number(
                                selectedOrderRequest.postage
                              ).toLocaleString()
                            : "0"
                        }}</span
                      >
                    </div>

                    <div class="flex">
                      <span class="font-bold text-slate-700 w-32 text-lg"
                        >合計金額:</span
                      >
                      <span class="text-slate-800 text-lg font-bold"
                        >¥{{
                          selectedOrderRequest.calc_price
                            ? Number(
                                selectedOrderRequest.calc_price
                              ).toLocaleString()
                            : "未設定"
                        }}</span
                      >
                    </div>
                  </div>
                </div>
              </div>

              <!-- 日程情報 -->
              <div>
                <h4
                  class="section-subtitle border-b border-slate-200 pb-2 mb-4"
                >
                  日程情報
                </h4>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                  <div class="flex">
                    <span class="font-medium text-slate-500 w-32"
                      >希望納期:</span
                    >
                    <span class="text-slate-800">{{
                      formatDate(selectedOrderRequest.desire_delivery_date)
                    }}</span>
                  </div>

                  <div class="flex">
                    <span class="font-medium text-slate-500 w-32"
                      >消化予定日:</span
                    >
                    <span class="text-slate-800">{{
                      formatDate(selectedOrderRequest.digest_date)
                    }}</span>
                  </div>
                </div>
              </div>

              <!-- 備考・説明 -->
              <div
                v-if="
                  selectedOrderRequest.description ||
                  selectedOrderRequest.sub_description
                "
              >
                <h4
                  class="section-subtitle border-b border-slate-200 pb-2 mb-4"
                >
                  備考・説明
                </h4>

                <div class="space-y-4">
                  <div v-if="selectedOrderRequest.description">
                    <span class="font-medium text-slate-500 block mb-2"
                      >詳細説明:</span
                    >
                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                      <p class="text-slate-800 whitespace-pre-wrap">
                        {{ selectedOrderRequest.description }}
                      </p>
                    </div>
                  </div>

                  <div v-if="selectedOrderRequest.sub_description">
                    <span class="font-medium text-slate-500 block mb-2"
                      >補足説明:</span
                    >
                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                      <p class="text-slate-800 whitespace-pre-wrap">
                        {{ selectedOrderRequest.sub_description }}
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- 添付ファイル -->
              <div v-if="selectedOrderRequest.file_path">
                <h4
                  class="section-subtitle border-b border-slate-200 pb-2 mb-4"
                >
                  添付ファイル
                </h4>

                <div class="space-y-4">
                  <div class="flex items-center justify-between mb-2">
                    <span class="font-medium text-slate-500"
                      >ファイルプレビュー:</span
                    >
                    <a
                      :href="selectedOrderRequest.file_path"
                      target="_blank"
                      class="text-indigo-600 hover:text-indigo-800 underline inline-flex items-center text-sm"
                    >
                      <i class="fas fa-external-link-alt mr-1"></i>
                      新しいタブで開く
                    </a>
                  </div>

                  <div
                    class="border border-slate-200 rounded-2xl overflow-hidden bg-white"
                  >
                    <iframe
                      :src="getFilePreviewUrl(selectedOrderRequest.file_path)"
                      class="w-full h-64"
                      frameborder="0"
                      title="添付ファイル"
                      @error="handleIframeError"
                    >
                      <p class="p-4 text-slate-500">
                        このブラウザではファイルプレビューがサポートされていません。
                        <a
                          :href="selectedOrderRequest.file_path"
                          target="_blank"
                          class="text-indigo-600 underline"
                        >
                          こちらをクリックしてファイルを開いてください。
                        </a>
                      </p>
                    </iframe>
                  </div>
                </div>
              </div>

              <!-- 承認状況 -->
              <div
                v-if="
                  selectedOrderRequest.order_request_approvals &&
                  selectedOrderRequest.order_request_approvals.length > 0
                "
              >
                <h4
                  class="section-subtitle border-b border-slate-200 pb-2 mb-4"
                >
                  承認状況
                </h4>

                <div class="space-y-4">
                  <div
                    v-for="approval in selectedOrderRequest.order_request_approvals"
                    :key="approval.user_id"
                    class="bg-slate-50 p-4 rounded-xl border border-slate-100"
                  >
                    <div class="flex items-center justify-between mb-2">
                      <div class="flex items-center space-x-3">
                        <span class="font-medium text-slate-800">{{
                          approval.name
                        }}</span>
                        <span
                          :class="{
                            'badge-success': approval.status === 1,
                            'badge-danger': approval.status === 2,
                            'badge-warning': approval.status === 0,
                            'bg-slate-400 text-white px-2.5 py-1 rounded-full text-xs font-medium': approval.status === null,
                          }"
                        >
                          {{
                            approval.status === 1
                              ? "承認"
                              : approval.status === 2
                              ? "却下"
                              : approval.status === 0
                              ? "承認待ち"
                              : "未処理"
                          }}
                        </span>
                        <span
                          v-if="approval.final_flg"
                          class="bg-indigo-500 text-white px-2.5 py-1 rounded-full text-xs font-medium"
                        >
                          最終承認者
                        </span>
                      </div>
                      <div
                        v-if="approval.updated_at"
                        class="text-sm text-slate-500"
                      >
                        {{ formatDateTime(approval.updated_at) }}
                      </div>
                    </div>

                    <div v-if="approval.comment" class="mt-2">
                      <span class="font-medium text-slate-500 block mb-1"
                        >コメント:</span
                      >
                      <p
                        v-html="approval.comment.replace(/\n/g, '<br>')"
                        class="text-slate-800 text-sm bg-white p-2 rounded-lg border border-slate-100"
                      ></p>
                    </div>
                  </div>
                </div>
              </div>
              <div v-if="selectedOrderRequest.accept_flg === 3">
                <!-- 稟議書情報 -->
                <div v-if="selectedOrderRequest.document_id">
                  <h4
                    class="section-subtitle border-b border-slate-200 pb-2 mb-4"
                  >
                    稟議書情報
                  </h4>

                  <div class="bg-slate-50 p-4 rounded-xl border border-slate-100 space-y-4">
                    <div v-if="selectedOrderRequest.title">
                      <span class="font-medium text-slate-500 block mb-2"
                        >タイトル:</span
                      >
                      <p class="text-slate-800">
                        {{ selectedOrderRequest.title }}
                      </p>
                    </div>

                    <div v-if="selectedOrderRequest.evalution_date">
                      <span class="font-medium text-slate-500 block mb-2"
                        >評価日:</span
                      >
                      <p class="text-slate-800">
                        {{ formatDate(selectedOrderRequest.evalution_date) }}
                      </p>
                    </div>

                    <div v-if="selectedOrderRequest.content">
                      <span class="font-medium text-slate-500 block mb-2"
                        >内容:</span
                      >
                      <div class="bg-white p-3 rounded-lg border border-slate-100">
                        <p class="text-slate-800 whitespace-pre-wrap">
                          {{ selectedOrderRequest.content }}
                        </p>
                      </div>
                    </div>

                    <div v-if="selectedOrderRequest.main_reason">
                      <span class="font-medium text-slate-500 block mb-2"
                        >主な理由:</span
                      >
                      <p class="text-slate-800">
                        {{ selectedOrderRequest.main_reason }}
                      </p>
                    </div>

                    <div v-if="selectedOrderRequest.sub_reason">
                      <span class="font-medium text-slate-500 block mb-2"
                        >副次的理由:</span
                      >
                      <p class="text-slate-800">
                        {{ selectedOrderRequest.sub_reason }}
                      </p>
                    </div>

                    <!-- 稟議書画像 -->
                    <div
                      v-if="
                        selectedOrderRequest.document_images &&
                        selectedOrderRequest.document_images.length > 0
                      "
                    >
                      <span class="font-medium text-slate-500 block mb-2"
                        >稟議書画像:</span
                      >
                      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div
                          v-for="(
                            image, index
                          ) in selectedOrderRequest.document_images"
                          :key="index"
                          class="border border-slate-200 rounded-2xl overflow-hidden"
                        >
                          <img
                            :src="image"
                            :alt="`稟議書画像 ${index + 1}`"
                            class="w-full h-auto cursor-pointer hover:opacity-80 transition-opacity"
                            @click="window.open(image, '_blank')"
                          />
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- デバイスメッセージ -->
                <div v-if="selectedOrderRequest.message">
                  <h4
                    class="section-subtitle border-b border-slate-200 pb-2 mb-4"
                  >
                    デバイスメッセージ
                  </h4>

                  <div class="space-y-4">
                    <div>
                      <span class="font-medium text-slate-500 block mb-2"
                        >メッセージ:</span
                      >
                      <div
                        class="bg-indigo-50 p-4 rounded-xl border border-indigo-200"
                      >
                        <p class="text-slate-800">
                          {{ selectedOrderRequest.message }}
                        </p>
                      </div>
                    </div>

                    <div v-if="selectedOrderRequest.answer">
                      <span class="font-medium text-slate-500 block mb-2"
                        >回答:</span
                      >
                      <div
                        class="bg-emerald-50 p-4 rounded-xl border border-emerald-200"
                      >
                        <p class="text-slate-800">
                          {{ selectedOrderRequest.answer }}
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <Link
              v-if="selectedOrderRequest.accept_flg === 3"
              class="inline-block text-center w-full py-6 btn-danger text-xl mt-12 mb-8"
              :href="route('order_request.reorder')"
              method="post"
              :data="{ order_request_id: selectedOrderRequest.id }"
            >
              再依頼
            </Link>
          </div>

          <!-- モーダルフッター -->
          <div class="flex justify-end pt-4 border-t border-slate-200">
            <button
              @click="closeModal"
              class="btn-secondary"
            >
              閉じる
            </button>
          </div>
        </div>
      </div>
    </template>
  </StockLayout>
</template>
<style lang="scss" scoped>
// #table_container {
//   width: 180vw;
//   overflow-x: scroll;
// }

table {
  &#table_container {
    width: 130vw;
  }

  td {
    white-space: nowrap;

    &.img_container {
      width: 2vw;
      padding: 0;

      img {
        width: 100%;
        height: auto;
        width: 80px;
        object-fit: contain;
      }
    }
    &.name {
      max-width: 300px;
      overflow-x: auto;
    }

    &.s_name {
      max-width: 220px;
      overflow-x: auto;
    }
  }
}
</style>
