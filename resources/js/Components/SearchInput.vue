<script setup>
import { ref, reactive, onMounted } from "vue";
import { router, Link } from "@inertiajs/vue3";
import axios from "axios";

const props = defineProps({
  formStatus: Boolean,
  suppliers: Array,
  search_keyword: Array,
});

// リアクティブな取引先セレクトボックス
const selectSuppliers = ref(props.suppliers);
const filterSuppliers = (filterStr) => {
  console.log(filterStr);
  console.log(props.suppliers);

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

  selectSuppliers.value = props.suppliers.filter((supplier) =>
    filterChars.some((char) => supplier.furi_name.startsWith(char))
  );
};

const formStatus = ref(props.formStatus);
const changeFormStatus = () => {
  formStatus.value = !formStatus.value;
};

const figure_search_archives = ref([]);

const figures = reactive([]);

const form = reactive({
  supplier_name: null,
  m_sup_no: null,
  name: null,
});
const clearForm = () => {
  form.supplier_name = null;
  form.m_sup_no = null;
  form.name = null;
};

const getFigures = async () => {
  try {
    await axios
      .get(route("calc.getFigures"), {
        params: {
          supplier_name: form.supplier_name,
          m_sup_no: form.m_sup_no,
          name: form.name,
        },
      })
      .then((res) => {
        figure_search_archives.value = res.data.figure_search_archives;

        console.log(res.data.figures);
        figures.value = res.data.figures;

        changeFormStatus();
      });
  } catch (e) {
    console.log(e);
  }
};

// 再検索
const reSearch = (id) => {
  console.log(id);
  const archive = figure_search_archives.value.find(
    (archive) => archive.id === id
  );
  if (archive) {
    form.supplier_name = archive.supplier_name;
    form.m_sup_no = archive.sup_no;
    form.name = archive.name;
    getFigures();
  }
};

const getFigureSearchArchives = () => {
  axios.get(route("api.getFigureSearchArchive")).then((res) => {
    figure_search_archives.value = res.data;
  });
};

onMounted(() => {
  // getFigureSearchArchives();
  console.log(props.search_keyword);
  try {
    if (props.search_keyword.supplier_name) {
      form.supplier_name = props.search_keyword.supplier_name;
    }
    if (props.search_keyword.m_sup_no) {
      form.m_sup_no = props.search_keyword.m_sup_no;
    }
    if (props.search_keyword.name) {
      form.name = props.search_keyword.name;
    }
  } catch (e) {
    console.log(e);
  }
});
</script>
<template>
  <div class="text-right">
    <button
      @click="changeFormStatus"
      :class="[
        formStatus
          ? 'bg-red-500 hover:bg-red-700'
          : 'bg-green-500 hover:bg-green-700',
        'text-white font-bold py-2 px-4 border border-gray-200 rounded',
      ]"
    >
      <i v-if="!formStatus" class="fa-solid fa-bars"></i>
      <i v-else class="fa-solid fa-xmark"></i>
    </button>
  </div>

  <div v-if="formStatus" id="search_container" class="bg-gray-100 px-4">
    <form action="" class="w-full mx-auto py-8">
      <span class="block mb-4 text-red-500"
        >*全て入力する必要はありません。</span
      >

      <p class="my-4">
        <label for="" class="text-gray-600">取引先選択</label><br />
        <div class="button_container my-4">
          <button @click.prevent="filterSuppliers('')" class="mr-2 bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
            リセット
          </button>
          <button @click.prevent="filterSuppliers('あ')" class="mr-2 bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            あ
          </button>
          <button @click.prevent="filterSuppliers('か')" class="mr-2 bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            か
          </button>
          <button @click.prevent="filterSuppliers('さ')" class="mr-2 bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            さ
          </button>
          <button @click.prevent="filterSuppliers('た')" class="mr-2 bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            た
          </button>
          <button @click.prevent="filterSuppliers('な')" class="mr-2 bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            な
          </button>
          <button @click.prevent="filterSuppliers('は')" class="mr-2 bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            は
          </button>
          <button @click.prevent="filterSuppliers('ま')" class="mr-2 bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            ま
          </button>
          <button @click.prevent="filterSuppliers('や')" class="mr-2 bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            や
          </button>
          <button @click.prevent="filterSuppliers('ら')" class="mr-2 bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            ら
          </button>
          <button @click.prevent="filterSuppliers('わ')" class="mr-2 bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            わ
          </button>
        </div>
        <select
          class="mt-1 w-full py-4 text-center"
          name=""
          id=""
          v-model="form.supplier_name"
        >
          <option
            class="w-3/5 text-left py-2 pl-4"
            v-for="supplier in selectSuppliers"
            :key="supplier.id"
            :value="supplier.name"
          >
            {{ supplier.name }}
          </option>
        </select>
      </p>
      <p class="my-4">
        <label for="" class="text-gray-600">図番</label><br />
        <input
          class="mt-1 w-full py-4 text-center"
          type="text"
          v-model="form.m_sup_no"
        />
      </p>
      <p class="my-4">
        <label for="" class="text-gray-600">品名</label><br />
        <input
          class="mt-1 w-full py-4 text-center"
          type="text"
          v-model="form.name"
        />
      </p>
      <p class="mt-8 text-right">
        <button
          @click.prevent="clearForm"
          class="mr-4 bg-transparent hover:bg-gray-500 text-gray-700 font-semibold hover:text-white py-2 px-4 border hover:border-transparent rounded"
        >
          クリア
        </button>

        <button
          @click.prevent="getFigures"
          class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 border border-blue-700 rounded"
          as="button"
        >
          検索
        </button>
      </p>

      <!-- 直近の検索履歴を表示 -->
      <div v-if="route().current() == 'home'" :href="route('show')" class="py-4">
        <details class="">
          <summary class="text-gray-500 text-md">
            直近の検索履歴から検索
          </summary>
          <table
            class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400 mt-8"
          >
            <thead
              class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400"
            >
              <tr>
                <th scope="col" class="px-6 py-3">取引先</th>
                <th scope="col" class="px-6 py-3">図番</th>
                <th scope="col" class="px-6 py-3">品名</th>
                <th scope="col" class="px-6 py-3"></th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="figure_search_archive in figure_search_archives"
                :key="figure_search_archive.id"
                class="odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800 border-b dark:border-gray-700"
              >
                <td class="px-6 py-4">
                  {{ figure_search_archive.supplier_name ?? "-" }}
                </td>
                <td class="px-6 py-4">
                  {{ figure_search_archive.sup_no ?? "-" }}
                </td>
                <td class="px-6 py-4">
                  {{ figure_search_archive.name ?? "-" }}
                </td>
                <td class="px-6 py-4 text-right">
                  <button
                    @click.prevent="reSearch(figure_search_archive.id)"
                    class="bg-transparent hover:bg-blue-500 text-blue-700 font-semibold hover:text-white py-2 px-4 border border-blue-500 hover:border-transparent rounded"
                  >
                    再検索
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </details>
      </div>
    </form>
  </div>

  <section
    v-if="figures && figures.value && figures.value.length > 0"
    class="text-gray-600 body-font"
  >
    <div class="container px-5 py-24 mx-auto">
      <div class="flex flex-col text-center w-full mb-20">
        <h1
          class="sm:text-4xl text-3xl font-medium title-font mb-2 text-gray-900"
        >
          検索結果<span class="text-indigo-500" v-if="figures.value">
            - {{ figures.value.length }}件</span
          >
        </h1>
      </div>
      <div class="w-full mx-auto overflow-auto">
        <table class="table-auto w-full text-left whitespace-no-wrap">
          <thead>
            <tr>
              <th
                class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100 rounded-tl rounded-bl"
              >
                ID
              </th>
              <th
                class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
              >
                画像
              </th>
              <th
                class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
              >
                品名
              </th>
              <th
                class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
              >
                取引先
              </th>
              <th
                class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
              >
                図番
              </th>

              <th
                class="w-10 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100 rounded-tr rounded-br"
              ></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="figure in figures.value" :key="figure.id">
              <td class="px-4 py-8">{{ figure.id }}</td>
              <td class="px-4 py-8">
                <img class="w-16" :src="figure.img_path" alt="" />
              </td>
              <td class="px-4 py-8 text-lg text-gray-900 font-bold">
                {{ figure.name }}
              </td>
              <td class="px-4 py-8">{{ figure.supplier_name }}</td>
              <td class="px-4 py-8">{{ figure.m_sup_no }}</td>
              <td class="px-4 py-8 w-10 text-center">
       
                <Link
                  v-if="route().current() == 'calc.new'"
                  :href="route('calc.show.new', {'id' : figure.id })"
                  :data="{ id: figure.id, search_keyword: form }"
                  class="whitespace-nowrap bg-transparent hover:bg-blue-500 text-blue-700 font-semibold hover:text-white py-2 px-4 border border-blue-500 hover:border-transparent rounded"
                  >棚卸登録</Link
                >


                <!-- <Link
                  v-else
                  :href="route('show')"
                  :data="{ id: figure.id, search_keyword: form }"
                  class="whitespace-nowrap bg-transparent hover:bg-blue-500 text-blue-700 font-semibold hover:text-white py-2 px-4 border border-blue-500 hover:border-transparent rounded"
                  >図面</Link
                > -->
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</template>


<style>
</style>