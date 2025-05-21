<script setup>
import { onMounted, ref, reactive } from "vue";

const isLoading = reactive({
    status: true,
    msg: '準備中...'

});

onMounted(() => {
  setTimeout(() => {
    const videoElement = document.querySelector("video");
    if (videoElement) {
      const videoHeight = videoElement.getBoundingClientRect().height;
      const screenHeight = window.innerHeight;
      const descriptionHeight = screenHeight - videoHeight;
      document.getElementById("description").style.height = `${
        200 + descriptionHeight
      }px`;
      console.log("Actual video height:", videoHeight);
      console.log("all inner height:", window.innerHeight);
      console.log("Description height:", descriptionHeight);
    } else {
      console.log("Video element not found.");
    }
  }, 4000);

  // 2秒ごとにisLoading.msgを変更
  const messages = ['準備中...', 'まもなく完了します...'];
  let messageIndex = 0;
  const messageInterval = setInterval(() => {
    isLoading.msg = messages[messageIndex];
    messageIndex = (messageIndex + 1) % messages.length;
  }, 2000);

  // 5秒後にローディングを非表示
  setTimeout(() => {
    clearInterval(messageInterval);
    isLoading.status = false;
  }, 5000);
});
</script>

<template>
  <div v-once>
    <a-scene
      mindar-image="imageTargetSrc: /marker.mind"
      color-space="sRGB"
      embedded
      renderer="colorManagement: true, physicallyCorrectLights"
      vr-mode-ui="enabled: false"
      device-orientation-permission-ui="enabled: false"
    >
      <a-assets>
        <a-asset-item id="3dmodel" src="/model.glb"></a-asset-item>
      </a-assets>

      <a-camera position="0 0 0" look-controls="enabled: false"> </a-camera>

      <a-entity mindar-image-target="targetIndex: 0">
        <a-gltf-model
          src="#3dmodel"
          position="0 0 0"
          rotation="0 0 0"
          scale="0.1 0.1 0.1"
        ></a-gltf-model>
      </a-entity>
    </a-scene>
  </div>

  <!-- ローディング表示 -->
  <div class="loading-overlay" :class="{ 'fade-out': !isLoading.status }">
    <div class="loading-content">
      <div class="loading-spinner"></div>
      <p class="loading-text">{{ isLoading.msg }}</p>
    </div>
  </div>

  <span id="left_up_ar_icon">AR</span>

  <a href="https://akioka1966.co.jp/"
    ><img id="bn_1" src="/bn_1.png" alt=""
  /></a>

  <div id="description">
    <p class="text-gray-400 p-2">製品の説明や会社HPへのリンクを表示。</p>
    <p class="text-gray-400 p-2">アクセス数をカウントする。</p>
  </div>
</template>

<style lang="scss" >
body {
  background-image: url("/ar_bg.png");
}
.mindar-ui-loading {
  display: none !important;
  & .loader {
    display: none !important;
  }
}

.a-enter-vr-button {
  display: none !important;
}

.a-loader-title {
  display: none !important;
}

video {
  height: 80vh !important;
  object-fit: cover;
}

#left_up_ar_icon {
  position: fixed;
  top: 2%;
  left: 2%;
  display: block;
  background-color: red;
  color: white;
  padding: 0.6rem;
  font-weight: bold;
  border-radius: 3px;
  opacity: 0.8;
  font-size: 0.7rem;
  animation: blink 3s infinite;
}

@keyframes blink {
  0%,
  100% {
    opacity: 0.8;
  }
  50% {
    opacity: 0.2;
  }
}

#description {
  position: fixed;
  bottom: 2%;
  left: 50%;
  transform: translateX(-50%);
  z-index: 100;
  padding: 1rem;

  width: 90vw;
  margin: 0 auto;
  //   height: 20vh;
  background-color: white;
  box-shadow: rgba(99, 99, 99, 0.2) 0px 2px 8px 0px;
  border-radius: 3px;
}

#bn_1 {
  width: 20vw;
  position: fixed;
  right: 2%;
  top: 2%;
  box-shadow: rgba(99, 99, 99, 0.2) 0px 2px 8px 0px;
}

.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.8);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
  transition: opacity 1s ease-in-out;

  &.fade-out {
    opacity: 0;
    pointer-events: none;
  }

  .loading-content {
    text-align: center;
  }

  .loading-spinner {
    width: 50px;
    height: 50px;
    border: 5px solid #f3f3f3;
    border-top: 5px solid #3498db;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 20px;
  }

  .loading-text {
    color: white;
    font-size: 1.2rem;
    font-weight: bold;
  }
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>