<script setup>
import StockLayout from "@/Layouts/StockLayout.vue";
import StockForm from "@/Components/StockForm.vue";
import { Link } from "@inertiajs/vue3";
import { getImgPath } from "@/Helper/method";
import { ref, onMounted } from "vue";

const props = defineProps({
  stocks: Array,
  search: Array
})

onMounted( ()=> {
  console.log(props.stocks[0])
})
</script>
<template>
  <StockLayout :title="'検索'">
    <template #content>

      <div class="page-header mb-6">
        <div>
          <h1 class="section-title">
            <i class="fas fa-search text-primary-500 mr-2"></i>
            検索結果
          </h1>
          <p class="section-subtitle">{{ stocks.length }} 件の在庫が見つかりました</p>
        </div>
        <div>
          <Link
            class="btn-primary"
            :href="route('stock.search', { search: props.search })"
          >
            <i class="fas fa-search mr-2"></i>
            検索画面を表示
          </Link>
        </div>
      </div>

      <!-- 検索結果表示用コンポーネント -->
      <div v-if="stocks.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="stock in stocks"
          :key="stock.id"
          class="card card-hover overflow-hidden"
        >
          <div class="bg-slate-50 flex items-center justify-center p-4" style="height: 220px;">
            <img
              class="rounded-xl max-h-full object-contain"
              :src="getImgPath(stock.img_path)"
              alt=""
            />
          </div>
          <div class="p-6">
            <h5 class="text-xl font-bold text-slate-800 truncate mb-2">
              {{ stock.name }}
            </h5>
            <p class="text-base text-slate-500 mb-4">
              <i class="fas fa-barcode mr-1.5"></i>品番: {{ stock.s_name }}
            </p>

            <div class="space-y-2 text-base text-slate-600 mb-5">
              <p>
                <i class="fas fa-map-marker-alt text-slate-400 mr-2 w-4 text-center"></i>
                格納先: {{ stock.location_name }}
                <span class="font-semibold text-slate-800">{{ stock.address }}</span>
              </p>
              <p>
                <i class="fas fa-boxes text-slate-400 mr-2 w-4 text-center"></i>
                格納数: <span class="font-semibold text-slate-800">{{ stock.quantity }}</span>
              </p>
              <p>
                <i class="fas fa-truck text-slate-400 mr-2 w-4 text-center"></i>
                手配先: {{ stock.supplier_name }}
              </p>
            </div>

            <Link
              :href="route('stock.inventory.show', {stock_id: stock.id,  stock_storage_id: stock.stock_storage_id ?? 0 })"
              :class="[stock.stock_storage_id ? 'btn-primary' : 'btn-secondary', 'w-full justify-center py-4 text-base font-bold rounded-xl']"
            >
              詳細画面へ進む
              <i class="fas fa-arrow-right ml-2"></i>
            </Link>
          </div>
        </div>
      </div>

      <!-- Empty state -->
      <div v-else class="card p-8 text-center">
        <div class="w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
          <i class="fas fa-search text-slate-400 text-2xl"></i>
        </div>
        <h2 class="text-lg font-semibold text-slate-600 mb-2">検索結果なし</h2>
        <p class="text-sm text-slate-400 mb-4">条件に一致する在庫が見つかりませんでした。</p>
        <Link
          class="btn-primary inline-flex"
          :href="route('stock.search', { search: props.search })"
        >
          <i class="fas fa-search mr-2"></i>
          検索画面に戻る
        </Link>
      </div>

    </template>
  </StockLayout>
</template>
