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
  process_users.value = props.users.filter(
    (user) => user.process_id === form.search.process_id
  );
};

const clickButton = () => {
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

const search_box = ref(true);

// QR Scanner
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
  setTimeout(() => { startCamera(); }, 100);
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
    if (stream) stopCamera();
    stream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: "environment", width: { ideal: 640 }, height: { ideal: 480 } }
    });
    video.value.srcObject = stream;
    isCameraActive.value = true;
    startQRScanning();
  } catch (error) {
    errorMessage.value = "カメラへのアクセスが拒否されました。ブラウザ設定を確認してください。";
  }
};

const stopCamera = () => {
  if (stream) { stream.getTracks().forEach(track => track.stop()); stream = null; }
  if (animationFrame) { cancelAnimationFrame(animationFrame); animationFrame = null; }
  if (video.value) video.value.srcObject = null;
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
        form.search.address_id = code.data;
        closeScanner();
      }
    }
    if (isCameraActive.value) animationFrame = requestAnimationFrame(tick);
  };
  tick();
};

onMounted(() => {
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

onUnmounted(() => { stopCamera(); });
</script>
<template>
  <StockLayout :title="'検索'">
    <template #content>
      <div :class="{ 'h-0 overflow-hidden opacity-0': !search_box }">
        <div class="max-w-4xl mx-auto">
          <!-- Page title -->
          <div class="mb-8">
            <h1 class="text-3xl font-bold text-slate-800">
              <i class="fas fa-search text-primary-500 mr-3"></i>
              検索
            </h1>
            <div class="h-1.5 w-20 bg-primary-500 rounded-full mt-3"></div>
          </div>

          <div class="card p-8">
            <form class="space-y-5">
              <!-- Order history filter -->
              <div class="bg-slate-50 rounded-xl p-5 border border-slate-200">
                <label class="form-label text-rose-500 text-base">発注履歴で絞り込む</label>
                <div class="flex flex-col md:flex-row gap-4 mt-3">
                  <select
                    v-model="form.search.process_id"
                    class="form-select-modern flex-1 text-base py-4"
                    @change="changeProcess"
                  >
                    <option value="0">工程を選択</option>
                    <option v-for="process in props.processes" :key="process.id" :value="process.id">
                      {{ process.name }}
                    </option>
                  </select>
                  <select
                    v-if="form.search.process_id"
                    v-model="form.search.user_id"
                    class="form-select-modern flex-1 text-base py-4"
                  >
                    <option value="0">依頼者で絞り込む</option>
                    <option v-for="user in process_users" :key="user.id" :value="user.id">
                      {{ user.name }}
                    </option>
                  </select>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                  <label class="form-label text-base">品名</label>
                  <input class="form-input-modern text-base py-4" type="text" placeholder="品名を入力" v-model="form.search.stock_name" />
                </div>
                <div>
                  <label class="form-label text-base">品番</label>
                  <input class="form-input-modern text-base py-4" type="text" placeholder="品番を入力" v-model="form.search.stock_s_name" />
                </div>
                <div>
                  <label class="form-label text-base">分類</label>
                  <select class="form-select-modern text-base py-4" v-model="form.search.classification_id">
                    <option value="0">分類を選択</option>
                    <option v-for="c in props.classifications" :key="c.id" :value="c.id">{{ c.name }}</option>
                  </select>
                </div>
                <div>
                  <label class="form-label text-base">別名</label>
                  <input class="form-input-modern text-base py-4" type="text" placeholder="別名を入力" v-model="form.search.alias" />
                </div>
                <div>
                  <label class="form-label text-base">棚番地</label>
                  <div class="flex gap-3">
                    <input class="form-input-modern flex-1 text-base py-4" type="number" placeholder="番地を入力" v-model="form.search.address_id" />
                    <button @click.prevent="openScanner" class="btn-primary shrink-0 px-5 text-lg">
                      <i class="fas fa-qrcode"></i>
                    </button>
                  </div>
                </div>
                <div>
                  <label class="form-label text-base">商品ID / JANコード</label>
                  <input class="form-input-modern text-base py-4" type="number" placeholder="IDまたはJANを入力" v-model="form.search.stock_id" />
                </div>
              </div>

              <button @click.prevent="clickButton" class="btn-primary w-full mt-4 py-5 text-lg font-bold rounded-2xl">
                <i class="fas fa-search mr-2"></i>
                検索
              </button>
            </form>
          </div>
        </div>
      </div>

      <button
        v-if="!search_box"
        class="btn-success"
        @click="search_box = true"
      >
        <i class="fas fa-search"></i>
        検索を表示
      </button>

      <!-- QR Scanner Modal -->
      <div v-if="showQRScanner" class="modal-overlay" @click="closeScanner">
        <div class="modal-content max-w-2xl w-full mx-4 p-6" @click.stop>
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-slate-800">
              <i class="fas fa-qrcode text-primary-500 mr-2"></i>
              QRスキャナー
            </h3>
            <button @click="closeScanner" class="btn-icon">
              <i class="fas fa-times"></i>
            </button>
          </div>

          <div class="bg-slate-100 rounded-xl overflow-hidden mb-4">
            <video ref="video" autoplay playsinline muted class="w-full h-72 object-cover"></video>
            <canvas ref="canvas" class="hidden"></canvas>
          </div>

          <div v-if="qrResult" class="mb-4 p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl text-sm">
            <i class="fas fa-check-circle mr-1"></i> 読取結果: <span class="font-mono font-bold">{{ qrResult }}</span>
          </div>
          <div v-if="errorMessage" class="mb-4 p-3 bg-rose-50 border border-rose-200 text-rose-700 rounded-xl text-sm">
            {{ errorMessage }}
          </div>

          <div class="text-center mb-4">
            <span class="badge" :class="isCameraActive ? 'badge-success' : 'bg-slate-100 text-slate-500'">
              <span class="w-1.5 h-1.5 rounded-full mr-1.5" :class="isCameraActive ? 'bg-emerald-500' : 'bg-slate-400'"></span>
              {{ isCameraActive ? '起動中' : '停止中' }}
            </span>
          </div>

          <p class="text-sm text-slate-400 text-center mb-4">QRコードにカメラを向けてください。読み取ると自動で入力されます。</p>

          <div class="flex gap-3 justify-center">
            <button @click="startCamera" :disabled="isCameraActive" class="btn-primary py-3 px-6 text-base" :class="{ 'opacity-50 cursor-not-allowed': isCameraActive }">開始</button>
            <button @click="stopCamera" :disabled="!isCameraActive" class="btn-danger py-3 px-6 text-base" :class="{ 'opacity-50 cursor-not-allowed': !isCameraActive }">停止</button>
            <button @click="closeScanner" class="btn-secondary py-3 px-6 text-base">閉じる</button>
          </div>
        </div>
      </div>
    </template>
  </StockLayout>
</template>
