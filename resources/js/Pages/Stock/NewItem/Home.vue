<script setup>
import { onMounted, reactive, ref } from "vue";
import axios from "axios";
import StockLayout from "@/Layouts/StockLayout.vue";

const props = defineProps({
  processes: Array,
  users: Array,
  suppliers: Array,
  order_request: Object,
});

const form = reactive({
  new_approval: 0, //新規品フラグ
  before_order_request_id: null, //引き継ぎ元の発注依頼ID
  user_id: 0,
  evaluation_date: null,
  desire_delivery_date: null,
  calc_price: null, //稟議合計金額

  name: null,
  s_name: null,
  supplier_name: null,
  price: null,
  quantity: null,
  approval_stocks: [], // 新規品依頼用物品配列

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
  device_name: null,
});

const select_users = ref([]);
const selectedFiles = ref([]);
const previewUrls = ref([]);

const gpt_msg = ref([]);

const handleProcessId = (val) => {
  console.log(val);
  select_users.value = props.users.filter((user) => user.process_id == val);
};

// 日本の祝日を計算式で判定（フォールバック用）
const isJapaneseHolidayByCalculation = (date) => {
  const year = date.getFullYear();
  const month = date.getMonth() + 1;
  const day = date.getDate();
  
  // 固定祝日
  const fixedHolidays = [
    { month: 1, day: 1 },      // 元日
    { month: 2, day: 11 },     // 建国記念の日
    { month: 4, day: 29 },     // 昭和の日
    { month: 5, day: 3 },      // 憲法記念日
    { month: 5, day: 4 },      // みどりの日
    { month: 5, day: 5 },      // こどもの日
    { month: 8, day: 11 },     // 山の日
    { month: 11, day: 3 },     // 文化の日
    { month: 11, day: 23 },    // 勤労感謝の日
  ];
  
  for (const holiday of fixedHolidays) {
    if (month === holiday.month && day === holiday.day) {
      return true;
    }
  }
  
  // 天皇誕生日
  if (year >= 2020 && month === 2 && day === 23) {
    return true; // 令和の天皇誕生日
  } else if (year >= 1989 && year <= 2018 && month === 12 && day === 23) {
    return true; // 平成の天皇誕生日
  }
  
  // 春分の日（2000-2099年）
  const springEquinox = getSpringEquinox(year);
  if (month === 3 && day === springEquinox) return true;
  
  // 秋分の日（2000-2099年）
  const autumnEquinox = getAutumnEquinox(year);
  if (month === 9 && day === autumnEquinox) return true;
  
  // 移動祝日
  // 海の日（7月の第3月曜日、2020年は7月23日、2021年は7月22日）
  if (year === 2020 && month === 7 && day === 23) return true;
  if (year === 2021 && month === 7 && day === 22) return true;
  if (year !== 2020 && year !== 2021 && month === 7 && day === getNthMonday(year, 7, 3)) return true;
  
  // 敬老の日（9月の第3月曜日）
  if (month === 9 && day === getNthMonday(year, 9, 3)) return true;
  
  // スポーツの日（10月の第2月曜日、2020年は7月24日、2021年は7月23日）
  if (year === 2020 && month === 7 && day === 24) return true;
  if (year === 2021 && month === 7 && day === 23) return true;
  if (year !== 2020 && year !== 2021 && month === 10 && day === getNthMonday(year, 10, 2)) return true;
  
  return false;
};

// 春分の日を計算（2000-2099年）
const getSpringEquinox = (year) => {
  if (year >= 2000 && year <= 2099) {
    const base = Math.floor((year - 2000) * 0.242194) + Math.floor((year - 2000) / 4);
    return 20 + base;
  }
  return 20;
};

// 秋分の日を計算（2000-2099年）
const getAutumnEquinox = (year) => {
  if (year >= 2000 && year <= 2099) {
    const base = Math.floor((year - 2000) * 0.242194) + Math.floor((year - 2000) / 4);
    return 23 + base;
  }
  return 23;
};

// 第N月曜日を取得
const getNthMonday = (year, month, n) => {
  const firstDay = new Date(year, month - 1, 1);
  const firstDayOfWeek = firstDay.getDay();
  const firstMonday = firstDayOfWeek === 0 ? 2 : (9 - firstDayOfWeek) % 7 || 7;
  return firstMonday + (n - 1) * 7;
};

// 日本の祝日APIを使用して祝日を判定
const isJapaneseHoliday = async (dateString) => {
  if (!dateString) return false;
  
  const date = new Date(dateString + "T00:00:00");
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  const dateStr = `${year}-${month}-${day}`;
  
  try {
    // 日本の祝日API（無料・メンテナンス不要）
    const response = await axios.get(`https://holidays-jp.github.io/api/v1/${year}/date.json`);
    const holidays = response.data;
    
    // 指定された日付が祝日かどうかをチェック
    if (holidays && holidays.hasOwnProperty(dateStr)) {
      return true;
    }
    
    // APIにデータがない場合は計算式で判定
    return isJapaneseHolidayByCalculation(date);
  } catch (error) {
    console.error("祝日APIの取得に失敗しました:", error);
    // APIが失敗した場合は計算式で判定
    return isJapaneseHolidayByCalculation(date);
  }
};

// 土日祝を判定する関数
const isWeekendOrHoliday = async (dateString) => {
  if (!dateString) return false;
  
  const date = new Date(dateString + "T00:00:00");
  const dayOfWeek = date.getDay();
  
  // 土曜日（6）または日曜日（0）
  if (dayOfWeek === 0 || dayOfWeek === 6) {
    return true;
  }
  
  // 祝日
  const isHoliday = await isJapaneseHoliday(dateString);
  return isHoliday;
};

// 次の平日を取得する関数
const getNextWeekday = async (dateString) => {
  if (!dateString) return null;
  
  let date = new Date(dateString + "T00:00:00");
  let attempts = 0;
  const maxAttempts = 30; // 無限ループ防止
  
  while (attempts < maxAttempts) {
    const dateStr = date.toISOString().split("T")[0];
    const isHoliday = await isWeekendOrHoliday(dateStr);
    
    if (!isHoliday) {
      return dateStr;
    }
    
    date.setDate(date.getDate() + 1);
    attempts++;
  }
  
  return date.toISOString().split("T")[0];
};

// 希望納期変更時の処理
const handleDesireDeliveryDateChange = async (event) => {
  const selectedDate = event.target.value;
  
  if (!selectedDate) {
    return;
  }
  
  const isHoliday = await isWeekendOrHoliday(selectedDate);
  
  if (isHoliday) {
    const nextWeekday = await getNextWeekday(selectedDate);
    alert("土日祝は選択できません。次の平日に自動設定します。");
    form.desire_delivery_date = nextWeekday;
  }
};

const handleFileSelect = (event) => {
  const files = Array.from(event.target.files);
  const currentPdfCount = selectedFiles.value.filter(
    (file) => file.type === "application/pdf"
  ).length;
  const newPdfCount = files.filter(
    (file) => file.type === "application/pdf"
  ).length;

  if (selectedFiles.value.length + files.length > 5) {
    alert("添付ファイルは合計で最大5つまでです");
    return;
  }

  if (currentPdfCount + newPdfCount > 1) {
    alert("PDFファイルは1つまでです");
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

const submitForm = (type = null) => {
  if (!validateForm()) {
    //バリデーション
    return;
  }

  // new_approvalの場合、未追加の物品データがないかチェック
  if (type === "new_approval") {
    if (
      form.name ||
      form.s_name ||
      form.supplier_name ||
      form.price ||
      form.quantity
    ) {
      alert(
        "未登録の物品データがあります。追加ボタンを押してから確定してください。"
      );
      return;
    }
  }


  if (!confirm("上位役職者の承認は完了していますか？")) {
    return;
  }

  const formData = new FormData();

  // 通常のフォームデータを追加
  Object.keys(form).forEach((key) => {
    if (form[key] !== null) {
      if (key === "approval_stocks" && Array.isArray(form[key])) {
        // 配列の場合はJSON文字列として送信
        formData.append(key, JSON.stringify(form[key]));
      } else {
        formData.append(key, form[key]);
      }
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
        console.log(res.data.msg);
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
    // if (!form.supplier_name) {
    //   alert("発注先を入力してください。");
    //   return false;
    // }

    // if (!form.price) {
    //   alert("単価入力してください。");
    //   return false;
    // }
    // if (!form.quantity) {
    //   alert("数量を入力してください。");
    //   return false;
    // }
    // if (!form.calc_price) {
    //   alert("金額を入力してください。");
    //   return false;
    // }

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

    // if (!form.evaluation_date) {
    //   alert("評価予定日を選択してください");
    //   return false;
    // }

    // ファイルのバリデーション
    // if (selectedFiles.value.length === 0) {
    //   alert("物品のイメージ画像を添付してください。");
    //   return false;
    // }
  } else {
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
  }

  if (!form.user_id) {
    alert("起案者を選択してください");
    return false;
  }

  if (!form.desire_delivery_date) {
    alert("希望納入日を選択してください");
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

const stockEdit = ref(null);
const editStock = (index) => {
  console.log(index);
  stockEdit.value = index;
  const stock = form.approval_stocks[index];
  console.log(stock);
  form.name = stock.name;
  form.s_name = stock.s_name;
  form.supplier_name = stock.supplier_name;
  form.price = stock.price;
  form.quantity = stock.quantity;
};
const deleteStock = (index) => {
  form.approval_stocks.splice(index, 1);
};
const createApprovalStocks = () => {
  if (
    form.name &&
    form.s_name &&
    form.supplier_name &&
    form.price &&
    form.quantity
  ) {
    form.approval_stocks.push({
      name: form.name,
      s_name: form.s_name,
      supplier_name: form.supplier_name,
      price: form.price,
      quantity: form.quantity,
      calc_price: form.calc_price,
    });

    form.name = null;
    form.s_name = null;
    form.supplier_name = null;
    form.price = null;
    form.quantity = null;
  }
};
const saveApprovalStocks = () => {
  const stock = form.approval_stocks[stockEdit.value];
  console.log(stockEdit.value, stock);
  stock.name = form.name;
  stock.s_name = form.s_name;
  stock.supplier_name = form.supplier_name;
  stock.price = form.price;
  stock.quantity = form.quantity;

  form.name = null;
  form.s_name = null;
  form.supplier_name = null;
  form.price = null;
  form.quantity = null;
  stockEdit.value = null;
};
onMounted(async () => {
  // デバイスID取得
  const savedId = localStorage.getItem("device_id");
  if (savedId && savedId != "null") {
    form.device_name = savedId;
    console.log(form.device_name);
  }

  select_users.value = props.users;
  push_gpt_msg(
    nl2br(
      "稟議書作成AIアシスタントです！\n入力頂いた内容についてアドバイスをおこないます。"
    )
  );

  if (props.order_request.new_stock_flg) {
    form.new_approval = 1;
    form.user_id = props.order_request.request_user_id;
    
    // 希望納期が土日祝の場合は次の平日に設定
    if (props.order_request.desire_delivery_date) {
      const isHoliday = await isWeekendOrHoliday(props.order_request.desire_delivery_date);
      if (isHoliday) {
        form.desire_delivery_date = await getNextWeekday(props.order_request.desire_delivery_date);
      } else {
        form.desire_delivery_date = props.order_request.desire_delivery_date;
      }
    }
    
    form.calc_price = props.order_request.calc_price;
    form.title = props.order_request.title;
    form.content = props.order_request.content;
    form.main_reason = props.order_request.main_reason;
    form.sub_reason = props.order_request.sub_reason;
    
    // 新規品の場合、order_requestのデータで物品を自動追加
    if (props.order_request.stock_name && props.order_request.stock_s_name && props.order_request.stock_supplier_name && props.order_request.price && props.order_request.quantity) {
      form.approval_stocks.push({
        name: props.order_request.stock_name,
        s_name: props.order_request.stock_s_name,
        supplier_name: props.order_request.stock_supplier_name,
        price: props.order_request.price,
        quantity: props.order_request.quantity,
        calc_price: props.order_request.price * props.order_request.quantity,
      });
      console.log('自動追加')
    }
  } else {
    form.new_approval = 0;
  }
  form.before_order_request_id = props.order_request.id;
  console.log('props.order_request:', props.order_request);
  console.log('new_stock_flg:', props.order_request.new_stock_flg);
  console.log('name:', props.order_request.name);
  console.log('s_name:', props.order_request.s_name);
  console.log('stock_supplier_name:', props.order_request.stock_supplier_name);
  console.log('price:', props.order_request.price);
  console.log('quantity:', props.order_request.quantity);
});
</script>
<template>
  <StockLayout :title="'在庫管理システム'">
    <template #content>
      <p class="text-gray-700 mb-4 text-left ml-4">
        device_id :{{ form.device_name }}
      </p>

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
        
        <!-- 再依頼モードの表示 -->
        <div v-if="form.before_order_request_id" class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
              </svg>
            </div>
            <div class="ml-3">
              <p class="text-sm font-medium">
                <strong>再依頼モード</strong>
              </p>
              <p class="mt-1 text-sm">
                発注依頼ID: {{ form.before_order_request_id }} から引き継いだ内容で再依頼を行います。
              </p>
            </div>
          </div>
        </div>
        <div v-if="form.new_approval">
          <!-- <h1 class="text-4xl font-bold text-red-500 mt-8 text-center">
            準備中...
          </h1>
          <p class="text-center mt-4">
            実装完了まで、もうしばらくお待ちください。
          </p> -->
          <div class="flex justify-between items-start">
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
                <!-- <div class="w-1/3 px-3 mb-6 md:mb-0">
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
                </div> -->
                <div class="w-1/2 px-3 mb-6 md:mb-0">
                  <label
                    class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2"
                    for="desire_delivery_date_new"
                  >
                    希望納入日
                  </label>
                  <input
                    class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                    id="desire_delivery_date_new"
                    type="date"
                    v-model="form.desire_delivery_date"
                    @change="handleDesireDeliveryDateChange"
                  />
                  <p class="mt-2 text-red-500 text-xs italic">
                    土日祝は選択できません。
                  </p>
                </div>
                <div class="w-1/2 px-3 mb-6 md:mb-0">
                  <label
                    class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2"
                    for="grid-state"
                  >
                    稟議合計金額
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

              <hr class="my-8" />
              <h2 class="font-bold text-gray-700 mb-4">購入物品</h2>

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
                    />
                  </div>
                </div>
              </div>
              <button
                v-if="stockEdit === null"
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mb-8"
                @click.prevent="createApprovalStocks"
              >
                追加
              </button>
              <button
                v-else
                class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded mb-8"
                @click.prevent="saveApprovalStocks"
              >
                更新
              </button>

              <div v-if="form.approval_stocks.length > 0">
                <div class="overflow-x-auto">
                  <table class="min-w-full bg-white border border-gray-200">
                    <thead>
                      <tr>
                        <th
                          class="py-2 px-4 border-b border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"
                        >
                          品名
                        </th>
                        <th
                          class="py-2 px-4 border-b border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"
                        >
                          品番
                        </th>
                        <th
                          class="py-2 px-4 border-b border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"
                        >
                          発注先
                        </th>
                        <th
                          class="py-2 px-4 border-b border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"
                        >
                          単価
                        </th>
                        <th
                          class="py-2 px-4 border-b border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"
                        >
                          数量
                        </th>
                        <th
                          class="py-2 px-4 border-b border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"
                        >
                          金額
                        </th>
                        <th
                          class="py-2 px-4 border-b border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"
                        ></th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr
                        v-for="approval_stock in form.approval_stocks"
                        :key="approval_stock.id"
                      >
                        <td
                          class="py-2 px-4 border-b border-gray-200 text-sm text-gray-700"
                        >
                          {{ approval_stock.name }}
                        </td>
                        <td
                          class="py-2 px-4 border-b border-gray-200 text-sm text-gray-700"
                        >
                          {{ approval_stock.s_name }}
                        </td>
                        <td
                          class="py-2 px-4 border-b border-gray-200 text-sm text-gray-700"
                        >
                          {{ approval_stock.supplier_name }}
                        </td>
                        <td
                          class="py-2 px-4 border-b border-gray-200 text-sm text-gray-700"
                        >
                          @ {{ approval_stock.price }}
                        </td>
                        <td
                          class="py-2 px-4 border-b border-gray-200 text-sm text-gray-700"
                        >
                          {{ approval_stock.quantity }}
                        </td>
                        <td
                          class="py-2 px-4 border-b border-gray-200 text-sm text-gray-700"
                        >
                          {{ approval_stock.price * approval_stock.quantity }}
                          円
                        </td>
                        <td
                          class="py-2 px-4 border-b border-gray-200 text-sm text-gray-700 flex"
                        >
                          <button
                            @click.prevent="
                              editStock(
                                form.approval_stocks.indexOf(approval_stock)
                              )
                            "
                            class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded mr-4"
                          >
                            編集
                          </button>
                          <button
                            @click.prevent="
                              deleteStock(
                                form.approval_stocks.indexOf(approval_stock)
                              )
                            "
                            class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded"
                          >
                            削除
                          </button>
                        </td>
                      </tr>
                      <!-- 他の行を追加する場合はここに -->
                    </tbody>
                  </table>
                </div>
              </div>
              <hr class="my-8" />
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
                  ></textarea>
                </div>
              </div>

              <p
                class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2"
              >
                添付ファイルがある場合は以下より添付してください。（最大5つまで）
              </p>
              <ul class="mt-4 list-disc list-inside text-sm text-gray-600">
                <li class="mb-1">
                  承認済み稟議書<span class="text-red-500 font-bold">
                    (PDFは一枚にまとめてください！)</span
                  >
                </li>
                <li class="mb-1">購入物品の画像</li>
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

              <div class="flex justify-between">
                <!-- <button
                  class="mr-4 w-1/2 bg-green-500 hover:bg-green-700 text-white font-bold mt-12 px-4 rounded py-4"
                >
                  AI添削
                </button> -->
                <button
                  class="ml-4 w-1/2 bg-blue-500 hover:bg-blue-700 text-white font-bold mt-12 px-4 rounded py-4"
                  @click.prevent="submitForm('new_approval')"
                >
                  確定
                </button>
              </div>
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
                  for="desire_delivery_date_existing"
                >
                  希望納期
                </label>
                <input
                  class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                  id="desire_delivery_date_existing"
                  type="date"
                  v-model="form.desire_delivery_date"
                  @change="handleDesireDeliveryDateChange"
                />
                <p class="mt-2 text-red-500 text-xs italic">
                  リードタイムの都合上難しい場合がございます。土日祝は選択できません。
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

      <hr class="mt-8 mb-4" />
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
