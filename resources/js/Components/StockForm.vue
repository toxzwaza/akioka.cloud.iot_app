<script setup>
import { getImgPath } from "@/Helper/method";
import { Link } from "@inertiajs/vue3";
import axios from "axios";
import { onMounted, ref, reactive } from "vue";

const props = defineProps({
  orderList: Array,
  title: String,
  stock_storage: Object,
});
const emit = defineEmits(["updateStocks"]);

const storage_addresses = ref([]);
const groups = ref([]);
const users = ref([]);
const orders = ref([]);

const stock = reactive({
  stock_name: null,
  stock_s_name: null,
  quantity: null,
  img_path: null,
});

const form = reactive({
  search: {
    stock_name: "",
    alias: "",
    address_id: null,
    stock_id: null,
  },
  shipment: {
    stock_id: null,
    address_id: null,
    quantity: null,
    user_id: null,
  },
});

const getGroups = () => {
  axios
    .get(route("getGroups"))
    .then((res) => {
      groups.value = res.data;
    })
    .catch((error) => {
      console.log(error);
    });
};
const getUsersByGroup = (group_id) => {
  axios
    .get(route("getUsersByGroup"), {
      params: {
        group_id: group_id,
      },
    })
    .then((res) => {
      console.log(res.data);
      users.value = res.data;
    })
    .catch((error) => {
      console.log(error);
    });
};
const changeGroups = (group_id) => {
  getUsersByGroup(group_id);
};

const clickedButton = (button_name) => {
  console.log(form);
  switch (button_name) {
    case "search":
      // 検索ボタンが押下された場合の処理
      axios
        .get(route("getStocks"), {
          params: {
            stock_name: form.search.stock_name,
            alias: form.search.alias,
            address_id: form.search.address_id,
            stock_id: form.search.stock_id,
          },
        })
        .then((res) => {
          emit("updateStocks", res.data); // 親コンポーネントにデータを送信
        })
        .catch((error) => {
          console.log(error);
        });
      break;
    case "shipment":
      // 出庫ボタンが押下された場合の処理
      console.log(form.shipment);
      // データチェック
      axios
        .post(route("stock.shipment.store"), form.shipment)
        .then((res) => {
          console.log(res.data);

          if (res.data.status) {
            alert("出庫登録が完了しました。\n発注が必要な場合は「詳細・発注画面へ進む」ボタンから発注依頼を行ってください。");
          } else {
            alert(
              "出庫登録が失敗しました。再度お試し頂くか、管理者に問い合わせてください。"
            );
          }
        })
        .catch((error) => {
          console.log(error);
        });
      break;
  }
};

// 在庫IDもしくはJANコードを取得
const changeStockId = (stock_id, selectStockStorageId = 0) => {
  axios
    .get(route("getStockStorages"), {
      params: {
        stock_id: stock_id,
      },
    })
    .then((res) => {
      console.log(res.data);
      stock.stock_name = res.data.name;
      stock.stock_s_name = res.data.s_name;
      stock.img_path = res.data.img_path;

      storage_addresses.value = res.data.stock_storages;

      if (storage_addresses.value.length === 1) {
        form.shipment.address_id = storage_addresses.value[0].id;
        stock.quantity = storage_addresses.value[0].quantity;
      } else if (storage_addresses.value.length > 1) {
        // alert('保管場所が複数あります。セレクトボックスから選択してください。')
        if (selectStockStorageId) {
          storage_addresses.value.forEach((address) => {
            if (address.id === selectStockStorageId) {
              form.shipment.address_id = address.id;
              stock.quantity = address.quantity;
            }
          });
        } else {
          alert("保管場所が複数存在します。対象の保管場所を選択してください。");
        }
      } else {
        // 保管場所が１つも存在しない場合
        alert(
          "保管場所が取得できませんでした。保管場所を登録して、再度お試しください。"
        );
        form.shipment.address_id = 0
      }
    })
    .catch((error) => {
      console.log(error);
    });
};

// janコードのinput要素をアクティブ
const focus_input_stock_id = () => {
  const input_stock_id = document.querySelector("#input_stock_id");
  input_stock_id.focus();
};

const clickStockInventoryButton = () => {
  window.location.href = route("stock.inventory.show", {
    stock_id: form.shipment.stock_id,
    stock_storage_id: form.shipment.address_id,
  });
};

onMounted(() => {
  getGroups();

  if (props.stock_storage) {
    form.shipment.stock_id = props.stock_storage.stock_id;
    changeStockId(props.stock_storage.stock_id, props.stock_storage.id);
  }

  if (route().current() == "stock.shipment") {
    focus_input_stock_id();
  }
});
</script>
<template>
  <h1 id="page_title" class="p-2 mb-4">
    {{ route().current().endsWith("shipment") ? "出庫" : "検索" }}
  </h1>

  <div class="flex justify-between items-start">
    <div
      id="stock_container"
      class="w-1/2 p-2 flex flex-col justify-center items-center"
    >
      <div v-if="route().current() == 'stock.shipment' && form.shipment.address_id !== null" class="button_container w-full mb-4">
        <!-- 詳細画面へ遷移するボタン -->
        <button

          @click="clickStockInventoryButton"
          :class="{
            'inline-flex items-center px-3 py-2 text-sm font-medium text-center text-white  rounded-lg  focus:ring-4 focus:outline-none dark:focus:ring-green-800 focus:ring-green-300': true,
            'bg-green-500 hover:bg-green-800 dark:bg-green-600 dark:hover:bg-green-500':
              form.shipment.address_id,
            'bg-gray-500 hover:bg-gray-800 dark:bg-gray-600 dark:hover:bg-gray-500':
              !form.shipment.address_id,
          }"
        >
          {{
            form.shipment.address_id ? "詳細・発注画面へ進む" : "格納先アドレスを登録"
          }}
          <svg
            class="rtl:rotate-180 w-3.5 h-3.5 ms-2"
            aria-hidden="true"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 14 10"
          >
            <path
              stroke="currentColor"
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M1 5h12m0 0L9 1m4 4L9 9"
            />
          </svg>
        </button>
      </div>
      <div class="text-container w-3/4">
        <p><span>品名:</span>{{ stock.stock_name }}</p>
        <p><span>品番:</span>{{ stock.stock_s_name }}</p>
        <p><span>数量:</span>{{ stock.quantity }}</p>
      </div>
      <div class="img_container">
        <img class="" :src="getImgPath(stock.img_path)" alt="" />
      </div>
    </div>

    <div v-if="route().current().endsWith('shipment')" class="w-1/2 p-2">
      <!-- 出庫用フォーム -->
      <form>
        <div class="flex flex-wrap -mx-3 mb-6">
          <div class="w-full px-3">
            <input
              name="stock_id"
              class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
              id="input_stock_id"
              type="text"
              placeholder="JANコード or 製品ID"
              v-model="form.shipment.stock_id"
              @change="changeStockId($event.target.value)"
            />
          </div>
        </div>
        <div class="flex flex-wrap -mx-3 mb-6">
          <div class="w-full px-3">
            <select
              name="address"
              class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
              id="grid-password"
              v-model="form.shipment.address_id"
            >
              <option value="0" disabled selected>
                出庫元アドレスを選択してください
              </option>
              <option
                v-for="storage_address in storage_addresses"
                :key="storage_address.id"
                :value="storage_address.id"
              >
                {{
                  storage_address.location_name +
                  " : " +
                  storage_address.address
                }}
              </option>
            </select>
          </div>
        </div>

        <div v-if="form.shipment.address_id" class="flex flex-wrap -mx-3 mb-6">
          <div class="w-full px-3">
            <input
              v-if="form.shipment.address_id"
              name="quantity"
              class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
              id="grid-password"
              type="number"
              placeholder="数量"
              v-model="form.shipment.quantity"
            />
          </div>
          <span v-if="!form.shipment.quantity" class="msg px-3 text-red-600"
            >数量を入力して下さい。</span
          >
        </div>

        <div v-if="form.shipment.quantity" class="flex flex-wrap -mx-3 mb-6">
          <div class="w-full px-3 flex items-center">
            <div class="mr-2 w-1/3">
              <label for="department">部署</label>
              <select
                @change="changeGroups($event.target.value)"
                id="department"
                class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
              >
                <option value="0">未選択</option>
                <option
                  v-for="group in groups"
                  :key="group.id"
                  :value="group.id"
                >
                  {{ group.name }}
                </option>
              </select>
            </div>

            <div class="mr-2 w-2/3">
              <label for="name">名前</label>
              <select
                name="user_id"
                id="name"
                class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                v-model="form.shipment.user_id"
              >
                <option v-for="user in users" :key="user.id" :value="user.id">
                  {{ user.name }}
                </option>
              </select>
            </div>
          </div>
          <span v-if="!form.shipment.user_id" class="msg px-3 text-red-600"
            >出庫者を選択して下さい。</span
          >
        </div>

        <button
          v-if="form.shipment.address_id && form.shipment.quantity"
          @click.prevent="clickedButton('shipment')"
          class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
        >
          出庫
        </button>
      </form>
      <section v-if="orders.length > 0" class="text-gray-600 body-font">
        <div class="container py-16 mx-auto">
          <h2 class="order_title">発注データ</h2>
          <div class="w-full mx-auto overflow-auto">
            <table class="table-auto w-full text-left whitespace-no-wrap">
              <thead>
                <tr>
                  <th
                    class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100 rounded-tl rounded-bl"
                  >
                    Plan
                  </th>
                  <th
                    class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
                  >
                    Speed
                  </th>
                  <th
                    class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
                  >
                    Storage
                  </th>
                  <th
                    class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
                  >
                    Price
                  </th>
                  <th
                    class="w-10 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100 rounded-tr rounded-br"
                  ></th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td class="px-4 py-3">Start</td>
                  <td class="px-4 py-3">5 Mb/s</td>
                  <td class="px-4 py-3">15 GB</td>
                  <td class="px-4 py-3 text-lg text-gray-900">Free</td>
                  <td class="w-10 text-center">
                    <input name="plan" type="radio" />
                  </td>
                </tr>
                <tr>
                  <td class="border-t-2 border-gray-200 px-4 py-3">Pro</td>
                  <td class="border-t-2 border-gray-200 px-4 py-3">25 Mb/s</td>
                  <td class="border-t-2 border-gray-200 px-4 py-3">25 GB</td>
                  <td
                    class="border-t-2 border-gray-200 px-4 py-3 text-lg text-gray-900"
                  >
                    $24
                  </td>
                  <td class="border-t-2 border-gray-200 w-10 text-center">
                    <input name="plan" type="radio" />
                  </td>
                </tr>
                <tr>
                  <td class="border-t-2 border-gray-200 px-4 py-3">Business</td>
                  <td class="border-t-2 border-gray-200 px-4 py-3">36 Mb/s</td>
                  <td class="border-t-2 border-gray-200 px-4 py-3">40 GB</td>
                  <td
                    class="border-t-2 border-gray-200 px-4 py-3 text-lg text-gray-900"
                  >
                    $50
                  </td>
                  <td class="border-t-2 border-gray-200 w-10 text-center">
                    <input name="plan" type="radio" />
                  </td>
                </tr>
                <tr>
                  <td class="border-t-2 border-b-2 border-gray-200 px-4 py-3">
                    Exclusive
                  </td>
                  <td class="border-t-2 border-b-2 border-gray-200 px-4 py-3">
                    48 Mb/s
                  </td>
                  <td class="border-t-2 border-b-2 border-gray-200 px-4 py-3">
                    120 GB
                  </td>
                  <td
                    class="border-t-2 border-b-2 border-gray-200 px-4 py-3 text-lg text-gray-900"
                  >
                    $72
                  </td>
                  <td
                    class="border-t-2 border-b-2 border-gray-200 w-10 text-center"
                  >
                    <input name="plan" type="radio" />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>
    </div>

    <div v-if="route().current().endsWith('search')" class="w-1/2 p-2">
      <!-- 検索用フォーム -->
      <form>
        <div class="flex flex-wrap -mx-3 mb-6">
          <div class="w-full px-3">
            <input
              name="search_name"
              class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
              id="grid-password"
              type="text"
              placeholder="品名・品番"
              v-model="form.search.stock_name"
            />
          </div>
        </div>
        <div class="flex flex-wrap -mx-3 mb-6">
          <div class="w-full px-3">
            <input
              name="alias"
              class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
              id="grid-password"
              type="text"
              placeholder="略名"
              v-model="form.search.alias"
            />
          </div>
        </div>
        <div class="flex flex-wrap -mx-3 mb-6">
          <div class="w-full px-3">
            <input
              name="address_id"
              class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
              id="grid-password"
              type="number"
              placeholder="棚アドレス"
              v-model="form.search.address_id"
            />
          </div>
        </div>
        <div class="flex flex-wrap -mx-3 mb-6">
          <div class="w-full px-3">
            <input
              name="stock_id"
              class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
              id="grid-password"
              type="number"
              placeholder="製品ID or JANコード"
              v-model="form.search.stock_id"
            />
          </div>
        </div>

        <button
          @click.prevent="clickedButton('search')"
          class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
        >
          検索
        </button>
      </form>
    </div>
  </div>
</template>
<style scoped lang="scss">
#page_title {
  font-size: 2rem;

  font-weight: bold;
  color: #109ff3;
  border-bottom: 3px solid gray;
  text-align: center;
}
.text-container {
  font-weight: bold;
  font-size: 1.4rem;

  & p {
    width: 100%;
    white-space: nowrap;
    overflow-x: hidden;
    color: #109ff3;
    border-bottom: 1px dashed rgb(199, 199, 199);
    margin-bottom: 1rem;

    & span {
      font-weight: normal;
      font-size: 1rem;
      color: gray;
      margin-right: 1rem;
      opacity: 0.8;
    }
  }
}
.img_container {
  width: 100%;
  display: flex;
  justify-content: center;

  & img {
    width: 80%;
    object-fit: contain;
  }
}

.order_title {
  font-weight: bold;
  color: #109ff3;
  font-size: 1.2rem;
}

.msg {
  &::before {
    content: "・";
    font-weight: bold;
    font-family: monospace;
  }
}
</style>
