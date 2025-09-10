<script setup>
</script>
<template>
  <div class="min-h-screen bg-gradient-to-br from-red-200 to-red-300 flex items-center justify-center px-4">
    <div class="max-w-3xl w-full bg-white rounded-2xl shadow-2xl p-8 text-center">
      <!-- エラーアイコン -->
      <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
        <i class="fas fa-exclamation-triangle text-red-600 text-3xl"></i>
      </div>
      
      <!-- エラータイトル -->
      <h1 class="text-5xl font-bold text-gray-900 mb-8">
        端末ID未登録エラー
      </h1>
      
      <!-- エラーメッセージ -->
      <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
        <p class="text-red-800 text-3xl leading-relaxed">
          端末IDが登録されていません。<br>
          管理者へ連絡してください。
        </p>
      </div>
      
      
      
      <!-- フッター -->
      <div class="mt-8 pt-6 border-t border-gray-200">
        <p class="text-xs text-gray-500">
          在庫管理システム - 端末認証エラー
        </p>
      </div>
    </div>
  </div>
</template>



<style scoped>
/* カスタムスタイル */
.shadow-2xl {
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}
</style>
