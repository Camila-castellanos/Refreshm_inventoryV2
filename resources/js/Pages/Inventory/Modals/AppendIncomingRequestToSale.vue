<template>
  <Dialog
    v-model:visible="dialogVisible"
    header="Add request items to existing invoice"
    modal
    dismissableMask
    :closable="false"
    class="min-w-[560px] bg-white rounded-lg shadow-lg p-6 mx-auto"
  >
    <div class="space-y-4">
      <div class="text-sm text-gray-600">
        Select the target invoice to append current request items.
      </div>

      <div class="text-sm text-gray-600 -mt-2">
        Shipping from the incoming request is not appended to the invoice.
      </div>

      <div class="flex flex-col">
        <label for="sale-select" class="mb-1 text-sm font-medium text-gray-700">
          Select invoice
        </label>
        <Dropdown
          id="sale-select"
          v-model="selectedSale"
          :options="options"
          option-label="label"
          option-value="value"
          placeholder="Select an invoice"
          :disabled="loading"
          filter
          :filterValue="searchTerm"
          @filter="onFilter"
          showClear
          class="w-full border border-gray-300 rounded px-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
      </div>

      <div v-if="loading" class="text-gray-500 text-sm">
        Loading invoices...
      </div>
      <div v-if="error" class="text-red-600 text-sm">
        Error loading invoices.
      </div>

      <div class="rounded border border-gray-200 p-3 bg-gray-50">
        <div class="text-sm font-medium text-gray-700 mb-2">Invoice preview</div>

        <div v-if="previewState === 'idle'" data-test="preview-idle" class="text-sm text-gray-500">
          Select an invoice to preview sold items.
        </div>

        <div v-else-if="previewState === 'loading'" data-test="preview-loading" class="text-sm text-gray-500">
          Loading preview...
        </div>

        <div v-else-if="previewState === 'error'" data-test="preview-error" class="space-y-2">
          <div class="text-sm text-red-600">
            {{ previewErrorMessage }}
          </div>
          <Button
            label="Retry preview"
            class="p-button-sm p-button-text"
            :disabled="selectedSale == null"
            @click="onRetryPreview"
          />
        </div>

        <div v-else-if="previewState === 'empty'" data-test="preview-empty" class="text-sm text-gray-500">
          No sold items to preview
        </div>

        <div v-else-if="previewState === 'loaded'" data-test="preview-loaded" class="space-y-2">
          <div class="text-xs text-gray-500">
            {{ previewData?.customer || 'Unknown customer' }} · {{ previewData?.date || 'N/A' }}
          </div>
          <ul class="divide-y divide-gray-200 rounded bg-white border border-gray-200">
            <li
              v-for="item in previewItems"
              :key="item.item_id"
              data-test="preview-item"
              class="px-3 py-2"
            >
              <div class="text-sm text-gray-800">{{ item.label }}</div>
              <div class="text-xs text-gray-500">{{ item.identifier }} · {{ item.type }}</div>
            </li>
          </ul>
          <div v-if="(previewData?.total_items || 0) > 4" class="text-xs text-gray-500">
            View full invoice for full details
          </div>
        </div>
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
          label="Add to invoice"
          class="text-white bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded disabled:opacity-50 disabled:cursor-not-allowed"
          :disabled="!selectedSale || loading || submitting"
          @click="onSubmit"
        />
      </div>
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import axios from 'axios';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import Button from 'primevue/button';
import { format, parseISO } from 'date-fns';

const props = defineProps<{ visible: boolean; submitting?: boolean }>();
const emit = defineEmits<{
  (e: 'update:visible', value: boolean): void;
  (e: 'submit', payload: { sale_id: number; idempotency_key: string }): void;
}>();

const dialogVisible = computed({
  get: () => props.visible,
  set: (v) => emit('update:visible', v),
});

const sales = ref<{ sale_id: number; date: string; customer: string; total: number }[]>([]);
const selectedSale = ref<number | null>(null);
const loading = ref(false);
const error = ref(false);
const searchTerm = ref('');
type PreviewState = 'idle' | 'loading' | 'loaded' | 'empty' | 'error';
type InvoicePreviewItem = {
  item_id: number;
  label: string;
  type: string;
  identifier: string;
  selling_price: number;
  currency: string;
};
type InvoicePreview = {
  sale_id: number;
  customer: string;
  date: string;
  paid_status: string;
  total_items: number;
  items_preview: InvoicePreviewItem[];
};

const previewState = ref<PreviewState>('idle');
const previewData = ref<InvoicePreview | null>(null);
const previewErrorMessage = ref('Unable to load invoice preview right now.');
let previewRequestToken = 0;
const LIMIT = 25;
let filterTimeout: ReturnType<typeof setTimeout> | null = null;

const options = computed(() =>
  sales.value.map((sale) => {
    let onlyDate = sale.date;

    try {
      onlyDate = format(parseISO(sale.date), 'yyyy-MM-dd');
    } catch {
      // keep original value if parsing fails
    }

    return {
      label: `${sale.customer} (${onlyDate}) - $${sale.total}`,
      value: sale.sale_id,
    };
  })
);

const previewItems = computed<InvoicePreviewItem[]>(() => {
  if (!previewData.value) {
    return [];
  }

  return previewData.value.items_preview.slice(0, 4);
});

function onFilter(event: { filter: string }) {
  searchTerm.value = event.filter;

  if (filterTimeout) {
    clearTimeout(filterTimeout);
  }

  filterTimeout = setTimeout(() => {
    fetchSales();
    filterTimeout = null;
  }, 300);
}

async function fetchSales() {
  loading.value = true;
  error.value = false;

  try {
    const { data } = await axios.get(route('payments.simpleList'), {
      params: { q: searchTerm.value, limit: LIMIT },
    });
    sales.value = data;
  } catch {
    error.value = true;
  } finally {
    loading.value = false;
  }
}

watch(
  () => dialogVisible.value,
  (visible) => {
    if (!visible) {
      resetPreviewState();
      return;
    }

    selectedSale.value = null;
    resetPreviewState();
    fetchSales();
  }
);

watch(selectedSale, (saleId) => {
  if (saleId == null) {
    resetPreviewState();
    return;
  }

  void fetchPreview(saleId);
});

async function fetchPreview(saleId: number) {
  const token = ++previewRequestToken;

  previewState.value = 'loading';
  previewData.value = null;
  previewErrorMessage.value = 'Unable to load invoice preview right now.';

  try {
    const { data } = await axios.get(route('items.incomingRequests.invoices.preview', { sale: saleId }));

    if (token !== previewRequestToken || selectedSale.value !== saleId) {
      return;
    }

    const normalizedPreview: InvoicePreview = {
      sale_id: Number(data?.sale_id ?? saleId),
      customer: String(data?.customer ?? 'Unknown customer'),
      date: String(data?.date ?? ''),
      paid_status: String(data?.paid_status ?? 'Unpaid'),
      total_items: Number(data?.total_items ?? 0),
      items_preview: Array.isArray(data?.items_preview)
        ? data.items_preview.slice(0, 4).map((item: any) => ({
            item_id: Number(item?.item_id ?? 0),
            label: String(item?.label ?? 'Deleted item'),
            type: String(item?.type ?? 'unknown'),
            identifier: String(item?.identifier ?? 'N/A'),
            selling_price: Number(item?.selling_price ?? 0),
            currency: String(item?.currency ?? 'CAD'),
          }))
        : [],
    };

    previewData.value = normalizedPreview;
    previewState.value = normalizedPreview.total_items > 0 ? 'loaded' : 'empty';
  } catch (error: any) {
    if (token !== previewRequestToken || selectedSale.value !== saleId) {
      return;
    }

    previewErrorMessage.value = resolvePreviewErrorMessage(error);
    previewState.value = 'error';
  }
}

function resolvePreviewErrorMessage(error: any): string {
  const status = Number(error?.response?.status ?? 0);

  if (status === 401) {
    return 'Session expired. Please sign in again.';
  }

  if (status === 403) {
    return 'You don’t have access to preview this invoice';
  }

  if (status === 404) {
    return 'Invoice not found';
  }

  return 'Unable to load invoice preview right now.';
}

function onRetryPreview() {
  if (selectedSale.value == null) {
    return;
  }

  void fetchPreview(selectedSale.value);
}

function resetPreviewState() {
  previewRequestToken++;
  previewState.value = 'idle';
  previewData.value = null;
  previewErrorMessage.value = 'Unable to load invoice preview right now.';
}

function onCancel() {
  dialogVisible.value = false;
}

function onSubmit() {
  if (selectedSale.value == null) {
    return;
  }

  emit('submit', {
    sale_id: selectedSale.value,
    idempotency_key: createIdempotencyKey(),
  });
}

function createIdempotencyKey(): string {
  if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
    return crypto.randomUUID();
  }

  return `append-${Date.now()}-${Math.random().toString(36).slice(2, 12)}`;
}
</script>
