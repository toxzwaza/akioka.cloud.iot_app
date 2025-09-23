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

// 過去メッセージ用
const showHistoryModal = ref(false);
const allMessages = ref([]);
const historyLoading = ref(false);

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
          // 既存のdevice_idでも新規でも、常にlocalStorageを更新
          localStorage.setItem("device_id", inputId.value);
          deviceId.value = inputId.value;
          
          // サーバーからのメッセージを表示
          if (res.data.msg) {
            alert(res.data.msg);
          }
          
          // 最終アクセス日を更新
          updateLastAccessDate();
          
          // メッセージを取得
          getDeviceMessages();
        } else {
          alert("デバイス登録に失敗しました: " + (res.data.msg || ""));
        }
      })
      .catch((error) => {
        console.error("ログイン失敗:", error);
        alert("通信エラーが発生しました。");
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

// デバイスメッセージ取得（未読のみ）
const getDeviceMessages = () => {
  axios
    .get(route("device-message.getDeviceMessages"), {
      params: {
        device_name: deviceId.value,
      },
    })
    .then((res) => {
      console.log(res.data);
      // read_flgが0の未読メッセージのみフィルタリング
      const unreadMessages = res.data.filter(message => message.read_flg === 0);
      device_messages.messages = unreadMessages;
      if (unreadMessages.length > 0) {
        device_messages.status = true;
      }
      
      // 全メッセージを保存（過去メッセージ表示用）
      allMessages.value = res.data;
    });
};

// 過去メッセージを表示
const showMessageHistory = () => {
  showHistoryModal.value = true;
};

// 過去メッセージモーダルを閉じる
const closeHistoryModal = () => {
  showHistoryModal.value = false;
};

// device_id リセット機能
const resetDeviceId = () => {
  const password = prompt("パスワードを入力してください:");
  
  if (password === "Akioka55") {
    // 正しいパスワードの場合
    localStorage.removeItem("device_id");
    alert("device_idを削除しました。ページをリロードします。");
    window.location.reload();
  } else if (password !== null) {
    // パスワードが間違っている場合（キャンセル時はnull）
    alert("パスワードが間違っています。");
  }
};

// デバイスの最終アクセス日を更新
const updateLastAccessDate = () => {
  if (deviceId.value) {
    axios
      .post(route("device-update-access"), {
        device_name: deviceId.value,
      })
      .then((res) => {
        console.log("最終アクセス日を更新:", res.data);
      })
      .catch((error) => {
        console.error("最終アクセス日更新エラー:", error);
      });
  }
};

// 初期処理：localStorageから読み込み
onMounted(() => {
  const savedId = localStorage.getItem("device_id");
  if (savedId && savedId != "null") {
    deviceId.value = savedId;

    // 既存のdevice_idの場合はトークンを再取得せず、基本機能のみ実行
    updateLastAccessDate();
    getDeviceMessages();
  } else {
    inputId.value = prompt("デバイスIDを設定してください。");
    
    // キャンセルが押された場合（inputId.value が null）
    if (inputId.value === null) {
      // エラーページにリダイレクト
      window.location.href = route('stock.device.error');
      return;
    }
    
    // 空文字列が入力された場合も同様に処理
    if (!inputId.value || inputId.value.trim() === "") {
      window.location.href = route('stock.device.error');
      return;
    }
    
    // 新規デバイスの場合のみトークンを取得してデバイス登録
    getFCMToken().then((fetchedToken) => {
      token.value = fetchedToken || "";
      // デバイスIDが設定されている場合のみ登録処理。トークン取得失敗でも続行
      if (inputId.value) {
        loginAndCreateTokenWithDeviceId();
      }
    });
  }
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
      <!-- ヘッダー部分 -->
      <div class="flex justify-between items-center mb-4 mx-4">
        <button
          @click="resetDeviceId"
          class="text-gray-700 hover:text-gray-900 transition-colors duration-200 cursor-pointer underline-offset-2 hover:underline"
        >
          device_id : {{ deviceId }}
        </button>
        
        <!-- 過去メッセージボタン -->
        <button
          @click="showMessageHistory"
          class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200 shadow-sm"
        >
          <i class="fas fa-history mr-2 text-gray-500"></i>
          過去メッセージ
          <span v-if="allMessages.length > 0" class="ml-2 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-blue-100 bg-blue-600 rounded-full">
            {{ allMessages.length }}
          </span>
        </button>
      </div>
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

        <!-- 製品棚卸 -->
        <div class="w-1/2 p-4">
          <a :href="route('calc.home')"
            ><img
              class=""
              src="/images/stocks/icons/calc_stock.png"
              alt="製品棚卸"
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
              <!-- 稟議再作成用ボタン -->
              <Link
                v-if="message.link"
                :href="message.link"
                class="inline-block mt-4 mb-2 bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded"
              >
                関連リンク
              </Link>

              <hr class="mt-4 mb-6" />

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

      <!-- 過去メッセージモーダル -->
      <div 
        v-if="showHistoryModal" 
        class="fixed inset-0 bg-gray-600 bg-opacity-50 z-50"
        @click="closeHistoryModal"
      >
        <div 
          class="fixed top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white shadow-lg rounded-xl overflow-hidden"
          style="height: 80vh; width: 90vw; z-index: 99;"
          @click.stop
        >
          <!-- モーダルヘッダー -->
          <div class="flex items-center justify-between p-6 border-b border-gray-200 bg-gray-50">
            <div class="flex items-center">
              <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                <i class="fas fa-history text-blue-600"></i>
              </div>
              <div>
                <h3 class="text-xl font-semibold text-gray-900">過去メッセージ履歴</h3>
                <p class="text-sm text-gray-500">全{{ allMessages.length }}件のメッセージ</p>
              </div>
            </div>
            <button 
              @click="closeHistoryModal"
              class="text-gray-400 hover:text-gray-600 transition-colors duration-200 p-2 rounded-full hover:bg-gray-200"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>

          <!-- モーダルコンテンツ -->
          <div class="p-6 overflow-y-auto" style="height: calc(80vh - 120px);">
            <div v-if="allMessages.length === 0" class="text-center py-8">
              <i class="fas fa-inbox text-gray-400 text-4xl mb-4"></i>
              <p class="text-gray-500">メッセージがありません</p>
            </div>
            
            <div v-else class="space-y-4">
              <div
                v-for="message in allMessages"
                :key="message.id"
                :class="[
                  'p-4 border rounded-lg transition-colors duration-200',
                  // 優先度による背景色（優先）
                  {
                    'bg-red-50 border-red-300': message.priority === 2,
                    'bg-yellow-50 border-yellow-300': message.priority === 1,
                    'bg-gray-50 border-gray-300': message.priority === 0,
                  },
                  // 既読・未読による背景色（優先度がない場合）
                  {
                    'bg-blue-50 border-blue-200': message.read_flg === 0 && ![0, 1, 2].includes(message.priority),
                    'bg-white border-gray-200': message.read_flg === 1 && ![0, 1, 2].includes(message.priority),
                  }
                ]"
              >
                <!-- メッセージヘッダー -->
                <div class="flex items-start justify-between mb-3">
                  <div class="flex items-center">
                    <i
                      :class="{
                        'fas fa-info-circle text-gray-500': message.priority === 0,
                        'fas fa-exclamation-triangle text-yellow-500': message.priority === 1,
                        'fas fa-exclamation-circle text-red-500': message.priority === 2,
                      }"
                      class="mr-3 mt-1"
                    ></i>
                    <div>
                      <div class="flex items-center space-x-4 mb-1">
                        <span class="text-sm font-medium text-gray-900">From: {{ message.from_user_name }}</span>
                        <span class="text-sm text-gray-600">To: {{ message.to_user_name }}</span>
                      </div>
                      <div class="text-xs text-gray-500">
                        {{ new Date(message.created_at).toLocaleString("ja-JP", {
                          year: "numeric",
                          month: "2-digit",
                          day: "2-digit",
                          hour: "2-digit",
                          minute: "2-digit",
                        }) }}
                      </div>
                    </div>
                  </div>
                  
                  <!-- 既読・未読バッジ -->
                  <div class="flex items-center space-x-2">
                    <span 
                      :class="{
                        'bg-green-100 text-green-800': message.read_flg === 1,
                        'bg-blue-100 text-blue-800': message.read_flg === 0,
                      }"
                      class="px-2 py-1 text-xs font-medium rounded-full"
                    >
                      {{ message.read_flg === 1 ? '既読' : '未読' }}
                    </span>
                  </div>
                </div>

                <!-- メッセージ内容 -->
                <div class="text-sm text-gray-700 mb-3" v-html="message.message.replace(/\n/g, '<br>')"></div>

                <!-- 返信がある場合 -->
                <div v-if="message.answer" class="mt-3 p-3 bg-white rounded-lg border-l-4 border-blue-400">
                  <div class="text-xs text-gray-500 mb-1">返信:</div>
                  <div class="text-sm text-gray-700" v-html="message.answer.replace(/\n/g, '<br>')"></div>
                </div>

                <!-- リンクがある場合 -->
                <div v-if="message.link" class="mt-3">
                  <a
                    :href="message.link"
                    target="_blank"
                    class="inline-flex items-center px-3 py-2 text-sm font-medium text-blue-600 bg-blue-100 rounded-lg hover:bg-blue-200 transition-colors duration-200"
                  >
                    <i class="fas fa-external-link-alt mr-2"></i>
                    関連リンク
                  </a>
                </div>
              </div>
            </div>
          </div>

          <!-- モーダルフッター -->
          <div class="absolute bottom-0 left-0 right-0 flex justify-end p-6 border-t border-gray-200 bg-gray-50">
            <button 
              @click="closeHistoryModal"
              class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors duration-200 font-medium"
            >
              閉じる
            </button>
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
