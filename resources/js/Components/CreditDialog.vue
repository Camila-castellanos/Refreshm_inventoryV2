<template>
  <Dialog 
    v-model:visible="localVisible" 
    modal 
    :header="creditDialogMode === 'add' ? `Usable Credit: $${maxCredit.toFixed(2)}` : 'Edit Credit'" 
    :style="{ width: '28rem' }"
    class="p-fluid"
  >
    <div class="flex flex-col gap-4 p-4">
      <div class="flex flex-col gap-3">
        <label for="credit-amount" class="font-semibold text-center block">
          {{ creditDialogMode === 'add' ? 'Credit Amount' : 'Current Credit Amount' }}
        </label>
        <InputNumber 
          id="credit-amount"
          v-model="localInputValue" 
          fluid
          class="relative left-1"
          mode="currency" 
          currency="USD" 
          locale="en-US"
          :max="creditDialogMode === 'add' ? maxCredit : undefined"
          :min="0"
          showButtons
          :step="0.01"
        />
      </div>
      <small v-if="creditDialogMode === 'add'" class="text-surface-500 text-center block">
        Maximum credit available: ${{ maxCredit.toFixed(2) }}
      </small>
      <small v-else class="text-surface-500 text-center block">
        Current credit: ${{ currentCredit.toFixed(2) }}
      </small>
    </div>
    
    <template #footer>
      <Button 
        label="Cancel" 
        variant="outlined"
        severity="secondary" 
        @click="localVisible = false" 
      />
      <Button 
        label="Confirm" 
        variant="outlined"
        severity="secondary"
        :disabled="creditDialogMode === 'add' ? (!localInputValue || localInputValue <= 0 || localInputValue > maxCredit) : (!localInputValue || localInputValue < 0)"
        @click="$emit('confirm')" 
      />
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { Dialog, Button, InputNumber } from 'primevue';
import { computed } from 'vue';

const props = defineProps<{
  creditDialogVisible: boolean;
  creditDialogMode: 'add' | 'edit';
  creditInputValue: number;
  maxCredit: number;
  currentCredit: number;
}>();

const emit = defineEmits<{
  confirm: [];
  'update:creditDialogVisible': [value: boolean];
  'update:creditInputValue': [value: number];
}>();

const localVisible = computed({
  get: () => props.creditDialogVisible,
  set: (value) => emit('update:creditDialogVisible', value)
});

const localInputValue = computed({
  get: () => props.creditInputValue,
  set: (value) => emit('update:creditInputValue', value)
});
</script>
