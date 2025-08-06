<script setup>
import ReceiveLayout from "@/Layouts/ReceiveLayout.vue";
import { onMounted, ref } from "vue";
import axios from "axios";
import MicroModal from "@/Components/MicroModal.vue";

const modalStatus = ref(false);
const modalImageSrc = ref("");
const videoRef = ref(null);
const streamRef = ref(null);
const captureCanvas = ref(null);
const fileInputRef = ref(null);

const modalImage = (target) => {
  modalStatus.value = true;
  modalImageSrc.value = target.src;
};

const handleCloseModal = () => {
  modalStatus.value = false;
  stopCamera();
};

// 検索用取引先リスト
const initial_order_suppliers = ref([]);
const searchText = ref("");
const handleChangeSupplier = (comName) => {
  if (comName) {
    initial_orders.value = base_initial_orders.value.filter(
      (initial_order) => initial_order.com_name === comName
    );
  } else {
    initial_orders.value = base_initial_orders.value;
  }
};
// 品名・品番検索
const searchOrders = () => {
  if (searchText.value) {
    initial_orders.value = initial_orders.value.map((initial_order) => {
      const nameMatch =
        initial_order.name && initial_order.name.includes(searchText.value);
      const sNameMatch =
        initial_order.s_name && initial_order.s_name.includes(searchText.value);
      return {
        ...initial_order,
        nameMatch,
        sNameMatch,
      };
    });
  }
};

const highlightMatch = (text, isMatch) => {
  if (!isMatch) return `${text}`;
  const regex = new RegExp(`(${searchText.value})`, "gi");
  return text.replace(
    regex,
    '<span class="font-bold bg-yellow-400 text-lg">$1</span>'
  );
};

const base_initial_orders = ref([]);
const initial_orders = ref([]);

const select_list = ref([]);
const updateSelectList = (orderId, isChecked) => {
  if (isChecked) {
    select_list.value.push(orderId);
  } else {
    select_list.value = select_list.value.filter((id) => id !== orderId);
  }
};

// 初期データ取得
const getInitialOrders = () => {
  axios
    .get(route("stock.receive.getInitialOrders"))
    .then((res) => {
      initial_orders.value = res.data;
      base_initial_orders.value = res.data;
      initial_order_suppliers.value = [
        ...new Set(initial_orders.value.map((order) => order.com_name)),
      ].sort();
    })
    .catch((error) => {
      console.log(error);
    });
};

// ===== カメラ制御部分 =====
const startCamera = async () => {
  try {
    streamRef.value = await navigator.mediaDevices.getUserMedia({ video: true });
    if (videoRef.value) {
      videoRef.value.srcObject = streamRef.value;
    }
  } catch (error) {
    alert("カメラにアクセスできません: " + error.message);
  }
};

const stopCamera = () => {
  if (streamRef.value) {
    streamRef.value.getTracks().forEach(track => track.stop());
    streamRef.value = null;
  }
};

const captureImage = () => {
  const canvas = captureCanvas.value;
  const context = canvas.getContext("2d");
  canvas.width = videoRef.value.videoWidth;
  canvas.height = videoRef.value.videoHeight;
  context.drawImage(videoRef.value, 0, 0);
  return new Promise((resolve) => {
    canvas.toBlob((blob) => {
      resolve(blob);
    }, "image/jpeg");
  });
};

// 撮影開始（uploadFileから呼び出す）
const uploadFile = async (id) => {
  if (select_list.value.length === 0) {
    updateSelectList(id, true);
  }
  modalStatus.value = true;
  await startCamera();
};

// 撮影＆アップロード
const handleCaptureAndUpload = async () => {
  const imageBlob = await captureImage();
  await sendFile(imageBlob, "capture.jpg");
};

// ファイル選択からアップロード
const handleFileSelect = async (event) => {
  const file = event.target.files[0];
  if (!file) return;
  await sendFile(file, file.name);
};

// 共通アップロード処理
const sendFile = async (file, fileName) => {

  const formData = new FormData();
  formData.append("file", file, fileName);
  select_list.value.forEach(item => {
    formData.append("select_list[]", item);
  });

  try {
    const res = await axios.post(route("stock.receive.uploadFile"), formData, {
      headers: { "Content-Type": "multipart/form-data" },
    });
    if (res.data.status) {
      console.log(res.data)
      alert("納品書を登録しました。");
      window.location.reload();
    }
  } catch (error) {
    console.error(error);
    alert("アップロードに失敗しました");
  } finally {
    handleCloseModal();
  }
};

// 削除
const deleteInitialOrder = (id) => {
  if (id && confirm("削除してよろしいですか？")) {
    axios
      .get(route("stock.receive.delete.initialOrder", { order_id: id }))
      .then((res) => {
        if (res.data.status === "ok") {
          if (confirm("削除が完了しました。再読み込みしますか？")) {
            initial_orders.value = initial_orders.value.filter(
              (order) => order.id !== id
            );
          }
        }
      })
      .catch((error) => {
        console.log(error);
      });
  } else {
    alert("キャンセルされました。");
  }
};

onMounted(() => {
  getInitialOrders();
});
</script>

<template>
  <ReceiveLayout :title="'納品登録'">
    <template #content>
      <section class="text-gray-600 body-font">
        <div class="container py-12 mx-auto">
          <div class="flex flex-col text-center w-full mb-8">
            <h1 class="text-3xl font-medium title-font mb-2 text-green-600">
              納品登録
            </h1>
            <p class="lg:w-2/3 mx-auto leading-relaxed text-base">
              以下の画面より納品書登録を行います。<br />
              品名・品番が一致するデータがない場合、背景色が青色で表示されます。<br />
              一致するデータがない場合、納品登録画面にて作成する必要があります。
            </p>
          </div>
          <div class="w-1/2 mx-auto mb-8">
            <div class="p-2 flex justify-start">
              <div class="w-1/3 relative mr-2">
                <label for="search_text" class="leading-7 text-sm text-gray-600">絞込み</label>
                <select
                  @change="handleChangeSupplier($event.target.value)"
                  class="w-full bg-gray-100 rounded border border-gray-300 focus:border-indigo-500"
                >
                  <option value="">全ての取引先</option>
                  <option
                    v-for="comName in initial_order_suppliers"
                    :key="comName"
                    :value="comName"
                  >
                    {{ comName }}
                  </option>
                </select>
              </div>

              <div class="w-1/2 relative ml-2">
                <label for="search_text" class="leading-7 text-sm text-gray-600">検索</label>
                <input
                  @input="searchOrders"
                  v-model="searchText"
                  type="text"
                  id="search_text"
                  class="w-full bg-gray-100 rounded border border-gray-300 focus:border-indigo-500"
                  placeholder="品名・品番"
                />
              </div>
            </div>
          </div>

          <div class="w-full mx-auto overflow-auto">
            <table class="table-auto w-full text-left whitespace-no-wrap">
              <thead>
                <tr>
                  <th class="px-4 py-3 bg-gray-100">選択</th>
                  <th class="px-4 py-3 bg-gray-100">注文No</th>
                  <th class="px-4 py-3 bg-gray-100">画像</th>
                  <th class="px-4 py-3 bg-gray-100">注文者</th>
                  <th class="px-4 py-3 bg-gray-100">注文日</th>
                  <th class="px-4 py-3 bg-gray-100">注文先</th>
                  <th class="px-4 py-3 bg-gray-100">品名</th>
                  <th class="px-4 py-3 bg-gray-100">品番</th>
                  <th class="px-4 py-3 bg-gray-100">数量</th>
                  <th class="w-10 bg-gray-100"></th>
                  <th class="w-10 bg-gray-100"></th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="order in initial_orders"
                  :key="order.id"
                  :class="{ 'bg-red-100': order.not_found_flg }"
                >
                  <td class="px-4 py-6">
                    <input
                      type="checkbox"
                      @change="updateSelectList(order.id, $event.target.checked)"
                    />
                  </td>
                  <td class="px-4 py-6">{{ order.order_no }}</td>
                  <td class="w-24 px-4 py-6">
                    <img
                      @click="modalImage($event.target)"
                      :src="order.img_path && order.img_path.includes('https://') 
                          ? order.img_path 
                          : 'https://akioka.cloud/' + order.img_path"
                      alt=""
                    />
                  </td>
                  <td class="px-4 py-6">{{ order.order_user }}</td>
                  <td class="px-4 py-6">{{ new Date(order.order_date).toLocaleDateString("ja-JP") }}</td>
                  <td class="px-4 py-6">{{ order.com_name }}</td>
                  <td class="px-4 py-6">
                    <span v-html="highlightMatch(order.name, order.nameMatch)"></span>
                  </td>
                  <td class="px-4 py-6">
                    <span v-html="highlightMatch(order.s_name ?? '', order.sNameMatch)"></span>
                  </td>
                  <td class="px-4 py-6">{{ order.quantity + order.order_unit }}</td>
                  <td class="w-10 text-center whitespace-nowrap">
                    <button
                      @click="uploadFile(order.id)"
                      class="bg-transparent hover:bg-gray-500 text-gray-700 font-semibold hover:text-white py-2 px-4 border border-gray-500 rounded text-sm"
                    >
                      納品書
                    </button>
                  </td>
                  <td class="w-10 text-center px-2 whitespace-nowrap">
                    <button
                      @click="deleteInitialOrder(order.id)"
                      class="bg-red-500 text-white font-semibold py-2 px-4 border border-red-500 rounded text-sm"
                    >
                      削除
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- カメラモーダル -->
      <div v-if="modalStatus" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50">
        <div class="bg-white p-4 rounded-xl shadow-xl w-2/3">
          <video ref="videoRef" autoplay playsinline class="w-full rounded-lg mb-2"></video>
          <canvas ref="captureCanvas" class="hidden"></canvas>
          <div class="flex justify-between space-x-2">
            <button @click="handleCaptureAndUpload" class="bg-green-500 text-white px-4 py-2 rounded">
              撮影・アップロード
            </button>
            <button 
              @click="fileInputRef.click()" 
              class="bg-blue-500 text-white px-4 py-2 rounded"
            >
              ファイルから選択
            </button>
            <input 
              type="file" 
              ref="fileInputRef" 
              accept="image/*" 
              class="hidden" 
              @change="handleFileSelect"
            />
            <button @click="handleCloseModal" class="bg-red-500 text-white px-4 py-2 rounded">
              キャンセル
            </button>
          </div>
        </div>
      </div>

      <!-- 画像モーダル -->
      <MicroModal
        v-if="modalImageSrc && !streamRef"
        @closeModal="handleCloseModal"
        :modalImageSrc="modalImageSrc"
      ></MicroModal>
    </template>
  </ReceiveLayout>
</template>

<style scoped lang="scss">
</style>
