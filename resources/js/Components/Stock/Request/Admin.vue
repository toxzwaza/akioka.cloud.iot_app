<script setup>
import { onMounted, ref,reactive, computed } from "vue";
import { getImgPath, changeDateFormat } from "@/Helper/method";

const props = defineProps({
  processes: Array,
  stock_requests: Array,
  stock_request_orders: Array,
});

const form = reactive({
  start_date : null,
  end_date: null,
})

const target_process = ref(null);
const stock_requests = ref([]);
const stock_request_orders = ref([]);

const setProcess = (process_id) => {
  console.log('実行', process_id)

  if (process_id) {
    target_process.value = props.processes.find(
      (process) => process.id == process_id
    );

    stock_requests.value = stock_request_orders.value
      .filter(
        (stock_request_order) => stock_request_order.process_id == process_id
      )
      .map((stock_request_order) => {
        const stock_request = props.stock_requests.find(
          (stock_request) =>
            stock_request.stock_id == stock_request_order.stock_id
        );
        if (stock_request) {
          return {
            ...stock_request,
            quantity: stock_request_order.quantity,
            updateQuantity:
              stock_request.stock_storage_quantity -
              stock_request_order.quantity,
            status: stock_request_order.status,
          };
        }
      })
      .filter((stock_request) => stock_request !== undefined);
  } else {
    target_process.value = null;

    stock_requests.value = [];
    stock_request_orders.value.forEach((stock_request_order) => {
      let stock_request = stock_requests.value.find(
        (sr) => sr.stock_id == stock_request_order.stock_id
      );
      if (stock_request) {
        stock_request.quantity += stock_request_order.quantity;
        stock_request.updateQuantity -= stock_request_order.quantity;
      } else {
        stock_request = props.stock_requests.find(
          (sr) => sr.stock_id == stock_request_order.stock_id
        );
        if (stock_request) {
          stock_requests.value.push({
            ...stock_request,
            quantity: stock_request_order.quantity,
            updateQuantity:
              stock_request.stock_storage_quantity -
              stock_request_order.quantity,
            order_flg:
              stock_request_order.order_flg == 1
                ? 1
                : stock_request_order.order_flg,
          });
        }
      }
    });
  }

  console.log("stock_requests.value:", stock_requests.value);
};

// 発注依頼
const orderRequestByStockRequestOrder = (stock) => {
  console.log(stock);

  if (
    confirm(
      `品名: ${stock.name}\n品番: ${
        stock.s_name ?? ""
      }\nの発注依頼を行います。よろしいですか？`
    )
  ) {
    axios
      .post(route("stock.request.order"), {
        stock_id: stock.stock_id,
        request_user_id: 81, //現状三谷さん固定
      })
      .then((res) => {
        console.log(res.data);
        if (res.data.status) {
          stock.order_flg = true;
          alert("発注依頼が完了しました。");
        }
      })
      .catch((error) => {
        console.log(error);
      });
  }
};

const checkStockRequest = (process_id) => {
  return stock_request_orders.value.some(
    (order) => order.process_id == process_id
  );
};

const updateUpdateQuantity = (val, stock) => {
  stock.updateQuantity = val;
};

// 完了処理
const completeStockRequest = (stock) => {
  // return console.log(stock)
  axios
    .post(route("stock.request.complete"), {
      process_id: target_process.value.id,
      stock_id: stock.stock_id,
      stock_storage_id: stock.stock_storage_id,
      updateQuantity: stock.updateQuantity,
    })
    .then((res) => {
      console.log(res.data);
      if (res.data.status) {
        stock.status = 1;
      }
    })
    .catch((error) => {
      console.log(error);
    });
};
const deleteStockRequest = (stock) => {
  console.log(stock.stock_id, target_process.value);
  if (
    !confirm(
      `品名: ${stock.name}\n品番: ${
        stock.s_name ?? ""
      }\nの発注削除を行います。よろしいですか？`
    )
  ) {
    alert("発注削除を取消しました。");
    return;
  }
  const deleteStockRequestOrder = stock_request_orders.value.find(
    (stock_request_order) =>
      stock_request_order.stock_id === stock.stock_id &&
      stock_request_order.process_id === target_process.value.id
  );

  // 削除対象の注文が見つかった場合
  if (deleteStockRequestOrder) {
    axios
      .delete(route("stock.request.delete"), {
        params: {
          stock_request_order_id: deleteStockRequestOrder.id,
        },
      })
      .then((res) => {
        console.log(res.data);
        if (res.data.status) {
          alert("注文依頼を削除しました。");
          window.location.reload();
        }
      })
      .catch((error) => {
        console.log(error);
      });
  }

  console.log(deleteStockRequestOrder);
};

const getAdminStockRequestOrders = () => {
  axios
    .get(route("stock.request.getAdminStockRequestOrders"), {
      params: {
        start_date: form.start_date,
        end_date : form.end_date
      }
    })
    .then((res) => {
      console.log(res.data);
      stock_request_orders.value = res.data;
      setProcess();
    })
    .catch((error) => {
      console.log(error);
    });
};

onMounted(() => {
  // 先週木曜日と今週水曜日の日付を設定
  const today = new Date();
  const currentDay = today.getDay(); // 0: 日曜日, 1: 月曜日, ..., 6: 土曜日
  
  // 先週木曜日を計算（木曜日は4）
  const lastThursday = new Date(today);
  const daysToLastThursday = (currentDay + 3) % 7; // 先週木曜日までの日数
  lastThursday.setDate(today.getDate() - daysToLastThursday - 7);
  
  // start_dateに7日を加えてend_dateを設定
  const endDate = new Date(lastThursday);
  endDate.setDate(lastThursday.getDate() + 6);
  
  // YYYY-MM-DD形式に変換
  form.start_date = lastThursday.toISOString().split('T')[0];
  form.end_date = endDate.toISOString().split('T')[0];

  getAdminStockRequestOrders();
  setProcess()

});
</script>
<template>
  <div class="mb-12 flex items-center justify-center">
    <div class="mr-12 flex items-center" >
      <input class="appearance-none bg-gray-200 text-gray-700 border-transparent rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white" type="date" name="" id="" v-model="form.start_date" />
      <span class="block mx-4">~</span>
      <input class="appearance-none bg-gray-200 text-gray-700 border-transparent rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white" type="date" name="" id="" v-model="form.end_date" />
    </div>

    <button
    @click="getAdminStockRequestOrders"
      :class="{
        'bg-green-500 hover:bg-green-700 text-white font-bold py-3 px-10 rounded': true,
      }"
    >
      検索
    </button>
  </div>
  <div class="flex justify-between">
    <button
      :class="{
        'bg-gray-500 hover:bg-gray-700 text-white font-bold py-4 px-12 rounded': true,
        'bg-red-500 hover:bg-red-700': !target_process,
      }"
      @click="setProcess()"
    >
      全て
    </button>
    <button
      v-for="process in processes"
      :value="process.id"
      :key="process.id"
      :class="{
        'bg-blue-500 hover:bg-blue-700 text-white font-bold py-4 px-12 rounded': true,
        'bg-red-500 hover:bg-red-700':
          target_process && process.id == target_process.id,
        'opacity-50 pointer-events-none': !checkStockRequest(process.id),
      }"
      @click="setProcess(process.id)"
    >
      {{ process.name }}
    </button>
  </div>

  <hr class="my-12" />

  <div class="mt-12">
    <h3
      v-if="target_process"
      class="my-8 text-4xl font-bold text-gray-700 text-center"
    >
      {{ target_process.name }}
    </h3>

    <div class="table-container">
      <div class="w-full">
        <table class="w-full">
          <tbody>
            <tr>
              <th>画像</th>
              <th>品名</th>
              <th>現在個数</th>
              <th>依頼数量</th>
              <th>出庫後数量</th>
              <th>単位</th>
              <th>アドレス</th>
              <th></th>
              <th v-if="target_process"></th>
            </tr>
            <tr v-for="stock in stock_requests" :key="stock.id">
              <td class="img">
                <img :src="getImgPath(stock.img_path)" alt="" />
              </td>
              <td class="text-2xl">{{ stock.alias ?? stock.name }}</td>
              <td class="quantity text-4xl">
                {{ stock.stock_storage_quantity }}
              </td>
              <td class="quantity text-4xl">
                {{ stock.quantity }}
              </td>
              <td
                :class="{
                  'text-4xl quantity': true,
                  'text-green-500': stock.updateQuantity >= 0,
                  'text-red-500': stock.updateQuantity < 0,
                }"
              >
                <input
                  class="text-4xl"
                  v-if="target_process"
                  type="number"
                  name=""
                  id=""
                  :value="stock.updateQuantity"
                  @change="updateUpdateQuantity($event.target.value, stock)"
                />

                <span v-else>{{ stock.updateQuantity }}</span>
              </td>
              <td class="unit text-2xl">
                {{ stock.orderUnit ?? stock.solo_unit }}
              </td>
              <td class="address text-2xl">{{ stock.address }}</td>

              <td class="comp_button text-lg">
                <button
                  v-if="target_process && !stock.status"
                  class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded"
                  @click.prevent="completeStockRequest(stock)"
                >
                  完了
                </button>
                <button
                  v-else-if="target_process && stock.status"
                  class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                >
                  済
                </button>

                <button
                  v-else-if="!target_process && !stock.order_flg"
                  class="bg-orange-500 hover:bg-orange-700 text-white font-bold py-2 px-4 rounded"
                  @click.prevent="orderRequestByStockRequestOrder(stock)"
                >
                  発注
                </button>
                <button
                  v-else-if="!target_process && stock.order_flg"
                  class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                >
                  済
                </button>
              </td>
              <td v-if="target_process" class="comp_button text-lg">
                <button
                  @click="deleteStockRequest(stock)"
                  class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded"
                >
                  削除
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
<style scoped lang="scss">
.table-container {
  display: flex;
  justify-content: space-between;
  color: #313131;
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

  &.comp_button {
    width: 6vw;

    & button {
      height: 100%;
      width: 100%;
      height: 9vw;
    }
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
