<template>
  <PortalLayout>
    <div class="p-6">
      <!-- Stats Grid -->
      <div class="mt-4 -mb-2 font-bold">
        <h2>Overview</h2>
      </div>
      <Divider />

      <div class="grid grid-cols-2 grow md:grid-cols-3 lg:grid-cols-3 gap-4 mt-6">
        <StatCard
          label="Total Requests"
          :value="total_requests"
          icon="pi-inbox"
          color="blue"
        />
        <StatCard
          label="Pending"
          :value="pending_requests"
          icon="pi-clock"
          color="yellow"
        />
        <StatCard
          label="Credit Balance"
          :value="credit_balance"
          icon="pi-wallet"
          color="green"
          currency="$"
        />
      </div>

      <!-- Navigation Cards -->
      <div class="mt-12 -mb-2 font-bold">
        <h2>Quick Access</h2>
      </div>
      <Divider />

      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mt-6">
        <Link href="/publicstore/account/requests" class="block">
          <div class="bg-white dark:bg-surface-800 rounded-lg shadow p-6 hover:shadow-lg transition-shadow cursor-pointer">
            <div class="flex items-center justify-between mb-4">
              <i class="pi pi-list text-3xl text-blue-500"></i>
              <span class="text-surface-500 text-sm">{{ total_requests }} requests</span>
            </div>
            <div class="text-lg font-medium text-surface-900 dark:text-surface-0">My Requests</div>
            <div class="text-surface-600 dark:text-surface-400 text-sm mt-1">View request status</div>
          </div>
        </Link>
        <Link href="/publicstore/account/returns" class="block">
          <div class="bg-white dark:bg-surface-800 rounded-lg shadow p-6 hover:shadow-lg transition-shadow cursor-pointer">
            <div class="flex items-center justify-between mb-4">
              <i class="pi pi-replay text-3xl text-orange-500"></i>
              <span class="text-surface-500 text-sm">Returns</span>
            </div>
            <div class="text-lg font-medium text-surface-900 dark:text-surface-0">Returns / Exchanges</div>
            <div class="text-surface-600 dark:text-surface-400 text-sm mt-1">Track returns and exchanges</div>
          </div>
        </Link>
        <Link href="/publicstore/account/credit" class="block">
          <div class="bg-white dark:bg-surface-800 rounded-lg shadow p-6 hover:shadow-lg transition-shadow cursor-pointer">
            <div class="flex items-center justify-between mb-4">
              <i class="pi pi-wallet text-3xl text-green-500"></i>
              <span class="text-surface-500 text-sm">{{ formatCurrency(credit_balance) }}</span>
            </div>
            <div class="text-lg font-medium text-surface-900 dark:text-surface-0">My Credit</div>
            <div class="text-surface-600 dark:text-surface-400 text-sm mt-1">Balance and transactions</div>
          </div>
        </Link>
        <div @click="showProfileModal = true" class="block">
          <div class="bg-white dark:bg-surface-800 rounded-lg shadow p-6 hover:shadow-lg transition-shadow cursor-pointer">
            <div class="flex items-center justify-between mb-4">
              <i class="pi pi-user text-3xl text-primary-500"></i>
            </div>
            <div class="text-lg font-medium text-surface-900 dark:text-surface-0">My Profile</div>
            <div class="text-surface-600 dark:text-surface-400 text-sm mt-1">Manage your preferences</div>
          </div>
        </div>
      </div>

      <!-- Recent Requests -->
      <div class="mt-12 -mb-2 font-bold">
        <h2>Recent Requests</h2>
      </div>
      <Divider />

      <div class="bg-white dark:bg-surface-800 rounded-lg shadow mt-6">
        <div class="p-6">
          <div v-if="!recent_requests || recent_requests.length === 0" class="text-center text-surface-500 py-8">
            You have no recent requests
          </div>
          <div v-else class="space-y-4">
            <div
              v-for="request in recent_requests"
              :key="request.id"
              class="flex justify-between items-center p-4 bg-surface-50 dark:bg-surface-700 rounded-lg"
            >
              <div>
                <div class="font-medium text-surface-900 dark:text-surface-0">#{{ request.id }} - {{ request.name || 'Unnamed' }}</div>
                <div class="text-sm text-surface-600 dark:text-surface-400">{{ request.shop_id || 'Store' }}</div>
              </div>
              <div>
                <span
                  v-if="request.processed"
                  class="px-3 py-1 bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded-full text-sm"
                >
                  Processed
                </span>
                <span
                  v-else
                  class="px-3 py-1 bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400 rounded-full text-sm"
                >
                  Pending
                </span>
              </div>
            </div>
          </div>
          <div v-if="recent_requests && recent_requests.length > 0" class="mt-4 text-center">
            <Link href="/publicstore/account/requests" class="text-primary-500 hover:text-primary-600">
              View all requests →
            </Link>
          </div>
        </div>
      </div>

      <!-- Credit Info -->
      <div class="mt-12 -mb-2 font-bold">
        <h2>Credit Summary</h2>
      </div>
      <Divider />

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
        <div class="bg-white dark:bg-surface-800 rounded-lg shadow p-6">
          <div class="text-surface-600 dark:text-surface-400 text-sm mb-1">Credit from Returns</div>
          <div class="text-2xl font-bold text-green-600">{{ formatCurrency(credit_from_returns || 0) }}</div>
        </div>
        <div class="bg-white dark:bg-surface-800 rounded-lg shadow p-6">
          <div class="text-surface-600 dark:text-surface-400 text-sm mb-1">Current Balance</div>
          <div class="text-2xl font-bold text-surface-900 dark:text-surface-0">{{ formatCurrency(credit_balance) }}</div>
        </div>
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
        <div class="flex justify-end gap-2 mt-4">
          <Button type="button" severity="secondary" @click="showProfileModal = false">Cancel</Button>
          <Button type="submit">Save</Button>
        </div>
      </form>
    </Dialog>
  </PortalLayout>
</template>

<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import { usePage } from "@inertiajs/vue3";
import { reactive, onMounted, ref } from "vue";
import PortalLayout from "@/Layouts/PortalLayout.vue";
import Divider from "primevue/divider";
import StatCard from "@/Components/StatCard.vue";
import Dialog from "primevue/dialog";
import InputText from "primevue/inputtext";
import Textarea from "primevue/textarea";
import Dropdown from "primevue/dropdown";
import Button from "primevue/button";
import axios from "axios";
import { useToast } from "primevue/usetoast";

defineProps<{
  total_requests: number;
  pending_requests: number;
  credit_balance: number;
  credit_from_returns: number;
  recent_requests: any[];
}>();

const page = usePage();
const toast = useToast();

const shippingOptions = [
  { label: 'Standard (Free)', value: 0 },
  { label: 'Express (+$25)', value: 25 }
];

const showProfileModal = ref(false);

const profileForm = reactive({
  default_store: '',
  default_shipping: null as number | null,
  notes: '',
});

onMounted(() => {
  // Load current values from page.props.customer_auth.user
  const user = page.props.customer_auth?.user;
  if (user) {
    profileForm.default_store = user.default_store || '';
    profileForm.default_shipping = user.default_shipping ?? null;
    profileForm.notes = user.notes || '';
  }
});

const saveProfile = async () => {
  try {
    await axios.put('/publicstore/account/profile', {
      default_store: profileForm.default_store,
      default_shipping: profileForm.default_shipping?.toString(),
      notes: profileForm.notes,
    });
    showProfileModal.value = false;
    toast.add({ severity: 'success', summary: 'Success', detail: 'Profile saved successfully.', life: 3000 });
  } catch (error) {
    console.error('Failed to save profile:', error);
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to save profile.', life: 5000 });
  }
};

const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('en-CA', {
    style: 'currency',
    currency: 'CAD'
  }).format(value || 0);
};
</script>