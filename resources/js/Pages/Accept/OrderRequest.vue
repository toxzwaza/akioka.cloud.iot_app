<script setup>
import AcceptLayout from "@/Layouts/AcceptLayout.vue";
import { onMounted, ref, reactive } from "vue";
import { getImgPath } from "@/Helper/method";
import ApprovalDocument from "@/Components/Accept/ApprovalDocument.vue";

const props = defineProps({
  user: Object,
  order_requests: Array,
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

const sendAccept = (order_request_approval_id, action) => {
  let status;
  let msg = "";

  const order_request_approval = props.order_requests.find(
    (order_request) =>
      order_request.order_request_approval_id === order_request_approval_id
  );

  if (order_request_approval_id) {
    switch (action) {
      case "accept": //承認
        msg = "承認が完了しました。";
        status = 1;
        break;
      case "reject": //非承認
        if (!order_request_approval.comment) {
          alert("非承認の場合は、コメントを追加してください。");
          openDescription(order_request_approval);
          return;
        }
        status = 2;
        msg = "承認を却下しました。";
        break;
    }

    axios
      .put(route("accept.order-request.update"), {
        order_request_approval_id: order_request_approval_id,
        status: status,
        comment: order_request_approval.comment,
      })
      .then((res) => {
        console.log(res.data);
        if (confirm(msg)) {
          window.location.reload();
        }
      })
      .catch((error) => {});
  }
};

onMounted(() => {
  console.log(props.order_requests);

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
      <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white py-12 mb-8">
        <div class="container mx-auto px-4">
          <div class="text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-white bg-opacity-20 rounded-full mb-4">
              <i class="fas fa-user-check text-2xl"></i>
            </div>
            <h1 class="text-4xl font-bold mb-2">承認システム</h1>
            <p class="text-xl opacity-90 mb-4">承認者：{{ props.user.name }}</p>
            <p class="text-lg opacity-80">以下の発注依頼について承認をお願いします</p>
          </div>
        </div>
      </div>

      <!-- インストラクション -->
      <div class="container mx-auto px-4 mb-8">
        <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded-r-lg">
          <div class="flex items-center">
            <i class="fas fa-info-circle text-blue-400 mr-3"></i>
            <p class="text-blue-800">
              コメントを送信する場合は、<i class="fas fa-comment text-blue-600 mx-1"></i>から追加した後、承認登録を行ってください。
            </p>
          </div>
        </div>
      </div>

      <!-- メインコンテンツ -->
      <section class="container mx-auto px-4 pb-20">
        <!-- テーブルコンテナ -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
          <div class="overflow-x-auto">
            <table
              id="order_request_table"
              class="w-full"
            >
              <thead>
                <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                  <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-r border-gray-200 whitespace-nowrap">
                    <div class="flex items-center">
                      <i class="fas fa-image mr-2 text-gray-400"></i>
                      画像
                    </div>
                  </th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-r border-gray-200 whitespace-nowrap">
                    <div class="flex items-center">
                      <i class="fas fa-tags mr-2 text-gray-400"></i>
                      分類
                    </div>
                  </th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-r border-gray-200 whitespace-nowrap">
                    <div class="flex items-center">
                      <i class="fas fa-calendar-alt mr-2 text-gray-400"></i>
                      最終発注日
                    </div>
                  </th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-r border-gray-200 whitespace-nowrap">
                    品名
                  </th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-r border-gray-200 whitespace-nowrap">
                    品番
                  </th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-r border-gray-200 whitespace-nowrap">
                    必要数量
                  </th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-r border-gray-200 whitespace-nowrap">
                    現在数量
                  </th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-r border-gray-200 whitespace-nowrap">
                    <div class="flex items-center">
                      <i class="fas fa-yen-sign mr-2 text-gray-400"></i>
                      単価
                    </div>
                  </th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-r border-gray-200 whitespace-nowrap">
                    <div class="flex items-center">
                      <i class="fas fa-calculator mr-2 text-gray-400"></i>
                      金額
                    </div>
                  </th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-r border-gray-200 whitespace-nowrap">
                    <div class="flex items-center">
                      <i class="fas fa-building mr-2 text-gray-400"></i>
                      発注先
                    </div>
                  </th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-r border-gray-200 whitespace-nowrap">
                    <div class="flex items-center">
                      <i class="fas fa-user mr-2 text-gray-400"></i>
                      依頼者
                    </div>
                  </th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-r border-gray-200 whitespace-nowrap">
                    <div class="flex items-center">
                      <i class="fas fa-user-tie mr-2 text-gray-400"></i>
                      担当者
                    </div>
                  </th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-r border-gray-200 whitespace-nowrap">
                    <div class="flex items-center">
                      <i class="fas fa-calendar-plus mr-2 text-gray-400"></i>
                      依頼日
                    </div>
                  </th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-r border-gray-200 whitespace-nowrap">
                    <div class="flex items-center">
                      <i class="fas fa-calendar-check mr-2 text-gray-400"></i>
                      消化予定日
                    </div>
                  </th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-r border-gray-200 whitespace-nowrap">
                    <div class="flex items-center">
                      <i class="fas fa-calendar-day mr-2 text-gray-400"></i>
                      希望納期
                    </div>
                  </th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-r border-gray-200 whitespace-nowrap">
                    <div class="flex items-center">
                      <i class="fas fa-comment mr-2 text-gray-400"></i>
                      依頼者備考
                    </div>
                  </th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-r border-gray-200 whitespace-nowrap">
                    <div class="flex items-center">
                      <i class="fas fa-comment-dots mr-2 text-gray-400"></i>
                      発注者備考
                    </div>
                  </th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-r border-gray-200 whitespace-nowrap">
                    <div class="flex items-center">
                      <i class="fas fa-file-alt mr-2 text-gray-400"></i>
                      添付ファイル
                    </div>
                  </th>
                  <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider border-r border-gray-200 whitespace-nowrap">
                    <div class="flex items-center justify-center">
                      <i class="fas fa-search mr-2 text-gray-400"></i>
                      詳細確認
                    </div>
                  </th>
                  <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider whitespace-nowrap">
                    <div class="flex items-center justify-center">
                      <i class="fas fa-stamp mr-2 text-gray-400"></i>
                      承認登録
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
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center justify-center">
                      <img 
                        :src="getImgPath(order_request.img_path)" 
                        alt="商品画像"
                        class="h-16 w-16 object-cover rounded-lg shadow-sm border border-gray-200" 
                      />
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span
                      v-if="order_request.new_stock_flg"
                      class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 border border-emerald-200"
                    >
                      <i class="fas fa-plus-circle mr-1"></i>
                      新規品
                    </span>
                    <span
                      v-else
                      class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800 border border-amber-200"
                    >
                      <i class="fas fa-box mr-1"></i>
                      既存品
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                    <div class="flex items-center">
                      <i class="fas fa-calendar-alt mr-2 text-gray-400"></i>
                    {{
                      order_request.digest_date
                        ? new Date(order_request.last_order_date)
                            .toLocaleDateString("ja-JP", {
                              year: "numeric",
                              month: "2-digit",
                              day: "2-digit",
                            })
                            .replace(/\//g, "/")
                        : "-"
                    }}
                    </div>
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-900">
                    <div class="font-medium truncate max-w-xs" :title="order_request.name">
                    <span v-if="order_request.name.length > 20">
                      {{ order_request.name.substring(0, 20) + "..." }}
                    </span>
                    <span v-else>
                      {{ order_request.name }}
                    </span>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                    <code class="bg-gray-100 px-2 py-1 rounded text-xs">{{ order_request.s_name ?? "-" }}</code>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    <div class="flex items-center">
                      <i class="fas fa-cube mr-2 text-blue-400"></i>
                      <span class="font-semibold">{{
                      `${order_request.quantity ?? ""}${
                        order_request.unit ?? ""
                      }`
                      }}</span>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                    <div class="flex items-center">
                      <i class="fas fa-boxes mr-2 text-gray-400"></i>
                    {{
                      `${order_request.now_quantity ?? "-"}${
                        order_request.now_quantity_unit ?? "-"
                      }`
                    }}
                    </div>
                  </td>

                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    <div class="flex items-center font-semibold">
                      <i class="fas fa-yen-sign mr-2 text-green-500"></i>
                    {{ order_request.price?.toLocaleString() }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    <div class="flex items-center font-bold text-blue-600">
                      <i class="fas fa-calculator mr-2"></i>
                      ¥{{ order_request.calc_price?.toLocaleString() }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    <div class="flex items-center">
                      <i class="fas fa-building mr-2 text-gray-400"></i>
                    {{ order_request.supplier_name }}
                    </div>
                  </td>

                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    <div class="flex items-center">
                      <div class="flex-shrink-0 h-8 w-8">
                        <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                          <i class="fas fa-user text-blue-600 text-xs"></i>
                        </div>
                      </div>
                      <div class="ml-3">
                        <div class="text-sm font-medium text-gray-900">{{ order_request.request_user_name }}</div>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    <div class="flex items-center">
                      <div class="flex-shrink-0 h-8 w-8">
                        <div class="h-8 w-8 rounded-full bg-purple-100 flex items-center justify-center">
                          <i class="fas fa-user-tie text-purple-600 text-xs"></i>
                        </div>
                      </div>
                      <div class="ml-3">
                        <div class="text-sm font-medium text-gray-900">{{ order_request.user_name }}</div>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                    <div class="flex items-center">
                      <i class="fas fa-calendar-plus mr-2 text-gray-400"></i>
                    {{
                      new Date(order_request.created_at)
                        .toLocaleDateString("ja-JP", {
                          year: "numeric",
                          month: "2-digit",
                          day: "2-digit",
                        })
                        .replace(/\//g, "/")
                    }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                    <div class="flex items-center">
                      <i class="fas fa-calendar-check mr-2 text-gray-400"></i>
                    {{
                      order_request.digest_date
                        ? new Date(order_request.digest_date)
                            .toLocaleDateString("ja-JP", {
                              year: "numeric",
                              month: "2-digit",
                              day: "2-digit",
                            })
                            .replace(/\//g, "/")
                        : "-"
                    }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                    <div class="flex items-center">
                      <i class="fas fa-calendar-day mr-2 text-red-400"></i>
                    {{
                      order_request.desire_delivery_date
                        ? new Date(order_request.desire_delivery_date)
                            .toLocaleDateString("ja-JP", {
                              year: "numeric",
                              month: "2-digit",
                              day: "2-digit",
                            })
                            .replace(/\//g, "/")
                        : "-"
                    }}
                    </div>
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-600 max-w-xs">
                    <div class="truncate" :title="order_request.description">
                      <i class="fas fa-comment mr-2 text-gray-400"></i>
                    {{ order_request.description ?? "-" }}
                    </div>
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-600 max-w-xs">
                    <div class="truncate" :title="order_request.sub_description">
                      <i class="fas fa-comment-dots mr-2 text-gray-400"></i>
                    {{ order_request.sub_description ?? "-" }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-center">
                    <span
                      v-if="order_request.file_path || order_request.document_id"
                      class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800"
                    >
                      <i class="fas fa-check mr-1"></i>
                      あり
                    </span>
                    <span
                      v-else
                      class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800"
                    >
                      <i class="fas fa-minus mr-1"></i>
                      未登録
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-center">
                    <button
                      @click="openDescription(order_request)"
                      class="inline-flex items-center px-4 py-2 border-2 border-blue-600 text-sm font-medium rounded-lg text-blue-600 bg-white hover:bg-blue-600 hover:text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-sm hover:shadow-md"
                    >
                      <i class="fas fa-search mr-2"></i>
                      詳細
                    </button>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-center">
                    <div class="flex items-center justify-center space-x-2">
                      <button
                        @click.prevent="
                          sendAccept(
                            order_request.order_request_approval_id,
                            'accept'
                          )
                        "
                        :disabled="getRowStyle(order_request).isDisabled"
                        :class="[
                          'inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md transition-colors duration-200',
                          getRowStyle(order_request).isDisabled 
                            ? 'text-gray-400 bg-gray-300 cursor-not-allowed'
                            : 'text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500'
                        ]"
                      >
                        <i class="fas fa-check mr-1"></i>
                        承認
                      </button>
                      <button
                        @click.prevent="
                          sendAccept(
                            order_request.order_request_approval_id,
                            'reject'
                          )
                        "
                        :disabled="getRowStyle(order_request).isDisabled"
                        :class="[
                          'inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md transition-colors duration-200',
                          getRowStyle(order_request).isDisabled 
                            ? 'text-gray-400 bg-gray-300 cursor-not-allowed'
                            : 'text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500'
                        ]"
                      >
                        <i class="fas fa-times mr-1"></i>
                        却下
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
          <div class="flex justify-center space-x-4 mb-8 p-4 bg-gray-50 rounded-xl">
            <button
              @click.prevent="
                sendAccept(
                  description_order_request.order_request.order_request_approval_id,
                  'accept'
                )
              "
              :disabled="getRowStyle(description_order_request.order_request).isDisabled"
              :class="[
                'flex-1 inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-xl transition-all duration-200',
                getRowStyle(description_order_request.order_request).isDisabled
                  ? 'text-gray-400 bg-gray-300 cursor-not-allowed'
                  : 'text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 hover:transform hover:scale-105'
              ]"
            >
              <i class="fas fa-check mr-2"></i>
              承認する
            </button>
            <button
              @click.prevent="
                sendAccept(
                  description_order_request.order_request.order_request_approval_id,
                  'reject'
                )
              "
              :disabled="getRowStyle(description_order_request.order_request).isDisabled"
              :class="[
                'flex-1 inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-xl transition-all duration-200',
                getRowStyle(description_order_request.order_request).isDisabled
                  ? 'text-gray-400 bg-gray-300 cursor-not-allowed'
                  : 'text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 hover:transform hover:scale-105'
              ]"
            >
              <i class="fas fa-times mr-2"></i>
              却下する
            </button>
          </div>

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
</template>
<style scoped lang="scss">
// テーブル全体のスタイル
#order_request_table {
  border-collapse: separate;
  border-spacing: 0;
  
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
</style>