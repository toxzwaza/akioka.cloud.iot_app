<script setup>
import { Chart, registerables } from "chart.js";
import { LineChart, BarChart } from "vue-chart-3";
import { reactive, watch } from "vue";
const props = defineProps({
  title: String,
  data: Array,
  inventory_operation_id: Number,
  average: Number
});

Chart.register(...registerables);

const chartData = reactive({
  labels: [],
  datasets: [],
});

const colors = [
  "#4BC0C0", // ターコイズ
  "#FFCD56", // 黄色
  "#FF6384", // 赤
  "#9966FF", // 紫
  "#FF9F40", // オレンジ
  "#2ECC71", // 緑
  "#E74C3C", // 濃い赤
  "#3498DB", // 薄い青
  "#36A2EB", // 青,
  "#F1C40F", // 濃い黄色
];

const currentMonth = new Date();
const months = [];
for (let i = 0; i < 12; i++) {
  const month = new Date(
    currentMonth.getFullYear(),
    currentMonth.getMonth() - i,
    1
  );
  months.push(
    month.toLocaleDateString("ja-JP", { year: "numeric", month: "2-digit" })
  );
}
chartData.labels = months.reverse();



watch(
  () => props.data,
  (newData) => {
    if (newData && newData.length === 12) {
      chartData.datasets = [
        {
          label: props.title,
          data: newData,
          backgroundColor: colors[props.inventory_operation_id],
          borderColor: colors[props.inventory_operation_id],
          tension: 0.1,
        },
      ];
    } else {
      console.error("データは過去12カ月間のデータである必要があります。");
    }
  },
  { immediate: true, deep: true }
);
</script>

<template>
  <div class="chart-container">
    <BarChart :chartData="chartData" />
  </div>
</template>

<style scoped lang="scss">
.chart-container {
  width: 100%;
  padding: 1%;
  & canvas{
    height: 100%;
    width: 100%;
    object-fit: cover;
  }
}
</style>
