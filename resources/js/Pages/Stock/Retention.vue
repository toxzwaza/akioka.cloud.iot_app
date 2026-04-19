<script setup>
import StockLayout from "@/Layouts/StockLayout.vue";
import { Link } from "@inertiajs/vue3";
</script>
<template>
  <StockLayout :padding="true" :title="'滞留品'">
    <template #content>
      <div class="card p-0 overflow-hidden h-full">
        <iframe
          :src="route('stock.retention')"
          class="w-full h-full border-0"
        ></iframe>
      </div>
    </template>
  </StockLayout>
</template>
<style scoped lang="scss">
.card {
  height: 100%;
  width: 100%;
}
</style>
