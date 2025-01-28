<script setup>
import StockLayout from "@/Layouts/StockLayout.vue";
import StockForm from "@/Components/StockForm.vue";

const props = defineProps({
  stock_storage: Object
})
</script>
<template>
  <StockLayout :title="'出庫'">
    <template #content>
      <StockForm :stock_storage="props.stock_storage"/>
    </template>
  </StockLayout>
</template>
<style scoped lang="scss">

</style>
