<script setup>
import { ref, onMounted, watch } from "vue";
import { Link } from "@inertiajs/vue3";

const props = defineProps({
  modalImageSrc: String,
  modalStockId: Number,
});

const modalStatus = ref(props.modalStatus);
const currentImageSrc = ref(props.modalImageSrc);
const currentStockId = ref(props.modalStockId);

watch(
  [() => props.modalImageSrc, () => props.modalStockId],
  ([newSrc, newId]) => {
    currentImageSrc.value = newSrc;
    currentStockId.value = newId;
    console.log("更新", currentImageSrc.value, currentStockId.value);
  }
);

const emit = defineEmits(["closeModal"]);
</script>
<template>
  <div class="modal" id="modal-1" aria-hidden="true">
    <div
      @click="emit('closeModal')"
      class="modal__overlay"
      tabindex="-1"
      data-micromodal-close
    >
      <div
        @click.stop
        class="modal__container"
        role="dialog"
        aria-modal="true"
        aria-labelledby="modal-1-title"
      >
        <header class="modal__header">
          <button
            @click="emit('closeModal')"
            class="modal__close"
            aria-label="Close modal"
            data-micromodal-close
          ></button>
        </header>
        <div class="text-right mb-4">
          <a v-if="currentStockId"
            :class="{
              'bg-indigo-500 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded  text-sm font-mono': true,
            }"
            :href="route('stock.edit.stocks', { stock_id: currentStockId })"
            >在庫編集ページへ遷移</a
          >
        </div>

        <main class="modal__content" id="modal-1-content">
          <img :src="currentImageSrc" alt="サンプル画像" />
        </main>
      </div>
    </div>
  </div>
</template>
<style scoped>
.modal {
  font-family: -apple-system, BlinkMacSystemFont, avenir next, avenir,
    helvetica neue, helvetica, ubuntu, roboto, noto, segoe ui, arial, sans-serif;
}
main {
  height: 100%;
  width: 100%;
  margin: 0 !important;
  display: flex;
  justify-content: center;
  align-items: center;
}
main img {
  height: 100%;
  object-fit: contain;
}

.modal__overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal__container {
  background-color: #fff;
  padding: 30px;
  width: 90vw;
  height: 90vh;
  border-radius: 4px;
  overflow-y: auto;
  box-sizing: border-box;
}

.modal__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal__title {
  margin-top: 0;
  margin-bottom: 0;
  font-weight: 600;
  font-size: 1.25rem;
  line-height: 1.25;
  color: #00449e;
  box-sizing: border-box;
}

.modal__close {
  background: transparent;
  border: 0;
}

.modal__header .modal__close:before {
  content: "\2715";
}

.modal__content {
  margin-top: 2rem;
  margin-bottom: 2rem;
  line-height: 1.5;
  color: rgba(0, 0, 0, 0.8);
}

.modal__btn {
  font-size: 0.875rem;
  padding-left: 1rem;
  padding-right: 1rem;
  padding-top: 0.5rem;
  padding-bottom: 0.5rem;
  background-color: #e6e6e6;
  color: rgba(0, 0, 0, 0.8);
  border-radius: 0.25rem;
  border-style: none;
  border-width: 0;
  cursor: pointer;
  -webkit-appearance: button;
  text-transform: none;
  overflow: visible;
  line-height: 1.15;
  margin: 0;
  will-change: transform;
  -moz-osx-font-smoothing: grayscale;
  -webkit-backface-visibility: hidden;
  backface-visibility: hidden;
  -webkit-transform: translateZ(0);
  transform: translateZ(0);
  transition: -webkit-transform 0.25s ease-out;
  transition: transform 0.25s ease-out;
  transition: transform 0.25s ease-out, -webkit-transform 0.25s ease-out;
}

.modal__btn:focus,
.modal__btn:hover {
  -webkit-transform: scale(1.05);
  transform: scale(1.05);
}

.modal__btn-primary {
  background-color: #00449e;
  color: #fff;
}

/**************************\
    Demo Animation Style
  \**************************/
@keyframes mmfadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes mmfadeOut {
  from {
    opacity: 1;
  }
  to {
    opacity: 0;
  }
}

@keyframes mmslideIn {
  from {
    transform: translateY(15%);
  }
  to {
    transform: translateY(0);
  }
}

@keyframes mmslideOut {
  from {
    transform: translateY(0);
  }
  to {
    transform: translateY(-10%);
  }
}

.micromodal-slide {
  display: none;
}

.micromodal-slide.is-open {
  display: block;
}

.micromodal-slide[aria-hidden="false"] .modal__overlay {
  animation: mmfadeIn 0.3s cubic-bezier(0, 0, 0.2, 1);
}

.micromodal-slide[aria-hidden="false"] .modal__container {
  animation: mmslideIn 0.3s cubic-bezier(0, 0, 0.2, 1);
}

.micromodal-slide[aria-hidden="true"] .modal__overlay {
  animation: mmfadeOut 0.3s cubic-bezier(0, 0, 0.2, 1);
}

.micromodal-slide[aria-hidden="true"] .modal__container {
  animation: mmslideOut 0.3s cubic-bezier(0, 0, 0.2, 1);
}

.micromodal-slide .modal__container,
.micromodal-slide .modal__overlay {
  will-change: transform;
}
</style>
