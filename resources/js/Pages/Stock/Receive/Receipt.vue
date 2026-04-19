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
      <section class="bg-slate-50 min-h-screen py-8 px-4">
        <div class="max-w-7xl mx-auto">
          <div class="page-header text-center mb-8">
            <h1 class="section-title text-primary-600">
              引き渡し登録
            </h1>
            <p class="section-subtitle max-w-2xl mx-auto">
              引き渡し登録を完了すると、サイネージディスプレイの表示が解除されます。<br />
              物品引き渡し時に登録を行ってください。
            </p>
          </div>

          <!-- 検索 -->
          <div class="card max-w-2xl mx-auto mb-8 p-6">
            <div>
              <label class="form-label">検索</label>
              <input
                @change="searchOrders($event.target.value)"
                type="text"
                class="form-input-modern"
                placeholder="注文No"
              />
            </div>
          </div>

          <!-- テーブル -->
          <div class="card overflow-hidden">
            <div class="overflow-x-auto">
              <table class="table-modern">
                <thead>
                  <tr>
                    <th>注文No</th>
                    <th>画像</th>
                    <th>注文者</th>
                    <th>注文日</th>
                    <th>注文先</th>
                    <th>品名:品番</th>
                    <th>数量</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="order in initial_orders" :key="order.id">
                    <td class="font-medium text-slate-800">{{ order.order_no }}</td>
                    <td class="w-24">
                      <img
                        @click="modalImage($event.target)"
                        :src="
                          order.img_path && order.img_path.includes('https://')
                            ? order.img_path
                            : 'https://akioka.cloud/' + order.img_path
                        "
                        alt=""
                        class="rounded-lg cursor-pointer hover:opacity-80 transition-opacity"
                      />
                    </td>
                    <td>{{ order.order_user }}</td>
                    <td>
                      {{ new Date(order.order_date).toLocaleDateString("ja-JP") }}
                    </td>
                    <td>{{ order.com_name }}</td>
                    <td>
                      {{ order.name + " : " + order.s_name }}
                    </td>
                    <td>
                      {{ order.quantity + order.order_unit }}
                    </td>
                    <td class="text-center whitespace-nowrap">
                      <Link
                        :href="route('stock.receive.updateReceipt', {'id' : order.id })"
                        class="btn-primary text-sm"
                      >
                        引渡済
                      </Link>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
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