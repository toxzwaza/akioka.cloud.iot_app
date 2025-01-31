<script setup>
import StockLayout from "@/Layouts/StockLayout.vue";

import { Link } from "@inertiajs/vue3";
</script>
<template>
  <StockLayout :padding="true" :title="'納品'">
    <template #content>
      <div id="iframe_container">
        <iframe
          :src="route('stock.receive')"
          frameborder="0"
        ></iframe>
      </div>
    </template>
  </StockLayout>
</template>
<style scoped lang="scss">
#iframe_container {
  height: 100%;
  width: 100%;
  box-shadow: rgba(99, 99, 99, 0.2) 0px 2px 8px 0px;
  padding: 1rem;

  & iframe {
    height: 100%;
    width: 100%;
    object-fit: cover;
  }
}
</style>
