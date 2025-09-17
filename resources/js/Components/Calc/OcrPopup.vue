<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import Tesseract from 'tesseract.js'

const emit = defineEmits(['close', 'ocrResult'])

const videoRef = ref(null)
const canvasRef = ref(null)
const result = ref('')
const isProcessing = ref(false)
let stream = null

onMounted(async () => {
  try {
    // カメラ起動
    stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" } })
    videoRef.value.srcObject = stream
    videoRef.value.play()

    // 自動スキャンは行わない（手動スキャンのみ）
  } catch (error) {
    console.error('カメラの起動に失敗しました:', error)
  }
})

onBeforeUnmount(() => {
  if (stream) {
    stream.getTracks().forEach(track => track.stop())
  }
})

async function runOcr() {
  if (isProcessing.value) return
  
  const video = videoRef.value
  const canvas = canvasRef.value
  
  if (!video || !canvas || video.videoWidth === 0) return
  
  isProcessing.value = true
  const ctx = canvas.getContext('2d')

  // 枠の座標（中央に横長）
  const rectW = video.videoWidth * 0.6
  const rectH = video.videoHeight * 0.15
  const x = (video.videoWidth - rectW) / 2
  const y = (video.videoHeight - rectH) / 2

  // 枠内をキャプチャ
  canvas.width = rectW
  canvas.height = rectH
  ctx.drawImage(video, x, y, rectW, rectH, 0, 0, rectW, rectH)

  try {
    // OCR処理
    const { data: { text } } = await Tesseract.recognize(canvas, 'jpn+eng')
    let cleanText = text.trim()
    
    // 図番のみを抽出（a-zA-Z0-9で構成される部分）
    const drawingNumberMatch = cleanText.match(/[a-zA-Z0-9]+/)
    if (drawingNumberMatch) {
      cleanText = drawingNumberMatch[0]
    } else {
      cleanText = ''
    }
    
    result.value = cleanText
    
    // コンソールに出力
    console.log('OCR結果（元）:', text.trim())
    console.log('OCR結果（抽出後）:', cleanText)
    
    // 自動送信は行わない（ボタンクリック時のみ送信）
  } catch (error) {
    console.error('OCR処理エラー:', error)
  } finally {
    isProcessing.value = false
  }
}

const closePopup = () => {
  emit('close')
}

const useResult = () => {
  if (result.value) {
    emit('ocrResult', result.value)
    emit('close')
  }
}

const scan = () => {
  result.value = ''
  runOcr()
}
</script>

<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">OCR文字認識</h2>
        <button 
          @click="closePopup"
          class="text-gray-500 hover:text-gray-700 text-2xl"
        >
          ×
        </button>
      </div>
      
      <div class="text-center">
        <div class="relative inline-block">
          <!-- カメラ映像 -->
          <video ref="videoRef" autoplay playsinline class="rounded-lg w-full max-w-md"></video>

          <!-- 社内図番ラベル（映像の中心に重ねる） -->
          <div class="absolute top-4 left-1/2 transform -translate-x-1/2  text-2xl font-bold text-gray-800 bg-white bg-opacity-90 px-4 py-2 rounded-lg shadow-lg whitespace-nowrap">
            スキャンボタンをおしてください。
          </div>

          <!-- 白枠 -->
          <div class="absolute border-4 border-white"
               :style="{
                 top: '40%',
                 left: '20%',
                 width: '60%',
                 height: '15%',
               }">
          </div>
        </div>

        <!-- OCR結果 -->
        <div class="mt-4">
          <p class="font-bold text-lg text-green-600">認識結果: {{ result }}</p>
          <div v-if="isProcessing" class="text-blue-500 mt-2">
            処理中...
          </div>
        </div>

        <!-- ボタン -->
        <div class="mt-6 flex justify-center space-x-4">
          <button 
            @click="scan"
            :disabled="isProcessing"
            class="bg-green-500 hover:bg-green-700 disabled:bg-gray-300 text-white font-bold py-2 px-4 rounded"
          >
            スキャン
          </button>
          <button 
            @click="useResult"
            :disabled="!result"
            class="bg-blue-500 hover:bg-blue-700 disabled:bg-gray-300 text-white font-bold py-2 px-4 rounded"
          >
            この結果を使用
          </button>
          <button 
            @click="closePopup"
            class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
          >
            キャンセル
          </button>
        </div>

        <!-- キャプチャ用Canvas（非表示） -->
        <canvas ref="canvasRef" class="hidden"></canvas>
      </div>
    </div>
  </div>
</template>
