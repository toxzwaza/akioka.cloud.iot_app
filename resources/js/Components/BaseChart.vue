<script setup>
import { Chart, registerables } from "chart.js";
import { LineChart } from "vue-chart-3";
import { reactive, watch } from "vue";
const props = defineProps({
    title: String,
    data: Array
})

Chart.register(...registerables);

const chartData = reactive({
    labels: ["0時", "1時", "2時", "3時", "4時", "5時", "6時", "7時", "8時", "9時", "10時", "11時", "12時", "13時", "14時", "15時", "16時", "17時", "18時", "19時", "20時", "21時", "22時", "23時"],
    datasets: []
});

const colors = [
    "#6366f1", "#10b981", "#f59e0b", "#ef4444", "#8b5cf6",
    "#06b6d4", "#ec4899", "#14b8a6", "#f97316", "#3b82f6"
];

watch(() => props.data, (newData) => {
    if (newData && newData.length > 0) {
        chartData.datasets = newData.map((item, index) => ({
            label: item.place_name,
            data: item.data,
            backgroundColor: colors[index % colors.length] + "20",
            borderColor: colors[index % colors.length],
            borderWidth: 2,
            pointRadius: 0,
            tension: 0.3,
            fill: true,
        }));
    }
}, { immediate: true, deep: true });
</script>

<template>
    <div class="chart-container">
        <h3 class="text-sm font-semibold text-slate-500 mb-3">{{ title }}</h3>
        <div class="card p-4">
            <LineChart :chartData="chartData" />
        </div>
    </div>
</template>

<style scoped>
.chart-container {
    height: 100%;
    width: 32%;
    padding: 0.5%;
}
</style>
