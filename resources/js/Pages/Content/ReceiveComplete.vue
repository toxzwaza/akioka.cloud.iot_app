<script setup>
import { onMounted } from "vue";

const props = defineProps({
  initial_orders: Array,
});

onMounted(() => {
  console.log(props.initial_orders);
});
</script>
<template>
  <div class="w-full">
    <h1 class="py-8 mb-8 text-red-500 text-4xl font-bold text-center">納品が完了しました！</h1>

    <div class="overflow-x-auto">
      <table class="w-full bg-white">
        <thead>
          <tr>
            <th class="py-2 px-4 bg-gray-800 text-white text-lg">注文者</th>
            <th class="py-2 px-4 bg-gray-800 text-white text-lg">品名</th>
            <th class="py-2 px-4 bg-gray-800 text-white text-lg">品番</th>
            <th class="py-2 px-4 bg-gray-800 text-white text-lg">納品場所</th>
            <th class="py-2 px-4 bg-gray-800 text-white text-lg">納品日</th>
            <th class="py-2 px-4 bg-gray-800 text-white text-lg">数量</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="order in props.initial_orders"
            :key="order.id"
            class="text-center bg-gray-50"
          >
            <td class="text-4xl font-bold py-8 px-4 border-b border-gray-200">
              {{ order.order_user }}
            </td>
            <td class="text-4xl font-bold py-8 px-4 border-b border-gray-200">{{ order.name }}</td>
            <td class="text-4xl font-bold py-8 px-4 border-b border-gray-200">
              {{ order.s_name }}
            </td>
            <td class="text-4xl font-bold py-8 px-4 border-b border-gray-200">
              {{ order.deli_location }}
            </td>
            <td class="text-4xl font-bold py-8 px-4 border-b border-gray-200">
              {{ order.delivery_date }}
            </td>

            <td class="text-4xl font-bold py-8 px-4 border-b border-gray-200">
              {{ order.quantity }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
<style scoped lang="scss">
</style>
