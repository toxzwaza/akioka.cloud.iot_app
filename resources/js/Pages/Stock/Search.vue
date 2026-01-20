<script setup>
import StockLayout from "@/Layouts/StockLayout.vue";
import StockForm from "@/Components/StockForm.vue";
import { Link, router } from "@inertiajs/vue3";
import { getImgPath } from "@/Helper/method";
import { reactive, ref, onMounted, onUnmounted } from "vue";
import jsQR from "jsqr";

const props = defineProps({
  processes: Array,
  users: Array,
  search: Array,
  classifications: Array
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
    classification_id: 0,
  },
});
const process_users = ref([]);
const changeProcess = () => {
  console.log(form.search.process_id);

  process_users.value = props.users.filter(
    (user) => user.process_id === form.search.process_id
  );
};

const clickButton = () => {
  console.log(form.search);

  router.get(route("stock.search.result"), {
    stock_name: form.search.stock_name,
    stock_s_name: form.search.stock_s_name,
    alias: form.search.alias,
    address_id: form.search.address_id,
    stock_id: form.search.stock_id,
    process_id: form.search.process_id,
    user_id: form.search.user_id,
    classification_id: form.search.classification_id,
  });
};
const clearStocks = () => {
  stocks.value = [];
};

const search_box = ref(true);

// QRスキャナー関連の状態
const showQRScanner = ref(false);
const video = ref(null);
const canvas = ref(null);
const qrResult = ref("");
const isCameraActive = ref(false);
const errorMessage = ref("");
let stream = null;
let animationFrame = null;

const openScanner = () => {
  showQRScanner.value = true;
  // 次のティックでカメラを開始
  setTimeout(() => {
    startCamera();
  }, 100);
};

const closeScanner = () => {
  stopCamera();
  showQRScanner.value = false;
  qrResult.value = "";
  errorMessage.value = "";
};

const startCamera = async () => {
  try {
    errorMessage.value = "";
    
    // 既存のストリームを停止
    if (stream) {
      stopCamera();
    }

    // カメラアクセス
    stream = await navigator.mediaDevices.getUserMedia({ 
      video: { 
        facingMode: "environment",
        width: { ideal: 640 },
        height: { ideal: 480 }
      } 
    });
    
    video.value.srcObject = stream;
    isCameraActive.value = true;
    
    // QRコード読み取り開始
    startQRScanning();
    
  } catch (error) {
    console.error("カメラアクセスエラー:", error);
    errorMessage.value = "カメラにアクセスできません。ブラウザの設定を確認してください。";
  }
};

const stopCamera = () => {
  if (stream) {
    stream.getTracks().forEach(track => track.stop());
    stream = null;
  }
  
  if (animationFrame) {
    cancelAnimationFrame(animationFrame);
    animationFrame = null;
  }
  
  if (video.value) {
    video.value.srcObject = null;
  }
  
  isCameraActive.value = false;
};

const startQRScanning = () => {
  const context = canvas.value.getContext("2d");

  const tick = () => {
    if (video.value && video.value.readyState === video.value.HAVE_ENOUGH_DATA) {
      canvas.value.width = video.value.videoWidth;
      canvas.value.height = video.value.videoHeight;
      context.drawImage(video.value, 0, 0, canvas.value.width, canvas.value.height);
      
      const imageData = context.getImageData(0, 0, canvas.value.width, canvas.value.height);
      const code = jsQR(imageData.data, canvas.value.width, canvas.value.height);
      
      if (code) {
        qrResult.value = code.data;
        // QRコードが読み取れたら棚アドレスフィールドに設定してポップアップを閉じる
        form.search.address_id = code.data;
        closeScanner();
      }
    }
    
    if (isCameraActive.value) {
      animationFrame = requestAnimationFrame(tick);
    }
  }
  
  tick();
};

onMounted(() => {
  console.log(props.processes);

  form.search.stock_name = props.search?.stock_name ?? "";
  form.search.stock_s_name = props.search?.stock_s_name ?? "";
  form.search.alias = props.search?.alias ?? "";
  form.search.address_id = props.search?.address_id ?? "";
  form.search.stock_id = props.search?.stock_id ?? "";
  form.search.classification_id = props.search?.classification_id ?? 0;
  if (props.search?.process_id) {
    form.search.process_id = props.search?.process_id;
    changeProcess();
  }
  form.search.user_id = props.search?.user_id ?? "";
});

onUnmounted(() => {
  stopCamera();
});
</script>
<template>
  <StockLayout :title="'検索'">
    <template #content>
      <!-- 検索フォームコンポーネント -->
      <div :class="{ hide: !search_box }">
        <div class="w-full p-2">
          <!-- 検索用フォーム -->
          <form>
            <!-- 工程選択 -->
            <div class="flex flex-wrap -mx-3 mb-10 items-end bg-white pt-2">
              <div class="w-1/2 px-3">
                <label for="" class="text-red-500 font-bold"
                  >過去の発注履歴から検索</label
                >
                <select
                  name=""
                  id=""
                  v-model="form.search.process_id"
                  class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-4 px-6 mb-3 leading-tight focus:outline-none focus:bg-white focus:border-gray-500 mt-2"
                  @change="changeProcess"
                >
                  <option value="0">工程を選択</option>
                  <option
                    v-for="process in props.processes"
                    :key="process.id"
                    :value="process.id"
                  >
                    {{ process.name }}
                  </option>
                </select>
              </div>
              <div class="w-1/2 px-3" v-if="form.search.process_id">
                <label for="" class="text-red-500 font-bold"></label>
                <select
                  name=""
                  id=""
                  v-model="form.search.user_id"
                  class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-4 px-6 mb-3 leading-tight focus:outline-none focus:bg-white focus:border-gray-500 mt-2"
                >
                  <option value="0">依頼者でさらに絞り込み</option>
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

            <div class="flex flex-wrap -mx-3 mb-6">
              <div class="w-full px-3">
                <input
                  name="search_name"
                  class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-4 px-6 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                  id="grid-password"
                  type="text"
                  placeholder="品名"
                  v-model="form.search.stock_name"
                />
              </div>
            </div>
            <div class="flex flex-wrap -mx-3 mb-6">
              <div class="w-full px-3">
                <input
                  name="search_name"
                  class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-4 px-6 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                  id="grid-password"
                  type="text"
                  placeholder="品番"
                  v-model="form.search.stock_s_name"
                />
              </div>
            </div>
            <div class="flex flex-wrap -mx-3 mb-6">
              <div class="w-full px-3">
                <select
                  name=""
                  id=""
                  v-model="form.search.classification_id"
                  class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-4 px-6 leading-tight focus:outline-none focus:bg-white focus:border-gray-500 mt-2"
                >
                  <option value="0">カテゴリを選択</option>
                  <option
                    v-for="classification in props.classifications"
                    :key="classification.id"
                    :value="classification.id"
                  >
                    {{ classification.name }}
                  </option>
                </select>
              </div>
            </div>
            <div class="flex flex-wrap -mx-3 mb-6">
              <div class="w-full px-3">
                <input
                  name="alias"
                  class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-4 px-6 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                  id="grid-password"
                  type="text"
                  placeholder="略名"
                  v-model="form.search.alias"
                />
              </div>
            </div>
            <div class="flex flex-wrap -mx-3 mb-6">
              <div class="w-full px-3 flex items-center justify-start">
                <input
                  name="address_id"
                  class="w-5/6 appearance-none block bg-gray-200 text-gray-700 border border-gray-200 rounded py-4 px-6 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                  id="grid-password"
                  type="number"
                  placeholder="棚アドレス"
                  v-model="form.search.address_id"
                />
                <button @click.prevent="openScanner" class="ml-4 bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-camera"></i>
              </button>
              </div>
            </div>
            <div class="flex flex-wrap -mx-3 mb-6">
              <div class="w-full px-3">
                <input
                  name="stock_id"
                  class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-4 px-6 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                  id="grid-password"
                  type="number"
                  placeholder="製品ID or JANコード"
                  v-model="form.search.stock_id"
                />
              </div>
            </div>

            <button
              @click.prevent="clickButton"
              class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-4 px-8 rounded"
            >
              検索
            </button>
          </form>
        </div>
      </div>
      <button
        v-if="!search_box"
        :class="{
          'ml-4 bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded': true,
        }"
        @click="search_box = true"
      >
        検索画面を表示
      </button>

      <!-- QRスキャナーポップアップ -->
      <div
        v-if="showQRScanner"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        @click="closeScanner"
      >
        <div
          class="bg-white rounded-lg shadow-xl p-6 max-w-2xl w-full mx-4"
          @click.stop
        >
          <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-semibold text-gray-800">
              QRコードをスキャンしてください
            </h3>
            <button
              @click="closeScanner"
              class="text-gray-500 hover:text-gray-700 text-xl font-bold"
            >
              ×
            </button>
          </div>

          <!-- カメラ表示エリア -->
          <div class="mb-4">
            <div class="relative bg-gray-200 rounded-lg overflow-hidden">
              <video
                ref="video"
                autoplay
                playsinline
                muted
                class="w-full h-80 object-cover"
              ></video>
              <canvas
                ref="canvas"
                class="hidden"
              ></canvas>
            </div>
          </div>

          <!-- 読み取り結果 -->
          <div v-if="qrResult" class="mb-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded-lg">
            <p class="font-semibold">読み取り完了:</p>
            <p class="font-mono">{{ qrResult }}</p>
          </div>

          <!-- エラーメッセージ -->
          <div v-if="errorMessage" class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg">
            {{ errorMessage }}
          </div>

          <!-- ステータス表示 -->
          <div class="mb-4 text-center">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                  :class="isCameraActive ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'">
              <span class="w-2 h-2 rounded-full mr-2"
                    :class="isCameraActive ? 'bg-green-500' : 'bg-gray-500'"></span>
              {{ isCameraActive ? 'カメラ稼働中' : 'カメラ停止中' }}
            </span>
          </div>

          <!-- 説明テキスト -->
          <div class="text-center text-gray-600 text-base mb-6">
            QRコードをカメラに向けてください。<br>
            読み取りが完了すると自動的に棚アドレスに設定されます。
          </div>

          <!-- ボタン -->
          <div class="flex gap-2 justify-center">
            <button
              @click="startCamera"
              :disabled="isCameraActive"
              class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-sm"
            >
              カメラ開始
            </button>
            <button
              @click="stopCamera"
              :disabled="!isCameraActive"
              class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-sm"
            >
              カメラ停止
            </button>
            <button
              @click="closeScanner"
              class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 text-sm"
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
.hide {
  height: 0;
  overflow: hidden;
  opacity: 0;
}

.stock_card {
  width: 28%;
  height: 30%;
  & .stock_img {
    width: 100%;
    height: 23vh;
    display: flex;
    justify-content: center;
    padding: 0 1rem;
    background-color: #ffffff;

    & img {
      width: 100%;
      object-fit: contain;
    }
  }
}
</style>
