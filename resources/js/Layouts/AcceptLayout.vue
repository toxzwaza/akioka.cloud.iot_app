<script setup>
import { Head, Link } from "@inertiajs/vue3";

const props = defineProps({
  title: String,
});
</script>

<template>
  <Head :title="props.title" />
  <header class="sticky top-0 z-40">
    <nav class="flex items-center justify-between bg-gradient-to-r from-slate-800 to-slate-900 px-6 py-3 shadow-header">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 bg-emerald-500 rounded-xl flex items-center justify-center">
          <i class="fas fa-check-circle text-white text-base"></i>
        </div>
        <span class="font-bold text-lg text-white tracking-tight">Akioka Cloud.</span>
      </div>

      <div class="flex items-center gap-1">
        <Link
          :href="route('dashboard')"
          class="px-4 py-2 rounded-lg text-sm font-semibold transition-all duration-200"
          :class="{
            'bg-primary-600 text-white shadow-sm': route().current() == 'dashboard',
            'text-slate-300 hover:text-white hover:bg-white/10': route().current() != 'dashboard',
          }"
        >
          <i class="fas fa-clipboard-check mr-1.5"></i>
          Approvals
        </Link>
      </div>
    </nav>
  </header>

  <main class="min-h-[calc(100vh-60px)] bg-slate-50 p-6">
    <slot name="content" />
  </main>
</template>
