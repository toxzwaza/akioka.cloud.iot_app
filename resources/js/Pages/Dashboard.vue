<script setup>
import MainLayout from "@/Layouts/MainLayout.vue";
import DataCard from "@/Components/DataCard.vue";
import BaseChart from "@/Components/BaseChart.vue";
import axios from "axios";
import { ref, onMounted } from "vue";
const latestData = ref([]);
const tempHumiCo2 = ref([]);

const updatedata = () => {
  getLatestdata();
  getTempHumiCo2();
}

const getLatestdata = () => {
  axios
    .get("/getLatestData")
    .then((res) => {
      latestData.value = res.data;
    })
    .catch((err) => {});
};
const getTempHumiCo2 = () => {
  axios
    .get(route('getTempHumiCo2'))
    .then((res) => {
      tempHumiCo2.value = res.data;
    })
    .catch((err) => {
      console.error('Data fetch failed:', err);
    });
};

onMounted(() => {
  getLatestdata();
  getTempHumiCo2();
});
</script>

<template>
    <MainLayout :title="'Dashboard'">
        <template #content>
            <div class="page-header">
                <div>
                    <h1 class="section-title">Dashboard</h1>
                    <p class="section-subtitle">Environmental monitoring</p>
                </div>
                <button @click="updatedata" class="btn-success text-sm">
                    <i class="fas fa-sync-alt"></i>
                    Refresh
                </button>
            </div>

            <!-- Sensor cards -->
            <div class="card p-5 mb-6">
                <div class="flex items-center gap-4 overflow-x-auto pb-2 custom-scroll">
                    <DataCard v-for="data in latestData" :key="data.id" :data="data"/>
                </div>
            </div>

            <!-- Charts -->
            <div class="card p-5">
                <h2 class="text-sm font-semibold text-slate-500 mb-4">Trends (24h)</h2>
                <div class="flex gap-4">
                    <BaseChart :title="'Temperature'" :data="tempHumiCo2.temperature" />
                    <BaseChart :title="'Humidity'" :data="tempHumiCo2.humidity" />
                    <BaseChart :title="'CO2'" :data="tempHumiCo2.co2" />
                </div>
            </div>
        </template>
    </MainLayout>
</template>

<style lang="scss" scoped>
.custom-scroll {
    &::-webkit-scrollbar {
        height: 6px;
    }
    &::-webkit-scrollbar-track {
        background: transparent;
    }
    &::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }
}
</style>
