<script setup>
import StockLayout from "@/Layouts/StockLayout.vue";
import { Link } from "@inertiajs/vue3";
</script>
<template>
  <StockLayout :padding="true" :title="'滞留品'">
    <template #content>
      <div id="iframe_container">
        <iframe
          :src="route('stock.retention')"
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

  & iframe {
    height: 100%;
    width: 100%;
    object-fit: cover;
  }
}
</style>
