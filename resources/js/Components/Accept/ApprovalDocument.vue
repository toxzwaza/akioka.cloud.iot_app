<script setup>
import { onMounted } from "vue";

const props = defineProps({
  approval_document: Object,
});

onMounted(() => {
  console.log(props.approval_document);
});
</script>

<template>
  <div id="approval_document" class="mx-auto w-2/3 p-20">
    <h1 class="text-center text-3xl mb-8 text-gray-700 font-bold">稟議書</h1>

    <div class="flex flex-wrap -mx-3 mb-8">
      <div class="w-full md:w-1/2 px-3 mb-6 md:mb-0">
        <label
          class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2"
          for="grid-first-name"
        >
          起案部門
        </label>
        <p
          class="appearance-none block w-full bg-gray-200 text-gray-700 border border-transparent rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white"
        >
          {{ props.approval_document.process_name }}
        </p>
      </div>
      <div class="w-full md:w-1/2 px-3">
        <label
          class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2"
          for="grid-last-name"
        >
          起案者
        </label>
        <p
          class="appearance-none block w-full bg-gray-200 text-gray-700 border border-transparent rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white"
        >
          {{ props.approval_document.user_name }}
        </p>
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
        <p
          class="appearance-none block w-full bg-gray-200 text-gray-700 border border-transparent rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white"
        >
          {{ props.approval_document.evalution_date }}
        </p>
      </div>
      <div class="w-1/3 px-3 mb-6 md:mb-0">
        <label
          class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2"
          for="grid-city"
        >
          希望納入日
        </label>
        <p
          class="appearance-none block w-full bg-gray-200 text-gray-700 border border-transparent rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white"
        >{{ props.approval_document.desire_delivery_date }}</p>
      </div>
      <div class="w-1/3 px-3 mb-6 md:mb-0">
        <label
          class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2"
          for="grid-state"
        >
          発注先
        </label>
        <div class="relative">
          <p
            class="appearance-none block w-full bg-gray-200 text-gray-700 border border-transparent rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white"
          >
            {{ props.approval_document.supplier_name }}
          </p>
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
        <p
          class="appearance-none block w-full bg-gray-200 text-gray-700 border border-transparent rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white"
        >
          {{ props.approval_document.price }}
        </p>
      </div>
      <div class="w-1/3 px-3 mb-6 md:mb-0">
        <label
          class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2"
          for="grid-state"
        >
          数量
        </label>
        <div class="relative">
          <p
            class="appearance-none block w-full bg-gray-200 text-gray-700 border border-transparent rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white"
          >
            {{ props.approval_document.quantity }}
          </p>
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
          <p
            class="appearance-none block w-full bg-gray-200 text-gray-700 border border-transparent rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white"
          >
            {{ props.approval_document.calc_price }}
          </p>
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
        <p
          class="appearance-none block w-full bg-gray-200 text-gray-700 border border-transparent rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white"
        >
          {{ props.approval_document.name }}
        </p>
      </div>
      <div class="w-1/2 px-3 mb-6 md:mb-0">
        <label
          class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2"
          for="grid-state"
        >
          品番
        </label>
        <div class="relative">
          <p
            class="appearance-none block w-full bg-gray-200 text-gray-700 border border-transparent rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white"
          >
            {{ props.approval_document.s_name }}
          </p>
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
        <p
          class="appearance-none block w-full bg-gray-200 text-gray-700 border border-transparent rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white"
        >
          {{ props.approval_document.title }}
        </p>
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
        <p
          v-html="props.approval_document.content?.replace(/\n/g, '<br>')"
          class="appearance-none block w-full bg-gray-200 text-gray-700 border border-transparent rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white"
        ></p>
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
        <p
          v-html="props.approval_document.main_reason?.replace(/\n/g, '<br>')"
          class="appearance-none block w-full bg-gray-200 text-gray-700 border border-transparent rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white"
        ></p>
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
        <p
          v-html="props.approval_document.sub_reason?.replace(/\n/g, '<br>')"
          class="appearance-none block w-full bg-gray-200 text-gray-700 border border-transparent rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white"
        ></p>
      </div>
    </div>
  </div>
</template>
<style scoped lang="scss">
#approval_document {
    margin-top: 1rem;
    // height: 90vh;
    // overflow-y: auto;
    box-shadow: rgba(0, 0, 0, 0.16) 0px 1px 4px;
    border: 1px solid rgba(201, 201, 201, 0.473);
}
</style>