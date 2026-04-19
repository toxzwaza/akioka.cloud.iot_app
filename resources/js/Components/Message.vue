<script setup>
import { ref, onMounted } from "vue";

const isVisible = ref(true);

onMounted(() => {
  if (isVisible.value) {
    setTimeout(() => {
      isVisible.value = false;
    }, 5000);
  }
});

const closeMessage = () => {
  isVisible.value = false;
};
</script>

<template>
  <div
    v-if="$page.props.flash.message && isVisible"
    class="modal-overlay"
    @click="closeMessage"
  >
    <div
      class="modal-content w-full max-w-md transform transition-all"
      @click.stop
      role="alert"
    >
      <!-- Header -->
      <div
        class="px-6 py-4 border-b"
        :class="{
          'bg-slate-50 border-slate-200': $page.props.flash.status === 'info',
          'bg-rose-50 border-rose-200': $page.props.flash.status === 'error',
          'bg-emerald-50 border-emerald-200': $page.props.flash.status === 'success',
        }"
      >
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div
              class="w-10 h-10 rounded-xl flex items-center justify-center"
              :class="{
                'bg-slate-100': $page.props.flash.status === 'info',
                'bg-rose-100': $page.props.flash.status === 'error',
                'bg-emerald-100': $page.props.flash.status === 'success',
              }"
            >
              <i
                class="text-lg"
                :class="{
                  'fas fa-info-circle text-slate-600': $page.props.flash.status === 'info',
                  'fas fa-exclamation-triangle text-rose-600': $page.props.flash.status === 'error',
                  'fas fa-check-circle text-emerald-600': $page.props.flash.status === 'success',
                }"
              ></i>
            </div>
            <h3
              class="text-base font-bold"
              :class="{
                'text-slate-800': $page.props.flash.status === 'info',
                'text-rose-800': $page.props.flash.status === 'error',
                'text-emerald-800': $page.props.flash.status === 'success',
              }"
            >
              {{
                $page.props.flash.status == "info"
                  ? "Info"
                  : $page.props.flash.status == "success"
                  ? "Success"
                  : "Error"
              }}
            </h3>
          </div>
          <button
            @click="closeMessage"
            class="btn-icon"
          >
            <i class="fas fa-times text-sm"></i>
          </button>
        </div>
      </div>

      <!-- Message body -->
      <div class="px-6 py-5">
        <p
          class="text-sm leading-relaxed"
          :class="{
            'text-slate-600': $page.props.flash.status === 'info',
            'text-rose-700': $page.props.flash.status === 'error',
            'text-emerald-700': $page.props.flash.status === 'success',
          }"
        >
          {{ $page.props.flash.message }}
        </p>
      </div>

      <!-- Action -->
      <div class="px-6 py-4 border-t border-slate-100 bg-slate-50">
        <button @click="closeMessage" class="btn-secondary w-full">
          <i class="fas fa-check text-xs"></i>
          OK
        </button>
      </div>
    </div>
  </div>
</template>
