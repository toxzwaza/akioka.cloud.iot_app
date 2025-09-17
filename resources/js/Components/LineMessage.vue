<script setup>
import { ref, onMounted } from "vue";
import { router } from "@inertiajs/vue3";
import axios from "axios";

const message = ref("");
const figure_app_questions = ref({});

const sendMessage = () => {
  const check = confirm(`${message.value}で送信してもよろしいですか？`);
  if (check) {
    router.post(route("store.question"), { question: message.value });
  }
  getQuestions();
};

const getQuestions = () => {
  axios
    .get(route("api.getFigureAppQuestion"))
    .then((response) => {
      figure_app_questions.value = response.data;
    })
    .catch((error) => {
      console.error("Error fetching questions:", error);
    });
};
onMounted(() => {
  getQuestions();
});
</script>
<template>
  <form class="w-full mt-16">
    <p class="text-gray-500 font-bold">
      追加機能・質問などあればこちらから送信してください。
    </p>
    <div class="flex items-center border-b border-indigo-500 py-2">
      <input
        class="appearance-none bg-transparent border-none w-full text-gray-700 mr-3 py-1 px-2 leading-tight focus:outline-none"
        type="text"
        placeholder="メッセージを入力してください。"
        aria-label="Full name"
        v-model="message"
      />
      <button
        @click="sendMessage"
        class="flex-shrink-0 bg-indigo-500 hover:bg-indigo-700 border-indigo-500 hover:border-indigo-700 text-sm border-4 text-white py-1 px-2 rounded"
        type="button"
      >
        送信
      </button>
      <button
        @click="message = ''"
        class="flex-shrink-0 border-transparent border-4 text-indigo-500 hover:text-indigo-800 text-sm py-1 px-2 rounded"
        type="button"
      >
        削除
      </button>
    </div>
  </form>
  <div class="mt-8 mb-16">
    <h3 class="font-bold text-gray-500">問い合わせ一覧</h3>
    <div class="mt-4 relative overflow-x-auto shadow-md sm:rounded-lg">
      <table
        class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400"
      >
        <thead
          class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400"
        >
          <tr>
            <th scope="col" class="px-6 py-3">ID</th>
            <th scope="col" class="px-6 py-3">質問</th>
            <th scope="col" class="px-6 py-3">回答</th>
            <th scope="col" class="px-6 py-3">質問日</th>
            <th scope="col" class="px-6 py-3">回答日</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="question in figure_app_questions"
            :key="question.id"
            class="odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800 border-b dark:border-gray-700"
          >
            <th
              scope="row"
              class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white"
            >
              {{ question.id }}
            </th>
            <td class="px-6 py-4">{{ question.question }}</td>
            <td class="px-6 py-4">{{ question.answer }}</td>
            <td class="px-6 py-4">
              {{ new Date(question.created_at).toLocaleDateString("ja-JP") }}
            </td>
            <td class="px-6 py-4">
              {{ new Date(question.updated_at).toLocaleDateString("ja-JP") }}
            </td>
          </tr>

        </tbody>
      </table>
    </div>
  </div>
  <hr class="my-8" />
</template>


<style>
</style>