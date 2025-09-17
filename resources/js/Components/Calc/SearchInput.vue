<script setup>
import { ref, reactive, onMounted, nextTick } from "vue";
import { router, Link } from "@inertiajs/vue3";
import axios from "axios";
import OcrPopup from "./OcrPopup.vue";

const props = defineProps({
  status: String,
  search_clients: Array,
  search_processes: Array,
  search_box_numbers: Array,
  search_keyword: Array,
});

// リアクティブな取引先セレクトボックス
const selectSuppliers = ref(props.search_clients);
const filterSuppliers = (filterStr) => {
  console.log("実行", filterStr);

  const filterMap = {
    あ: ["あ", "い", "う", "え", "お"],
    か: ["か", "き", "く", "け", "こ"],
    さ: ["さ", "し", "す", "せ", "そ"],
    た: ["た", "ち", "つ", "て", "と"],
    な: ["な", "に", "ぬ", "ね", "の"],
    は: ["は", "ひ", "ふ", "へ", "ほ"],
    ま: ["ま", "み", "む", "め", "も"],
    や: ["や", "ゆ", "よ"],
    ら: ["ら", "り", "る", "れ", "ろ"],
    わ: ["わ", "を", "ん"],
  };

  const filterChars = filterMap[filterStr] || [filterStr];

  selectSuppliers.value = props.search_clients.filter((supplier) =>
    filterChars.some((char) => supplier.furi_name.startsWith(char))
  );
};

const formStatus = ref(true);
const changeFormStatus = (status) => {
  if (status === "open") {
    formStatus.value = true;
  } else if (status === "close") {
    formStatus.value = false;
  } else {
    formStatus.value = !formStatus.value;
  }
};

const addFormSearchKeyword = (search_keyword) => {
  try {
    console.log('検索キーワードを設定中:', search_keyword);
    
    if (search_keyword.box_number) {
      form.box_number = search_keyword.box_number;
      console.log('箱番号を設定:', search_keyword.box_number);
    }
    if (search_keyword.process_code) {
      form.process_code = search_keyword.process_code;
      console.log('工程コードを設定:', search_keyword.process_code);
    }
    if (search_keyword.client_name) {
      form.client_name = search_keyword.client_name;
      console.log('得意先名を設定:', search_keyword.client_name);
    }
    if (search_keyword.client_code) {
      form.client_name = search_keyword.client_name;
      console.log('得意先コードから得意先名を設定:', search_keyword.client_name);
    }
    if (search_keyword.client_drawing_number) {
      form.client_drawing_number = search_keyword.client_drawing_number;
      console.log('図番を設定:', search_keyword.client_drawing_number);
    }
    if (search_keyword.product_name) {
      form.product_name = search_keyword.product_name;
      console.log('製品名を設定:', search_keyword.product_name);
    }
    
    console.log('フォーム設定後の状態:', form);
  } catch (e) {
    console.log("error", e);
  }
};
// フォーム
const form = reactive({
  box_number: null,
  process_code: null,
  client_name: null,
  client_drawing_number: null,
  product_name: null,
});

// 検索
// 箱No、現品表から
const getByBoxNumber = async (val) => {
  if (val.startsWith("*")) {
    const firstAsteriskIndex = val.indexOf("*");
    const secondAsteriskIndex = val.indexOf("*", firstAsteriskIndex + 1);
    const extractedString = val.substring(
      firstAsteriskIndex + 1,
      secondAsteriskIndex
    );
    form.box_number = extractedString;
  }

  try {
    isSearching.value = true;
    searchError.value = null;
    
    const res = await axios.get(route("api.getProducts"), {
      params: {
        box_number: form.box_number,
        process_code: form.process_code,
        client_name: form.client_name,
        client_drawing_number: form.client_drawing_number,
        product_name: form.product_name,
      },
    });
    products.value = res.data.products;
    console.log(res.data.products);
    console.log(res.data.search_keyword);
    
    // 既に並び替えが適用されている場合は、新しい検索結果にも同じ並び替えを適用
    if (sortField.value) {
      sortProducts(sortField.value);
    }
  } catch (e) {
    console.error('検索エラー:', e);
    searchError.value = '検索中にエラーが発生しました。もう一度お試しください。';
  } finally {
    isSearching.value = false;
  }
};
// 検索項目から
const getProducts = async () => {
  try {
    isSearching.value = true;
    searchError.value = null;
    
    await axios
      .get(route("api.getProducts"), {
        params: {
          box_number: form.box_number,
          process_code: form.process_code,
          client_name: form.client_name,
          client_drawing_number: form.client_drawing_number,
          product_name: form.product_name,
        },
      })
      .then((res) => {
        products.value = res.data.products;
        console.log('フォーム最小化', products.value)
        console.log('msg:', res.data.msg)
        formStatus.value = false;
        
        // 既に並び替えが適用されている場合は、新しい検索結果にも同じ並び替えを適用
        if (sortField.value) {
          sortProducts(sortField.value);
        }
      });
  } catch (e) {
    console.error('検索エラー:', e);
    searchError.value = '検索中にエラーが発生しました。もう一度お試しください。';
  } finally {
    isSearching.value = false;
  }
};

// フォームクリア
const clearForm = () => {
  form.box_number = null;
  form.process_code = null;
  form.client_name = null;
  form.client_drawing_number = null;
  form.product_name = null;
};

// OCRポップアップの表示状態
const showOcrPopup = ref(false);

const openOcr = () => {
  showOcrPopup.value = true;
};

const closeOcrPopup = () => {
  showOcrPopup.value = false;
};

const handleOcrResult = (result) => {
  console.log('OCR結果を受信:', result);
  form.client_drawing_number = result;
  showOcrPopup.value = false;
};

// 詳細画面への遷移
const navigateToDetail = (productId) => {
  // 既にナビゲーション中の場合は処理しない
  if (isNavigating.value) {
    return;
  }
  
  isNavigating.value = true;
  console.log('詳細画面への遷移開始:', productId);
  
  // Vue.jsの次のティックでローディング表示を確実に反映
  nextTick(() => {
    router.visit(route('calc.show', { id: productId, search_keyword: form }), {
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

// 並び替え機能
const sortProducts = (field) => {
  console.log('並び替え実行:', field);
  
  // 同じフィールドをクリックした場合は順序を切り替え、違うフィールドの場合は昇順にリセット
  if (sortField.value === field) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortField.value = field;
    sortOrder.value = 'asc';
  }
  
  // 製品リストを並び替え
  products.value.sort((a, b) => {
    let valueA, valueB;
    
    switch (field) {
      case 'client_drawing_number':
        valueA = a.client_drawing_number || '';
        valueB = b.client_drawing_number || '';
        break;
      case 'product_name':
        valueA = a.product_name || '';
        valueB = b.product_name || '';
        break;
      default:
        return 0;
    }
    
    // 文字列比較（大文字小文字を区別しない）
    valueA = valueA.toString().toLowerCase();
    valueB = valueB.toString().toLowerCase();
    
    if (sortOrder.value === 'asc') {
      return valueA.localeCompare(valueB, 'ja');
    } else {
      return valueB.localeCompare(valueA, 'ja');
    }
  });
  
  console.log('並び替え完了:', { field, order: sortOrder.value });
};

onMounted(() => {
  addFormSearchKeyword(props.search_keyword);
  changeFormStatus(props.status);
  
  // 得意先名が設定されている場合、selectSuppliersを適切に初期化
  if (props.search_keyword && props.search_keyword.client_name) {
    // 該当する得意先を含むようにフィルタリングをリセット
    selectSuppliers.value = props.search_clients;
  }
  
  // 検索キーワードがある場合は自動的に検索を実行
  if (props.search_keyword && Object.keys(props.search_keyword).length > 0) {
    const hasValidSearchKeyword = props.search_keyword.box_number || 
                                  props.search_keyword.process_code || 
                                  props.search_keyword.client_name || 
                                  props.search_keyword.client_drawing_number || 
                                  props.search_keyword.product_name;
    
    if (hasValidSearchKeyword) {
      // 検索を実行
      getProducts();
      // 検索フォームを閉じる
      changeFormStatus('close');
    }
  }
});

const products = reactive([]);
const isSearching = ref(false);
const isNavigating = ref(false);
const searchError = ref(null);
const sortField = ref(null);
const sortOrder = ref('asc');
</script>
<template>
  <!-- コントロールヘッダー -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="bg-blue-100 rounded-full p-2">
            <i class="fas fa-search text-blue-600"></i>
          </div>
          <div>
            <h2 class="text-xl font-bold text-gray-800">製品検索</h2>
            <p class="text-sm text-gray-600">条件を指定して棚卸対象の製品を検索します</p>
          </div>
        </div>
        <button
          @click="changeFormStatus"
          class="px-4 py-2 rounded-lg font-medium text-sm transition-all duration-200 shadow-sm hover:shadow-md flex items-center gap-2"
          :class="[
            formStatus
              ? 'bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white'
              : 'bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white'
          ]"
        >
          <i v-if="!formStatus" class="fas fa-chevron-down"></i>
          <i v-else class="fas fa-chevron-up"></i>
          {{ formStatus ? '検索フォームを閉じる' : '検索フォームを開く' }}
        </button>
      </div>
    </div>
  </div>

  <!-- 検索結果ヘッダー -->
  <div v-if="products.value" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
    <div class="text-center">
      <div class="flex items-center justify-center gap-3 mb-2">
        <div class="bg-indigo-100 rounded-full p-2">
          <i class="fas fa-list text-indigo-600"></i>
        </div>
        <h2 class="text-2xl font-bold text-gray-800">検索結果</h2>
      </div>
      <p class="text-lg text-indigo-600 font-semibold">{{ products.value.length }}件の製品が見つかりました</p>
    </div>
  </div>

  <!-- 新規登録案内 -->
  <div
    v-if="!products.value && route().current() == 'calc.home'"
    class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-xl p-8 text-center mb-6 hover:shadow-md transition-shadow"
  >
    <div class="flex justify-center items-center flex-col text-green-700">
      <div class="bg-green-100 rounded-full p-4 mb-4">
        <i class="fas fa-plus-circle text-3xl text-green-600"></i>
      </div>
      <h3 class="text-xl font-bold mb-2">製品が見つからない場合</h3>
      <p class="text-sm text-gray-600 mb-4">検索しても見つからない場合は、新規登録をご利用ください。</p>
      <Link
        :href="route('calc.new')"
        class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-semibold rounded-lg transition-all duration-200 shadow-sm hover:shadow-md"
      >
        <i class="fas fa-plus"></i>
        棚卸し新規登録
      </Link>
    </div>
  </div>

  <!-- 検索フォーム -->
  <div v-if="formStatus" id="search_container" class="mb-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="p-6">
        <form class="space-y-6">
          <!-- クイック検索セクション -->
          <div class="bg-gradient-to-r from-yellow-50 to-amber-50 border border-yellow-200 rounded-lg p-4">
            <div class="flex items-center gap-2 mb-3">
              <div class="bg-yellow-100 rounded-full p-1">
                <i class="fas fa-bolt text-yellow-600 text-sm"></i>
              </div>
              <h3 class="text-lg font-semibold text-gray-800">クイック検索</h3>
            </div>
            <div class="space-y-2">
              <label class="block text-sm font-semibold text-gray-700">
                箱No/現品票No
                <span class="text-red-500 text-xs ml-2">(入力ごとに検索がかかります)</span>
              </label>
              <input
                @change="getByBoxNumber($event.target.value)"
                class="w-full px-4 py-3 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all text-center"
                type="text"
                name="box_number"
                v-model="form.box_number"
                list="box_numbers"
                placeholder="箱番号または現品票番号を入力"
              />
              <datalist id="box_numbers">
                <option
                  v-for="box_number in search_box_numbers"
                  :key="box_number.id"
                  :value="box_number.box_number"
                >
                  {{ box_number.box_number }}
                </option>
              </datalist>
            </div>
          </div>

          <!-- 区切り線 -->
          <div class="border-t border-gray-200 pt-6">
            <div class="flex items-center gap-2 mb-4">
              <div class="bg-blue-100 rounded-full p-1">
                <i class="fas fa-filter text-blue-600 text-sm"></i>
              </div>
              <h3 class="text-lg font-semibold text-gray-800">詳細検索</h3>
            </div>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-6">
              <p class="text-sm text-blue-700">
                <i class="fas fa-info-circle mr-1"></i>
                全て入力する必要はありません。必要な項目のみ入力してください。
              </p>
            </div>
          </div>

          <!-- 得意先名 -->
          <div class="space-y-3">
            <label class="block text-sm font-semibold text-gray-700">得意先名</label>
            <div class="bg-gray-50 rounded-lg p-4">
              <p class="text-sm text-gray-600 mb-3">五十音で絞り込み:</p>
              <div class="flex flex-wrap gap-2">
                <button
                  @click.prevent="filterSuppliers('')"
                  class="px-3 py-1.5 bg-red-100 hover:bg-red-200 text-red-700 rounded-lg transition-colors text-sm font-medium"
                >
                  リセット
                </button>
                <button
                  v-for="kana in ['あ', 'か', 'さ', 'た', 'な', 'は', 'ま', 'や', 'ら', 'わ']"
                  :key="kana"
                  @click.prevent="filterSuppliers(kana)"
                  class="px-3 py-1.5 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-lg transition-colors text-sm font-medium"
                >
                  {{ kana }}
                </button>
              </div>
            </div>
            <select
              class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
              name="client_name"
              v-model="form.client_name"
            >
              <option value="" disabled>得意先を選択してください</option>
              <option
                v-for="client in selectSuppliers"
                :key="client.id"
                :value="client.name"
              >
                {{ client.name }}
              </option>
            </select>
          </div>

          <!-- 図番 -->
          <div class="space-y-2">
            <label class="block text-sm font-semibold text-gray-700">図番</label>
            <div class="flex gap-3">
              <input
                class="flex-1 px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                type="text"
                v-model="form.client_drawing_number"
                placeholder="図面番号を入力"
              />
              <button
                @click.prevent="openOcr"
                class="px-4 py-3 bg-gradient-to-r from-indigo-500 to-indigo-600 hover:from-indigo-600 hover:to-indigo-700 text-white rounded-lg transition-all duration-200 shadow-sm hover:shadow-md flex items-center gap-2"
              >
                <i class="fas fa-camera"></i>
                OCR
              </button>
            </div>
          </div>

          <!-- 品名 -->
          <div class="space-y-2">
            <label class="block text-sm font-semibold text-gray-700">品名</label>
            <input
              class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
              type="text"
              v-model="form.product_name"
              placeholder="製品名を入力"
            />
          </div>

          <!-- 工程区分 -->
          <div class="space-y-2">
            <label class="block text-sm font-semibold text-gray-700">工程区分</label>
            <select
              class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
              name="process_code"
              v-model="form.process_code"
            >
              <option value="" disabled>工程区分を選択してください</option>
              <option
                v-for="process in search_processes"
                :key="process.id"
                :value="process.process_code"
              >
                {{ process.process_name }}
              </option>
            </select>
          </div>

          <!-- アクションボタン -->
          <div class="pt-6 border-t border-gray-200">
            <div class="flex gap-4">
              <button
                @click.prevent="clearForm"
                class="flex-1 px-6 py-3 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium flex items-center justify-center gap-2"
              >
                <i class="fas fa-eraser"></i>
                クリア
              </button>
              <button
                @click.prevent="getProducts"
                :disabled="isSearching"
                class="flex-1 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 disabled:from-gray-400 disabled:to-gray-500 disabled:cursor-not-allowed text-white font-semibold py-3 px-6 rounded-lg transition-all duration-200 shadow-sm hover:shadow-md flex items-center justify-center gap-2"
              >
                <i v-if="!isSearching" class="fas fa-search"></i>
                <i v-else class="fas fa-spinner fa-spin"></i>
                {{ isSearching ? '検索中...' : '検索' }}
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- エラー表示 -->
  <section v-if="searchError && !isSearching">
    <div class="bg-white rounded-xl shadow-sm border border-red-200 overflow-hidden">
      <div class="p-8 text-center">
        <div class="flex justify-center items-center flex-col">
          <div class="bg-red-100 rounded-full p-4 mb-4">
            <i class="fas fa-exclamation-triangle text-3xl text-red-600"></i>
          </div>
          <h3 class="text-xl font-bold text-red-800 mb-2">エラーが発生しました</h3>
          <p class="text-sm text-red-600 mb-4">{{ searchError }}</p>
          <button
            @click="searchError = null"
            class="px-4 py-2 bg-red-100 hover:bg-red-200 text-red-700 rounded-lg transition-colors font-medium"
          >
            閉じる
          </button>
        </div>
      </div>
    </div>
  </section>

  <!-- ローディング表示 -->
  <section v-else-if="isSearching">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="p-12 text-center">
        <div class="flex justify-center items-center flex-col">
          <div class="bg-blue-100 rounded-full p-4 mb-4">
            <i class="fas fa-spinner fa-spin text-3xl text-blue-600"></i>
          </div>
          <h3 class="text-xl font-bold text-gray-800 mb-2">検索中...</h3>
          <p class="text-sm text-gray-600">製品データを検索しています。しばらくお待ちください。</p>
        </div>
      </div>
    </div>
  </section>

  <!-- ナビゲーション中のローディング表示 -->
  <section v-if="isNavigating">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="p-12 text-center">
        <div class="flex justify-center items-center flex-col">
          <div class="bg-indigo-100 rounded-full p-4 mb-4">
            <i class="fas fa-spinner fa-spin text-3xl text-indigo-600"></i>
          </div>
          <h3 class="text-xl font-bold text-gray-800 mb-2">ページを読み込み中...</h3>
          <p class="text-sm text-gray-600">詳細ページに移動しています。しばらくお待ちください。</p>
        </div>
      </div>
    </div>
  </section>

  <!-- 製品検索結果 -->
  <section v-else-if="products.value && !isSearching && !isNavigating">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="p-6">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="border-b border-gray-200">
                <th 
                  @click="sortProducts('client_drawing_number')"
                  class="px-4 py-3 text-left text-sm font-semibold text-gray-700 cursor-pointer hover:bg-gray-50 transition-colors select-none"
                  :class="{
                    'bg-blue-50 text-blue-700': sortField === 'client_drawing_number'
                  }"
                >
                  <div class="flex items-center gap-2">
                    <span>客先図番</span>
                    <div class="flex flex-col">
                      <i 
                        class="fas fa-caret-up text-xs"
                        :class="{
                          'text-blue-600': sortField === 'client_drawing_number' && sortOrder === 'asc',
                          'text-gray-300': sortField !== 'client_drawing_number' || sortOrder !== 'asc'
                        }"
                      ></i>
                      <i 
                        class="fas fa-caret-down text-xs -mt-1"
                        :class="{
                          'text-blue-600': sortField === 'client_drawing_number' && sortOrder === 'desc',
                          'text-gray-300': sortField !== 'client_drawing_number' || sortOrder !== 'desc'
                        }"
                      ></i>
                    </div>
                  </div>
                </th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">箱No/現品表No</th>
                <th 
                  @click="sortProducts('product_name')"
                  class="px-4 py-3 text-left text-sm font-semibold text-gray-700 cursor-pointer hover:bg-gray-50 transition-colors select-none"
                  :class="{
                    'bg-blue-50 text-blue-700': sortField === 'product_name'
                  }"
                >
                  <div class="flex items-center gap-2">
                    <span>品名</span>
                    <div class="flex flex-col">
                      <i 
                        class="fas fa-caret-up text-xs"
                        :class="{
                          'text-blue-600': sortField === 'product_name' && sortOrder === 'asc',
                          'text-gray-300': sortField !== 'product_name' || sortOrder !== 'asc'
                        }"
                      ></i>
                      <i 
                        class="fas fa-caret-down text-xs -mt-1"
                        :class="{
                          'text-blue-600': sortField === 'product_name' && sortOrder === 'desc',
                          'text-gray-300': sortField !== 'product_name' || sortOrder !== 'desc'
                        }"
                      ></i>
                    </div>
                  </div>
                </th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">得意先</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">工程名</th>
                <th class="px-4 py-3 text-center text-sm font-semibold text-gray-700">
                  <i class="fas fa-mouse-pointer text-gray-400"></i>
                </th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="product in products.value"
                :key="product.id"
                @click.self="navigateToDetail(product.id)"
                class="border-b border-gray-100 hover:bg-blue-50 hover:border-blue-200 transition-all duration-100 cursor-pointer group"
                :class="{ 'opacity-50 pointer-events-none': isNavigating }"
              >
                <td @click="navigateToDetail(product.id)" class="px-4 py-4">
                  <div class="flex items-center gap-2">
                    <div v-if="product.abroad_flg == 0" class="text-indigo-600 group-hover:text-indigo-700 transition-colors">
                      <i class="fas fa-warehouse text-sm"></i>
                    </div>
                    <span
                      class="text-sm font-semibold transition-colors"
                      :class="{
                        'text-green-600 group-hover:text-green-700': product.comp_flg === 1,
                        'text-gray-800 group-hover:text-blue-700': product.comp_flg !== 1,
                      }"
                    >
                      {{ product.client_drawing_number }}
                    </span>
                    <span
                      v-if="product.comp_flg === 1"
                      class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 group-hover:bg-green-200 transition-colors"
                    >
                      <i class="fas fa-check-circle mr-1"></i>
                      完了
                    </span>
                  </div>
                </td>
                <td @click="navigateToDetail(product.id)" class="px-4 py-4">
                  <span class="text-sm font-mono bg-gray-100 group-hover:bg-blue-100 px-2 py-1 rounded font-semibold transition-colors">
                    {{ product.box_number }}
                  </span>
                </td>
                <td @click="navigateToDetail(product.id)" class="px-4 py-4">
                  <span class="text-sm font-medium text-gray-800 group-hover:text-blue-700 transition-colors">{{ product.product_name }}</span>
                </td>
                <td @click="navigateToDetail(product.id)" class="px-4 py-4">
                  <span class="text-sm text-gray-700 group-hover:text-blue-600 transition-colors">{{ product.client_name }}</span>
                </td>
                <td @click="navigateToDetail(product.id)" class="px-4 py-4">
                  <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 group-hover:bg-blue-200 group-hover:text-blue-900 transition-colors">
                    {{ product.process_name }}
                  </span>
                </td>
                <td @click="navigateToDetail(product.id)" class="px-4 py-4 text-center">
                  <div class="inline-flex items-center gap-1 text-gray-400 group-hover:text-blue-600 transition-colors">
                    <i class="fas fa-arrow-right text-sm"></i>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>

  <!-- OCRポップアップ -->
  <OcrPopup 
    v-if="showOcrPopup"
    @close="closeOcrPopup"
    @ocrResult="handleOcrResult"
  />
</template>
