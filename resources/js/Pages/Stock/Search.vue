<script setup>
import StockLayout from "@/Layouts/StockLayout.vue";
import StockForm from "@/Components/StockForm.vue";
import { Link, router } from "@inertiajs/vue3";
import { getImgPath } from "@/Helper/method";
import { reactive, ref, onMounted } from "vue";

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
  console.log(form.search.process_id);

  process_users.value = props.users.filter(
    (user) => user.process_id === form.search.process_id
  );
};

const clickButton = () => {
  console.log(form.search);

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
const clearStocks = () => {
  stocks.value = [];
};

const search_box = ref(true);

onMounted(() => {
  console.log(props.search);

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
</script>
<template>
  <StockLayout :title="'検索'">
    <template #content>
      <!-- 検索フォームコンポーネント -->
      <div :class="{ hide: !search_box }">
        <div class="w-full p-2">
          <!-- 検索用フォーム -->
          <form>
            <!-- 工程選択 -->
            <div class="flex flex-wrap -mx-3 mb-10 items-end bg-white pt-2">
              <div class="w-1/2 px-3">
                <label for="" class="text-red-500 font-bold"
                  >過去の発注履歴から検索</label
                >
                <select
                  name=""
                  id=""
                  v-model="form.search.process_id"
                  class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-4 px-6 mb-3 leading-tight focus:outline-none focus:bg-white focus:border-gray-500 mt-2"
                  @change="changeProcess"
                >
                  <option value="0">工程を選択</option>
                  <option
                    v-for="process in props.processes"
                    :key="process.id"
                    :value="process.id"
                  >
                    {{ process.name }}
                  </option>
                </select>
              </div>
              <div class="w-1/2 px-3" v-if="form.search.process_id">
                <label for="" class="text-red-500 font-bold"></label>
                <select
                  name=""
                  id=""
                  v-model="form.search.user_id"
                  class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-4 px-6 mb-3 leading-tight focus:outline-none focus:bg-white focus:border-gray-500 mt-2"
                >
                  <option value="0">依頼者でさらに絞り込み</option>
                  <option
                    v-for="user in process_users"
                    :key="user.id"
                    :value="user.id"
                  >
                    {{ user.name }}
                  </option>
                </select>
              </div>
            </div>

            <div class="flex flex-wrap -mx-3 mb-6">
              <div class="w-full px-3">
                <input
                  name="search_name"
                  class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-4 px-6 mb-3 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                  id="grid-password"
                  type="text"
                  placeholder="品名"
                  v-model="form.search.stock_name"
                />
              </div>
            </div>
            <div class="flex flex-wrap -mx-3 mb-6">
              <div class="w-full px-3">
                <input
                  name="search_name"
                  class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-4 px-6 mb-3 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                  id="grid-password"
                  type="text"
                  placeholder="品番"
                  v-model="form.search.stock_s_name"
                />
              </div>
            </div>
            <div class="flex flex-wrap -mx-3 mb-6">
              <div class="w-full px-3">
                <select
                  name=""
                  id=""
                  v-model="form.search.classification_id"
                  class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-4 px-6 mb-3 leading-tight focus:outline-none focus:bg-white focus:border-gray-500 mt-2"
                >
                  <option value="0">カテゴリを選択</option>
                  <option
                    v-for="classification in props.classifications"
                    :key="classification.id"
                    :value="classification.id"
                  >
                    {{ classification.name }}
                  </option>
                </select>
              </div>
            </div>
            <div class="flex flex-wrap -mx-3 mb-6">
              <div class="w-full px-3">
                <input
                  name="alias"
                  class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-4 px-6 mb-3 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                  id="grid-password"
                  type="text"
                  placeholder="略名"
                  v-model="form.search.alias"
                />
              </div>
            </div>
            <div class="flex flex-wrap -mx-3 mb-6">
              <div class="w-full px-3">
                <input
                  name="address_id"
                  class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-4 px-6 mb-3 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                  id="grid-password"
                  type="number"
                  placeholder="棚アドレス"
                  v-model="form.search.address_id"
                />
              </div>
            </div>
            <div class="flex flex-wrap -mx-3 mb-6">
              <div class="w-full px-3">
                <input
                  name="stock_id"
                  class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-4 px-6 mb-3 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                  id="grid-password"
                  type="number"
                  placeholder="製品ID or JANコード"
                  v-model="form.search.stock_id"
                />
              </div>
            </div>

            <button
              @click.prevent="clickButton"
              class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-4 px-8 rounded"
            >
              検索
            </button>
          </form>
        </div>
      </div>
      <button
        v-if="!search_box"
        :class="{
          'ml-4 bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded': true,
        }"
        @click="search_box = true"
      >
        検索画面を表示
      </button>
    </template>
  </StockLayout>
</template>
<style scoped lang="scss">
.hide {
  height: 0;
  overflow: hidden;
  opacity: 0;
}

.stock_card {
  width: 28%;
  height: 30%;
  & .stock_img {
    width: 100%;
    height: 23vh;
    display: flex;
    justify-content: center;
    padding: 0 1rem;
    background-color: #ffffff;

    & img {
      width: 100%;
      object-fit: contain;
    }
  }
}
</style>
