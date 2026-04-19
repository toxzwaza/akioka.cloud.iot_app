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
        alert('Please select both user and location.');
        return;
    }

    router.post(route('calc.login'), form);
}
</script>
<template>
  <div class="w-full max-w-md mx-auto">
    <div class="card overflow-hidden">
      <!-- Header -->
      <div class="bg-primary-50 px-6 py-4 border-b border-primary-100">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 bg-primary-100 rounded-xl flex items-center justify-center">
            <i class="fas fa-sign-in-alt text-primary-600"></i>
          </div>
          <div>
            <h2 class="text-lg font-bold text-slate-800">Login</h2>
            <p class="text-xs text-slate-500">Inventory count system</p>
          </div>
        </div>
      </div>

      <!-- Form -->
      <div class="p-6">
        <form class="space-y-5">
          <div>
            <label class="form-label">
              <span class="text-rose-500">*</span> Operator
            </label>
            <select v-model="form.user_id" class="form-select-modern">
              <option value="0" disabled>Select operator</option>
              <option v-for="user in users" :key="user.id" :value="user.id">
                {{ user.name }}
              </option>
            </select>
          </div>

          <div>
            <label class="form-label">
              <span class="text-rose-500">*</span> Location
            </label>
            <select v-model="form.location_id" class="form-select-modern">
              <option value="0" disabled>Select location</option>
              <option v-for="location in locations" :key="location.id" :value="location.id">
                {{ location.name }}
              </option>
            </select>
          </div>

          <div class="pt-4 border-t border-slate-100">
            <button
              @click.prevent="login"
              :disabled="!form.user_id || form.user_id === 0 || !form.location_id || form.location_id === 0"
              class="btn-primary w-full disabled:opacity-50 disabled:cursor-not-allowed"
              type="button"
            >
              <i class="fas fa-sign-in-alt"></i>
              Start Inventory
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
