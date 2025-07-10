<script setup>
import { onMounted, reactive, ref } from "vue";
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
            {{ isSearchOpen ? '検索を閉じる' : '検索を開く' }}
          </button>
        </div>

        <!-- 検索コンテナ -->
        <div 
          id="search_container" 
          class="mb-12 transition-all duration-300 ease-in-out"
          :class="isSearchOpen ? 'opacity-100 max-h-screen' : 'opacity-0 max-h-0 overflow-hidden'"
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
        <div class=" mb-8 flex justify-center">
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
                  >
                    発注依頼日時
                  </th>
                  <th
                    class="px-4 py-4 title-font tracking-wider font-medium text-white text-sm bg-gray-600"
                  >
                    依頼者
                  </th>
                  <th
                    class="px-4 py-4 title-font tracking-wider font-medium text-white text-sm bg-gray-600 whitespace-nowrap"
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
