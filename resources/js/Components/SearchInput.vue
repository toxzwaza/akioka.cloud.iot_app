<script setup>
import { ref, reactive, onMounted } from "vue";
import { router, Link } from "@inertiajs/vue3";
import axios from "axios";

const props = defineProps({
  formStatus: Boolean,
  suppliers: Array,
  search_keyword: Array,
});

const selectSuppliers = ref(props.suppliers);
const filterSuppliers = (filterStr) => {
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
        figures.value = res.data.figures;
        changeFormStatus();
      });
  } catch (e) {
    console.log(e);
  }
};

const reSearch = (id) => {
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

onMounted(() => {
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
  <!-- Toggle button -->
  <div class="text-right mb-4">
    <button
      @click="changeFormStatus"
      :class="formStatus ? 'btn-danger' : 'btn-success'"
      class="btn"
    >
      <i :class="formStatus ? 'fa-solid fa-xmark' : 'fa-solid fa-bars'"></i>
      {{ formStatus ? 'Close' : 'Search' }}
    </button>
  </div>

  <!-- Search form -->
  <div v-if="formStatus" class="card p-6 mb-6">
    <form class="space-y-5">
      <p class="text-sm text-slate-400">* All fields are optional.</p>

      <!-- Supplier filter -->
      <div>
        <label class="form-label">Supplier</label>
        <div class="flex flex-wrap gap-2 mb-3">
          <button
            @click.prevent="filterSuppliers('')"
            class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-rose-100 text-rose-600 hover:bg-rose-200 transition-colors"
          >
            Reset
          </button>
          <button
            v-for="kana in ['あ','か','さ','た','な','は','ま','や','ら','わ']"
            :key="kana"
            @click.prevent="filterSuppliers(kana)"
            class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-primary-50 text-primary-600 hover:bg-primary-100 transition-colors"
          >
            {{ kana }}
          </button>
        </div>
        <select class="form-select-modern" v-model="form.supplier_name">
          <option
            v-for="supplier in selectSuppliers"
            :key="supplier.id"
            :value="supplier.name"
          >
            {{ supplier.name }}
          </option>
        </select>
      </div>

      <!-- Figure number -->
      <div>
        <label class="form-label">Figure No.</label>
        <input class="form-input-modern" type="text" v-model="form.m_sup_no" placeholder="Enter figure number" />
      </div>

      <!-- Product name -->
      <div>
        <label class="form-label">Product Name</label>
        <input class="form-input-modern" type="text" v-model="form.name" placeholder="Enter product name" />
      </div>

      <!-- Buttons -->
      <div class="flex justify-end gap-3 pt-2">
        <button @click.prevent="clearForm" class="btn-secondary">
          <i class="fas fa-eraser text-xs"></i>
          Clear
        </button>
        <button @click.prevent="getFigures" class="btn-primary">
          <i class="fas fa-search text-xs"></i>
          Search
        </button>
      </div>

      <!-- Search history -->
      <div v-if="route().current() == 'home'" class="pt-4 border-t border-slate-100">
        <details>
          <summary class="text-sm text-slate-500 cursor-pointer hover:text-slate-700 transition-colors">
            Recent search history
          </summary>
          <div class="mt-4 overflow-x-auto">
            <table class="table-modern">
              <thead>
                <tr>
                  <th>Supplier</th>
                  <th>Figure No.</th>
                  <th>Product</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="archive in figure_search_archives"
                  :key="archive.id"
                >
                  <td>{{ archive.supplier_name ?? "-" }}</td>
                  <td>{{ archive.sup_no ?? "-" }}</td>
                  <td>{{ archive.name ?? "-" }}</td>
                  <td class="text-right">
                    <button
                      @click.prevent="reSearch(archive.id)"
                      class="text-sm font-semibold text-primary-600 hover:text-primary-700 px-3 py-1.5 rounded-lg hover:bg-primary-50 transition-all"
                    >
                      Re-search
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </details>
      </div>
    </form>
  </div>

  <!-- Results -->
  <section v-if="figures && figures.value && figures.value.length > 0">
    <div class="mb-6">
      <h2 class="section-title">
        Results
        <span class="text-primary-600 ml-2">{{ figures.value.length }} items</span>
      </h2>
    </div>
    <div class="card overflow-hidden">
      <div class="overflow-x-auto">
        <table class="table-modern">
          <thead>
            <tr>
              <th>ID</th>
              <th>Image</th>
              <th>Product</th>
              <th>Supplier</th>
              <th>Figure No.</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="figure in figures.value" :key="figure.id">
              <td class="font-mono text-xs">{{ figure.id }}</td>
              <td>
                <img class="w-14 h-14 object-contain rounded-lg bg-slate-50" :src="figure.img_path" alt="" />
              </td>
              <td class="font-semibold text-slate-900">{{ figure.name }}</td>
              <td>{{ figure.supplier_name }}</td>
              <td class="font-mono text-xs">{{ figure.m_sup_no }}</td>
              <td class="text-right">
                <Link
                  v-if="route().current() == 'calc.new'"
                  :href="route('calc.show.new', {'id' : figure.id })"
                  :data="{ id: figure.id, search_keyword: form }"
                  class="btn-primary text-xs px-3 py-2"
                >
                  Register
                </Link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</template>
