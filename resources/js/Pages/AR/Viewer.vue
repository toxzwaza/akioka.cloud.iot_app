<script setup>
import { onMounted } from "vue";

onMounted(() => {
  setTimeout(() => {
    const videoElement = document.querySelector("video");
    if (videoElement) {
      const videoHeight = videoElement.getBoundingClientRect().height;
      const screenHeight = window.innerHeight;
      const descriptionHeight = screenHeight - videoHeight;
      document.getElementById(
        "description"
      ).style.height = `${descriptionHeight}px`;
      console.log("Actual video height:", videoHeight);
      console.log("all inner height:", window.innerHeight);
      console.log("Description height:", descriptionHeight);
    } else {
      console.log("Video element not found.");
    }
  }, 3000);
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
  <span id="left_up_ar_icon">AR</span>

  <img id="bn_1" src="/bn_1.png" alt="" />

  <div id="description">
    <p class="text-gray-400 p-2">製品の説明や会社HPへのリンクを表示。</p>
    <p class="text-gray-400 p-2">アクセス数をカウントする。</p>
  </div>
</template>

<style lang="scss" >
body {
  background-image: url("ar_bg.png");
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
  width: 30vw;
  position: fixed;
  right: 2%;
  top: 2%;
  box-shadow: rgba(99, 99, 99, 0.2) 0px 2px 8px 0px;
}
</style>