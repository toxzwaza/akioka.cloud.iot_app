<script setup>
import ReceiveLayout from "@/Layouts/ReceiveLayout.vue";
import { onMounted, ref, reactive } from "vue";
import { Link, router } from "@inertiajs/vue3";
import axios from "axios";

const props = defineProps({
  order: Object,
  supplier_id: Number,
  locations: Array,
  storage_addresses: Array,
});

// 対象格納先在庫数
const storage_quantity = ref(0);
// カテゴリーリスト
const classifications = ref([]);
// 仕入れ先リスト
const suppliers = ref([]);

// 格納先アドレスリスト
const storage_addresses = ref([]);

// 納入換算数
const quantity_per_org = ref(0);

// 数量登録用フォーム
const form = reactive({
  id: props.order.id,
  stock_id: props.order.stock_id,
  storage_address_id: null,
  stock_storage_id: null,
  conversion_flg: 0,
  quantity: props.order.quantity - props.order.split_quantity_sum,
  calc_quantity: props.order.quantity - props.order.split_quantity_sum,
  signage: 1,
});

// 在庫データ新規登録用フォーム
const stock_create_form = reactive({
  order_id: null,
  supplier_id: null,
  deli_location: null,
  classification_id: null,
  storage_address_id: null,
});

// 在庫新規登録
const createStock = () => {
  if (
    stock_create_form.order_id &&
    stock_create_form.supplier_id &&
    stock_create_form.classification_id &&
    stock_create_form.storage_address_id
  ) {
    axios
      .post(route("stock.receive.store"), stock_create_form)
      .then((res) => {
        if (res.data.status === true) {
          if (
            confirm(
              "登録が完了しました。続いて、数量登録に移りますが、よろしいですか？"
            )
          ) {
            location.reload();
          }
        } else {
          alert(
            "登録に失敗しました。再度やり直すか、管理者へ連絡してください。"
          );
          location.reload();
        }
      })
      .catch((error) => {
        console.log(error);
      });
  } else {
    alert("未入力の項目があります。");
  }
};

const updateDelivery = () => {
  if (confirm("納品登録をおこないますか？")) {
    router.get(route("stock.receive.updateDelivery"), {
      id: form.id,
      stock_id: form.stock_id,
      stock_storage_id: form.stock_storage_id,
      storage_address_id: form.storage_address_id,
      conversion_flg: form.conversion_flg,
      quantity: form.quantity,
      calc_quantity: form.calc_quantity,
      signage: form.signage,
    });
  }
};

const sortStorageAddresses = (locationId) => {
  storage_addresses.value = props.storage_addresses.filter(
    (address) => address.location_id == locationId
  );
  console.log(storage_addresses.value);
};

const changeConversion = (val) => {
  calcConversionQuantity();
  form.calc_quantity = form.quantity * quantity_per_org.value;

  if (!val) {
    form.conversion_flg = 0;
    form.calc_quantity = form.quantity;
  }
};

// 換算値を計算
const calcConversionQuantity = () => {
  form.calc_quantity = form.quantity * quantity_per_org.value;
};

// 換算値変更時の処理
const handleChangeQuantityPerOrg = () => {
  calcConversionQuantity();

  if (confirm("換算値が更新されました。次回以降のデフォルトに設定しますか？")) {
    axios
      .post(route("stock.receive.updateQuantityPerOrg"), {
        stock_id: props.order.stock_id,
        quantity_per_org: quantity_per_org.value,
      })
      .then((res) => {
        console.log(res.data);
        alert("換算値の設定が完了しました");
      })
      .catch((error) => {
        console.log(error);
      });
  }
};

// 納入数変更再計算
const handleChangeQuantity = () => {
  if (form.quantity > props.order.quantity) {
    alert("発注数より大きい数が入力されています。");
    form.quantity = props.order.quantity;
  } else {
    // 小さい場合
    if (form.conversion_flg) {
      calcConversionQuantity();
    } else {
      console.log(storage_quantity.value, form.quantity);
      form.calc_quantity = form.quantity;
    }
  }
};
onMounted(() => {
  console.log("order", props.order);
  console.log("supplier_id", props.supplier_id);
  console.log("storage_addresses", props.storage_addresses);

  if (props.order.stock_storages && props.order.stock_storages.length > 0) {
    form.storage_address_id = props.order.stock_storages[0].address_id;
    form.stock_storage_id = props.order.stock_storages[0].stock_storage_id;
    storage_quantity.value = props.order.stock_storages[0].storage_quantity;
  }

  // 新規作成の場合カテゴリーリストを取得
  // 新規作成フォーム初期化
  if (props.order.not_found_flg) {
    axios
      .get(route("stock.receive.getClassifications"))
      .then((res) => {
        console.log(res.data);
        classifications.value = res.data;
      })
      .catch((error) => {
        console.log(error);
      });

    axios
      .get(route("stock.receive.getSuppliers"))
      .then((res) => {
        console.log(res.data);
        suppliers.value = res.data;
      })
      .catch((error) => {
        console.log(error);
      });
  }

  // 納入換算値の初期値を設定
  if (props.order.quantity_per_org) {
    quantity_per_org.value = props.order.quantity_per_org;
  }

  stock_create_form.order_id = props.order.id;
  stock_create_form.supplier_id = props.supplier_id;
  stock_create_form.deli_location = props.order.deli_location;
});
</script>
<template>
  <ReceiveLayout :title="'納品登録'">
    <template #content>
      <section class="text-gray-600 body-font">
        <div class="container px-5 py-24 mx-auto">
          <div class="flex flex-col text-center w-full mb-8">
            <h1
              class="sm:text-4xl text-3xl font-medium title-font mb-2 text-blue-600"
            >
              納品数量登録
            </h1>
            <p class="lg:w-2/3 mx-auto leading-relaxed text-base">
              以下の画面より納品数量登録を行います。
            </p>
          </div>

          <div class="text-gray-600 body-font relative">
            <div class="container px-5 mx-auto">
              <div class="flex flex-col text-center w-full mb-12">
                <p class="w-full mx-auto leading-relaxed text-base">
                  分納の場合は、以下のテキストボックスに数量を記入して、納品ボタンを押下してください。<br />
                  なお、分納の場合は<span class="font-bold text-red-600 mx-2"
                    >納品登録リスト</span
                  >から削除されません。
                </p>
              </div>
              <div
                class="bg-gray-50 py-8 w-full mx-auto flex justify-center items-start"
              >
                <!-- 物品確認用コンテンツ -->
                <div class="w-1/3">
                  <section class="text-gray-600 body-font">
                    <div
                      class="container mx-auto flex items-center justify-center flex-col"
                    >
                      <div class="w-full">
                        <h1
                          class="title-font text-2xl mb-2 font-medium text-gray-600"
                        >
                          {{ props.order.name }}
                        </h1>
                        <span class="block mb-4 text-xl">{{
                          props.order.s_name
                        }}</span>
                      </div>

                      <img
                        v-if="props.order.img_path"
                        class="w-5/6 object-cover object-center rounded"
                        alt="hero"
                        :src="
                          props.order.img_path &&
                          props.order.img_path.includes('https://')
                            ? props.order.img_path
                            : 'https://akioka.cloud/' + props.order.img_path
                        "
                      />
                    </div>
                  </section>
                </div>

                <!-- 納入用フォーム -->
                <!-- 格納先アドレスが一つ以上の場合表示 -->
                <div
                  v-if="form.stock_storage_id"
                  class="flex flex-wrap -m-2 justify-center mb-4"
                >
                  <div class="p-2">
                    <div class="relative mb-4">
                      <label
                        for="name"
                        class="font-bold mb-1leading-7 text-xl text-gray-600"
                        >デジタルサイネージ</label
                      >
                      <div
                        class="flex justify-between items-center space-x-4 rounded border border-gray-300 text-gray-700 py-1 px-6 leading-8"
                      >
                        <label class="flex items-center">
                          <input
                            type="radio"
                            name="skip_option"
                            :value="1"
                            class="mr-2"
                            v-model="form.signage"
                          />
                          <span>表示</span>
                        </label>

                        <label class="flex items-center">
                          <input
                            type="radio"
                            name="skip_option"
                            :value="0"
                            class="mr-2"
                            v-model="form.signage"
                          />
                          <span>非表示</span>
                        </label>
                      </div>
                    </div>

                    <div class="relative mb-2">
                      <label
                        for="name"
                        class="font-bold mb-1leading-7 text-xl text-gray-600"
                        >格納先アドレス</label
                      >
                      <select
                        v-model="form.stock_storage_id"
                        id="storage_address_id"
                        name="storage_address_id"
                        class="text-center w-full bg-gray-100 bg-opacity-50 rounded border border-gray-300 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-200 text-base outline-none text-gray-700 py-1 px-3 leading-8 transition-colors duration-200 ease-in-out"
                      >
                        <option
                          v-for="storage in props.order.stock_storages"
                          :key="storage.address_id"
                          :value="storage.stock_storage_id"
                        >
                          {{ storage.address }}
                        </option>
                      </select>
                    </div>
                    <div class="relative mt-8">
                      <label
                        for="name"
                        class="font-bold mb-1leading-7 text-xl text-gray-600"
                        >納入数換算</label
                      >
                      <div
                        class="flex items-center justify-around rounded border border-gray-300 py-1 px-3 leading-8"
                      >
                        <label>
                          なし
                          <input
                            v-model="form.conversion_flg"
                            @change="changeConversion(0)"
                            class="ml-2"
                            type="radio"
                            name="conversion"
                            value="0"
                          />
                        </label>
                        <label>
                          あり
                          <input
                            v-model="form.conversion_flg"
                            @change="changeConversion(1)"
                            class="ml-2"
                            type="radio"
                            name="conversion"
                            value="1"
                          />
                        </label>
                      </div>

                      <div v-if="form.conversion_flg" class="mt-2">
                        <p>換算数：</p>
                        <input
                          type="number"
                          name=""
                          id=""
                          v-model="quantity_per_org"
                          @change="handleChangeQuantityPerOrg"
                          class="text-center w-full bg-gray-100 bg-opacity-50 rounded border border-gray-300 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-200 text-base outline-none text-gray-700 py-1 px-3 leading-8 transition-colors duration-200 ease-in-out"
                        />
                      </div>
                    </div>

                    <div class="relative mt-8">
                      <label
                        for="name"
                        class="font-bold mb-1leading-7 text-xl text-gray-600"
                        >納入数</label
                      >
                      <input
                        @change="handleChangeQuantity"
                        v-model="form.quantity"
                        type="number"
                        id="quantity"
                        name="quantity"
                        class="text-center w-full bg-gray-100 bg-opacity-50 rounded border border-gray-300 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-200 text-base outline-none text-gray-700 py-1 px-3 leading-8 transition-colors duration-200 ease-in-out"
                      />
                    </div>

                    <p class="mt-4 w-full flex justify-between text-lg">
                      <span
                        >現在:
                        {{ storage_quantity + props.order.order_unit }}</span
                      >
                      <span>
                        <i class="fas fa-arrow-right w-6 h-6 inline-block"></i>
                      </span>
                      <span class="font-bold text-red-500"
                        >納入後:
                        {{
                          form.calc_quantity +
                          storage_quantity +
                          props.order.order_unit
                        }}</span
                      >
                    </p>
                  </div>
                  <div class="p-2 w-full mt-4">
                    <button
                      @click="updateDelivery"
                      class="flex mx-auto text-white bg-red-500 border-0 py-2 px-8 focus:outline-none hover:bg-red-600 rounded text-lg"
                    >
                      納入
                    </button>
                  </div>
                </div>

                <!-- 在庫データ作成フォーム -->
                <!-- <div v-else>
                  <div class="flex flex-wrap -m-2 justify-center mb-4">
                    <div class="p-2">
                      <div class="mb-8">
                        <h2 class="mb-2 text-indigo-600 text-sm">
                          <span class="font-bold text-lg"
                            >在庫情報を登録してください。</span
                          ><br />
                          単発は注品のように倉庫で管理していない場合は、下のボタンをクリックすることで<br />在庫登録をスキップしてサイネージに表示することが可能です。
                        </h2>
                        <Link
                          :href="
                            route('stock.receive.none_storage', {
                              order_id: props.order.id,
                              none_storage_flg: 1
                            })
                          "
                          class="mt-2 bg-indigo-500 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded"
                        >
                          在庫登録をスキップしてサイネージ表示
                        </Link>
                        <Link
                          :href="
                            route('stock.receive.none_storage', {
                              order_id: props.order.id,
                              none_storage_flg: 2
                            })
                          "
                          class="ml-2 mt-2 bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                        >
                          在庫登録とサイネージ表示をスキップ
                        </Link>
                      </div>

                      <div class="relative mb-2">
                        <label
                          for="name"
                          class="font-bold mb-1leading-7 text-xl text-gray-600"
                          >品名</label
                        >
                        <p
                          class="text-center w-full bg-gray-300 bg-opacity-50 rounded border border-gray-300 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-200 text-base outline-none text-gray-700 py-1 px-3 leading-8 transition-colors duration-200 ease-in-out"
                        >
                          {{ props.order.name }}
                        </p>
                      </div>
                      <div class="relative mb-2">
                        <label
                          for="name"
                          class="font-bold mb-1leading-7 text-xl text-gray-600"
                          >品番</label
                        >
                        <p
                          class="text-center w-full bg-gray-300 bg-opacity-50 rounded border border-gray-300 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-200 text-base outline-none text-gray-700 py-1 px-3 leading-8 transition-colors duration-200 ease-in-out"
                        >
                          {{ props.order.s_name }}
                        </p>
                      </div>

                      <hr class="my-8" />

                      <div class="relative mt-8">
                        <label
                          for="name"
                          class="font-bold mb-1leading-7 text-xl text-gray-600"
                          >仕入れ先</label
                        >
                        <select
                          v-model="stock_create_form.supplier_id"
                          name="supplier_id"
                          id="supplier_id"
                          class="text-center w-full bg-white bg-opacity-50 rounded border border-gray-300 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-200 text-base outline-none text-gray-700 py-1 px-3 leading-8 transition-colors duration-200 ease-in-out"
                        >
                          <option
                            v-for="supplier in suppliers"
                            :key="supplier.id"
                            :value="supplier.id"
                          >
                            {{ supplier.name }}
                          </option>
                        </select>
                      </div>

                      <div class="relative mt-8">
                        <label
                          for="deli_location"
                          class="font-bold mb-1leading-7 text-xl text-gray-600"
                          >配達先</label
                        >
                        <input
                          v-model="stock_create_form.deli_location"
                          type="text"
                          id="deli_location"
                          name="deli_location"
                          class="text-center w-full bg-white bg-opacity-50 rounded border border-gray-300 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-200 text-base outline-none text-gray-700 py-1 px-3 leading-8 transition-colors duration-200 ease-in-out"
                        />
                      </div>

                      <div class="relative mt-8">
                        <label
                          for="classification_id"
                          class="font-bold mb-1leading-7 text-xl text-gray-600"
                          >カテゴリー</label
                        >
                        <select
                          v-model="stock_create_form.classification_id"
                          name="classification_id"
                          id="classification_id"
                          class="text-center w-full bg-white bg-opacity-50 rounded border border-gray-300 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-200 text-base outline-none text-gray-700 py-1 px-3 leading-8 transition-colors duration-200 ease-in-out"
                          :class="{
                            'border-red-400':
                              !stock_create_form.classification_id,
                          }"
                        >
                          <option value="">選択してください</option>
                          <option
                            v-for="classification in classifications"
                            :key="classification.id"
                            :value="classification.id"
                          >
                            {{ classification.name }}
                          </option>
                        </select>
                      </div>

                      <hr class="my-8" />
                      <p>格納先とアドレスを設定してください。</p>
                      <div class="relative mt-4">
                        <label
                          for="name"
                          class="font-bold mb-1leading-7 text-xl text-gray-600"
                          >格納先</label
                        >
                        <select
                          @change="sortStorageAddresses($event.target.value)"
                          name="supplier_id"
                          id="supplier_id"
                          class="text-center w-full bg-white bg-opacity-50 rounded border border-gray-300 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-200 text-base outline-none text-gray-700 py-1 px-3 leading-8 transition-colors duration-200 ease-in-out"
                          :class="{
                            'border-red-400':
                              !stock_create_form.storage_address_id,
                          }"
                        >
                          <option value="">選択してください</option>
                          <option
                            v-for="location in locations"
                            :key="location.id"
                            :value="location.id"
                          >
                            {{ location.name }}
                          </option>
                        </select>
                      </div>
                      <div class="relative mt-8">
                        <label
                          for="name"
                          class="font-bold mb-1leading-7 text-xl text-gray-600"
                          >アドレス</label
                        >
                        <select
                          v-model="stock_create_form.storage_address_id"
                          name="storage_address_id"
                          id="storage_address_id"
                          class="text-center w-full bg-white bg-opacity-50 rounded border border-gray-300 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-200 text-base outline-none text-gray-700 py-1 px-3 leading-8 transition-colors duration-200 ease-in-out"
                          :class="{
                            'border-red-400':
                              !stock_create_form.storage_address_id,
                          }"
                        >
                          <option
                            v-for="storage_address in storage_addresses"
                            :key="storage_address.id"
                            :value="storage_address.id"
                          >
                            {{ storage_address.address }}
                          </option>
                        </select>
                      </div>
                    </div>
                    <div class="p-2 w-full mt-8">
                      <button
                        @click="createStock"
                        class="flex mx-auto text-white bg-indigo-500 border-0 py-2 px-8 focus:outline-none hover:bg-red-600 rounded text-lg"
                      >
                        新規作成
                      </button>
                    </div>
                  </div>
                </div> -->

                <!-- 格納先が設定されていない場合 -->
                <div
                  class="w-1/3"
                  v-if="
                    props.order.stock_storages &&
                    props.order.stock_storages.length == 0
                  "
                >
                  <div class="mb-8">
                    <h2 class="mb-2 text-indigo-600 text-sm">
                      <span class="font-bold text-lg"
                        >格納先・アドレスを登録してください。</span
                      ><br />
                      単発は注品のように倉庫で管理していない場合は、下のボタンをクリックすることで<br />在庫登録をスキップしてサイネージに表示することが可能です。
                    </h2>
                    <Link
                      :href="
                        route('stock.receive.none_storage', {
                          order_id: props.order.id,
                          signage: 1,
                        })
                      "
                      class="mt-2 bg-indigo-500 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded"
                    >
                      スキップしてサイネージ表示
                    </Link>
                    <Link
                      :href="
                        route('stock.receive.none_storage', {
                          order_id: props.order.id,
                          signage: 0,
                        })
                      "
                      class="ml-2 mt-2 bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                    >
                      格納先登録とサイネージ表示をスキップ
                    </Link>
                  </div>

                  <div class="relative mt-4">
                    <label
                      for="name"
                      class="font-bold mb-1leading-7 text-xl text-gray-600"
                      >格納先</label
                    >
                    <select
                      @change="sortStorageAddresses($event.target.value)"
                      name="supplier_id"
                      id="supplier_id"
                      class="text-center w-full bg-white bg-opacity-50 rounded border border-gray-300 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-200 text-base outline-none text-gray-700 py-1 px-3 leading-8 transition-colors duration-200 ease-in-out"
                      :class="{
                        'border-red-400': !stock_create_form.storage_address_id,
                      }"
                    >
                      <option value="">選択してください</option>
                      <option
                        v-for="location in props.locations"
                        :key="location.id"
                        :value="location.id"
                      >
                        {{ location.name }}
                      </option>
                    </select>
                  </div>
                  <div class="relative mt-8">
                    <label
                      for="name"
                      class="font-bold mb-1leading-7 text-xl text-gray-600"
                      >アドレス</label
                    >
                    <select
                      v-model="form.storage_address_id"
                      name="storage_address_id"
                      id="storage_address_id"
                      class="text-center w-full bg-white bg-opacity-50 rounded border border-gray-300 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-200 text-base outline-none text-gray-700 py-1 px-3 leading-8 transition-colors duration-200 ease-in-out"
                      :class="{
                        'border-red-400': !form.storage_address_id,
                      }"
                    >
                      <option
                        v-for="storage_address in storage_addresses"
                        :key="storage_address.id"
                        :value="storage_address.id"
                      >
                        {{ storage_address.address }}
                      </option>
                    </select>
                  </div>

                  <div class="p-2 w-full mt-4">
                    <button
                      @click="updateDelivery"
                      class="flex mx-auto text-white bg-red-500 border-0 py-2 px-8 focus:outline-none hover:bg-red-600 rounded text-lg"
                    >
                      納入
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </template>
  </ReceiveLayout>
</template>
<style scoped>
</style>