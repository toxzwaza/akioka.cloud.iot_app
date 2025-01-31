<script setup>
import ReceiveLayout from "@/Layouts/ReceiveLayout.vue";
import { onMounted, ref } from "vue";
import axios from "axios";
import MicroModal from "@/Components/MicroModal.vue";
import { Link } from "@inertiajs/vue3";


const modalStatus = ref(false);
const modalImageSrc = ref("");
const modalImage = (target) => {
  modalStatus.value = true;
  modalImageSrc.value = target.src;
  console.log(modalImageSrc.value);
};
const handleCloseModal = () => {
  modalStatus.value = !modalStatus.value;
};
const base_initial_orders = ref([]);
const initial_orders = ref([]);

const getReceiptOrders = () => {
  axios
    .get(route("stock.receive.getReceiptOrders"))
    .then((res) => {
      initial_orders.value = res.data;
      base_initial_orders.value = res.data;
      console.log(initial_orders.value);
    })
    .catch((error) => {
      console.log(error);
    });
};

const searchOrders = (val) => {
  console.log(val);
  if (val) {
    initial_orders.value = initial_orders.value.filter(
      (order) => order.order_no && order.order_no.includes(val)
    );

    if (initial_orders.value.length == 0) {
      initial_orders.value = base_initial_orders.value;
    }
  } else {
    initial_orders.value = base_initial_orders.value;
  }
};

onMounted(() => {
  getReceiptOrders();
});
</script>
<template>
  <ReceiveLayout :title="'納品登録'">
    <template #content>
      <section class="text-gray-600 body-font">
        <div class="container px-5 py-24 mx-auto">
          <div class="flex flex-col text-center w-full mb-8">
            <h1
              class="sm:text-4xl text-3xl font-medium title-font mb-2 text-blue-600"
            >
              引き渡し登録
            </h1>
            <p class="lg:w-2/3 mx-auto leading-relaxed text-base">
              引き渡し登録を完了すると、サイネージディスプレイの表示が解除されます。<br />
              物品引き渡し時に登録を行ってください。
            </p>
          </div>
          <div class="w-1/2 mx-auto mb-8">
            <div class="p-2">
              <div class="relative">
                <label for="email" class="leading-7 text-sm text-gray-600"
                  >検索</label
                >
                <input
                  @change="searchOrders($event.target.value)"
                  type="email"
                  id="email"
                  name="email"
                  class="w-full bg-gray-100 bg-opacity-50 rounded border border-gray-300 focus:border-indigo-500 focus:bg-white focus:ring-2 focus:ring-indigo-200 text-base outline-none text-gray-700 py-1 px-3 leading-8 transition-colors duration-200 ease-in-out"
                  placeholder="注文No"
                />
              </div>
            </div>
          </div>
          <div class="w-full mx-auto overflow-auto">
            <table class="table-auto w-full text-left whitespace-no-wrap">
              <thead>
                <tr>
                  <th
                    class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100 rounded-tl rounded-bl"
                  >
                    注文No
                  </th>
                  <th
                    class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100 rounded-tl rounded-bl"
                  >
                    画像
                  </th>
                  <th
                    class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
                  >
                    注文者
                  </th>
                  <th
                    class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
                  >
                    注文日
                  </th>
                  <th
                    class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
                  >
                    注文先
                  </th>
                  <th
                    class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
                  >
                    品名:品番
                  </th>
                  <th
                    class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
                  >
                    数量
                  </th>
                  <th
                    class="w-10 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100 rounded-tr rounded-br"
                  ></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="order in initial_orders" :key="order.id" class="">
                  <td class="px-4 py-6">{{ order.order_no }}</td>
                  <td class="w-24 px-4 py-6">
                    <img
                      @click="modalImage($event.target)"
                      :src="
                        order.img_path && order.img_path.includes('https://')
                          ? order.img_path
                          : 'https://akioka.cloud/' + order.img_path
                      "
                      alt=""
                    />
                  </td>
                  <td class="px-4 py-6">{{ order.order_user }}</td>
                  <td class="px-4 py-6">
                    {{ new Date(order.order_date).toLocaleDateString("ja-JP") }}
                  </td>
                  <td class="px-4 py-6">{{ order.com_name }}</td>
                  <td class="px-4 py-6">
                    {{ order.name + " : " + order.s_name }}
                  </td>
                  <td class="px-4 py-6">
                    {{ order.quantity + order.order_unit }}
                  </td>
                  <td class="w-10 text-center">
                    <Link
                      :href="route('stock.receive.updateReceipt', {'id' : order.id })"
                      class="bg-transparent hover:bg-blue-500 text-blue-700 font-semibold hover:text-white py-2 px-4 border border-blue-500 hover:border-transparent rounded text-sm whitespace-nowrap"
                    >
                      引渡済
                    </Link>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <MicroModal
        v-if="modalStatus"
        @closeModal="handleCloseModal"
        :modalImageSrc="modalImageSrc"
      ></MicroModal>
    </template>
  </ReceiveLayout>
</template>
<style scoped>
</style>