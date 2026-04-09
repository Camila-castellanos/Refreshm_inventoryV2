<template>
  <Dialog 
    v-model:visible="visible" 
    header="Expense Breakdown" 
    :modal="true" 
    class="w-full max-w-lg"
    :dismissableMask="true"
  >
    <div v-if="breakdown && breakdown.length > 0">
      <DataTable 
        :value="breakdown" 
        class="p-datatable-sm" 
        stripedRows 
        responsiveLayout="scroll"
        :rows="10"
        :paginator="breakdown.length > 10"
      >
        <Column field="category" header="Category" sortable>
          <template #body="slotProps">
            <span class="font-medium text-sm text-surface-900 dark:text-surface-0">
              {{ slotProps.data.category || 'Uncategorized' }}
            </span>
          </template>
        </Column>
        
        <Column field="total" header="Total" sortable class="text-right">
          <template #body="slotProps">
            <span class="text-sm font-semibold text-surface-700 dark:text-surface-0/70">
              {{ formatCurrency(slotProps.data.total) }}
            </span>
          </template>
        </Column>
      </DataTable>
    </div>
    <div v-else class="flex flex-col items-center justify-center p-8 text-surface-500 dark:text-surface-400">
      <i class="pi pi-info-circle text-4xl mb-4"></i>
      <p>No expenses found for this period</p>
    </div>
  </Dialog>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import Dialog from 'primevue/dialog';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import { ExpenseBreakdown } from '@/Lib/types';

const props = defineProps<{
  modelValue: boolean;
  breakdown: ExpenseBreakdown[];
}>();

const emit = defineEmits(['update:modelValue']);

const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
});

function formatCurrency(value: any) {
  const num = Number(value)
  if (isNaN(num) || num <= 0) return '$ 0.00'
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(num).replace(/^/, '$ ')
}
</script>
