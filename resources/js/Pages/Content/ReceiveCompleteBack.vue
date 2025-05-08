<script setup>
import { onMounted, ref } from "vue";

const props = defineProps({
  initial_orders: Array,
  place_name: String,
});

const active_orders = ref([]);
const pageNumber = ref(1);
const ordersPerPage = 6;
const updateActiveOrders = () => {
  const start = (pageNumber.value - 1) * ordersPerPage;
  const end = start + ordersPerPage;
  active_orders.value = props.initial_orders.slice(start, end);
};

const reloadPage = () => {
  window.location.reload();
};



onMounted(() => {
  if (props.initial_orders.length > ordersPerPage) {
    updateActiveOrders();
    setInterval(() => {
      pageNumber.value =
        (pageNumber.value %
          Math.ceil(props.initial_orders.length / ordersPerPage)) +
        1;
      updateActiveOrders();
    }, 300000); // 5分周期
  } else {
    active_orders.value = props.initial_orders;
  }

  setTimeout(reloadPage, 3600000); // 1時間後にリロード
});
</script>
<template>
  <div id="signage_content" class="w-full bg-gray-50">
    <h1 class="text-center py-4 text-2xl font-bold text-white">
      <span class="text-red-500 inline-block mr-4">{{ props.place_name }}</span
      >の納品完了済みデータを表示中
    </h1>
    <p class="mb-4 text-center text-lg text-white font-bold underline">
      自分の納品物が表示されている場合、該当の納品場所まで取りに来てください。
    </p>

    <!-- ページ番号 -->
    <span id="page_number">{{ pageNumber }}</span>

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
            v-for="order in active_orders"
            :key="order.id"
            class="text-center bg-black"
          >
            <td
              class="w-1/5 font-bold py-4 px-4 border-b border-gray-200 text-left"
            >
              {{ order.order_user }}
            </td>
            <td
              class="w-3/5 font-bold py-4 px-4 border-b border-gray-200 text-left"
            >
              {{
                order.name.length > 15
                  ? order.name.slice(0, 15) + "..."
                  : order.name
              }}
            </td>
            <td
              class="w-1/5 font-bold py-4 px-4 border-b border-gray-200 text-left whitespace-nowrap"
            >
              {{
                order.s_name && order.s_name.length > 15
                  ? order.s_name.slice(0, 15) + "..."
                  : order.s_name
              }}
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
  height: 100vh;
  padding: 0.6rem;

  & #page_number {
    position: fixed;
    top: 2%;
    right: 2%;
    font-size: 1.5rem;
    color: white;
  }
}

td {
  font-size: 2rem;
}
</style>
