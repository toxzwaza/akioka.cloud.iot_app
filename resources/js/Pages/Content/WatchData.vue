<script setup>
</script>
<template>
  <main>
    <!-- 背景工場内マップ -->
    <img src="/akioka_map.png" alt="" />

    <!-- 温度・湿度・CO２濃度表示 -->
    <div class="contents">
      <!-- 塗装 -->
      <p class="tosou">28℃ 80% 700</p>
      <p class="nakago">28℃ 80% 700</p>
      <p class="shotto">28℃ 80% 700</p>
      <p class="siage">28℃ 80% 700</p>

      <p class="denkiro">28℃ 80% 700</p>
      <p class="romae">28℃ 80% 700</p>
      <p class="namagata">28℃ 80% 700</p>
      <p class="sunasyori">28℃ 80% 700</p>
      <p class="syukka">28℃ 80% 700</p>
      <p class="jimusyo">28℃ 80% 700</p>
    </div>
  </main>
</template>
<style scoped lang="scss">
main {
  font-family: 'IPAexGothic','Noto Sans CJK JP', 'Arial', sans-serif;
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
      left: 62px;
      top: 120px;
    }
    p.nakago {
      left: 62px;
      top: 290px;
    }
    p.shotto {
      left: 62px;
      top: 460px;
    }
    p.siage {
      left: 62px;
      top: 630px;
    }

    p.denkiro {
      right: 67px;
      top: 110px;
    }
    p.romae {
      right: 67px;
      top: 290px;
    }
    p.namagata {
      right: 67px;
      top: 480px;
    }
    p.sunasyori {
      right: 67px;
      top: 700px;
    }
    p.syukka {
      right: 67px;
      top: 860px;
    }
    p.jimusyo {
      right: 67px;
      top: 1000px;
    }

  }
}
</style>
