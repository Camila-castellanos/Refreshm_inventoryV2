<template>
  <PortalLayout>
    <div class="p-4 md:p-8 max-w-7xl mx-auto">
      <!-- Top Bar: Quick Stats & Profile -->
      <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 mb-10">
        <div>
          <h1 class="text-3xl font-bold text-surface-900 dark:text-surface-0">
            Welcome, {{ $page.props.customer_auth.user.first_name }}!
          </h1>
          <p class="text-surface-500 mt-1">Manage your device requests and account preferences.</p>
        </div>

        <div class="flex flex-wrap gap-4 w-full lg:w-auto">
          <!-- Total Items Card -->
          <div class="bg-white dark:bg-surface-800 border border-surface-100 dark:border-surface-700 rounded-2xl px-6 py-4 flex items-center gap-5 shadow-sm flex-1 lg:flex-none">
            <i class="pi pi-box text-blue-400 text-2xl"></i>
            <div>
              <div class="text-[9px] uppercase text-surface-400 font-bold tracking-[0.1em] mb-0.5">Devices Purchased</div>
              <div class="text-xl font-semibold text-surface-900 dark:text-surface-0 leading-tight">
                {{ total_items_count }}
              </div>
            </div>
          </div>

          <!-- Credit Card -->
          <div class="bg-white dark:bg-surface-800 border border-surface-100 dark:border-surface-700 rounded-2xl px-6 py-4 flex items-center gap-5 shadow-sm flex-1 lg:flex-none">
            <i class="pi pi-wallet text-emerald-400 text-2xl"></i>
            <div>
              <div class="text-[9px] uppercase text-surface-400 font-bold tracking-[0.1em] mb-0.5">Available Credit</div>
              <div class="text-xl font-semibold text-surface-900 dark:text-surface-0 leading-tight">
                {{ formatCurrency(credit_balance) }}
              </div>
            </div>
          </div>

          <!-- Profile Button -->
          <button
            @click="showProfileModal = true"
            class="bg-white dark:bg-surface-800 border border-surface-100 dark:border-surface-700 rounded-2xl px-6 py-4 flex items-center gap-5 shadow-sm hover:bg-surface-50 dark:hover:bg-surface-700 transition-all text-left flex-1 lg:flex-none"
          >
            <i class="pi pi-user text-primary-400 text-2xl"></i>
            <div>
              <div class="text-[9px] uppercase text-surface-400 font-bold tracking-[0.1em] mb-0.5">Account</div>
              <div class="text-sm font-semibold text-surface-900 dark:text-surface-0 leading-tight">Preferences</div>
            </div>
          </button>
        </div>
      </div>

      <!-- Main Two-Column Layout -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
        <!-- LEFT COLUMN: Current Requests -->
        <section>
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold flex items-center gap-3">
              <i class="pi pi-clock text-yellow-500"></i>
              Current Requests
              <Tag :value="pendingRequests.length" severity="warn" rounded class="ml-1" />
            </h2>
          </div>

          <div class="space-y-4">
            <div
              v-if="pendingRequests.length === 0"
              class="bg-surface-50 dark:bg-surface-800/50 border-2 border-dashed border-surface-200 dark:border-surface-700 rounded-2xl p-12 text-center text-surface-500"
            >
              <i class="pi pi-inbox text-4xl mb-3 block opacity-20"></i>
              No current requests at the moment.
            </div>
            
            <div
              v-for="request in pendingRequests"
              :key="request.id"
              @click="openRequestDetail(request)"
              class="group relative bg-white dark:bg-surface-800 p-5 rounded-2xl border border-surface-200 dark:border-surface-700 hover:border-primary-400 dark:hover:border-primary-500 shadow-sm hover:shadow-xl transition-all cursor-pointer overflow-hidden"
            >
              <!-- Accent border -->
              <div class="absolute left-0 top-0 bottom-0 w-1 bg-yellow-400"></div>
              
              <div class="flex justify-between items-start">
                <div class="min-w-0">
                  <div class="flex items-center gap-2 mb-1">
                    <span class="font-bold text-lg text-surface-900 dark:text-surface-0">#{{ request.id }}</span>
                    <span class="text-surface-400">·</span>
                    <span class="text-surface-900 dark:text-surface-0 font-medium truncate">{{ request.name || 'Device Request' }}</span>
                  </div>
                  <div class="text-sm text-surface-500 flex items-center gap-3">
                    <span>{{ formatDate(request.created_at) }}</span>
                    <span class="w-1 h-1 bg-surface-300 rounded-full"></span>
                    <span>{{ request.items?.length || 0 }} items</span>
                  </div>
                </div>
                <i class="pi pi-chevron-right text-surface-300 group-hover:text-primary-500 transition-colors"></i>
              </div>
            </div>
          </div>
        </section>

        <!-- RIGHT COLUMN: Past Orders -->
        <section>
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold flex items-center gap-3">
              <i class="pi pi-history text-blue-500"></i>
              Past Orders
            </h2>
            <Link 
              v-if="total_requests > all_requests.length"
              :href="route('public-store.portal.requests')" 
              class="text-sm text-primary-500 font-bold hover:underline"
            >
              View Full History
            </Link>
          </div>

          <div class="space-y-4">
            <div v-if="pastRequests.length === 0" class="text-surface-400 text-center py-12 italic border border-surface-100 dark:border-surface-800 rounded-2xl">
              Your request history is empty.
            </div>
            
            <div
              v-for="request in pastRequests"
              :key="request.id"
              @click="openRequestDetail(request)"
              class="flex justify-between items-center p-4 bg-surface-50 dark:bg-surface-800/40 rounded-xl border border-transparent hover:border-surface-300 dark:hover:border-surface-600 hover:bg-white dark:hover:bg-surface-800 transition-all cursor-pointer group"
            >
              <div class="min-w-0">
                <div class="font-bold text-surface-900 dark:text-surface-0 flex items-center gap-2">
                  <span class="text-surface-500">#{{ request.id }}</span>
                  <span class="truncate text-sm">{{ request.name || 'Device Request' }}</span>
                </div>
                <div class="text-xs text-surface-500 mt-0.5">
                  {{ formatDate(request.created_at) }} · {{ request.items?.length || 0 }} items
                </div>
              </div>
              <Tag 
                :value="request.processed ? 'Processed' : (request.deleted_at ? 'Cancelled' : 'Pending')" 
                :severity="request.processed ? 'success' : (request.deleted_at ? 'danger' : 'warn')" 
                class="text-[10px]" 
              />
            </div>
          </div>
        </section>
      </div>
    </div>

    <!-- My Profile Modal -->
    <Dialog v-model:visible="showProfileModal" header="My Profile" :modal="true" class="w-full mx-4 md:w-1/2">
      <form @submit.prevent="saveProfile" class="flex flex-col gap-4">
        <div>
          <label for="defaultStore" class="block text-surface-900 dark:text-surface-0 text-sm font-medium mb-1">Default Store</label>
          <InputText id="defaultStore" v-model="profileForm.default_store" type="text" class="w-full" placeholder="Enter your default store" />
        </div>
        <div>
          <label for="defaultShipping" class="block text-surface-900 dark:text-surface-0 text-sm font-medium mb-1">Default Shipping</label>
          <Dropdown
            id="defaultShipping"
            v-model="profileForm.default_shipping"
            :options="shippingOptions"
            optionLabel="label"
            optionValue="value"
            class="w-full"
            placeholder="Select shipping"
          />
        </div>
        <div>
          <label for="notes" class="block text-surface-900 dark:text-surface-0 text-sm font-medium mb-1">Notes</label>
          <Textarea id="notes" v-model="profileForm.notes" rows="3" class="w-full" placeholder="Enter any notes" />
        </div>
        <div>
          <label for="currency" class="block text-surface-900 dark:text-surface-0 text-sm font-medium mb-1">Preferred Currency</label>
          <Dropdown
            id="currency"
            v-model="profileForm.currency"
            :options="[{label: 'CAD - Canadian Dollar', value: 'CAD'}, {label: 'USD - US Dollar', value: 'USD'}]"
            optionLabel="label"
            optionValue="value"
            class="w-full"
            placeholder="Select currency"
          />
        </div>
        <div class="flex justify-end gap-2 mt-4">
          <Button type="button" severity="secondary" @click="showProfileModal = false">Cancel</Button>
          <Button type="submit">Save</Button>
        </div>
      </form>
    </Dialog>

    <!-- My Requests Modal -->
    <Dialog v-model:visible="showRequestsModal" header="My Requests" :modal="true" class="w-full mx-4 md:w-2/3 lg:w-1/2">
      <div v-if="!all_requests || all_requests.length === 0" class="text-center text-surface-500 py-8">
        You have no requests yet
      </div>
      <div v-else class="space-y-3 max-h-96 overflow-y-auto pr-1">
        <div
          v-for="request in all_requests"
          :key="request.id"
          @click="openRequestDetail(request)"
          class="flex justify-between items-center p-4 bg-surface-50 hover:bg-black/[0.03] dark:bg-surface-700 dark:hover:bg-black/[0.10] rounded-lg border border-transparent hover:border-primary-300 dark:hover:border-primary-600 hover:shadow-md cursor-pointer transition-all duration-200"
        >
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-1">
              <span class="font-semibold text-surface-900 dark:text-surface-0">#{{ request.id }}</span>
              <span class="text-surface-600 dark:text-surface-400">·</span>
              <span class="text-surface-900 dark:text-surface-0 truncate">{{ request.name || 'Unnamed' }}</span>
            </div>
            <div class="text-sm text-surface-500">{{ formatDate(request.created_at) }} · {{ request.items?.length || 0 }} items</div>
          </div>
          <Tag
            :value="request.processed ? 'Processed' : (request.deleted_at ? 'Cancelled' : 'Pending')"
            :severity="request.processed ? 'success' : (request.deleted_at ? 'danger' : 'warn')"
            class="font-medium ml-3 flex-shrink-0"
          />
        </div>
      </div>
      <div v-if="total_requests > 50" class="mt-4 text-center text-surface-500 text-sm">
        Showing 50 of {{ total_requests }} requests
      </div>
      <div class="flex justify-end mt-4">
        <Button type="button" severity="secondary" @click="showRequestsModal = false">Close</Button>
      </div>
    </Dialog>

    <!-- Request Detail Modal -->
    <Dialog v-model:visible="showRequestDetailModal" :header="'Request #' + (selectedRequest?.id || '')" :modal="true" class="w-full mx-4 md:w-2/3 lg:w-3/4">
      <div v-if="selectedRequest">
        <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mb-6">
          <div class="bg-surface-50 dark:bg-surface-700 rounded-lg p-3 border border-surface-200 dark:border-surface-600">
            <div class="text-xs text-surface-500 mb-1">Name</div>
            <div class="font-semibold text-surface-900 dark:text-surface-0">{{ selectedRequest.name || 'Unnamed' }}</div>
          </div>
          <div class="bg-surface-50 dark:bg-surface-700 rounded-lg p-3 border border-surface-200 dark:border-surface-600">
            <div class="text-xs text-surface-500 mb-1">Email</div>
            <div class="font-semibold text-surface-900 dark:text-surface-0 truncate">{{ selectedRequest.email || 'N/A' }}</div>
          </div>
          <div class="bg-surface-50 dark:bg-surface-700 rounded-lg p-3 border border-surface-200 dark:border-surface-600">
            <div class="text-xs text-surface-500 mb-1">Store</div>
            <div class="font-semibold text-surface-900 dark:text-surface-0">{{ selectedRequest.store || 'N/A' }}</div>
          </div>
          <div class="bg-surface-50 dark:bg-surface-700 rounded-lg p-3 border border-surface-200 dark:border-surface-600">
            <div class="text-xs text-surface-500 mb-1">Status</div>
            <div class="mt-1">
              <Tag
                :value="selectedRequest.processed ? 'Processed' : (selectedRequest.deleted_at ? 'Cancelled' : 'Pending')"
                :severity="selectedRequest.processed ? 'success' : (selectedRequest.deleted_at ? 'danger' : 'warn')"
                class="font-medium"
              />
            </div>
          </div>
          <div v-if="selectedRequest.notes" class="bg-surface-50 dark:bg-surface-700 rounded-lg p-3 border border-surface-200 dark:border-surface-600 col-span-2">
            <div class="text-xs text-surface-500 mb-1">Notes</div>
            <div class="font-semibold text-surface-900 dark:text-surface-0">{{ selectedRequest.notes }}</div>
          </div>
        </div>

        <div class="font-semibold text-surface-900 dark:text-surface-0 mb-3">Items ({{ selectedRequest.items?.length || 0 }})</div>
        <div v-if="!selectedRequest.items || selectedRequest.items.length === 0" class="text-center text-surface-500 py-4">
          No items in this request
        </div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-surface-200 dark:border-surface-600">
                <th class="text-left p-2 text-surface-500 font-medium">Model</th>
                <th class="text-left p-2 text-surface-500 font-medium">Manufacturer</th>
                <th class="text-left p-2 text-surface-500 font-medium">Colour</th>
                <th class="text-left p-2 text-surface-500 font-medium">Battery</th>
                <th class="text-left p-2 text-surface-500 font-medium">Grade</th>
                <th class="text-left p-2 text-surface-500 font-medium">Issues</th>
                <th class="text-right p-2 text-surface-500 font-medium">Price</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="item in selectedRequest.items"
                :key="item.id"
                class="border-b border-surface-100 dark:border-surface-700 hover:bg-surface-50 dark:hover:bg-surface-700"
              >
                <td class="p-2 text-surface-900 dark:text-surface-0">{{ item.model || 'N/A' }}</td>
                <td class="p-2 text-surface-600 dark:text-surface-400">{{ item.manufacturer || 'N/A' }}</td>
                <td class="p-2 text-surface-600 dark:text-surface-400">{{ item.colour || 'N/A' }}</td>
                <td class="p-2 text-surface-600 dark:text-surface-400">{{ item.battery ? item.battery + '%' : 'N/A' }}</td>
                <td class="p-2 text-surface-600 dark:text-surface-400">{{ item.grade || 'N/A' }}</td>
                <td class="p-2 text-surface-600 dark:text-surface-400 text-sm max-w-xs">{{ item.issues || 'None' }}</td>
                <td class="p-2 text-surface-900 dark:text-surface-0 text-right font-medium">{{ formatCurrency(item.selling_price) }}</td>
              </tr>
            </tbody>
            <tfoot>
              <tr class="bg-surface-50 dark:bg-surface-700">
                <td colspan="6" class="p-3 text-right font-semibold text-surface-900 dark:text-surface-0">Total:</td>
                <td class="p-3 text-right font-bold text-lg text-surface-900 dark:text-surface-0">{{ formatCurrency(requestTotal) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
      <div class="flex justify-end mt-6">
        <Button type="button" severity="secondary" @click="showRequestDetailModal = false">Close</Button>
      </div>
    </Dialog>
  </PortalLayout>
</template>

<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import { usePage } from "@inertiajs/vue3";
import { reactive, onMounted, ref, computed } from "vue";
import { useCurrency } from "@/Composables/useCurrency";
import PortalLayout from "@/Layouts/PortalLayout.vue";
import Divider from "primevue/divider";
import StatCard from "@/Components/StatCard.vue";
import Dialog from "primevue/dialog";
import InputText from "primevue/inputtext";
import Textarea from "primevue/textarea";
import Dropdown from "primevue/dropdown";
import Button from "primevue/button";
import Tag from "primevue/tag";
import axios from "axios";
import { useToast } from "primevue/usetoast";

const props = defineProps<{
  total_requests: number;
  pending_requests: number;
  total_items_count: number;
  credit_balance: number;
  credit_from_returns: number;
  recent_requests: any[];
  all_requests: any[];
}>();

const pendingRequests = computed(() => props.all_requests.filter(r => !r.processed && !r.deleted_at));
const pastRequests = computed(() => props.all_requests.filter(r => r.processed || r.deleted_at));
const requestTotal = computed(() => {
  if (!selectedRequest.value?.items) return 0;
  return selectedRequest.value.items.reduce((sum, item) => sum + (Number(item.selling_price) || 0), 0);
});

const page = usePage();
const toast = useToast();
const { formatCurrency } = useCurrency();

const shippingOptions = [
  { label: 'Standard (Free)', value: 0 },
  { label: 'Express (+$25)', value: 25 }
];

const showProfileModal = ref(false);
const showRequestsModal = ref(false);
const showRequestDetailModal = ref(false);
const selectedRequest = ref<any>(null);

const profileForm = reactive({
  default_store: '',
  default_shipping: null as number | null,
  notes: '',
  currency: 'CAD',
});

onMounted(() => {
  // Load current values from page.props.customer_auth.user
  const user = page.props.customer_auth?.user;
  if (user) {
    profileForm.default_store = user.default_store || '';
    profileForm.default_shipping = (user.default_shipping !== null && user.default_shipping !== undefined) 
      ? Number(user.default_shipping) 
      : null;
    profileForm.notes = user.notes || '';
    profileForm.currency = user.currency || 'CAD';
  }
});

const saveProfile = async () => {
  try {
    await axios.put(route('public-store.portal.profile'), {
      default_store: profileForm.default_store,
      default_shipping: profileForm.default_shipping?.toString(),
      notes: profileForm.notes,
      currency: profileForm.currency,
    });
    // Update local Inertia state
    if (page.props.customer_auth?.user) {
      page.props.customer_auth.user.currency = profileForm.currency;
    }
    showProfileModal.value = false;
    toast.add({ severity: 'success', summary: 'Success', detail: 'Profile saved successfully.', life: 3000 });
  } catch (error) {
    console.error('Failed to save profile:', error);
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to save profile.', life: 5000 });
  }
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-CA', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};

const openRequestDetail = (request: any) => {
  selectedRequest.value = request;
  showRequestDetailModal.value = true;
};
</script>