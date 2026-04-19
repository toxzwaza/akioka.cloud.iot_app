<script setup>
import MainLayout from "@/Layouts/Calc/MainLayout.vue";
import SearchInput from "@/Components/SearchInput.vue";

import { ref, reactive, onMounted } from "vue";
import { router, Link } from "@inertiajs/vue3";
import axios from "axios";
const props = defineProps({
  login_user: Object,
  suppliers: Array,
});

onMounted(() => {});
</script>
<template>
  <MainLayout :url="'stock.home'" :title="'New Inventory'" :login_user="login_user">
    <template #content>
      <SearchInput
        :formStatus="true"
        :suppliers="props.suppliers"
      ></SearchInput>
    </template>
  </MainLayout>
</template>
