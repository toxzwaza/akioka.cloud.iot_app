<script setup>
import AcceptLayout from "@/Layouts/AcceptLayout.vue";
import { onMounted, ref, reactive } from "vue";
import { getImgPath } from "@/Helper/method";
import ApprovalDocument from "@/Components/Accept/ApprovalDocument.vue";

const props = defineProps({
  user: Object,
  order_requests: Array,
  process_stats: Array,
});
const description_order_request = reactive({
  order_request: null,
  approval_document: {
    document_id: null,
    process_name: null,
    user_name: null,
    evalution_date: null, //評価日
    desire_delivery_date: null, //希望日
    supplier_name: null,
    price: null,
    quantity: null,
    calc_price: null,
    name: null,
    s_name: null,
    document_id: null,
    title: null,
    content: null,
    main_reason: null,
    sub_reason: null,
  },
  viewerUrl: "",
  comment: {
    order_request_id: null,
    placeholder: "",
    msg: "",
  },
});

// ローディング状態管理
const loading = reactive({
  isLoading: false,
  currentAction: '', // 'accept' or 'reject'
  message: ''
});

// 部署詳細モーダル状態管理
const departmentModal = reactive({
  isOpen: false,
  selectedDepartment: null,
  departmentItems: [],
  sortField: null,
  sortDirection: 'asc'
});

// 一括選択機能
const bulkSelection = reactive({
  selectedIds: [],
  isAllSelected: false
});

// 一括処理モーダル
const bulkModal = reactive({
  isOpen: false,
  action: '', // 'accept' or 'reject'
  currentIndex: 0,
  items: [],
  comments: {}
});

// const comment = reactive({
//   order_request_id: null,
//   placeholder: "",
//   msg: "",
// });

const save_comment = () => {
  const order_request = props.order_requests.find(
    (order_request) =>
      order_request.id === description_order_request.comment.order_request_id
  );
  order_request.comment = description_order_request.comment.msg;

  console.log(props.order_requests);
};

const openDescription = (order_request) => {
  console.log(order_request);

  description_order_request.order_request = order_request;

  description_order_request.comment.order_request_id = order_request.id;
  description_order_request.comment.msg = order_request.comment || "";
  description_order_request.comment.placeholder = `${order_request.name} - ${order_request.s_name} のコメントを入力してください。`;

  if (order_request.file_path) {
    console.log("file_path登録:", order_request.file_path);

    const filePath = order_request.file_path.startsWith("/storage/")
      ? order_request.file_path
      : `/storage/${order_request.file_path}`;

    description_order_request.viewerUrl = `/pdfjs/web/main_viewer.html?file=${filePath}`;
  }
  if (order_request.document_id) {
    description_order_request.approval_document.document_id =
      order_request.document_id;
    description_order_request.approval_document.process_name =
      order_request.request_user_process_name;
    description_order_request.approval_document.user_name =
      order_request.request_user_name;
    description_order_request.approval_document.evalution_date =
      order_request.evalution_date;
    description_order_request.approval_document.desire_delivery_date =
      order_request.desire_delivery_date;
    description_order_request.approval_document.supplier_name =
      order_request.supplier_name;
    description_order_request.approval_document.price = order_request.price;
    description_order_request.approval_document.quantity =
      order_request.quantity;
    description_order_request.approval_document.calc_price =
      order_request.calc_price;
    description_order_request.approval_document.name = order_request.name;
    description_order_request.approval_document.s_name = order_request.s_name;
    description_order_request.approval_document.title = order_request.title;
    description_order_request.approval_document.content = order_request.content;
    description_order_request.approval_document.main_reason =
      order_request.main_reason;
    description_order_request.approval_document.sub_reason =
      order_request.sub_reason;
  } else {
    description_order_request.approval_document.document_id = null;
    description_order_request.approval_document.process_name = null;
    description_order_request.approval_document.user_name = null;
    description_order_request.approval_document.evalution_date = null;
    description_order_request.approval_document.desire_delivery_date = null;
    description_order_request.approval_document.supplier_name = null;
    description_order_request.approval_document.price = null;
    description_order_request.approval_document.quantity = null;
    description_order_request.approval_document.calc_price = null;
    description_order_request.approval_document.name = null;
    description_order_request.approval_document.s_name = null;
    description_order_request.approval_document.title = null;
    description_order_request.approval_document.content = null;
    description_order_request.approval_document.main_reason = null;
    description_order_request.approval_document.sub_reason = null;
  }
};
// 行のスタイルを判定する関数
const getRowStyle = (order_request) => {
  // accept_flg が 6（差し戻し状態）の場合
  if (order_request.accept_flg === 6) {
    // 現在のユーザーの承認状況を確認
    const currentUserApproval = order_request.order_request_approvals?.find(
      approval => approval.user_id === props.user.id
    );
    
    if (currentUserApproval) {
      // 現在のユーザーの承認ステータスが0の場合（回答待ち）はオレンジ色背景
      if (currentUserApproval.status === 0) {
        return {
          isDisabled: false,
          isWaitingResponse: true,
          rowClass: 'bg-orange-50 hover:bg-orange-100 transition-colors duration-200 border-l-4 border-orange-400',
          textClass: 'text-gray-900'
        };
      }
      // 現在のユーザーの承認ステータスがnullの場合はグレーアウト
      else if (currentUserApproval.status === 2) {
        return {
          isDisabled: true,
          isWaitingResponse: false,
          rowClass: 'bg-gray-100 hover:bg-gray-200 transition-colors duration-200',
          textClass: 'text-gray-500'
        };
      }
    }
  }
  
  return {
    isDisabled: false,
    isWaitingResponse: false,
    rowClass: 'hover:bg-gray-50 transition-colors duration-200',
    textClass: 'text-gray-900'
  };
};

const sendAccept = (order_request_approval_id, action, skipCommentCheck = false) => {
  // すでにローディング中の場合は処理を停止
  if (loading.isLoading) {
    return;
  }

  let status;
  let msg = "";

  const order_request_approval = props.order_requests.find(
    (order_request) =>
      order_request.order_request_approval_id === order_request_approval_id
  );

  if (order_request_approval_id) {
    switch (action) {
      case "accept": //承認
        // 承認の場合は確認なしで即実行
        if (!confirm(`${order_request_approval.name} を承認しますか？`)) {
          return;
        }
        msg = "承認が完了しました。";
        status = 1;
        loading.message = "承認処理中...";
        break;
      case "reject": //非承認
        // 却下時はコメント必須（モーダル経由の場合はスキップ）
        if (!skipCommentCheck && !order_request_approval.comment) {
          alert("却下の場合は、詳細ボタンからコメントを追加してください。");
          return;
        }
        msg = "却下しました。";
        status = 2;
        loading.message = "却下処理中...";
        break;
    }

    // ローディング開始
    loading.isLoading = true;
    loading.currentAction = action;

    axios
      .put(route("accept.order-request.update"), {
        order_request_approval_id: order_request_approval_id,
        status: status,
        comment: order_request_approval.comment,
      })
      .then((res) => {
        console.log(res.data);
        // 成功時は自動でリロード
        window.location.reload();
      })
      .catch((error) => {
        console.error('Error:', error);
        // ローディング終了
        loading.isLoading = false;
        loading.currentAction = '';
        loading.message = '';
        
        alert('処理中にエラーが発生しました。もう一度お試しください。');
      });
  }
};

// 部署詳細表示
const showDepartmentDetails = async (departmentStat) => {
  try {
    // ローディング開始
    loading.isLoading = true;
    loading.message = "データを取得中...";

    // APIから承認済み物品一覧を取得
    const response = await axios.get(route("accept.department-approved-items"), {
      params: {
        user_id: props.user.id,
        process_id: departmentStat.process_id
      }
    });

    if (response.data.status) {
      console.log(response.data.items);

      departmentModal.selectedDepartment = departmentStat;
      departmentModal.departmentItems = response.data.items;
      departmentModal.isOpen = true;
    } else {
      alert('データの取得に失敗しました。');
    }
  } catch (error) {
    console.error('Error fetching department items:', error);
    alert('データの取得中にエラーが発生しました。');
  } finally {
    // ローディング終了
    loading.isLoading = false;
    loading.message = '';
  }
};

// 部署詳細モーダルを閉じる
const closeDepartmentModal = () => {
  departmentModal.isOpen = false;
  departmentModal.selectedDepartment = null;
  departmentModal.departmentItems = [];
  departmentModal.sortField = null;
  departmentModal.sortDirection = 'asc';
};

// ソート機能
const sortItems = (field) => {
  if (departmentModal.sortField === field) {
    // 同じフィールドをクリックした場合は昇順・降順を切り替え
    departmentModal.sortDirection = departmentModal.sortDirection === 'asc' ? 'desc' : 'asc';
  } else {
    // 新しいフィールドをクリックした場合は昇順で開始
    departmentModal.sortField = field;
    departmentModal.sortDirection = 'asc';
  }
  
  // アイテムをソート
  departmentModal.departmentItems.sort((a, b) => {
    let aValue = a[field] || '';
    let bValue = b[field] || '';
    
    // 文字列として比較
    if (typeof aValue === 'string') {
      aValue = aValue.toLowerCase();
    }
    if (typeof bValue === 'string') {
      bValue = bValue.toLowerCase();
    }
    
    if (departmentModal.sortDirection === 'asc') {
      return aValue < bValue ? -1 : aValue > bValue ? 1 : 0;
    } else {
      return aValue > bValue ? -1 : aValue < bValue ? 1 : 0;
    }
  });
};

// ソートアイコンを取得
const getSortIcon = (field) => {
  if (departmentModal.sortField !== field) {
    return 'fas fa-sort text-gray-400';
  }
  return departmentModal.sortDirection === 'asc' ? 'fas fa-sort-up text-blue-500' : 'fas fa-sort-down text-blue-500';
};

// チェックボックス: 全選択/解除
const toggleAllSelection = () => {
  if (bulkSelection.isAllSelected) {
    bulkSelection.selectedIds = [];
    bulkSelection.isAllSelected = false;
  } else {
    bulkSelection.selectedIds = props.order_requests
      .filter(req => !getRowStyle(req).isDisabled)
      .map(req => req.order_request_approval_id);
    bulkSelection.isAllSelected = true;
  }
};

// チェックボックス: 個別選択/解除
const toggleSelection = (order_request_approval_id) => {
  const index = bulkSelection.selectedIds.indexOf(order_request_approval_id);
  if (index > -1) {
    bulkSelection.selectedIds.splice(index, 1);
  } else {
    bulkSelection.selectedIds.push(order_request_approval_id);
  }
  
  // 全選択状態を更新
  const selectableCount = props.order_requests.filter(req => !getRowStyle(req).isDisabled).length;
  bulkSelection.isAllSelected = bulkSelection.selectedIds.length === selectableCount;
};

// 一括承認を開始
const startBulkAccept = () => {
  if (bulkSelection.selectedIds.length === 0) {
    alert('承認する項目を選択してください。');
    return;
  }
  
  // 選択されたアイテムを取得
  bulkModal.items = props.order_requests.filter(req => 
    bulkSelection.selectedIds.includes(req.order_request_approval_id)
  );
  
  // すでに入力されているコメントを取得
  bulkModal.comments = {};
  bulkModal.items.forEach(item => {
    bulkModal.comments[item.order_request_approval_id] = item.comment || '';
  });
  
  bulkModal.action = 'accept';
  bulkModal.currentIndex = 0;
  bulkModal.isOpen = true;
};

// 一括却下を開始
const startBulkReject = () => {
  if (bulkSelection.selectedIds.length === 0) {
    alert('却下する項目を選択してください。');
    return;
  }
  
  // 選択されたアイテムを取得
  bulkModal.items = props.order_requests.filter(req => 
    bulkSelection.selectedIds.includes(req.order_request_approval_id)
  );
  
  // すでに入力されているコメントを取得
  bulkModal.comments = {};
  bulkModal.items.forEach(item => {
    bulkModal.comments[item.order_request_approval_id] = item.comment || '';
  });
  
  bulkModal.action = 'reject';
  bulkModal.currentIndex = 0;
  bulkModal.isOpen = true;
};

// 個別承認を開始
const startSingleAccept = (order_request) => {
  // 無効な行の場合は処理しない
  if (getRowStyle(order_request).isDisabled) {
    return;
  }
  
  // ローディング中の場合は処理しない
  if (loading.isLoading) {
    return;
  }
  
  // 1件だけを配列に入れる
  bulkModal.items = [order_request];
  
  // すでに入力されているコメントを取得
  bulkModal.comments = {};
  bulkModal.comments[order_request.order_request_approval_id] = order_request.comment || '';
  
  bulkModal.action = 'accept';
  bulkModal.currentIndex = 0;
  bulkModal.isOpen = true;
};

// 個別却下を開始
const startSingleReject = (order_request) => {
  // 無効な行の場合は処理しない
  if (getRowStyle(order_request).isDisabled) {
    return;
  }
  
  // ローディング中の場合は処理しない
  if (loading.isLoading) {
    return;
  }
  
  // 1件だけを配列に入れる
  bulkModal.items = [order_request];
  
  // すでに入力されているコメントを取得
  bulkModal.comments = {};
  bulkModal.comments[order_request.order_request_approval_id] = order_request.comment || '';
  
  bulkModal.action = 'reject';
  bulkModal.currentIndex = 0;
  bulkModal.isOpen = true;
};

// モーダル: 次へ
const bulkModalNext = () => {
  if (bulkModal.currentIndex < bulkModal.items.length - 1) {
    bulkModal.currentIndex++;
  }
};

// モーダル: 前へ
const bulkModalPrev = () => {
  if (bulkModal.currentIndex > 0) {
    bulkModal.currentIndex--;
  }
};

// モーダル: 閉じる
const closeBulkModal = () => {
  bulkModal.isOpen = false;
  bulkModal.action = '';
  bulkModal.currentIndex = 0;
  bulkModal.items = [];
  bulkModal.comments = {};
};

// 一括処理を実行
const executeBulkAction = async () => {
  console.log('一括処理開始', {
    action: bulkModal.action,
    itemsCount: bulkModal.items.length,
    comments: bulkModal.comments
  });

  // 却下の場合、すべてのコメントが入力されているか確認
  if (bulkModal.action === 'reject') {
    const missingComments = bulkModal.items.filter(item => 
      !bulkModal.comments[item.order_request_approval_id]?.trim()
    );
    
    if (missingComments.length > 0) {
      console.error('コメント未入力の項目あり', missingComments);
      alert('すべての項目にコメントを入力してください。');
      return;
    }
  }
  
  // 確認メッセージ（1件の場合は単数形、複数件の場合は複数形）
  const itemName = bulkModal.items.length === 1 
    ? bulkModal.items[0].name 
    : `${bulkModal.items.length}件`;
  const confirmMsg = bulkModal.action === 'accept' 
    ? bulkModal.items.length === 1
      ? `${itemName} を承認しますか？`
      : `選択した${bulkModal.items.length}件を承認しますか？`
    : bulkModal.items.length === 1
      ? `${itemName} を却下しますか？`
      : `選択した${bulkModal.items.length}件を却下しますか？`;
  
  if (!confirm(confirmMsg)) {
    console.log('ユーザーがキャンセル');
    return;
  }
  
  // ローディング開始
  loading.isLoading = true;
  loading.currentAction = bulkModal.action;
  loading.message = bulkModal.action === 'accept' ? '一括承認処理中...' : '一括却下処理中...';
  
  const status = bulkModal.action === 'accept' ? 1 : 2;
  
  // 元のコメントを更新
  bulkModal.items.forEach(item => {
    const orderRequest = props.order_requests.find(
      req => req.order_request_approval_id === item.order_request_approval_id
    );
    if (orderRequest) {
      orderRequest.comment = bulkModal.comments[item.order_request_approval_id];
    }
  });
  
  try {
    // 配列データを作成
    const items = bulkModal.items.map(item => ({
      order_request_approval_id: item.order_request_approval_id,
      status: status,
      comment: bulkModal.comments[item.order_request_approval_id] || '',
    }));
    
    console.log('一括APIリクエスト送信', {
      itemsCount: items.length,
      items: items
    });
    
    // 一括処理APIを呼び出し
    const response = await axios.put(route("accept.order-request.bulk-update"), {
      items: items
    });
    
    console.log('一括APIレスポンス', response.data);
    
    // レスポンスを確認
    if (response.data.status) {
      console.log('一括処理成功、リロード実行');
      // 成功時は自動でリロード
      window.location.reload();
    } else {
      throw new Error(response.data.msg || '処理に失敗しました。');
    }
  } catch (error) {
    console.error('一括処理エラー:', error);
    console.error('エラー詳細:', error.response?.data);
    loading.isLoading = false;
    loading.currentAction = '';
    loading.message = '';
    
    const errorMsg = error.response?.data?.msg || error.message || '処理中にエラーが発生しました。';
    alert(errorMsg);
  }
};

onMounted(() => {
  console.log(props.order_requests);
  console.log(props.process_stats);
});
</script>
<template>
  <AcceptLayout :title="'承認画面'">
    <template #content>
      <div
        v-if="description_order_request.comment.order_request_id"
        id="modal_cover"
      ></div>
      
      <!-- ヘッダーセクション -->
      <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white py-6 sm:py-12 mb-4 sm:mb-8">
        <div class="container mx-auto px-4">
          <div class="text-center">
            <div class="inline-flex items-center justify-center w-12 h-12 sm:w-16 sm:h-16 bg-white bg-opacity-20 rounded-full mb-3 sm:mb-4">
              <i class="fas fa-user-check text-lg sm:text-2xl"></i>
            </div>
            <h1 class="text-2xl sm:text-4xl font-bold mb-2">承認システム</h1>
            <p class="text-base sm:text-xl opacity-90 mb-2 sm:mb-4">承認者：{{ props.user.name }}</p>
            <p class="text-sm sm:text-lg opacity-80">以下の発注依頼について承認をお願いします</p>
          </div>
        </div>
      </div>

      <!-- 統計情報セクション -->
      <div class="mx-auto px-2 sm:px-4 mb-4 sm:mb-8">
        <div class="flex gap-3 sm:gap-6 overflow-x-auto pb-4" style="scrollbar-width: thin; scrollbar-color: #cbd5e1 #f1f5f9;">
          <!-- 全体合計カード -->
          <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden hover:shadow-xl transition-all duration-300 hover:transform hover:-translate-y-1 flex-shrink-0 w-64 sm:w-80">
            <!-- ヘッダー -->
            <div class="bg-gradient-to-r from-emerald-500 to-teal-600 px-4 sm:px-6 py-3 sm:py-4">
              <div class="flex items-center justify-between">
                <div class="flex items-center">
                  <div class="w-8 h-8 sm:w-10 sm:h-10 bg-white bg-opacity-20 rounded-full flex items-center justify-center mr-2 sm:mr-3">
                    <i class="fas fa-calculator text-white text-sm sm:text-lg"></i>
                  </div>
                  <div>
                    <h3 class="text-sm sm:text-lg font-bold text-white">全体合計</h3>
                    <p class="text-emerald-100 text-xs sm:text-sm">全部署統計</p>
                  </div>
                </div>
                <div class="text-right">
                  <div class="text-lg sm:text-2xl font-bold text-white">{{ props.process_stats.reduce((sum, stat) => sum + stat.order_request_count, 0) }}</div>
                  <div class="text-emerald-100 text-xs">総件数</div>
                </div>
              </div>
            </div>
            
            <!-- コンテンツ -->
            <div class="p-4 sm:p-6">
              <div class="space-y-3 sm:space-y-4">
                <!-- 合計金額 -->
                <div class="flex items-center justify-between p-3 sm:p-4 bg-gradient-to-r from-emerald-50 to-green-50 rounded-lg border border-emerald-100">
                  <div class="flex items-center">
                    <div class="w-6 h-6 sm:w-8 sm:h-8 bg-emerald-100 rounded-full flex items-center justify-center mr-2 sm:mr-3">
                      <i class="fas fa-yen-sign text-emerald-600 text-xs sm:text-sm"></i>
                    </div>
                    <div>
                      <div class="text-xs sm:text-sm font-medium text-gray-600">総合計金額</div>
                      <div class="text-lg sm:text-xl font-bold text-emerald-600">
                        ¥{{ props.process_stats.reduce((sum, stat) => sum + parseInt(stat.total_calc_price), 0).toLocaleString() }}
                      </div>
                    </div>
                  </div>
                </div>
                
                <!-- 平均金額 -->
                <div class="flex items-center justify-between p-3 sm:p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg border border-blue-100">
                  <div class="flex items-center">
                    <div class="w-6 h-6 sm:w-8 sm:h-8 bg-blue-100 rounded-full flex items-center justify-center mr-2 sm:mr-3">
                      <i class="fas fa-chart-line text-blue-600 text-xs sm:text-sm"></i>
                    </div>
                    <div>
                      <div class="text-xs sm:text-sm font-medium text-gray-600">平均金額</div>
                      <div class="text-base sm:text-lg font-semibold text-blue-600">
                        ¥{{ Math.round(props.process_stats.reduce((sum, stat) => sum + parseInt(stat.total_calc_price), 0) / props.process_stats.reduce((sum, stat) => sum + stat.order_request_count, 0)).toLocaleString() }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- フッター -->
              <div class="mt-4 pt-4 border-t border-gray-100">
                <div class="flex items-center justify-center">
                  <div class="flex items-center text-sm text-gray-500">
                    <i class="fas fa-check-circle text-emerald-500 mr-2"></i>
                    <span>全部署承認済み</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- 部署別カード -->
          <div
            v-for="stat in props.process_stats"
            :key="stat.process_id"
            class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden hover:shadow-xl transition-all duration-300 hover:transform hover:-translate-y-1 flex-shrink-0 w-64 sm:w-80"
          >
            <!-- ヘッダー -->
            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 px-4 sm:px-6 py-3 sm:py-4">
              <div class="flex items-center justify-between">
                <div class="flex items-center">
                  <div class="w-8 h-8 sm:w-10 sm:h-10 bg-white bg-opacity-20 rounded-full flex items-center justify-center mr-2 sm:mr-3">
                    <i class="fas fa-building text-white text-sm sm:text-lg"></i>
                  </div>
                  <div>
                    <h3 class="text-sm sm:text-lg font-bold text-white">{{ stat.process_name }}</h3>
                    <p class="text-indigo-100 text-xs sm:text-sm">部署統計</p>
                  </div>
                </div>
                <div class="text-right">
                  <div class="text-lg sm:text-2xl font-bold text-white">{{ stat.order_request_count }}</div>
                  <div class="text-indigo-100 text-xs">件数</div>
                </div>
              </div>
            </div>
            
            <!-- コンテンツ -->
            <div class="p-4 sm:p-6">
              <div class="space-y-3 sm:space-y-4">
                <!-- 合計金額 -->
                <div class="flex items-center justify-between p-3 sm:p-4 bg-gradient-to-r from-emerald-50 to-green-50 rounded-lg border border-emerald-100">
                  <div class="flex items-center">
                    <div class="w-6 h-6 sm:w-8 sm:h-8 bg-emerald-100 rounded-full flex items-center justify-center mr-2 sm:mr-3">
                      <i class="fas fa-yen-sign text-emerald-600 text-xs sm:text-sm"></i>
                    </div>
                    <div>
                      <div class="text-xs sm:text-sm font-medium text-gray-600">合計金額</div>
                      <div class="text-lg sm:text-xl font-bold text-emerald-600">
                        ¥{{ parseInt(stat.total_calc_price).toLocaleString() }}
                      </div>
                    </div>
                  </div>
                </div>
                
                <!-- 平均金額 -->
                <div class="flex items-center justify-between p-3 sm:p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg border border-blue-100">
                  <div class="flex items-center">
                    <div class="w-6 h-6 sm:w-8 sm:h-8 bg-blue-100 rounded-full flex items-center justify-center mr-2 sm:mr-3">
                      <i class="fas fa-chart-line text-blue-600 text-xs sm:text-sm"></i>
                    </div>
                    <div>
                      <div class="text-xs sm:text-sm font-medium text-gray-600">平均金額</div>
                      <div class="text-base sm:text-lg font-semibold text-blue-600">
                        ¥{{ Math.round(parseInt(stat.total_calc_price) / stat.order_request_count).toLocaleString() }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- フッター -->
              <div class="mt-3 sm:mt-4 pt-3 sm:pt-4 border-t border-gray-100">
                <div class="flex items-center justify-center">
                  <button
                    @click="showDepartmentDetails(stat)"
                    class="w-full inline-flex items-center justify-center px-3 sm:px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs sm:text-sm font-medium rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                  >
                    <i class="fas fa-list mr-1 sm:mr-2"></i>
                    <span class="hidden sm:inline">詳細を見る</span>
                    <span class="sm:hidden">詳細</span>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- インストラクションと一括操作 -->
      <div class="mx-auto px-2 sm:px-4 mb-4 sm:mb-8">
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border-l-4 border-blue-500 p-3 sm:p-4 rounded-r-lg shadow-sm">
          <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between space-y-3 lg:space-y-0">
            <!-- 操作説明 -->
            <div class="space-y-2 flex-1">
              <div class="flex items-center">
                <i class="fas fa-check-circle text-emerald-500 mr-2 sm:mr-3 text-sm sm:text-base"></i>
                <p class="text-gray-800 text-sm sm:text-base font-medium">
                  <strong>承認：</strong>テーブルの<span class="inline-flex items-center px-2 py-1 bg-emerald-100 text-emerald-700 rounded text-xs mx-1">承認</span>ボタンをクリックするだけで完了
                </p>
              </div>
              <div class="flex items-center">
                <i class="fas fa-times-circle text-red-500 mr-2 sm:mr-3 text-sm sm:text-base"></i>
                <p class="text-gray-800 text-sm sm:text-base font-medium">
                  <strong>却下：</strong><i class="fas fa-search text-blue-600 mx-1"></i>詳細ボタンからコメントを追加してから却下してください
                </p>
              </div>
            </div>
            
            <!-- 一括操作ボタン -->
            <div class="flex flex-col sm:flex-row gap-2 lg:ml-4">
              <div class="bg-white px-3 py-2 rounded-lg border border-gray-200 flex items-center justify-center">
                <i class="fas fa-check-square text-indigo-500 mr-2"></i>
                <span class="text-sm font-semibold text-gray-700">
                  選択中: <span class="text-indigo-600">{{ bulkSelection.selectedIds.length }}</span>件
                </span>
              </div>
              <button
                @click="startBulkAccept"
                :disabled="bulkSelection.selectedIds.length === 0 || loading.isLoading"
                class="inline-flex items-center justify-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white text-sm font-medium rounded-lg transition-colors duration-200 shadow-sm hover:shadow-md"
              >
                <i class="fas fa-check-double mr-2"></i>
                まとめて承認
              </button>
              <button
                @click="startBulkReject"
                :disabled="bulkSelection.selectedIds.length === 0 || loading.isLoading"
                class="inline-flex items-center justify-center px-4 py-2 bg-red-600 hover:bg-red-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white text-sm font-medium rounded-lg transition-colors duration-200 shadow-sm hover:shadow-md"
              >
                <i class="fas fa-times-circle mr-2"></i>
                まとめて却下
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- メインコンテンツ -->
      <section class="mx-auto px-2 sm:px-4 pb-16 sm:pb-20">
        <!-- テーブルコンテナ -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
          <div class="overflow-x-auto">
            <table
              id="order_request_table"
              class="w-full"
            >
              <thead>
                <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                  <th class="px-2 sm:px-4 py-3 sm:py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider border-r border-gray-200 whitespace-nowrap">
                    <input
                      type="checkbox"
                      :checked="bulkSelection.isAllSelected"
                      @change="toggleAllSelection"
                      class="w-4 h-4 text-indigo-600 bg-gray-100 border-gray-300 rounded focus:ring-indigo-500 focus:ring-2 cursor-pointer"
                    />
                  </th>
                  <th class="px-2 sm:px-6 py-3 sm:py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-r border-gray-200 whitespace-nowrap">
                    <div class="flex items-center">
                      <i class="fas fa-image mr-1 sm:mr-2 text-gray-400 text-xs sm:text-sm"></i>
                      <span class="hidden sm:inline">緊急度</span>
                    </div>
                  </th>
                  <th class="px-2 sm:px-6 py-3 sm:py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-r border-gray-200 whitespace-nowrap">
                    <div class="flex items-center">
                      <i class="fas fa-image mr-1 sm:mr-2 text-gray-400 text-xs sm:text-sm"></i>
                      <span class="hidden sm:inline">画像</span>
                    </div>
                  </th>
                  <th class="px-2 sm:px-6 py-3 sm:py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-r border-gray-200 whitespace-nowrap">
                    <div class="flex items-center">
                      <i class="fas fa-tags mr-1 sm:mr-2 text-gray-400 text-xs sm:text-sm"></i>
                      <span class="hidden sm:inline">分類</span>
                    </div>
                  </th>
                  <th class="px-2 sm:px-6 py-3 sm:py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-r border-gray-200 whitespace-nowrap">
                    <div class="flex items-center">
                      <i class="fas fa-calendar-alt mr-1 sm:mr-2 text-gray-400 text-xs sm:text-sm"></i>
                      <span class="hidden sm:inline">最終発注日</span>
                      <span class="sm:hidden">発注日</span>
                    </div>
                  </th>
                  <th class="px-2 sm:px-6 py-3 sm:py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-r border-gray-200 whitespace-nowrap">
                    <span class="hidden sm:inline">品名</span>
                    <span class="sm:hidden">品名</span>
                  </th>
                  <th class="px-2 sm:px-6 py-3 sm:py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-r border-gray-200 whitespace-nowrap">
                    <span class="hidden sm:inline">品番</span>
                    <span class="sm:hidden">品番</span>
                  </th>
                  <th class="px-2 sm:px-6 py-3 sm:py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-r border-gray-200 whitespace-nowrap">
                    <span class="hidden sm:inline">必要数量</span>
                    <span class="sm:hidden">数量</span>
                  </th>
                  <th class="px-2 sm:px-6 py-3 sm:py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-r border-gray-200 whitespace-nowrap hidden sm:table-cell">
                    <span>現在数量</span>
                  </th>
                  <th class="px-2 sm:px-6 py-3 sm:py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-r border-gray-200 whitespace-nowrap">
                    <div class="flex items-center">
                      <i class="fas fa-yen-sign mr-1 sm:mr-2 text-gray-400 text-xs sm:text-sm"></i>
                      <span class="hidden sm:inline">単価</span>
                    </div>
                  </th>
                  <th class="px-2 sm:px-6 py-3 sm:py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-r border-gray-200 whitespace-nowrap">
                    <div class="flex items-center">
                      <i class="fas fa-calculator mr-1 sm:mr-2 text-gray-400 text-xs sm:text-sm"></i>
                      <span class="hidden sm:inline">金額</span>
                    </div>
                  </th>
                  <th class="px-2 sm:px-6 py-3 sm:py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-r border-gray-200 whitespace-nowrap hidden sm:table-cell">
                    <div class="flex items-center">
                      <i class="fas fa-building mr-1 sm:mr-2 text-gray-400 text-xs sm:text-sm"></i>
                      発注先
                    </div>
                  </th>
                  <th class="px-2 sm:px-6 py-3 sm:py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-r border-gray-200 whitespace-nowrap hidden sm:table-cell">
                    <div class="flex items-center">
                      <i class="fas fa-building mr-1 sm:mr-2 text-gray-400 text-xs sm:text-sm"></i>
                      リードタイム
                    </div>
                  </th>
                  <th class="px-2 sm:px-6 py-3 sm:py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-r border-gray-200 whitespace-nowrap hidden md:table-cell">
                    <div class="flex items-center">
                      <i class="fas fa-user mr-1 sm:mr-2 text-gray-400 text-xs sm:text-sm"></i>
                      依頼者
                    </div>
                  </th>
                  <th class="px-2 sm:px-6 py-3 sm:py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-r border-gray-200 whitespace-nowrap hidden md:table-cell">
                    <div class="flex items-center">
                      <i class="fas fa-user-tie mr-1 sm:mr-2 text-gray-400 text-xs sm:text-sm"></i>
                      担当者
                    </div>
                  </th>
                  <th class="px-2 sm:px-6 py-3 sm:py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-r border-gray-200 whitespace-nowrap hidden lg:table-cell">
                    <div class="flex items-center">
                      <i class="fas fa-calendar-plus mr-1 sm:mr-2 text-gray-400 text-xs sm:text-sm"></i>
                      依頼日
                    </div>
                  </th>
                  <th class="px-2 sm:px-6 py-3 sm:py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-r border-gray-200 whitespace-nowrap hidden lg:table-cell">
                    <div class="flex items-center">
                      <i class="fas fa-calendar-check mr-1 sm:mr-2 text-gray-400 text-xs sm:text-sm"></i>
                      消化予定日
                    </div>
                  </th>
                  <th class="px-2 sm:px-6 py-3 sm:py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-r border-gray-200 whitespace-nowrap hidden lg:table-cell">
                    <div class="flex items-center">
                      <i class="fas fa-calendar-day mr-1 sm:mr-2 text-gray-400 text-xs sm:text-sm"></i>
                      希望納期
                    </div>
                  </th>
                  <th class="px-2 sm:px-6 py-3 sm:py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-r border-gray-200 whitespace-nowrap hidden xl:table-cell">
                    <div class="flex items-center">
                      <i class="fas fa-comment mr-1 sm:mr-2 text-gray-400 text-xs sm:text-sm"></i>
                      依頼者備考
                    </div>
                  </th>
                  <th class="px-2 sm:px-6 py-3 sm:py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-r border-gray-200 whitespace-nowrap hidden xl:table-cell">
                    <div class="flex items-center">
                      <i class="fas fa-comment-dots mr-1 sm:mr-2 text-gray-400 text-xs sm:text-sm"></i>
                      発注者備考
                    </div>
                  </th>
                  <th class="px-2 sm:px-6 py-3 sm:py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-r border-gray-200 whitespace-nowrap hidden lg:table-cell">
                    <div class="flex items-center">
                      <i class="fas fa-file-alt mr-1 sm:mr-2 text-gray-400 text-xs sm:text-sm"></i>
                      添付ファイル
                    </div>
                  </th>
                  <th class="px-2 sm:px-6 py-3 sm:py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider border-r border-gray-200 whitespace-nowrap">
                    <div class="flex items-center justify-center">
                      <i class="fas fa-search mr-1 sm:mr-2 text-gray-400 text-xs sm:text-sm"></i>
                      <span class="hidden sm:inline">詳細確認</span>
                    </div>
                  </th>
                  <th class="px-2 sm:px-6 py-3 sm:py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider whitespace-nowrap">
                    <div class="flex items-center justify-center">
                      <i class="fas fa-stamp mr-1 sm:mr-2 text-gray-400 text-xs sm:text-sm"></i>
                      <span class="hidden sm:inline">承認登録</span>
                    </div>
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <tr
                  v-for="order_request in order_requests"
                  :key="order_request.id"
                  :class="getRowStyle(order_request).rowClass"
                >
                  <td class="px-2 sm:px-4 py-3 sm:py-4 whitespace-nowrap text-center">
                    <input
                      type="checkbox"
                      :checked="bulkSelection.selectedIds.includes(order_request.order_request_approval_id)"
                      @change="toggleSelection(order_request.order_request_approval_id)"
                      :disabled="getRowStyle(order_request).isDisabled"
                      class="w-4 h-4 text-indigo-600 bg-gray-100 border-gray-300 rounded focus:ring-indigo-500 focus:ring-2 cursor-pointer disabled:cursor-not-allowed disabled:opacity-50"
                    />
                  </td>
                  <td class="px-2 sm:px-6 py-3 sm:py-4 whitespace-nowrap">
                    <div class="flex items-center justify-center">
                      <span v-if="order_request.emergency_level == 2" class="bg-pink-100 text-pink-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded-full dark:bg-pink-900 dark:text-pink-300">緊急</span>
                      <span v-else-if="order_request.emergency_level == 1" class="bg-orange-100 text-orange-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded-full dark:bg-orange-900 dark:text-orange-300">期限間近</span>
                      <span v-else class="bg-gray-100 text-gray-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded-full dark:bg-gray-900 dark:text-gray-300">期限内</span>
                    </div>
                  </td>
                  <td class="px-2 sm:px-6 py-3 sm:py-4 whitespace-nowrap">
                    <div class="flex items-center justify-center">
                      <img 
                        :src="getImgPath(order_request.img_path)" 
                        alt="商品画像"
                        class="h-12 w-12 sm:h-16 sm:w-16 object-cover rounded-lg shadow-sm border border-gray-200" 
                      />
                    </div>
                  </td>
                  <td class="px-2 sm:px-6 py-3 sm:py-4 whitespace-nowrap">
                    <span
                      v-if="order_request.new_stock_flg"
                      class="inline-flex items-center px-2 sm:px-3 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 border border-emerald-200"
                    >
                      <i class="fas fa-plus-circle mr-1 text-xs"></i>
                      <span class="hidden sm:inline">新規品</span>
                      <span class="sm:hidden">新</span>
                    </span>
                    <span
                      v-else
                      class="inline-flex items-center px-2 sm:px-3 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800 border border-amber-200"
                    >
                      <i class="fas fa-box mr-1 text-xs"></i>
                      <span class="hidden sm:inline">既存品</span>
                      <span class="sm:hidden">既</span>
                    </span>
                  </td>
                  <td class="px-2 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-sm text-gray-600">
                    <div class="flex items-center">
                      <i class="fas fa-calendar-alt mr-1 sm:mr-2 text-gray-400 text-xs"></i>
                      <span class="text-xs sm:text-sm">{{
                        order_request.digest_date
                          ? new Date(order_request.last_order_date)
                              .toLocaleDateString("ja-JP", {
                                year: "numeric",
                                month: "2-digit",
                                day: "2-digit",
                              })
                              .replace(/\//g, "/")
                          : "-"
                      }}</span>
                    </div>
                  </td>
                  <td class="px-2 sm:px-6 py-3 sm:py-4 text-sm text-gray-900">
                    <div class="font-medium truncate max-w-xs" :title="order_request.name">
                    <span v-if="order_request.name.length > 20">
                      {{ order_request.name.substring(0, 20) + "..." }}
                    </span>
                    <span v-else>
                      {{ order_request.name }}
                    </span>
                    </div>
                  </td>
                  <td class="px-2 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-sm text-gray-600">
                    <code class="bg-gray-100 px-1 sm:px-2 py-1 rounded text-xs">{{ order_request.s_name ?? "-" }}</code>
                  </td>
                  <td class="px-2 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-sm text-gray-900">
                    <div class="flex items-center">
                      <i class="fas fa-cube mr-1 sm:mr-2 text-blue-400 text-xs"></i>
                      <span class="font-semibold text-xs sm:text-sm">{{
                      `${order_request.quantity ?? ""}${
                        order_request.unit ?? ""
                      }`
                      }}</span>
                    </div>
                  </td>
                  <td class="px-2 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-sm text-gray-600 hidden sm:table-cell">
                    <div class="flex items-center">
                      <i class="fas fa-boxes mr-1 sm:mr-2 text-gray-400 text-xs"></i>
                      <span class="text-xs sm:text-sm">{{
                        `${order_request.now_quantity ?? "-"}${
                          order_request.now_quantity_unit ?? "-"
                        }`
                      }}</span>
                    </div>
                  </td>

                  <td class="px-2 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-sm text-gray-900">
                    <div class="flex items-center font-semibold">
                      <i class="fas fa-yen-sign mr-1 sm:mr-2 text-green-500 text-xs"></i>
                      <span class="text-xs sm:text-sm">{{ order_request.price?.toLocaleString() }}</span>
                    </div>
                  </td>
                  <td class="px-2 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-sm text-gray-900">
                    <div class="flex items-center font-bold text-blue-600">
                      <i class="fas fa-calculator mr-1 sm:mr-2 text-xs"></i>
                      <span class="text-xs sm:text-sm">¥{{ order_request.calc_price?.toLocaleString() }}</span>
                    </div>
                  </td>
                  <td class="px-2 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-sm text-gray-900 hidden sm:table-cell">
                    <div class="flex items-center">
                      <i class="fas fa-building mr-1 sm:mr-2 text-gray-400 text-xs"></i>
                      <span class="text-xs sm:text-sm truncate max-w-xs" :title="order_request.supplier_name">{{ order_request.supplier_name }}</span>
                    </div>
                  </td>
                  <td class="px-2 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-sm text-gray-900 hidden sm:table-cell">
                    <div class="flex items-center">
                      <i class="fas fa-building mr-1 sm:mr-2 text-gray-400 text-xs"></i>
                      <span class="text-xs sm:text-sm truncate max-w-xs" :title="order_request.supplier_name">{{ order_request.lead_time }}</span>
                    </div>
                  </td>

                  <td class="px-2 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-sm text-gray-900 hidden md:table-cell">
                    <div class="flex items-center">
                      <div class="flex-shrink-0 h-6 w-6 sm:h-8 sm:w-8">
                        <div class="h-6 w-6 sm:h-8 sm:w-8 rounded-full bg-blue-100 flex items-center justify-center">
                          <i class="fas fa-user text-blue-600 text-xs"></i>
                        </div>
                      </div>
                      <div class="ml-2 sm:ml-3">
                        <div class="text-xs sm:text-sm font-medium text-gray-900 truncate max-w-xs" :title="order_request.request_user_name">{{ order_request.request_user_name }}</div>
                      </div>
                    </div>
                  </td>
                  <td class="px-2 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-sm text-gray-900 hidden md:table-cell">
                    <div class="flex items-center">
                      <div class="flex-shrink-0 h-6 w-6 sm:h-8 sm:w-8">
                        <div class="h-6 w-6 sm:h-8 sm:w-8 rounded-full bg-purple-100 flex items-center justify-center">
                          <i class="fas fa-user-tie text-purple-600 text-xs"></i>
                        </div>
                      </div>
                      <div class="ml-2 sm:ml-3">
                        <div class="text-xs sm:text-sm font-medium text-gray-900 truncate max-w-xs" :title="order_request.user_name">{{ order_request.user_name }}</div>
                      </div>
                    </div>
                  </td>
                  <td class="px-2 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-sm text-gray-600 hidden lg:table-cell">
                    <div class="flex items-center">
                      <i class="fas fa-calendar-plus mr-1 sm:mr-2 text-gray-400 text-xs"></i>
                      <span class="text-xs sm:text-sm">{{
                        new Date(order_request.created_at)
                          .toLocaleDateString("ja-JP", {
                            year: "numeric",
                            month: "2-digit",
                            day: "2-digit",
                          })
                          .replace(/\//g, "/")
                      }}</span>
                    </div>
                  </td>
                  <td class="px-2 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-sm text-gray-600 hidden lg:table-cell">
                    <div class="flex items-center">
                      <i class="fas fa-calendar-check mr-1 sm:mr-2 text-gray-400 text-xs"></i>
                      <span class="text-xs sm:text-sm">{{
                        order_request.digest_date
                          ? new Date(order_request.digest_date)
                              .toLocaleDateString("ja-JP", {
                                year: "numeric",
                                month: "2-digit",
                                day: "2-digit",
                              })
                              .replace(/\//g, "/")
                          : "-"
                      }}</span>
                    </div>
                  </td>
                  <td class="px-2 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-sm text-gray-600 hidden lg:table-cell">
                    <div class="flex items-center">
                      <i class="fas fa-calendar-day mr-1 sm:mr-2 text-red-400 text-xs"></i>
                      <span class="text-xs sm:text-sm">{{
                        order_request.desire_delivery_date
                          ? new Date(order_request.desire_delivery_date)
                              .toLocaleDateString("ja-JP", {
                                year: "numeric",
                                month: "2-digit",
                                day: "2-digit",
                              })
                              .replace(/\//g, "/")
                          : "-"
                      }}</span>
                    </div>
                  </td>
                  <td class="px-2 sm:px-6 py-3 sm:py-4 text-sm text-gray-600 max-w-xs hidden xl:table-cell">
                    <div class="truncate" :title="order_request.description">
                      <i class="fas fa-comment mr-1 sm:mr-2 text-gray-400 text-xs"></i>
                      <span class="text-xs sm:text-sm">{{ order_request.description ?? "-" }}</span>
                    </div>
                  </td>
                  <td class="px-2 sm:px-6 py-3 sm:py-4 text-sm text-gray-600 max-w-xs hidden xl:table-cell">
                    <div class="truncate" :title="order_request.sub_description">
                      <i class="fas fa-comment-dots mr-1 sm:mr-2 text-gray-400 text-xs"></i>
                      <span class="text-xs sm:text-sm">{{ order_request.sub_description ?? "-" }}</span>
                    </div>
                  </td>
                  <td class="px-2 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-center hidden lg:table-cell">
                    <span
                      v-if="order_request.file_path || order_request.document_id"
                      class="inline-flex items-center px-1 sm:px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800"
                    >
                      <i class="fas fa-check mr-1 text-xs"></i>
                      <span class="hidden sm:inline">あり</span>
                      <span class="sm:hidden">○</span>
                    </span>
                    <span
                      v-else
                      class="inline-flex items-center px-1 sm:px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800"
                    >
                      <i class="fas fa-minus mr-1 text-xs"></i>
                      <span class="hidden sm:inline">未登録</span>
                      <span class="sm:hidden">×</span>
                    </span>
                  </td>
                  <td class="px-2 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-center">
                    <button
                      @click="openDescription(order_request)"
                      class="inline-flex items-center px-2 sm:px-4 py-2 border-2 border-blue-600 text-xs sm:text-sm font-medium rounded-lg text-blue-600 bg-white hover:bg-blue-600 hover:text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-sm hover:shadow-md"
                    >
                      <i class="fas fa-search mr-1 sm:mr-2 text-xs"></i>
                      <span class="hidden sm:inline">詳細</span>
                    </button>
                  </td>
                  <td class="px-2 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-center">
                    <div class="flex flex-col sm:flex-row items-center justify-center space-y-1 sm:space-y-0 sm:space-x-2">
                      <button
                        @click.prevent="startSingleAccept(order_request)"
                        :disabled="getRowStyle(order_request).isDisabled || loading.isLoading"
                        :class="[
                          'inline-flex items-center justify-center px-2 sm:px-3 py-2 border border-transparent text-xs sm:text-sm font-medium rounded-md transition-colors duration-200 w-full sm:w-auto',
                          getRowStyle(order_request).isDisabled || loading.isLoading
                            ? 'text-gray-400 bg-gray-300 cursor-not-allowed'
                            : 'text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 shadow-sm hover:shadow-md'
                        ]"
                      >
                        <i v-if="loading.isLoading && loading.currentAction === 'accept'" class="fas fa-spinner fa-spin mr-1 text-xs"></i>
                        <i v-else class="fas fa-check mr-1 text-xs"></i>
                        <span class="hidden sm:inline">{{ loading.isLoading && loading.currentAction === 'accept' ? '処理中...' : '承認' }}</span>
                        <span class="sm:hidden">{{ loading.isLoading && loading.currentAction === 'accept' ? '処理中' : '承認' }}</span>
                      </button>
                      <button
                        @click.prevent="startSingleReject(order_request)"
                        :disabled="getRowStyle(order_request).isDisabled || loading.isLoading"
                        :class="[
                          'inline-flex items-center justify-center px-2 sm:px-3 py-2 border border-transparent text-xs sm:text-sm font-medium rounded-md transition-colors duration-200 w-full sm:w-auto',
                          getRowStyle(order_request).isDisabled || loading.isLoading
                            ? 'text-gray-400 bg-gray-300 cursor-not-allowed'
                            : 'text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 shadow-sm hover:shadow-md'
                        ]"
                      >
                        <i class="fas fa-times mr-1 text-xs"></i>
                        <span class="hidden sm:inline">却下</span>
                        <span class="sm:hidden">却下</span>
                      </button>
                    </div>
                    <!-- 差し戻し状態の説明 -->
                    <div v-if="getRowStyle(order_request).isDisabled" class="mt-2">
                      <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                        <i class="fas fa-pause mr-1"></i>
                        承認停止中
                      </span>
                    </div>
                    <!-- 回答待ち状態の説明 -->
                    <div v-if="getRowStyle(order_request).isWaitingResponse" class="mt-2">
                      <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800 animate-pulse">
                        <i class="fas fa-reply mr-1"></i>
                        あなたの回答待ち
                      </span>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>
    </template>
  </AcceptLayout>

  <!-- 全画面ローディングオーバーレイ -->
  <div
    v-if="loading.isLoading"
    class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center"
    style="z-index: 9999;"
  >
    <div class="bg-white rounded-2xl p-8 shadow-2xl max-w-sm w-full mx-4">
      <div class="text-center">
        <!-- スピナー -->
        <div class="relative mb-6">
          <div class="w-16 h-16 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin mx-auto"></div>
          <div class="absolute inset-0 flex items-center justify-center">
            <i class="fas fa-check text-blue-600 text-lg" v-if="loading.currentAction === 'accept'"></i>
            <i class="fas fa-times text-red-600 text-lg" v-else-if="loading.currentAction === 'reject'"></i>
          </div>
        </div>
        
        <!-- メッセージ -->
        <h3 class="text-lg font-semibold text-gray-900 mb-2">
          {{ loading.message }}
        </h3>
        <p class="text-sm text-gray-600">
          しばらくお待ちください...
        </p>
        
        <!-- プログレスバー -->
        <div class="mt-6">
          <div class="w-full bg-gray-200 rounded-full h-2">
            <div class="bg-blue-600 h-2 rounded-full animate-pulse" style="width: 70%;"></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- 詳細ダイアログモーダル -->
  <div
    v-if="description_order_request.comment.order_request_id"
    class="fixed inset-0 bg-gray-900 bg-opacity-50 z-50 overflow-y-auto"
    id="description_container"
  >
    <div class="flex items-start justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:p-0">
      <div class="fixed inset-0 transition-opacity" aria-hidden="true">
        <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
      </div>

      <!-- モーダルコンテンツ -->
      <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-6xl sm:w-full">
        <!-- ヘッダー -->
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-4">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div class="w-10 h-10 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                  <i class="fas fa-file-alt text-white text-lg"></i>
                </div>
              </div>
              <div class="ml-4">
                <h3 class="text-xl font-bold text-white">発注依頼詳細</h3>
                <p class="text-blue-100 text-sm">{{ description_order_request.order_request.name }}</p>
              </div>
            </div>
                <button
              @click="description_order_request.comment.order_request_id = 0"
              class="text-white hover:text-gray-200 transition-colors duration-200 p-2 rounded-full hover:bg-white hover:bg-opacity-20"
            >
              <i class="fas fa-times text-xl"></i>
                </button>
          </div>
              </div>

        <!-- メインコンテンツ -->
        <div class="bg-white px-6 py-6 max-h-screen-80 overflow-y-auto" style="max-height: 80vh;">
          <!-- 状態表示 -->
          <div v-if="getRowStyle(description_order_request.order_request).isDisabled" class="mb-6 p-4 bg-gray-100 border border-gray-200 rounded-xl">
            <div class="flex items-center">
              <i class="fas fa-info-circle text-gray-500 mr-3"></i>
              <div>
                <div class="text-sm font-medium text-gray-700">承認停止中</div>
                <div class="text-xs text-gray-500">この発注依頼は差し戻されており、あなたの承認権限は現在停止中です。</div>
              </div>
            </div>
          </div>
          
          <div v-if="getRowStyle(description_order_request.order_request).isWaitingResponse" class="mb-6 p-4 bg-orange-50 border border-orange-200 rounded-xl">
            <div class="flex items-center">
              <i class="fas fa-exclamation-triangle text-orange-500 mr-3"></i>
              <div>
                <div class="text-sm font-medium text-orange-700">あなたの回答が必要です</div>
                <div class="text-xs text-orange-600">この発注依頼は差し戻されており、あなたの再承認が必要です。</div>
              </div>
            </div>
          </div>

          <!-- アクションボタン -->
          <!-- <div class="flex justify-center space-x-4 mb-8 p-4 bg-gray-50 rounded-xl">
            <button
              @click.prevent="
                sendAccept(
                  description_order_request.order_request.order_request_approval_id,
                  'accept',
                  true
                )
              "
              :disabled="getRowStyle(description_order_request.order_request).isDisabled || loading.isLoading"
              :class="[
                'flex-1 inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-xl transition-all duration-200',
                getRowStyle(description_order_request.order_request).isDisabled || loading.isLoading
                  ? 'text-gray-400 bg-gray-300 cursor-not-allowed'
                  : 'text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 hover:transform hover:scale-105'
              ]"
            >
              <i v-if="loading.isLoading && loading.currentAction === 'accept'" class="fas fa-spinner fa-spin mr-2"></i>
              <i v-else class="fas fa-check mr-2"></i>
              {{ loading.isLoading && loading.currentAction === 'accept' ? '承認処理中...' : '承認する' }}
            </button>
            <button
              @click.prevent="
                sendAccept(
                  description_order_request.order_request.order_request_approval_id,
                  'reject',
                  true
                )
              "
              :disabled="getRowStyle(description_order_request.order_request).isDisabled || loading.isLoading"
              :class="[
                'flex-1 inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-xl transition-all duration-200',
                getRowStyle(description_order_request.order_request).isDisabled || loading.isLoading
                  ? 'text-gray-400 bg-gray-300 cursor-not-allowed'
                  : 'text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 hover:transform hover:scale-105'
              ]"
            >
              <i v-if="loading.isLoading && loading.currentAction === 'reject'" class="fas fa-spinner fa-spin mr-2"></i>
              <i v-else class="fas fa-times mr-2"></i>
              {{ loading.isLoading && loading.currentAction === 'reject' ? '却下処理中...' : '却下する' }}
            </button>
          </div> -->

          <!-- コメント入力セクション -->
          <div class="mb-8">
            <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
              <div class="flex items-center mb-4">
                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                  <i class="fas fa-comment text-blue-600"></i>
                </div>
                <h4 class="text-lg font-semibold text-gray-900">コメント追加</h4>
              </div>

              <textarea
                id="message"
                rows="4"
                class="w-full p-4 text-sm text-gray-900 bg-gray-50 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 resize-none"
                :placeholder="description_order_request.comment.placeholder"
                v-model="description_order_request.comment.msg"
                @change="save_comment"
                :disabled="loading.isLoading"
                :class="{ 'opacity-50 cursor-not-allowed': loading.isLoading }"
              ></textarea>
            </div>
          </div>

          <!-- 承認フロー -->
          <div class="mb-8">
            <div class="flex items-center mb-6">
              <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center mr-3">
                <i class="fas fa-route text-purple-600"></i>
              </div>
              <h4 class="text-lg font-semibold text-gray-900">承認フロー</h4>
            </div>
            
            <div class="flex items-center justify-start overflow-x-auto pb-4" id="approval_container">
              <div
                v-for="(approval, index) in description_order_request.order_request.order_request_approvals"
                  :key="approval.id"
                class="flex items-center flex-shrink-0"
              >
                <!-- 承認カード -->
                <div class="bg-white border-2 rounded-xl shadow-sm p-4 min-w-64 hover:shadow-md transition-shadow duration-200"
                     :class="{
                       'border-emerald-200 bg-emerald-50': approval.status === 1,
                       'border-red-200 bg-red-50': approval.status === 2,
                       'border-blue-200 bg-blue-50': approval.status === 0,
                       'border-gray-200': approval.status === null
                     }">
                  
                  <!-- ステータスアイコンとヘッダー -->
                  <div class="flex items-center mb-3">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center mr-3"
                         :class="{
                           'bg-emerald-100': approval.status === 1,
                           'bg-red-100': approval.status === 2,
                           'bg-blue-100': approval.status === 0,
                           'bg-gray-100': approval.status === null
                         }">
                      <i class="text-lg"
                         :class="{
                           'fas fa-check text-emerald-600': approval.status === 1,
                           'fas fa-times text-red-600': approval.status === 2,
                           'fas fa-clock text-blue-600': approval.status === 0,
                           'fas fa-user text-gray-400': approval.status === null
                         }"></i>
                    </div>
                    <div>
                      <div class="font-bold text-gray-900">{{ approval.name }}</div>
                      <div class="text-xs text-gray-500"
                           :class="{
                             'text-emerald-600': approval.status === 1,
                             'text-red-600': approval.status === 2,
                             'text-blue-600': approval.status === 0
                           }">
                        {{ 
                          approval.status === 1 ? '承認済み' : 
                          approval.status === 2 ? '却下' : 
                          approval.status === 0 ? '承認待ち' : 
                          '未処理' 
                        }}
                      </div>
                    </div>
                  </div>

                  <!-- 日時 -->
                  <div class="text-xs text-gray-500 mb-2" v-if="approval.updated_at">
                    <i class="fas fa-calendar-alt mr-1"></i>
                      {{ new Date(approval.updated_at).getFullYear() }}年{{
                        new Date(approval.updated_at).getMonth() + 1
                      }}月{{ new Date(approval.updated_at).getDate() }}日
                      {{ new Date(approval.updated_at).getHours() }}時{{
                        new Date(approval.updated_at).getMinutes()
                      }}分
                    </div>

                  <!-- コメント -->
                  <div class="text-sm text-gray-700 bg-white p-3 rounded-lg border border-gray-100 h-64 max-h-64 overflow-y-auto">
                    <i class="fas fa-quote-left text-gray-400 mr-2"></i>
                    <p v-if="approval.comment" v-html="approval.comment.replace(/\n/g, '<br>')">
                    </p>
                    <p v-else>コメントはありません。</p>
                  </div>
                </div>

                <!-- 矢印（最後の要素以外） -->
                <div v-if="index < description_order_request.order_request.order_request_approvals.length - 1" 
                     class="mx-4 text-gray-400">
                  <i class="fas fa-arrow-right text-2xl"></i>
                </div>
                  </div>
                </div>
              </div>

              

          <!-- 基本情報 -->
          <div class="mb-8">
            <div class="flex items-center mb-6">
              <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-3">
                <i class="fas fa-info-circle text-green-600"></i>
              </div>
              <h4 class="text-lg font-semibold text-gray-900">基本情報</h4>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <!-- 左側 -->
              <div class="space-y-6">
                <!-- 商品画像 -->
                <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
                  <div class="flex items-center mb-4">
                    <i class="fas fa-image text-gray-400 mr-2"></i>
                    <span class="text-sm font-semibold text-gray-600 uppercase tracking-wide">商品画像</span>
                  </div>
                  <div class="flex justify-center">
                    <img
                      :src="getImgPath(description_order_request.order_request.img_path)"
                      alt="商品画像"
                      class="max-w-full h-48 object-contain rounded-lg border border-gray-200"
                    />
                  </div>
                </div>

                <!-- 価格情報 -->
                <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
                  <div class="flex items-center mb-4">
                    <i class="fas fa-yen-sign text-gray-400 mr-2"></i>
                    <span class="text-sm font-semibold text-gray-600 uppercase tracking-wide">価格情報</span>
                  </div>
                  <div class="grid grid-cols-2 gap-4">
                    <div>
                      <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">単価</label>
                      <div class="mt-1 text-lg font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-yen-sign text-green-500 mr-2"></i>
                        {{ description_order_request.order_request.price.toLocaleString() }}
                  </div>
                </div>
                    <div>
                      <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">合計金額</label>
                      <div class="mt-1 text-lg font-bold text-blue-600 flex items-center">
                        <i class="fas fa-calculator text-blue-500 mr-2"></i>
                        ¥{{ description_order_request.order_request.calc_price.toLocaleString() }}
                  </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- 右側 -->
              <div class="space-y-6">
                <!-- 担当者情報 -->
                <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
                  <div class="flex items-center mb-4">
                    <i class="fas fa-users text-gray-400 mr-2"></i>
                    <span class="text-sm font-semibold text-gray-600 uppercase tracking-wide">担当者情報</span>
                  </div>
                  <div class="space-y-4">
                    <div class="flex items-center">
                      <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                        <i class="fas fa-user text-blue-600"></i>
                      </div>
                      <div>
                        <div class="text-xs font-medium text-gray-500">依頼者</div>
                        <div class="font-semibold text-gray-900">{{ description_order_request.order_request.request_user_name }}</div>
                      </div>
                    </div>
                    <div class="flex items-center">
                      <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center mr-3">
                        <i class="fas fa-user-tie text-purple-600"></i>
                      </div>
                      <div>
                        <div class="text-xs font-medium text-gray-500">担当者</div>
                        <div class="font-semibold text-gray-900">{{ description_order_request.order_request.user_name }}</div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- 商品情報 -->
                <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
                  <div class="flex items-center mb-4">
                    <i class="fas fa-box text-gray-400 mr-2"></i>
                    <span class="text-sm font-semibold text-gray-600 uppercase tracking-wide">商品情報</span>
                  </div>
                  <div class="space-y-3">
                <div>
                      <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">品名</label>
                      <div class="mt-1 text-sm font-medium text-gray-900">{{ description_order_request.order_request.name }}</div>
                    </div>
                    <div>
                      <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">品番</label>
                      <div class="mt-1">
                        <code class="bg-gray-100 px-2 py-1 rounded text-xs">{{ description_order_request.order_request.s_name }}</code>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- 備考情報 -->
            <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
              <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
                <div class="flex items-center mb-4">
                  <i class="fas fa-comment text-gray-400 mr-2"></i>
                  <span class="text-sm font-semibold text-gray-600 uppercase tracking-wide">依頼者備考</span>
                    </div>
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                  <p class="text-sm text-gray-700">{{ description_order_request.order_request.description || "-" }}</p>
                </div>
              </div>

              <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
                <div class="flex items-center mb-4">
                  <i class="fas fa-comment-dots text-gray-400 mr-2"></i>
                  <span class="text-sm font-semibold text-gray-600 uppercase tracking-wide">発注者備考</span>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                  <p class="text-sm text-gray-700">{{ description_order_request.order_request.sub_description || "-" }}</p>
                </div>
              </div>
                    </div>
                  </div>

          <!-- 稟議書コンポーネント -->
          <div v-if="description_order_request.order_request.new_stock_flg" class="mb-8">
            <div class="flex items-center mb-6">
              <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center mr-3">
                <i class="fas fa-file-contract text-indigo-600"></i>
                </div>
              <h4 class="text-lg font-semibold text-gray-900">稟議書</h4>
              </div>
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
              <ApprovalDocument :approval_document="description_order_request.approval_document" />
            </div>
          </div>

          <!-- PDF ビューワー -->
          <div v-if="description_order_request.viewerUrl != ''" class="mb-8">
            <div class="flex items-center mb-6">
              <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center mr-3">
                <i class="fas fa-file-pdf text-red-600"></i>
              </div>
              <h4 class="text-lg font-semibold text-gray-900">添付ファイル</h4>
            </div>
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
          <iframe
            ref="pdfViewer"
            :src="description_order_request.viewerUrl"
                class="w-full"
                style="min-height: 80vh;"
            frameborder="0"
          ></iframe>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- 一括処理モーダル -->
  <div
    v-if="bulkModal.isOpen"
    class="fixed inset-0 bg-gray-900 bg-opacity-50 z-50 overflow-y-auto"
    style="z-index: 9998;"
  >
    <div class="flex items-start justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:p-0">
      <div class="fixed inset-0 transition-opacity" aria-hidden="true">
        <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
      </div>

      <!-- モーダルコンテンツ -->
      <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full mx-2 sm:mx-0">
        <!-- ヘッダー -->
        <div :class="[
          'px-4 sm:px-6 py-3 sm:py-4',
          bulkModal.action === 'accept' ? 'bg-gradient-to-r from-emerald-600 to-green-600' : 'bg-gradient-to-r from-red-600 to-pink-600'
        ]">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div class="w-8 h-8 sm:w-10 sm:h-10 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                  <i :class="[
                    'text-white text-sm sm:text-lg',
                    bulkModal.action === 'accept' ? 'fas fa-check-double' : 'fas fa-times-circle'
                  ]"></i>
                </div>
              </div>
              <div class="ml-3 sm:ml-4">
                <h3 class="text-lg sm:text-xl font-bold text-white">
                  {{ bulkModal.action === 'accept' ? 'まとめて承認' : 'まとめて却下' }}
                </h3>
                <p class="text-white text-opacity-90 text-xs sm:text-sm">
                  {{ bulkModal.currentIndex + 1 }} / {{ bulkModal.items.length }} 件目
                </p>
              </div>
            </div>
            <button
              @click="closeBulkModal"
              class="text-white hover:text-gray-200 transition-colors duration-200 p-2 rounded-full hover:bg-white hover:bg-opacity-20"
            >
              <i class="fas fa-times text-lg sm:text-xl"></i>
            </button>
          </div>
        </div>

        <!-- メインコンテンツ -->
        <div class="bg-white px-4 sm:px-6 py-4 sm:py-6">
          <div v-if="bulkModal.items[bulkModal.currentIndex]" class="space-y-4">
            <!-- 現在のアイテム情報 -->
            <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
              <div class="flex items-start space-x-4">
                <img
                  :src="getImgPath(bulkModal.items[bulkModal.currentIndex].img_path)"
                  alt="商品画像"
                  class="h-20 w-20 object-cover rounded-lg border border-gray-300"
                />
                <div class="flex-1">
                  <h4 class="text-lg font-bold text-gray-900 mb-1">
                    {{ bulkModal.items[bulkModal.currentIndex].name }}
                  </h4>
                  <p class="text-sm text-gray-600 mb-2">
                    品番: <code class="bg-gray-200 px-2 py-1 rounded text-xs">{{ bulkModal.items[bulkModal.currentIndex].s_name }}</code>
                  </p>
                  <div class="flex items-center space-x-4 text-sm">
                    <span class="flex items-center text-gray-700">
                      <i class="fas fa-cube mr-1 text-blue-500"></i>
                      数量: {{ bulkModal.items[bulkModal.currentIndex].quantity }}{{ bulkModal.items[bulkModal.currentIndex].unit }}
                    </span>
                    <span class="flex items-center text-gray-700">
                      <i class="fas fa-yen-sign mr-1 text-green-500"></i>
                      金額: ¥{{ bulkModal.items[bulkModal.currentIndex].calc_price?.toLocaleString() }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- コメント入力 -->
            <div class="bg-white border border-gray-200 rounded-xl p-4">
              <div class="flex items-center mb-3">
                <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center mr-3">
                  <i class="fas fa-comment text-indigo-600"></i>
                </div>
                <h4 class="text-base font-semibold text-gray-900">
                  {{ bulkModal.action === 'accept' ? 'コメント（任意）' : 'コメント（必須）' }}
                </h4>
                <span v-if="bulkModal.action === 'reject'" class="ml-2 text-xs text-red-600 font-medium">
                  ※ 却下の場合はコメント必須です
                </span>
              </div>
              <textarea
                v-model="bulkModal.comments[bulkModal.items[bulkModal.currentIndex].order_request_approval_id]"
                rows="4"
                :placeholder="`${bulkModal.items[bulkModal.currentIndex].name} のコメントを入力してください`"
                class="w-full p-3 text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 resize-none"
                :class="{ 'border-red-300 focus:ring-red-500 focus:border-red-500': bulkModal.action === 'reject' && !bulkModal.comments[bulkModal.items[bulkModal.currentIndex].order_request_approval_id]?.trim() }"
              ></textarea>
            </div>

            <!-- ナビゲーションボタン -->
            <div class="flex items-center justify-between pt-4 border-t border-gray-200">
              <button
                @click="bulkModalPrev"
                :disabled="bulkModal.currentIndex === 0"
                class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white text-sm font-medium rounded-lg transition-colors duration-200"
              >
                <i class="fas fa-chevron-left mr-2"></i>
                前へ
              </button>
              
              <div class="text-sm text-gray-600 font-medium">
                {{ bulkModal.currentIndex + 1 }} / {{ bulkModal.items.length }}
              </div>
              
              <button
                v-if="bulkModal.currentIndex < bulkModal.items.length - 1"
                @click="bulkModalNext"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors duration-200"
              >
                次へ
                <i class="fas fa-chevron-right ml-2"></i>
              </button>
              <button
                v-else
                @click="executeBulkAction"
                :disabled="loading.isLoading"
                :class="[
                  'inline-flex items-center px-6 py-2 text-white text-sm font-medium rounded-lg transition-colors duration-200',
                  bulkModal.action === 'accept' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-red-600 hover:bg-red-700',
                  loading.isLoading && 'opacity-50 cursor-not-allowed'
                ]"
              >
                <i v-if="loading.isLoading" class="fas fa-spinner fa-spin mr-2"></i>
                <i v-else :class="['mr-2', bulkModal.action === 'accept' ? 'fas fa-check' : 'fas fa-times']"></i>
                {{ bulkModal.action === 'accept' ? '一括承認を実行' : '一括却下を実行' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- 部署詳細モーダル -->
  <div
    v-if="departmentModal.isOpen"
    class="fixed inset-0 bg-gray-900 bg-opacity-50 z-50 overflow-y-auto"
  >
    <div class="flex items-start justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:p-0">
      <div class="fixed inset-0 transition-opacity" aria-hidden="true">
        <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
      </div>

      <!-- モーダルコンテンツ -->
      <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-6xl sm:w-full mx-2 sm:mx-0">
        <!-- ヘッダー -->
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-4 sm:px-6 py-3 sm:py-4">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div class="w-8 h-8 sm:w-10 sm:h-10 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                  <i class="fas fa-building text-white text-sm sm:text-lg"></i>
                </div>
              </div>
              <div class="ml-3 sm:ml-4">
                <h3 class="text-lg sm:text-xl font-bold text-white">{{ departmentModal.selectedDepartment.process_name }} - 承認済み物品一覧</h3>
                <p class="text-indigo-100 text-xs sm:text-sm">{{ departmentModal.departmentItems.length }}件の承認済み物品</p>
              </div>
            </div>
            <button
              @click="closeDepartmentModal"
              class="text-white hover:text-gray-200 transition-colors duration-200 p-2 rounded-full hover:bg-white hover:bg-opacity-20"
            >
              <i class="fas fa-times text-lg sm:text-xl"></i>
            </button>
          </div>
        </div>

        <!-- 統計情報 -->
        <div class="bg-gray-50 px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
            <div class="text-center">
              <div class="text-lg sm:text-2xl font-bold text-indigo-600">{{ departmentModal.departmentItems.length }}</div>
              <div class="text-xs sm:text-sm text-gray-600">承認件数</div>
            </div>
            <div class="text-center">
              <div class="text-lg sm:text-2xl font-bold text-emerald-600">
                ¥{{ departmentModal.departmentItems.reduce((sum, item) => sum + parseInt(item.calc_price || 0), 0).toLocaleString() }}
              </div>
              <div class="text-xs sm:text-sm text-gray-600">合計金額</div>
            </div>
            <div class="text-center">
              <div class="text-lg sm:text-2xl font-bold text-blue-600">
                ¥{{ departmentModal.departmentItems.length > 0 ? Math.round(departmentModal.departmentItems.reduce((sum, item) => sum + parseInt(item.calc_price || 0), 0) / departmentModal.departmentItems.length).toLocaleString() : 0 }}
              </div>
              <div class="text-xs sm:text-sm text-gray-600">平均金額</div>
            </div>
          </div>
        </div>

        <!-- メインコンテンツ -->
        <div class="bg-white px-2 sm:px-6 py-4 sm:py-6 max-h-screen-80 overflow-y-auto" style="max-height: 60vh;">
          <!-- 物品一覧テーブル -->
          <div v-if="departmentModal.departmentItems.length > 0" class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 border border-gray-200 rounded-lg department-modal-table">
              <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                <tr>
                  <th class="px-2 sm:px-4 py-2 sm:py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-r border-gray-200 whitespace-nowrap">
                    <div class="flex items-center">
                      <i class="fas fa-image mr-1 sm:mr-2 text-gray-400 text-xs"></i>
                      <span class="hidden sm:inline">画像</span>
                    </div>
                  </th>
                  <th class="px-2 sm:px-4 py-2 sm:py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-r border-gray-200 whitespace-nowrap cursor-pointer hover:bg-gray-100 transition-colors duration-200" @click="sortItems('name')">
                    <div class="flex items-center justify-between">
                      <div class="flex items-center">
                        <i class="fas fa-tag mr-1 sm:mr-2 text-gray-400 text-xs"></i>
                        <span class="hidden sm:inline">品名</span>
                      </div>
                      <i :class="getSortIcon('name')" class="text-xs ml-1"></i>
                    </div>
                  </th>
                  <th class="px-2 sm:px-4 py-2 sm:py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-r border-gray-200 whitespace-nowrap cursor-pointer hover:bg-gray-100 transition-colors duration-200" @click="sortItems('s_name')">
                    <div class="flex items-center justify-between">
                      <div class="flex items-center">
                        <i class="fas fa-barcode mr-1 sm:mr-2 text-gray-400 text-xs"></i>
                        <span class="hidden sm:inline">品番</span>
                      </div>
                      <i :class="getSortIcon('s_name')" class="text-xs ml-1"></i>
                    </div>
                  </th>
                  <th class="px-2 sm:px-4 py-2 sm:py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-r border-gray-200 whitespace-nowrap">
                    <div class="flex items-center">
                      <i class="fas fa-cube mr-1 sm:mr-2 text-gray-400 text-xs"></i>
                      <span class="hidden sm:inline">数量</span>
                    </div>
                  </th>
                  <th class="px-2 sm:px-4 py-2 sm:py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-r border-gray-200 whitespace-nowrap">
                    <div class="flex items-center">
                      <i class="fas fa-yen-sign mr-1 sm:mr-2 text-gray-400 text-xs"></i>
                      <span class="hidden sm:inline">単価</span>
                    </div>
                  </th>
                  <th class="px-2 sm:px-4 py-2 sm:py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-r border-gray-200 whitespace-nowrap">
                    <div class="flex items-center">
                      <i class="fas fa-calculator mr-1 sm:mr-2 text-gray-400 text-xs"></i>
                      <span class="hidden sm:inline">金額</span>
                    </div>
                  </th>
                  <th class="px-2 sm:px-4 py-2 sm:py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-r border-gray-200 whitespace-nowrap hidden sm:table-cell cursor-pointer hover:bg-gray-100 transition-colors duration-200" @click="sortItems('supplier_name')">
                    <div class="flex items-center justify-between">
                      <div class="flex items-center">
                        <i class="fas fa-building mr-1 sm:mr-2 text-gray-400 text-xs"></i>
                        発注先
                      </div>
                      <i :class="getSortIcon('supplier_name')" class="text-xs ml-1"></i>
                    </div>
                  </th>
                  <th class="px-2 sm:px-4 py-2 sm:py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-r border-gray-200 whitespace-nowrap hidden md:table-cell cursor-pointer hover:bg-gray-100 transition-colors duration-200" @click="sortItems('request_user_name')">
                    <div class="flex items-center justify-between">
                      <div class="flex items-center">
                        <i class="fas fa-folder mr-1 sm:mr-2 text-gray-400 text-xs"></i>
                        依頼者
                      </div>
                      <i :class="getSortIcon('request_user_name')" class="text-xs ml-1"></i>
                    </div>
                  </th>
                  <th class="px-2 sm:px-4 py-2 sm:py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-r border-gray-200 whitespace-nowrap hidden md:table-cell cursor-pointer hover:bg-gray-100 transition-colors duration-200" @click="sortItems('classification_name')">
                    <div class="flex items-center justify-between">
                      <div class="flex items-center">
                        <i class="fas fa-folder mr-1 sm:mr-2 text-gray-400 text-xs"></i>
                        カテゴリー
                      </div>
                      <i :class="getSortIcon('classification_name')" class="text-xs ml-1"></i>
                    </div>
                  </th>
                  <th class="px-2 sm:px-4 py-2 sm:py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider whitespace-nowrap">
                    <div class="flex items-center">
                      <i class="fas fa-calendar-check mr-1 sm:mr-2 text-gray-400 text-xs"></i>
                      <span class="hidden sm:inline">承認日</span>
                    </div>
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="item in departmentModal.departmentItems" :key="item.id" class="hover:bg-blue-50 transition-colors duration-200">
                  <td class="px-2 sm:px-4 py-3 sm:py-4 whitespace-nowrap border-r border-gray-100">
                    <div class="flex items-center justify-center">
                      <img 
                        :src="getImgPath(item.img_path)" 
                        alt="商品画像"
                        class="h-10 w-10 sm:h-14 sm:w-14 object-cover rounded-lg shadow-sm border border-gray-200"
                      />
                    </div>
                  </td>
                  <td class="px-2 sm:px-4 py-3 sm:py-4 text-sm text-gray-900 border-r border-gray-100">
                    <div class="font-medium text-gray-900 max-w-xs truncate text-xs sm:text-sm" :title="item.name">{{ item.name }}</div>
                  </td>
                  <td class="px-2 sm:px-4 py-3 sm:py-4 whitespace-nowrap text-sm text-gray-600 border-r border-gray-100">
                    <code class="bg-blue-100 text-blue-800 px-1 sm:px-2 py-1 rounded text-xs font-mono">{{ item.s_name || "-" }}</code>
                  </td>
                  <td class="px-2 sm:px-4 py-3 sm:py-4 whitespace-nowrap text-sm text-gray-900 border-r border-gray-100">
                    <div class="flex items-center">
                      <i class="fas fa-cube mr-1 sm:mr-2 text-blue-400 text-xs"></i>
                      <span class="font-semibold text-xs sm:text-sm">{{ `${item.quantity || ""}${item.unit || ""}` }}</span>
                    </div>
                  </td>
                  <td class="px-2 sm:px-4 py-3 sm:py-4 whitespace-nowrap text-sm text-gray-900 border-r border-gray-100">
                    <div class="flex items-center font-semibold">
                      <i class="fas fa-yen-sign mr-1 sm:mr-2 text-green-500 text-xs"></i>
                      <span class="text-xs sm:text-sm">¥{{ parseInt(item.price || 0).toLocaleString() }}</span>
                    </div>
                  </td>
                  <td class="px-2 sm:px-4 py-3 sm:py-4 whitespace-nowrap text-sm text-gray-900 font-bold border-r border-gray-100">
                    <div class="flex items-center text-emerald-600">
                      <i class="fas fa-calculator mr-1 sm:mr-2 text-xs"></i>
                      <span class="text-xs sm:text-sm">¥{{ parseInt(item.calc_price || 0).toLocaleString() }}</span>
                    </div>
                  </td>
                  <td class="px-2 sm:px-4 py-3 sm:py-4 whitespace-nowrap text-sm text-gray-600 border-r border-gray-100 hidden sm:table-cell">
                    <div class="flex items-center">
                      <i class="fas fa-building mr-1 sm:mr-2 text-gray-400 text-xs"></i>
                      <span class="max-w-xs truncate text-xs sm:text-sm" :title="item.supplier_name">{{ item.supplier_name }}</span>
                    </div>
                  </td>
                  <td class="px-2 sm:px-4 py-3 sm:py-4 whitespace-nowrap text-sm text-gray-600 border-r border-gray-100 hidden md:table-cell">
                    <div class="flex items-center">
                      <i class="fas fa-user mr-1 sm:mr-2 text-blue-400 text-xs"></i>
                      <span class="max-w-xs truncate text-xs sm:text-sm" :title="item.request_user_name">{{ item.request_user_name || "-" }}</span>
                    </div>
                  </td>
                  <td class="px-2 sm:px-4 py-3 sm:py-4 whitespace-nowrap text-sm text-gray-600 border-r border-gray-100 hidden md:table-cell">
                    <div class="flex items-center">
                      <i class="fas fa-folder mr-1 sm:mr-2 text-purple-400 text-xs"></i>
                      <span class="max-w-xs truncate text-xs sm:text-sm" :title="item.classification_name">{{ item.classification_name || "-" }}</span>
                    </div>
                  </td>
                  <td class="px-2 sm:px-4 py-3 sm:py-4 whitespace-nowrap text-sm text-gray-600">
                    <div class="flex items-center">
                      <i class="fas fa-calendar-check mr-1 sm:mr-2 text-gray-400 text-xs"></i>
                      <span class="text-xs sm:text-sm">{{ new Date(item.updated_at).toLocaleDateString("ja-JP") }}</span>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          
          <!-- データなしの場合 -->
          <div v-else class="text-center py-12">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <i class="fas fa-box-open text-gray-400 text-2xl"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">承認済み物品がありません</h3>
            <p class="text-gray-500">この部署の承認済み物品はまだありません。</p>
          </div>
        </div>

        <!-- フッター -->
        <div class="bg-gray-50 px-4 sm:px-6 py-3 sm:py-4 border-t border-gray-200">
          <div class="flex justify-end">
            <button
              @click="closeDepartmentModal"
              class="inline-flex items-center px-3 sm:px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-xs sm:text-sm font-medium rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
            >
              <i class="fas fa-times mr-1 sm:mr-2 text-xs"></i>
              閉じる
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<style scoped lang="scss">
// テーブル全体のスタイル
#order_request_table {
  border-collapse: separate;
  border-spacing: 0;
  // 固定列の幅（調整可）
  --col-1: 80px;   // 緊急度
  --col-2: 100px;  // 画像
  --col-3: 100px;  // 分類
  --col-4: 120px;  // 最終発注日
  --col-5: 220px;  // 品名
  --col-6: 140px;  // 品番

  // 1〜6列目を固定（ヘッダー・ボディ共通の幅指定）
  thead th:nth-child(1), tbody td:nth-child(1) { min-width: var(--col-1); width: var(--col-1); }
  thead th:nth-child(2), tbody td:nth-child(2) { min-width: var(--col-2); width: var(--col-2); }
  thead th:nth-child(3), tbody td:nth-child(3) { min-width: var(--col-3); width: var(--col-3); }
  thead th:nth-child(4), tbody td:nth-child(4) { min-width: var(--col-4); width: var(--col-4); }
  thead th:nth-child(5), tbody td:nth-child(5) { min-width: var(--col-5); width: var(--col-5); }
  thead th:nth-child(6), tbody td:nth-child(6) { min-width: var(--col-6); width: var(--col-6); }

  // 左オフセット（横スクロール固定）
  thead th:nth-child(1), tbody td:nth-child(1) { position: sticky; left: 0; }
  thead th:nth-child(2), tbody td:nth-child(2) { position: sticky; left: var(--col-1); }
  thead th:nth-child(3), tbody td:nth-child(3) { position: sticky; left: calc(var(--col-1) + var(--col-2)); }
  thead th:nth-child(4), tbody td:nth-child(4) { position: sticky; left: calc(var(--col-1) + var(--col-2) + var(--col-3)); }
  thead th:nth-child(5), tbody td:nth-child(5) { position: sticky; left: calc(var(--col-1) + var(--col-2) + var(--col-3) + var(--col-4)); }
  thead th:nth-child(6), tbody td:nth-child(6) { position: sticky; left: calc(var(--col-1) + var(--col-2) + var(--col-3) + var(--col-4) + var(--col-5)); }

  // 固定セルの背景を不透明化（透け防止）。ヘッダー/ボディのデフォルト背景
  thead th:nth-child(-n+6), tbody td:nth-child(-n+6) {
    background-color: #fff;
    background-clip: padding-box;
  }

  // 行ホバー時・状態別の背景同期（固定列にも反映）
  tbody tr:hover td:nth-child(-n+6) { background-color: #f9fafb; } // gray-50
  tbody tr.bg-orange-50 td:nth-child(-n+6) { background-color: #fff7ed; } // orange-50
  tbody tr.bg-orange-50:hover td:nth-child(-n+6) { background-color: #ffedd5; } // orange-100
  tbody tr.bg-gray-100 td:nth-child(-n+6) { background-color: #f3f4f6; } // gray-100
  tbody tr.bg-gray-100:hover td:nth-child(-n+6) { background-color: #e5e7eb; } // gray-200

  // 前面に出すためのz-index（ヘッダーはボディより上）
  tbody td:nth-child(-n+6) { z-index: 3; }
  thead th:nth-child(-n+6) { z-index: 5; }

  // 固定領域の右側を視認しやすくする影
  thead th:nth-child(6), tbody td:nth-child(6) { box-shadow: 2px 0 0 #e5e7eb; }
  
  // レスポンシブ対応
  @media screen and (max-width: 1200px) {
    font-size: 0.875rem;
  }
  
  @media screen and (max-width: 800px) {
    font-size: 0.75rem;
  }
}

// グラデーション背景のアニメーション
.bg-gradient-to-r {
  background-size: 200% 200%;
  animation: gradient 6s ease infinite;
}

@keyframes gradient {
  0% {
    background-position: 0% 50%;
  }
  50% {
    background-position: 100% 50%;
  }
  100% {
    background-position: 0% 50%;
  }
}

// ホバーエフェクト
.hover\:bg-gray-50:hover {
  background-color: #f9fafb;
}

// フォーカス状態のスタイル改善
button:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

// スクロールバーのカスタマイズ
.overflow-x-auto {
  scrollbar-width: thin;
  scrollbar-color: #cbd5e1 #f1f5f9;
  
  &::-webkit-scrollbar {
    height: 8px;
  }
  
  &::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 4px;
  }
  
  &::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
    
    &:hover {
      background: #94a3b8;
    }
  }
}

// 影のカスタマイズ
.shadow-lg {
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

// ボタンのホバーエフェクト改善
button {
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  
  &:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.12);
  }
  
  &:active:not(:disabled) {
    transform: translateY(0);
  }
  
  &:disabled {
    cursor: not-allowed;
    opacity: 0.6;
    transform: none;
    box-shadow: none;
  }
}

// アイコンのアニメーション
i.fas {
  transition: transform 0.2s ease;
  
  &:hover {
    transform: scale(1.1);
  }
}

// モーダルのアニメーション
#description_container {
  backdrop-filter: blur(4px);
  
  .inline-block {
    animation: modalSlideIn 0.3s ease-out;
  }
}

@keyframes modalSlideIn {
  from {
    opacity: 0;
    transform: translateY(20px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

// 承認フローのスクロール
#approval_container {
  &::-webkit-scrollbar {
    height: 6px;
  }
  
  &::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
  }
  
  &::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
    
    &:hover {
      background: #94a3b8;
    }
  }
}

// カードのホバーエフェクト
.hover\:shadow-md:hover {
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

// フォーム要素のフォーカス状態
textarea:focus {
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  border-color: #3b82f6;
}

// モーダルのz-index管理
.z-50 {
  z-index: 50;
}

// 統計カードの横スクロール
.overflow-x-auto {
  &::-webkit-scrollbar {
    height: 8px;
  }
  
  &::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 4px;
  }
  
  &::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
    
    &:hover {
      background: #94a3b8;
    }
  }
}

// 部署詳細モーダルのテーブルスタイル
.department-modal-table {
  border-collapse: separate;
  border-spacing: 0;
  
  th {
    position: sticky;
    top: 0;
    z-index: 10;
    background: linear-gradient(to right, #f9fafb, #f3f4f6);
  }
  
  tr:hover {
    background-color: #eff6ff;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  }
  
  td {
    border-right: 1px solid #e5e7eb;
    transition: all 0.2s ease;
  }
  
  td:last-child {
    border-right: none;
  }
  
  // レスポンシブ対応
  @media screen and (max-width: 1200px) {
    font-size: 0.875rem;
    
    th, td {
      padding: 0.75rem 0.5rem;
    }
  }
  
  @media screen and (max-width: 800px) {
    font-size: 0.75rem;
    
    th, td {
      padding: 0.5rem 0.25rem;
    }
  }
  
  @media screen and (max-width: 640px) {
    font-size: 0.625rem;
    
    th, td {
      padding: 0.375rem 0.125rem;
    }
  }
}

// スマートフォン用の追加スタイル
@media screen and (max-width: 640px) {
  // テーブルの横スクロール改善
  .overflow-x-auto {
    -webkit-overflow-scrolling: touch;
  }
  
  // ボタンのタッチエリア拡大
  button {
    min-height: 44px;
    min-width: 44px;
  }
  
  // モーダルのマージン調整
  .inline-block {
    margin: 0.5rem;
  }
  
  // 統計カードの幅調整
  .flex-shrink-0 {
    min-width: 240px;
  }
}

// ソート可能なヘッダーのスタイル
.cursor-pointer {
  user-select: none;
  
  &:hover {
    background-color: #f9fafb;
  }
  
  &:active {
    background-color: #f3f4f6;
  }
}

// ソートアイコンのアニメーション
.fa-sort,
.fa-sort-up,
.fa-sort-down {
  transition: color 0.2s ease;
}
</style>