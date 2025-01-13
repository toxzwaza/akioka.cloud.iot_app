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
      top: 131px;
    }
    p.nakago {
      left: 70px;
      top: 308px;
    }
    p.shotto {
      left: 70px;
      top: 490px;
    }
    p.siage {
      left: 70px;
      top: 680px;
    }

    p.denkiro {
      right: 100px;
      top: 130px;
    }
    p.romae {
      right: 100px;
      top: 320px;
    }
    p.namagata {
      right: 100px;
      top: 520px;
    }
    p.sunasyori {
      right: 100px;
      top: 760px;
    }
    p.syukka {
      right: 100px;
      top: 920px;
    }
    p.jimusyo {
      right: 100px;
      top: 1080px;
    }

  }
}
</style>
