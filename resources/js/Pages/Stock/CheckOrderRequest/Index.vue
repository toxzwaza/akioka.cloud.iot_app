<script setup>
import { onMounted, reactive, ref } from "vue";
import { Link } from "@inertiajs/vue3";
import axios from "axios";
import StockLayout from "@/Layouts/StockLayout.vue";

const props = defineProps({
  processes: Array,
  users: Array,
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

const getOrderRequest = (page = 1, reset_flg) => {
  if (reset_flg) {
    form.process_id = 0;
    form.user_id = 0;
    form.name = null;
    form.s_name = null;
  }

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
      console.log(res.data);
      order_requests.value = res.data.order_requests.data;
      pagination.value = {
        current_page: res.data.order_requests.current_page,
        last_page: res.data.order_requests.last_page,
        per_page: res.data.order_requests.per_page,
        total: res.data.order_requests.total,
        from: res.data.order_requests.from,
        to: res.data.order_requests.to,
      };
      currentPage.value = page;
    })
    .catch((error) => {
      console.log(error);
    });
};

const changePage = (page) => {
  getOrderRequest(page);
};

// モーダル関連のメソッド（Show.vueから移植）
const getStatusText = (acceptFlg, receiveFlg, initialOrderId, orderCompleteFlg) => {
  if (receiveFlg) return { text: "納品済", class: "bg-green-900 text-white" };
  if (initialOrderId && orderCompleteFlg) return { text: "発注済", class: "bg-blue-700 text-white" };
  if (initialOrderId && !orderCompleteFlg) return { text: "未発注", class: "bg-yellow-600 text-white" };
  if (acceptFlg === 0) return { text: "依頼済", class: "bg-blue-500 text-white" };
  if (acceptFlg === 1) return { text: "承認待ち", class: "bg-orange-500 text-white" };
  if (acceptFlg === 2) return { text: "承認済", class: "bg-green-500 text-white" };
  if (acceptFlg === 3) return { text: "却下", class: "bg-red-500 text-white" };
  if (acceptFlg === 4) return { text: "却下再依頼待ち", class: "bg-gray-500 text-white" };
  if (acceptFlg === 5) return { text: "確認中", class: "bg-purple-500 text-white" };
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
  if (!filePath) return '';
  
  // ファイル拡張子を取得
  const extension = filePath.split('.').pop().toLowerCase();
  
  // PDFファイルの場合はGoogle Docs Viewerを使用
  if (extension === 'pdf') {
    return `https://docs.google.com/viewer?url=${encodeURIComponent(filePath)}&embedded=true`;
  }
  
  // その他のファイルは直接表示
  return filePath;
};

const handleIframeError = (event) => {
  console.warn("iframe読み込みエラー:", event);
  const iframe = event.target;
  const fallback = document.getElementById('iframe-fallback');
  if (iframe && fallback) {
    iframe.style.display = 'none';
    fallback.style.display = 'block';
  }
};

const showOrderRequestDetail = async (orderRequestId) => {
  try {
    modalLoading.value = true;
    modalError.value = "";
    showModal.value = true;
    
    const response = await axios.get(`/check_order_request/detail/${orderRequestId}`);
    
    if (response.data.status) {
      selectedOrderRequest.value = response.data.order_request;
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
  getOrderRequest();

  process_users.value = props.users;
});
</script>
<template>
  <StockLayout :title="'在庫管理システム'">
    <template #content>
      <!-- ページネーション情報（右上） -->
      <div class="flex justify-between items-center mb-4">
        <div></div>
        <div class="flex items-center space-x-4">
          <div class="text-sm text-gray-600">
            全 {{ pagination.total || 0 }} 件中 {{ pagination.from || 0 }}-{{
              pagination.to || 0
            }}
            件を表示
          </div>
          <div class="flex items-center space-x-2">
            <button
              @click="changePage(currentPage - 1)"
              :disabled="currentPage <= 1"
              class="px-3 py-1 text-sm bg-gray-200 text-gray-700 rounded hover:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              前へ
            </button>
            <span class="text-sm text-gray-600">
              {{ currentPage }} / {{ pagination.last_page || 1 }}
            </span>
            <button
              @click="changePage(currentPage + 1)"
              :disabled="currentPage >= (pagination.last_page || 1)"
              class="px-3 py-1 text-sm bg-gray-200 text-gray-700 rounded hover:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              次へ
            </button>
          </div>
        </div>
      </div>

      <!-- 検索ボタン -->
      <div class="flex justify-end mb-4">
        <button
          @click="toggleSearch"
          class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded flex items-center"
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
        <div class="flex flex-wrap -mx-3 mb-4 items-end pt-2">
          <div class="w-1/2 px-3">
            <label for="" class="text-gray-500 font-bold">依頼者</label>
            <select
              name=""
              id=""
              class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-4 px-6 mb-3 leading-tight focus:outline-none focus:bg-white focus:border-gray-500 mt-2"
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
          <div class="w-1/2 px-3">
            <label for="" class="text-red-500 font-bold"></label>
            <select
              name=""
              id=""
              v-model="form.user_id"
              class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-4 px-6 mb-3 leading-tight focus:outline-none focus:bg-white focus:border-gray-500 mt-2"
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
        <div class="flex flex-wrap -mx-3 mb-4 items-end">
          <div class="w-1/2 px-3">
            <label for="" class="text-gray-500 font-bold">品名・品番</label>
            <input
              type="text"
              name=""
              id=""
              class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-4 px-6 mb-3 leading-tight focus:outline-none focus:bg-white focus:border-gray-500 mt-2"
              placeholder="品名"
              v-model="form.name"
            />
          </div>
          <div class="w-1/2 px-3">
            <input
              type="text"
              name=""
              id=""
              class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-4 px-6 mb-3 leading-tight focus:outline-none focus:bg-white focus:border-gray-500 mt-2"
              placeholder="品番"
              v-model="form.s_name"
            />
          </div>
        </div>

        <div class="flex items-center justify-center mt-8">
          <button
            @click="getOrderRequest(null, 1)"
            class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded mr-4"
          >
            リセット
          </button>
          <button
            @click="getOrderRequest(null, 0)"
            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded ml-4"
          >
            検索
          </button>
        </div>
      </div>

      <section id="table_container" class="text-gray-600 body-font">
        <div class="mb-8 flex justify-center">
          <img class="w-1/2" src="/images/stocks/approval_flow.png" alt="" />
        </div>
        <div class="mx-auto">
          <div class="w-full mx-auto overflow-auto">
            <table
              id="table_container"
              class="table-auto w-full text-left whitespace-no-wrap"
            >
              <thead>
                <tr>
                  <th
                    class="px-4 py-4 title-font tracking-wider font-medium text-white text-sm bg-gray-600"
                  ></th>
                  <th
                    class="px-4 py-4 title-font tracking-wider font-medium text-white text-sm bg-gray-600"
                  >
                    発注依頼日時
                  </th>
                  <th
                    class="px-4 py-4 title-font tracking-wider font-medium text-white text-sm bg-gray-600"
                  >
                    依頼者
                  </th>
                  <th
                    class="px-4 py-4 title-font tracking-wider font-medium text-white text-sm bg-gray-600 whitespace-nowrap text-centerf"
                  >
                    承認
                  </th>
                  <th
                    class="px-4 py-4 title-font tracking-wider font-medium text-white text-sm bg-gray-600 whitespace-nowrap"
                  >
                    依頼品
                  </th>

                  <th
                    class="px-4 py-4 title-font tracking-wider font-medium text-white text-sm bg-gray-600 whitespace-nowrap"
                  >
                    画像
                  </th>
                  <th
                    class="px-4 py-4 title-font tracking-wider font-medium text-white text-sm bg-gray-600"
                  >
                    品名
                  </th>
                  <th
                    class="px-4 py-4 title-font tracking-wider font-medium text-white text-sm bg-gray-600"
                  >
                    品番
                  </th>

                  <th
                    class="px-4 py-4 title-font tracking-wider font-medium text-white text-sm bg-gray-600"
                  >
                    希望納期
                  </th>
                  <th
                    class="px-4 py-4 title-font tracking-wider font-medium text-white text-sm bg-gray-600 whitespace-nowrap"
                  >
                    現在個数
                  </th>
                  <th
                    class="px-4 py-4 title-font tracking-wider font-medium text-white text-sm bg-gray-600 whitespace-nowrap"
                  >
                    発注点
                  </th>
                  <th
                    class="px-4 py-4 title-font tracking-wider font-medium text-white text-sm bg-gray-600"
                  >
                    単価
                  </th>
                  <th
                    class="px-4 py-4 title-font tracking-wider font-medium text-white text-sm bg-gray-600 whitespace-nowrap"
                  >
                    発注数量
                  </th>
                  <th
                    class="px-4 py-4 title-font tracking-wider font-medium text-white text-sm bg-gray-600 whitespace-nowrap"
                  >
                    発注単位
                  </th>

                  <th
                    class="px-4 py-4 title-font tracking-wider font-medium text-white text-sm bg-gray-600"
                  >
                    金額
                  </th>

                  <th
                    class="px-4 py-4 title-font tracking-wider font-medium text-white text-sm bg-gray-600"
                  >
                    消化予定日
                  </th>

                  <th
                    class="px-4 py-4 title-font tracking-wider font-medium text-white text-sm bg-gray-600 whitespace-nowrap"
                  >
                    発注者
                  </th>
                  <!-- <th
                    class="px-4 py-4 title-font tracking-wider font-medium text-white text-sm bg-gray-600"
                  >
                    注文書
                  </th> -->
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="order_request in order_requests"
                  :key="order_request.id"
                  :class="{
                    'transition duration-300 border': true,
                  }"
                >
                  <td class="px-4 py-6 ">
                    <button
                      @click="showOrderRequestDetail(order_request.id)"
                      class="bg-gray-700 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded"
                    >
                      詳細確認
                    </button>
                  </td>
                  <td class="px-4 py-6 text-lg">
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

                  <td
                    :class="{
                      'px-4 py-6 text-lg text-gray-900': true,
                    }"
                  >
                    {{ order_request.request_user_name }}
                  </td>
                  <td
                    :class="{
                      'px-4 py-6 text-lg': true,
                    }"
                  >
                    <div class="flex items-center justify-center">
                      <span
                        v-if="order_request.receive_flg"
                        class="text-sm bg-green-900 hover:bg-green-700 text-white py-2 px-4 rounded-full"
                      >
                        納品済
                      </span>
                      <span
                        v-else-if="
                          order_request.initial_order_id &&
                          order_request.order_complete_flg
                        "
                        class="text-sm bg-blue-700 hover:bg-blue-500 text-white py-2 px-4 rounded-full"
                      >
                        発注済
                      </span>
                      <span
                        v-else-if="
                          order_request.initial_order_id &&
                          !order_request.order_complete_flg
                        "
                        class="text-sm bg-yellow-600 hover:bg-yellow-500 text-white py-2 px-4 rounded-full"
                      >
                        未発注
                      </span>
                      <span
                        v-else-if="order_request.accept_flg === 0"
                        class="text-sm bg-blue-500 hover:bg-blue-300 text-white py-2 px-4 rounded-full"
                      >
                        依頼済
                      </span>
                      <span
                        class="text-sm bg-orange-500 hover:bg-orange-300 text-white py-2 px-4 rounded-full"
                        v-else-if="order_request.accept_flg === 1"
                        >承認待ち</span
                      >
                      <span
                        v-else-if="order_request.accept_flg === 2"
                        class="text-sm bg-green-500 hover:bg-green-300 text-white py-2 px-4 rounded-full"
                      >
                        承認済
                      </span>
                      <span
                        class="text-sm bg-red-500 hover:bg-red-300 text-white py-2 px-4 rounded-full"
                        v-else-if="order_request.accept_flg === 3"
                        >却下</span
                      >
                      <span
                        class="text-sm bg-gray-500 hover:bg-gray-300 text-white py-2 px-4 rounded-full"
                        v-else-if="order_request.accept_flg === 4"
                        >却下再依頼待ち</span
                      >
                      <span
                        v-else-if="order_request.accept_flg === 5"
                        class="text-sm bg-purple-500 hover:bg-purple-300 text-white py-2 px-4 rounded-full"
                      >
                        確認中
                      </span>
                    </div>
                  </td>

                  <td class="px-4 py-6 text-lg">
                    <span
                      v-if="order_request.new_stock_flg"
                      class="bg-blue-100 text-blue-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded-sm dark:bg-blue-900 dark:text-blue-300"
                      >新規品</span
                    >
                    <span
                      v-else
                      class="bg-orange-100 text-orange-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded-sm dark:bg-orange-900 dark:text-orange-300"
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
                  <td class="name px-4 py-6 text-gray-900">
                    {{
                      order_request.stock_id
                        ? order_request.name
                        : order_request.order_request_name
                    }}
                  </td>
                  <td class="s_name px-4 py-6 text-gray-900">
                    {{
                      order_request.s_name
                        ? order_request.s_name
                        : order_request.order_request_s_name
                    }}
                  </td>
                  <td class="px-4 py-6 text-lg">
                    {{
                      new Date(
                        order_request.desire_delivery_date
                      ).toLocaleDateString("ja-JP")
                    }}
                  </td>
                  <td class="px-4 py-6 text-lg">
                    {{ order_request.now_quantity }}
                  </td>
                  <td class="px-4 py-6 text-lg">
                    {{ order_request.reorder_point }}
                  </td>
                  <td class="px-4 py-6 text-lg">
                    {{ order_request.price }}
                  </td>
                  <td class="px-4 py-6 text-lg">
                    {{ order_request.quantity }}
                  </td>
                  <td class="px-4 py-6 text-lg w-32">
                    {{ order_request.unit }}
                  </td>

                  <td class="px-4 py-6 text-lg">
                    {{ order_request.calc_price }}
                  </td>

                  <td class="px-4 py-6 text-lg">
                    {{
                      new Date(order_request.digest_date).toLocaleDateString(
                        "ja-JP"
                      )
                    }}
                  </td>

                  <td
                    :class="{
                      'px-4 py-6 text-lg': true,
                    }"
                  >
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
        class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
        @click="closeModal"
      >
        <div 
          class="relative top-5 mx-auto p-6 border w-11/12 max-w-7xl shadow-lg rounded-md bg-white"
          @click.stop
        >
          <!-- モーダルヘッダー -->
          <div class="flex items-center justify-between pb-3 border-b">
            <h3 class="text-xl font-semibold text-gray-900">発注依頼詳細情報</h3>
            <button 
              @click="closeModal"
              class="text-gray-400 hover:text-gray-600"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>

          <!-- モーダルコンテンツ -->
          <div class="mt-4 max-h-screen-80 overflow-y-auto" style="max-height: 80vh;">
            <!-- ローディング表示 -->
            <div v-if="modalLoading" class="flex justify-center items-center py-12">
              <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
              <span class="ml-3 text-gray-600">読み込み中...</span>
            </div>

            <!-- エラー表示 -->
            <div v-else-if="modalError" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
              <strong class="font-bold">エラー:</strong>
              <span class="block sm:inline">{{ modalError }}</span>
            </div>

            <!-- 詳細情報表示 -->
            <div v-else-if="selectedOrderRequest.id" class="space-y-6">
              <!-- 基本情報セクション -->
              <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- 左側 -->
                <div>
                  <h4 class="text-lg font-semibold mb-4 text-gray-800 border-b border-gray-200 pb-2">基本情報</h4>
                  
                  <div class="space-y-3">
                    <div class="flex">
                      <span class="font-medium text-gray-600 w-32">依頼ID:</span>
                      <span class="text-gray-900">{{ selectedOrderRequest.id }}</span>
                    </div>
                    
                    <div class="flex">
                      <span class="font-medium text-gray-600 w-32">依頼日時:</span>
                      <span class="text-gray-900">{{ formatDateTime(selectedOrderRequest.created_at) }}</span>
                    </div>
                    
                    <div class="flex">
                      <span class="font-medium text-gray-600 w-32">依頼者:</span>
                      <span class="text-gray-900">{{ selectedOrderRequest.request_user_name }} ({{ selectedOrderRequest.process_name }})</span>
                    </div>
                    
                    <div class="flex">
                      <span class="font-medium text-gray-600 w-32">発注者:</span>
                      <span class="text-gray-900">{{ selectedOrderRequest.order_user_name || '未設定' }}</span>
                    </div>
                    
                    <div class="flex items-center">
                      <span class="font-medium text-gray-600 w-32">ステータス:</span>
                      <span 
                        :class="getStatusText(selectedOrderRequest.accept_flg, selectedOrderRequest.receive_flg, selectedOrderRequest.initial_order_id, selectedOrderRequest.order_complete_flg).class"
                        class="px-3 py-1 rounded-full text-sm font-medium"
                      >
                        {{ getStatusText(selectedOrderRequest.accept_flg, selectedOrderRequest.receive_flg, selectedOrderRequest.initial_order_id, selectedOrderRequest.order_complete_flg).text }}
                      </span>
                    </div>
                  </div>
                </div>

                <!-- 右側 -->
                <div>
                  <h4 class="text-lg font-semibold mb-4 text-gray-800 border-b border-gray-200 pb-2">商品情報</h4>
                  
                  <div class="space-y-3">
                    <div class="flex">
                      <span class="font-medium text-gray-600 w-32">依頼品:</span>
                      <span 
                        :class="selectedOrderRequest.new_stock_flg ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800'"
                        class="px-2 py-1 rounded text-xs font-medium"
                      >
                        {{ selectedOrderRequest.new_stock_flg ? '新規品' : '既存品' }}
                      </span>
                    </div>
                    
                    <div class="flex">
                      <span class="font-medium text-gray-600 w-32">品名:</span>
                      <span class="text-gray-900">{{ selectedOrderRequest.stock_id ? selectedOrderRequest.name : selectedOrderRequest.order_request_name }}</span>
                    </div>
                    
                    <div class="flex">
                      <span class="font-medium text-gray-600 w-32">品番:</span>
                      <span class="text-gray-900">{{ selectedOrderRequest.s_name || selectedOrderRequest.order_request_s_name || '未設定' }}</span>
                    </div>
                    
                    <div class="flex">
                      <span class="font-medium text-gray-600 w-32">仕入先:</span>
                      <span class="text-gray-900">{{ selectedOrderRequest.supplier_name || '未設定' }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- 商品画像 -->
              <div v-if="selectedOrderRequest.img_path" class="mb-6">
                <h4 class="text-lg font-semibold mb-4 text-gray-800 border-b border-gray-200 pb-2">商品画像</h4>
                <div class="flex justify-center">
                  <img 
                    :src="selectedOrderRequest.img_path && selectedOrderRequest.img_path.includes('storage') 
                      ? 'https://akioka.cloud/' + selectedOrderRequest.img_path 
                      : selectedOrderRequest.img_path"
                    alt="商品画像"
                    class="max-w-md h-auto border border-gray-300 rounded-lg shadow-sm"
                  />
                </div>
              </div>

              <!-- 数量・価格情報 -->
              <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- 左側 -->
                <div>
                  <h4 class="text-lg font-semibold mb-4 text-gray-800 border-b border-gray-200 pb-2">数量情報</h4>
                  
                  <div class="space-y-3">
                    <div class="flex">
                      <span class="font-medium text-gray-600 w-32">現在個数:</span>
                      <span class="text-gray-900">{{ selectedOrderRequest.now_quantity || '0' }}</span>
                    </div>
                    
                    <div class="flex">
                      <span class="font-medium text-gray-600 w-32">発注点:</span>
                      <span class="text-gray-900">{{ selectedOrderRequest.reorder_point || '未設定' }}</span>
                    </div>
                    
                    <div class="flex">
                      <span class="font-medium text-gray-600 w-32">発注数量:</span>
                      <span class="text-gray-900">{{ selectedOrderRequest.quantity }}</span>
                    </div>
                    
                    <div class="flex">
                      <span class="font-medium text-gray-600 w-32">発注単位:</span>
                      <span class="text-gray-900">{{ selectedOrderRequest.unit || '未設定' }}</span>
                    </div>
                  </div>
                </div>

                <!-- 右側 -->
                <div>
                  <h4 class="text-lg font-semibold mb-4 text-gray-800 border-b border-gray-200 pb-2">価格情報</h4>
                  
                  <div class="space-y-3">
                    <div class="flex">
                      <span class="font-medium text-gray-600 w-32">単価:</span>
                      <span class="text-gray-900">¥{{ selectedOrderRequest.price ? Number(selectedOrderRequest.price).toLocaleString() : '未設定' }}</span>
                    </div>
                    
                    <div class="flex">
                      <span class="font-medium text-gray-600 w-32">送料:</span>
                      <span class="text-gray-900">¥{{ selectedOrderRequest.postage ? Number(selectedOrderRequest.postage).toLocaleString() : '0' }}</span>
                    </div>
                    
                    <div class="flex">
                      <span class="font-bold text-gray-600 w-32 text-lg">合計金額:</span>
                      <span class="text-gray-900 text-lg font-bold">¥{{ selectedOrderRequest.calc_price ? Number(selectedOrderRequest.calc_price).toLocaleString() : '未設定' }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- 日程情報 -->
              <div>
                <h4 class="text-lg font-semibold mb-4 text-gray-800 border-b border-gray-200 pb-2">日程情報</h4>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                  <div class="flex">
                    <span class="font-medium text-gray-600 w-32">希望納期:</span>
                    <span class="text-gray-900">{{ formatDate(selectedOrderRequest.desire_delivery_date) }}</span>
                  </div>
                  
                  <div class="flex">
                    <span class="font-medium text-gray-600 w-32">消化予定日:</span>
                    <span class="text-gray-900">{{ formatDate(selectedOrderRequest.digest_date) }}</span>
                  </div>
                </div>
              </div>

              <!-- 備考・説明 -->
              <div v-if="selectedOrderRequest.description || selectedOrderRequest.sub_description">
                <h4 class="text-lg font-semibold mb-4 text-gray-800 border-b border-gray-200 pb-2">備考・説明</h4>
                
                <div class="space-y-4">
                  <div v-if="selectedOrderRequest.description">
                    <span class="font-medium text-gray-600 block mb-2">詳細説明:</span>
                    <div class="bg-gray-50 p-4 rounded border">
                      <p class="text-gray-900 whitespace-pre-wrap">{{ selectedOrderRequest.description }}</p>
                    </div>
                  </div>
                  
                  <div v-if="selectedOrderRequest.sub_description">
                    <span class="font-medium text-gray-600 block mb-2">補足説明:</span>
                    <div class="bg-gray-50 p-4 rounded border">
                      <p class="text-gray-900 whitespace-pre-wrap">{{ selectedOrderRequest.sub_description }}</p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- 添付ファイル -->
              <div v-if="selectedOrderRequest.file_path">
                <h4 class="text-lg font-semibold mb-4 text-gray-800 border-b border-gray-200 pb-2">添付ファイル</h4>
                
                <div class="space-y-4">
                  <div class="flex items-center justify-between mb-2">
                    <span class="font-medium text-gray-600">ファイルプレビュー:</span>
                    <a 
                      :href="selectedOrderRequest.file_path" 
                      target="_blank"
                      class="text-blue-600 hover:text-blue-800 underline inline-flex items-center text-sm"
                    >
                      <i class="fas fa-external-link-alt mr-1"></i>
                      新しいタブで開く
                    </a>
                  </div>
                  
                  <div class="border border-gray-300 rounded-lg overflow-hidden bg-white">
                    <iframe 
                      :src="getFilePreviewUrl(selectedOrderRequest.file_path)"
                      class="w-full h-64"
                      frameborder="0"
                      title="添付ファイル"
                      @error="handleIframeError"
                    >
                      <p class="p-4 text-gray-500">
                        このブラウザではファイルプレビューがサポートされていません。
                        <a :href="selectedOrderRequest.file_path" target="_blank" class="text-blue-600 underline">
                          こちらをクリックしてファイルを開いてください。
                        </a>
                      </p>
                    </iframe>
                  </div>
                </div>
              </div>

              <!-- 承認状況 -->
              <div v-if="selectedOrderRequest.order_request_approvals && selectedOrderRequest.order_request_approvals.length > 0">
                <h4 class="text-lg font-semibold mb-4 text-gray-800 border-b border-gray-200 pb-2">承認状況</h4>
                
                <div class="space-y-4">
                  <div 
                    v-for="approval in selectedOrderRequest.order_request_approvals" 
                    :key="approval.user_id"
                    class="bg-gray-50 p-4 rounded border"
                  >
                    <div class="flex items-center justify-between mb-2">
                      <div class="flex items-center space-x-3">
                        <span class="font-medium text-gray-800">{{ approval.name }}</span>
                        <span 
                          :class="{
                            'bg-green-500 text-white': approval.status === 1,
                            'bg-red-500 text-white': approval.status === 2,
                            'bg-yellow-500 text-white': approval.status === 0,
                            'bg-gray-400 text-white': approval.status === null
                          }"
                          class="px-2 py-1 rounded text-xs font-medium"
                        >
                          {{ 
                            approval.status === 1 ? '承認' : 
                            approval.status === 2 ? '却下' : 
                            approval.status === 0 ? '承認待ち' : 
                            '未処理' 
                          }}
                        </span>
                        <span v-if="approval.final_flg" class="bg-purple-500 text-white px-2 py-1 rounded text-xs font-medium">
                          最終承認者
                        </span>
                      </div>
                      <div v-if="approval.updated_at" class="text-sm text-gray-500">
                        {{ formatDateTime(approval.updated_at) }}
                      </div>
                    </div>
                    
                    <div v-if="approval.comment" class="mt-2">
                      <span class="font-medium text-gray-600 block mb-1">コメント:</span>
                      <p class="text-gray-900 text-sm bg-white p-2 rounded border">{{ approval.comment }}</p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- 稟議書情報 -->
              <div v-if="selectedOrderRequest.document_id">
                <h4 class="text-lg font-semibold mb-4 text-gray-800 border-b border-gray-200 pb-2">稟議書情報</h4>
                
                <div class="bg-gray-50 p-4 rounded border space-y-4">
                  <div v-if="selectedOrderRequest.title">
                    <span class="font-medium text-gray-600 block mb-2">タイトル:</span>
                    <p class="text-gray-900">{{ selectedOrderRequest.title }}</p>
                  </div>
                  
                  <div v-if="selectedOrderRequest.evalution_date">
                    <span class="font-medium text-gray-600 block mb-2">評価日:</span>
                    <p class="text-gray-900">{{ formatDate(selectedOrderRequest.evalution_date) }}</p>
                  </div>
                  
                  <div v-if="selectedOrderRequest.content">
                    <span class="font-medium text-gray-600 block mb-2">内容:</span>
                    <div class="bg-white p-3 rounded border">
                      <p class="text-gray-900 whitespace-pre-wrap">{{ selectedOrderRequest.content }}</p>
                    </div>
                  </div>
                  
                  <div v-if="selectedOrderRequest.main_reason">
                    <span class="font-medium text-gray-600 block mb-2">主な理由:</span>
                    <p class="text-gray-900">{{ selectedOrderRequest.main_reason }}</p>
                  </div>
                  
                  <div v-if="selectedOrderRequest.sub_reason">
                    <span class="font-medium text-gray-600 block mb-2">副次的理由:</span>
                    <p class="text-gray-900">{{ selectedOrderRequest.sub_reason }}</p>
                  </div>
                  
                  <!-- 稟議書画像 -->
                  <div v-if="selectedOrderRequest.document_images && selectedOrderRequest.document_images.length > 0">
                    <span class="font-medium text-gray-600 block mb-2">稟議書画像:</span>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div 
                        v-for="(image, index) in selectedOrderRequest.document_images" 
                        :key="index"
                        class="border border-gray-300 rounded-lg overflow-hidden"
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
                <h4 class="text-lg font-semibold mb-4 text-gray-800 border-b border-gray-200 pb-2">デバイスメッセージ</h4>
                
                <div class="space-y-4">
                  <div>
                    <span class="font-medium text-gray-600 block mb-2">メッセージ:</span>
                    <div class="bg-blue-50 p-4 rounded border border-blue-200">
                      <p class="text-gray-900">{{ selectedOrderRequest.message }}</p>
                    </div>
                  </div>
                  
                  <div v-if="selectedOrderRequest.answer">
                    <span class="font-medium text-gray-600 block mb-2">回答:</span>
                    <div class="bg-green-50 p-4 rounded border border-green-200">
                      <p class="text-gray-900">{{ selectedOrderRequest.answer }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- モーダルフッター -->
          <div class="flex justify-end pt-4 border-t">
            <button 
              @click="closeModal"
              class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600"
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
