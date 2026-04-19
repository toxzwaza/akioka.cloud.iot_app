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
      <h1 class="text-base font-bold text-slate-800 whitespace-nowrap">
        <i class="fas fa-boxes text-amber-600 mr-2"></i>
        Retention
      </h1>
      <div class="h-5 w-px bg-slate-300"></div>
      <nav class="flex items-center gap-1">
        <Link
          :href="route('stock.retention')"
          class="px-4 py-2 rounded-lg text-sm font-semibold transition-all duration-200"
          :class="{
            'bg-primary-50 text-primary-700 ring-1 ring-primary-200': route().current() == 'stock.retention',
            'text-slate-500 hover:text-slate-700 hover:bg-slate-100': route().current() != 'stock.retention'
          }"
        >
          Retention List
        </Link>
      </nav>
    </div>
  </header>

  <main class="min-h-[calc(100vh-56px)] bg-slate-50 p-6">
    <slot name="content" />
  </main>
</template>
