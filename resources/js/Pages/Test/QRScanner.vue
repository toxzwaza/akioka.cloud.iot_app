<template>
  <div class="min-h-screen bg-gray-100 p-4">
    <div class="max-w-4xl mx-auto">
      <h1 class="text-3xl font-bold text-center mb-8 text-gray-800">
        QRコードスキャナーテスト
      </h1>
      
      <div class="bg-white rounded-lg shadow-lg p-6">
        <!-- カメラ表示エリア -->
        <div class="mb-6">
          <div class="relative bg-gray-200 rounded-lg overflow-hidden">
            <video
              ref="video"
              autoplay
              playsinline
              muted
              class="w-full h-64 object-cover"
            ></video>
            <canvas
              ref="canvas"
              class="hidden"
            ></canvas>
          </div>
        </div>

        <!-- QRコード読み取り結果 -->
        <div class="mb-6">
          <h2 class="text-xl font-semibold mb-4 text-gray-700">
            読み取り結果
          </h2>
          <div class="bg-gray-50 p-4 rounded-lg border">
            <p v-if="qrResult" class="text-lg font-mono text-green-600 break-all">
              {{ qrResult }}
            </p>
            <p v-else class="text-gray-500 italic">
              QRコードをカメラに向けてください...
            </p>
          </div>
        </div>

        <!-- コントロールボタン -->
        <div class="flex gap-4 justify-center">
          <button
            @click="startCamera"
            :disabled="isCameraActive"
            class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors"
          >
            カメラ開始
          </button>
          <button
            @click="stopCamera"
            :disabled="!isCameraActive"
            class="px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors"
          >
            カメラ停止
          </button>
          <button
            @click="clearResult"
            class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors"
          >
            結果クリア
          </button>
        </div>

        <!-- エラーメッセージ -->
        <div v-if="errorMessage" class="mt-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
          {{ errorMessage }}
        </div>

        <!-- ステータス表示 -->
        <div class="mt-4 text-center">
          <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                :class="isCameraActive ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'">
            <span class="w-2 h-2 rounded-full mr-2"
                  :class="isCameraActive ? 'bg-green-500' : 'bg-gray-500'"></span>
            {{ isCameraActive ? 'カメラ稼働中' : 'カメラ停止中' }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from "vue"
import jsQR from "jsqr"

const video = ref(null)
const canvas = ref(null)
const qrResult = ref("")
const isCameraActive = ref(false)
const errorMessage = ref("")
let stream = null
let animationFrame = null

const startCamera = async () => {
  try {
    errorMessage.value = ""
    
    // 既存のストリームを停止
    if (stream) {
      stopCamera()
    }

    // カメラアクセス
    stream = await navigator.mediaDevices.getUserMedia({ 
      video: { 
        facingMode: "environment",
        width: { ideal: 1280 },
        height: { ideal: 720 }
      } 
    })
    
    video.value.srcObject = stream
    isCameraActive.value = true
    
    // QRコード読み取り開始
    startQRScanning()
    
  } catch (error) {
    console.error("カメラアクセスエラー:", error)
    errorMessage.value = "カメラにアクセスできません。ブラウザの設定を確認してください。"
  }
}

const stopCamera = () => {
  if (stream) {
    stream.getTracks().forEach(track => track.stop())
    stream = null
  }
  
  if (animationFrame) {
    cancelAnimationFrame(animationFrame)
    animationFrame = null
  }
  
  if (video.value) {
    video.value.srcObject = null
  }
  
  isCameraActive.value = false
}

const clearResult = () => {
  qrResult.value = ""
}

const startQRScanning = () => {
  const context = canvas.value.getContext("2d")

  const tick = () => {
    if (video.value && video.value.readyState === video.value.HAVE_ENOUGH_DATA) {
      canvas.value.width = video.value.videoWidth
      canvas.value.height = video.value.videoHeight
      context.drawImage(video.value, 0, 0, canvas.value.width, canvas.value.height)
      
      const imageData = context.getImageData(0, 0, canvas.value.width, canvas.value.height)
      const code = jsQR(imageData.data, canvas.value.width, canvas.value.height)
      
      if (code) {
        qrResult.value = code.data
      }
    }
    
    if (isCameraActive.value) {
      animationFrame = requestAnimationFrame(tick)
    }
  }
  
  tick()
}

onMounted(() => {
  // 自動的にカメラを開始
  startCamera()
})

onUnmounted(() => {
  stopCamera()
})
</script>


