<script setup>
import MainLayout from "@/Layouts/Calc/MainLayout.vue";
// import LineMessage from "@/Components/LineMessage.vue";
import Login from "@/Components/Calc/Login.vue";

import { ref, reactive, onMounted } from "vue";
import { Link } from "@inertiajs/vue3";
import axios from "axios";

defineProps({
  login_user: Object,
  users: Array,
  locations: Array,
});

onMounted(() => {});
</script>
<template>
  <MainLayout :url="'calc.home'" :title="'棚卸し登録'" :login_user="login_user">
    <template #content>
      <!-- ログインフォーム -->
      <Login v-if="!login_user" :users="users" :locations="locations" />


      <!-- 検索ページへのリダイレクト案内 -->
      <div
        v-if="login_user"
        class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-8 text-center mb-6 hover:shadow-md transition-shadow"
      >
        <div class="flex justify-center items-center flex-col text-blue-700">
          <div class="bg-blue-100 rounded-full p-4 mb-4">
            <i class="fas fa-search text-3xl text-blue-600"></i>
          </div>
          <h3 class="text-xl font-bold mb-2">製品検索を開始</h3>
          <p class="text-sm text-gray-600 mb-4">棚卸対象の製品を検索して登録を開始します</p>
          <Link
            :href="route('calc.search')"
            class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-semibold rounded-lg transition-all duration-200 shadow-sm hover:shadow-md"
          >
            <i class="fas fa-search"></i>
            製品検索ページへ
          </Link>
        </div>
      </div>

      <!-- <LineMessage /> -->
    </template>
  </MainLayout>
</template>



<style>
</style>