<script setup>
import { ref, onMounted  } from "vue";

const props = defineProps({
    uid: String
})
const currentStep = ref(1);
const totalSteps = 3;

// 回答格納用オブジェクト
const form = ref({
  uid: null,
  school: "",
  grade: "",
  gender: "",
});

// 質問内容
const questions = [
  { key: "school", text: "学校を教えてください！", type: "text" },
  { key: "grade", text: "学年をおしえてください！", type: "select", options: ["3年","2年", "1年"] },
  {
    key: "gender",
    text: "性別をおしえてください！",
    type: "select",
    options: ["男性", "女性", "その他"],
  },
];

const next = () => {
  if (currentStep.value < totalSteps) {
    currentStep.value++;
  } else {
    console.log("回答結果:", form.value);
    alert(JSON.stringify(form.value, null, 2)); // デモ用
  }
};

const back = () => {
  if (currentStep.value > 1) {
    currentStep.value--;
  }
};

// 現在の質問の回答が入力されているかをチェック
const isCurrentQuestionAnswered = () => {
  const currentQuestion = questions[currentStep.value - 1];
  const answer = form.value[currentQuestion.key];
  return answer && answer.trim() !== "";
};

// プログレスバーの色を段階的に濃くする
const getProgressColor = () => {
  const progress = currentStep.value / totalSteps;
  if (progress <= 0.33) {
    return '#87CEEB'; // 薄い青
  } else if (progress <= 0.66) {
    return '#5CC6F5'; // 中間の青
  } else {
    return '#1E90FF'; // 濃い青
  }
};

onMounted( () => {
    console.log(props.uid)
    form.value.uid = props.uid
})
</script>

<template>
  <div class="survey">
    <!-- ヘッダー -->
    <div class="header">
      本日はご多用の中、<br />ご来場ありがとうございました！
    </div>

    <!-- 質問エリア -->
    <div class="question">
      <div class="q-text">
        <span class="inline-block mr-2">Q{{ currentStep }}.</span
        >{{ questions[currentStep - 1].text }}
      </div>

      <!-- 入力欄 -->
      <div v-if="questions[currentStep - 1].type === 'text'">
        <input
          v-model="form[questions[currentStep - 1].key]"
          type="text"
          placeholder="入力してください"
        />
      </div>
      <div v-else-if="questions[currentStep - 1].type === 'select'">
        <select v-model="form[questions[currentStep - 1].key]">
          <option value="" disabled>選択してください</option>
          <option
            v-for="option in questions[currentStep - 1].options"
            :key="option"
            :value="option"
          >
            {{ option }}
          </option>
        </select>
      </div>
    </div>

    <!-- ナビゲーション -->
    <div class="navigation">
      <button class="back" @click="back" :disabled="currentStep === 1">
        戻る
      </button>
      <button
        class="next"
        @click="next"
        :disabled="!isCurrentQuestionAnswered()"
      >
        {{ currentStep === totalSteps ? "送信" : "次へ" }}
      </button>
    </div>

    <!-- キャラクター -->
    <div class="character">
      <img src="/images/questionnaire/background.png" alt="キャラクター" />
    </div>

    <div class="w-2/3 py-8">
      <div class="progress-text">ご協力ありがとうございます！</div>
      <div class="progress-container">
        <div class="progress">
          <div
            class="progress-bar"
            :style="{
              width: (currentStep / totalSteps) * 100 + '%',
              backgroundColor: getProgressColor(),
            }"
          ></div>
        </div>
        <div class="progress-status">{{ currentStep }}/{{ totalSteps }}</div>
      </div>
    </div>
  </div>
</template>

<style scoped lang="scss">
.survey {
  display: flex;
  flex-direction: column;
  align-items: center;
  background: #ffffff;
  font-family: "sans-serif";
  height: 100vh;
  justify-content: space-between;
  //   padding: 10px;
}

.header {
  background: #5cc6f5;
  color: white;
  padding: 15px;
  text-align: center;
  width: 100%;
  font-size: 1.2rem;
  font-weight: bold;
}

.question {
  text-align: center;
  margin-top: 20px;

  .q-text {
    font-size: 1.5rem;
    font-weight: bold;
    margin: 10px 0;
    color: #5cc6f5;
  }

  input,
  select {
    padding: 10px;
    border: 2px solid #5cc6f5;
    border-radius: 6px;
    width: 80vw;
    font-size: 1rem;
  }
}

.navigation {
  display: flex;
  justify-content: space-between;
  width: 80%;
  margin: 20px 0;

  .back {
    background: #eeeeee;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    font-size: 1rem;
  }

  .next {
    background: #5cc6f5;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    color: white;
    font-size: 1rem;

    &:disabled {
      background: #cccccc;
      color: #666666;
      cursor: not-allowed;
    }
  }
}

.progress-text {
  color: #5cc6f5;
  font-size: 1.1rem;
  margin-bottom: 10px;
  text-align: center;
  font-weight: bold;
}

.progress-container {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 10px;
}

.progress-status {
  color: #5cc6f5;
  font-size: 1rem;
  font-weight: bold;
  white-space: nowrap;
}

.progress {
  background: #e0f4fd;
  border-radius: 15px;
  flex: 1;
  height: 15px;
  overflow: hidden;

  .progress-bar {
    background: #5cc6f5;
    height: 100%;
    transition: width 0.3s ease;
  }
}

.character {
  width: 100%;
  display: flex;
  justify-content: end;
  opacity: 0.6;

  & img {
    height: 100%;
    width: 80%;
    object-fit: contain;
  }
}
</style>
