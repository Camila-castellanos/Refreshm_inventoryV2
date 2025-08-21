<template>
  <div
    ref="tooltipTarget"
    v-tooltip.bottom="{
      value: title,
      showDelay: 300,
      hideDelay: 100,
      pt: {
        root: {
          class: 'primevue-header-tooltip'
        },
        arrow: {
          style: {
            borderBottomColor: 'var(--p-primary-color)'
          }
        },
        text: {
          class: 'custom-tooltip-text'
        }
      }
    }"
    class="header-tooltip-wrapper"
  >
    <slot />
  </div>
</template>

<script lang="ts" setup>
interface Props {
  title: string;
}

defineProps<Props>();
</script>

<style scoped>
.header-tooltip-wrapper {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  cursor: help;
}

:global(.custom-tooltip-text) {
  @apply !bg-gradient-to-br !from-primary !to-primary-600 !text-primary-contrast;
  @apply !font-medium !text-sm !px-4 !py-2 !rounded-xl !shadow-2xl;
  @apply !backdrop-blur-sm !border !border-primary-300/20;
  max-width: 300px !important;
}

:global(.primevue-header-tooltip) {
  z-index: 1001 !important;
}

/* Animación de entrada */
:global(.primevue-header-tooltip .p-tooltip-text) {
  animation: tooltipFadeIn 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

@keyframes tooltipFadeIn {
  from {
    opacity: 0;
    transform: translateY(-10px) scale(0.9);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}
</style>