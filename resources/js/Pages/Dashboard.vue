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
      console.log(latestData.value);
    })
    .catch((err) => {});
};
const getTempHumiCo2 = () => {
  axios
    .get(route('getTempHumiCo2'))
    .then((res) => {
      console.log(res.data);
      tempHumiCo2.value = res.data;
    })
    .catch((err) => {
      console.error('データの取得に失敗しました:', err);
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
            <div class="flex justify-between items-center mb-2">
                <h1 class="text-lg font-bold text-gray-400 mb-2">
                    ダッシュボード
                </h1>
                <button
                    @click="updatedata"
                    class="bg-green-500 hover:bg-white text-white hover:text-green-500 px-4 py-2 rounded font-bold text-sm border border-green-500 transition duration-200"
                >
                    データ更新
                </button>
            </div>

            <div id="top_container">
                <div>
                    <DataCard v-for="data in latestData" :key="data.id" :data="data"/>
                </div>
            </div>
            <div id="bottom_container">
                <div class="base_chart_container">
                    <div>
                        <BaseChart :title="'温度'" :data="tempHumiCo2.temperature"  />
                        <BaseChart :title="'湿度'" :data="tempHumiCo2.humidity" />
                        <BaseChart :title="'Co2濃度'" :data="tempHumiCo2.co2" />
                    </div>
                </div>
            </div>
        </template>
    </MainLayout>
</template>

<style lang="scss" scoped>
#top_container {
    background-color: #fff;
    padding: 1% 2%;
    border-radius: 4px;
    box-shadow: rgba(50, 50, 93, 0.25) 0px 2px 5px -1px,
        rgba(0, 0, 0, 0.3) 0px 1px 3px -1px;
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
    background-color: #fff;
    margin-top: 20px;
    padding: 1% 2%;
    border-radius: 4px;
    box-shadow: rgba(50, 50, 93, 0.25) 0px 2px 5px -1px,
        rgba(0, 0, 0, 0.3) 0px 1px 3px -1px;

    & .base_chart_container {
        & > div {
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 100%;
        }
    }
}
</style>
