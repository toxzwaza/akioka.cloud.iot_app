
<script setup>
// MindARはCDN経由で利用（次のセクションで説明）
</script>
<template>
  <div id="ar-container">
    <mindar-viewer
      image-target-src="/assets/empty.mind"
      :ui-scanning="true"
      :ui-loading="true"
      :ui-error="true"
    >
      <!-- 仮にダミーでマーカーIndex 0を用意しておく（後で追加） -->
      <mindar-image-target targetIndex="0">
        <a-entity position="0 0 0">
          <!-- 空のエンティティ（まだ何も表示しない） -->
        </a-entity>
      </mindar-image-target>
    </mindar-viewer>
  </div>
</template>


<style>
#ar-container {
  width: 100vw;
  height: 100vh;
  overflow: hidden;
}
</style>