<template>
    <Dialog
        v-model:visible="dialogVisible"
        header="Add Items to Sale"
        modal
        dismissableMask
        :closable="false"
        class="min-w-[520px] bg-white rounded-lg shadow-lg p-6 mx-auto"
    >
        <div class="space-y-4">
            <div class="flex flex-col">
                <label for="sale-select" class="mb-1 text-sm font-medium text-gray-700">
                    Select Sale
                </label>
                <Dropdown
                    id="sale-select"
                    v-model="selectedSale"
                    :options="options"
                    option-label="label"
                    option-value="value"
                    placeholder="Select a sale"
                    :disabled="loading"
                    filter
                    :filterValue="searchTerm"           
                    @filter="onFilter" 
                    showClear
                    class="w-full border border-gray-300 rounded px-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
            </div>
            <div v-if="loading" class="text-gray-500 text-sm">
                Loading sales...
            </div>
            <div v-if="error" class="text-red-600 text-sm">
                Error loading sales.
            </div>
        </div>
        <template #footer>
            <div class="flex justify-end space-x-2 pt-4">
                <Button
                    label="Cancel"
                    class="p-button-text text-gray-500 hover:text-gray-700"
                    @click="onCancel"
                />
                <Button
                    label="Add"
                    class="text-white bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="!selectedSale || loading"
                    @click="onAdd"
                />
            </div>
        </template>
    </Dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import Button from 'primevue/button';

const props = defineProps<{ visible: boolean }>();
const emit = defineEmits<{
  (e: 'update:visible', v: boolean): void;
  (e: 'add', saleId: number): void;
}>();

const dialogVisible = computed({
  get: () => props.visible,
  set: v => emit('update:visible', v)
});

const sales = ref<{ sale_id: number; date: string; customer: string, total: number }[]>([]);
const selectedSale = ref<number | null>(null);
const loading = ref(false);
const error = ref(false);
const searchTerm   = ref('');   
const LIMIT        = 25; 
let filterTimeout: ReturnType<typeof setTimeout>|null = null;

const options = computed(() =>
  sales.value.map(s => ({
    label: `${s.customer} (${s.date}) - $${s.total}`,
    value: s.sale_id
  }))
);

function onFilter(event: { filter: string }) {
  searchTerm.value = event.filter;

  // Cancelar llamada previa
  if (filterTimeout) {
    clearTimeout(filterTimeout);
  }
  // Programar nueva llamada dentro de 300 ms
  filterTimeout = setTimeout(() => {
    fetchSales();
    filterTimeout = null;
  }, 300);
}

async function fetchSales() {
  loading.value = true;
  error.value = false;
  try {
    // Ajusta la ruta según tu configuración (Ziggy/Inertia)
    const { data } = await axios.get(route('payments.simpleList'),{ params: { q: searchTerm.value, limit: LIMIT }});
    sales.value = data;
  } catch (e) {
    console.error('Error fetching sales', e);
    error.value = true;
  } finally {
    loading.value = false;
  }
}

watch(
  () => dialogVisible.value,
  visible => {
    if (visible) {
      selectedSale.value = null;
      fetchSales();
    }
  }
);

function onCancel() {
  dialogVisible.value = false;
}

function onAdd() {
  if (selectedSale.value != null) {
    emit('add', selectedSale.value);
    dialogVisible.value = false;
  }
}
</script>