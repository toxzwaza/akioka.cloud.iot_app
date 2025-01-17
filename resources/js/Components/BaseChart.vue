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
    "#FF6384", // 赤
    "#36A2EB", // 青
    "#4BC0C0", // ターコイズ
    "#FFCD56", // 黄色
    "#9966FF", // 紫
    "#FF9F40", // オレンジ
    "#2ECC71", // 緑
    "#E74C3C", // 濃い赤
    "#3498DB", // 薄い青
    "#F1C40F"  // 濃い黄色
];

watch(() => props.data, (newData) => {
    if (newData && newData.length > 0) {
        chartData.datasets = newData.map((item, index) => ({
            label: item.place_name,
            data: item.data,
            backgroundColor: colors[index % colors.length],
            borderColor: colors[index % colors.length],
            tension: 0.1
        }));
    }
}, { immediate: true, deep: true });
</script>

<template>
    <div class="chart-container">
        <h3 class="title">{{ title }}</h3>
        <LineChart :chartData="chartData" />
    </div>
</template>

<style scoped>
.chart-container {
    height: 100%;
    width: 32%;
    padding: 1%;

}
.title {
    font-size: 1rem;
    color: gray;
    font-weight: bold;
}
</style>
