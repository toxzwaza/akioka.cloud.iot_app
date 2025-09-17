<script setup>
import MainLayout from "@/Layouts/Calc/MainLayout.vue";
import SearchInput from "@/Components/Calc/SearchInput.vue";

import { onMounted } from "vue";

const props = defineProps({
  login_user: Object,
  search_clients: Array,
  search_processes: Array,
  search_box_numbers: Array,
  search_keyword: Array,
});

onMounted(() => {
  // 検索キーワードがある場合の処理はSearchInputコンポーネント内で行われる
  if (props.search_keyword && Object.keys(props.search_keyword).length > 0) {
    console.log('検索キーワードでページを復元:', props.search_keyword);
  }
});
</script>

<template>
  <MainLayout :url="'stock.home'" :title="'製品検索'" :login_user="login_user">
    <template #content>
      <!-- 検索フォームと結果 -->
      <SearchInput
        :status="search_keyword && Object.keys(search_keyword).length > 0 ? 'close' : 'open'"
        :search_clients="search_clients"
        :search_processes="search_processes"
        :search_box_numbers="search_box_numbers"
        :search_keyword="search_keyword"
      />
    </template>
  </MainLayout>
</template>

<style>
</style>
