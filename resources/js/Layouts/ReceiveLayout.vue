<script setup>
import { Head, Link } from "@inertiajs/vue3";

const props = defineProps({
  title: String,
});
</script>
<template>
  <Head :title="props.title" />
  <header class="bg-white border-b border-slate-200 shadow-sm sticky top-0 z-40">
    <div class="px-6 py-3 flex items-center gap-6">
      <Link
        :href="route('stock.home')"
        class="inline-flex items-center gap-2 text-slate-500 hover:text-slate-800 transition-colors"
        title="在庫管理トップへ"
      >
        <i class="fas fa-arrow-left text-sm"></i>
        <span class="text-sm font-semibold hidden md:inline">在庫管理</span>
      </Link>
      <div class="h-5 w-px bg-slate-300"></div>
      <h1 class="text-base font-bold text-slate-800 whitespace-nowrap">
        <i class="fas fa-truck-loading text-primary-600 mr-2"></i>
        納品
      </h1>
      <div class="h-5 w-px bg-slate-300"></div>
      <nav class="flex items-center gap-1">
        <Link
          :href="route('stock.receive.home')"
          class="px-4 py-2 rounded-lg text-sm font-semibold transition-all duration-200"
          :class="{
            'bg-primary-50 text-primary-700 ring-1 ring-primary-200': ['stock.receive.home', 'stock.receive'].includes(route().current()),
            'text-slate-500 hover:text-slate-700 hover:bg-slate-100': !['stock.receive.home', 'stock.receive'].includes(route().current())
          }"
        >
          納品書登録
        </Link>
        <Link
          :href="route('stock.receive.archive')"
          class="px-4 py-2 rounded-lg text-sm font-semibold transition-all duration-200"
          :class="{
            'bg-primary-50 text-primary-700 ring-1 ring-primary-200': route().current() == 'stock.receive.archive',
            'text-slate-500 hover:text-slate-700 hover:bg-slate-100': route().current() != 'stock.receive.archive'
          }"
        >
          納品処理
        </Link>
        <Link
          :href="route('stock.receive.receipt')"
          class="px-4 py-2 rounded-lg text-sm font-semibold transition-all duration-200"
          :class="{
            'bg-primary-50 text-primary-700 ring-1 ring-primary-200': route().current() == 'stock.receive.receipt',
            'text-slate-500 hover:text-slate-700 hover:bg-slate-100': route().current() != 'stock.receive.receipt'
          }"
        >
          受け渡し
        </Link>
      </nav>
    </div>
  </header>

  <main class="min-h-[calc(100vh-56px)] bg-slate-50 p-6">
    <slot name="content" />
  </main>
</template>
