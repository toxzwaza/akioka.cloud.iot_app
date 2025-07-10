<script setup>
import StockLayout from "@/Layouts/StockLayout.vue";
import { Link } from "@inertiajs/vue3";
import { reactive, ref, onMounted } from "vue";
import axios from "axios";
import { messaging, getToken } from "@/Firebase/firebase";
import { onMessage } from "firebase/messaging";

const deviceId = ref(null);
const inputId = ref("");
const token = ref("");

const device_messages = reactive({
  status: false,
  messages: [],
});

const device_message_method = {
  confirm_message: (device_message_id) => {
    let all_confirm_flg = false;
    let device_message_ids = [];

    if (!device_message_id) {
      device_message_ids = device_messages.messages.map(
        (message) => message.id
      );
      all_confirm_flg = true;
      console.log(device_message_ids);
    } else {
      device_message_ids.push(device_message_id);
    }

    axios
      .post(route("device-message.confirm_message"), {
        device_massage_ids: device_message_ids,
      })
      .then((res) => {
        console.log(res.data);
        if (res.data.status) {
          if (all_confirm_flg) {
            device_messages.status = false;
          } else {
            device_messages.messages = device_messages.messages.filter(
              (message) => message.id !== device_message_id
            );
          }
        }
      });
  },
  send_answer: (device_message_id) => {
    console.log(device_message_id);
    const device_message = device_messages.messages.find(
      (message) => message.id === device_message_id
    );
    if (device_message && device_message.answer) {
      axios
        .post(route("device-message.send_answer"), {
          device_message_id: device_message_id,
          answer: device_message.answer,
        })
        .then((res) => {
          console.log(res.data);
          if (res.data.status) {
            alert("メッセージを送信しました。");
            device_messages.messages = device_messages.messages.filter(
              (message) => message.id !== device_message_id
            );
          }
        });
    }
  },
};

// デバイスID・トークン作成用API通信
const loginAndCreateTokenWithDeviceId = () => {
  console.log("test");

  try {
    axios
      .post(route("device-login"), {
        name: inputId.value,
        token: token.value,
      })
      .then((res) => {
        console.log(res.data);
        if (res.data.status) {
          localStorage.setItem("device_id", inputId.value);
          deviceId.value = inputId.value;
        }
      });
  } catch (error) {
    console.error("ログイン失敗:", error);
  }
};

// トークン取得
const getFCMToken = async () => {
  try {
    const currentToken = await getToken(messaging, {
      vapidKey:
        "BAFiNQy1EiKe3dMiEdWTWw00FegkQc4uUvoaG8YPCPuAMD86GQPKpZRXkZALHqEsaS7-1R-3xGopdqyflwqGZpg",
    });

    if (currentToken) {
      console.log("取得したトークン:", currentToken);
      return currentToken;
    } else {
      console.warn("トークンが取得できませんでした");
      return null;
    }
  } catch (error) {
    console.error("トークン取得時にエラーが発生:", error);
    return null;
  }
};

// デバイスメッセージ取得
const getDeviceMessages = () => {
  axios
    .get(route("device-message.getDeviceMessages"), {
      params: {
        device_name: deviceId.value,
      },
    })
    .then((res) => {
      console.log(res.data);
      device_messages.messages = res.data;
      if (res.data.length > 0) {
        device_messages.status = true;
      }
    });
};

// 初期処理：localStorageから読み込み
onMounted(() => {
  const savedId = localStorage.getItem("device_id");
  if (savedId && savedId != "null") {
    deviceId.value = savedId;

    // 通知を取得する処理
    getDeviceMessages();
  } else {
    inputId.value = prompt("デバイスIDを設定してください。");
    getFCMToken().then((fetchedToken) => {
      token.value = fetchedToken;

      if (inputId.value && token.value) {
        loginAndCreateTokenWithDeviceId();
      }
    });
  }

  getFCMToken();
});

onMessage(messaging, (payload) => {
  // alert(
  //   `📩 フォアグラウンド通知を受信しました: ${payload.notification.title}\n ${payload.notification.body}`
  // );
  window.location.reload();
});
</script>
<template>
  <StockLayout :title="'在庫管理システム'">
    <template #content>
      <p class="text-gray-700 mb-4 text-left ml-4">
        device_id : {{ deviceId }}
      </p>
      <div
        id="icon_container"
        :class="{ 'opacity-20': device_messages.status }"
      >
        <!-- 検索画面 -->
        <div class="w-1/2 p-4">
          <Link :href="route('stock.search')"
            ><img class="" src="/images/stocks/icons/search.png" alt="検索画面"
          /></Link>
        </div>
        <!-- 出庫画面 -->
        <div class="w-1/2 p-4">
          <Link :href="route('stock.shipment')"
            ><img
              class=""
              src="/images/stocks/icons/shipment.png"
              alt="出庫画面"
          /></Link>
        </div>

        <!-- 物品依頼 -->
        <div class="w-1/2 p-4">
          <a :href="route('stock.new_item.home')"
            ><img
              class=""
              src="/images/stocks/icons/approval.png"
              alt="物品依頼"
          /></a>
        </div>

        <!-- 物品依頼 -->
        <div class="w-1/2 p-4">
          <a :href="route('stock.check_order_request.home')"
            ><img
              class=""
              src="/images/stocks/icons/check-order-request.png"
              alt="物品依頼"
          /></a>
        </div>

        <!-- 定期物品依頼 -->
        <div class="w-1/2 p-4">
          <a :href="route('stock.request.home')"
            ><img
              class=""
              src="/images/stocks/icons/per_stock_request.png"
              alt="現場物品依頼"
          /></a>
        </div>

        <!-- 納品画面 -->
        <div class="w-1/2 p-4">
          <Link :href="route('stock.receive.home')"
            ><img
              class=""
              src="/images/stocks/icons/receive.png"
              alt="納品画面"
          /></Link>
        </div>

        <!-- 滞留画面 -->
        <div class="w-1/2 p-4">
          <a :href="route('stock.retention.home')"
            ><img
              class=""
              src="/images/stocks/icons/retention.png"
              alt="滞留画面"
          /></a>
        </div>

        <!-- 発注画面 -->
        <!-- <div class="w-1/2 p-4">
          <Link :href="route('stock.order.create')"
            ><img class="" src="/images/stocks/icons/order.png" alt="発注画面"
          /></Link>
        </div> -->
        <!-- 在庫追加画面 -->
        <!-- <div class="w-1/2 p-4">
          <Link :href="route('stock.inventory.create')"><img class="" src="/images/stocks/icons/inventory.png" alt="在庫追加画面" /></Link>
        </div> -->
      </div>

      <div v-if="device_messages.status" id="device_message">
        <div class="flex justify-end">
          <button
            @click="device_messages.status = false"
            class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
          >
            <i class="fas fa-times"></i>
          </button>
        </div>
        <h2 class="text-center text-red-500 font-bold text-lg mb-8">
          <i class="fas fa-envelope mr-2"></i> 新着メッセージがあります。
        </h2>
        <div class="flex justify-end mb-8">
          <button
            class="mt-2 bg-blue-700 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
            @click="device_message_method.confirm_message(null)"
          >
            すべて確認しました
          </button>
        </div>

        <div class="msg_container">
          <div
            v-for="message in device_messages.messages"
            :key="message.id"
            :class="{
              'flex items-start p-4 mb-4 text-sm border rounded-lg': true,
              'text-gray-800 border-gray-300 bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:border-blue-800':
                message.priority === 0,
              'text-yellow-800 border-yellow-300 bg-yellow-50 dark:bg-gray-800 dark:text-yellow-400 dark:border-yellow-800':
                message.priority === 1,
              'text-red-800 border-red-300 bg-red-50 dark:bg-gray-800 dark:text-red-400 dark:border-red-800':
                message.priority === 2,
              'text-green-800 border-green-300 bg-green-50 dark:bg-gray-800 dark:text-green-400 dark:border-green-800':
                ![0, 1, 2].includes(message.priority),
            }"
            role="alert"
          >
            <i
              v-if="message.priority === 0"
              class="fas fa-info-circle shrink-0 inline w-4 h-4 me-3"
              aria-hidden="true"
            ></i>
            <i
              v-else-if="message.priority === 1"
              class="fas fa-exclamation-triangle shrink-0 inline w-4 h-4 me-3"
              aria-hidden="true"
            ></i>
            <i
              v-else-if="message.priority === 2"
              class="fas fa-exclamation-circle shrink-0 inline w-4 h-4 me-3"
              aria-hidden="true"
            ></i>
            <i
              v-else
              class="fas fa-question-circle shrink-0 inline w-4 h-4 me-3"
              aria-hidden="true"
            ></i>
            <div class="font-medium w-full">
              <div class="flex justify-between mb-2">
                <div class="flex">
                  <p class="mr-4">From: {{ message.from_user_name }}</p>
                  <p>To: {{ message.to_user_name }}</p>
                </div>

                <p>
                  送信日時:
                  {{
                    new Date(message.created_at).toLocaleString("ja-JP", {
                      year: "numeric",
                      month: "2-digit",
                      day: "2-digit",
                      hour: "2-digit",
                      minute: "2-digit",
                    })
                  }}
                </p>
              </div>

              <p v-html="message.message.replace(/\n/g, '<br>')"></p>

              <hr class="mt-4 mb-8" />
              <form class="max-full mb-8">
                <label
                  for="default-search"
                  class="mb-2 text-sm font-medium text-gray-900 sr-only dark:text-white"
                  >Search</label
                >

                <p class="text-gray-700 mb-2">以下より返信が可能です。</p>
                <div class="relative">
                  <div
                    class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none"
                  >
                    <i class="fas fa-envelope mr-2"></i>
                  </div>

                  <input
                    type="search"
                    id="default-search"
                    class="block w-full p-4 ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                    placeholder="メッセージを送信"
                    required
                    v-model="message.answer"
                  />
                  <button
                    @click.prevent="
                      device_message_method.send_answer(message.id)
                    "
                    type="submit"
                    class="text-white absolute end-2.5 bottom-2.5 bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
                  >
                    <i class="fas fa-paper-plane"></i>
                  </button>
                </div>
              </form>

              <div class="flex justify-end">
                <button
                  class="mt-2 bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                  @click="device_message_method.confirm_message(message.id)"
                >
                  確認しました
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </StockLayout>
</template>
<style scoped lang="scss">
#icon_container {
  display: flex;
  flex-wrap: wrap;
}

#device_message {
  position: fixed;
  top: 10%;
  left: 50%;
  transform: translateX(-50%);
  z-index: 99;
  background-color: rgb(255, 255, 255);
  height: 80vh;
  width: 90vw;
  padding: 2%;
  overflow-y: scroll;
  box-shadow: rgba(99, 99, 99, 0.2) 0px 2px 8px 0px;
  border-radius: 10px;
}
</style>
