<script setup>
import { onMounted, ref, reactive } from "vue";
import axios from "axios";

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
  description: "",
  price: null,
  calc_price: null,
  check: null,
});

// 特殊フォーム用
const special_area = reactive({
  cd_1: {
    text_1: 0,
    text_2: null,
    add_description() {
      if (form.description) {
        form.description =
          form.description +
          `\n${special_area.cd_1.text_1} - ${special_area.cd_1.text_2}`;
      } else {
        form.description =
          form.description +
          `${special_area.cd_1.text_1} - ${special_area.cd_1.text_2}`;
      }
    },
  },
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

// 日本の祝日を計算式で判定（フォールバック用）
const isJapaneseHolidayByCalculation = (date) => {
  const year = date.getFullYear();
  const month = date.getMonth() + 1;
  const day = date.getDate();
  
  // 固定祝日
  const fixedHolidays = [
    { month: 1, day: 1 },      // 元日
    { month: 2, day: 11 },     // 建国記念の日
    { month: 4, day: 29 },     // 昭和の日
    { month: 5, day: 3 },      // 憲法記念日
    { month: 5, day: 4 },      // みどりの日
    { month: 5, day: 5 },      // こどもの日
    { month: 8, day: 11 },     // 山の日
    { month: 11, day: 3 },     // 文化の日
    { month: 11, day: 23 },    // 勤労感謝の日
  ];
  
  for (const holiday of fixedHolidays) {
    if (month === holiday.month && day === holiday.day) {
      return true;
    }
  }
  
  // 天皇誕生日
  if (year >= 2020 && month === 2 && day === 23) {
    return true; // 令和の天皇誕生日
  } else if (year >= 1989 && year <= 2018 && month === 12 && day === 23) {
    return true; // 平成の天皇誕生日
  }
  
  // 春分の日（2000-2099年）
  const springEquinox = getSpringEquinox(year);
  if (month === 3 && day === springEquinox) return true;
  
  // 秋分の日（2000-2099年）
  const autumnEquinox = getAutumnEquinox(year);
  if (month === 9 && day === autumnEquinox) return true;
  
  // 移動祝日
  // 海の日（7月の第3月曜日、2020年は7月23日、2021年は7月22日）
  if (year === 2020 && month === 7 && day === 23) return true;
  if (year === 2021 && month === 7 && day === 22) return true;
  if (year !== 2020 && year !== 2021 && month === 7 && day === getNthMonday(year, 7, 3)) return true;
  
  // 敬老の日（9月の第3月曜日）
  if (month === 9 && day === getNthMonday(year, 9, 3)) return true;
  
  // スポーツの日（10月の第2月曜日、2020年は7月24日、2021年は7月23日）
  if (year === 2020 && month === 7 && day === 24) return true;
  if (year === 2021 && month === 7 && day === 23) return true;
  if (year !== 2020 && year !== 2021 && month === 10 && day === getNthMonday(year, 10, 2)) return true;
  
  return false;
};

// 春分の日を計算（2000-2099年）
const getSpringEquinox = (year) => {
  if (year >= 2000 && year <= 2099) {
    const base = Math.floor((year - 2000) * 0.242194) + Math.floor((year - 2000) / 4);
    return 20 + base;
  }
  return 20;
};

// 秋分の日を計算（2000-2099年）
const getAutumnEquinox = (year) => {
  if (year >= 2000 && year <= 2099) {
    const base = Math.floor((year - 2000) * 0.242194) + Math.floor((year - 2000) / 4);
    return 23 + base;
  }
  return 23;
};

// 第N月曜日を取得
const getNthMonday = (year, month, n) => {
  const firstDay = new Date(year, month - 1, 1);
  const firstDayOfWeek = firstDay.getDay();
  const firstMonday = firstDayOfWeek === 0 ? 2 : (9 - firstDayOfWeek) % 7 || 7;
  return firstMonday + (n - 1) * 7;
};

// 日本の祝日APIを使用して祝日を判定
const isJapaneseHoliday = async (dateString) => {
  if (!dateString) return false;
  
  const date = new Date(dateString + "T00:00:00");
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  const dateStr = `${year}-${month}-${day}`;
  
  try {
    // 日本の祝日API（無料・メンテナンス不要）
    const response = await axios.get(`https://holidays-jp.github.io/api/v1/${year}/date.json`);
    const holidays = response.data;
    
    // 指定された日付が祝日かどうかをチェック
    if (holidays && holidays.hasOwnProperty(dateStr)) {
      return true;
    }
    
    // APIにデータがない場合は計算式で判定
    return isJapaneseHolidayByCalculation(date);
  } catch (error) {
    console.error("祝日APIの取得に失敗しました:", error);
    // APIが失敗した場合は計算式で判定
    return isJapaneseHolidayByCalculation(date);
  }
};

// 土日祝を判定する関数
const isWeekendOrHoliday = async (dateString) => {
  if (!dateString) return false;
  
  const date = new Date(dateString + "T00:00:00");
  const dayOfWeek = date.getDay();
  
  // 土曜日（6）または日曜日（0）
  if (dayOfWeek === 0 || dayOfWeek === 6) {
    return true;
  }
  
  // 祝日
  const isHoliday = await isJapaneseHoliday(dateString);
  return isHoliday;
};

// 次の平日を取得する関数
const getNextWeekday = async (dateString) => {
  if (!dateString) return null;
  
  let date = new Date(dateString + "T00:00:00");
  let attempts = 0;
  const maxAttempts = 30; // 無限ループ防止
  
  while (attempts < maxAttempts) {
    const dateStr = date.toISOString().split("T")[0];
    const isHoliday = await isWeekendOrHoliday(dateStr);
    
    if (!isHoliday) {
      return dateStr;
    }
    
    date.setDate(date.getDate() + 1);
    attempts++;
  }
  
  return date.toISOString().split("T")[0];
};

// 希望納期変更時の処理
const handleDesireDeliveryDateChange = async (event) => {
  const selectedDate = event.target.value;
  
  if (!selectedDate) {
    return;
  }
  
  const isHoliday = await isWeekendOrHoliday(selectedDate);
  
  if (isHoliday) {
    const nextWeekday = await getNextWeekday(selectedDate);
    alert("土日祝は選択できません。次の平日に自動設定します。");
    form.desire_delivery_date = nextWeekday;
  }
};

const handleSubmit = () => {
  if (confirm("上位役職者の確認は完了していますか？")) {
    emit("submit", form);
  }
};

onMounted(async () => {
  process_users.value = props.users;

  if (props.stock) {
    // 現在個数と希望納期のデフォルトを設定
    console.log("StockRequest.vue:stock", props.stock);
    if (props.stock.stock_storage && props.stock.stock_storage.quantity) {
      form.now_quantity = props.stock.stock_storage.quantity;
    }
    if (props.stock.stock_supplier && props.stock.stock_supplier.lead_time) {
      shortest_date.value = new Date(
        Date.now() + props.stock.stock_supplier.lead_time * 24 * 60 * 60 * 1000
      )
        .toISOString()
        .split("T")[0];

      // 初期値が土日祝の場合は次の平日に設定
      const isHoliday = await isWeekendOrHoliday(shortest_date.value);
      if (isHoliday) {
        form.desire_delivery_date = await getNextWeekday(shortest_date.value);
      } else {
        form.desire_delivery_date = shortest_date.value;
      }
    }

    form.price = props.stock.price;
    form.calc_price = form.price * form.quantity;

    if (props.request_user) {
      form.process_id = props.request_user.process_id;
      form.user_id = props.request_user.id;
      handleUser();
    }

    if(props.stock.re_order_request){
      const ror = props.stock.re_order_request;

      form.user_id = ror.request_user_id 
      form.now_quantity = ror.now_quantity
      // form.now_quantity_unit = ror.now_quantity_unit
      form.digest_date = ror.digest_date
      form.quantity = ror.quantity
      // form.quantity_unit = ror.quantity_unit
      
      // 再依頼時の希望納期も土日祝チェック
      if (ror.desire_delivery_date) {
        const isHoliday = await isWeekendOrHoliday(ror.desire_delivery_date);
        if (isHoliday) {
          form.desire_delivery_date = await getNextWeekday(ror.desire_delivery_date);
        } else {
          form.desire_delivery_date = ror.desire_delivery_date;
        }
      }
      
      form.description = ror.description
      form.price = ror.price
      form.calc_price = ror.calc_price
    }
  }
});
</script>
<template>
  <div id="stock_request_container" class="mt-8 mb-8 bg-gray-50 p-2 rounded">
    <h1 class="text-xl mb-4 text-gray-600 font-bold">物品依頼</h1>
    
    <!-- 再依頼モードの表示 -->
    <div v-if="props.stock && props.stock.re_order_request" class="bg-orange-100 border-l-4 border-orange-500 text-orange-700 p-4 mb-6">
      <div class="flex">
        <div class="flex-shrink-0">
          <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
          </svg>
        </div>
        <div class="ml-3">
          <p class="text-sm font-medium">
            <strong>再依頼モード</strong>
          </p>
          <p class="mt-1 text-sm">
            前回の依頼内容を引き継いで再依頼を行います。内容を確認・修正してから送信してください。
          </p>
        </div>
      </div>
    </div>

    <form class="w-ful">
      <div class="flex justify-between items-start">
        <div class="w-1/2 pr-4">
          <div class="flex flex-wrap -mx-3 mb-4">
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
          <div class="flex flex-wrap -mx-3 mb-4">
            <div class="w-1/4 px-3 mb-6 md:mb-0">
              <label
                :class="{
                  'block uppercase tracking-wide text-xs font-bold mb-2': true,
                  'text-gray-700':
                    form.now_quantity !== null &&
                    form.now_quantity !== undefined,
                  'text-red-500':
                    form.now_quantity === null ||
                    form.now_quantity === undefined,
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
                for="desire_delivery_date"
              >
                希望納期
              </label>
              <input
                class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                id="desire_delivery_date"
                type="date"
                v-model="form.desire_delivery_date"
                @change="handleDesireDeliveryDateChange"
              />
              <p class="mt-2 text-red-500 text-xs italic">
                リードタイムの都合上難しい場合がございます。土日祝は選択できません。
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
          <div v-if="props.stock.special_area_cd == 1" class="">
            <div class="flex items-end mb-4">
              <div class="w-1/6 mr-4">
                <label
                  class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2"
                  for="grid-first-name"
                >
                  文字
                </label>
                <select
                  name=""
                  id=""
                  class="appearance-none block w-full bg-gray-200 text-gray-700 border-transparent rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white"
                  v-model="special_area.cd_1.text_1"
                >
                  <option value="0">未選択</option>
                  <option
                    v-for="char in 'ABCDEFGHIJKLMNOPQRSTUVWXYZ012345678'"
                    :key="char"
                    :value="char"
                  >
                    {{ char }}
                  </option>
                </select>
              </div>
              <div class="w-1/6 mr-4">
                <label
                  class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2"
                  for="grid-first-name"
                >
                  数量
                </label>
                <input
                  type="number"
                  name=""
                  id=""
                  v-model="special_area.cd_1.text_2"
                  class="ppearance-none block w-full bg-gray-200 text-gray-700 border-transparent rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white"
                />
              </div>

              <div class="w-1/6 mr-4 flex items-end">
                <button
                  v-if="special_area.cd_1.text_1 && special_area.cd_1.text_2"
                  @click="special_area.cd_1.add_description"
                  type="button"
                  class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                >
                  追加
                </button>
              </div>
            </div>
          </div>

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
              rows="8"
              v-model="form.description"
              class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
            ></textarea>
          </div>
        </div>
      </div>

      <button
        v-if="form.user_id && form.quantity && form.desire_delivery_date"
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