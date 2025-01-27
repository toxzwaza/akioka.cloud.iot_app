<script setup>
import { Head, Link } from "@inertiajs/vue3";

const props = defineProps({
  title: String,
});

const forwardButton = () => {
  window.history.forward();
};

const beforeButton = () => {
  window.history.back();
};
</script>

<template>
  <Head :title="props.title" />
  <header id="main_header">
    <nav class="flex items-center justify-between bg-blue-500 p-6">
      <div class="flex items-center flex-shrink-0 text-white mr-6">
        <Link class="flex items-center " :href="route('stock.home')">
          <i class="fas fa-home"></i>
          <span class="ml-2 font-semibold text-xl tracking-tight">備品倉庫</span>
        </Link>

        <!-- <div class="ml-4">
          <a
            href="#"
            class="inline-block text-sm px-4 py-2 leading-none border rounded text-white border-white hover:border-transparent hover:text-teal-500 hover:bg-white mt-4 lg:mt-0"
            >ログイン</a
          >
        </div> -->
      </div>
      <div class="w-full flex justify-between">
        <div class="w-1/2 flex items-center justify-center">
          <!-- ナビゲーション -->
          <Link class="nav_image" :href="route('stock.search')"><img src="/images/stocks/icons/search.png" alt="検索画面" /></Link>
          <Link class="nav_image" :href="route('stock.shipment')"><img src="/images/stocks/icons/shipment.png" alt="出庫画面" /></Link>
          <Link class="nav_image" :href="route('stock.order.create')"><img src="/images/stocks/icons/order.png" alt="発注画面" /></Link>
        </div>
        <div>
          <button
            @click="beforeButton"
            class="inline-block text-sm px-4 py-2 leading-none border rounded text-white border-white hover:border-transparent hover:text-teal-500 hover:bg-white mt-4 lg:mt-0"
          >
            <i class="fas fa-arrow-left"></i>
          </button>
          <button
            @click="forwardButton"
            class="ml-2 inline-block text-sm px-4 py-2 leading-none border rounded text-white border-white hover:border-transparent hover:text-teal-500 hover:bg-white mt-4 lg:mt-0"
          >
            <i class="fas fa-arrow-right"></i>
          </button>
        </div>
      </div>
    </nav>
  </header>

  <main id="main_container">
    <slot name="content" />
  </main>
</template>
<style lang="scss" scoped>
#header_container {
  height: 10vh;
}
#main_container {
  height: 90vh;
  overflow-y: scroll;

  padding: 4%;
  background-color: #f5f5f5;
}

.nav_image{
  width: 6rem;
  margin-right: 1rem;
}
</style>
