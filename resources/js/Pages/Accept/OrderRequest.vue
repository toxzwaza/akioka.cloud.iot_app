<script setup>
import AcceptLayout from "@/Layouts/AcceptLayout.vue";
import { onMounted } from "vue";
import { getImgPath } from "@/Helper/method";

const props = defineProps({
  user: Object,
  order_requests: Array,
});

const sendAccept = (order_request_id, action) => {
  let accept_flg;
  let msg = "";
  if (order_request_id) {
    switch (action) {
      case "accept":
        accept_flg = 2;
        msg = "承認登録が完了しました。";
        break;
      case "reject":
        accept_flg = 3;
        msg = "非承認登録が完了しました。";
        break;
    }

    axios
      .put(route("accept.order-request.update"), {
        order_request_id: order_request_id,
        accept_flg: accept_flg,
      })
      .then((res) => {
        console.log(res.data);
        if (res.data.status && confirm(msg)) {
          window.location.reload();
        }
      })
      .catch((error) => {});
  }
};

onMounted(() => {
  console.log(props.order_requests);
});
</script>
<template>
  <AcceptLayout :title="'承認画面'">
    <template #content>
      <section class="text-gray-600 body-font">
        <div class="py-12 mx-auto">
          <div class="flex flex-col text-center w-full mb-20">
            <h1
              class="sm:text-4xl text-3xl font-medium title-font mb-2 text-gray-900"
            >
              承認者：{{ props.user.name }}
            </h1>
            <p class="lg:w-2/3 mx-auto leading-relaxed text-base">
              以下より、承認を行ってください。
            </p>
          </div>
          <div class="w-full mx-auto overflow-auto">
            <table
              id="order_request_table"
              class="table-auto w-full text-left whitespace-no-wrap"
            >
              <thead>
                <tr>
                  <th
                    class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100 rounded-tl rounded-bl"
                  >
                    画像
                  </th>
                  <th
                    class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
                  >
                    品名
                  </th>
                  <th
                    class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
                  >
                    品番
                  </th>
                  <th
                    class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
                  >
                    数量
                  </th>
                  <th
                    class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
                  >
                    単価
                  </th>
                  <th
                    class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
                  >
                    金額
                  </th>
                  <th
                    class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
                  >
                    発注先
                  </th>
                  <th
                    class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
                  >
                    発注依頼日
                  </th>
                  <th
                    class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
                  >
                    発注依頼者
                  </th>
                  <th
                    class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
                  ></th>
                  <th
                    class="w-10 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100 rounded-tr rounded-br"
                  ></th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="order_request in order_requests"
                  :key="order_request.id"
                >
                  <td class="img py-8">
                    <img :src="getImgPath(order_request.img_path)" alt="" />
                  </td>
                  <td class="px-4 py-8">{{ order_request.name }}</td>
                  <td class="px-4 py-8">{{ order_request.s_name }}</td>
                  <td class="px-4 py-8 text-lg text-gray-900">
                    {{ order_request.quantity }}
                  </td>
                  <td class="px-4 py-8 text-lg text-gray-900">
                    {{ order_request.price }}
                  </td>
                  <td class="px-4 py-8 text-lg text-gray-900">
                    {{ order_request.calc_price }}
                  </td>
                  <td class="px-4 py-8 text-lg text-gray-900">
                    {{ order_request.supplier_name }}
                  </td>
                  <td class="px-4 py-8 text-lg text-gray-900">
                    {{
                      new Date(order_request.created_at)
                        .toLocaleDateString("ja-JP", {
                          year: "numeric",
                          month: "2-digit",
                          day: "2-digit",
                        })
                        .replace(/\//g, "/")
                    }}
                  </td>
                  <td class="px-4 py-8 text-lg text-gray-900">
                    {{ order_request.request_user_name }}
                  </td>
                  <td class="w-10 text-center">
                    <button
                      @click.prevent="sendAccept(order_request.id, 'accept')"
                      class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded"
                    >
                      承認
                    </button>
                  </td>
                  <td class="w-10 text-center">
                    <button
                      @click.prevent="sendAccept(order_request.id, 'reject')"
                      class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded"
                    >
                      否認
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>
    </template>
  </AcceptLayout>
</template>
<style scoped lang="scss">
#order_request_table {
  width: 200vw;
  font-size: 0.9rem;

  @media screen and (min-width: 800px) {
    width: 100%;
    font-size: 1rem;

    td {
      img {
        max-width: 12vw;
      }
    }
  }

  td,
  th {
    max-width: 20vw;
    white-space: nowrap;
  }

  td.img {
    min-width: 20vw;

    img {
      height: 100%;
      width: 100%;
      object-fit: contain;
    }
  }
}
</style>