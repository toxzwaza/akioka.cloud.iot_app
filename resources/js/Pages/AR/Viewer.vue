<script setup>
import { onMounted, ref, reactive } from "vue";

const isLoading = reactive({
  status: true,
  msg: "準備中...",
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
  const messages = ["準備中...", "まもなく完了します..."];
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

  // ARターゲット検知時のイベントリスナー
  const scene = document.querySelector("a-scene");
  if (scene) {
    scene.addEventListener("targetFound", (event) => {
      console.log("🎯 ターゲットを検知しました！", event);
      // 検知時の位置情報を出力
      const target = event.target;
      console.log("ターゲットの位置:", target.object3D.position);
      console.log("ターゲットの回転:", target.object3D.rotation);
      console.log("ターゲットのスケール:", target.object3D.scale);

      // 画像要素の確認
      const image = target.querySelector("a-image");
      if (image) {
        console.log("画像の位置:", image.object3D.position);
        console.log("画像の回転:", image.object3D.rotation);
        console.log("画像のスケール:", image.object3D.scale);
        console.log("画像のソース:", image.getAttribute("src"));
        console.log("画像の要素:", image);
        console.log("画像のマテリアル:", image.getAttribute("material"));

        // テキスト要素の確認
        const text = target.querySelector("a-text");
        if (text) {
          console.log("テキストの位置:", text.object3D.position);
          console.log("テキストの回転:", text.object3D.rotation);
          console.log("テキストのスケール:", text.object3D.scale);
          console.log("テキストの内容:", text.getAttribute("value"));
          console.log("テキストの要素:", text);
          console.log("テキストのマテリアル:", text.getAttribute("material"));
        } else {
          console.log("テキスト要素が見つかりません");
        }

        // 画像の実際のサイズを確認
        const imgElement = document.querySelector("#my-image");
        if (imgElement) {
          console.log("画像の実際のサイズ:", {
            width: imgElement.naturalWidth,
            height: imgElement.naturalHeight,
          });
          console.log(
            "画像の読み込み状態:",
            imgElement.complete ? "完了" : "読み込み中"
          );
          console.log("画像のURL:", imgElement.src);
        }
      } else {
        console.log("画像要素が見つかりません");
      }
    });
    scene.addEventListener("targetLost", (event) => {
      console.log("❌ ターゲットを見失いました", event);
    });
    scene.addEventListener("arError", (error) => {
      console.error("ARエラーが発生しました:", error);
    });
  }
});
</script>

<template>
  <div v-once>
    <a-scene
      mindar-image="imageTargetSrc: /targets/targets.mind"
      color-space="sRGB"
      embedded
      renderer="colorManagement: true, physicallyCorrectLights"
      vr-mode-ui="enabled: false"
      device-orientation-permission-ui="enabled: false"
    >
      <a-assets>
        <!-- <a-asset-item id="3dmodel" src="/assets/haniwa.glb"></a-asset-item> -->
        <img id="my-image" src="/assets/haniwa.png" crossorigin="anonymous" />
      </a-assets>

      <a-camera position="0 0 0" look-controls="enabled: false"> </a-camera>

      <a-entity mindar-image-target="targetIndex: 0">
        <!-- デバッグ用の座標軸表示 -->
        <a-entity position="0 0 0">
          <!-- X軸（赤） -->
          <a-box position="0.5 0 0" scale="1 0.1 0.1" color="red"></a-box>
          <!-- Y軸（緑） -->
          <a-box position="0 0.5 0" scale="0.1 1 0.1" color="green"></a-box>
          <!-- Z軸（青） -->
          <a-box position="0 0 0.5" scale="0.1 0.1 1" color="blue"></a-box>
        </a-entity>

        <!-- <a-gltf-model
          src="#3dmodel"
          position="0 0 0"
          rotation="0 0 0"
          scale="0.1 0.1 0.1"
        ></a-gltf-model> -->
        <a-image
          src="#my-image"
          position="0 0 0"
          rotation="0 0 0"
          width="2"
          height="2"
          scale="1 1 1"
          material="shader: flat; transparent: true; opacity: 1"
        ></a-image>

        <!-- テキスト -->
        <a-text
          value="これは埴輪です"
          position="0 1.5 0.1"
          align="center"
          color="#FF0000"
          width="4"
          scale="1 1 1"
          material="shader: flat; transparent: true; opacity: 1"
        ></a-text>
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
.inner {
  transform: translateY(-44%) scale(0.6);
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
  width: 100% !important;
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
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}
</style>