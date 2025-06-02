<template>
  <Dialog
    v-model:visible="dialogVisible"
    header="Choose Save Option"
    modal
    dismissableMask
    :closable="false"
    class="w-1/3"
  >
    <div class="p-fluid space-y-4">
      <p>Please select how you want to save the devices:</p>
    </div>

    <template #footer>
      <div class="flex justify-end space-x-2">
        <Button
          label="Save Devices to Inventory"
          icon="pi pi-check"
          class="p-button-primary"
          @click="onSaveInventory"
        />
        <Button
          label="Save as Draft"
          icon="pi pi-file"
          class="p-button-secondary"
          @click="onSaveDraft"
        />
      </div>
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';

const props = defineProps({
  // controla la visibilidad del diálogo
  visible: { type: Boolean, required: true }
});

const emit = defineEmits<{
  (e: 'update:visible', v: boolean): void;
  // evento al elegir guardar en inventario
  (e: 'toInventory'): void;
  // evento al elegir guardar como borrador
  (e: 'asDraft'): void;
}>();

// Puente para v-model:visible
const dialogVisible = computed({
  get: () => props.visible,
  set: v => emit('update:visible', v)
});

function onSaveInventory() {
  emit('toInventory');
  dialogVisible.value = false;
}

function onSaveDraft() {
  emit('asDraft');
  dialogVisible.value = false;
}
</script>