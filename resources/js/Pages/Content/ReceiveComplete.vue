<script setup>
import { onMounted } from "vue";

const props = defineProps({
  initial_orders: Array,
  place_name: String,
});

onMounted(() => {
  console.log(props.initial_orders, props.place_name);
});
</script>
<template>
  <div id="signage_content" class="w-full bg-gray-50">
    <h1 class="text-center py-8  text-4xl font-bold text-white">
      <span class=" text-red-500 inline-block mr-4">{{ props.place_name }}</span>の納品完了済みデータを表示中
    </h1>
    <p class="mb-8 text-center text-2xl text-white font-bold underline">
      自分の納品物が表示されている場合、該当の納品場所まで取りに来てください。
    </p>

    <div class="overflow-x-auto">
      <table class="w-full bg-white">
        <thead>
          <tr>
            <th class="py-2 px-4 bg-gray-800 text-white text-lg">注文者</th>
            <th class="py-2 px-4 bg-gray-800 text-white text-lg">品名</th>
            <th class="py-2 px-4 bg-gray-800 text-white text-lg">品番</th>
            <!-- <th class="py-2 px-4 bg-gray-800 text-white text-lg">納品場所</th>
            <th class="py-2 px-4 bg-gray-800 text-white text-lg">納品日</th>
            <th class="py-2 px-4 bg-gray-800 text-white text-lg">数量</th> -->
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="order in props.initial_orders"
            :key="order.id"
            class="text-center bg-black"
          >
            <td class="w-1/5 font-bold py-8 px-4 border-b border-gray-200">
              {{ order.order_user }}
            </td>
            <td class="w-3/5  font-bold py-8 px-4 border-b border-gray-200">
              {{ order.name }}
            </td>
            <td class="w-1/5  font-bold py-8 px-4 border-b border-gray-200">
              {{ order.s_name }}
            </td>
            <!-- <td class="text-4xl font-bold py-8 px-4 border-b border-gray-200">
              {{ order.deli_location }}
            </td>
            <td class="text-4xl font-bold py-8 px-4 border-b border-gray-200">
              {{ order.delivery_date }}
            </td>

            <td class="text-4xl font-bold py-8 px-4 border-b border-gray-200">
              {{ order.quantity }}
            </td> -->
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
<style scoped lang="scss">
#signage_content {
  font-family: "Noto Sans JP", sans-serif;
  font-optical-sizing: auto;
  font-style: normal;

  background-color: black;
  color: yellow;
}

td{
    font-size: 3.6rem;
}
</style>
