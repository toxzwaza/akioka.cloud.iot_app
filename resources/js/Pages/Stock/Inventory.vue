<script setup>
import StockLayout from "@/Layouts/StockLayout.vue";
import { Link } from "@inertiajs/vue3";
import { onMounted, ref } from "vue";

const stock_storage = ref(null);

const props = defineProps({
  stock: Object,
});

onMounted(() => {
  console.log(props.stock);
  if (props.stock.stock_storage.length == 1) {
    stock_storage.value = props.stock.stock_storage[0];
  }
  console.log(stock_storage.value);
});
</script>
<template>
  <StockLayout :title="'在庫詳細'">
    <template #content>
      <div class="flex">
        <div id="left_container" class="w-1/2">
          <h1 class="stock_name">{{ props.stock.name }}</h1>
          <h2 class="stock_s_name">品番: {{ props.stock.s_name }}</h2>

          <div class="flex flex-col mt-6 mb-2">
            <input class="w-1/2" type="file" capture="camera" />
          </div>
          <img
            class="stock_img w-2/3"
            :src="'/' + props.stock.img_path"
            alt=""
          />
        </div>
        <div id="right_container" class="w-1/2">
          <!-- 格納先が１つの場合 -->
          <section id="one_address"  v-if="stock_storage">
            <div class="flex flex-col">
              <h1 id="location_name" class="text-center mb-4 ">{{ stock_storage.location_name }}</h1>
              <div class="flex justify-around">
                <h1 id="address" class="">{{ stock_storage.address }}</h1>
                <h1 id="quantity" class="">{{ stock_storage.quantity }}個</h1>
              </div>
            </div>

            <div id="button_container" class="mt-12 mb-12">
              <Link
                :href="
                  route('stock.shipment', {
                    stock_storage_address_id: stock_storage.id,
                  })
                "
                ><img src="/images/stocks/icons/shipment.png" alt="出庫画面"
              /></Link>
            </div>
          </section>

          <!-- 格納先が複数ある場合 -->
          <section v-else class="mb-8 some_addresses text-gray-600 body-font">
            <div class="container mx-auto">
              <h2 class="array_title">保管場所</h2>
              <div class="w-full mx-auto overflow-auto">
                <table class="table-auto w-full text-left whitespace-no-wrap">
                  <thead>
                    <tr>
                      <th
                        class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100 rounded-tl rounded-bl"
                      >
                        保管倉庫
                      </th>
                      <th
                        class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
                      >
                        アドレス
                      </th>
                      <th
                        class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
                      >
                        数量
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="stock_storage in props.stock.stock_storage"
                      :key="stock_storage.id"
                    >
                      <td class="px-4 py-3">
                        {{ stock_storage.location_name }}
                      </td>
                      <td class="px-4 py-3">{{ stock_storage.address }}</td>
                      <td class="px-4 py-3">{{ stock_storage.quantity }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </section>

          <section class="w-full mt-8 text-gray-600 body-font">
            <div class="container mx-auto">
              <h2 class="array_title">発注履歴</h2>
              <div class="w-full mx-auto overflow-auto">
                <table class="table-auto w-full text-left whitespace-no-wrap">
                  <thead>
                    <tr>
                      <th
                        class="px-8 py-6 title-font tracking-wider font-medium text-gray-900 text-lg bg-gray-100 rounded-tl rounded-bl"
                      >
                        発注日
                      </th>
                      <th
                        class="px-8 py-6 title-font tracking-wider font-medium text-gray-900 text-lg bg-gray-100"
                      >
                        個数
                      </th>
                      <th
                        class="px-8 py-6 title-font tracking-wider font-medium text-gray-900 text-lg bg-gray-100"
                      >
                        ステータス
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="stock_storage in props.stock.stock_storage"
                      :key="stock_storage.id"
                    >
                      <td class="px-8 py-6">
                        {{ stock_storage.location_name }}
                      </td>
                      <td class="px-8 py-6">{{ stock_storage.address }}</td>
                      <td class="px-8 py-6">{{ stock_storage.quantity }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </section>
        </div>
      </div>
    </template>
  </StockLayout>
</template>
<style scoped lang="scss">
#left_container {
  & h1 {
    font-size: 1.2rem;
  }
  & h2 {
    font-size: 1.1rem;
  }

  & .stock_name {
    font-size: 2rem;
    font-weight: bold;
    color: #109ff3;
  }
  & .stock_s_name {
    font-size: 1.6rem;
    color: gray;
  }
  & .stock_img {
    box-shadow: rgba(0, 0, 0, 0.35) 0px 5px 15px;
  }
}

#right_container {
  & #one_address {
    & h1 {
      font-weight: bold;
      font-size: 2.6rem;

      &#location_name{
        color: #222222;
      }
      &#address{
        color: rgb(44, 44, 44);
      }
      &#quantity{
        font-weight: normal;
        font-family: serif ;
        color: rgb(44, 44, 44);
      }
    }
  }

  & #button_container {
    height: 80px;

    & img {
      height: 100%;
    }
  }
  & .array_title {
    font-size: 1.1rem;
    font-weight: bold;
  }
}
</style>
