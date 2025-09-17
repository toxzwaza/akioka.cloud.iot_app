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
  if (confirm('ログアウトしますか？')) {
    router.get(route('calc.logout'));
  }
};

onMounted(() => {
  console.log(props.login_user);
});
</script>
<template>
  <Head :title="title" />
  <header class="bg-white border-b border-gray-200 shadow-sm">
    <div class="px-6 py-4">
      <div class="flex items-center justify-between">
        <!-- ロゴ・ホームエリア -->
        <div class="flex items-center gap-4">
          <Link 
            :href="route(url)"
            class="flex items-center gap-3 px-4 py-2 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors group"
          >
            <div class="bg-blue-100 rounded-full p-2 group-hover:bg-blue-200 transition-colors">
              <i class="fas fa-home text-blue-600 text-lg"></i>
            </div>
            <span class="text-gray-700 font-semibold group-hover:text-blue-700 transition-colors">ホーム</span>
          </Link>

          <!-- <Link 
            :href="route('stock.home')"
            class="flex items-center gap-3 px-4 py-2 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors group"
          >
            <div class="bg-blue-100 rounded-full p-2 group-hover:bg-blue-200 transition-colors">
              <i class="fas fa-home text-blue-600 text-lg"></i>
            </div>
            <span class="text-gray-700 font-semibold group-hover:text-blue-700 transition-colors">TOP</span>
          </Link> -->

          <!-- ユーザー情報（クリックでログアウト） -->
          <div 
            v-if="props.login_user" 
            @click="logout"
            class="flex items-center gap-2 px-4 py-2 hover:bg-red-50 rounded-lg cursor-pointer transition-colors group"
          >
            <div class="bg-green-100 group-hover:bg-red-100 rounded-full p-1 transition-colors">
              <i class="fas fa-user text-green-600 group-hover:text-red-600 text-sm transition-colors"></i>
            </div>
            <div class="text-sm">
              <span class="font-semibold text-gray-800 group-hover:text-red-700 transition-colors">{{ props.login_user.user_name }}</span>
              <span class="text-gray-500 mx-1">•</span>
              <span class="text-gray-600 group-hover:text-red-600 transition-colors">{{ props.login_user.location_name }}</span>
            </div>
            <div class="ml-2 opacity-0 group-hover:opacity-100 transition-opacity">
              <i class="fas fa-sign-out-alt text-red-500 text-xs"></i>
            </div>
          </div>
        </div>

        <!-- ナビゲーションメニュー -->
        <nav class="flex gap-2">
          <Link
            :href="route('calc.home')"
            class="px-4 py-2 rounded-lg font-semibold text-sm transition-all duration-200 flex items-center gap-2"
            :class="{
              'bg-gradient-to-r from-blue-500 to-blue-600 text-white shadow-sm': 
                route().current() == 'calc.home' || route().current() == 'calc.search' || route().current() == 'calc.show',
              'bg-gray-100 text-gray-700 hover:bg-gray-200': 
                !(route().current() == 'calc.home' || route().current() == 'calc.search' || route().current() == 'calc.show')
            }"
          >
            <i class="fas fa-clipboard-list text-sm"></i>
            棚卸登録
          </Link>
          <Link
            v-if="props.login_user"
            :href="route('calc.new')"
            class="px-4 py-2 rounded-lg font-semibold text-sm transition-all duration-200 flex items-center gap-2"
            :class="{
              'bg-gradient-to-r from-green-500 to-green-600 text-white shadow-sm': 
                route().current() == 'calc.new',
              'bg-gray-100 text-gray-700 hover:bg-gray-200': 
                route().current() != 'calc.new'
            }"
          >
            <i class="fas fa-plus text-sm"></i>
            棚卸新規
          </Link>
        </nav>
      </div>
    </div>
  </header>

  <Message />

  <main id="main_container">
    <slot name="content" />
  </main>
</template>


<style>
html {
  overflow-y: scroll;
}

#main_container {
  height: auto;
  width: 100vw;
  padding: 5%;
  background-color: #f8fafc;
  min-height: calc(100vh - 80px);
}
</style>