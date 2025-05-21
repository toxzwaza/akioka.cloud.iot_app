<!-- resources/js/Pages/ArView.vue -->
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

      <a-camera position="0 0 0" look-controls="enabled: false"></a-camera>

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
</template>

<script setup>
// コンポーネントのスクリプトは不要です（A-Frameが自動実行するため）
</script>
