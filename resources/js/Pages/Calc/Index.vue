<script setup>
import MainLayout from "@/Layouts/Calc/MainLayout.vue";
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
  <MainLayout :url="'stock.home'" :title="'Inventory'" :login_user="login_user">
    <template #content>
      <Login v-if="!login_user" :users="users" :locations="locations" />

      <div
        v-if="login_user"
        class="card-hover p-8 text-center"
      >
        <div class="flex justify-center items-center flex-col">
          <div class="w-16 h-16 bg-primary-100 rounded-2xl flex items-center justify-center mb-4">
            <i class="fas fa-search text-2xl text-primary-600"></i>
          </div>
          <h3 class="text-xl font-bold text-slate-800 mb-2">Start Product Search</h3>
          <p class="text-sm text-slate-400 mb-6">Search and register products for inventory count</p>
          <Link
            :href="route('calc.search')"
            class="btn-primary btn-lg"
          >
            <i class="fas fa-search"></i>
            Go to Search
          </Link>
        </div>
      </div>
    </template>
  </MainLayout>
</template>
