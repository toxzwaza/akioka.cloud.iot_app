<script setup>
import { onMounted, reactive, ref } from "vue";
import axios from "axios";
import StockLayout from "@/Layouts/StockLayout.vue";

const props = defineProps({
  processes: Array,
  users: Array,
  suppliers: Array,
});

const form = reactive({
  new_approval: 0,
  user_id: 0,
  evaluation_date: null,
  desire_delivery_date: null,
  supplier_name: null,
  price: null,
  quantity: null,
  calc_price: null,
  name: null,
  s_name: null,
  title: "",
  content: null,
  main_reason: null,
  sub_reason: null,

  // 既存品
  // user_id: 0, //依頼者
  // name: null, //品名
  // s_name: null, //品番
  now_quantity: null, //現在個数
  now_quantity_unit: null, //現在個数単位
  digest_date: null, //消化予定日
  // quantity: null, //必要個数
  quantity_unit: null, //必要個数単位
  // desire_delivery_date:null, 希望納期
  description: null, //備考
});

const select_users = ref([]);
const selectedFiles = ref([]);
const previewUrls = ref([]);

const gpt_msg = ref([]);

const handleProcessId = (val) => {
  console.log(val);
  select_users.value = props.users.filter((user) => user.process_id == val);
};

const handleFileSelect = (event) => {
  const files = Array.from(event.target.files);

  if (selectedFiles.value.length + files.length > 5) {
    alert("添付ファイルは最大5つまでです");
    return;
  }

  selectedFiles.value = [...selectedFiles.value, ...files];

  files.forEach((file) => {
    const reader = new FileReader();
    reader.onload = (e) => {
      previewUrls.value.push(e.target.result);
    };
    reader.readAsDataURL(file);
  });
};

const removeFile = (index) => {
  selectedFiles.value.splice(index, 1);
  previewUrls.value.splice(index, 1);
};

const submitForm = () => {
  if (!validateForm()) {
    //バリデーション
    return;
  }

  if (!confirm("上位役職者の承認は完了していますか？")) {
    return;
  }

  const formData = new FormData();

  // 通常のフォームデータを追加
  Object.keys(form).forEach((key) => {
    if (form[key] !== null) {
      formData.append(key, form[key]);
    }
  });

  // ファイルを追加
  selectedFiles.value.forEach((file, index) => {
    formData.append(`files[${index}]`, file);
  });

  // axiosでPOSTリクエストを送信
  axios
    .post("/stock/new-item/store", formData, {
      headers: {
        "Content-Type": "multipart/form-data",
      },
    })
    .then((res) => {
      console.log(res.data);
      // 成功時の処理
      if (res.data.status) {
        alert(res.data.msg);
        // フォームをリセット
        Object.keys(form).forEach((key) => {
          form[key] = null;
        });
        selectedFiles.value = [];
        previewUrls.value = [];
      } else {
        alert("失敗しました。");
        console.log(res.data.msg)
      }
    })
    .catch((error) => {
      // エラー時の処理
      console.error("Error:", error);
      alert("エラーが発生しました。もう一度お試しください。");
    });
};

const validateForm = () => {
  if (form.new_approval) {
    if (!form.supplier_name) {
      alert("発注先を入力してください。");
      return false;
    }

    if (!form.price) {
      alert("単価入力してください。");
      return false;
    }
    if (!form.quantity) {
      alert("数量を入力してください。");
      return false;
    }
    if (!form.calc_price) {
      alert("金額を入力してください。");
      return false;
    }

    if (!form.title) {
      alert("タイトルを入力してください");
      return false;
    }

    if (!form.content) {
      alert("発注内容を入力してください");
      return false;
    }
    if (!form.main_reason) {
      alert("申請理由を入力してください");
      return false;
    }
    if (!form.sub_reason) {
      alert("選定理由を入力してください");
      return false;
    }

    if (!form.evaluation_date) {
      alert("評価予定日を選択してください");
      return false;
    }

    // ファイルのバリデーション
    if (selectedFiles.value.length === 0) {
      alert("物品のイメージ画像を添付してください。");
      return false;
    }
  }

  if (!form.user_id) {
    alert("起案者を選択してください");
    return false;
  }

  if (!form.desire_delivery_date) {
    alert("希望納入日を選択してください");
    return false;
  }

  if (!form.name) {
    alert("品名を入力してください");
    return false;
  }

  if (!form.s_name) {
    alert("品番を入力してください");
    return false;
  }

  if (form.now_quantity === null || form.now_quantity === undefined) {
    alert("現在個数を入力してください");
    return false;
  }
  if (!form.now_quantity_unit) {
    alert("現在個数単位を入力してください");
    return false;
  }
  if (!form.digest_date) {
    alert("消化予定日を入力してください");
    return false;
  }
  if (!form.quantity_unit) {
    alert("必要個数単位を入力してください");
    return false;
  }

  return true;
};

const handleChatGpt = (flg) => {
  let message = "";
  let target = "";
  console.log(flg);

  switch (flg) {
    case "title":
      target = "件名";
      message = `${form.title}`;
      break;
    case "content":
      target = "発注内容";
      message = `${form.content}`;
      break;
    case "main_reason":
      target = "申請理由";
      message = `${form.main_reason}`;
      break;
    case "sub_reason":
      target = "選定理由";
      message = `${form.sub_reason}`;
      break;
  }

  if (message) {
    axios
      .post(route("chatgpt.api"), {
        message: `項目名(${target})についてのテキスト → ${message}`,
      })
      .then((res) => {
        console.log(res.data);
        push_gpt_msg(nl2br(res.data.choices[0].message.content));
      })
      .catch((error) => {
        console.log(error);
      });
  }
};

const nl2br = (text) => {
  if (!text) return "";
  // 文字列型でない場合は文字列に変換
  const textStr = String(text);
  const escapedText = textStr
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
  return escapedText.replace(/\n/g, "<br>");
};

const push_gpt_msg = (msg) => {
  gpt_msg.value.push({
    time: new Date().toLocaleTimeString("ja-JP", {
      hour: "2-digit",
      minute: "2-digit",
    }),
    text: msg,
  });
};

onMounted(() => {
  select_users.value = props.users;
  push_gpt_msg(
    nl2br(
      "稟議書作成AIアシスタントです！\n入力頂いた内容についてアドバイスをおこないます。"
    )
  );
});
</script>
<template>
  <StockLayout :title="'在庫管理システム'">
    <template #content>
      <ul class="flex border-b">
        <li class="-mb-px mr-1">
          <button
            @click="form.new_approval = 0"
            :class="{
              'inline-block border-l border-t border-r rounded-t py-2 px-4 font-semibold': true,
              'bg-gray-50  text-blue-700': form.new_approval,
              ' bg-blue-700 text-white': !form.new_approval,
            }"
          >
            既存品
          </button>
        </li>
        <li class="mr-1">
          <button
            @click="form.new_approval = 1"
            :class="{
              'inline-block border-l border-t border-r rounded-t py-2 px-4 font-semibold': true,
              'bg-gray-50  text-blue-700': !form.new_approval,
              ' bg-blue-700 text-white': form.new_approval,
            }"
          >
            新規品
          </button>
        </li>
      </ul>

      <form class="w-full mt-8">
        <h1 class="text-center text-3xl mb-4 text-gray-700 font-bold">
          {{ form.new_approval ? "新規品稟議書" : "既存品依頼" }}
        </h1>
        <div v-if="form.new_approval">
          <h1 class="text-4xl font-bold text-red-500 mt-8 text-center">
            準備中...
          </h1>
          <p class="text-center mt-4">
            実装完了まで、もうしばらくお待ちください。
          </p>
          <div v-if="false" class="flex justify-between items-start">
            <div class="w-2/3">
              <div class="flex flex-wrap -mx-3 mb-8">
                <div class="w-full md:w-1/2 px-3 mb-6 md:mb-0">
                  <label
                    class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2"
                    for="grid-first-name"
                  >
                    起案部門
                  </label>
                  <select
                    name=""
                    id=""
                    class="appearance-none block w-full bg-gray-200 text-gray-700 border border-transparent rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white"
                    @change="handleProcessId($event.target.value)"
                  >
                    <option value="0">未選択</option>
                    <option
                      v-for="process in props.processes"
                      :key="process.id"
                      :value="process.id"
                    >
                      {{ process.name }}
                    </option>
                  </select>
                </div>
                <div class="w-full md:w-1/2 px-3">
                  <label
                    class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2"
                    for="grid-last-name"
                  >
                    起案者
                  </label>
                  <select
                    name=""
                    id=""
                    class="appearance-none block w-full bg-gray-200 text-gray-700 border border-transparent rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white"
                    v-model="form.user_id"
                  >
                    <option value="0">未選択</option>
                    <option
                      v-for="user in select_users"
                      :key="user.id"
                      :value="user.id"
                    >
                      {{ user.name }}
                    </option>
                  </select>
                </div>
              </div>

              <div class="flex flex-wrap -mx-3 mb-8">
                <div class="w-1/3 px-3 mb-6 md:mb-0">
                  <label
                    class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2"
                    for="grid-city"
                  >
                    評価予定日
                    <i class="fas fa-question-circle"></i>
                  </label>
                  <input
                    class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                    id="grid-city"
                    type="date"
                    v-model="form.evaluation_date"
                  />
                </div>
                <div class="w-1/3 px-3 mb-6 md:mb-0">
                  <label
                    class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2"
                    for="grid-city"
                  >
                    希望納入日
                  </label>
                  <input
                    class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                    id="grid-city"
                    type="date"
                    v-model="form.desire_delivery_date"
                  />
                </div>
                <div class="w-1/3 px-3 mb-6 md:mb-0">
                  <label
                    class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2"
                    for="grid-state"
                  >
                    発注先
                  </label>
                  <div class="relative">
                    <input
                      class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                      id="grid-zip"
                      type="text"
                      list="supplier-list"
                      v-model="form.supplier_name"
                    />
                    <datalist id="supplier-list">
                      <option
                        v-for="supplier in suppliers"
                        :key="supplier.id"
                        :value="supplier.name"
                      >
                        {{ supplier.name }}
                      </option>
                    </datalist>
                  </div>
                </div>
              </div>

              <div class="flex flex-wrap -mx-3 mb-8">
                <div class="w-1/3 px-3 mb-6 md:mb-0">
                  <label
                    class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2"
                    for="grid-city"
                  >
                    単価
                  </label>
                  <input
                    class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                    id="grid-city"
                    type="number"
                    v-model="form.price"
                  />
                </div>
                <div class="w-1/3 px-3 mb-6 md:mb-0">
                  <label
                    class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2"
                    for="grid-state"
                  >
                    数量
                  </label>
                  <div class="relative">
                    <input
                      class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                      id="grid-zip"
                      type="number"
                      v-model="form.quantity"
                      @change="form.calc_price = form.price * form.quantity"
                    />
                  </div>
                </div>
                <div class="w-1/3 px-3 mb-6 md:mb-0">
                  <label
                    class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2"
                    for="grid-state"
                  >
                    金額
                  </label>
                  <div class="relative">
                    <input
                      class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                      id="grid-zip"
                      type="number"
                      v-model="form.calc_price"
                    />
                  </div>
                </div>
              </div>
              <div class="flex flex-wrap -mx-3 mb-8">
                <div class="w-1/2 px-3 mb-6 md:mb-0">
                  <label
                    class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2"
                    for="grid-city"
                  >
                    品名
                  </label>
                  <input
                    class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                    id="grid-city"
                    type="text"
                    v-model="form.name"
                  />
                </div>
                <div class="w-1/2 px-3 mb-6 md:mb-0">
                  <label
                    class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2"
                    for="grid-state"
                  >
                    品番
                  </label>
                  <div class="relative">
                    <input
                      class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                      id="grid-zip"
                      type="text"
                      v-model="form.s_name"
                    />
                  </div>
                </div>
              </div>
              <div class="flex flex-wrap -mx-3 mb-8">
                <div class="w-full px-3 mb-6 md:mb-0">
                  <label
                    class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2"
                    for="grid-first-name"
                  >
                    件名
                  </label>
                  <input
                    type="text"
                    class="appearance-none block w-full bg-gray-200 text-gray-700 border border-transparent rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white"
                    v-model="form.title"
                    placeholder="○○における○○の○○（例：備品管理における発注業務の効率化）"
                    @change="handleChatGpt('title')"
                  />
                </div>
              </div>
              <div class="flex flex-wrap -mx-3 mb-8">
                <div class="w-full px-3 mb-6 md:mb-0">
                  <label
                    class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2"
                    for="grid-first-name"
                  >
                    発注内容
                  </label>
                  <textarea
                    name=""
                    id=""
                    cols="30"
                    rows="10"
                    class="appearance-none block w-full bg-gray-200 text-gray-700 border border-transparent rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white"
                    v-model="form.content"
                    @change="handleChatGpt('content')"
                  ></textarea>
                </div>
              </div>

              <div class="flex flex-wrap -mx-3 mb-8">
                <div class="w-full px-3 mb-6 md:mb-0">
                  <label
                    class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2"
                    for="grid-first-name"
                  >
                    申請理由
                  </label>
                  <textarea
                    name=""
                    id=""
                    cols="30"
                    rows="10"
                    class="appearance-none block w-full bg-gray-200 text-gray-700 border border-transparent rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white"
                    v-model="form.main_reason"
                    @change="handleChatGpt('main_reason')"
                  ></textarea>
                </div>
              </div>

              <div class="flex flex-wrap -mx-3 mb-8">
                <div class="w-full px-3 mb-6 md:mb-0">
                  <label
                    class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2"
                    for="grid-first-name"
                  >
                    選定理由
                  </label>
                  <textarea
                    name=""
                    id=""
                    cols="30"
                    rows="10"
                    class="appearance-none block w-full bg-gray-200 text-gray-700 border border-transparent rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white"
                    v-model="form.sub_reason"
                    @change="handleChatGpt('sub_reason')"
                  ></textarea>
                </div>
              </div>

              <p
                class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2"
              >
                添付ファイルがある場合は以下より添付してください。（最大5つまで）
              </p>
              <ul class="mt-4 list-disc list-inside text-sm text-gray-600">
                <li class="mb-1">・購入物品の画像</li>
                <li class="mb-1">・承認済み稟議書(承認をスキップできます)</li>
                <li class="mb-1">・その他参考資料 etc...</li>
              </ul>
              <div class="flex items-center justify-center w-full">
                <label
                  for="dropzone-file"
                  class="flex flex-col items-center justify-center w-full h-64 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 dark:hover:bg-gray-800 dark:bg-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:hover:border-gray-500 dark:hover:bg-gray-600"
                >
                  <div class="flex flex-col items-center justify-center p-8">
                    <svg
                      class="w-8 h-8 mb-4 text-gray-500 dark:text-gray-400"
                      aria-hidden="true"
                      xmlns="http://www.w3.org/2000/svg"
                      fill="none"
                      viewBox="0 0 20 16"
                    >
                      <path
                        stroke="currentColor"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2"
                      />
                    </svg>
                    <p class="mb-2 text-sm text-gray-500 dark:text-gray-400">
                      添付資料
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                      PNG, JPG, PDF
                    </p>
                  </div>
                  <input
                    id="dropzone-file"
                    type="file"
                    class="hidden"
                    multiple
                    accept="image/*,.pdf"
                    @change="handleFileSelect"
                  />
                </label>
              </div>

              <!-- プレビュー表示エリア -->
              <div
                v-if="previewUrls.length > 0"
                class="mt-4 flex justify-around items-center flex-wrap gap-4"
              >
                <div
                  v-for="(url, index) in previewUrls"
                  :key="index"
                  class="relative w-48"
                >
                  <div class="border rounded-lg p-2 h-48">
                    <img
                      v-if="selectedFiles[index].type.startsWith('image/')"
                      :src="url"
                      class="w-full h-32 object-cover"
                      alt="プレビュー画像"
                    />
                    <div
                      v-else
                      class="flex items-center justify-center h-32 bg-gray-100"
                    >
                      <span class="text-gray-500">PDFファイル</span>
                    </div>

                    <button
                      @click.prevent="removeFile(index)"
                      class="absolute top-2 right-4 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center"
                    >
                      ×
                    </button>
                    <p class="text-normal mt-1 pl-8 truncate font-bold">
                      {{ selectedFiles[index].name }}
                    </p>
                  </div>
                </div>
              </div>

              <button
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold mt-12 px-4 rounded w-full py-4"
                @click.prevent="submitForm"
              >
                確定
              </button>
            </div>

            <div class="w-1/3 p-4 ml-4" id="right_container">
              <h2 class="text-center mb-8 font-bold text-xl text-gray-700">
                AI 稟議書作成アドバイス
              </h2>

              <div>
                <div
                  v-for="msg in gpt_msg"
                  :key="msg.id"
                  class="flex items-start gap-2.5 my-8"
                >
                  <img
                    class="w-8 h-8 rounded-full"
                    src="/images/stocks/ai_asistant.png"
                    alt="Jese image"
                  />

                  <div class="flex flex-col gap-1 w-full max-w-[320px] pl-2">
                    <div
                      class="flex items-center space-x-2 rtl:space-x-reverse"
                    >
                      <span
                        class="text-sm font-semibold text-gray-900 dark:text-white"
                        >稟議書作成アシスタント</span
                      >
                      <span
                        class="text-sm font-normal text-gray-500 dark:text-gray-400 ml-2"
                        >{{ msg.time }}</span
                      >
                    </div>
                    <div
                      class="flex flex-col leading-1.5 py-4 border-gray-200 bg-gray-100 rounded-e-xl rounded-es-xl dark:bg-gray-700"
                    >
                      <p
                        class="text-sm font-normal text-gray-900 dark:text-white"
                        v-html="msg.text"
                      ></p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div v-else class="flex justify-between items-start">
          <div class="w-full">
            <div class="flex flex-wrap -mx-3 mb-8">
              <div class="w-full md:w-1/2 px-3 mb-6 md:mb-0">
                <label
                  class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2"
                  for="grid-first-name"
                >
                  部門
                </label>
                <select
                  name=""
                  id=""
                  class="appearance-none block w-full bg-gray-200 text-gray-700 border border-transparent rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white"
                  @change="handleProcessId($event.target.value)"
                >
                  <option value="0">未選択</option>
                  <option
                    v-for="process in props.processes"
                    :key="process.id"
                    :value="process.id"
                  >
                    {{ process.name }}
                  </option>
                </select>
              </div>
              <div class="w-full md:w-1/2 px-3">
                <label
                  class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2"
                  for="grid-last-name"
                >
                  依頼者
                </label>
                <select
                  name=""
                  id=""
                  class="appearance-none block w-full bg-gray-200 text-gray-700 border border-transparent rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white"
                  v-model="form.user_id"
                >
                  <option value="0">未選択</option>
                  <option
                    v-for="user in select_users"
                    :key="user.id"
                    :value="user.id"
                  >
                    {{ user.name }}
                  </option>
                </select>
              </div>
            </div>

            <div class="flex flex-wrap -mx-3 mb-8">
              <div class="w-1/2 px-3 mb-6 md:mb-0">
                <label
                  class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2"
                  for="grid-city"
                >
                  品名
                </label>
                <input
                  class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                  id="grid-city"
                  type="text"
                  v-model="form.name"
                />
              </div>
              <div class="w-1/2 px-3 mb-6 md:mb-0">
                <label
                  class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2"
                  for="grid-state"
                >
                  品番
                </label>
                <div class="relative">
                  <input
                    class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                    id="grid-zip"
                    type="text"
                    v-model="form.s_name"
                  />
                </div>
              </div>
            </div>

            <div class="flex flex-wrap -mx-3 mb-8">
              <div class="w-1/4 px-3 mb-6 md:mb-0">
                <label
                  class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2"
                  for="grid-city"
                >
                  現在個数
                </label>
                <input
                  class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                  id="grid-city"
                  type="number"
                  v-model="form.now_quantity"
                />
              </div>
              <div class="w-1/4 px-3 mb-6 md:mb-0">
                <label
                  class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2"
                  for="grid-city"
                >
                  単位
                </label>
                <input
                  v-model="form.now_quantity_unit"
                  type="text"
                  name=""
                  id=""
                  class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                />
              </div>

              <div class="w-1/2 px-3 mb-6 md:mb-0">
                <label
                  class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2"
                  for="grid-state"
                >
                  消化予定日
                </label>
                <input
                  class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                  id="grid-city"
                  type="date"
                  v-model="form.digest_date"
                />
              </div>
            </div>
            <div class="flex flex-wrap -mx-3 mb-8">
              <div class="w-1/4 px-3 mb-6 md:mb-0">
                <label
                  class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2"
                  for="grid-city"
                >
                  必要数量
                </label>
                <input
                  class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                  id="grid-city"
                  type="number"
                  v-model="form.quantity"
                />
              </div>
              <div class="w-1/4 px-3 mb-6 md:mb-0">
                <label
                  class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2"
                  for="grid-city"
                >
                  単位
                </label>
                <input
                  v-model="form.quantity_unit"
                  type="text"
                  name=""
                  id=""
                  class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                />
              </div>

              <div class="w-1/2 px-3 mb-6 md:mb-0">
                <label
                  class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2"
                  for="grid-state"
                >
                  希望納期
                </label>
                <input
                  class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                  id="grid-city"
                  type="date"
                  v-model="form.desire_delivery_date"
                />
                <p class="mt-2 text-red-500 text-xs italic">
                  リードタイムの都合上難しい場合がございます。
                </p>
              </div>
            </div>

            <div class="w-full">
              <label
                class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2"
                for="grid-password"
              >
                備考（使用用途を記載）
              </label>
              <textarea
                name=""
                id=""
                cols="30"
                rows="9"
                v-model="form.description"
                class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
              ></textarea>
            </div>

            <button
              class="bg-blue-500 hover:bg-blue-700 text-white font-bold mt-12 px-4 rounded w-full py-4"
              @click.prevent="submitForm"
            >
              確定
            </button>
          </div>
        </div>
      </form>

      <hr class="mt-8 mb-4">
      <h1 class="font-bold text-gray-700 text-xl text-center">物品依頼状況</h1>

    </template>
  </StockLayout>
</template>
<style lang="scss" scoped>
#right_container {
  position: sticky;
  top: 3%;
  height: 100vh;
  overflow-y: scroll;

  &::-webkit-scrollbar {
    width: 8px;
  }

  &::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
  }

  &::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
    transition: background 0.3s ease;

    &:hover {
      background: #555;
    }
  }
}
</style>
