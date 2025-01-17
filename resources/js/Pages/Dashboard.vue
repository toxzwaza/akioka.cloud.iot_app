<script setup>
import MainLayout from "@/Layouts/MainLayout.vue"
import DataCard from "@/Components/DataCard.vue"
</script>

<template>
  <MainLayout :title="'Dashboard'">
    <template #content>
      <h1 class="text-lg font-bold text-gray-400 mb-2">ダッシュボード</h1>
      <div id="top_container">
        <div>
            <DataCard />


        </div>
      </div>
      <div id="bottom_container"></div>
    </template>
  </MainLayout>
</template>

<style lang="scss" scoped>
#top_container {
    padding: 1% 2%;
    border-radius: 4px;
    box-shadow: rgba(50, 50, 93, 0.25) 0px 2px 5px -1px, rgba(0, 0, 0, 0.3) 0px 1px 3px -1px;
  & > div {
    padding: 1% 0;

    &::-webkit-scrollbar {
      height: 8px;
    }

    &::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 10px;
    }

    &::-webkit-scrollbar-thumb {
      background: #888;
      border-radius: 10px;
    }

    &::-webkit-scrollbar-thumb:hover {
      background: #555;
    }

    display: flex;
    align-items: center;
    justify-content: space-between;
    overflow-x: scroll;
  }


}
#bottom_container {
}
</style>
