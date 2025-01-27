<script setup>
import { Link } from "@inertiajs/vue3";
import { onMounted, ref } from "vue";
const props = defineProps({
  orderList: Array,
  title: String,
});

onMounted(() => {
  console.log(route().current());
});
</script>
<template>
  <h1 id="page_title" class="p-2 mb-4">
    {{ route().current().endsWith("shipment") ? "出庫" : "検索" }}
  </h1>

  <div class="flex justify-between items-start">
    <div class="w-1/2 p-2">
      <form>
        <div class="flex flex-wrap -mx-3 mb-6">
          <div class="w-full px-3">
            <input
              class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
              id="grid-password"
              type="text"
              placeholder="JANコード or 製品ID"
            />
          </div>
        </div>
        <div class="flex flex-wrap -mx-3 mb-6">
          <div class="w-full px-3">
            <input
              class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
              id="grid-password"
              type="number"
              placeholder="数量"
            />
          </div>
        </div>
        <div class="flex flex-wrap -mx-3 mb-6">
          <div class="w-full px-3 flex items-center">
            <div class="mr-2 w-1/3">
              <label for="department">部署</label>
              <select
                id="department"
                class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
              >
                <option value="">test</option>
              </select>
            </div>

            <div class="mr-2 w-2/3">
              <label for="name">名前</label>
              <select
                id="name"
                class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
              >
                <option value="">test</option>
              </select>
            </div>
          </div>
        </div>

        <button
          class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
        >
          {{ route().current().endsWith("shipment") ? "出庫" : "検索" }}
        </button>
      </form>
      <section class="text-gray-600 body-font">
        <div class="container py-16 mx-auto">
          <div class="w-full mx-auto overflow-auto">
            <table class="table-auto w-full text-left whitespace-no-wrap">
              <thead>
                <tr>
                  <th
                    class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100 rounded-tl rounded-bl"
                  >
                    Plan
                  </th>
                  <th
                    class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
                  >
                    Speed
                  </th>
                  <th
                    class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
                  >
                    Storage
                  </th>
                  <th
                    class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
                  >
                    Price
                  </th>
                  <th
                    class="w-10 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100 rounded-tr rounded-br"
                  ></th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td class="px-4 py-3">Start</td>
                  <td class="px-4 py-3">5 Mb/s</td>
                  <td class="px-4 py-3">15 GB</td>
                  <td class="px-4 py-3 text-lg text-gray-900">Free</td>
                  <td class="w-10 text-center">
                    <input name="plan" type="radio" />
                  </td>
                </tr>
                <tr>
                  <td class="border-t-2 border-gray-200 px-4 py-3">Pro</td>
                  <td class="border-t-2 border-gray-200 px-4 py-3">25 Mb/s</td>
                  <td class="border-t-2 border-gray-200 px-4 py-3">25 GB</td>
                  <td
                    class="border-t-2 border-gray-200 px-4 py-3 text-lg text-gray-900"
                  >
                    $24
                  </td>
                  <td class="border-t-2 border-gray-200 w-10 text-center">
                    <input name="plan" type="radio" />
                  </td>
                </tr>
                <tr>
                  <td class="border-t-2 border-gray-200 px-4 py-3">Business</td>
                  <td class="border-t-2 border-gray-200 px-4 py-3">36 Mb/s</td>
                  <td class="border-t-2 border-gray-200 px-4 py-3">40 GB</td>
                  <td
                    class="border-t-2 border-gray-200 px-4 py-3 text-lg text-gray-900"
                  >
                    $50
                  </td>
                  <td class="border-t-2 border-gray-200 w-10 text-center">
                    <input name="plan" type="radio" />
                  </td>
                </tr>
                <tr>
                  <td class="border-t-2 border-b-2 border-gray-200 px-4 py-3">
                    Exclusive
                  </td>
                  <td class="border-t-2 border-b-2 border-gray-200 px-4 py-3">
                    48 Mb/s
                  </td>
                  <td class="border-t-2 border-b-2 border-gray-200 px-4 py-3">
                    120 GB
                  </td>
                  <td
                    class="border-t-2 border-b-2 border-gray-200 px-4 py-3 text-lg text-gray-900"
                  >
                    $72
                  </td>
                  <td
                    class="border-t-2 border-b-2 border-gray-200 w-10 text-center"
                  >
                    <input name="plan" type="radio" />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>
    </div>

    <div
      id="stock_container"
      class="w-1/2 p-2 flex flex-col justify-center items-center"
    >
      <div class="text-container w-3/4">
        <p>
          <span>品名:</span
          >サンプル名サンプル名ル名サンサンプル名ル名サンサンプル名ル名サンサンプル名ル名サンプ
        </p>
        <p><span>品番:</span>sample223</p>
        <p><span>数量:</span>32</p>
      </div>
      <div class="img_container">
        <img class="" src="images/stocks/not-image-sample2.png" alt="" />
      </div>
    </div>
  </div>
</template>
<style scoped lang="scss">
#page_title {
  font-size: 2rem;

  font-weight: bold;
  color: #109ff3;
  border-bottom: 3px solid gray;
  text-align: center;
}
.text-container {
  font-weight: bold;
  font-size: 1.6rem;

  & p {
    width: 100%;
    overflow-x: auto;
    white-space: nowrap;
    color: #109ff3;
    & span {

      font-size: 1rem;
      color: gray;
      margin-right: 1rem;
      opacity: 0.8;
      background-color: rgb(211, 211, 211);
    }
  }
}
.img_container {
  width: 100%;
  display: flex;
  justify-content: center;

  & img {
    width: 80%;
    object-fit: contain;
  }
}
</style>
