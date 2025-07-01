<script setup>
import { onMounted, ref } from "vue";

const props = defineProps({
  order_requests: Array,
});

const active_orders = ref([]);
const pageNumber = ref(1);
const ordersPerPage = 11;
const updateActiveOrders = () => {
  const start = (pageNumber.value - 1) * ordersPerPage;
  const end = start + ordersPerPage;
  active_orders.value = props.order_requests.slice(start, end);
};

const reloadPage = () => {
  window.location.reload();
};

onMounted(() => {
  console.log(props.order_requests);

  if (props.order_requests.length > ordersPerPage) {
    updateActiveOrders();
    setInterval(() => {
      pageNumber.value =
        (pageNumber.value %
          Math.ceil(props.order_requests.length / ordersPerPage)) +
        1;
      updateActiveOrders();
    }, 6000); // 5分周期
  } else {
    active_orders.value = props.order_requests;
  }

  setTimeout(reloadPage, 3600000); // 1時間後にリロード
});
</script>
<template>
  <div id="signage_content" class="w-full bg-gray-50">
    <h1 class="text-center py-4 text-2xl font-bold text-gray-700">
      <span class="text-red-500 inline-block mr-4"></span
      >本日の物品依頼データを表示中
    </h1>
    <p class="mb-4 text-center text-lg text-gray-700 font-bold underline">
      こちらに物品依頼が表示されていない場合は、きちんと依頼できていない可能性があります。タブレットより、確認してください。
    </p>

    <!-- ページ番号 -->
    <span id="page_number">{{ pageNumber }}/{{ Math.ceil(props.order_requests.length / ordersPerPage) }}ページ</span>

    <div class="overflow-x-auto">
      <table class="w-full">
        <thead>
          <tr>
            <th class="py-2 px-4 bg-gray-800 text-white text-lg whitespace-nowrap">依頼日</th>
            <th class="py-2 px-4 bg-gray-800 text-white text-lg whitespace-nowrap">注文者</th>
            <th class="py-2 px-4 bg-gray-800 text-white text-lg whitespace-nowrap">品名</th>
            <th class="py-2 px-4 bg-gray-800 text-white text-lg whitespace-nowrap">品番</th>
            <th class="py-2 px-4 bg-gray-800 text-white text-lg whitespace-nowrap">承認状況</th>
            <th class="py-2 px-4 bg-gray-800 text-white text-lg whitespace-nowrap">依頼状況</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="order in active_orders"
            :key="order.id"
            class="text-center bg-gray-500"
          >
            <td
              class="w-1/6 font-bold py-4 px-4 border-b border-gray-200 text-center whitespace-nowrap"
            >
              {{ new Date(order.created_at).toLocaleDateString('ja-JP', { month: '2-digit', day: '2-digit' }) }}
            </td>
          
            <td
              class="w-1/5 font-bold py-4 px-4 border-b border-gray-200 text-center whitespace-nowrap"
            >
              {{ order.request_user_name }}
            </td>
            <td
              class="w-2/5 font-bold py-4 px-4 border-b border-gray-200 text-center whitespace-nowrap"
            >
              {{
                order.name && order.name.length > 20
                  ? order.name.slice(0, 20) + "..."
                  : order.name || ''
              }}
            </td>
            <td
              class="w-2/5 font-bold py-4 px-4 border-b border-gray-200 text-center whitespace-nowrap"
            >
              {{
                order.s_name && typeof order.s_name === 'string' && order.s_name.length > 20
                  ? order.s_name.slice(0, 20) + "..."
                  : order.s_name || ''
              }}
            </td>
            <td
              :class="{'w-1/6 font-bold py-4 px-4 border-b border-gray-200 text-center whitespace-nowrap': true,
              'text-white' : !order.accept_flg,
              'text-orange-500' : order.accept_flg == 1,
              'text-green-500' : order.accept_flg == 2,
              'text-red-500' : order.accept_flg == 3,
              }"
            >
              {{ !order.accept_flg ? '準備中' : order.accept_flg == 1 ? '承認待ち' : order.accept_flg == 2 ? '承認済' : order.accept_flg == 3 ? '承認却下' : '' }}
            </td>
            <td
              :class="{'w-1/6 font-bold py-4 px-4 border-b border-gray-200 text-center whitespace-nowrap': true,
              'text-white': !order.status,
              'text-green-500': order.status,
              }"
            >
              {{ order.status ? '完了' : '準備中' }}
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

  background-color: rgb(233, 233, 233);
  color: rgb(255, 255, 255);
  height: 100vh;
  padding: 0.6rem;

  & #page_number {
    position: fixed;
    top: 2%;
    right: 2%;
    font-size: 1.5rem;
    color: black;
  }
}

td {
  font-size: 2rem;
}
</style>
