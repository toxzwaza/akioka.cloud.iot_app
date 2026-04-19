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

// デフォルトは外カメラ
const cameraFacing = ref("environment");

// プレビュー用
const previewImage = ref("");
const previewFile = ref(null);

// 検索用データ
const initial_order_suppliers = ref([]);
const searchText = ref("");
const base_initial_orders = ref([]);
const initial_orders = ref([]);
const select_list = ref([]);

// モーダル内画像表示
const modalImage = (target) => {
  modalStatus.value = true;
  modalImageSrc.value = target.src;
};

const handleCloseModal = () => {
  modalStatus.value = false;
  stopCamera();
  previewImage.value = "";
  previewFile.value = null;
};

// 再撮影（プレビュー解除）
const retakePhoto = async () => {
  previewImage.value = "";
  previewFile.value = null;
  await startCamera(); // 再度カメラを起動
};
// 取引先変更
const handleChangeSupplier = (comName) => {
  if (comName) {
    initial_orders.value = base_initial_orders.value.filter(
      (initial_order) => initial_order.com_name === comName
    );
  } else {
    initial_orders.value = base_initial_orders.value;
  }
};

// 検索
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

// ハイライト
const highlightMatch = (text, isMatch) => {
  if (!isMatch) return `${text}`;
  const regex = new RegExp(`(${searchText.value})`, "gi");
  return text.replace(
    regex,
    '<span class="font-bold bg-yellow-400 text-lg">$1</span>'
  );
};

// 選択リスト更新
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
      console.log(res.data);
      base_initial_orders.value = res.data;
      initial_order_suppliers.value = [
        ...new Set(initial_orders.value.map((order) => order.com_name)),
      ].sort();
    })
    .catch(console.error);
};

// ===== カメラ制御部分 =====
const startCamera = async () => {
  stopCamera();
  try {
    streamRef.value = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: cameraFacing.value },
      width: { ideal: 1920 }, // 横解像度
      height: { ideal: 1080 }, // 縦解像度
    });
    if (videoRef.value) {
      videoRef.value.srcObject = streamRef.value;
    }
  } catch (error) {
    alert("カメラにアクセスできません: " + error.message);
  }
};

const stopCamera = () => {
  if (streamRef.value) {
    streamRef.value.getTracks().forEach((track) => track.stop());
    streamRef.value = null;
  }
};

// カメラ切替
const toggleCameraFacing = async () => {
  cameraFacing.value =
    cameraFacing.value === "environment" ? "user" : "environment";
  await startCamera();
};

// 撮影
const captureImage = () => {
  const canvas = captureCanvas.value;
  const context = canvas.getContext("2d");
  canvas.width = videoRef.value.videoWidth;
  canvas.height = videoRef.value.videoHeight;
  context.drawImage(videoRef.value, 0, 0);
  return new Promise((resolve) => {
    canvas.toBlob((blob) => {
      const previewUrl = URL.createObjectURL(blob);
      previewImage.value = previewUrl;
      previewFile.value = new File([blob], "capture.jpg", {
        type: "image/jpeg",
      });
      resolve();
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

// 撮影（アップロードは確定時に行う）
const handleCapture = async () => {
  await captureImage();
};

// ファイル選択（アップロードは確定時に行う）
const handleFileSelect = (event) => {
  const file = event.target.files[0];
  if (!file) return;
  previewImage.value = URL.createObjectURL(file);
  previewFile.value = file;
};

// 確定アップロード
const confirmUpload = async () => {
  if (!previewFile.value) {
    alert("画像が選択されていません");
    return;
  }
  const formData = new FormData();
  formData.append("file", previewFile.value);
  select_list.value.forEach((item) => {
    formData.append("select_list[]", item);
  });

  try {
    const res = await axios.post(route("stock.receive.uploadFile"), formData, {
      headers: { "Content-Type": "multipart/form-data" },
    });
    if (res.data.status) {
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
      .catch(console.error);
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
      <section class="bg-slate-50 min-h-screen py-8 px-4">
        <div class="max-w-7xl mx-auto">
          <!-- タイトル -->
          <div class="page-header text-center mb-8">
            <h1 class="section-title text-primary-600">
              納品登録
            </h1>
            <p class="section-subtitle max-w-2xl mx-auto">
              以下の画面より納品書登録を行います。<br />
              品名・品番が一致するデータがない場合、背景色が赤色で表示されます。<br />
              一致するデータがない場合、納品登録画面にて作成する必要があります。
            </p>
          </div>

          <!-- 絞り込み -->
          <div class="card max-w-2xl mx-auto mb-8 p-6">
            <div class="flex flex-wrap gap-4">
              <div class="flex-1 min-w-[200px]">
                <label class="form-label">絞込み</label>
                <select
                  @change="handleChangeSupplier($event.target.value)"
                  class="form-select-modern"
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

              <div class="flex-1 min-w-[200px]">
                <label class="form-label">検索</label>
                <input
                  @input="searchOrders"
                  v-model="searchText"
                  type="text"
                  class="form-input-modern"
                  placeholder="品名・品番"
                />
              </div>
            </div>
          </div>

          <!-- テーブル -->
          <div class="card overflow-hidden">
            <div class="overflow-x-auto">
              <table class="table-modern">
                <thead>
                  <tr>
                    <th>選択</th>
                    <th>注文No</th>
                    <th>画像</th>
                    <th>注文者</th>
                    <th>注文日</th>
                    <th>希望納期</th>
                    <th>注文先</th>
                    <th>品名</th>
                    <th>品番</th>
                    <th>数量</th>
                    <th></th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="order in initial_orders"
                    :key="order.id"
                    :class="{ 'bg-red-50': order.not_found_flg }"
                  >
                    <td>
                      <input
                        type="checkbox"
                        class="rounded border-slate-300 text-primary-600 focus:ring-primary-500"
                        @change="
                          updateSelectList(order.id, $event.target.checked)
                        "
                      />
                    </td>
                    <td class="font-medium text-slate-800">{{ order.order_no }}</td>
                    <td class="w-24">
                      <img
                        @click="modalImage($event.target)"
                        :src="
                          order.img_path && order.img_path.includes('https://')
                            ? order.img_path
                            : 'https://akioka.cloud/' + order.img_path
                        "
                        alt=""
                        class="rounded-lg cursor-pointer hover:opacity-80 transition-opacity"
                      />
                    </td>
                    <td>{{ order.order_user }}</td>
                    <td>
                      {{ new Date(order.order_date).toLocaleDateString("ja-JP") }}
                    </td>
                    <td>
                      {{
                        order.desire_delivery_date
                          ? new Date(
                              order.desire_delivery_date
                            ).toLocaleDateString("ja-JP")
                          : "未指定"
                      }}
                    </td>
                    <td>{{ order.com_name }}</td>
                    <td>
                      <span
                        v-html="highlightMatch(order.name, order.nameMatch)"
                      ></span>
                    </td>
                    <td>
                      <span
                        v-html="
                          highlightMatch(order.s_name ?? '', order.sNameMatch)
                        "
                      ></span>
                    </td>
                    <td>
                      {{ order.quantity + order.order_unit }}
                    </td>
                    <td class="text-center whitespace-nowrap">
                      <button
                        @click="uploadFile(order.id)"
                        class="btn-secondary text-sm"
                      >
                        納品書
                      </button>
                    </td>
                    <td class="text-center whitespace-nowrap">
                      <button
                        @click="deleteInitialOrder(order.id)"
                        class="btn-danger text-sm"
                      >
                        削除
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </section>

      <!-- カメラモーダル -->
      <div
        v-if="modalStatus"
        class="modal-overlay"
      >
        <div class="modal-content w-2/3 max-w-2xl">
          <!-- カメラ映像：プレビューがない時のみ表示 -->
          <video
            v-if="!previewImage"
            ref="videoRef"
            autoplay
            playsinline
            class="w-full rounded-xl mb-4"
          ></video>

          <canvas ref="captureCanvas" class="hidden"></canvas>

          <!-- プレビュー -->
          <div v-if="previewImage" class="mb-4">
            <p class="text-sm text-slate-700 mb-2 font-semibold">プレビュー:</p>
            <img :src="previewImage" class="w-full rounded-xl border border-slate-200" />
          </div>

          <div class="flex justify-between gap-3 mt-4">
            <!-- プレビュー前 -->
            <template v-if="!previewImage">
              <button
                @click="handleCapture"
                class="btn-success"
              >
                撮影
              </button>
              <button
                @click="toggleCameraFacing"
                class="btn-secondary"
              >
                カメラ切替
              </button>
              <button
                @click="fileInputRef.click()"
                class="btn-primary"
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
              <button
                @click="handleCloseModal"
                class="btn-danger"
              >
                キャンセル
              </button>
            </template>

            <!-- プレビュー後 -->
            <template v-else>
              <button
                @click="confirmUpload"
                class="btn-primary"
                :disabled="!previewFile"
              >
                確定
              </button>
              <button
                @click="retakePhoto"
                class="btn-secondary"
              >
                再撮影
              </button>
              <button
                @click="handleCloseModal"
                class="btn-danger"
              >
                キャンセル
              </button>
            </template>
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
