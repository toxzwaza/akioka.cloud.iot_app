<script setup>
import { onMounted, ref, reactive } from "vue";

const props = defineProps({
  processes: Array,
  users: Array,
  request_user: Object,
  stock: Object,
});

const emit = defineEmits(["submit"]);

const process_users = ref([]);
// 最短希望納期
const shortest_date = ref("");

const gl_check = ref(null);

const form = reactive({
  process_id: 0,
  user_id: 0,
  now_quantity: 0,
  now_quantity_unit: "",
  digest_date: new Date().toISOString().split("T")[0],
  quantity: 1,
  quantity_unit: "",
  desire_delivery_date: null,
  description: null,
  price: null,
  calc_price: null,
  check: null,
});

const handleProcess = () => {
  if (form.process_id) {
    process_users.value = props.users.filter(
      (user) => user.process_id == form.process_id
    );
  }
};
const handleUser = () => {
  const user = props.users.find((user) => user.id === form.user_id);

  // 製造部所属勝、一般社員の場合GLユーザーを取得
  // if (user && user.position_id == 9 && user.process_id <= 9) {
  //   gl_check.value = props.users.find(
  //     (user) => user.process_id === form.process_id && user.position_id == 8
  //   );
  //   console.log(gl_check.value);
  //   form.check = false;
  // } else {
  //   gl_check.value = null;
  //   form.check = true;
  // }
};

const handleCheck = (event) => {
  form.check = event.target.checked;
};

const handleSubmit = () => {
  if (confirm("上位役職者の確認は完了していますか？")) {
    emit("submit", form);
  }
};

onMounted(() => {
  process_users.value = props.users;

  if (props.stock) {
    // 現在個数と希望納期のデフォルトを設定
    console.log("stock", props.stock);
    if (props.stock.stock_storage && props.stock.stock_storage.quantity) {
      form.now_quantity = props.stock.stock_storage.quantity;
    }
    if (props.stock.stock_supplier && props.stock.stock_supplier.lead_time) {
      shortest_date.value = new Date(
        Date.now() + props.stock.stock_supplier.lead_time * 24 * 60 * 60 * 1000
      )
        .toISOString()
        .split("T")[0];

      form.desire_delivery_date = shortest_date.value;
    }

    form.price = props.stock.price;
    form.calc_price = form.price * form.quantity;

    if (props.request_user) {
      form.process_id = props.request_user.process_id;
      form.user_id = props.request_user.id;
      handleUser();
    }
  }
});
</script>
<template>
  <div id="stock_request_container" class="mt-8 mb-8 bg-gray-50 p-2 rounded">
    <h1 class="text-xl mb-4 text-gray-600 font-bold">物品依頼</h1>

    <form class="w-ful">
      <div class="flex justify-between items-start">
        <div class="w-1/2 pr-4">
          <div class="flex flex-wrap -mx-3 mb-2">
            <div class="w-full md:w-1/2 px-3 mb-6 md:mb-0">
              <label
                class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2"
                for="grid-first-name"
              >
                依頼者所属部署選択
              </label>
              <select
                name=""
                id=""
                class="appearance-none block w-full bg-gray-200 text-gray-700 border-transparent rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white"
                v-model="form.process_id"
                @change="handleProcess"
              >
                <option value="0">未選択</option>
                <option
                  v-for="process in props.processes"
                  :key="process.id"
                  :value="process.id"
                >
                  {{ process.name }}
                </option>
              </select>
            </div>
            <div class="w-full md:w-1/2 px-3">
              <label
                :class="{
                  'block uppercase tracking-wide text-xs font-bold mb-2': true,
                  'text-gray-700': form.user_id,
                  'text-red-500': !form.user_id,
                }"
                for="grid-last-name"
              >
                依頼者選択
              </label>
              <select
                name=""
                id=""
                class="appearance-none block w-full bg-gray-200 text-gray-700 border-transparent rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white"
                v-model="form.user_id"
              >
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
          <div class="flex flex-wrap -mx-3 mb-2">
            <div class="w-1/4 px-3 mb-6 md:mb-0">
              <label
                :class="{
                  'block uppercase tracking-wide text-xs font-bold mb-2': true,
                  'text-gray-700': form.now_quantity !== null && form.now_quantity !== undefined,
                  'text-red-500': form.now_quantity === null || form.now_quantity === undefined,
                }"
                for="grid-city"
              >
                現在個数
              </label>
              <input
                class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                id="grid-city"
                type="number"
                v-model="form.now_quantity"
              />
            </div>
            <div class="w-1/4 px-3 mb-6 md:mb-0">
              <label
                :class="{
                  'block uppercase tracking-wide text-xs font-bold mb-2': true,
                  'text-gray-700': form.now_quantity_unit,
                  'text-red-500': !form.now_quantity_unit,
                }"
                for="grid-city"
              >
                単位
              </label>
              <select
                name=""
                id=""
                class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                v-model="form.now_quantity_unit"
              >
                <option
                  v-if="props.stock.solo_unit"
                  :value="props.stock.solo_unit"
                >
                  {{ props.stock.solo_unit }}
                </option>
                <option
                  v-if="props.stock.org_unit"
                  :value="props.stock.org_unit"
                >
                  {{ props.stock.org_unit }}
                </option>
              </select>
            </div>

            <div class="w-1/2 px-3 mb-6 md:mb-0">
              <label
                :class="{
                  'block uppercase tracking-wide text-xs font-bold mb-2': true,
                  'text-gray-700': form.digest_date,
                  'text-red-500': !form.digest_date,
                }"
                for="grid-state"
              >
                消化予定日
              </label>
              <input
                class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                id="grid-city"
                type="date"
                v-model="form.digest_date"
              />
            </div>
          </div>

          <div class="flex flex-wrap -mx-3 mb-2">
            <div class="w-1/4 px-3 mb-6 md:mb-0">
              <label
                :class="{
                  'block uppercase tracking-wide text-xs font-bold mb-2': true,
                  'text-gray-700': form.quantity,
                  'text-red-500': !form.quantity,
                }"
                for="grid-city"
              >
                必要数量
              </label>
              <input
                class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                id="grid-city"
                type="number"
                v-model="form.quantity"
                @change="form.calc_price = form.price * form.quantity"
              />
            </div>
            <div class="w-1/4 px-3 mb-6 md:mb-0">
              <label
                :class="{
                  'block uppercase tracking-wide text-xs font-bold mb-2': true,
                  'text-gray-700': form.quantity_unit,
                  'text-red-500': !form.quantity_unit,
                }"
                for="grid-city"
              >
                単位
              </label>
              <select
                name=""
                id=""
                class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                v-model="form.quantity_unit"
              >
                <option
                  v-if="props.stock.solo_unit"
                  :value="props.stock.solo_unit"
                >
                  {{ props.stock.solo_unit }}
                </option>
                <option
                  v-if="props.stock.org_unit"
                  :value="props.stock.org_unit"
                >
                  {{ props.stock.org_unit }}
                </option>
              </select>
            </div>

            <div class="w-1/2 px-3 mb-6 md:mb-0">
              <label
                :class="{
                  'block uppercase tracking-wide text-xs font-bold mb-2': true,
                  'text-gray-700': form.desire_delivery_date,
                  'text-red-500': !form.desire_delivery_date,
                }"
                for="grid-state"
              >
                希望納期
              </label>
              <input
                class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                id="grid-city"
                type="date"
                v-model="form.desire_delivery_date"
              />
              <p class="mt-2 text-red-500 text-xs italic">
                リードタイムの都合上難しい場合がございます。
              </p>
            </div>
          </div>

          <!-- <div class="flex flex-wrap -mx-3 mb-2">
            <div class="w-1/2 px-3 mb-6 md:mb-0">
              <label
                class="block uppercase tracking-wide text-gray-500 text-xs font-bold mb-2"
                for="grid-city"
              >
                単価
              </label>
              <input
                class="pointer-events-none appearance-none block w-full bg-gray-300 text-gray-500 border border-gray-300 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                id="grid-city"
                type="number"
                v-model="form.price"
              />
            </div>
            <div class="w-1/2 px-3 mb-6 md:mb-0">
              <label
                class="block uppercase tracking-wide text-gray-500 text-xs font-bold mb-2"
                for="grid-city"
              >
                金額
              </label>
              <input
                class="pointer-events-none appearance-none block w-full bg-gray-300 text-gray-500 border border-gray-300 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                id="grid-city"
                type="number"
                v-model="form.calc_price"
              />
            </div>
          </div> -->
          <!-- <div class="flex flex-wrap -mx-3 mb-2 mt-4">
            <div class="w-full px-3 my-6 md:mb-0">
              <label
                class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2"
                for="grid-state"
              >
                上位役職者確認
              </label>

              <p>
                上位役職者の確認を頂いた後、以下のボタンを押してください。
              </p>
              <label class="inline-flex items-center cursor-pointer">
                <input
                  type="checkbox"
                  class="sr-only peer"
                  @change="handleCheck($event)"
                />
                <div
                  class="relative w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:start-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600 dark:peer-checked:bg-blue-600"
                ></div>
                <span
                  class="ms-3 text-sm font-medium text-gray-900 dark:text-gray-300"
                ></span>
              </label>
            </div>
          </div> -->
        </div>

        <div class="w-1/2 pl-4">
          <div class="w-full">
            <label
              for="grid-password"
              :class="{
                'block uppercase tracking-wide text-xs font-bold mb-2': true,
                'text-gray-700': form.description,
                'text-red-500': !form.description,
              }"
            >
              備考（使用用途を記載）
            </label>
            <textarea
              name=""
              id=""
              cols="30"
              rows="9"
              v-model="form.description"
              class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
            ></textarea>
          </div>
        </div>
      </div>

      <button
        v-if="
          form.user_id &&
          form.quantity &&
          form.desire_delivery_date &&
          form.description
        "
        class="mt-4 w-full bg-blue-500 hover:bg-blue-700 text-white font-bold py-6 px-4 rounded"
        @click.prevent="handleSubmit"
      >
        物品依頼
      </button>
    </form>
  </div>
</template>
<style scoped style="scss">
#stock_request_container {
  background-color: rgb(255, 255, 255);
  padding: 1rem;
  border-radius: 5px;
  box-shadow: rgba(99, 99, 99, 0.2) 0px 2px 8px 0px;
}
</style>