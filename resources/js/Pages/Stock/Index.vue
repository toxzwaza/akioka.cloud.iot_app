<script setup>
import StockLayout from "@/Layouts/StockLayout.vue";
import { Link } from "@inertiajs/vue3";
import { ref, onMounted } from "vue";
import axios from "axios";
import { messaging, getToken } from "@/Firebase/firebase";
import { onMessage } from "firebase/messaging";

const deviceId = ref(null);
const inputId = ref("");
const token = ref("");

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
        console.log(res.data)
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

// 初期処理：localStorageから読み込み
onMounted(() => {
  const savedId = localStorage.getItem("device_id");
  if (savedId && savedId != "null") {
    deviceId.value = savedId;
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
  alert(
    `📩 フォアグラウンド通知を受信しました: ${payload.notification.title}\n ${payload.notification.body}`
  );
});
</script>
<template>
  <StockLayout :title="'在庫管理システム'">
    <template #content>
      <p class="text-gray-700 mb-4 text-left ml-4">
        device_id : {{ deviceId }}
      </p>
      <div id="icon_container">
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

        <!-- 定期物品依頼 -->
        <div class="w-1/2 p-4">
          <a :href="route('stock.request.home')"
            ><img
              class=""
              src="/images/stocks/icons/per_stock_request.png"
              alt="現場物品依頼"
          /></a>
        </div>

        <!-- 新規品依頼 -->
        <div class="w-1/2 p-4">
          <a :href="route('stock.new_item.home')"
            ><img
              class=""
              src="/images/stocks/icons/approval.png"
              alt="新規品依頼"
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
    </template>
  </StockLayout>
</template>
<style scoped>
#icon_container {
  display: flex;
  flex-wrap: wrap;
}
</style>
