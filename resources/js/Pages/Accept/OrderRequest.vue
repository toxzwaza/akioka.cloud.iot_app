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
  viewerUrl: '',
  comment: {
    order_request_id: null,
    placeholder: "",
    msg: "",
  }
})

// const comment = reactive({
//   order_request_id: null,
//   placeholder: "",
//   msg: "",
// });

const save_comment = () => {
  const order_request = props.order_requests.find(
    (order_request) => order_request.id === description_order_request.comment.order_request_id
  );
  order_request.comment = description_order_request.comment.msg;

  console.log(props.order_requests);
  alert('コメントを追記しました。')
};

const openDescription = (order_request) => {
  console.log(order_request);

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
    description_order_request.approval_document.document_id = order_request.document_id;
    description_order_request.approval_document.process_name = order_request.request_user_process_name;
    description_order_request.approval_document.user_name = order_request.request_user_name;
    description_order_request.approval_document.evalution_date = order_request.evalution_date;
    description_order_request.approval_document.desire_delivery_date = order_request.desire_delivery_date;
    description_order_request.approval_document.supplier_name = order_request.supplier_name;
    description_order_request.approval_document.price = order_request.price;
    description_order_request.approval_document.quantity = order_request.quantity;
    description_order_request.approval_document.calc_price = order_request.calc_price;
    description_order_request.approval_document.name = order_request.name;
    description_order_request.approval_document.s_name = order_request.s_name;
    description_order_request.approval_document.title = order_request.title;
    description_order_request.approval_document.content = order_request.content;
    description_order_request.approval_document.main_reason = order_request.main_reason;
    description_order_request.approval_document.sub_reason = order_request.sub_reason;
  }
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
          openDescription(order_request_approval)
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
      <div v-if="description_order_request.comment.order_request_id" id="modal_cover"></div>
      <section class="text-gray-600 body-font" style="margin-bottom: 20vh">
        <div class="py-12 mx-auto">
          <div class="flex flex-col text-center w-full mb-20">
            <h1
              class="sm:text-4xl text-3xl font-medium title-font mb-2 text-gray-900"
            >
              承認者：{{ props.user.name }}
            </h1>
            <p class="lg:w-2/3 mx-auto leading-relaxed text-base">
              以下より、承認を行ってください。
            </p>
          </div>
          <div class="w-full mx-auto overflow-auto">
            <h3 class="mb-4">
              コメントを送信する場合は、<i
                class="fa-solid fa-comment text-blue-600 mx-1"
              >
              </i>
              から、追加した後、承認登録を行ってください。
            </h3>
            <table
              id="order_request_table"
              class="table-auto w-full text-left whitespace-no-wrap"
            >
              <thead>
                <tr>
                  <th
                    class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100 rounded-tl rounded-bl"
                  >
                    画像
                  </th>
                  <th
                    class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
                  >
                    分類<i class="ml-1 fa-solid fa-tags"></i>
                  </th>
                  <th
                    class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
                  >
                    最終発注日<i class="ml-1 fa-solid fa-calendar-alt"></i>
                  </th>
                  <th
                    class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
                  >
                    品名
                  </th>
                  <th
                    class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
                  >
                    品番
                  </th>
                  <th
                    class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
                  >
                    必要数量
                  </th>
                  <th
                    class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
                  >
                    現在数量
                  </th>

                  <th
                    class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
                  >
                    単価
                  </th>
                  <th
                    class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
                  >
                    金額
                  </th>
                  <th
                    class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
                  >
                    発注先
                  </th>
                  <th
                    class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
                  >
                    発注依頼者<i class="ml-1 fa-solid fa-user"></i>
                  </th>
                  <th
                    class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
                  >
                    発注担当者<i class="ml-1 fa-solid fa-user"></i>
                  </th>
                  <th
                    class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
                  >
                    発注依頼日<i class="ml-1 fa-solid fa-calendar-alt"></i>
                  </th>
                  <th
                    class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
                  >
                    消化予定日<i class="ml-1 fa-solid fa-calendar-alt"></i>
                  </th>
                  <th
                    class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
                  >
                    希望納期<i class="ml-1 fa-solid fa-calendar-alt"></i>
                  </th>
                  <th
                    class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
                  >
                    依頼者備考<i class="ml-1 fa-solid fa-comment"></i>
                  </th>
                  <th
                    class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
                  >
                    発注者備考<i class="ml-1 fa-solid fa-comment"></i>
                  </th>

                  <th
                    class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100 w-20"
                  >
                    添付ファイル<i class="ml-1 fa-solid fa-file-alt"></i>
                  </th>
                  <th
                    class="w-10 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100 rounded-tr rounded-br"
                  >
                    詳細確認<i class="ml-1 fa-solid fa-comment"></i>
                  </th>
                  <th
                    class="w-10 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100 rounded-tr rounded-br"
                  >
                    承認登録<i class="ml-1 fa-solid fa-stamp"></i>
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="order_request in order_requests"
                  :key="order_request.id"
                >
                  <td class="img">
                    <img :src="getImgPath(order_request.img_path)" alt="" />
                  </td>
                  <td class="px-4 py-8">
                    <span
                      v-if="order_request.new_stock_flg"
                      class="bg-green-100 text-green-800 text-xs font-medium me-2 px-4 py-1 rounded-full dark:bg-green-900 dark:text-green-300"
                      >新規品</span
                    >
                    <span
                      v-else
                      class="bg-yellow-100 text-yellow-800 text-xs font-medium me-2 px-4 py-1 rounded-full dark:bg-yellow-900 dark:text-yellow-300"
                      >既存品</span
                    >
                  </td>
                  <td class="px-4 py-8 text-lg text-gray-900">
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
                  </td>
                  <td class="px-4 py-8 text-lg text-gray-900">
                    <span v-if="order_request.name.length > 20">
                      {{ order_request.name.substring(0, 20) + "..." }}
                    </span>
                    <span v-else>
                      {{ order_request.name }}
                    </span>
                  </td>
                  <td class="px-4 py-8 text-lg text-gray-900">
                    {{ order_request.s_name ?? "-" }}
                  </td>
                  <td class="px-4 py-8 text-lg text-gray-900">
                    {{
                      `${order_request.quantity ?? ""}${
                        order_request.unit ?? ""
                      }`
                    }}
                  </td>
                  <td class="px-4 py-8 text-lg text-gray-900">
                    {{
                      `${order_request.now_quantity ?? "-"}${
                        order_request.now_quantity_unit ?? "-"
                      }`
                    }}
                  </td>

                  <td class="px-4 py-8 text-lg text-gray-900">
                    {{ order_request.price?.toLocaleString() }}
                  </td>
                  <td class="px-4 py-8 text-lg text-gray-900">
                    {{ order_request.calc_price?.toLocaleString() }}
                  </td>
                  <td class="px-4 py-8 text-lg text-gray-900">
                    {{ order_request.supplier_name }}
                  </td>

                  <td class="px-4 py-8 text-lg text-gray-900">
                    {{ order_request.request_user_name }}
                  </td>
                  <td class="px-4 py-8 text-lg text-gray-900">
                    {{ order_request.user_name }}
                  </td>
                  <td class="px-4 py-8 text-lg text-gray-900">
                    {{
                      new Date(order_request.created_at)
                        .toLocaleDateString("ja-JP", {
                          year: "numeric",
                          month: "2-digit",
                          day: "2-digit",
                        })
                        .replace(/\//g, "/")
                    }}
                  </td>
                  <td class="px-4 py-8 text-lg text-gray-900">
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
                  </td>
                  <td class="px-4 py-8 text-lg text-gray-900">
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
                  </td>
                  <td class="px-4 py-8 text-lg text-gray-900 description">
                    {{ order_request.description ?? "-" }}
                  </td>
                  <td class="px-4 py-8 text-lg text-gray-900 description">
                    {{ order_request.sub_description ?? "-" }}
                  </td>
                  <td class="w-10 text-center px-8">
                    <!-- <button
                      v-if="
                        order_request.file_path || order_request.document_id
                      "
                      @click.prevent="openApproval(order_request)"
                      class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                    >
                      稟議書
                    </button> -->

                    <span
                      v-if="
                        order_request.file_path || order_request.document_id
                      "
                      >あり</span
                    >
                    <span v-else>未登録</span>
                  </td>
                  <td class="w-10 text-center px-8">
                    <button
                      class="mr-2 bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                      @click="openDescription(order_request)"
                    >
                      <i class="fa-solid fa-comment"></i>
                    </button>
                  </td>
                  <td class="w-20 text-center">
                    <button
                      @click.prevent="
                        sendAccept(
                          order_request.order_request_approval_id,
                          'accept'
                        )
                      "
                      class="mr-2 bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded"
                    >
                      承認
                    </button>
                    <button
                      @click.prevent="
                        sendAccept(
                          order_request.order_request_approval_id,
                          'reject'
                        )
                      "
                      class="ml-2 bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded"
                    >
                      非承認
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>
    </template>
  </AcceptLayout>

  <div v-if="description_order_request.comment.order_request_id" id="description_container">
    <!-- 稟議書用モーダル -->
    <div id="approval_modal" class="bg-gray-900 bg-opacity-50">
      <div class="">
        <!-- <div class="button_container flex justify-end p-4 bg-gray-800">
          <button
            @click="approval_modal = false"
            class="text-white font-bold py-2 px-4 rounded hover:bg-gray-700"
          >
            閉じる
          </button>
        </div> -->

        <div class="flex flex-col bg-white w-full">
          <div class="mb-4">
            <div id="comment_container" class="w-2/3 mx-auto">
              <div class="flex justify-between items-end mb-2">
                <label
                  for="message"
                  class="w-1/2 block mb-2 text-sm font-medium text-white dark:text-white"
                  >コメント追加</label
                >
                <button
                  @click="description_order_request.comment.order_request_id = 0"
                  class="bg-blue-600 text-white font-bold py-2 px-4 rounded hover:bg-blue-600"
                >
                  <i class="fa-solid fa-times"></i>
                </button>
              </div>

              <textarea
                id="message"
                rows="4"
                class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                :placeholder="description_order_request.comment.placeholder"
                v-model="description_order_request.comment.msg"
                @change="save_comment"
              ></textarea>
            </div>
            <ApprovalDocument :approval_document="description_order_request.approval_document" />
          </div>
          <iframe
            ref="pdfViewer"
            :src="description_order_request.viewerUrl"
            class="w-2/3 mx-auto"
            frameborder="0"
          ></iframe>
        </div>
      </div>
    </div>
  </div>
</template>
<style scoped lang="scss">
#order_request_table {
  // width: 200vw;
  font-size: 0.9rem;

  @media screen and (min-width: 800px) {
    width: 100%;
    font-size: 1rem;

    td {
      img {
        max-width: 100px;
      }
    }
  }

  td,
  th {
    max-width: 20vw;
    white-space: nowrap;
  }

  td {
    &.img {
      min-width: 120px;

      & img {
        height: 100%;
        width: 100%;
        object-fit: contain;
      }
    }

    &.description {
      overflow-x: scroll;
    }
  }
}

#description_container {
  background-color: white;

  padding: 6% 0;
  position: fixed;
  bottom: 5%;
  right: 5%;
  overflow-y: scroll;

  z-index: 50;
  width: 90%;
  height: 80vh;

  #approval_modal {
    height: 60%;

    .button_container {
      border-bottom: 1px solid #4a5568;
    }

    .main_container {
      iframe {
        width: 100%;
        height: 100%;
      }
    }
  }

  & #comment_container {
    height: 20%;
    & textarea {
      height: 100%;
    }
  }
}

#modal_cover {
  position: fixed;
  top: 0;
  left: 0;
  height: 100vh;
  width: 100vw;
  z-index: 49;
  background-color: rgba(0, 0, 0, 0.479);
}
</style>