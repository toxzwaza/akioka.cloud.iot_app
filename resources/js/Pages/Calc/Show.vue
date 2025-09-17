<script setup>
import MainLayout from "@/Layouts/Calc/MainLayout.vue";
import SearchInput from "@/Components/Calc/SearchInput.vue";

import { ref, reactive, onMounted, nextTick } from "vue";
import { router, Link } from "@inertiajs/vue3";
import axios from "axios";

const props = defineProps({
  login_user: Object,
  search_clients: Array,
  search_processes: Array,
  search_box_numbers: Array,
  product: Object,
  processes: Array,
  users: Array,
  locations: Array,
  different_box_numbers: Array,
  search_keyword: Array,
});

const form = reactive({
  statement_id: null,
  calc_product_data_id: null,
  user_id: null,
  calc_product_location_id: null,
  box_number: null, //箱No現品No 棚卸札番
  calc_product_process_id: null, //工程区分
  calc_product_status: null, //製品状態
  inventory_quantity: null,
  memo: null,
});

// 棚卸明細作成・編集
const createCalcProductStatement = () => {
  router.post(route("calc.store"), form);
  clearForm();
  location.reload();
};
// 棚卸明細削除
const deleteCalcProductStatement = () => {
  if (confirm("本当に削除しますか？")) {
    router.get(route("calc.destroy"), { statement_id: form.statement_id });
  }
};

// 編集
const editCalcProductStatement = (state_id) => {
  const statement = calcProductStatements.value.find(
    (statement) => statement.id === state_id
  );

  if (statement) {
    form.statement_id = statement.id;
    form.calc_product_data_id = statement.calc_product_data_id;
    form.calc_product_data_id = statement.calc_product_data_id;
    form.user_id = statement.user_id;
    form.calc_product_location_id = statement.calc_product_location_id;
    form.box_number = statement.box_number;
    form.calc_product_status = statement.calc_product_status;
    form.calc_product_process_id = statement.calc_product_process_id;
    form.inventory_quantity = statement.inventory_quantity;
    form.memo = statement.memo;
  }

  const main_calc_container = document.querySelector("#main_calc_container");
  main_calc_container.scrollIntoView({ behavior: "smooth" });

  console.log(form);
};
// フォームクリア
const clearForm = () => {
  location.reload();
};
// 登録済み棚卸データ
const calcProductStatements = ref([]);
const isNavigating = ref(false);

//   登録済み棚卸状況取得
const getCalcProductStatements = () => {
  axios
    .get(route("api.getCalcProductStatements"), {
      params: {
        calc_product_data_id: props.product.id,
      },
    })
    .then((res) => {
      if (res.data.length > 0) {
        const boxNumber = parseInt(res.data[0].box_number);
        if (!isNaN(boxNumber)) {
          form.box_number = boxNumber + 1;
        } else {
          console.log("文字列が含まれています");
        }
        calcProductStatements.value = res.data;
      } else {
        console.log("既存データなし");
      }
    })
    .catch((error) => {
      console.error("Error fetching product statements:", error);
    });
};

// 棚卸総数を取得
const getCalcProductQuantity = () => {
  let calc_quantity = 0;

  const statements = calcProductStatements.value;
  statements.forEach((statement) => {
    calc_quantity += statement.inventory_quantity;
  });

  return calc_quantity;
};

// メモが登録されている詳細のみ取得
const filteredCalcProductStatements = () => {
  console.log(
    calcProductStatements.value.filter((statement) => statement.memo !== null)
  );
  return calcProductStatements.value.filter(
    (statement) => statement.memo !== null
  );
};

// 完了登録
const checkComplete = () => {
  if (confirm("完了登録を行いますか？")) {
    router.get(
      route("calc.complete", { calc_product_data_id: props.product.id })
    );
  }
};

// 完了解除
const cancelComplete = () => {
  if (confirm("完了を解除しますか？")) {
    // 解除処理
    router.get(
      route("calc.cancel_complete", { calc_product_data_id: props.product.id })
    );
  }
};

// 入力値チェック
const validateCheck = (kind, val) => {
  let checkStatus = false;

  switch (kind) {
    case "box_number":
      const toHalfWidth = (str) => {
        return str.replace(/[！-～]/g, (char) =>
          String.fromCharCode(char.charCodeAt(0) - 0xfee0)
        );
      };

      val = toHalfWidth(val);
      form.box_number = val;
      break;
    default:
      console.log("チェック対象ではありません");
      break;
  }
};

// 関連候補への遷移
const navigateToRelated = (productId) => {
  // 既にナビゲーション中の場合は処理しない
  if (isNavigating.value) {
    return;
  }
  
  isNavigating.value = true;
  console.log('関連候補への遷移開始:', productId);
  
  // Vue.jsの次のティックでローディング表示を確実に反映
  nextTick(() => {
    router.visit(route('calc.show', { id: productId, search_keyword: props.search_keyword }), {
      onStart: () => {
        console.log('Inertia navigation started');
      },
      onFinish: () => {
        console.log('Inertia navigation finished');
        isNavigating.value = false;
      },
      onError: () => {
        console.log('Inertia navigation error');
        isNavigating.value = false;
      }
    });
  });
};

onMounted(() => {
  console.log("product", props.product);
  // 初期値登録
  form.calc_product_data_id = props.product.id;
  form.box_number =
    !props.product.box_number || props.product.box_number === " "
      ? 1
      : props.product.box_number;
  form.user_id = props.login_user.user_id;
  form.calc_product_location_id = props.login_user.location_id;

  getCalcProductStatements();
});
</script>
<template>
  <MainLayout :url="'stock.home'" :title="'棚卸し登録'" :login_user="login_user">
    <template #content>
      <!-- 検索ページに戻るボタン -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
        <div class="bg-gradient-to-r from-gray-50 to-slate-50 px-6 py-4 border-b border-gray-200">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="bg-gray-100 rounded-full p-2">
                <i class="fas fa-arrow-left text-gray-600"></i>
              </div>
              <div>
                <h2 class="text-xl font-bold text-gray-800">検索結果に戻る</h2>
                <p class="text-sm text-gray-600">前回の検索結果を確認できます</p>
              </div>
            </div>
            <Link
              :href="route('calc.search', { search_keyword: search_keyword })"
              class="px-6 py-3 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-semibold rounded-lg transition-all duration-200 shadow-sm hover:shadow-md flex items-center gap-2"
            >
              <i class="fas fa-search"></i>
              検索ページに戻る
            </Link>
          </div>
        </div>
      </div>
      
      <!-- 完了済み表示 -->
      <div
        v-if="props.product.comp_flg == '1'"
        class="my-8 bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-xl p-8 text-center shadow-sm"
      >
        <div class="flex justify-center items-center flex-col text-green-700">
          <div class="bg-green-100 rounded-full p-4 mb-4">
            <i class="text-4xl fa-solid fa-check-circle text-green-600"></i>
          </div>
          <h3 class="text-2xl font-bold mb-3">完了済</h3>
          <p class="text-sm text-gray-600 max-w-md">
            再度登録を行いたい場合は、下記の「想定在庫数」をクリックして完了状態を解除してください。
          </p>
        </div>
      </div>

      <!-- 製品情報カード -->
      <section id="base_figure" class="mt-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="p-6">
            <div class="flex flex-wrap lg:flex-nowrap gap-6">
              <div class="lg:w-1/2 w-full">
                <img
                  v-if="props.product.img_path"
                  alt="図面検索システムの方で画像を登録している場合、製品画像が表示されます。"
                  class="w-full h-80 object-cover object-center rounded-lg bg-gray-50 border border-gray-200"
                  :src="props.product.img_path"
                />
                <div v-else class="w-full h-80 bg-gray-50 border border-gray-200 rounded-lg flex items-center justify-center">
                  <div class="text-center p-6">
                    <i class="fas fa-image text-4xl text-gray-300 mb-3"></i>
                    <p class="text-sm text-gray-500">
                      図面検索システムより製品画像を登録されている場合、製品画像が表示されます。
                    </p>
                  </div>
                </div>
              </div>
              <div class="lg:w-1/2 w-full space-y-4">
                <div class="border-b border-gray-100 pb-4">
                  <span class="text-xs font-medium text-gray-500 bg-gray-100 px-2 py-1 rounded">
                    ID: {{ props.product.id }}
                  </span>
                  <h1 class="text-2xl font-bold text-gray-800 mt-2">
                    {{ props.product.product_name }}
                  </h1>
                </div>
                <div class="space-y-3">
                  <div class="flex items-center gap-2">
                    <span class="text-sm font-medium text-gray-600">顧客名:</span>
                    <span class="text-sm text-gray-800">{{ props.product.client_name }}</span>
                  </div>
                  <div class="flex items-center gap-2">
                    <span class="text-sm font-medium text-gray-600">図番:</span>
                    <span class="text-sm text-gray-800">{{ props.product.client_drawing_number }}</span>
                  </div>
                  <div class="flex items-center gap-2">
                    <span class="text-sm font-medium text-gray-600">工程:</span>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-lg font-bold bg-red-100 text-red-700">
                      {{ props.product.process_name }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
      <!-- メモ一覧セクション -->
      <section v-if="filteredCalcProductStatements().length > 0" class="mt-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-gray-200">
            <div class="flex items-center gap-3">
              <div class="bg-blue-100 rounded-full p-2">
                <i class="fas fa-sticky-note text-blue-600"></i>
              </div>
              <div>
                <h2 class="text-xl font-bold text-gray-800">メモ一覧</h2>
                <p class="text-sm text-gray-600">棚卸詳細に登録したメモ一覧を確認できます。</p>
              </div>
            </div>
          </div>
          <div class="p-6">
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead>
                  <tr class="border-b border-gray-200">
                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">投稿者</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">メモ</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="statement in filteredCalcProductStatements()"
                    :key="statement.id"
                    class="border-b border-gray-100 hover:bg-gray-50 transition-colors"
                  >
                    <td class="px-4 py-4 whitespace-nowrap">
                      <div class="flex flex-col">
                        <span class="text-sm font-medium text-gray-800">{{ statement.user_name }}</span>
                        <span class="text-xs text-gray-500">{{
                          new Date(statement.created_at).toLocaleDateString("ja-JP", {
                            year: "2-digit",
                            month: "2-digit",
                            day: "2-digit",
                          })
                        }}</span>
                      </div>
                    </td>
                    <td class="px-4 py-4">
                      <div class="bg-gray-50 rounded-lg p-3 border-l-4 border-blue-400">
                        <p class="text-sm text-gray-800">{{ statement.memo }}</p>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </section>

      <!-- 在庫数比較カード（Sticky） -->
      <div class="sticky top-0 bg-white/95 backdrop-blur-sm z-10 py-4">
        <div class="flex gap-4">
          <!-- 想定在庫数カード -->
          <div class="flex-1 bg-white rounded-xl border border-gray-200 shadow-sm">
            <div class="p-6 text-center">
              <div class="flex items-center justify-center gap-2 mb-2">
                <i class="fas fa-clipboard-list text-gray-500"></i>
                <h3 class="text-lg font-semibold text-gray-700">想定在庫数</h3>
              </div>
              <p class="text-3xl font-bold text-gray-800 mb-4">{{ props.product.stock_quantity ?? "-" }}</p>
              <button
                @click="props.product.comp_flg == '1' ? cancelComplete() : null"
                :disabled="props.product.comp_flg != '1'"
                class="w-full px-4 py-2 font-semibold rounded-lg transition-all duration-200 flex items-center justify-center gap-2"
                :class="{
                  'bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white shadow-sm hover:shadow-md': props.product.comp_flg == '1',
                  'bg-gray-100 text-gray-400 cursor-not-allowed': props.product.comp_flg != '1'
                }"
              >
                <i class="fas fa-undo"></i>
                完了を解除
              </button>
            </div>
          </div>

          <!-- 棚卸実数カード -->
          <div
            class="flex-1 rounded-xl shadow-sm border"
            :class="{
              'bg-gradient-to-r from-green-50 to-emerald-50 border-green-200':
                props.product.stock_quantity == getCalcProductQuantity(),
              'bg-gradient-to-r from-red-50 to-rose-50 border-red-200':
                props.product.stock_quantity < getCalcProductQuantity(),
              'bg-white border-gray-200':
                props.product.stock_quantity > getCalcProductQuantity() || !getCalcProductQuantity(),
            }"
          >
            <div class="p-6 text-center">
              <div class="flex items-center justify-center gap-2 mb-2">
                <i class="fas fa-calculator"
                   :class="{
                     'text-green-600': props.product.stock_quantity == getCalcProductQuantity(),
                     'text-red-600': props.product.stock_quantity < getCalcProductQuantity(),
                     'text-gray-500': props.product.stock_quantity > getCalcProductQuantity() || !getCalcProductQuantity(),
                   }"></i>
                <h3 class="text-lg font-semibold"
                    :class="{
                      'text-green-700': props.product.stock_quantity == getCalcProductQuantity(),
                      'text-red-700': props.product.stock_quantity < getCalcProductQuantity(),
                      'text-gray-700': props.product.stock_quantity > getCalcProductQuantity() || !getCalcProductQuantity(),
                    }">棚卸実数</h3>
              </div>
              <p class="text-3xl font-bold mb-4"
                 :class="{
                   'text-green-800': props.product.stock_quantity == getCalcProductQuantity(),
                   'text-red-800': props.product.stock_quantity < getCalcProductQuantity(),
                   'text-gray-800': props.product.stock_quantity > getCalcProductQuantity() || !getCalcProductQuantity(),
                 }">{{ getCalcProductQuantity() }}</p>
              <button
                v-if="props.product.comp_flg != '1' && getCalcProductQuantity() > 0"
                @click="checkComplete"
                class="w-full px-4 py-2 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-semibold rounded-lg transition-all duration-200 shadow-sm hover:shadow-md flex items-center justify-center gap-2"
              >
                <i class="fas fa-check"></i>
                完了登録
              </button>
              <div v-else-if="props.product.comp_flg == '1'" class="w-full px-4 py-2 bg-green-100 text-green-700 rounded-lg border border-green-200">
                <div class="flex items-center justify-center gap-2">
                  <i class="fas fa-check-circle"></i>
                  <span class="font-semibold">完了済み</span>
                </div>
              </div>
              <div v-else class="w-full px-4 py-2 bg-gray-100 text-gray-500 rounded-lg">
                <span class="text-sm">棚卸データを登録してください</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- 棚卸登録フォーム -->
      <section
        v-if="!props.product.comp_flg"
        id="main_calc_container"
        class="mt-8"
      >
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <!-- フォームヘッダー -->
          <div class="bg-gradient-to-r from-slate-50 to-gray-50 px-6 py-4 border-b border-gray-200">
            <div v-if="form.statement_id" class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="bg-green-100 rounded-full p-2">
                  <i class="fas fa-edit text-green-600"></i>
                </div>
                <div>
                  <h2 class="text-xl font-bold text-green-700">編集モード</h2>
                  <p class="text-sm text-gray-600">棚卸データを編集しています</p>
                </div>
              </div>
              <button
                @click="clearForm"
                class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium text-sm"
              >
                新規登録に戻る
              </button>
            </div>
            <div v-else class="flex items-center gap-3">
              <div class="bg-blue-100 rounded-full p-2">
                <i class="fas fa-plus text-blue-600"></i>
              </div>
              <div>
                <h2 class="text-xl font-bold text-gray-800">新規登録</h2>
                <p class="text-sm text-gray-600">新しい棚卸データを登録します</p>
              </div>
            </div>
          </div>

          <!-- フォーム本体 -->
          <div class="p-6">
            <form class="space-y-6">

              <!-- 登録者選択 -->
              <div class="space-y-2">
                <label class="block text-sm font-semibold text-gray-700">
                  <span class="text-red-500">*</span> 登録者
                </label>
                <select
                  class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                  v-model="form.user_id"
                >
                  <option value="" disabled>登録者を選択してください</option>
                  <option v-for="user in users" :key="user.id" :value="user.id">
                    {{ user.name }}
                  </option>
                </select>
              </div>

              <!-- 保管場所選択 -->
              <div v-if="form.user_id" class="space-y-2">
                <label class="block text-sm font-semibold text-gray-700">
                  <span class="text-red-500">*</span> 保管場所
                </label>
                <select
                  class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                  v-model="form.calc_product_location_id"
                >
                  <option value="" disabled>保管場所を選択してください</option>
                  <option
                    v-for="location in locations"
                    :key="location.id"
                    :value="location.id"
                  >
                    {{ location.name }}
                  </option>
                </select>
              </div>

              <!-- 区切り線 -->
              <div v-if="form.calc_product_location_id" class="border-t border-gray-200 pt-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                  <i class="fas fa-box text-blue-600"></i>
                  製品詳細情報
                </h3>
              </div>

              <!-- 現品表番号/箱No/棚卸札No -->
              <div v-if="form.calc_product_location_id" class="space-y-2">
                <label class="block text-sm font-semibold text-gray-700">
                  <span class="text-red-500">*</span> 現品表番号/箱No/棚卸札No
                </label>
                <div class="space-y-2">
                  <p v-if="props.product.id" class="text-sm text-red-600 bg-red-50 p-2 rounded">
                    <i class="fas fa-info-circle mr-1"></i>
                    ハイフン(-) 以降の連番を入力してください。
                  </p>
                  <input
                    @change="validateCheck('box_number', $event.target.value)"
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                    type="text"
                    :placeholder="props.product.id + ' - '"
                    v-model="form.box_number"
                  />
                </div>
              </div>

              <!-- 工程区分 -->
              <div v-if="form.box_number" class="space-y-2">
                <label class="block text-sm font-semibold text-gray-700">
                  <span class="text-red-500">*</span> 工程区分 <span class="text-red-500 text-xs">(必須)</span>
                </label>
                <select
                  class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                  v-model="form.calc_product_process_id"
                >
                  <option value="" disabled>工程区分を選択してください</option>
                  <option
                    v-for="process in processes"
                    :key="process.id"
                    :value="process.id"
                  >
                    {{ process.name }}
                  </option>
                </select>
              </div>

              <!-- 製品状況 -->
              <div v-if="form.calc_product_process_id" class="space-y-2">
                <label class="block text-sm font-semibold text-gray-700">
                  <span class="text-red-500">*</span> 製品状況
                </label>
                <select
                  class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                  v-model="form.calc_product_status"
                >
                  <option value="" disabled>製品状況を選択してください</option>
                  <option value="1">ショット前</option>
                  <option value="2">検査前</option>
                  <option value="3">検査後</option>
                  <option value="4">加工品</option>
                </select>
              </div>

              <!-- 棚卸数とメモ -->
              <div v-if="form.calc_product_status" class="space-y-4">
                <div class="space-y-2">
                  <label class="block text-sm font-semibold text-gray-700">
                    <span class="text-red-500">*</span> 棚卸数
                  </label>
                  <input
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                    type="number"
                    placeholder="実際の数量を入力してください"
                    v-model="form.inventory_quantity"
                    min="0"
                  />
                </div>

                <div class="space-y-2">
                  <label class="block text-sm font-semibold text-gray-700" for="memo">
                    メモ <span class="text-xs text-gray-500">(任意)</span>
                  </label>
                  <textarea
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all resize-none"
                    id="memo"
                    rows="4"
                    placeholder="特記事項があれば記入してください"
                    v-model="form.memo"
                  ></textarea>
                </div>
              </div>

              <!-- アクションボタン -->
              <div
                v-if="
                  form.user_id &&
                  form.calc_product_location_id &&
                  form.box_number &&
                  form.calc_product_process_id &&
                  form.inventory_quantity
                "
                class="pt-6 border-t border-gray-200"
              >
                <div class="flex gap-4">
                  <button
                    @click.prevent="deleteCalcProductStatement"
                    v-if="form.statement_id"
                    class="flex-1 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white font-semibold py-3 px-6 rounded-lg transition-all duration-200 shadow-sm hover:shadow-md flex items-center justify-center gap-2"
                  >
                    <i class="fas fa-trash-alt"></i>
                    削除
                  </button>
                  <button
                    @click.prevent="createCalcProductStatement"
                    class="flex-1 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition-all duration-200 shadow-sm hover:shadow-md flex items-center justify-center gap-2"
                  >
                    <i class="fas fa-save"></i>
                    {{ form.statement_id ? '更新' : '登録' }}
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>

        <!-- 登録済み棚卸状況 -->
        <div v-if="calcProductStatements.length" class="mt-8">
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-emerald-50 to-green-50 px-6 py-4 border-b border-gray-200">
              <div class="flex items-center gap-3">
                <div class="bg-green-100 rounded-full p-2">
                  <i class="fas fa-list-check text-green-600"></i>
                </div>
                <div>
                  <h2 class="text-xl font-bold text-gray-800">登録済棚卸状況</h2>
                  <p class="text-sm text-gray-600">{{ calcProductStatements.length }}件の棚卸データが登録されています</p>
                </div>
              </div>
            </div>
            <div class="p-6">
              <div class="overflow-x-auto">
                <table class="w-full">
                  <thead>
                    <tr class="border-b border-gray-200">
                      <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">登録者</th>
                      <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">保管場所</th>
                      <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">箱No/現品票番号</th>
                      <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">工程</th>
                      <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">棚卸数</th>
                      <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">登録日</th>
                      <th class="px-4 py-3 text-center text-sm font-semibold text-gray-700">操作</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="calcProductStatement in calcProductStatements"
                      :key="calcProductStatement.id"
                      class="border-b border-gray-100 hover:bg-gray-50 transition-colors"
                    >
                      <td class="px-4 py-4">
                        <div class="flex items-center gap-2">
                          <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-user text-gray-500 text-xs"></i>
                          </div>
                          <span class="text-sm font-medium text-gray-800">{{ calcProductStatement.user_name }}</span>
                        </div>
                      </td>
                      <td class="px-4 py-4">
                        <span class="text-sm text-gray-700">{{ calcProductStatement.location_name }}</span>
                      </td>
                      <td class="px-4 py-4">
                        <span class="text-sm font-mono bg-gray-100 px-2 py-1 rounded">{{ calcProductStatement.box_number }}</span>
                      </td>
                      <td class="px-4 py-4">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                          {{ calcProductStatement.process_name }}
                        </span>
                      </td>
                      <td class="px-4 py-4">
                        <span class="text-sm font-semibold text-gray-800">{{ calcProductStatement.inventory_quantity }}</span>
                      </td>
                      <td class="px-4 py-4">
                        <span class="text-xs text-gray-500">
                          {{
                            new Date(calcProductStatement.created_at).toLocaleDateString("ja-JP", {
                              year: "2-digit",
                              month: "2-digit",
                              day: "2-digit",
                            })
                          }}
                        </span>
                      </td>
                      <td class="px-4 py-4 text-center">
                        <button
                          @click="editCalcProductStatement(calcProductStatement.id)"
                          class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-600 hover:text-blue-700 rounded-lg transition-colors text-sm font-medium"
                        >
                          <i class="fas fa-edit text-xs"></i>
                          編集
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ナビゲーション中のローディング表示 -->
      <section v-if="isNavigating" class="mt-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="p-12 text-center">
            <div class="flex justify-center items-center flex-col">
              <div class="bg-amber-100 rounded-full p-4 mb-4">
                <i class="fas fa-spinner fa-spin text-3xl text-amber-600"></i>
              </div>
              <h3 class="text-xl font-bold text-gray-800 mb-2">関連製品に移動中...</h3>
              <p class="text-sm text-gray-600">関連する製品の詳細ページに移動しています。しばらくお待ちください。</p>
            </div>
          </div>
        </div>
      </section>

      <!-- 関連候補 -->
      <div v-else class="mt-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="bg-gradient-to-r from-amber-50 to-orange-50 px-6 py-4 border-b border-gray-200">
            <div class="flex items-center gap-3">
              <div class="bg-amber-100 rounded-full p-2">
                <i class="fas fa-link text-amber-600"></i>
              </div>
              <div>
                <h2 class="text-xl font-bold text-gray-800">関連候補</h2>
                <p class="text-sm text-gray-600">同一図番箱Noもしくは、現品票Noが異なるものを表示しています。</p>
              </div>
            </div>
          </div>
          <div class="p-6">
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead>
                  <tr class="border-b border-gray-200">
                    <th class="px-4 py-3 text-center text-sm font-semibold text-gray-700">実施状況</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">箱No/現品票No</th>
                    <th class="px-4 py-3 text-center text-sm font-semibold text-gray-700">
                      <i class="fas fa-mouse-pointer text-gray-400"></i>
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="different_box_number in different_box_numbers"
                    :key="different_box_number.id"
                    @click="navigateToRelated(different_box_number.id)"
                    class="border-b border-gray-100 hover:bg-amber-50 hover:border-amber-200 transition-all duration-100 cursor-pointer group"
                    :class="{ 'opacity-50 pointer-events-none': isNavigating }"
                  >
                    <td class="px-4 py-4 text-center">
                      <div class="flex justify-center">
                        <span
                          v-if="different_box_number.comp_flg == 1"
                          class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 group-hover:bg-green-200 transition-colors"
                        >
                          <i class="fas fa-check-circle mr-1"></i>
                          完了
                        </span>
                        <span
                          v-else
                          class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 group-hover:bg-red-200 transition-colors"
                        >
                          <i class="fas fa-times-circle mr-1"></i>
                          未完了
                        </span>
                      </div>
                    </td>
                    <td class="px-4 py-4">
                      <span class="text-sm font-mono bg-gray-100 group-hover:bg-amber-100 px-2 py-1 rounded transition-colors">{{ different_box_number.box_number }}</span>
                    </td>
                    <td class="px-4 py-4 text-center">
                      <div class="inline-flex items-center gap-1 text-gray-400 group-hover:text-amber-600 transition-colors">
                        <i class="fas fa-arrow-right text-sm"></i>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </template>
  </MainLayout>
</template>



<style>
</style>