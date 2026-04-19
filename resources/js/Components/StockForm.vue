<script setup>
import { getImgPath } from "@/Helper/method";
import { Link, router } from "@inertiajs/vue3";
import axios from "axios";
import { onMounted, ref, reactive } from "vue";

const props = defineProps({
  orderList: Array,
  title: String,
  stock_storage: Object,
  processes: Array,
  users: Array,
});

const process_users = ref([]);
const changeProcess = () => {
  process_users.value = props.users.filter(
    (user) => user.process_id === form.search.process_id
  );
};

const success = ref(false);
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
    stock_s_name: "",
    alias: "",
    address_id: null,
    stock_id: null,
    process_id: 0,
    user_id: 0,
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
      params: { group_id: group_id },
    })
    .then((res) => {
      users.value = res.data;
    })
    .catch((error) => {
      console.log(error);
    });
};
const changeGroups = (group_id) => {
  getUsersByGroup(group_id);
};

const playAudio = () => {
  const audio = document.querySelector("#shipment_success_mp3");
  setTimeout(() => {
    audio.play();
  }, 1000);
};

const clickedButton = (button_name) => {
  switch (button_name) {
    case "search":
      if (
        !form.search.stock_name &&
        !form.search.alias &&
        !form.search.address_id &&
        !form.search.stock_id &&
        !form.search.process_id
      ) {
        return alert("検索条件を入力してください。");
      }
      axios
        .get(route("getStocks"), {
          params: {
            stock_name: form.search.stock_name,
            stock_s_name: form.search.stock_s_name,
            alias: form.search.alias,
            address_id: form.search.address_id,
            stock_id: form.search.stock_id,
            process_id: form.search.process_id,
            user_id: form.search.user_id,
          },
        })
        .then((res) => {
          emit("updateStocks", res.data);
        })
        .catch((error) => {
          console.log(error);
        });
      break;
    case "shipment":
      axios
        .post(route("stock.shipment.store"), form.shipment)
        .then((res) => {
          if (res.data.status) {
            success.value = true;
            playAudio();
            if(confirm("出庫が完了しました。")){
              location.href = route("stock.home")
            }
          }
        })
        .catch((error) => {
          console.log(error);
        });
      break;
  }
};

const changeStockId = (stock_id, selectStockStorageId = 0) => {
  axios
    .get(route("getStockStorages"), {
      params: { stock_id: stock_id },
    })
    .then((res) => {
      stock.stock_name = res.data.name;
      stock.stock_s_name = res.data.s_name;
      stock.img_path = res.data.img_path;

      storage_addresses.value = res.data.stock_storages;

      if (storage_addresses.value.length === 1) {
        form.shipment.address_id = storage_addresses.value[0].id;
        stock.quantity = storage_addresses.value[0].quantity;
      } else if (storage_addresses.value.length > 1) {
        if (selectStockStorageId) {
          storage_addresses.value.forEach((address) => {
            if (address.id === selectStockStorageId) {
              form.shipment.address_id = address.id;
              stock.quantity = address.quantity;
            }
          });
        } else {
          alert("複数の格納先が見つかりました。選択してください。");
        }
      } else {
        alert("格納先が登録されていません。先に登録してください。");
        form.shipment.address_id = 0;
      }
    })
    .catch((error) => {
      console.log(error);
    });
};

const focus_input_stock_id = () => {
  const input_stock_id = document.querySelector("#input_stock_id");
  input_stock_id.focus();
};

const clickStockInventoryButton = () => {
  router.get(
    route("stock.inventory.show", {
      stock_id: form.shipment.stock_id,
      stock_storage_id: form.shipment.address_id,
    }),
    { request_user_id: form.shipment.user_id }
  );
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
  <!-- Page title -->
  <div class="mb-8">
    <h1 class="text-3xl font-bold text-slate-800">
      <i class="fas fa-dolly text-primary-500 mr-3" v-if="route().current().endsWith('shipment')"></i>
      <i class="fas fa-search text-primary-500 mr-3" v-else></i>
      {{ route().current().endsWith("shipment") ? "出庫" : "検索" }}
    </h1>
    <div class="h-1.5 w-20 bg-primary-500 rounded-full mt-3"></div>
  </div>

  <div class="flex flex-col lg:flex-row gap-6">
    <!-- Left: Stock info -->
    <div class="w-full lg:w-1/2">
      <div class="card p-6">
        <!-- Detail button -->
        <div
          v-if="route().current() == 'stock.shipment' && form.shipment.address_id !== null"
          class="mb-4"
        >
          <button
            v-if="form.shipment.user_id"
            @click="clickStockInventoryButton"
            :class="[
              'btn w-full py-4 text-base font-bold rounded-xl',
              form.shipment.address_id ? 'btn-success' : 'btn-secondary',
              { 'animate-pulse': success }
            ]"
          >
            {{ form.shipment.address_id ? "詳細画面へ進む" : "格納先を登録" }}
            <i class="fas fa-arrow-right ml-2"></i>
          </button>
        </div>

        <!-- Stock info display -->
        <div class="space-y-4 mb-6">
          <div class="flex items-center gap-4 py-3 border-b border-slate-100">
            <span class="text-sm text-slate-400 w-20 shrink-0">品名</span>
            <span class="text-lg font-semibold text-slate-800">{{ stock.stock_name }}</span>
          </div>
          <div class="flex items-center gap-4 py-3 border-b border-slate-100">
            <span class="text-sm text-slate-400 w-20 shrink-0">品番</span>
            <span class="text-lg font-semibold text-slate-800">{{ stock.stock_s_name }}</span>
          </div>
          <div class="flex items-center gap-4 py-3 border-b border-slate-100">
            <span class="text-sm text-slate-400 w-20 shrink-0">数量</span>
            <span class="text-2xl font-bold text-slate-800">{{ stock.quantity }}</span>
          </div>
        </div>

        <!-- Stock image -->
        <div v-if="stock.img_path" class="bg-slate-50 rounded-xl p-4 flex justify-center">
          <img class="max-h-48 object-contain" :src="getImgPath(stock.img_path)" alt="" />
        </div>
      </div>
    </div>

    <!-- Right: Shipment form -->
    <div v-if="route().current().endsWith('shipment')" class="w-full lg:w-1/2">
      <div class="card p-6">
        <form class="space-y-5">
          <div>
            <label class="form-label text-base">JANコード / 商品ID</label>
            <input
              id="input_stock_id"
              class="form-input-modern text-lg py-5 font-mono"
              type="text"
              placeholder="コードをスキャンまたは入力"
              v-model="form.shipment.stock_id"
              @change="changeStockId($event.target.value)"
            />
          </div>

          <div>
            <label class="form-label text-base">格納先番地</label>
            <select class="form-select-modern text-base py-4" v-model="form.shipment.address_id">
              <option value="0" disabled selected>格納先を選択</option>
              <option
                v-for="addr in storage_addresses"
                :key="addr.id"
                :value="addr.id"
              >
                {{ addr.location_name + " : " + addr.address }}
              </option>
            </select>
          </div>

          <div v-if="form.shipment.address_id">
            <label class="form-label text-base">数量</label>
            <input
              class="form-input-modern text-xl py-5 text-center font-bold"
              type="number"
              placeholder="数量を入力"
              v-model="form.shipment.quantity"
            />
            <p v-if="!form.shipment.quantity" class="mt-2 text-sm text-rose-500 flex items-center gap-1.5">
              <i class="fas fa-exclamation-circle"></i> 必須項目
            </p>
          </div>

          <div v-if="form.shipment.quantity" class="flex flex-col md:flex-row gap-3">
            <div class="flex-1">
              <label class="form-label text-base">部署</label>
              <select
                @change="changeGroups($event.target.value)"
                class="form-select-modern text-base py-4"
              >
                <option value="0">選択</option>
                <option v-for="group in groups" :key="group.id" :value="group.id">
                  {{ group.name }}
                </option>
              </select>
            </div>
            <div class="flex-[2]">
              <label class="form-label text-base">氏名</label>
              <select class="form-select-modern text-base py-4" v-model="form.shipment.user_id">
                <option v-for="user in users" :key="user.id" :value="user.id">
                  {{ user.name }}
                </option>
              </select>
            </div>
          </div>
          <p v-if="form.shipment.quantity && !form.shipment.user_id" class="text-sm text-rose-500 flex items-center gap-1.5">
            <i class="fas fa-exclamation-circle"></i> 担当者を選択してください
          </p>

          <button
            v-if="form.shipment.address_id && form.shipment.quantity && form.shipment.user_id"
            @click.prevent="clickedButton('shipment')"
            class="btn-primary w-full py-5 text-xl font-bold rounded-2xl mt-3"
          >
            <i class="fas fa-dolly mr-2"></i>
            出庫
          </button>
        </form>
      </div>
    </div>

    <!-- Right: Search form -->
    <div v-if="route().current().endsWith('search')" class="w-full lg:w-1/2">
      <div class="card p-6">
        <form class="space-y-4">
          <div class="card p-4 bg-slate-50 border-slate-200">
            <label class="form-label text-rose-500">発注履歴から検索</label>
            <div class="flex gap-3">
              <select
                class="form-select-modern flex-1"
                v-model="form.search.process_id"
                @change="changeProcess"
              >
                <option value="0">工程を選択</option>
                <option v-for="process in props.processes" :key="process.id" :value="process.id">
                  {{ process.name }}
                </option>
              </select>
              <select
                v-if="form.search.process_id"
                class="form-select-modern flex-1"
                v-model="form.search.user_id"
              >
                <option value="0">依頼者で絞り込む</option>
                <option v-for="user in process_users" :key="user.id" :value="user.id">
                  {{ user.name }}
                </option>
              </select>
            </div>
          </div>

          <div>
            <label class="form-label">品名</label>
            <input class="form-input-modern" type="text" placeholder="品名を入力" v-model="form.search.stock_name" />
          </div>
          <div>
            <label class="form-label">品番</label>
            <input class="form-input-modern" type="text" placeholder="品番を入力" v-model="form.search.stock_s_name" />
          </div>
          <div>
            <label class="form-label">別名</label>
            <input class="form-input-modern" type="text" placeholder="別名を入力" v-model="form.search.alias" />
          </div>
          <div>
            <label class="form-label">棚番地</label>
            <input class="form-input-modern" type="number" placeholder="番地を入力" v-model="form.search.address_id" />
          </div>
          <div>
            <label class="form-label">商品ID / JAN</label>
            <input class="form-input-modern" type="number" placeholder="IDまたはJANコードを入力" v-model="form.search.stock_id" />
          </div>

          <button @click.prevent="clickedButton('search')" class="btn-primary w-full btn-lg mt-2">
            <i class="fas fa-search text-sm"></i>
            検索
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- Success animation -->
  <img v-if="success" class="fixed top-1/4 left-1/2 -translate-x-1/2 z-50 w-[30vw] rounded-2xl opacity-90" src="/images/stocks/shipment_success.gif" alt="" />
  <audio id="shipment_success_mp3">
    <source src="/audio/stocks/shipment_success.mp3" />
  </audio>
</template>
