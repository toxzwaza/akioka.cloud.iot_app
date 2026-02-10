<script setup>
import { DotLottieVue } from "@lottiefiles/dotlottie-vue";
import { onMounted, ref, reactive } from "vue";
import axios from "axios";

const props = defineProps({
  always_orders: Array,
});

const popup_dialog = ref(false);
const users = ref([]);
const orders = ref([]);

const duty_users = ref([]);

const time_flg = ref(false);

const getUsers = () => {
  axios
    .get(route("lunch.getUsers"))
    .then((res) => {
      console.log(res.data);
      users.value = res.data;
    })
    .catch((error) => {
      console.log(error);
    });
};
const getOrders = () => {
  axios
    .get(route("lunch.getOrders"))
    .then((res) => {
      orders.value = res.data;
      console.log(orders.value);
    })
    .catch((error) => {
      console.log(error);
    });
};

const available_scan = ref(false);
const form = reactive({
  user_id: null,
  user_name: null,
  order_flg: null,
  receive_flg: null,
  order_id: null,
});
const handleChangeUserId = () => {
  if (form.user_id) {
    checkTimeFlg();
    const user = users.value.find((user) => user.id == form.user_id);
    console.log(user);
    if (user) {
      form.user_name = user.name;
      form.duty_flg = user.duty_flg;
      available_scan.value = false;
      checkOrder(form.user_id);
    }
    if (user.duty_flg) {
      // alert("あなたは本日のポット清掃当番です");
      popup_dialog.value = true;
    }
  } else {
    scan_input_available();
  }
};

const checkOrder = (user_id) => {
  const order = orders.value.find((order) => order.user_id == user_id);
  if (order) {
    form.order_flg = order.order_flg;
    form.order_id = order.id;
    console.log(order);
    if (time_flg.value == "receive" && !order.order_flg) {
      alert("あなたは本日注文していません。");
      clearForm();
    }
  } else {
    form.order_flg = 0;
  }
};

const scan_input_available = () => {
  const qr_reader_input = document.querySelector("#qr-reader-input");
  if (document.activeElement !== qr_reader_input) {
    qr_reader_input.focus();
    available_scan.value = true;
  }
};

// 注文
const sendOrder = (type) => {
  switch (type) {
    case "order":
    case "cancel":
      axios
        .post(route("lunch.order"), {
          user_id: form.user_id,
          order_flg: form.order_flg,
        })
        .then((res) => {
          console.log(res.data);
          clearForm();
        });
      break;
    case "receive":
      axios
        .post(route("lunch.receive"), {
          order_id: form.order_id,
        })
        .then((res) => {
          console.log(res.data);
          clearForm();
        });
      break;
  }
};

const clearForm = () => {
  // form.user_id = null;
  // form.user_name = null;
  // form.order_flg = null;
  // form.receive_flg = null;
  // form.order_id = null;
  // scan_input_available();
  window.location.reload();
};

const checkTimeFlg = () => {
  let test_time = null;
  // test_time = "2025-01-01 8:00:00";
  const now = test_time ? new Date(test_time) : new Date();
  const hours = now.getHours();
  const minutes = now.getMinutes();

  // 0:00から8:50の間かチェック
  if (hours === 0 || hours < 8 || (hours === 8 && minutes <= 50)) {
    time_flg.value = "order";
  } else if (hours >= 9 && hours < 18) {
    time_flg.value = "receive";
  }
  console.log(time_flg.value);
};

const checkTimeFlagInterval = () => {
  setInterval(() => {
    window.location.reload();
  }, 360000);
};

const setDutyUsers = (users) => {
  console.log("🔍 [DEBUG] setDutyUsers 開始");
  console.log("🔍 [DEBUG] 引数 users:", users);
  console.log("🔍 [DEBUG] users の型:", typeof users);
  console.log("🔍 [DEBUG] users が配列か:", Array.isArray(users));
  console.log("🔍 [DEBUG] users.length:", users?.length);
  
  if (!users || !Array.isArray(users)) {
    console.error("❌ [DEBUG] users が配列ではありません、または undefined/null です");
    return;
  }
  
  if (users.length === 0) {
    console.warn("⚠️ [DEBUG] users が空の配列です");
    return;
  }
  
  // duty_users をリセット
  duty_users.value = [];
  console.log("🔍 [DEBUG] duty_users をリセットしました");
  
  let count = 0;
  let dutyFoundCount = 0;
  
  for (let i = 0; i < users.length; i++) {
    console.log(`🔍 [DEBUG] ループ ${i}:`, {
      user: users[i],
      duty_flg: users[i]?.duty_flg,
      duty_flg_type: typeof users[i]?.duty_flg,
      duty_flg_strict: users[i]?.duty_flg === 1,
    });
    
    if (users[i].duty_flg === 1) {
      dutyFoundCount++;
      console.log(`✅ [DEBUG] duty_flg === 1 のユーザーを発見 (${i}番目):`, users[i]);
      
      duty_users.value.push(users[i]);
      console.log(`🔍 [DEBUG] duty_users に追加 (1人目):`, users[i]);
      
      if (users[i + 1]) {
        duty_users.value.push(users[i + 1]);
        console.log(`🔍 [DEBUG] duty_users に追加 (2人目):`, users[i + 1]);
      } else {
        duty_users.value.push(users[count]);
        console.log(`🔍 [DEBUG] duty_users に追加 (2人目 - 折り返し):`, users[count]);
        count++;
      }

      if (users[i + 2]) {
        duty_users.value.push(users[i + 2]);
        console.log(`🔍 [DEBUG] duty_users に追加 (3人目):`, users[i + 2]);
      } else {
        duty_users.value.push(users[count]);
        console.log(`🔍 [DEBUG] duty_users に追加 (3人目 - 折り返し):`, users[count]);
        count++;
      }

      if (users[i + 3]) {
        duty_users.value.push(users[i + 3]);
        console.log(`🔍 [DEBUG] duty_users に追加 (4人目):`, users[i + 3]);
      } else {
        duty_users.value.push(users[count]);
        console.log(`🔍 [DEBUG] duty_users に追加 (4人目 - 折り返し):`, users[count]);
        count++;
      }
    }
  }
  
  console.log("🔍 [DEBUG] setDutyUsers 終了");
  console.log("🔍 [DEBUG] duty_flg === 1 のユーザー数:", dutyFoundCount);
  console.log("🔍 [DEBUG] 最終的な duty_users.value:", duty_users.value);
  console.log("🔍 [DEBUG] duty_users.value.length:", duty_users.value.length);
};

onMounted(() => {
  getUsers();
  getOrders();

  setDutyUsers(props.always_orders);

  scan_input_available();
  checkTimeFlg();

  checkTimeFlagInterval();
});
</script>
<template>
  <main class="p-8">
    <div class="flex justify-between items-start">
      <div id="left_container" class="w-1/3">
        <div
          class="table_container relative overflow-x-auto shadow-md sm:rounded-lg"
        >
          <table
            class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400"
          >
            <thead
              class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400"
            >
              <tr>
                <th scope="col" class="px-6 py-3">名前</th>
                <th scope="col" class="px-6 py-3">ステータス</th>
              </tr>
            </thead>
            <tbody>
              <tr
                class="odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800 border-b dark:border-gray-700 border-gray-200 text-xl font-bold"
                v-for="order in orders"
                :key="order.id"
              >
                <td class="px-6 py-4 order_name">
                  <span
                    v-if="order.duty_flg"
                    class="duty_user inline-flex items-center bg-red-50 text-red-600 text-xs font-medium mr-2 pl-2 pr-2.5 rounded-full py-1"
                  >
                    <span
                      class="w-1 h-1 mr-1 rounded-full bg-red-400 flex"
                    ></span
                    >ポット登板
                  </span>
                  <span class="opacity-40">[{{ order.user_id }}]</span
                  >{{ ` ${order.user_name}` }}
                </td>
                <td
                  :class="{
                    'px-6 py-8 font-bold': true,
                    'text-blue-500': order.order_flg == 1,
                    'text-red-500': order.order_flg == 0,
                    'text-green-500': order.receive_flg == 1,
                  }"
                >
                  {{
                    order.receive_flg === 1
                      ? "受け取り"
                      : order.order_flg === 1
                      ? "注文"
                      : "キャンセル"
                  }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div id="right_container" class="w-2/3 py-8 pl-8">
        <div id="system_msg" class="font-bold text-2xl mb-4">
          <h3 v-if="time_flg == 'order'" class="text-blue-500">
            弁当注文時間内です。
          </h3>
          <h3 v-else-if="time_flg == 'receive'" class="text-green-500">
            弁当受け取り時間内です。
          </h3>
          <h3 v-else class="text-gray-500">弁当注文システム利用時間外です。</h3>
        </div>

        <div class="flex justify-start items-center">
          <input
            v-model="form.user_id"
            type="number"
            name=""
            id="qr-reader-input"
            class="text-center mr-2 appearance-none block w-2/3 bg-gray-200 text-gray-700 border border-gray-200 rounded py-4 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500 text-xl font-bold"
            @change="handleChangeUserId"
          />
          <button
            class="w-1/3 bg-blue-500 hover:bg-blue-700 text-white py-4 px-4 rounded whitespace-nowrap font-bold text-xl"
            @click.prevent="clearForm"
          >
            再スキャン
          </button>
        </div>

        <div v-if="available_scan" class="content">
          <h1 class="text-center font-bold text-4xl text-red-500">
            QRコードをスキャンしてください
          </h1>
          <DotLottieVue
            class="mx-auto"
            style="height: 400px; width: 400px"
            autoplay
            loop
            src="https://lottie.host/061aefbf-c9fb-4081-bd58-fb534a5e2c9c/WwBjZin5yr.lottie"
          />
        </div>
        <div v-else class="content">
          <h1 class="text-center font-bold text-8xl text-blue-500 mt-16">
            {{ form.user_name }}
          </h1>
          <div
            v-if="form.order_flg === 0 || form.order_flg === 1"
            class="flex justify-center mt-24 button_content w-full"
          >
            <button
              v-if="!form.order_flg && time_flg == 'order'"
              @click.prevent="sendOrder('order')"
              class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-12 px-16 rounded text-4xl w-4/5"
            >
              注文
            </button>
            <button
              v-else-if="form.order_flg && time_flg == 'order'"
              @click.prevent="sendOrder('cancel')"
              class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-12 px-16 rounded text-4xl w-4/5"
            >
              キャンセル
            </button>

            <button
              v-else-if="
                form.order_flg && !form.receive_flg && time_flg == 'receive'
              "
              @click.prevent="sendOrder('receive')"
              class="bg-green-500 hover:bg-green-700 text-white font-bold py-12 px-16 rounded text-4xl w-4/5"
            >
              受け取り
            </button>
          </div>
        </div>

        <div id="button_content" class="">
          <h2 class="text-xl font-bold text-red-500">ポット当番</h2>

          <div
            v-if="duty_users.length > 0"
            class="duty_container flex justify-between items-center flex-wrap text-gray-700"
          >
            <span
              :class="{
                'py-2 whitespace-nowrap text-3xl font-bold inline-block w-1/2': true,
                'text-red-500 text-4xl': i == 0,
              }"
              v-for="(user, i) in duty_users"
              :key="i"
              >{{ `(${i + 1})${user.name}` }}</span
            >
          </div>
        </div>
      </div>
    </div>

    <!-- Main modal -->
    <div
      v-if="popup_dialog"
      id="popup-modal"
      tabindex="-1"
      aria-hidden="true"
      class="overflow-y-auto overflow-x-hidden z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full"
    >
      <div id="modal_content">
        <div class="relative p-4 w-full">
          <!-- Modal content -->
          <div class="relative bg-white rounded-lg shadow-sm dark:bg-gray-700 p-4">
            <!-- Modal header -->
            <div
              class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600 border-gray-200"
            >
              <h3 class="text-3xl font-semibold text-gray-900 dark:text-white">
                本日のポット清掃当番です。
              </h3>
              <button
                type="button"
                class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white"
                data-modal-hide="default-modal"
              >

                <span class="sr-only">Close modal</span>
              </button>
            </div>
            <!-- Modal body -->
            <div class="p-4 md:p-5 space-y-4">
              <p
                class="text-xl leading-relaxed text-gray-500 dark:text-gray-400"
              >
                出勤時、ポットに水が入っていない場合はお湯を入れてください。
              </p>
              <p
                class="text-xl leading-relaxed text-gray-500 dark:text-gray-400"
              >
                退勤時、ポットからコンセントを抜いてください。<br />
                また、ポットのお湯を流しに捨て、内部を水道で綺麗に清掃してください。
              </p>
            </div>
            <!-- Modal footer -->
            <div
              class="flex items-center p-4 md:p-5 border-t border-gray-200 rounded-b dark:border-gray-600"
            >
              <button
                @click="popup_dialog = false"
                data-modal-hide="default-modal"
                type="button"
                class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-lg px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
              >
                確認しました
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>
</template>
<style scoped lang="scss">
main {
  height: 100vh;
  width: 100vw;
  overflow: hidden;
  background-color: #f8f8f8;
  position: relative;

  & #popup-modal {
    position: fixed;
    top: 0;
    left: 0;
    background-color: rgba(62, 62, 62, 0.46);

    & #modal_content {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
    }
  }
}
#left_container {
  & .table_container {
    height: 94vh;
    overflow-y: auto;

    & .order_name {
      position: relative;
      & .duty_user {
        position: absolute;
        top: 6%;
        left: 8%;
      }
    }

    &::-webkit-scrollbar {
      width: 12px;
    }

    &::-webkit-scrollbar-track {
      background: #ffffff;
      border-radius: 4px;
    }

    &::-webkit-scrollbar-thumb {
      background: #28ffbf;
      border-radius: 4px;
    }
  }
}
#right_container {
  position: relative;
  padding: 2% 4% 4% 4%;

  & .content {
    height: 70vh;
    padding: 4%;
    position: relative;
    & .button_content {
      position: absolute;
      bottom: 30%;
      left: 0;
    }
  }
  & #button_content {
    position: absolute;
    bottom: 4%;
    left: 4%;
    width: 96%;
    padding: 2%;
    box-shadow: rgba(99, 99, 99, 0.2) 0px 2px 8px 0px;
    border-radius: 5px;
    background-color: #ebebeb;
  }

  & #system_msg {
    width: 100%;
    height: 100px;
    line-height: 100px;
    overflow: hidden;
    background: white;

    & h3 {
      animation: animetxt 15s linear infinite;
      transform: translateX(100%);
    }
    @keyframes animetxt {
      100% {
        transform: translateX(-40%);
      }
    }
  }
}

.duty_container {
  width: 90%;
}
</style>
