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

const showHistoryModal = ref(false);
const allMessages = ref([]);

const device_message_method = {
  confirm_message: (device_message_id) => {
    let all_confirm_flg = false;
    let device_message_ids = [];

    if (!device_message_id) {
      device_message_ids = device_messages.messages.map((message) => message.id);
      all_confirm_flg = true;
    } else {
      device_message_ids.push(device_message_id);
    }

    axios
      .post(route("device-message.confirm_message"), {
        device_massage_ids: device_message_ids,
      })
      .then((res) => {
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
          if (res.data.status) {
            alert("送信しました。");
            device_messages.messages = device_messages.messages.filter(
              (message) => message.id !== device_message_id
            );
          }
        });
    }
  },
};

const loginAndCreateTokenWithDeviceId = () => {
  try {
    axios
      .post(route("device-login"), {
        name: inputId.value,
        token: token.value,
      })
      .then((res) => {
        if (res.data.status) {
          localStorage.setItem("device_id", inputId.value);
          deviceId.value = inputId.value;
          if (res.data.msg) alert(res.data.msg);
          updateLastAccessDate();
          getDeviceMessages();
        } else {
          alert("デバイス登録に失敗しました: " + (res.data.msg || ""));
        }
      })
      .catch(() => {
        alert("通信エラーが発生しました。");
      });
  } catch (error) {
    console.error("Login failed:", error);
  }
};

const getFCMToken = async () => {
  try {
    const currentToken = await getToken(messaging, {
      vapidKey:
        "BAFiNQy1EiKe3dMiEdWTWw00FegkQc4uUvoaG8YPCPuAMD86GQPKpZRXkZALHqEsaS7-1R-3xGopdqyflwqGZpg",
    });
    if (currentToken) return currentToken;
    return null;
  } catch (error) {
    console.error("Token error:", error);
    return null;
  }
};

const getDeviceMessages = () => {
  axios
    .get(route("device-message.getDeviceMessages"), {
      params: { device_name: deviceId.value },
    })
    .then((res) => {
      const unreadMessages = res.data.filter((message) => message.read_flg === 0);
      device_messages.messages = unreadMessages;
      if (unreadMessages.length > 0) device_messages.status = true;
      allMessages.value = res.data;
    });
};

const showMessageHistory = () => { showHistoryModal.value = true; };
const closeHistoryModal = () => { showHistoryModal.value = false; };

const resetDeviceId = () => {
  const password = prompt("パスワードを入力してください:");
  if (password === "Akioka55") {
    localStorage.removeItem("device_id");
    alert("デバイスIDを削除しました。再読み込みします...");
    window.location.reload();
  } else if (password !== null) {
    alert("パスワードが違います。");
  }
};

const updateLastAccessDate = () => {
  if (deviceId.value) {
    axios.post(route("device-update-access"), { device_name: deviceId.value });
  }
};

onMounted(() => {
  const savedId = localStorage.getItem("device_id");
  if (savedId && savedId != "null") {
    deviceId.value = savedId;
    updateLastAccessDate();
    getDeviceMessages();
  } else {
    inputId.value = prompt("デバイスIDを設定してください:");
    if (inputId.value === null || !inputId.value.trim()) {
      window.location.href = route("stock.device.error");
      return;
    }
    getFCMToken().then((fetchedToken) => {
      token.value = fetchedToken || "";
      if (inputId.value) loginAndCreateTokenWithDeviceId();
    });
  }
});

onMessage(messaging, () => {
  window.location.reload();
});

// Menu items definition
const menuItems = [
  { route: 'stock.search', icon: 'fas fa-search', label: '検索', color: 'from-blue-500 to-blue-600', bgLight: 'bg-blue-50', textColor: 'text-blue-600', isLink: true },
  { route: 'stock.shipment', icon: 'fas fa-dolly', label: '出庫', color: 'from-emerald-500 to-emerald-600', bgLight: 'bg-emerald-50', textColor: 'text-emerald-600', isLink: true },
  { route: 'stock.new_item.home', icon: 'fas fa-file-alt', label: '物品依頼', color: 'from-violet-500 to-violet-600', bgLight: 'bg-violet-50', textColor: 'text-violet-600', isLink: false },
  { route: 'stock.check_order_request.home', icon: 'fas fa-clipboard-check', label: '物品依頼確認', color: 'from-amber-500 to-amber-600', bgLight: 'bg-amber-50', textColor: 'text-amber-600', isLink: false },
  { route: 'stock.request.home', icon: 'fas fa-hand-holding-box', label: '定期物品依頼', color: 'from-pink-500 to-pink-600', bgLight: 'bg-pink-50', textColor: 'text-pink-600', isLink: false, iconFallback: 'fas fa-hand-paper' },
  { route: 'stock.receive.home', icon: 'fas fa-truck-loading', label: '納品', color: 'from-cyan-500 to-cyan-600', bgLight: 'bg-cyan-50', textColor: 'text-cyan-600', isLink: true },
];
</script>
<template>
  <StockLayout :title="'在庫管理'">
    <template #content>
      <!-- Header -->
      <div class="flex justify-between items-center mb-8">
        <button
          @click="resetDeviceId"
          class="badge bg-slate-100 text-slate-600 hover:bg-slate-200 active:bg-slate-300 transition-colors cursor-pointer px-4 py-2.5 text-sm"
        >
          <i class="fas fa-microchip mr-2 text-xs"></i>
          {{ deviceId }}
        </button>

        <button
          @click="showMessageHistory"
          class="btn-secondary text-sm py-3 px-5"
        >
          <i class="fas fa-history"></i>
          メッセージ
          <span
            v-if="allMessages.length > 0"
            class="ml-1.5 inline-flex items-center justify-center w-6 h-6 text-xs font-bold text-white bg-primary-600 rounded-full"
          >
            {{ allMessages.length }}
          </span>
        </button>
      </div>

      <!-- Menu grid -->
      <div
        class="grid grid-cols-2 md:grid-cols-3 gap-5 md:gap-6 max-w-5xl mx-auto"
        :class="{ 'opacity-20 pointer-events-none': device_messages.status }"
      >
        <component
          v-for="item in menuItems"
          :key="item.route"
          :is="item.isLink ? Link : 'a'"
          :href="route(item.route)"
          class="card-hover p-8 flex flex-col items-center gap-4 group cursor-pointer active:scale-95 transition-transform"
        >
          <div
            :class="['w-20 h-20 rounded-3xl bg-gradient-to-br flex items-center justify-center shadow-md group-hover:scale-110 transition-transform duration-200', item.color]"
          >
            <i :class="[item.iconFallback || item.icon, 'text-white text-3xl']"></i>
          </div>
          <span class="text-lg font-bold text-slate-700 group-hover:text-slate-900 transition-colors">{{ item.label }}</span>
        </component>
      </div>

      <!-- New messages overlay -->
      <div v-if="device_messages.status" class="modal-overlay" @click="device_messages.status = false">
        <div class="modal-content" style="height: 80vh; width: 90vw;" @click.stop>
          <!-- Header -->
          <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 bg-slate-50">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-rose-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-envelope text-rose-600"></i>
              </div>
              <div>
                <h3 class="text-base font-bold text-slate-800">新着メッセージ</h3>
                <p class="text-xs text-slate-400">未読 {{ device_messages.messages.length }} 件</p>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <button
                @click="device_message_method.confirm_message(null)"
                class="btn-primary text-xs"
              >
                <i class="fas fa-check-double"></i>
                すべて既読にする
              </button>
              <button @click="device_messages.status = false" class="btn-icon">
                <i class="fas fa-times"></i>
              </button>
            </div>
          </div>

          <!-- Message list -->
          <div class="p-6 overflow-y-auto" style="height: calc(80vh - 72px);">
            <div class="space-y-4">
              <div
                v-for="message in device_messages.messages"
                :key="message.id"
                class="card p-5"
                :class="{
                  'border-l-4 border-l-slate-400': message.priority === 0,
                  'border-l-4 border-l-amber-400': message.priority === 1,
                  'border-l-4 border-l-rose-400': message.priority === 2,
                }"
              >
                <!-- Message header -->
                <div class="flex items-start justify-between mb-3">
                  <div class="flex items-center gap-2">
                    <i
                      :class="{
                        'fas fa-info-circle text-slate-400': message.priority === 0,
                        'fas fa-exclamation-triangle text-amber-500': message.priority === 1,
                        'fas fa-exclamation-circle text-rose-500': message.priority === 2,
                      }"
                    ></i>
                    <span class="text-sm font-semibold text-slate-700">{{ message.from_user_name }}</span>
                    <i class="fas fa-arrow-right text-[8px] text-slate-300"></i>
                    <span class="text-sm text-slate-500">{{ message.to_user_name }}</span>
                  </div>
                  <span class="text-xs text-slate-400">
                    {{ new Date(message.created_at).toLocaleString("ja-JP", {
                      month: "2-digit", day: "2-digit", hour: "2-digit", minute: "2-digit",
                    }) }}
                  </span>
                </div>

                <!-- Message body -->
                <div class="text-sm text-slate-600 mb-4 leading-relaxed" v-html="message.message.replace(/\n/g, '<br>')"></div>

                <Link
                  v-if="message.link"
                  :href="message.link"
                  class="inline-flex items-center gap-1.5 text-xs font-semibold text-primary-600 hover:text-primary-700 mb-4"
                >
                  <i class="fas fa-external-link-alt"></i>
                  関連リンク
                </Link>

                <!-- Reply -->
                <div class="pt-3 border-t border-slate-100">
                  <div class="relative">
                    <input
                      type="text"
                      class="form-input-modern pr-14 text-base py-3"
                      placeholder="返信を入力..."
                      v-model="message.answer"
                    />
                    <button
                      @click.prevent="device_message_method.send_answer(message.id)"
                      class="absolute right-2 top-1/2 -translate-y-1/2 w-11 h-11 bg-primary-600 hover:bg-primary-700 active:bg-primary-800 text-white rounded-xl flex items-center justify-center transition-colors"
                    >
                      <i class="fas fa-paper-plane text-sm"></i>
                    </button>
                  </div>
                  <div class="flex justify-end mt-3">
                    <button
                      class="text-xs font-semibold text-slate-400 hover:text-slate-600 transition-colors"
                      @click="device_message_method.confirm_message(message.id)"
                    >
                      <i class="fas fa-check mr-1"></i>
                      閉じる
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- History modal -->
      <div v-if="showHistoryModal" class="modal-overlay" @click="closeHistoryModal">
        <div class="modal-content" style="height: 80vh; width: 90vw;" @click.stop>
          <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 bg-slate-50">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-primary-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-history text-primary-600"></i>
              </div>
              <div>
                <h3 class="text-base font-bold text-slate-800">メッセージ履歴</h3>
                <p class="text-xs text-slate-400">合計 {{ allMessages.length }} 件</p>
              </div>
            </div>
            <button @click="closeHistoryModal" class="btn-icon">
              <i class="fas fa-times"></i>
            </button>
          </div>

          <div class="p-6 overflow-y-auto" style="height: calc(80vh - 130px);">
            <div v-if="allMessages.length === 0" class="text-center py-12">
              <i class="fas fa-inbox text-slate-300 text-4xl mb-3"></i>
              <p class="text-slate-400">メッセージはありません</p>
            </div>

            <div v-else class="space-y-3">
              <div
                v-for="message in allMessages"
                :key="message.id"
                class="card p-4"
                :class="{
                  'border-l-4 border-l-rose-400': message.priority === 2,
                  'border-l-4 border-l-amber-400': message.priority === 1,
                  'border-l-4 border-l-slate-300': message.priority === 0,
                }"
              >
                <div class="flex items-start justify-between mb-2">
                  <div class="flex items-center gap-2">
                    <span class="text-sm font-semibold text-slate-700">{{ message.from_user_name }}</span>
                    <i class="fas fa-arrow-right text-[8px] text-slate-300"></i>
                    <span class="text-sm text-slate-500">{{ message.to_user_name }}</span>
                  </div>
                  <div class="flex items-center gap-2">
                    <span
                      :class="message.read_flg === 1 ? 'badge-success' : 'badge-primary'"
                    >
                      {{ message.read_flg === 1 ? '既読' : '未読' }}
                    </span>
                    <span class="text-xs text-slate-400">
                      {{ new Date(message.created_at).toLocaleString("ja-JP", {
                        month: "2-digit", day: "2-digit", hour: "2-digit", minute: "2-digit",
                      }) }}
                    </span>
                  </div>
                </div>

                <div class="text-sm text-slate-600 leading-relaxed" v-html="message.message.replace(/\n/g, '<br>')"></div>

                <div v-if="message.answer" class="mt-3 p-3 bg-slate-50 rounded-xl border-l-4 border-l-primary-400">
                  <div class="text-xs text-slate-400 mb-1">返信:</div>
                  <div class="text-sm text-slate-600" v-html="message.answer.replace(/\n/g, '<br>')"></div>
                </div>

                <div v-if="message.link" class="mt-3">
                  <a
                    :href="message.link"
                    target="_blank"
                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-primary-600 hover:text-primary-700"
                  >
                    <i class="fas fa-external-link-alt"></i>
                    Related Link
                  </a>
                </div>
              </div>
            </div>
          </div>

          <div class="flex justify-end px-6 py-4 border-t border-slate-200 bg-slate-50">
            <button @click="closeHistoryModal" class="btn-secondary">
              閉じる
            </button>
          </div>
        </div>
      </div>
    </template>
  </StockLayout>
</template>
