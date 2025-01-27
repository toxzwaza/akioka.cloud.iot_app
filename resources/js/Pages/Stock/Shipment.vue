<script setup>
import StockLayout from "@/Layouts/StockLayout.vue";
import StockForm from "@/Components/StockForm.vue";
</script>
<template>
  <StockLayout :title="'出庫'">
    <template #content>
      <StockForm />
    </template>
  </StockLayout>
</template>
<style scoped lang="scss">

</style>
