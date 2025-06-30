<script setup>
import { Head, Link } from "@inertiajs/vue3";

const props = defineProps({
  title: String,
  padding: Boolean,
});

const forwardButton = () => {
  window.history.forward();
};

const beforeButton = () => {
  window.history.back();
};

const reloadPage = () => {
  window.location.reload()
}
</script>

<template>
  <Head :title="props.title" />
  <header id="main_header">
    <nav class="flex items-center justify-between bg-blue-500 py-2 px-4">
      <div class="flex items-center flex-shrink-0 text-white mr-6">
        <Link class="flex items-center" :href="route('stock.home')">
          <i id="home_icon" class="fas fa-home"></i>
          <!-- <span class="ml-2 font-semibold text-xl tracking-tight">備品倉庫</span> -->
        </Link>

        <!-- <div class="ml-4">
          <a
            href="#"
            class="inline-block text-sm px-4 py-2 leading-none border rounded text-white border-white hover:border-transparent hover:text-teal-500 hover:bg-white mt-4 lg:mt-0"
            >ログイン</a
          >
        </div> -->
      </div>
      <div class="w-full flex justify-between items-center">
        <div class="w-1/2 flex items-center justify-center">
          <!-- ナビゲーション -->
          <Link
            class="nav_image"
            :class="{ 'opacity-50': route().current().endsWith('search') }"
            :href="route('stock.search')"
            ><img src="/images/stocks/icons/search.png" alt="検索画面"
          /></Link>
          <Link
            class="nav_image"
            :class="{ 'opacity-50': route().current().endsWith('shipment') }"
            :href="route('stock.shipment')"
            ><img src="/images/stocks/icons/shipment.png" alt="出庫画面"
          /></Link>
          <Link
            class="nav_image"
            :class="{ 'opacity-50': route().current().includes('receive') }"
            :href="route('stock.receive.home')"
            ><img src="/images/stocks/icons/receive.png" alt="納品画面"
          /></Link>
          <Link
            class="nav_image"
            :class="{ 'opacity-50': route().current().includes('retention') }"
            :href="route('stock.retention.home')"
            ><img src="/images/stocks/icons/retention.png" alt="納品画面"
          /></Link>
          <!-- <Link class="nav_image" :class="{'opacity-50': route().current().endsWith('order') }" :href="route('stock.order.create')"><img src="/images/stocks/icons/order.png" alt="発注画面" /></Link> -->
        </div>
        <div>
          <button
            @click="beforeButton"
            class="btn inline-block text-sm px-4 py-2 leading-none border rounded text-white border-white hover:border-transparent hover:text-teal-500 hover:bg-white mt-4 lg:mt-0"
          >
            <i class="arrow-icon fas fa-arrow-left"></i>
          </button>
          <button
            @click="forwardButton"
            class="arrow-icon btn ml-2 inline-block text-sm px-4 py-2 leading-none border rounded text-white border-white hover:border-transparent hover:text-teal-500 hover:bg-white mt-4 lg:mt-0"
          >
            <i class="fas fa-arrow-right"></i>
          </button>
          <button
            @click="reloadPage"
            class="ml-6 arrow-icon btn inline-block text-sm px-4 py-2 leading-none border rounded text-white border-white hover:border-transparent hover:text-teal-500 hover:bg-white mt-4 lg:mt-0"
          >
            <i class="fas fa-sync-alt"></i>
          </button>
        </div>
      </div>
    </nav>
  </header>

  <main id="main_container" :class="{ padding_container: props.padding }">
    <slot name="content" />
  </main>
</template>
<style lang="scss" scoped>
#main_header {
  height: auto;
  & #home_icon {
    font-size: 2rem;
  }
  & .arrow-icon {
    font-size: 1.2rem;
  }
}
#main_container {
  height: 90vh;
  overflow-y: scroll;
  background-color: #f5f5f5;
  padding: 4%;
  &.padding_container {
    padding: 0;
  }
}

.nav_image {
  width: 6rem;
  margin-right: 1rem;
}
</style>
