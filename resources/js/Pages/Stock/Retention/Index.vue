<script setup>
import RetentionLayout from "@/Layouts/RetentionLayout.vue";
import { onMounted, reactive, ref } from "vue";
import axios from "axios";

const props = defineProps({
  stocks: Array,
});

const retentionStocks = ref([]);

const getRetentionStocks = () => {
  axios
    .get(route("stock.retention.getRetentionStocks"))
    .then((res) => {
      console.log(res.data);
      retentionStocks.value = res.data;
    })
    .catch((error) => {
      console.log(error);
    });
};
onMounted(() => {
  getRetentionStocks();
});
</script>
<template>
  <RetentionLayout :title="'納品登録'">
    <template #content>
      <section class="text-gray-600 body-font">
        <div class="container py-12 mx-auto w-full">
          <h1 class="text-center text-3xl font-medium title-font mb-2 text-gray-600">
            滞留品一覧
          </h1>
          <div class="flex flex-col text-center w-full mb-20">
            <p class="lg:w-2/3 mx-auto leading-relaxed text-base">
              滞留品及び半滞留品が表示されます。
            </p>
          </div>
          <!-- データロード中 -->
          <div v-if="retentionStocks.length < 1">
            <h2 class="text-xl text-gray-700 text-center font-serif">
              滞留データ取得中...しばらくお待ちください。
            </h2>
            <div class="flex justify-center">
              <img src="/images/stocks/spinner.gif" alt="" />
            </div>
          </div>

          <div v-else class="w-full mx-auto overflow-auto">
            <table class="table-auto w-full text-left whitespace-no-wrap">
              <thead>
                <tr>
                  <th
                    class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100 rounded-tl rounded-bl"
                  >
                    画像
                  </th>
                  <th
                    class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100 rounded-tl rounded-bl"
                  >
                    品名
                  </th>
                  <th
                    class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
                  >
                    品番
                  </th>
                  <th
                    class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
                  >
                    格納先
                  </th>
                  <th
                    class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
                  >
                    アドレス
                  </th>
                  <th
                    class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
                  >
                    設置日
                  </th>
                  <th
                    class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
                  >
                    最終出庫日
                  </th>
                  <th
                    class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
                  >
                    滞留ステータス
                  </th>
                  <th
                    class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
                  >
                    滞留カード設置
                  </th>
                  <th
                    class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
                  ></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="stock in retentionStocks" :key="stock.stock_id">
                  <td class="px-4 py-3 w-32">
                    <img
                      :src="
                        stock.img_path && stock.img_path.includes('storage')
                          ? `https://akioka.cloud/${stock.img_path}`
                          : stock.img_path
                      "
                      alt="Stock Image"
                    />
                  </td>
                  <td class="px-4 py-3 w-52">
                    {{ stock.name }}
                  </td>
                  <td class="px-4 py-3 w-38">
                    {{ stock.s_name }}
                  </td>
                  <td class="px-4 py-3 whitespace-nowrap">
                    {{ stock.location_name }}
                  </td>
                  <td class="px-4 py-3 whitespace-nowrap">
                    {{ stock.address }}
                  </td>
                  <td class="px-4 py-3">
                    {{
                      new Date(stock.initial_date).toLocaleDateString("ja-JP", {
                        year: "numeric",
                        month: "2-digit",
                        day: "2-digit",
                      })
                    }}
                  </td>
                  <td class="px-4 py-3">
                    {{
                      stock.last_shipment_date
                        ? new Date(stock.last_shipment_date).toLocaleDateString(
                            "ja-JP",
                            {
                              year: "numeric",
                              month: "2-digit",
                              day: "2-digit",
                            }
                          )
                        : " - "
                    }}
                  </td>
                  <td
                    :class="{
                      'px-4 py-3 whitespace-nowrap': true,
                      'text-green-500': stock.retention_code == 0,
                      'text-orange-500 font-bold': stock.retention_code == 1,
                      'text-red-500 font-bold': retention_code == 2,
                    }"
                  >
                    {{
                      stock.retention_code == 1
                        ? "半滞留"
                        : stock.retention_code == 2
                        ? "滞留"
                        : "正常"
                    }}
                  </td>
                  <td class="px-4 py-3"></td>
                  <td class="px-4 py-3">
                    <button
                      class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-xs whitespace-nowrap"
                    >
                      処理済
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>
    </template>
  </RetentionLayout>
</template>
<style scoped>
</style>