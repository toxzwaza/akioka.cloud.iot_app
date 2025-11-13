<script setup>
import StockLayout from "@/Layouts/StockLayout.vue";
import { Link } from "@inertiajs/vue3";
import { onMounted, ref, reactive } from "vue";
import axios from "axios";
import { getImgPath, changeDateFormat } from "@/Helper/method";
import Chart from "@/Components/Stock/Inventory/BarChart.vue";
import PickStorageAddress from "@/Components/Stock/Inventory/PickStorageAddress.vue";
import EditAlias from "@/Components/Stock/Inventory/EditAlias.vue";
import StockRequest from "@/Components/Stock/StockRequest.vue";
import _ from "lodash";
import Swiper from "swiper";
import { Navigation, Pagination, Thumbs } from "swiper/modules";
import "swiper/css";
import "swiper/css/navigation";
import "swiper/css/pagination";
import "swiper/css/thumbs";

const props = defineProps({
  stock: Object,
  request_user: Object,
  processes: Array,
  users: Array,
});

const request_user = reactive({
  id: 0,
  name: "未設定",
});

const deviceName = ref(""); //端末ID

const stock_storage = ref(null);
const initial_orders = ref(null);

// 滞留品フラグ
const retention = reactive({
  retention_flg: 0,
  dif_month: null,
  start_date: null,
});

const previewImage = reactive({
  img_path: null,
  msg: null,
  update_button: null,
});

const selectedFile = ref(null);

const change_quantity = ref(null);

// 画像の配列を作成（正しく画像パスを取得）
const images = ref([
  props.stock.img_path,
  ...props.stock.stock_images.map((image) => image.img_path),
]);

// 入庫月平均
const receive_average = ref(0);

// 出庫月平均
const shipment_average = ref(0);

// サムネイル用のSwiperインスタンスを保持するref
const thumbsSwiper = ref(null);

// アクティブなスライドのインデックスを保持するref
const activeSlideIndex = ref(0);

// 画像タイプを保持するref
const selectedFileType = ref("");

// テンキー入力処理
const handleTenKeyInput = (value) => {
  if (change_quantity.value === null || change_quantity.value === undefined) {
    change_quantity.value = value.toString();
  } else {
    change_quantity.value = change_quantity.value.toString() + value.toString();
  }
};

// テンキー削除処理
const handleTenKeyDelete = () => {
  if (change_quantity.value && change_quantity.value.toString().length > 0) {
    const currentValue = change_quantity.value.toString();
    change_quantity.value = currentValue.slice(0, -1);

    // 空になった場合はnullに設定
    if (change_quantity.value === "") {
      change_quantity.value = null;
    }
  }
};

// 数量変更
const changeQuantity = () => {
  if (
    confirm(`数量を ${change_quantity.value} に変更します。よろしいですか？`)
  ) {
    // 数量更新処理
    axios
      .post(route("stock.changeQuantity"), {
        stock_id: props.stock.id,
        stock_storage_id: stock_storage.value.id,
        quantity: change_quantity.value,
      })
      .then((res) => {
        console.log(res.data);
        if (res.data.status) {
          if (confirm("更新が完了しました。")) {
            window.location.reload();
          }
        } else {
          alert(res.data.msg);
        }
      })
      .catch((error) => {
        console.log(error);
      });
  }
};

// 画像プレビュー
const handleFileChange = (event, type) => {
  const files = event.target.files;
  if (files && files.length > 0) {
    console.log("file変更");
    selectedFile.value = files; // ファイルを保存
    selectedFileType.value = type; // 画像タイプを保存
    // プレビュー用のURLを作成
    previewImage.img_path = URL.createObjectURL(files[0]);
    previewImage.msg = `${files.length}枚の画像をアップロードします。よろしいですか？`;
    previewImage.update_button = true;
  }
};
const checkDeliFile = (deli_file) => {
  console.log(deli_file);
  previewImage.img_path = `https://akioka.cloud/storage/${deli_file}`;
  previewImage.msg = "納品書確認";
  previewImage.update_button = false;
};

// 画像アップロード
const uploadFile = () => {
  if (!selectedFile.value || selectedFile.value.length === 0) {
    console.error("No files selected");
    return;
  }

  const formData = new FormData();
  // 複数ファイルを追加
  for (let i = 0; i < selectedFile.value.length; i++) {
    formData.append(`files[${i}]`, selectedFile.value[i]);
  }
  formData.append("stock_id", props.stock.id);
  formData.append("image_type", selectedFileType.value); // 画像タイプを追加

  axios
    .post(route("stock.updateFile"), formData, {
      headers: {
        "Content-Type": "multipart/form-data",
      },
    })
    .then((res) => {
      console.log("Files uploaded successfully:", res.data);
      if (res.data.status) {
        if (confirm("ファイルのアップロードが完了しました。")) {
          window.location.reload();
        }
      } else {
        alert("ファイルのアップロード中にエラーが発生しました。");
      }
    })
    .catch((error) => {
      console.error("Error uploading files:", error);
    });
};

const handleStockRequest = (form) => {
  const hasPendingOrderRequest = props.stock.order_requests.some(
    (order_request) => order_request.status === 0
  );
  if (hasPendingOrderRequest) {
    if (!confirm("未受理の発注依頼がありますが、発注依頼を行いますか？")) {
      return;
    }
  } else {
    if (
      !confirm(
        `以下の内容で発注依頼を行います。よろしいですか？\n物品: ${props.stock.name}`
      )
    ) {
      return;
    }
  }

  axios
    .post(route("stock.order.store"), {
      stock_id: props.stock?.id,
      request_user_id: form?.user_id, //物品依頼者
      stock_storage_id: stock_storage?.value?.id,
      desire_delivery_date: form?.desire_delivery_date, //希望納期
      now_quantity: form?.now_quantity, //現在個数
      now_quantity_unit: form?.now_quantity_unit, //現在個数単位
      digest_date: form?.digest_date, //消化予定日
      quantity: form?.quantity, //必要数量
      quantity_unit: form?.quantity_unit, //必要数量単位
      description: form?.description, //備考
      device_name: deviceName.value ?? "",
    })
    .then((res) => {
      console.log(res.data);
      if (res.data.status) {
        if (confirm("発注依頼が完了しました。")) {
          // window.location.reload();
        }
      }
    })
    .catch((error) => {
      console.log(error);
    });
};

const deleteOrderRequest = (order_request_id) => {
  if (confirm("発注依頼を取消します。よろしいですか？")) {
    axios
      .delete(
        route("stock.order.delete", { order_request_id: order_request_id })
      )
      .then((res) => {
        console.log(res.data);
        if (res.data.status) {
          alert("注文依頼を削除しました。");
          window.location.reload();
        }
      })
      .catch((error) => {
        console.error(error);
      });
  }
};

const isLastSixShipmentsZero = () => {
  const shipments = props.stock.shipments;
  const lastSixShipments = shipments.slice(-6);
  const sumOfLastSixShipments = lastSixShipments.reduce(
    (acc, val) => acc + val,
    0
  );
  return sumOfLastSixShipments === 0;
};

const parentDate = (targetDate) => {
  const currentDate = new Date();
  const monthDifference =
    (currentDate.getFullYear() - targetDate.getFullYear()) * 12 +
    (currentDate.getMonth() - targetDate.getMonth());

  return monthDifference;
};

const handleUpdateLocation = (payload) => {
  // storage_address_id , quantity
  axios
    .post(route("stock.createStockStorage"), {
      stock_id: props.stock.id,
      storage_address_id: payload.storage_address_id,
      quantity: payload.quantity,
      // 既存の格納先がある場合
      stock_storage_id: stock_storage.value ? stock_storage.value.id : null,
    })
    .then((res) => {
      console.log(res.data);

      if (!stock_storage.value) {
        window.location.href = route("stock.inventory.show", {
          stock_id: props.stock.id,
          stock_storage_id: res.data.stock_storage_id,
        });
      } else {
        window.location.reload();
      }
    })
    .catch((error) => {
      console.log(error);
    });
};

const deleteImage = (index) => {
  if (index) {
    const image_path = images.value[index];
    console.log(image_path);

    axios
      .delete(route("stock.deleteImage"), {
        params: {
          image_path: image_path,
        },
      })
      .then((res) => {
        console.log(res.data);
        if (res.data.status) {
          window.location.reload();
        }
      })
      .catch((error) => {
        console.log(error);
      });
  }
};

onMounted(() => {
  const savedId = localStorage.getItem("device_id");
  if (savedId && savedId != "null") {
    deviceName.value = savedId;
    console.log(deviceName.value);
  }

  console.log(props.stock);
  if (props.request_user) {
    request_user.id = props.request_user.id;
    request_user.name = props.request_user.name;
  }

  stock_storage.value = props.stock.stock_storage;
  initial_orders.value = props.stock.initial_orders;

  // それぞれの平均を取得
  receive_average.value = Math.round(_.mean(props.stock.receives));
  shipment_average.value = Math.round(_.mean(props.stock.shipments));

  // 滞留しているか調査

  // 格納先に格納されており、直近半年間の出庫合計が０より大きい
  if (
    props.stock.stock_storage &&
    props.stock.stock_storage.length > 0 &&
    isLastSixShipmentsZero()
  ) {
    const stockCreatedDate = new Date(props.stock.stock_storage[0].created_at);
    retention.start_date = stockCreatedDate;

    const dif_month = parentDate(stockCreatedDate);
    retention.dif_month = dif_month;
    if (dif_month > 12) {
      // 滞留品
      console.log("滞留しています");
      retention.retention_flg = 2;
    } else if (dif_month > 6) {
      console.log("半滞留品です");
      retention.retention_flg = 1;
    }
  }

  // サムネイル用のスライドショーを先に初期化
  thumbsSwiper.value = new Swiper(".swiper-thumbs", {
    modules: [Navigation],
    spaceBetween: 10,
    slidesPerView: 4,
    freeMode: true,
    watchSlidesProgress: true,
  });

  // メインのスライドショー
  const mainSwiper = new Swiper(".swiper", {
    modules: [Navigation, Pagination, Thumbs],
    loop: images.value.length > 1,
    pagination: {
      el: ".swiper-pagination",
      clickable: true,
    },
    navigation: {
      nextEl: ".swiper-button-next",
      prevEl: ".swiper-button-prev",
    },
    thumbs: {
      swiper: thumbsSwiper.value,
      slideThumbActiveClass: "swiper-slide-active",
    },
    on: {
      slideChange: function () {
        activeSlideIndex.value = this.realIndex;
      },
    },
  });
});
</script>
<template>
  <StockLayout :title="'在庫詳細'">
    <template #content>
      <div v-if="previewImage.img_path" id="previewImage" class="py-4 px-8">
        <!-- 画像変更時のダイアログボックス -->
        <div class="flex justify-between items-center my-4">
          <p class="">
            {{
              previewImage.msg
                ? previewImage.msg
                : "こちらの画像で更新します。よろしいですか？"
            }}
          </p>
          <div class="button_container">
            <button
              @click="previewImage.img_path = null"
              class="bg-transparent hover:bg-blue-500 text-blue-700 font-semibold hover:text-white py-2 px-4 border border-blue-500 hover:border-transparent rounded mr-2"
            >
              戻る
            </button>
            <button
              v-if="previewImage.update_button"
              @click="uploadFile"
              class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded ml-2"
            >
              更新
            </button>
          </div>
        </div>
        <div class="img_container">
          <img :src="previewImage.img_path" />
        </div>
      </div>
      <div :class="{ flex: true, 'opacity-20': previewImage.img_path }">
        <div id="left_container" class="w-1/2">
          <h2 class="stock_id text-gray-700 text-sm font-mono">
            ID: {{ props.stock.id }}
          </h2>

          <h1 class="stock_name font-mono">
            {{ `${props.stock.name}` }}
            <a
              :href="`http://monokanri-manage.local/stock/stocks/show/${props.stock.id}`"
              target="blank"
              ><i class="fas fa-edit ml-2 cursor-pointer"></i
            ></a>
          </h1>

          <h2 class="stock_s_name font-mono">品番: {{ props.stock.s_name }}</h2>

          <!-- 略名 -->
          <h3 class="stock_aliases font-mono">
            略名:
            <span
              v-for="alias in props.stock.aliases"
              :key="alias.id"
              class="bg-gray-100 text-gray-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded-sm dark:bg-gray-700 dark:text-gray-400 border border-gray-500 ml-2"
              >{{ alias.alias }}</span
            >
          </h3>
          <h3 class="stock_aliases font-mono">
            得意先:
            <span
              v-for="(stock_supplier, index) in props.stock.stock_suppliers"
              :key="stock_supplier.id"
              :class="[
                index === 0 
                  ? 'bg-green-100 text-green-800 border-green-500 dark:bg-green-900 dark:text-green-300 dark:border-green-600' 
                  : 'bg-gray-100 text-gray-800 border-gray-500 dark:bg-gray-700 dark:text-gray-400 dark:border-gray-500',
                'text-xs font-medium me-2 px-2.5 py-0.5 rounded-sm border ml-2'
              ]"
              >{{ stock_supplier.supplier_name }}{{ index === 0 ? ' (適用中)' : '' }}</span
            >
          </h3>

          <!-- 画像変更ボタン -->
          <div class="file_container flex flex-col mt-6 mb-2">
            <div id="setting_img" class="flex justify-between items-center">
              <div class="open_camera_button">
                <img src="/images/stocks/open_camera_button.png" alt="" />
                <input
                  type="file"
                  capture="camera"
                  @change="(e) => handleFileChange(e, 'thumbnail')"
                  accept="image/*"
                />
              </div>

              <div class="open_camera_button">
                <img src="/images/stocks/create_etc_button.png" alt="" />
                <input
                  type="file"
                  capture="camera"
                  @change="(e) => handleFileChange(e, 'etc')"
                  accept="image/*"
                />
              </div>
            </div>

            <!-- スライドショー -->
            <div class="swiper-container">
              <div class="swiper">
                <div class="swiper-wrapper">
                  <div
                    class="swiper-slide"
                    v-for="(image, index) in images"
                    :key="index"
                  >
                    <img
                      class="stock_img w-2/3 mt-2"
                      :src="getImgPath(image)"
                      :alt="'スライド' + (index + 1)"
                    />
                  </div>
                </div>

                <!-- ナビゲーションボタン -->
                <div class="swiper-button-prev"></div>
                <div class="swiper-button-next"></div>

                <!-- ページネーション -->
                <div class="swiper-pagination"></div>
              </div>

              <!-- サムネイルスライドショー -->
              <div class="swiper-thumbs mt-4">
                <div class="swiper-wrapper">
                  <div
                    class="swiper-slide"
                    v-for="(image, index) in images"
                    :key="'thumb-' + index"
                  >
                    <div class="thumb-container">
                      <img
                        class="thumb-img"
                        :src="getImgPath(image)"
                        :alt="'サムネイル' + (index + 1)"
                      />
                      <button
                        class="delete-btn"
                        v-if="index === activeSlideIndex && index"
                        @click="deleteImage(index)"
                      >
                        <i class="fas fa-trash-alt"></i>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div id="right_container" class="w-1/2">
          <!-- 格納先が登録されている場合 -->
          <section v-if="stock_storage" id="one_address" class="px-4">
            <div class="flex flex-col">
              <h1 id="location_name" class="text-center mb-4">
                {{ stock_storage.location_name }}
              </h1>
              <div class="flex justify-around">
                <h1 id="address" class="">{{ stock_storage.address }}</h1>
                <h1 id="quantity" class="">{{ stock_storage.quantity }}個</h1>
              </div>
            </div>
            <div>
              <details class="manage_details">
                <summary class="text-white pl-4 mt-4">数量編集</summary>
                <div class="px-2 py-2 bg-gray-300">
                  <p class="text-sm text-red-500 mt-2 mb-1">
                    数量を入力して、確定ボタンを押してください。
                  </p>
                  <div class="flex items-center justify-start py-2 mb-2">
                    <input
                      class="appearance-none block w-1/2 bg-gray-50 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500 text-center font-bold text-xl pointer-events-none"
                      type="number"
                      name="change_quantity"
                      id=""
                      v-model="change_quantity"
                    />

                    <button
                      @click="changeQuantity"
                      v-if="change_quantity || change_quantity == '0'"
                      class="ml-4 text-sm bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                    >
                      確定
                    </button>
                  </div>

                  <div class="ten-keypad">
                    <div class="grid grid-cols-3 gap-2">
                      <button
                        class="ten-key-button"
                        @click="handleTenKeyInput(1)"
                      >
                        1
                      </button>
                      <button
                        class="ten-key-button"
                        @click="handleTenKeyInput(2)"
                      >
                        2
                      </button>
                      <button
                        class="ten-key-button"
                        @click="handleTenKeyInput(3)"
                      >
                        3
                      </button>
                      <button
                        class="ten-key-button"
                        @click="handleTenKeyInput(4)"
                      >
                        4
                      </button>
                      <button
                        class="ten-key-button"
                        @click="handleTenKeyInput(5)"
                      >
                        5
                      </button>
                      <button
                        class="ten-key-button"
                        @click="handleTenKeyInput(6)"
                      >
                        6
                      </button>
                      <button
                        class="ten-key-button"
                        @click="handleTenKeyInput(7)"
                      >
                        7
                      </button>
                      <button
                        class="ten-key-button"
                        @click="handleTenKeyInput(8)"
                      >
                        8
                      </button>
                      <button
                        class="ten-key-button"
                        @click="handleTenKeyInput(9)"
                      >
                        9
                      </button>
                      <button
                        class="ten-key-button"
                        @click="handleTenKeyDelete"
                        title="削除"
                      >
                        <i class="fas fa-backspace"></i>
                      </button>
                      <button
                        class="ten-key-button"
                        @click="handleTenKeyInput(0)"
                      >
                        0
                      </button>
                      <button
                        class="ten-key-button"
                        @click="change_quantity = null"
                        title="クリア"
                      >
                        <i class="fas fa-times"></i>
                      </button>
                    </div>
                  </div>
                </div>
              </details>
              <details class="manage_details">
                <summary class="text-white pl-4 mt-4">
                  格納先・アドレス編集
                </summary>
                <div class="px-2 py-2 bg-gray-300">
                  <PickStorageAddress
                    @updateLocation="handleUpdateLocation"
                    :quantity="stock_storage.quantity"
                    :stock_storage_id="stock_storage.id"
                  />
                </div>
              </details>
              <details class="alias_details">
                <summary class="text-white pl-4 mt-4">
                  略名登録・編集・削除
                </summary>
                <EditAlias
                  :aliases="props.stock.aliases"
                  :stock_id="props.stock.id"
                />
              </details>
            </div>

            <div class="my-8">
              <!-- 発注点 -->
              <h1 id="reorder_point" class="">
                <span class="label">発注点：</span>
                <span class="value">{{ stock_storage.reorder_point }}個</span>
              </h1>
            </div>

            <div id="button_container" class="mt-12 mb-12">
              <Link
                :href="
                  route('stock.shipment', {
                    stock_storage_address_id: stock_storage.id,
                  })
                "
                ><img src="/images/stocks/icons/shipment.png" alt="出庫画面"
              /></Link>

              <!-- <button @click="orderStock">
                <img src="/images/stocks/icons/order.png" alt="発注画面" />
              </button> -->
            </div>

            <!-- 滞留情報を表示 -->
            <div>
              <span
                :class="{
                  'rounded py-4 text-white block text-4xl font-bold text-center font-mono': true,
                  'bg-red-400': retention.retention_flg == 2,
                  'bg-orange-400': retention.retention_flg == 1,
                  'bg-green-400': !retention.retention_flg,
                }"
                >{{
                  retention.retention_flg == 2
                    ? "滞留品"
                    : retention.retention_flg == 1
                    ? "半滞留品"
                    : "正常"
                }}</span
              >

              <p v-if="retention.retention_flg" class="text-gray-700 mt-2">
                {{ `${changeDateFormat(retention.start_date)} より` }}
                <span class="text-red-500 text-lg font-bold"
                  >{{ `${retention.dif_month}` }}カ月</span
                >
                滞留しています。
                <br />
              </p>
              <p v-else class="text-gray-700 mt-2">
                この物品は滞留していません。
              </p>
            </div>
          </section>

          <!-- 格納先が登録されていない場合 -->
          <section v-else class="bg-gray-50 p-2 rounded">
            <h1 class="text-xl mb-4 text-gray-600 font-bold">
              ロケーション登録
            </h1>
            <PickStorageAddress
              :quantity="0"
              @updateLocation="handleUpdateLocation"
            />
          </section>
        </div>
      </div>
      <!-- 物品依頼画面 -->
      <StockRequest
        :processes="props.processes"
        :stock="props.stock"
        :users="props.users"
        :request_user="props.request_user"
        @submit="handleStockRequest"
      />

      <div>
        <section
          id="order_container"
          :class="{
            'w-full mt-8 text-gray-600 body-font flex justify-between items-center': true,
            'opacity-20': previewImage.img_path,
          }"
        >
          <div class="container mx-auto mr-2">
            <h2 class="array_title text-green-500">発注依頼</h2>
            <div id="archive_container" class="w-full mx-auto overflow-auto">
              <table class="table-auto w-full text-left whitespace-no-wrap border-collapse">
                <thead>
                  <tr class="bg-gray-100 dark:bg-gray-800">
                    <th
                      class="py-4 px-4 title-font tracking-wider font-medium text-gray-500 text-md border-b-2 border-gray-300 dark:border-gray-600 text-center"
                    >操作</th>
                    <th
                      class="py-4 px-4 title-font tracking-wider font-medium text-gray-500 text-md border-b-2 border-gray-300 dark:border-gray-600"
                    >
                      状況
                    </th>
                    <th
                      class="py-4 px-4 title-font tracking-wider font-medium text-gray-500 text-md border-b-2 border-gray-300 dark:border-gray-600"
                    >
                      発注依頼日
                    </th>
                    <th
                      class="py-4 px-4 title-font tracking-wider font-medium text-gray-500 text-md border-b-2 border-gray-300 dark:border-gray-600"
                    >
                      必要個数
                    </th>
                    <th
                      class="py-4 px-4 title-font tracking-wider font-medium text-gray-500 text-md border-b-2 border-gray-300 dark:border-gray-600"
                    >
                      現在個数
                    </th>
                    <th
                      class="py-4 px-4 title-font tracking-wider font-medium text-gray-500 text-md border-b-2 border-gray-300 dark:border-gray-600"
                    >
                      注文依頼者
                    </th>
                    <th
                      class="py-4 px-4 title-font tracking-wider font-medium text-gray-500 text-md border-b-2 border-gray-300 dark:border-gray-600"
                    >
                      注文者
                    </th>
                    <th
                      class="py-4 px-4 title-font tracking-wider font-medium text-gray-500 text-md border-b-2 border-gray-300 dark:border-gray-600"
                    >
                      備考
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="order_request in props.stock.order_requests"
                    :key="order_request.id"
                    :class="{
                      'bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30 border-l-4 border-red-400': !order_request.status,
                      'bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/30 border-l-4 border-green-400': order_request.status,
                      'transition-colors duration-150': true
                    }"
                  >
                    <td class="py-4 px-4 text-center border-b border-gray-200 dark:border-gray-700">
                      <button
                        @click="deleteOrderRequest(order_request.id)"
                        v-if="!order_request.status"
                        class="bg-red-500 hover:bg-red-700 text-white font-bold py-2.5 px-5 rounded-lg shadow-md hover:shadow-lg transition-all duration-200 transform hover:scale-105"
                      >
                        🗑️ 取消
                      </button>
                      <span v-else class="text-gray-400 dark:text-gray-500 text-sm">取消不可</span>
                    </td>
                    <td class="py-4 px-4 border-b border-gray-200 dark:border-gray-700">
                      <span
                        :class="{
                          'inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold': true,
                          'bg-green-200 text-green-800 dark:bg-green-700 dark:text-green-100': order_request.status,
                          'bg-red-200 text-red-800 dark:bg-red-700 dark:text-red-100': !order_request.status,
                        }"
                      >
                        {{ order_request.status ? "✓ 受理済" : "⚠ 未受理" }}
                      </span>
                    </td>
                    <td class="py-4 px-4 border-b border-gray-200 dark:border-gray-700">
                      {{
                        new Date(order_request.created_at).toLocaleDateString(
                          "ja-JP",
                          {
                            year: "numeric",
                            month: "2-digit",
                            day: "2-digit",
                          }
                        )
                      }}
                    </td>
                    <td class="py-4 px-4 border-b border-gray-200 dark:border-gray-700 font-semibold">
                      {{
                        order_request.quantity ? order_request.quantity : "-"
                      }}
                    </td>
                    <td class="py-4 px-4 border-b border-gray-200 dark:border-gray-700 font-semibold">
                      {{ order_request.now_quantity ?? "-" }}
                    </td>
                    <td class="py-4 px-4 border-b border-gray-200 dark:border-gray-700">
                      {{
                        order_request.request_user_name
                          ? order_request.request_user_name
                          : "-"
                      }}
                    </td>
                    <td class="py-4 px-4 border-b border-gray-200 dark:border-gray-700">
                      {{
                        order_request.user_name ? order_request.user_name : "-"
                      }}
                    </td>
                    <td class="py-4 px-4 border-b border-gray-200 dark:border-gray-700">
                      {{ order_request.description ?? "-" }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div class="container mx-auto ml-2">
            <h2 class="array_title text-red-500">発注履歴</h2>
            <div id="archive_container" class="w-full mx-auto overflow-auto">
              <table class="table-auto w-full text-left whitespace-no-wrap border-collapse">
                <thead>
                  <tr class="bg-gray-100 dark:bg-gray-800">
                    <th
                      class="py-4 px-4 title-font tracking-wider font-medium text-gray-500 text-md border-b-2 border-gray-300 dark:border-gray-600"
                    >
                      状況
                    </th>
                    <th
                      class="py-4 px-4 title-font tracking-wider font-medium text-gray-500 text-md border-b-2 border-gray-300 dark:border-gray-600"
                    >
                      発注日
                    </th>
                    <th
                      class="py-4 px-4 title-font tracking-wider font-medium text-gray-500 text-md border-b-2 border-gray-300 dark:border-gray-600"
                    >
                      依頼者
                    </th>
                    <th
                      class="py-4 px-4 title-font tracking-wider font-medium text-gray-500 text-md border-b-2 border-gray-300 dark:border-gray-600"
                    >
                      発注者
                    </th>
                    <th
                      class="py-4 px-4 title-font tracking-wider font-medium text-gray-500 text-md border-b-2 border-gray-300 dark:border-gray-600"
                    >
                      個数
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="order in props.stock.initial_orders"
                    :key="order.id"
                    :class="{
                      'bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30 border-l-4 border-red-400': !order.receipt_flg && !order.receive_flg,
                      'bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/30 border-l-4 border-green-400': order.receipt_flg || order.receive_flg,
                      'transition-colors duration-150': true
                    }"
                  >
                    <td class="py-4 px-4 border-b border-gray-200 dark:border-gray-700">
                      <button 
                        @click="checkDeliFile(order.delifile_path)"
                        class="w-full text-left"
                      >
                        <span
                          :class="{
                            'inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold': true,
                            'bg-green-200 text-green-800 dark:bg-green-700 dark:text-green-100': order.receipt_flg || order.receive_flg,
                            'bg-red-200 text-red-800 dark:bg-red-700 dark:text-red-100': !order.receipt_flg && !order.receive_flg,
                          }"
                        >
                          {{
                            order.receipt_flg
                              ? "✓ 納品済(入庫)"
                              : order.receive_flg
                              ? "✓ 納品済(引渡)"
                              : "⚠ 未納品"
                          }}
                        </span>
                      </button>
                    </td>
                    <td class="py-4 px-4 border-b border-gray-200 dark:border-gray-700">
                      {{
                        new Date(order.order_date).toLocaleDateString("ja-JP", {
                          year: "numeric",
                          month: "2-digit",
                          day: "2-digit",
                        })
                      }}
                    </td>

                    <td class="py-4 px-4 border-b border-gray-200 dark:border-gray-700">{{ order.user_name ?? "-" }}</td>
                    <td class="py-4 px-4 border-b border-gray-200 dark:border-gray-700">{{ order.order_user_name ?? "-" }}</td>
                    <td class="py-4 px-4 border-b border-gray-200 dark:border-gray-700 font-semibold">{{ order.quantity ?? "-" }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <!-- 出庫・入庫変動グラフを表示 -->
        <section
          id="chart_container"
          class="w-full mt-8 text-gray-600 body-font flex justify-between items-center"
        >
          <div class="container mx-auto mr-2">
            <h3 class="font-bold text-gray-500">
              月平均入庫数 : {{ receive_average + props.stock.solo_unit }}
            </h3>
            <Chart
              :title="'過去12カ月間入庫データ'"
              :data="props.stock.receives"
              :inventory_operation_id="2"
              :average="receive_average"
            />
          </div>
          <div class="container mx-auto ml-2">
            <h3 class="font-bold text-gray-500">
              月平均出庫数 : {{ shipment_average + props.stock.solo_unit }}
            </h3>
            <Chart
              :title="'過去12カ月間出庫データ'"
              :data="props.stock.shipments"
              :inventory_operation_id="8"
              :average="shipment_average"
            />
          </div>
        </section>
      </div>
    </template>
  </StockLayout>
</template>
<style scoped lang="scss">
#previewImage {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  z-index: 2;
  height: 80vh;
  width: 80vw;
  background-color: rgb(255, 255, 255);
  border-radius: 5px;
  box-shadow: rgba(99, 99, 99, 0.2) 0px 2px 8px 0px;
  & p {
    font-size: 1.1rem;
    font-family: serif;
    color: #109ff3;
    font-weight: bold;
  }

  & .img_container {
    width: 100%;
    height: 80%;
    & img {
      width: 100%;
      height: 100%;
      object-fit: contain;
    }
  }
}

#left_container {
  & h1 {
    font-size: 1.2rem;
  }
  & h2 {
    font-size: 1.1rem;
  }

  & .stock_name {
    font-size: 2rem;
    font-weight: bold;
    color: #109ff3;
  }
  & .stock_s_name {
    font-size: 1.6rem;
    color: gray;
  }
  & .stock_aliases {
    font-size: 1.2rem;
    color: gray;
  }
  & .file_container {
    width: 95%;
    & .open_camera_button {
      height: 5vh;
      width: 45%;
      position: relative;

      & img {
        position: absolute;
        top: 0;
        left: 0;

        height: 100%;
        width: 100%;
        object-fit: contain;
      }

      & input[type="file"] {
        position: absolute;
        top: 0;
        left: 0;

        height: 100%;
        width: 100%;
        object-fit: cover;
        opacity: 0;
        z-index: 2;
      }
    }
  }
}

#right_container {
  & #one_address {
    & h1 {
      font-weight: bold;
      font-size: 2.6rem;

      &#location_name {
        color: #646464;
      }
      &#address {
        color: rgb(83, 83, 83);
      }
      &#quantity {
        font-weight: normal;
        color: rgb(102, 102, 102);
      }
      &#reorder_point {
        display: flex;
        justify-content: center;
        align-items: baseline;
        color: rgb(44, 44, 44);
        border-bottom: 2px dashed rgba(126, 126, 126, 0.575);

        & span {
          display: inline-block;
          text-align: center;

          &.label {
            font-size: 2rem;
            width: 30%;
          }
          &.value {
            font-size: 3rem;
            color: rgb(255, 51, 51);
            width: 20%;
          }
        }
      }
    }

    & .manage_details,
    .alias_details {
      & summary {
        border-radius: 5px;
        background-color: rgb(59 130 246);
        font-family: monospace;
        padding: 1% 2%;
      }
      &[open] {
        & summary {
          border-radius: 5px 5px 0 0;
        }
      }
      & > div {
        border-radius: 0 0 5px 5px;
      }
    }
  }

  & #button_container {
    display: flex;
    justify-content: space-between;
    align-items: center;

    height: 80px;

    & a,
    button {
      display: block;
      width: 30%;
      & img {
        width: 100%;
        height: 100%;
        object-fit: contain;
      }
    }
  }
}

#order_container {
  height: 45vh;

  & > div {
    height: 100%;
    overflow-y: auto;

    background-color: rgb(255, 255, 255);
    padding: 1rem;
    border-radius: 5px;
    box-shadow: rgba(99, 99, 99, 0.2) 0px 2px 8px 0px;

    & .array_title {
      font-size: 1.2rem;
      font-weight: bold;
    }

    & #archive_container {
      overflow-x: auto;

      // スクロールバー
      &::-webkit-scrollbar {
        width: 8px;
        height: 8px;
      }
      &::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
      }

      &::-webkit-scrollbar-thumb {
        background: linear-gradient(180deg, #4a90e2, #007aff);
        border-radius: 10px;
      }

      &::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(180deg, #007aff, #005bb5);
      }

      ////

      & table {
        table-layout: auto;
        width: 100%;
        & tr:nth-child(even) {
          background-color: #f9f9f9;
        }
        & tr:nth-child(odd) {
          background-color: #ffffff;
        }
        & td,
        th {
          padding: 0.8rem 0.6rem;
          text-align: left;
          white-space: nowrap;
        }
      }
    }
  }
}

#chart_container {
  & > div {
    background-color: rgb(255, 255, 255);
    padding: 1rem;
    border-radius: 5px;
    box-shadow: rgba(99, 99, 99, 0.2) 0px 2px 8px 0px;

    & .array_title {
      font-size: 1.2rem;
      font-weight: bold;
    }

    & canvas {
      height: 100%;
      width: 100%;
      object-fit: cover;
    }
  }
}

.swiper-container {
  width: 100%;
}

.swiper {
  width: 100%;
  height: 400px;
}

.swiper-slide img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.swiper-thumbs {
  height: 100px;
  box-sizing: border-box;
  padding: 10px 0;

  & .swiper-slide {
    width: 25%;
    height: 100%;
    opacity: 0.4;
    cursor: pointer;

    .thumb-container {
      position: relative;
      width: 100%;
      height: 100%;
    }

    .delete-btn {
      position: absolute;
      bottom: 5px;
      right: 5px;
      background: #ff4444;
      color: white;
      border: none;
      border-radius: 50%;
      width: 24px;
      height: 24px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.3s ease;
      z-index: 10;

      &:hover {
        background: #cc0000;
        transform: scale(1.1);
      }

      i {
        font-size: 12px;
      }
    }
  }

  & .swiper-slide-active {
    opacity: 1;
    border: 3px solid #109ff3;
    border-radius: 6px;
  }
}

.thumb-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 4px;
}

.ten-keypad {
  margin-top: 1rem;

  .ten-key-button {
    background-color: #f3f4f6;
    border: 2px solid #d1d5db;
    border-radius: 8px;
    padding: 0.75rem;
    font-size: 1.25rem;
    font-weight: bold;
    color: #374151;
    cursor: pointer;
    transition: all 0.2s ease;
    min-height: 3rem;
    display: flex;
    align-items: center;
    justify-content: center;

    &:hover {
      background-color: #e5e7eb;
      border-color: #9ca3af;
      transform: translateY(-1px);
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    &:active {
      background-color: #d1d5db;
      transform: translateY(0);
      box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    }

    &:focus {
      outline: none;
      border-color: #3b82f6;
      box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
  }
}
</style>
