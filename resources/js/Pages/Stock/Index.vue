<script setup>
import StockLayout from "@/Layouts/StockLayout.vue";

import { Link } from "@inertiajs/vue3";
</script>
<template>
  <StockLayout :title="'在庫管理システム'">
    <template #content>
      <div id="icon_container">
        <!-- 検索画面 -->
        <div class="w-1/2 p-4">
          <Link :href="route('stock.search')"
            ><img class="" src="/images/stocks/icons/search.png" alt="検索画面"
          /></Link>
        </div>
        <!-- 出庫画面 -->
        <div class="w-1/2 p-4">
          <Link :href="route('stock.shipment')"
            ><img
              class=""
              src="/images/stocks/icons/shipment.png"
              alt="出庫画面"
          /></Link>
        </div>

        <!-- 発注画面 -->
        <!-- <div class="w-1/2 p-4">
          <Link :href="route('stock.order.create')"
            ><img class="" src="/images/stocks/icons/order.png" alt="発注画面"
          /></Link>
        </div> -->
        <!-- 在庫追加画面 -->
        <!-- <div class="w-1/2 p-4">
          <Link :href="route('stock.inventory.create')"><img class="" src="/images/stocks/icons/inventory.png" alt="在庫追加画面" /></Link>
        </div> -->
      </div>
    </template>
  </StockLayout>
</template>
<style scoped>
#icon_container {
  display: flex;
  flex-wrap: wrap;
}
</style>
