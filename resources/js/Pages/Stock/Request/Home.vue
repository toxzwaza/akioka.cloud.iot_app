<script setup>
import StockLayout from "@/Layouts/StockLayout.vue";
import { Link } from "@inertiajs/vue3";
import { reactive, ref, onMounted } from "vue";
import { getImgPath, changeDateFormat } from "@/Helper/method";
import MicroModal from "@/Components/MicroModal.vue";
import Admin from "@/Components/Stock/Request/Admin.vue";

const props = defineProps({
  processes: Array,
  stock_requests: Array,
  users: Array,
  stock_request_orders: Array,
});

const left_stock_requests = ref([]);
const right_stock_requests = ref([]);
const users = ref([]);

const modalStatus = ref(false);
const modalImageSrc = ref("");
const modalStockId = ref();

const orderData = ref(null);

const is_login = ref(false);

const handleCloseModal = () => {
  modalStatus.value = !modalStatus.value;
};

const handleProcessId = (process_id) => {
  console.log(process_id);
  users.value = props.users.filter((user) => user.process_id == process_id);
  checkAlreadyStockRequest();
};

const modalImage = (target, stock_id) => {
  if (stock_id) {
    modalStockId.value = stock_id;
  }
  modalStatus.value = true;
  modalImageSrc.value = target.src;
};

const form = reactive({
  process_id: 0,
  user_id: 0,
  pwd: "",
});

const loginAdmin = () => {
  if (form.pwd == "pwd") {
    is_login.value = true;
  } else {
    alert("パスワードが違います。");
  }
};

const updateQuantity = (stock_id, quantity) => {
  if (!orderData.value) {
    orderData.value = {};
  }
  orderData.value[stock_id] = quantity;
  console.log(orderData.value);
};

const orderStockRequest = () => {

  axios
    .post(route("stock.request.store"), {
      process_id: form.process_id,
      user_id: form.user_id,
      data: orderData.value,
    })
    .then((res) => {
      if (res.data.status) {
        if (confirm("物品依頼が完了しました。")) {
          window.location.reload();
        }
      }
    })
    .catch((error) => {
      console.log(error);
    });
};

const checkAlreadyStockRequest = () => {
  if (
    props.stock_request_orders.some(
      (order) => order.process_id === form.process_id
    )
  ) {
    alert("既に来週分の物品依頼が完了しています。");

    props.stock_requests = props.stock_request_orders
      .filter(
        (stock_request_order) =>
          stock_request_order.process_id == form.process_id
      )
      .map((stock_request_order) => {
        const stock_request = props.stock_requests.find(
          (stock_request) =>
            stock_request.stock_id == stock_request_order.stock_id
        );
        if (stock_request) {
          stock_request.quantity = stock_request_order.quantity;
          return stock_request;
        }
      })
      .filter((stock_request) => stock_request !== undefined);
  } else {
    props.stock_requests.forEach((stock_request) => {
      stock_request.quantity = '';
    });
  }
  sliceStockRequests(props.stock_requests);
};

// 依頼可能期間かチェック
const isWeekdayMonToWed = () => {
  const today = new Date();
  const day = today.getDay();
  return day >= 1 && day <= 3;
};

const sliceStockRequests = (stock_requests) => {
  left_stock_requests.value = stock_requests.slice(
    0,
    Math.floor(stock_requests.length / 2)
  );
  right_stock_requests.value = stock_requests.slice(
    Math.floor(stock_requests.length / 2)
  );

  console.log(left_stock_requests.value, right_stock_requests.value);
};

onMounted(() => {
  console.log(props.stock_requests);
  sliceStockRequests(props.stock_requests);

  users.value = props.users;
});
</script>
<template>
  <StockLayout :title="'在庫管理システム'">
    <template #content>
      <!-- 管理者ログイン -->
      <div class="flex justify-end mb-12">
        <form class="w-full max-w-sm">
          <div class="flex items-center border-b border-teal-500 py-2">
            <input
              class="appearance-none bg-transparent border-none w-full text-gray-700 mr-3 py-1 px-2 leading-tight focus:outline-none"
              type="text"
              placeholder="管理者パスワード"
              v-model="form.pwd"
            />
            <button
              v-if="!is_login"
              @click.prevent="loginAdmin"
              class="flex-shrink-0 bg-teal-500 hover:bg-teal-700 border-teal-500 hover:border-teal-700 text-sm border-4 text-white py-1 px-2 rounded"
              type="button"
            >
              ログイン
            </button>
            <button
              v-if="is_login"
              @click="
                is_login = false;
                form.pwd = '';
              "
              class="flex-shrink-0 border-transparent border-4 text-red-500 hover:text-red-800 text-sm py-1 px-2 rounded"
              type="button"
            >
              ログアウト
            </button>
          </div>
        </form>
      </div>

      <!-- 物品依頼者画面 -->
      <div v-if="!is_login">
        <div class="mb-16">
          <h2 class="text-4xl text-center font-bold text-gray-700">
            物品依頼期間は月曜～水曜の定時までとなっております。<br />
            依頼頂いた物品は翌週月曜日の朝礼後に備品倉庫前からお取りください。
          </h2>
        </div>
        <div v-if="isWeekdayMonToWed">
          <div>
            <label
              for="large"
              class="block mb-4 text-2xl text-red-500 dark:text-white font-bold"
              >作業場所を選択してください</label
            >
            <select
              id="large"
              class="font-bold block w-full px-4 py-6 text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 text-4xl text-center"
              v-model="form.process_id"
              @change="handleProcessId($event.target.value)"
            >
              <option value="0">未選択</option>
              <option
                v-for="process in processes"
                :value="process.id"
                :key="process.id"
              >
                {{ process.name }}
              </option>
            </select>
          </div>

          <div class="mt-8">
            <label
              for="large"
              class="block mb-4 text-2xl text-red-500 dark:text-white font-bold"
              >担当者を選択してください</label
            >
            <select
              id="large"
              class="font-bold block w-full px-4 py-6 text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 text-4xl text-center"
              v-model="form.user_id"
            >
              <option value="0">未選択</option>
              <option v-for="user in users" :value="user.id" :key="user.id">
                {{ user.name }}
              </option>
            </select>
          </div>
        </div>
        <div v-else>
          <h2 class="text-6xl text-center text-red-500 font-bold">
            物品依頼可能日ではありません。
          </h2>
        </div>

        <!-- 注文依頼用紙 -->
        <div v-if="form.process_id && form.user_id" class="mt-12">
          <div class="table-container">
            <div class="left_table_container">
              <table>
                <tbody>
                  <tr>
                    <th>画像</th>
                    <th>品名</th>
                    <th>数量</th>
                    <th>単位</th>
                  </tr>
                  <tr v-for="stock in left_stock_requests" :key="stock.id">
                    <td class="img">
                      <img
                        @click="modalImage($event.target, stock.stock_id)"
                        :src="getImgPath(stock.img_path)"
                        alt=""
                      />
                    </td>
                    <td class="text-2xl">{{ stock.alias ?? stock.name }}</td>
                    <td class="quantity">
                      <input
                        @change="
                          updateQuantity(stock.stock_id, $event.target.value)
                        "
                        class="text-4xl"
                        type="number"
                        name=""
                        id=""
                        :value="stock.quantity"
                      />
                    </td>
                    <td class="unit text-2xl">{{ stock.solo_unit }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div class="right_table_container">
              <table>
                <tbody>
                  <tr>
                    <th>画像</th>
                    <th>品名</th>
                    <th>数量</th>
                    <th>単位</th>
                  </tr>
                  <tr v-for="stock in right_stock_requests" :key="stock.id">
                    <td class="img">
                      <img
                        @click="modalImage($event.target, stock.stock_id)"
                        :src="getImgPath(stock.img_path)"
                        alt=""
                      />
                    </td>
                    <td class="text-2xl">{{ stock.alias ?? stock.name }}</td>
                    <td class="quantity">
                      <input
                        @change="
                          updateQuantity(stock.stock_id, $event.target.value)
                        "
                        class="text-4xl"
                        type="number"
                        name=""
                        id=""
                      />
                    </td>
                    <td class="unit text-2xl">{{ stock.solo_unit }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div v-if="orderData" class="mt-8">
            <!-- 物品依頼ボタン -->
            <button
              @click="orderStockRequest"
              class="text-4xl w-full bg-red-500 hover:bg-red-700 text-white font-bold py-8 px-4 rounded"
            >
              物品依頼
            </button>
          </div>
        </div>
      </div>

      <!-- 管理者画面 -->
      <div v-else>
        <Admin
          :processes="props.processes"
          :stock_requests="props.stock_requests"
          :stock_request_orders="props.stock_request_orders"
        />
      </div>

      <MicroModal
        v-if="modalStatus"
        @closeModal="handleCloseModal"
        :modalImageSrc="modalImageSrc"
        :modalStockId="modalStockId"
      />
    </template>
  </StockLayout>
</template>
<style scoped lang="scss">
.table-container {
  display: flex;
  justify-content: space-between;

  & > div {
    width: 50%;
    padding: 4px;

    & table {
      width: 100%;
    }
  }

  & .left_table_container {
  }
  & .right_table_container {
  }
}

table,
td,
th {
  border: 1px solid #595959;
  border-collapse: collapse;
}
td,
th {
  padding: 3px;
  overflow-wrap: hidden;
  text-align: center;
  font-weight: bold;

  &.img {
    width: 10vw;
    height: 9vw;
    max-width: 10vw;
    max-height: 9vw;
    & img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
  }

  &.quantity {
    width: 6vw;
    height: 100%;

    & input[type="number"] {
      display: block;
      height: 9vw;
      width: 100%;
      border: none;
      text-align: center;
    }
  }

  &.unit {
    width: 2vw;
  }
}

td {
  // text-align: center;
  // font-size: 2rem;
}
th {
  background: #f0e6cc;
}
.even {
  background: #fbf8f0;
}
.odd {
  background: #fefcf9;
}
</style>
