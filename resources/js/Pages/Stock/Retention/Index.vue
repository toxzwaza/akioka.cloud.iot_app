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
      <section class="py-8">
        <div class="container mx-auto w-full px-4">
          <div class="page-header mb-8">
            <h1 class="section-title text-center">滞留品一覧</h1>
            <p class="section-subtitle text-center mt-2">
              滞留品及び半滞留品が表示されます。
            </p>
          </div>

          <!-- データロード中 -->
          <div v-if="retentionStocks.length < 1" class="flex flex-col items-center justify-center py-16">
            <div class="animate-spin rounded-full h-12 w-12 border-4 border-indigo-200 border-t-indigo-600 mb-4"></div>
            <p class="text-slate-600 text-lg">滞留データ取得中...しばらくお待ちください。</p>
          </div>

          <div v-else class="card overflow-hidden">
            <div class="overflow-x-auto">
              <table class="table-modern">
                <thead>
                  <tr>
                    <th>画像</th>
                    <th>品名</th>
                    <th>品番</th>
                    <th>格納先</th>
                    <th>アドレス</th>
                    <th>設置日</th>
                    <th>最終出庫日</th>
                    <th>滞留ステータス</th>
                    <th>滞留カード設置</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="stock in retentionStocks" :key="stock.stock_id">
                    <td class="w-32">
                      <img
                        :src="
                          stock.img_path && stock.img_path.includes('storage')
                            ? `https://akioka.cloud/${stock.img_path}`
                            : stock.img_path
                        "
                        alt="Stock Image"
                        class="w-20 h-auto object-contain rounded"
                      />
                    </td>
                    <td class="w-52">
                      {{ stock.name }}
                    </td>
                    <td class="w-38">
                      {{ stock.s_name }}
                    </td>
                    <td class="whitespace-nowrap">
                      {{ stock.location_name }}
                    </td>
                    <td class="whitespace-nowrap">
                      {{ stock.address }}
                    </td>
                    <td>
                      {{
                        new Date(stock.initial_date).toLocaleDateString("ja-JP", {
                          year: "numeric",
                          month: "2-digit",
                          day: "2-digit",
                        })
                      }}
                    </td>
                    <td>
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
                    <td class="whitespace-nowrap">
                      <span
                        v-if="stock.retention_code == 0"
                        class="badge-success"
                      >正常</span>
                      <span
                        v-else-if="stock.retention_code == 1"
                        class="badge-warning"
                      >半滞留</span>
                      <span
                        v-else-if="retention_code == 2"
                        class="badge-danger"
                      >滞留</span>
                    </td>
                    <td></td>
                    <td>
                      <button class="btn-primary text-xs whitespace-nowrap">
                        処理済
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </section>
    </template>
  </RetentionLayout>
</template>
<style scoped>
</style>