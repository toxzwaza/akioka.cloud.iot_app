<script setup>
import StockLayout from "@/Layouts/StockLayout.vue";
import StockForm from "@/Components/StockForm.vue";
import { Link } from "@inertiajs/vue3";
import { getImgPath } from "@/Helper/method";
import { ref } from "vue";

const props = defineProps({
  stocks: Array,
  search: Array
})
</script>
<template>
  <StockLayout :title="'検索'">
    <template #content>

      <Link
        :class="{'ml-4 bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded' : true}"
        :href="route('stock.search', { search: props.search })"
      >
        検索画面を表示
      </Link>

      <!-- 検索結果表示用コンポーネント -->
      <div>
        <div v-if="stocks.length > 0" class="">

          <hr class="my-8" />
          <div class="mt-4 flex flex-wrap justify-between">
            <div
              v-for="stock in stocks"
              :key="stock.id"
              class="stock_card bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700 m-4"
            >
              <div class="stock_img">
                <img class="rounded-t-lg" :src="getImgPath(stock.img_path)" alt="" />
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
                  :href="route('stock.inventory.show', {stock_id: stock.id,  stock_storage_id: stock.stock_storage_id ?? 0 })"
                  :class="{ 'mt-4 inline-flex items-center px-3 py-2 text-sm font-medium text-center text-white  rounded-lg  focus:ring-4 focus:outline-none dark:focus:ring-blue-800 focus:ring-blue-300' : true , 'bg-blue-500 hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-500' : stock.stock_storage_id, 'bg-gray-500 hover:bg-gray-800 dark:bg-gray-600 dark:hover:bg-gray-500' : !stock.stock_storage_id}"
                >
                  {{ stock.stock_storage_id ? '詳細画面へ進む' : '詳細画面へ進む' }}
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
