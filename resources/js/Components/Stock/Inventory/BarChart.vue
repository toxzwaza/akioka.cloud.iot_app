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
  "#6366f1", // indigo
  "#10b981", // emerald
  "#f59e0b", // amber
  "#ef4444", // red
  "#8b5cf6", // violet
  "#06b6d4", // cyan
  "#ec4899", // pink
  "#14b8a6", // teal
  "#f97316", // orange
  "#3b82f6", // blue
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
  <div class="card p-4">
    <div class="chart-container">
      <BarChart :chartData="chartData" />
    </div>
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
