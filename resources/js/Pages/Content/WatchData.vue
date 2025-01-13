<script setup>
</script>
<template>
  <main>
    <!-- 背景工場内マップ -->
    <img src="/akioka_map.png" alt="" />

    <!-- 温度・湿度・CO２濃度表示 -->
    <div class="contents">
      <!-- 塗装 -->
      <p class="tosou">28℃/80%/700PPM</p>
      <p class="nakago">28℃/80%/700PPM</p>
      <p class="shotto">28℃/80%/700PPM</p>
      <p class="siage">28℃/80%/700PPM</p>

      <p class="denkiro">28℃/80%/700PPM</p>
      <p class="romae">28℃/80%/700PPM</p>
      <p class="namagata">28℃/80%/700PPM</p>
      <p class="sunasyori">28℃/80%/700PPM</p>
      <p class="syukka">28℃/80%/700PPM</p>
      <p class="jimusyo">28℃/80%/700PPM</p>
    </div>
  </main>
</template>
<style scoped lang="scss">
main {
  height: 100vh;
  width: 100vw;
  position: relative;

  img {
    height: 100%;
    width: 100%;
    object-fit: cover;
  }
  .contents {
    font-size: 32px;
    font-weight: bold;
    & p {
      position: absolute;
      z-index: 100;
    }
    p.tosou {
      left: 70px;
      top: 120px;
    }
    p.nakago {
      left: 70px;
      top: 290px;
    }
    p.shotto {
      left: 70px;
      top: 460px;
    }
    p.siage {
      left: 70px;
      top: 630px;
    }

    p.denkiro {
      right: 75px;
      top: 110px;
    }
    p.romae {
      right: 75px;
      top: 290px;
    }
    p.namagata {
      right: 75px;
      top: 480px;
    }
    p.sunasyori {
      right: 75px;
      top: 700px;
    }
    p.syukka {
      right: 75px;
      top: 860px;
    }
    p.jimusyo {
      right: 75px;
      top: 1000px;
    }

  }
}
</style>
