<script setup>
import { ref, watch } from "vue";

const props = defineProps({
  modalImageSrc: String,
  modalStockId: Number,
});

const currentImageSrc = ref(props.modalImageSrc);
const currentStockId = ref(props.modalStockId);

watch(
  [() => props.modalImageSrc, () => props.modalStockId],
  ([newSrc, newId]) => {
    currentImageSrc.value = newSrc;
    currentStockId.value = newId;
  }
);

const emit = defineEmits(["closeModal"]);
</script>
<template>
  <div class="modal-overlay" @click="emit('closeModal')">
    <div
      @click.stop
      class="modal-content relative"
      style="width: 90vw; height: 90vh;"
      role="dialog"
      aria-modal="true"
    >
      <!-- Close button -->
      <button
        @click="emit('closeModal')"
        class="absolute top-4 right-4 z-10 w-10 h-10 bg-slate-800/60 hover:bg-slate-800/80 text-white rounded-xl flex items-center justify-center transition-colors"
      >
        <i class="fas fa-times"></i>
      </button>

      <!-- Image -->
      <div class="w-full h-full flex items-center justify-center p-6 bg-slate-50">
        <img
          :src="currentImageSrc"
          alt="Image"
          class="max-w-full max-h-full object-contain rounded-lg"
        />
      </div>
    </div>
  </div>
</template>
