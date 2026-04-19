<script setup>
import { Link, Head } from "@inertiajs/vue3";
import { onMounted } from "vue";
import { router } from "@inertiajs/vue3";

import Message from "@/Components/Message.vue";

const props = defineProps({
  login_user: Object,
  url: String,
  title: String,
});

const logout = () => {
  if (confirm('Logout?')) {
    router.get(route('calc.logout'));
  }
};

onMounted(() => {
  console.log(props.login_user);
});
</script>
<template>
  <Head :title="title" />
  <header class="bg-white border-b border-slate-200 shadow-sm sticky top-0 z-40">
    <div class="px-6 py-3">
      <div class="flex items-center justify-between">
        <!-- Logo + Home -->
        <div class="flex items-center gap-3">
          <Link
            :href="route(url)"
            class="flex items-center gap-3 px-3 py-2 rounded-xl hover:bg-slate-100 transition-colors group"
          >
            <div class="w-9 h-9 bg-primary-100 rounded-xl flex items-center justify-center group-hover:bg-primary-200 transition-colors">
              <i class="fas fa-home text-primary-600"></i>
            </div>
            <span class="text-slate-700 font-semibold group-hover:text-primary-700 transition-colors">Home</span>
          </Link>

          <div v-if="props.login_user" class="h-5 w-px bg-slate-200"></div>

          <!-- User info -->
          <div
            v-if="props.login_user"
            @click="logout"
            class="flex items-center gap-2 px-3 py-2 hover:bg-rose-50 rounded-xl cursor-pointer transition-colors group"
          >
            <div class="w-8 h-8 bg-emerald-100 group-hover:bg-rose-100 rounded-full flex items-center justify-center transition-colors">
              <i class="fas fa-user text-emerald-600 group-hover:text-rose-600 text-xs transition-colors"></i>
            </div>
            <div class="text-sm">
              <span class="font-semibold text-slate-800 group-hover:text-rose-700 transition-colors">{{ props.login_user.user_name }}</span>
              <span class="text-slate-400 mx-1">/</span>
              <span class="text-slate-500 group-hover:text-rose-600 transition-colors">{{ props.login_user.location_name }}</span>
            </div>
            <i class="fas fa-sign-out-alt text-rose-400 text-xs opacity-0 group-hover:opacity-100 transition-opacity ml-1"></i>
          </div>
        </div>

        <!-- Navigation -->
        <nav class="flex gap-2">
          <Link
            :href="route('calc.home')"
            class="px-4 py-2 rounded-xl font-semibold text-sm transition-all duration-200 flex items-center gap-2"
            :class="{
              'bg-primary-600 text-white shadow-sm':
                route().current() == 'calc.home' || route().current() == 'calc.search' || route().current() == 'calc.show',
              'bg-slate-100 text-slate-600 hover:bg-slate-200':
                !(route().current() == 'calc.home' || route().current() == 'calc.search' || route().current() == 'calc.show')
            }"
          >
            <i class="fas fa-clipboard-list text-sm"></i>
            Inventory
          </Link>
          <Link
            v-if="props.login_user"
            :href="route('calc.new')"
            class="px-4 py-2 rounded-xl font-semibold text-sm transition-all duration-200 flex items-center gap-2"
            :class="{
              'bg-emerald-600 text-white shadow-sm': route().current() == 'calc.new',
              'bg-slate-100 text-slate-600 hover:bg-slate-200': route().current() != 'calc.new'
            }"
          >
            <i class="fas fa-plus text-sm"></i>
            New
          </Link>
        </nav>
      </div>
    </div>
  </header>

  <Message />

  <main class="min-h-[calc(100vh-64px)] bg-slate-50 p-6">
    <slot name="content" />
  </main>
</template>
