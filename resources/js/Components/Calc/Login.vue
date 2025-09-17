<script setup>
import { onMounted, reactive } from "vue";
import { router, Link } from "@inertiajs/vue3";

const props = defineProps({
  users: Array,
  locations: Array,
});

onMounted(() => {
  console.log(props.users, props.locations);
});

const form = reactive({
  user_id: null,
  location_id: null,
});

const login = ()=> {
    console.log(form);
    if(!(form.user_id && form.location_id)){
        alert('実施者もしくは実施場所が未選択の可能性があります。');
        return;
    }

    router.post(route('calc.login'), form);
}
</script>
<template>
  <div class="w-full max-w-md mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <!-- ログインヘッダー -->
      <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-gray-200">
        <div class="flex items-center gap-3">
          <div class="bg-blue-100 rounded-full p-2">
            <i class="fas fa-sign-in-alt text-blue-600"></i>
          </div>
          <div>
            <h2 class="text-xl font-bold text-gray-800">ログイン</h2>
            <p class="text-sm text-gray-600">棚卸システムにログインします</p>
          </div>
        </div>
      </div>

      <!-- ログインフォーム -->
      <div class="p-6">
        <form class="space-y-6">
          <!-- 棚卸実施者 -->
          <div class="space-y-2">
            <label class="block text-sm font-semibold text-gray-700" for="user">
              <span class="text-red-500">*</span> 棚卸実施者
            </label>
            <select
              v-model="form.user_id"
              class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
              id="user"
            >
              <option value="0" disabled>実施者を選択してください</option>
              <option v-for="user in users" :key="user.id" :value="user.id">
                {{ user.name }}
              </option>
            </select>
          </div>

          <!-- 棚卸実施場所 -->
          <div class="space-y-2">
            <label class="block text-sm font-semibold text-gray-700" for="location">
              <span class="text-red-500">*</span> 棚卸実施場所
            </label>
            <select
              v-model="form.location_id"
              class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
              id="location"
            >
              <option value="0" disabled>実施場所を選択してください</option>
              <option v-for="location in locations" :key="location.id" :value="location.id">
                {{ location.name }}
              </option>
            </select>
          </div>

          <!-- ログインボタン -->
          <div class="pt-6 border-t border-gray-200">
            <button
              @click.prevent="login"
              :disabled="!form.user_id || form.user_id === 0 || !form.location_id || form.location_id === 0"
              class="w-full px-6 py-3 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 disabled:from-gray-400 disabled:to-gray-500 disabled:cursor-not-allowed text-white font-semibold rounded-lg transition-all duration-200 shadow-sm hover:shadow-md flex items-center justify-center gap-2"
              type="button"
            >
              <i class="fas fa-sign-in-alt"></i>
              棚卸開始
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
