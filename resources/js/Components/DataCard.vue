<script setup>
const props = defineProps({
    data: Object,
});
</script>
<template>
  <div class="data-card card">
    <div class="p-4">
      <!-- Header -->
      <div class="flex items-start justify-between mb-3">
        <div>
          <h2 class="text-sm font-bold text-slate-800">{{ props.data.place_name }}</h2>
          <p class="text-xs text-slate-400 mt-0.5">{{ props.data.computer_name }}</p>
        </div>
        <span class="badge bg-slate-100 text-slate-500 text-[10px]">
          {{ new Date(props.data.created_at).toLocaleString('ja-JP', {month: 'numeric', day: 'numeric', hour: 'numeric', minute: 'numeric'}) }}
        </span>
      </div>

      <!-- Data rows -->
      <div class="space-y-2">
        <div class="data-row">
          <div class="data-icon bg-rose-50 text-rose-500">
            <i class="fas fa-thermometer-half"></i>
          </div>
          <div class="data-value-group">
            <span class="data-value">{{ props.data.temperature }}</span>
            <span class="data-unit">&deg;C</span>
          </div>
        </div>
        <div class="data-row">
          <div class="data-icon bg-sky-50 text-sky-500">
            <i class="fas fa-tint"></i>
          </div>
          <div class="data-value-group">
            <span class="data-value">{{ props.data.humidity }}</span>
            <span class="data-unit">%</span>
          </div>
        </div>
        <div class="data-row">
          <div class="data-icon bg-emerald-50 text-emerald-500">
            <i class="fas fa-wind"></i>
          </div>
          <div class="data-value-group">
            <span class="data-value">{{ props.data.co2 }}</span>
            <span class="data-unit">PPM</span>
          </div>
        </div>
      </div>

      <!-- Log button -->
      <div class="mt-3 pt-3 border-t border-slate-100">
        <button class="w-full flex items-center justify-center gap-2 text-xs font-semibold text-primary-600 hover:text-primary-700 py-1.5 rounded-lg hover:bg-primary-50 transition-all">
          <span>Data Log</span>
          <i class="fas fa-arrow-right text-[10px]"></i>
        </button>
      </div>
    </div>
  </div>
</template>
<style lang="scss" scoped>
.data-card {
  min-width: 200px;
  max-width: 240px;
  flex-shrink: 0;
}

.data-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.5rem 0;

  &:not(:last-child) {
    border-bottom: 1px solid #f1f5f9;
  }
}

.data-icon {
  width: 2rem;
  height: 2rem;
  border-radius: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
}

.data-value-group {
  display: flex;
  align-items: baseline;
  gap: 0.25rem;
}

.data-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1e293b;
  line-height: 1;
}

.data-unit {
  font-size: 0.75rem;
  font-weight: 500;
  color: #94a3b8;
}
</style>
