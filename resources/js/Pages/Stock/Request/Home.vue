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

const stock_requests = ref([]);
const left_stock_requests = ref([]);
const right_stock_requests = ref([]);
const users = ref([]);
const admin_users = ref([]);

const already_flg = reactive({
  status: false,
  delivery_date: null,
});

const modalStatus = ref(false);
const modalImageSrc = ref("");
const modalStockId = ref();

const orderData = ref({});

const is_login = ref(false);

const handleCloseModal = () => {
  modalStatus.value = !modalStatus.value;
};

const handleProcessId = (process_id) => {
  form.user_id = null;
  users.value = props.users.filter((user) => user.process_id == process_id);
  checkAlreadyStockRequest();
};

const modalImage = (target, stock_id) => {
  if (stock_id) modalStockId.value = stock_id;
  modalStatus.value = true;
  modalImageSrc.value = target.src;
};

const form = reactive({
  process_id: 0,
  user_id: 0,
  pwd: "",
});

const loginAdmin = () => {
  if (form.pwd) {
    is_login.value = admin_users.value.find((user) => user.password === form.pwd);
    if (!is_login.value) alert("パスワードが正しくありません。");
  } else {
    alert("パスワードを入力してください。");
  }
};

const updateQuantity = (stock_id, quantity) => {
  orderData.value[stock_id] = quantity;
};

const orderStockRequest = () => {
  axios
    .post(route("stock.request.store"), {
      already_flg: already_flg.status,
      process_id: form.process_id,
      user_id: form.user_id,
      data: orderData.value,
    })
    .then((res) => {
      if (res.data.status) {
        if (confirm("依頼が完了しました。")) window.location.reload();
      }
    })
    .catch((error) => console.log(error));
};

const checkAlreadyStockRequest = () => {
  if (props.stock_request_orders.some((order) => order.process_id === form.process_id)) {
    if (confirm("既存の依頼が見つかりました。確認・修正しますか？\n（キャンセルで新規依頼）")) {
      stock_requests.value.forEach((stock_request) => {
        const matchingOrder = props.stock_request_orders.find(
          (o) => o.process_id == form.process_id && o.stock_id == stock_request.stock_id
        );
        stock_request.quantity = matchingOrder ? matchingOrder.quantity : '';
      });
      already_flg.status = true;
    } else {
      stock_requests.value = props.stock_requests.map((sr) => ({ ...sr, quantity: '' }));
      already_flg.status = false;
    }
  } else {
    stock_requests.value = props.stock_requests;
    stock_requests.value.forEach((sr) => { sr.quantity = ""; });
    already_flg.status = false;
  }
  sliceStockRequests(stock_requests.value);
};

const isWeekdayMonToWed = () => {
  const day = new Date().getDay();
  return day >= 1 && day <= 3;
};

const setUpAlreadyFlg = () => {
  if (isWeekdayMonToWed()) {
    const today = new Date();
    const nextMonday = new Date(today.setDate(today.getDate() + ((1 + 7 - today.getDay()) % 7 || 7)));
    const days = ["日", "月", "火", "水", "木", "金", "土"];
    already_flg.delivery_date = `${nextMonday.getFullYear()}/${(nextMonday.getMonth() + 1).toString().padStart(2, "0")}/${nextMonday.getDate().toString().padStart(2, "0")}(${days[nextMonday.getDay()]})`;
  }
};

const sliceStockRequests = (requests) => {
  left_stock_requests.value = requests.slice(0, Math.floor(requests.length / 2));
  right_stock_requests.value = requests.slice(Math.floor(requests.length / 2));
};

onMounted(() => {
  stock_requests.value = props.stock_requests;
  sliceStockRequests(stock_requests.value);
  users.value = props.users;
  setUpAlreadyFlg();
  admin_users.value = props.users.filter((user) => user.is_admin);
});
</script>
<template>
  <StockLayout :title="'定期物品依頼'">
    <template #content>
      <!-- Admin login -->
      <div class="flex items-center justify-between mb-8">
        <p v-if="is_login" class="text-sm font-semibold text-slate-600">
          <i class="fas fa-user-shield text-primary-500 mr-1"></i>
          {{ is_login.name }}
        </p>
        <div v-else></div>

        <div class="flex items-center gap-2">
          <input
            class="form-input-modern w-48 text-sm py-2"
            type="password"
            placeholder="管理者パスワード"
            v-model="form.pwd"
          />
          <button v-if="!is_login" @click.prevent="loginAdmin" class="btn-primary text-xs py-2">
            <i class="fas fa-sign-in-alt"></i> ログイン
          </button>
          <button v-if="is_login" @click="is_login = false; form.pwd = ''" class="btn-danger text-xs py-2">
            <i class="fas fa-sign-out-alt"></i> ログアウト
          </button>
        </div>
      </div>

      <!-- User view -->
      <div v-if="!is_login">
        <!-- Info banner -->
        <div class="card p-6 mb-8 bg-primary-50 border-primary-200">
          <p class="text-center text-lg font-semibold text-slate-700 leading-relaxed">
            依頼期間：月〜水曜日（当日終業時刻まで）<br>
            翌週月曜日の朝、供給倉庫にて受け取り可能です。
          </p>
        </div>

        <!-- Process selector -->
        <div class="space-y-5 mb-8">
          <div>
            <label class="form-label text-base text-rose-500">作業場所を選択</label>
            <select
              class="form-select-modern text-xl py-5 text-center font-bold"
              v-model="form.process_id"
              @change="handleProcessId($event.target.value)"
            >
              <option value="0">-- 選択してください --</option>
              <option v-for="process in processes" :value="process.id" :key="process.id">
                {{ process.name }}
              </option>
            </select>
          </div>
          <div>
            <label class="form-label text-base text-rose-500">ユーザーを選択</label>
            <select
              class="form-select-modern text-xl py-5 text-center font-bold"
              v-model="form.user_id"
            >
              <option value="0">-- 選択してください --</option>
              <option v-for="user in users" :value="user.id" :key="user.id">
                {{ user.name }}
              </option>
            </select>
          </div>
        </div>

        <!-- Order form -->
        <div v-if="form.process_id && form.user_id" class="mt-8">
          <div class="text-center mb-6">
            <span v-if="already_flg.status" class="badge-warning text-base px-6 py-2">確認・修正</span>
            <span v-else class="badge-success text-base px-6 py-2">新規依頼</span>
          </div>

          <div class="flex flex-col lg:flex-row gap-4">
            <!-- Left table -->
            <div class="flex-1">
              <div class="card overflow-hidden">
                <table class="request-table">
                  <thead>
                    <tr>
                      <th class="w-24">画像</th>
                      <th>品名</th>
                      <th class="w-24">数量</th>
                      <th class="w-16">単位</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="stock in left_stock_requests" :key="stock.id">
                      <td class="p-1">
                        <img
                          @click="modalImage($event.target, stock.stock_id)"
                          :src="getImgPath(stock.img_path)"
                          class="w-20 h-16 object-cover rounded-lg cursor-pointer hover:opacity-80 transition-opacity"
                        />
                      </td>
                      <td class="text-sm font-semibold text-slate-700">{{ stock.alias ?? stock.name }}</td>
                      <td class="p-1">
                        <input
                          @change="updateQuantity(stock.stock_id, $event.target.value)"
                          class="w-full h-16 text-center text-xl font-bold border-0 bg-slate-50 rounded-lg focus:ring-2 focus:ring-primary-500"
                          type="number"
                          v-model="stock.quantity"
                        />
                      </td>
                      <td class="text-sm text-slate-500 text-center">{{ stock.orderUnit ?? stock.solo_unit }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <!-- Right table -->
            <div class="flex-1">
              <div class="card overflow-hidden">
                <table class="request-table">
                  <thead>
                    <tr>
                      <th class="w-24">画像</th>
                      <th>品名</th>
                      <th class="w-24">数量</th>
                      <th class="w-16">単位</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="stock in right_stock_requests" :key="stock.id">
                      <td class="p-1">
                        <img
                          @click="modalImage($event.target, stock.stock_id)"
                          :src="getImgPath(stock.img_path)"
                          class="w-20 h-16 object-cover rounded-lg cursor-pointer hover:opacity-80 transition-opacity"
                        />
                      </td>
                      <td class="text-sm font-semibold text-slate-700">{{ stock.alias ?? stock.name }}</td>
                      <td class="p-1">
                        <input
                          @change="updateQuantity(stock.stock_id, $event.target.value)"
                          v-model="stock.quantity"
                          class="w-full h-16 text-center text-xl font-bold border-0 bg-slate-50 rounded-lg focus:ring-2 focus:ring-primary-500"
                          type="number"
                        />
                      </td>
                      <td class="text-sm text-slate-500 text-center">{{ stock.orderUnit ?? stock.solo_unit }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <div v-if="Object.keys(orderData).length > 0 && isWeekdayMonToWed" class="mt-8">
            <p v-if="already_flg.delivery_date" class="text-center text-2xl mb-6 text-slate-700 font-bold">
              <i class="fas fa-calendar-check text-primary-500 mr-2"></i>
              納品日：{{ already_flg.delivery_date }}
            </p>
            <button @click="orderStockRequest" class="btn-danger w-full py-6 text-xl rounded-2xl">
              <i class="fas fa-paper-plane mr-2"></i>
              依頼を送信
            </button>
          </div>
        </div>
      </div>

      <!-- Admin view -->
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
.request-table {
  width: 100%;
  border-collapse: collapse;

  thead {
    background-color: #f8fafc;
    border-bottom: 2px solid #e2e8f0;
  }

  th {
    padding: 0.75rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    text-align: center;
  }

  td {
    padding: 0.5rem;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
  }

  tbody tr:hover {
    background-color: #f8fafc;
  }
}
</style>
