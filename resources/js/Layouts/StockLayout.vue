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
  <header class="sticky top-0 z-40">
    <nav class="flex items-center justify-between bg-gradient-to-r from-primary-700 to-primary-800 px-6 py-4 shadow-header">
      <!-- Left: Home + Nav -->
      <div class="flex items-center gap-6">
        <Link :href="route('stock.home')" class="flex items-center gap-3 text-white hover:text-primary-200 transition-colors">
          <div class="w-12 h-12 bg-white/15 rounded-xl flex items-center justify-center">
            <i class="fas fa-warehouse text-xl"></i>
          </div>
          <span class="font-bold text-lg tracking-tight hidden md:inline">Akioka Cloud</span>
        </Link>

        <div class="flex items-center gap-2">
          <Link
            :href="route('stock.search')"
            class="nav-link"
            :class="{ 'nav-link-active': route().current()?.endsWith('search') || route().current()?.endsWith('search.result') }"
          >
            <i class="fas fa-search text-base"></i>
            <span>探す</span>
          </Link>
          <Link
            :href="route('stock.shipment')"
            class="nav-link"
            :class="{ 'nav-link-active': route().current()?.endsWith('shipment') }"
          >
            <i class="fas fa-dolly text-base"></i>
            <span>出庫</span>
          </Link>
          <Link
            :href="route('stock.receive.home')"
            class="nav-link"
            :class="{ 'nav-link-active': route().current()?.includes('receive') }"
          >
            <i class="fas fa-truck-loading text-base"></i>
            <span>納品</span>
          </Link>
        </div>
      </div>

      <!-- Right: History buttons -->
      <div class="flex items-center gap-2">
        <button @click="beforeButton" class="w-12 h-12 flex items-center justify-center rounded-xl text-white/80 hover:text-white hover:bg-white/10 active:bg-white/20 transition-all">
          <i class="fas fa-chevron-left text-base"></i>
        </button>
        <button @click="forwardButton" class="w-12 h-12 flex items-center justify-center rounded-xl text-white/80 hover:text-white hover:bg-white/10 active:bg-white/20 transition-all">
          <i class="fas fa-chevron-right text-base"></i>
        </button>
        <button @click="reloadPage" class="w-12 h-12 flex items-center justify-center rounded-xl text-white/80 hover:text-white hover:bg-white/10 active:bg-white/20 transition-all ml-2">
          <i class="fas fa-sync-alt text-base"></i>
        </button>
      </div>
    </nav>
  </header>

  <main id="main_container" :class="{ 'p-0': props.padding }">
    <slot name="content" />
  </main>
</template>
<style lang="scss" scoped>
.nav-link {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.25rem;
  border-radius: 0.75rem;
  font-size: 0.9375rem;
  font-weight: 600;
  color: rgba(255, 255, 255, 0.8);
  transition: all 0.2s;
  white-space: nowrap;
  min-height: 44px;

  &:hover {
    color: white;
    background-color: rgba(255, 255, 255, 0.1);
  }

  &:active {
    background-color: rgba(255, 255, 255, 0.25);
  }

  &.nav-link-active {
    color: white;
    background-color: rgba(255, 255, 255, 0.2);
    box-shadow: inset 0 -2px 0 0 white;
  }
}

#main_container {
  min-height: calc(100vh - 60px);
  overflow-y: auto;
  background-color: #f8fafc;
  padding: 1.5rem;
}
</style>
