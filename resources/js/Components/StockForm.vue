<script setup>
import { Link } from "@inertiajs/vue3";
import axios from "axios";
import { onMounted, ref, reactive } from "vue";
const props = defineProps({
  orderList: Array,
  title: String,
});
const emit = defineEmits(["updateStocks"]);

const groups = ref([]);
const users = ref([]);

const form = reactive({
  search: {
    stock_name: "",
    address_id: null,
    stock_id: null,
  },
  shipment: {
    stock_id: null,
    quantity: null,
    user_id: null,
  },
});

const getGroups = () => {
  axios
    .get(route("getGroups"))
    .then((res) => {
      console.log(res.data);
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

      break;
  }
};
onMounted(() => {
  getGroups();
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
      <div class="text-container w-3/4">
        <p>
          <span>品名:</span>
        </p>
        <p><span>品番:</span></p>
        <p><span>数量:</span></p>
      </div>
      <div class="img_container">
        <img class="" src="images/stocks/not-image-sample2.png" alt="" />
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
              id="grid-password"
              type="text"
              placeholder="JANコード or 製品ID"
              v-model="form.shipment.stock_id"
            />
          </div>
        </div>
        <div class="flex flex-wrap -mx-3 mb-6">
          <div class="w-full px-3">
            <input
              name="quantity"
              class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
              id="grid-password"
              type="number"
              placeholder="数量"
              v-model="form.shipment.quantity"
            />
          </div>
        </div>
        <div class="flex flex-wrap -mx-3 mb-6">
          <div class="w-full px-3 flex items-center">
            <div class="mr-2 w-1/3">
              <label for="department">部署</label>
              <select
                @change="changeGroups($event.target.value)"
                id="department"
                class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
              >
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
        </div>

        <button
          @click.prevent="clickedButton('shipment')"
          class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
        >
          出庫
        </button>
      </form>
      <section class="text-gray-600 body-font">
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
              placeholder="品名・品番・略名"
              v-model="form.search.stock_name"
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
</style>
