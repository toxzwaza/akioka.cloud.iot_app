<script setup>
import { onMounted, reactive, ref } from "vue";
import { Link } from "@inertiajs/vue3";
import axios from "axios";
import StockLayout from "@/Layouts/StockLayout.vue";

const props = defineProps({
  order_request_id: Number,
});

const orderRequest = ref({});
const loading = ref(true);
const error = ref("");

const getStatusText = (acceptFlg, receiveFlg, initialOrderId, orderCompleteFlg) => {
  if (receiveFlg) return { text: "納品済", class: "bg-green-900 text-white" };
  if (initialOrderId && orderCompleteFlg) return { text: "発注済", class: "bg-blue-700 text-white" };
  if (initialOrderId && !orderCompleteFlg) return { text: "未発注", class: "bg-yellow-600 text-white" };
  if (acceptFlg === 0) return { text: "依頼済", class: "bg-blue-500 text-white" };
  if (acceptFlg === 1) return { text: "承認待ち", class: "bg-orange-500 text-white" };
  if (acceptFlg === 2) return { text: "承認済", class: "bg-green-500 text-white" };
  if (acceptFlg === 3) return { text: "却下", class: "bg-red-500 text-white" };
  if (acceptFlg === 4) return { text: "却下再依頼待ち", class: "bg-gray-500 text-white" };
  if (acceptFlg === 5) return { text: "確認中", class: "bg-purple-500 text-white" };
  return { text: "不明", class: "bg-gray-400 text-white" };
};

const formatDate = (dateString) => {
  if (!dateString) return "未設定";
  return new Date(dateString).toLocaleDateString("ja-JP");
};

const formatDateTime = (dateString) => {
  if (!dateString) return "未設定";
  return new Date(dateString).toLocaleString("ja-JP", {
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
    hour12: false,
  });
};

const getOrderRequestDetail = async () => {
  try {
    loading.value = true;
    const response = await axios.get(`/check_order_request/detail/${props.order_request_id}`);
    console.log(response.data)
    if (response.data.status) {
        
      orderRequest.value = response.data.order_request;
    } else {
      error.value = response.data.msg || "データの取得に失敗しました。";
    }
  } catch (err) {
    error.value = "サーバーエラーが発生しました。";
    console.error(err);
  } finally {
    loading.value = false;
  }
};

const handleIframeError = (event) => {
  console.warn("iframe読み込みエラー:", event);
  const iframe = event.target;
  const fallback = document.getElementById('iframe-fallback');
  if (iframe && fallback) {
    iframe.style.display = 'none';
    fallback.style.display = 'block';
  }
};

const getFilePreviewUrl = (filePath) => {
  if (!filePath) return '';
  
  // ファイル拡張子を取得
  const extension = filePath.split('.').pop().toLowerCase();
  
  // PDFファイルの場合はGoogle Docs Viewerを使用
  if (extension === 'pdf') {
    return `https://docs.google.com/viewer?url=${encodeURIComponent(filePath)}&embedded=true`;
  }
  
  // その他のファイルは直接表示
  return filePath;
};

onMounted(() => {
  getOrderRequestDetail();
});
</script>
<template>
  <StockLayout :title="'在庫管理システム - 発注依頼詳細'">
    <template #content>
      <!-- 戻るボタン -->
      <div class="mb-6">
        <Link
          :href="route('stock.check_order_request.home')"
          class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded inline-flex items-center"
        >
          <i class="fas fa-arrow-left mr-2"></i>
          一覧に戻る
        </Link>
      </div>

      <!-- ローディング表示 -->
      <div v-if="loading" class="flex justify-center items-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
        <span class="ml-3 text-gray-600">読み込み中...</span>
      </div>

      <!-- エラー表示 -->
      <div v-else-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        <strong class="font-bold">エラー:</strong>
        <span class="block sm:inline">{{ error }}</span>
      </div>

      <!-- 詳細情報表示 -->
      <div v-else class="bg-white shadow-lg rounded-lg overflow-hidden">
        <!-- ヘッダー -->
        <div class="bg-gray-600 text-white px-6 py-4">
          <h2 class="text-xl font-bold">発注依頼詳細情報</h2>
        </div>

        <div class="p-6">
          <!-- 基本情報セクション -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- 左側 -->
            <div>
              <h3 class="text-lg font-semibold mb-4 text-gray-800 border-b border-gray-200 pb-2">基本情報</h3>
              
              <div class="space-y-3">
                <div class="flex">
                  <span class="font-medium text-gray-600 w-32">依頼ID:</span>
                  <span class="text-gray-900">{{ orderRequest.id }}</span>
                </div>
                
                <div class="flex">
                  <span class="font-medium text-gray-600 w-32">依頼日時:</span>
                  <span class="text-gray-900">{{ formatDateTime(orderRequest.created_at) }}</span>
                </div>
                
                <div class="flex">
                  <span class="font-medium text-gray-600 w-32">依頼者:</span>
                  <span class="text-gray-900">{{ orderRequest.request_user_name }} ({{ orderRequest.process_name }})</span>
                </div>
                
                <div class="flex">
                  <span class="font-medium text-gray-600 w-32">発注者:</span>
                  <span class="text-gray-900">{{ orderRequest.order_user_name || '未設定' }}</span>
                </div>
                
                <div class="flex items-center">
                  <span class="font-medium text-gray-600 w-32">ステータス:</span>
                  <span 
                    :class="getStatusText(orderRequest.accept_flg, orderRequest.receive_flg, orderRequest.initial_order_id, orderRequest.order_complete_flg).class"
                    class="px-3 py-1 rounded-full text-sm font-medium"
                  >
                    {{ getStatusText(orderRequest.accept_flg, orderRequest.receive_flg, orderRequest.initial_order_id, orderRequest.order_complete_flg).text }}
                  </span>
                </div>
              </div>
            </div>

            <!-- 右側 -->
            <div>
              <h3 class="text-lg font-semibold mb-4 text-gray-800 border-b border-gray-200 pb-2">商品情報</h3>
              
              <div class="space-y-3">
                <div class="flex">
                  <span class="font-medium text-gray-600 w-32">依頼品:</span>
                  <span 
                    :class="orderRequest.new_stock_flg ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800'"
                    class="px-2 py-1 rounded text-xs font-medium"
                  >
                    {{ orderRequest.new_stock_flg ? '新規品' : '既存品' }}
                  </span>
                </div>
                
                <div class="flex">
                  <span class="font-medium text-gray-600 w-32">品名:</span>
                  <span class="text-gray-900">{{ orderRequest.stock_id ? orderRequest.name : orderRequest.order_request_name }}</span>
                </div>
                
                <div class="flex">
                  <span class="font-medium text-gray-600 w-32">品番:</span>
                  <span class="text-gray-900">{{ orderRequest.s_name || orderRequest.order_request_s_name || '未設定' }}</span>
                </div>
                
                <div class="flex">
                  <span class="font-medium text-gray-600 w-32">仕入先:</span>
                  <span class="text-gray-900">{{ orderRequest.supplier_name || '未設定' }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- 商品画像 -->
          <div v-if="orderRequest.img_path" class="mb-8">
            <h3 class="text-lg font-semibold mb-4 text-gray-800 border-b border-gray-200 pb-2">商品画像</h3>
            <div class="flex justify-center">
              <img 
                :src="orderRequest.img_path && orderRequest.img_path.includes('storage') 
                  ? 'https://akioka.cloud/' + orderRequest.img_path 
                  : orderRequest.img_path"
                alt="商品画像"
                class="max-w-md h-auto border border-gray-300 rounded-lg shadow-sm"
              />
            </div>
          </div>

          <!-- 数量・価格情報 -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- 左側 -->
            <div>
              <h3 class="text-lg font-semibold mb-4 text-gray-800 border-b border-gray-200 pb-2">数量情報</h3>
              
              <div class="space-y-3">
                <div class="flex">
                  <span class="font-medium text-gray-600 w-32">現在個数:</span>
                  <span class="text-gray-900">{{ orderRequest.now_quantity || '0' }}</span>
                </div>
                
                <div class="flex">
                  <span class="font-medium text-gray-600 w-32">発注点:</span>
                  <span class="text-gray-900">{{ orderRequest.reorder_point || '未設定' }}</span>
                </div>
                
                <div class="flex">
                  <span class="font-medium text-gray-600 w-32">発注数量:</span>
                  <span class="text-gray-900">{{ orderRequest.quantity }}</span>
                </div>
                
                <div class="flex">
                  <span class="font-medium text-gray-600 w-32">発注単位:</span>
                  <span class="text-gray-900">{{ orderRequest.unit || '未設定' }}</span>
                </div>
              </div>
            </div>

            <!-- 右側 -->
            <div>
              <h3 class="text-lg font-semibold mb-4 text-gray-800 border-b border-gray-200 pb-2">価格情報</h3>
              
              <div class="space-y-3">
                <div class="flex">
                  <span class="font-medium text-gray-600 w-32">単価:</span>
                  <span class="text-gray-900">¥{{ orderRequest.price ? Number(orderRequest.price).toLocaleString() : '未設定' }}</span>
                </div>
                
                <div class="flex">
                  <span class="font-medium text-gray-600 w-32">送料:</span>
                  <span class="text-gray-900">¥{{ orderRequest.postage ? Number(orderRequest.postage).toLocaleString() : '0' }}</span>
                </div>
                
                <div class="flex">
                  <span class="font-bold text-gray-600 w-32 text-lg">合計金額:</span>
                  <span class="text-gray-900 text-lg font-bold">¥{{ orderRequest.calc_price ? Number(orderRequest.calc_price).toLocaleString() : '未設定' }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- 日程情報 -->
          <div class="mb-8">
            <h3 class="text-lg font-semibold mb-4 text-gray-800 border-b border-gray-200 pb-2">日程情報</h3>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <div class="flex">
                <span class="font-medium text-gray-600 w-32">希望納期:</span>
                <span class="text-gray-900">{{ formatDate(orderRequest.desire_delivery_date) }}</span>
              </div>
              
              <div class="flex">
                <span class="font-medium text-gray-600 w-32">消化予定日:</span>
                <span class="text-gray-900">{{ formatDate(orderRequest.digest_date) }}</span>
              </div>
            </div>
          </div>

          <!-- 備考・説明 -->
          <div v-if="orderRequest.description || orderRequest.sub_description" class="mb-8">
            <h3 class="text-lg font-semibold mb-4 text-gray-800 border-b border-gray-200 pb-2">備考・説明</h3>
            
            <div class="space-y-4">
              <div v-if="orderRequest.description">
                <span class="font-medium text-gray-600 block mb-2">詳細説明:</span>
                <div class="bg-gray-50 p-4 rounded border">
                  <p class="text-gray-900 whitespace-pre-wrap">{{ orderRequest.description }}</p>
                </div>
              </div>
              
              <div v-if="orderRequest.sub_description">
                <span class="font-medium text-gray-600 block mb-2">補足説明:</span>
                <div class="bg-gray-50 p-4 rounded border">
                  <p class="text-gray-900 whitespace-pre-wrap">{{ orderRequest.sub_description }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- 関連ファイル -->
          <div v-if="orderRequest.file_path" class="mb-8">
            <h3 class="text-lg font-semibold mb-4 text-gray-800 border-b border-gray-200 pb-2">添付ファイル</h3>
            
            <div class="space-y-4">
              <div class="flex items-center justify-between mb-2">
                <span class="font-medium text-gray-600">ファイルプレビュー:</span>
                <a 
                  :href="orderRequest.file_path" 
                  target="_blank"
                  class="text-blue-600 hover:text-blue-800 underline inline-flex items-center text-sm"
                >
                  <i class="fas fa-external-link-alt mr-1"></i>
                  新しいタブで開く
                </a>
              </div>
              
              <div class="border border-gray-300 rounded-lg overflow-hidden bg-white">
                <iframe 
                  :src="getFilePreviewUrl(orderRequest.file_path)"
                  class="w-full h-96"
                  frameborder="0"
                  title="添付ファイル"
                  @error="handleIframeError"
                >
                  <p class="p-4 text-gray-500">
                    このブラウザではファイルプレビューがサポートされていません。
                    <a :href="orderRequest.file_path" target="_blank" class="text-blue-600 underline">
                      こちらをクリックしてファイルを開いてください。
                    </a>
                  </p>
                </iframe>
              </div>
              
              <!-- ファイルが読み込めない場合のフォールバック -->
              <div class="text-center p-4 bg-gray-50 rounded border" style="display: none;" id="iframe-fallback">
                <i class="fas fa-file-alt text-gray-400 text-4xl mb-2"></i>
                <p class="text-gray-600 mb-2">ファイルをプレビューできません</p>
                <a 
                  :href="orderRequest.file_path" 
                  target="_blank"
                  class="text-blue-600 hover:text-blue-800 underline inline-flex items-center"
                >
                  <i class="fas fa-download mr-2"></i>
                  ファイルをダウンロード
                </a>
              </div>
            </div>
          </div>

          <!-- 承認状況 -->
          <div v-if="orderRequest.order_request_approvals && orderRequest.order_request_approvals.length > 0" class="mb-8">
            <h3 class="text-lg font-semibold mb-4 text-gray-800 border-b border-gray-200 pb-2">承認状況</h3>
            
            <div class="space-y-4">
              <div 
                v-for="approval in orderRequest.order_request_approvals" 
                :key="approval.user_id"
                class="bg-gray-50 p-4 rounded border"
              >
                <div class="flex items-center justify-between mb-2">
                  <div class="flex items-center space-x-3">
                    <span class="font-medium text-gray-800">{{ approval.name }}</span>
                    <span 
                      :class="{
                        'bg-green-500 text-white': approval.status === 1,
                        'bg-red-500 text-white': approval.status === 2,
                        'bg-yellow-500 text-white': approval.status === 0,
                        'bg-gray-400 text-white': approval.status === null
                      }"
                      class="px-2 py-1 rounded text-xs font-medium"
                    >
                      {{ 
                        approval.status === 1 ? '承認' : 
                        approval.status === 2 ? '却下' : 
                        approval.status === 0 ? '承認待ち' : 
                        '未処理' 
                      }}
                    </span>
                    <span v-if="approval.final_flg" class="bg-purple-500 text-white px-2 py-1 rounded text-xs font-medium">
                      最終承認者
                    </span>
                  </div>
                  <div v-if="approval.updated_at" class="text-sm text-gray-500">
                    {{ formatDateTime(approval.updated_at) }}
                  </div>
                </div>
                
                <div v-if="approval.comment" class="mt-2">
                  <span class="font-medium text-gray-600 block mb-1">コメント:</span>
                  <p class="text-gray-900 text-sm bg-white p-2 rounded border">{{ approval.comment }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- 稟議書情報 -->
          <div v-if="orderRequest.document_id" class="mb-8">
            <h3 class="text-lg font-semibold mb-4 text-gray-800 border-b border-gray-200 pb-2">稟議書情報</h3>
            
            <div class="bg-gray-50 p-4 rounded border space-y-4">
              <div v-if="orderRequest.title">
                <span class="font-medium text-gray-600 block mb-2">タイトル:</span>
                <p class="text-gray-900">{{ orderRequest.title }}</p>
              </div>
              
              <div v-if="orderRequest.evalution_date">
                <span class="font-medium text-gray-600 block mb-2">評価日:</span>
                <p class="text-gray-900">{{ formatDate(orderRequest.evalution_date) }}</p>
              </div>
              
              <div v-if="orderRequest.content">
                <span class="font-medium text-gray-600 block mb-2">内容:</span>
                <div class="bg-white p-3 rounded border">
                  <p class="text-gray-900 whitespace-pre-wrap">{{ orderRequest.content }}</p>
                </div>
              </div>
              
              <div v-if="orderRequest.main_reason">
                <span class="font-medium text-gray-600 block mb-2">主な理由:</span>
                <p class="text-gray-900">{{ orderRequest.main_reason }}</p>
              </div>
              
              <div v-if="orderRequest.sub_reason">
                <span class="font-medium text-gray-600 block mb-2">副次的理由:</span>
                <p class="text-gray-900">{{ orderRequest.sub_reason }}</p>
              </div>
              
              <!-- 稟議書画像 -->
              <div v-if="orderRequest.document_images && orderRequest.document_images.length > 0">
                <span class="font-medium text-gray-600 block mb-2">稟議書画像:</span>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                  <div 
                    v-for="(image, index) in orderRequest.document_images" 
                    :key="index"
                    class="border border-gray-300 rounded-lg overflow-hidden"
                  >
                    <img 
                      :src="image" 
                      :alt="`稟議書画像 ${index + 1}`"
                      class="w-full h-auto cursor-pointer hover:opacity-80 transition-opacity"
                      @click="window.open(image, '_blank')"
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- デバイスメッセージ -->
          <div v-if="orderRequest.message" class="mb-8">
            <h3 class="text-lg font-semibold mb-4 text-gray-800 border-b border-gray-200 pb-2">デバイスメッセージ</h3>
            
            <div class="space-y-4">
              <div>
                <span class="font-medium text-gray-600 block mb-2">メッセージ:</span>
                <div class="bg-blue-50 p-4 rounded border border-blue-200">
                  <p class="text-gray-900">{{ orderRequest.message }}</p>
                </div>
              </div>
              
              <div v-if="orderRequest.answer">
                <span class="font-medium text-gray-600 block mb-2">回答:</span>
                <div class="bg-green-50 p-4 rounded border border-green-200">
                  <p class="text-gray-900">{{ orderRequest.answer }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </StockLayout>
</template>
<style lang="scss" scoped>
</style>
