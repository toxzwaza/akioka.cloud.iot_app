<script setup>
import StockLayout from "@/Layouts/StockLayout.vue";
import { Link } from "@inertiajs/vue3";
import { onMounted, ref, reactive } from "vue";
import axios from "axios";
import { getImgPath, changeDateFormat } from "@/Helper/method";
import Chart from "@/Components/Stock/Inventory/BarChart.vue";
import _ from "lodash";

const props = defineProps({
  stock: Object,
});

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

// 入庫月平均
const receive_average = ref(0);

// 出庫月平均
const shipment_average = ref(0);

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
const handleFileChange = (event) => {
  const file = event.target.files[0];
  if (file) {
    console.log("file変更");
    selectedFile.value = file; // ファイルを保存
    // プレビュー用のURLを作成
    previewImage.img_path = URL.createObjectURL(file);
    previewImage.msg = "こちらの画像で更新します。よろしいですか？";
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
  if (!selectedFile.value) {
    console.error("No file selected");
    return;
  }

  const formData = new FormData();
  formData.append("file", selectedFile.value);
  formData.append("stock_id", props.stock.id);

  axios
    .post(route("stock.updateFile"), formData, {
      headers: {
        "Content-Type": "multipart/form-data",
      },
    })
    .then((res) => {
      console.log("File uploaded successfully:", res.data);
      if (res.data.status) {
        if (confirm("ファイルのアップロードが完了しました。")) {
          window.location.reload();
        }
      } else {
        alert("ファイルのアップロード中にエラーが発生しました。");
      }
    })
    .catch((error) => {
      console.error("Error uploading file:", error);
    });
};

const orderStock = () => {
  const hasPendingOrderRequest = props.stock.order_requests.some(
    (order_request) => order_request.status === 0
  );
  if (hasPendingOrderRequest) {
    if (!confirm("未受理の発注依頼がありますが、発注依頼を行いますか？")) {
      return;
    }
  } else {
    if (
      !confirm(`${props.stock.name} の発注依頼を行います。よろしいですか？`)
    ) {
      return;
    }
  }
  axios
    .post(route("stock.order.store"), { stock_id: props.stock.id })
    .then((res) => {
      console.log(res.data);
      if (res.data.status) {
        if (confirm("発注依頼が完了しました。")) {
          window.location.reload();
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
onMounted(() => {
  console.log(props.stock);
  if (props.stock.stock_storage.length == 1) {
    stock_storage.value = props.stock.stock_storage[0];
  }
  initial_orders.value = props.stock.initial_orders;

  // それぞれの平均を取得
  receive_average.value = Math.round(_.mean(props.stock.receives));
  shipment_average.value = Math.round(_.mean(props.stock.shipments));

  // 滞留しているか調査

  // 格納先に格納されており、直近半年間の出庫合計が０より大きい
  if (props.stock.stock_storage.length > 0 && isLastSixShipmentsZero()) {
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
          <h1 class="stock_name">
            {{ props.stock.name }}
            <a
              :href="`http://monokanri-manage.local/stock/edit/stocks/${props.stock.id}`"
              target="blank"
              ><i class="fas fa-edit ml-2 cursor-pointer"></i
            ></a>
          </h1>

          <h2 class="stock_s_name">品番: {{ props.stock.s_name }}</h2>

          <div class="file_container flex flex-col mt-6 mb-2">
            <div class="open_camera_button">
              <img src="/images/stocks/open_camera_button.png" alt="">
              <input type="file" capture="camera" @change="handleFileChange" />
            </div>

            <img
              class="stock_img w-2/3 mt-2"
              :src="getImgPath(props.stock.img_path)"
              alt=""
            />
          </div>
        </div>
        <div id="right_container" class="w-1/2">
          <!-- 格納先が１つの場合 -->
          <section id="one_address" class="px-4" v-if="stock_storage">
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
              <details>
                <summary class="bg-gray-500 text-white pl-4 mt-4">
                  数量編集(管理者のみ)
                </summary>
                <p class="text-sm text-red-500 mt-2 mb-1">
                  数量を入力して、確定ボタンを押してください。
                </p>
                <div class="flex items-center justify-start">
                  <input
                    class="appearance-none block w-1/2 bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                    type="number"
                    name="change_quantity"
                    id=""
                    v-model="change_quantity"
                  />
                  <button
                    @click="changeQuantity"
                    v-if="change_quantity"
                    class="bg-transparent hover:bg-blue-500 text-blue-700 font-semibold hover:text-white py-2 px-4 border border-blue-500 hover:border-transparent rounded ml-2 whitespace-nowrap"
                  >
                    確定
                  </button>
                </div>
              </details>
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

              <button @click="orderStock">
                <img src="/images/stocks/icons/order.png" alt="発注画面" />
              </button>
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

          <!-- 格納先が複数ある場合 -->
          <section v-else class="mb-8 some_addresses text-gray-600 body-font">
            <div class="container mx-auto">
              <h2 class="array_title">保管場所</h2>
              <div class="w-full mx-auto overflow-auto">
                <table class="table-auto w-full text-left whitespace-no-wrap">
                  <thead>
                    <tr>
                      <th
                        class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100 rounded-tl rounded-bl"
                      >
                        保管倉庫
                      </th>
                      <th
                        class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
                      >
                        アドレス
                      </th>
                      <th
                        class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
                      >
                        数量
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="stock_storage in props.stock.stock_storage"
                      :key="stock_storage.id"
                    >
                      <td class="px-4 py-3">
                        {{ stock_storage.location_name }}
                      </td>
                      <td class="px-4 py-3">{{ stock_storage.address }}</td>
                      <td class="px-4 py-3">{{ stock_storage.quantity }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </section>
        </div>
      </div>

      <section
        id="order_container"
        :class="{
          'w-full mt-8 text-gray-600 body-font flex justify-between items-center': true,
          'opacity-20': previewImage.img_path,
        }"
      >
        <div class="container mx-auto mr-2">
          <h2 class="array_title text-green-500">発注依頼</h2>
          <div class="w-full mx-auto overflow-auto">
            <table class="table-auto w-full text-left whitespace-no-wrap">
              <thead>
                <tr>
                  <th
                    class="py-4 title-font tracking-wider font-medium text-gray-500 text-md"
                  >
                    発注依頼日
                  </th>
                  <th
                    class="py-4 title-font tracking-wider font-medium text-gray-500 text-md"
                  >
                    個数
                  </th>
                  <th
                    class="py-4 title-font tracking-wider font-medium text-gray-500 text-md"
                  >
                    注文者
                  </th>
                  <th
                    class="py-4 title-font tracking-wider font-medium text-gray-500 text-md"
                  >
                    ステータス
                  </th>
                  <th
                    class="py-4 title-font tracking-wider font-medium text-gray-500 text-md"
                  ></th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="order_request in props.stock.order_requests"
                  :key="order_request.id"
                >
                  <td class="py-4">
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
                  <td class="py-4">
                    {{ order_request.quantity ? order_request.quantity : "-" }}
                  </td>
                  <td class="py-4">
                    {{
                      order_request.user_name ? order_request.user_name : "-"
                    }}
                  </td>
                  <td
                    :class="{
                      'py-4 font-bold': true,
                      'text-green-500': order_request.status,
                      'text-red-500': !order_request.status,
                    }"
                  >
                    {{ order_request.status ? "受理" : "未受理" }}
                  </td>
                  <td
                    :class="{
                      'py-4 font-bold text-center': true,
                    }"
                  >
                    <button
                      @click="deleteOrderRequest(order_request.id)"
                      v-if="!order_request.status"
                      class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 text-sm px-4 rounded-full"
                    >
                      取消
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div class="container mx-auto ml-2">
          <h2 class="array_title text-red-500">発注履歴</h2>
          <div class="w-full mx-auto overflow-auto">
            <table class="table-auto w-full text-left whitespace-no-wrap">
              <thead>
                <tr>
                  <th
                    class="py-4 title-font tracking-wider font-medium text-gray-500 text-md"
                  >
                    発注日
                  </th>
                  <th
                    class="py-4 title-font tracking-wider font-medium text-gray-500 text-md"
                  >
                    個数
                  </th>
                  <th
                    class="py-4 title-font tracking-wider font-medium text-gray-500 text-md"
                  >
                    ステータス
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="order in props.stock.initial_orders" :key="order.id">
                  <td class="py-4">
                    {{
                      new Date(order.order_date).toLocaleDateString("ja-JP", {
                        year: "numeric",
                        month: "2-digit",
                        day: "2-digit",
                      })
                    }}
                  </td>
                  <td class="py-4">{{ order.quantity }}</td>
                  <td
                    :class="{
                      'py-4 font-bold': true,
                      'text-green-500': order.receipt_flg || order.receive_flg,
                      'text-red-500': !order.receipt_flg && !order.receive_flg,
                    }"
                  >
                    <button @click="checkDeliFile(order.delifile_path)">
                      {{
                        order.receipt_flg
                          ? "納品済(入庫)"
                          : order.receive_flg
                          ? "納品済(引渡)"
                          : "未納品"
                      }}
                    </button>
                  </td>
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
            平均入庫数 : {{ receive_average + props.stock.solo_unit }}
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
            平均出庫数 : {{ shipment_average + props.stock.solo_unit }}
          </h3>
          <Chart
            :title="'過去12カ月間出庫データ'"
            :data="props.stock.shipments"
            :inventory_operation_id="8"
            :average="shipment_average"
          />
        </div>
      </section>
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
  & .file_container {
    width: 80%;
    & .open_camera_button {
      height: 4vh;
      width: 50%;
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
    & .stock_img {
      width: 100%;
      object-fit: contain;

      box-shadow: rgba(0, 0, 0, 0.35) 0px 5px 15px;
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
</style>
