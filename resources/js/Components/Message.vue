<script setup>
import { ref, onMounted } from "vue";

const isVisible = ref(true);

// 自動非表示機能（5秒後）
onMounted(() => {
  if (isVisible.value) {
    setTimeout(() => {
      isVisible.value = false;
    }, 5000);
  }
});

const closeMessage = () => {
  isVisible.value = false;
};
</script>

<template>
  <!-- ダイアログオーバーレイ -->
  <div 
    v-if="$page.props.flash.message && isVisible" 
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
  >
    <!-- 背景オーバーレイ -->
    <div 
      class="fixed inset-0 bg-black/30 backdrop-blur-sm transition-opacity"
      @click="closeMessage"
    ></div>
    
    <!-- ダイアログコンテンツ -->
    <div
      class="relative bg-white rounded-xl shadow-2xl border overflow-hidden w-full max-w-md transform transition-all"
      :class="{
        'border-gray-200': $page.props.flash.status === 'info',
        'border-red-200': $page.props.flash.status === 'error',
        'border-green-200': $page.props.flash.status === 'success',
      }"
      role="alert"
    >
      <!-- ヘッダー -->
      <div
        class="px-6 py-4 border-b"
        :class="{
          'bg-gradient-to-r from-gray-50 to-slate-50 border-gray-200': $page.props.flash.status === 'info',
          'bg-gradient-to-r from-red-50 to-rose-50 border-red-200': $page.props.flash.status === 'error',
          'bg-gradient-to-r from-green-50 to-emerald-50 border-green-200': $page.props.flash.status === 'success',
        }"
      >
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div
              class="rounded-full p-2"
              :class="{
                'bg-gray-100': $page.props.flash.status === 'info',
                'bg-red-100': $page.props.flash.status === 'error',
                'bg-green-100': $page.props.flash.status === 'success',
              }"
            >
              <i
                class="text-lg"
                :class="{
                  'fas fa-info-circle text-gray-600': $page.props.flash.status === 'info',
                  'fas fa-exclamation-triangle text-red-600': $page.props.flash.status === 'error',
                  'fas fa-check-circle text-green-600': $page.props.flash.status === 'success',
                }"
              ></i>
            </div>
            <div>
              <h3
                class="text-lg font-bold"
                :class="{
                  'text-gray-800': $page.props.flash.status === 'info',
                  'text-red-800': $page.props.flash.status === 'error',
                  'text-green-800': $page.props.flash.status === 'success',
                }"
              >
                {{
                  $page.props.flash.status == "info"
                    ? "お知らせ"
                    : $page.props.flash.status == "success"
                    ? "成功"
                    : "エラー"
                }}
              </h3>
            </div>
          </div>
          <button
            @click="closeMessage"
            class="p-1 rounded-lg hover:bg-gray-100 transition-colors"
          >
            <i class="fas fa-times text-gray-400 hover:text-gray-600"></i>
          </button>
        </div>
      </div>
      
      <!-- メッセージ本体 -->
      <div class="px-6 py-4">
        <p
          class="text-sm leading-relaxed"
          :class="{
            'text-gray-700': $page.props.flash.status === 'info',
            'text-red-700': $page.props.flash.status === 'error',
            'text-green-700': $page.props.flash.status === 'success',
          }"
        >
          {{ $page.props.flash.message }}
        </p>
      </div>
      
      <!-- アクションボタン -->
      <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
        <button
          @click="closeMessage"
          class="w-full px-4 py-2 bg-gradient-to-r from-gray-500 to-gray-600 hover:from-gray-600 hover:to-gray-700 text-white font-semibold rounded-lg transition-all duration-200 shadow-sm hover:shadow-md flex items-center justify-center gap-2"
        >
          <i class="fas fa-check"></i>
          確認
        </button>
      </div>
    </div>
  </div>
</template>
  