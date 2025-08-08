<template>
  <div class="p-6">
    <h1 class="text-2xl font-bold mb-4">現在のUID</h1>
    <p class="text-lg bg-gray-100 p-4 rounded border border-gray-300">
      {{ uid || '接続待機中...' }}
    </p>
    <p v-if="error" class="text-red-500 mt-2">接続エラー: {{ error }}</p>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'

const uid = ref(null)
const error = ref(null)
let socket = null

onMounted(() => {
  const wsUrl = 'ws://192.168.210.91:8765' // ← ラズパイのIPに変更

  socket = new WebSocket(wsUrl)

  socket.onmessage = (event) => {
    uid.value = event.data
    error.value = null
  }

  socket.onerror = (err) => {
    error.value = 'WebSocketエラー'
    console.error('WebSocket error:', err)
  }

  socket.onclose = () => {
    error.value = '接続が切断されました'
  }
})

onBeforeUnmount(() => {
  if (socket) {
    socket.close()
  }
})
</script>
