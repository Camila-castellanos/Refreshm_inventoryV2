<template>
  <Dialog
    v-model:visible="dialogManager.showSessionDialog"
    modal
    :closable="false"
    :style="{ width: '300px', borderRadius: '0.75rem' }"
    contentClass="p-6"
  >
    <div class="text-center space-y-4">
      <h3 class="text-xl font-semibold"><i class="pi pi-exclamation-triangle mr-2"></i> Session Expired</h3>
      <p class=" text-sm leading-relaxed">
        {{ dialogManager.onLoginPage ? "Please reload the page." : "Your session has expired. Please log in again to continue." }}
      </p>
      <Button
        label="OK"
        class="!bg-primary !border-primary hover:!bg-primary-dark w-full mt-4"
        @click="handleOk"
        autofocus
      />
    </div>
  </Dialog>
</template>

<script setup>
import { dialogManager } from '@/dialogManager';
import { Button, Dialog } from 'primevue';

function handleOk() {
  dialogManager.showSessionDialog = false;
}
</script>

<style scoped>
/* PrimeVue theme overrides */
.p-dialog .p-dialog-content {
  padding: 0 !important;
}
</style>
