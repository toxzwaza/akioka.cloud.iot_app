<script setup>
import StockLayout from "@/Layouts/StockLayout.vue";
import StockForm from "@/Components/StockForm.vue";
import { Link } from "@inertiajs/vue3";

import { ref } from "vue";

const stocks = ref([]);
const handleUpdateStocks = (data) => {
  stocks.value = data;
  search_box.value = false;
};
const clearStocks = () => {
  stocks.value = [];
};

const search_box = ref(true);
</script>
<template>
  <StockLayout :title="'検索'">
    <template #content>
      <!-- 検索フォームコンポーネント -->
      <div :class="{ 'hide' : !search_box }">
        <StockForm  @updateStocks="handleUpdateStocks" />
      </div>
      <button
        v-if="!search_box"
        :class="{'ml-4 bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded' : true}"
        @click="search_box = true"
      >
        検索画面を表示
      </button>

      <!-- 検索結果表示用コンポーネント -->
      <div>
        <div v-if="stocks.length > 0" class="">
          <button
            v-if="search_box"
            @click="search_box = false"
            :class="{'ml-4 bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded' : true }"
          >
            検索画面を非表示
          </button>

          <hr class="my-8" />
          <div class="mt-4 flex flex-wrap justify-between">
            <div
              v-for="stock in stocks"
              :key="stock.id"
              class="stock_card bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700 m-4"
            >
              <div class="stock_img">
                <img class="rounded-t-lg" :src="stock.img_path" alt="" />
              </div>
              <div class="p-5">
                <a href="#">
                  <h5
                    class="whitespace-nowrap text-ellipsis overflow-hidden mb-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white"
                  >
                    {{ stock.name }}
                  </h5>
                  <h6>品番:{{ stock.s_name }}</h6>
                </a>
                <p class="font-normal text-gray-700 dark:text-gray-400">
                  格納先:{{ stock.location_name }}
                  <span class="font-bold">{{ stock.address }}</span>
                </p>
                <p class="font-normal text-gray-700 dark:text-gray-400">
                  格納数:{{ stock.quantity }}
                </p>

                <Link
                  :href="route('stock.inventory.show', { id: stock.id })"
                  class="mt-4 inline-flex items-center px-3 py-2 text-sm font-medium text-center text-white bg-blue-500 rounded-lg hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-500 dark:focus:ring-blue-800"
                >
                  詳細画面へ進む
                  <svg
                    class="rtl:rotate-180 w-3.5 h-3.5 ms-2"
                    aria-hidden="true"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 14 10"
                  >
                    <path
                      stroke="currentColor"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M1 5h12m0 0L9 1m4 4L9 9"
                    />
                  </svg>
                </Link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </StockLayout>
</template>
<style scoped lang="scss">
.hide{
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
