<script setup>
import { onMounted, ref } from "vue";
import axios from "axios"


const props = defineProps({
  place_id: Number,
});

const data = ref(null)

const weatherData = ref(null)

const getData = () => {
    axios.get(route('getData', { place_id : props.place_id }))
    .then(res => {
        console.log(res.data)
        data.value = res.data
        if(data.value.temperature && data.value.humidity){
            data.value.wbgt = calculateWbgt(data.value.temperature, data.value.humidity)
        }
    })
    .catch(error => {
        console.log(error)
    })

}
const getWeather = () => {
    axios.get(route('getWeather'))
    .then(res => {
        console.log(res.data)
        weatherData.value = res.data
    })
}

const calculateWbgt = (temperature, humidity) => {
    return Math.round(0.725 * temperature + 0.0368 * humidity + 0.00364 * (temperature * humidity) * 10) / 10;
}

onMounted(() => {
  getData()
  getWeather()

  setInterval(() => {
    getData()
  }, 1000 * 60 * 10)
});
</script>
<template>
  <main id="main_container">
    <div id="meta_content" class="">
      <p v-if="data && data.place_name ">{{ data.place_name }}</p>
      <p v-if="data && data.created_at">データ取得時刻： {{ new Date(data.created_at).toLocaleTimeString('ja-JP', { hour: '2-digit', minute: '2-digit' }) }}</p>
    </div>

    <div id="top_content">
      <div class="content">
        <p class="title text-orange-500">温度</p>
        <p v-if="data && data.temperature" :class="{'val' : true, 'text-purple-600': data.temperature > 30 , 'text-purple-400': data.temperature > 25, 'text-green-500': data.temperature < 25 }">{{ data.temperature }}<span class="unit">℃</span></p>

        <div class="color_palette">
          <div class="bg-orange-500"></div>
          <div class="bg-orange-300"></div>
          <div class="bg-green-500"></div>
        </div>
      </div>
      <div class="content">
        <p class="title text-blue-600">湿度</p>
        <p v-if="data && data.humidity" :class="{'val' : true, 'text-purple-600': data.humidity > 70 , 'text-purple-400': data.humidity > 60, 'text-green-500': data.humidity < 60 }">{{ data.humidity }}<span class="unit">％</span></p>

        <div class="color_palette">
          <div class="bg-blue-500"></div>
          <div class="bg-blue-300"></div>
          <div class="bg-green-500"></div>
        </div>
      </div>
      <div class="content">
        <p class="title text-purple-600">Co2濃度</p>
        <p v-if="data && data.co2" :class="{'val' : true, 'text-purple-600': data.co2 > 5000 , 'text-purple-400': data.co2 > 1000, 'text-green-500': data.co2 < 1000 }">{{ data.co2 }}<span class="unit">PPM</span></p>
        <div class="color_palette">
          <div class="bg-purple-500"></div>
          <div class="bg-purple-300"></div>
          <div class="bg-green-500"></div>
        </div>
      </div>
      <div class="content ">
        <p class="title text-red-600">WBGT</p>
        <p v-if="data && data.wbgt" :class="{'val' : true, 'text-red-600': data.wbgt > 27 , 'text-red-400': data.wbgt > 18, 'text-green-500': data.wbgt < 18 }"> {{ data.wbgt }}</p>
        <div class="color_palette">
          <div class="bg-red-500"></div>
          <div class="bg-red-300"></div>
          <div class="bg-green-500"></div>
        </div>
      </div>
    </div>
    <div id="bottom_content">
      <div class="content">
        <p class="time_label">0 - 6</p>
        <div class="val" v-if="weatherData && weatherData.T00_06">{{ weatherData.T00_06 }}</div>
      </div>
      <div class="content">
        <p class="time_label">6 - 12</p>
        <div class="val" v-if="weatherData && weatherData.T06_12">{{ weatherData.T06_12 }}</div>
      </div>
      <div class="content">
        <p class="time_label">12 - 18</p>
        <div class="val" v-if="weatherData && weatherData.T12_18">{{ weatherData.T12_18 }}</div>
      </div>
      <div class="content">
        <p class="time_label">18 - 24</p>
        <div class="val" v-if="weatherData && weatherData.T18_24">{{ weatherData.T18_24 }}</div>
      </div>
    </div>
  </main>
</template>
<style scoped lang="scss">
#main_container {
  font-family: "Noto Sans JP", sans-serif;
  height: 100vh;
  width: 100vw;
  overflow: hidden;
  background-color: #272727;
  padding: 0 1%;
  & #meta_content {
    height: 10%;
    padding-top: 1%;
    display: flex;
    justify-content: space-around;
    align-items: center;

    & p {
      color: yellow;
      font-size: 4rem;
      font-weight: bold;
    }
  }

  & #top_content {
    height: 60%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;

    & .content {
      width: 48%;
      height: 46%;
      background-color: white;
      padding: 1%;
      position: relative;

      & .color_palette {
        position: absolute;
        right: 0;
        top: 0;
        height: 100%;
        width: 10%;

        display: flex;
        flex-direction: column;

        & > div {
          height: 33.33%;
        }
      }

      & .title {
        font-size: 3rem;
        font-weight: bold;
      }
      & .val {
        font-size: 6rem;
        font-weight: bold;
        text-align: center;

        & .unit {
          font-size: 3rem;
          display: inline-block;
          margin-left: 1rem;
          color: gray;
        }
      }
    }
  }

  & #bottom_content {
    height: 40%;
    display: flex;
    justify-content: space-between;
    padding: 1% 0;

    & .content {
      width: 22%;
      height: 70%;
      box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;
      border-radius: 8px;
      overflow: hidden;

      & .time_label {
        font-size: 3rem;
        text-align: center;
        color: white;
        background-color: blue;
        height: 30%;
        border-radius: 8px 8px 0 0;
      }

      & .val {
        font-size: 6rem;
        text-align: center;
        font-weight: bold;
        height: 70%;
        display: flex;
        justify-content: center;
        align-items: center;
        background-color: white;
      }
    }
  }
}
</style>
