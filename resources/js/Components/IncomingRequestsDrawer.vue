<template>
  <div>
    <Toast />
    <Button :class="['incoming-requests-button', { 'open': visible } ]" icon="pi pi-inbox" severity="secondary" @click="open">
      <span class="sr-only">Incoming Requests</span>
      <span v-if="count > 0" class="badge">{{ count }}</span>
    </Button>

    <Sidebar v-model:visible="visible" position="right" :baseZIndex="10000" dismissable :style="{ width: '380px' }">
      <div class="p-4">
        <h3 class="text-lg font-bold">Incoming Requests</h3>
        <p class="mt-2 text-sm text-gray-600">Here you can manage incoming requests.</p>

        <div class="mt-4">
          <div v-if="loading" class="text-sm text-gray-500">Loading...</div>
          <div v-else-if="error" class="text-sm text-red-500">Error loading requests</div>
          <ul v-else class="divide-y mt-2">
            <li v-if="requests.length === 0" class="py-2 text-sm text-gray-600">No requests.</li>
            <li v-for="r in requests" :key="r.id" class="py-2 request-row" @click="openRequest(r)">
              <div class="flex justify-between items-center">
                <div>
                  <div class="font-medium">{{ r.name || 'Anon' }}</div>
                  <div class="text-xs text-gray-500">{{ r.email }} - {{ r.store }}</div>
                </div>
                <div class="text-xs text-gray-400">{{ new Date(r.created_at).toLocaleString() }}</div>
              </div>
              <div class="text-sm text-gray-600 mt-1">Items: {{ r.items.length }}</div>
              
            </li>
          </ul>
        </div>
      </div>
    </Sidebar>

    <Dialog v-model:visible="dialogVisible" :modal="true" :style="{ width: '520px' }" header="Request details"
      :draggable="false" :resizable="false">
      <div class="p-4">
        <div class="mb-2">
          <div class="font-semibold text-lg">{{ activeRequest?.name || 'Solicitud' }}</div>
          <div class="text-sm text-gray-500">{{ activeRequest?.email }} — {{ activeRequest?.store }}</div>
          <div class="text-xs text-gray-400 mt-1">{{ activeRequest ? new Date(activeRequest.created_at).toLocaleString() : '' }}</div>
          <div v-if="activeRequest?.notes" class="mt-2">
            <div class="text-xs font-medium text-gray-700 mb-1">Notes</div>
            <div class="text-sm text-gray-600">{{ activeRequest.notes }}</div>
          </div>

        </div>

        <div class="flex justify-end mb-3 gap-2">
          <Button label="Create invoice" icon="pi pi-receipt" class="p-button-sm p-button-outlined create-invoice" @click="createInvoice(activeRequest)" />
          <Button label="Delete request" icon="pi pi-trash" class="p-button-sm p-button-danger p-button-outlined" @click="deleteRequest(activeRequest)" />
        </div>

        <div>
          <h4 class="font-medium mb-2">Items ({{ activeRequest?.items?.length || 0 }})</h4>
          <ul class="divide-y">
            <li v-for="it in activeRequest?.items || []" :key="it.id" class="py-2 flex justify-between items-start">
              <div>
                <div class="font-medium">{{ it.model || it.type || 'Item' }}</div>
                <div class="text-xs text-gray-500">{{ it.manufacturer || '' }} — {{ it.imei || '' }}</div>
                <div v-if="it.issues" class="text-xs text-red-600 mt-1">Issues: {{ it.issues }}</div>
              </div>
              <div class="flex flex-col items-end gap-2">
                <div class="text-sm text-gray-600">Price: {{ it.selling_price ?? it.cost ?? '-' }}</div>
                <Button icon="pi pi-trash" class="p-button-text p-button-sm p-button-danger" @click.stop="deleteItem(it)" />
              </div>
            </li>
            <li v-if="(activeRequest?.items || []).length === 0" class="py-2 text-sm text-gray-600">No items.</li>
          </ul>
        </div>
        <div class="mt-3 pt-2 border-t">
          <div class="flex justify-between items-center">
            <div class="text-sm text-gray-600">Subtotal (items)</div>
            <div class="text-sm font-medium text-gray-800">${{ ((activeRequest?.items || []).reduce((acc, it) => acc + (Number(it.selling_price) || Number(it.cost) || 0), 0)).toFixed(2) }}</div>
          </div>
          <div class="flex justify-between items-center mt-1">
            <div class="text-sm text-gray-600">{{ activeRequest?.shipping?.label || 'Shipping' }}</div>
            <div class="text-sm font-medium text-gray-800">${{ (Number(activeRequest?.shipping?.value) || 0).toFixed(2) }}</div>
          </div>
          <div class="flex justify-between items-center mt-2 border-t pt-2">
            <div class="text-base font-semibold">Total</div>
            <div class="text-base font-bold text-black">${{ (((activeRequest?.items || []).reduce((acc, it) => acc + (Number(it.selling_price) || Number(it.cost) || 0), 0)) + (Number(activeRequest?.shipping?.value) || 0)).toFixed(2) }}</div>
          </div>
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import Sidebar from 'primevue/sidebar';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import { useToast } from 'primevue/usetoast';
import { useDialog } from 'primevue/usedialog';
import ItemsSell from '../Pages/Inventory/Modals/ItemsSell.vue';
import axios from 'axios';
import { ItemType} from '@/Enums/itemType';


const visible = ref(false);
const requests = ref<any[]>([]);
const loading = ref(false);
const error = ref(false);
const count = ref(0);
const dialogVisible = ref(false);
const activeRequest = ref<any | null>(null);
const toast = useToast();
const dialog = useDialog();

// log for testing and see ActiveRequest structure
watch(activeRequest, (newVal) => {
  console.log('Active request changed:', newVal);
});

async function createInvoice(req: any) {
  if (!req) return;
  
  try {
    // Fetch current item status to check if they're already sold
    const itemIds = (req.items || []).map((it: any) => it.original_item_id ?? it.id);
    const itemsResponse = await axios.post(route('items.getSpecificItems'), { ids: itemIds });
    const currentItems = itemsResponse.data || [];
    
    // Create a map of item IDs to their current status
    const currentItemsMap = new Map(currentItems.map((it: any) => [it.id, it]));
    
    // Separate sold and available items
    const availableItems: any[] = [];
    const soldItems: any[] = [];
    
    (req.items || []).forEach((it: any) => {
      const itemId = it.original_item_id ?? it.id;
      const currentItem = currentItemsMap.get(itemId);
      
      // Check if item is sold (has a sold date/value)
      if (currentItem && currentItem.sold) {
        soldItems.push(it);
      } else {
        availableItems.push(it);
      }
    });
    
    // Notify user about sold items that were removed
    if (soldItems.length > 0) {
      const soldModels = soldItems.map((it: any) => it.model || it.type || 'Item').join(', ');
      toast.add({ 
        severity: 'warn', 
        summary: 'Items Removed', 
        detail: `The following items have already been sold and were removed: ${soldModels}`, 
        life: 5000 
      });
    }
    
    // If no available items, don't open modal
    if (availableItems.length === 0) {
      toast.add({ 
        severity: 'info', 
        summary: 'No Items Available', 
        detail: 'All items in the current request have already been sold.', 
        life: 4000 
      });
      return;
    }
    
    // Map available items: use original_item_id as id when available
    const mappedItems = availableItems.map((it: any) => ({
      ...it,
      id: it.original_item_id ?? it.id,
    }));

    // If the request includes shipping and it's NOT "Standard (Free)", add a synthetic item for the shipping fee
    if (req.shipping && req.shipping.label !== 'Standard (Free)') {
      try {
        mappedItems.push({
          id: null,
          original_item_id: null,
          model: req.shipping.label || 'Shipping',
          type: ItemType.SHIPPING_FEE,
          selling_price: Number(req.shipping.value) || 0,
          isNew: true,
        });
      } catch (err) {
        console.error('Error mapping shipping to item', err);
      }
    }

    // Open ItemsSell modal with the mapped request items
    console.log('Opening ItemsSell dialog with items:', mappedItems);
    dialog.open(ItemsSell, {
      data: {
        // ItemsSell expects items as an array/ref similar to Index; pass the mapped items
        items: mappedItems,
        customers: [],
      },
      props: {
        modal: true,
      },
      onClose: async (result: any) => {
        if (result?.data?.sold) {
          // If the sale was successful, delete the originating incoming request first
          try {
            await axios.delete(route('items.incomingRequests.delete', req.id));
            toast.add({ severity: 'success', summary: 'Request removed', detail: 'Incoming request successfully processed and deleted', life: 2500 });
          } catch (e) {
            console.error('Failed to delete incoming request after sale', e);
            toast.add({ severity: 'warn', summary: 'Warning', detail: 'Sale created but failed to delete request', life: 4000 });
          }

          // refresh requests list and close dialog
          await fetchRequests(false);
          dialogVisible.value = false;
          toast.add({ severity: 'success', summary: 'Sold', detail: 'Items sold successfully', life: 3000 });
        }
      },
    });
  } catch (e) {
    console.error('Error validating items:', e);
    toast.add({ 
      severity: 'error', 
      summary: 'Error', 
      detail: 'Failed to validate items. Please try again.', 
      life: 4000 
    });
  }
}

async function deleteItem(it: any) {
  try {
  await axios.delete(route('items.incomingRequests.deleteItem', it.id));
    // remove item from activeRequest
    if (activeRequest.value && activeRequest.value.items) {
      activeRequest.value.items = activeRequest.value.items.filter((x: any) => x.id !== it.id);
    }
    // also refresh the drawer list count
  const res = await axios.get(route('items.incomingRequests'), { params: { processed: false } });
    requests.value = res.data || [];
    count.value = requests.value.length;
  toast.add({ severity: 'success', summary: 'Deleted', detail: 'Item deleted from the request successfully', life: 3000 });
  } catch (e) {
  console.error('deleteItem error', e);
  toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to delete item from request', life: 4000 });
  }
}

async function deleteRequest(req: any) {
  if (!req || !req.id) return;
  try {
  await axios.delete(route('items.incomingRequests.delete', req.id));
    // remove from requests list
    requests.value = requests.value.filter((r: any) => r.id !== req.id);
    count.value = requests.value.length;
    dialogVisible.value = false;
    toast.add({ severity: 'success', summary: 'Deleted', detail: 'Request deleted successfully', life: 3000 });
  } catch (e) {
    console.error('deleteRequest error', e);
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to delete request', life: 4000 });
  }
}

async function fetchRequests(processed = false) {
  loading.value = true;
  error.value = false;
  try {
    const res = await axios.get(route('items.incomingRequests'), { params: { processed } });
    requests.value = res.data || [];
    count.value = requests.value.length;
  } catch (e) {
    error.value = true;
    requests.value = [];
    count.value = 0;
  } finally {
    loading.value = false;
  }
}

function open() {
  visible.value = true;
  // refresh unprocessed requests each time we open
  fetchRequests(false);
}

function openRequest(r: any) {
  activeRequest.value = r;
  dialogVisible.value = true;
}

// initial badge load without opening the drawer
(async () => {
  try {
    const res = await axios.get(route('items.incomingRequests'), { params: { processed: false } });
    count.value = (res.data || []).length;
  } catch (e) {
    // ignore
  }
})();
</script>

<style scoped>
.incoming-requests-button {
  position: fixed;
  right: -10px;
  top: 140px;
  z-index: 70;
  display: flex;
  width: fit-content;
  align-items: center;
  gap: 0.5rem;
  padding: 0.6rem 1rem;
  height: auto;
  border-radius: 0;
  border-top-left-radius: 7px;
  border-bottom-left-radius: 7px;
  background: var(--prime-button-secondary-bg, #ffffff);
  color: var(--prime-button-secondary-color, #0f172a);
  border: 1px solid rgba(15,23,42,0.06);
  box-shadow: 0 6px 18px rgba(13, 38, 59, 0.12);
  transition: transform 0.15s ease, background-color 0.15s ease;
}
.incoming-requests-button.open {
  transform: translateY(-4px);
}

.incoming-requests-button .pi {
  font-size: 1.25rem;
  line-height: 1;
}

.incoming-requests-button span.sr-only {
  position: absolute;
  left: -9999px;
  width: 1px;
  height: 1px;
  overflow: hidden;
}

/* Show text label on larger screens */
@media (min-width: 640px) {
  .incoming-requests-button::after {
    content: 'Incoming Requests';
    color: var(--prime-text-color, #0f172a);
    font-size: 0.95rem;
    margin-left: 0.35rem;
  }
}

.badge {
  background: #f5f5f5;
  color: #0f172a;
  border-radius: 3px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
  margin-left: 0.35rem;
  padding: 5px 10px;
  line-height: 1;
}

.request-row {
  cursor: pointer;
  padding: 0.75rem 0.5rem; /* increase vertical and horizontal padding */
  border-radius: 6px;
}
.request-row:hover {
  background: rgba(15,23,42,0.03);
}

</style>
