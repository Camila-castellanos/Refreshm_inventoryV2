<template>
  <PortalLayout>
    <!-- Header bar -->
    <div class="flex items-center gap-4 mb-6">
      <Link :href="route('public-store.portal.dashboard')" class="text-surface-600 hover:text-surface-900 dark:text-surface-300">
        <i class="pi pi-arrow-left text-xl"></i>
      </Link>
      <h1 class="text-2xl font-semibold text-surface-900 dark:text-surface-0">My Credit</h1>
    </div>

    <!-- Balance Card -->
    <div class="bg-gradient-to-r from-primary-500 to-primary-600 rounded-lg shadow p-8 text-white mb-6">
      <div class="text-surface-200 text-sm mb-1">Current Balance</div>
      <div class="text-4xl font-bold mb-2">{{ formatCurrency(balance) }}</div>
      <div class="text-surface-200 text-sm">{{ currentCurrency }}</div>
    </div>

    <!-- Transactions -->
    <div class="bg-white dark:bg-surface-800 rounded-lg shadow mb-6">
      <div class="px-6 py-4 border-b border-surface-200 dark:border-surface-700">
        <h2 class="text-lg font-semibold text-surface-900 dark:text-surface-0">Transactions</h2>
      </div>
      <div class="p-6">
        <div class="space-y-4">
          <!-- Credit from Returns -->
          <div class="flex justify-between items-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
            <div>
              <div class="font-medium text-surface-900 dark:text-surface-0">Credit from Returns</div>
              <div class="text-sm text-surface-600 dark:text-surface-400">Total accumulated</div>
            </div>
            <div class="text-lg font-semibold text-green-600">
              +{{ formatCurrency(transactions.credit_from_returns || 0) }}
            </div>
          </div>

          <!-- Current Balance -->
          <div class="flex justify-between items-center p-4 bg-surface-50 dark:bg-surface-700 rounded-lg">
            <div>
              <div class="font-medium text-surface-900 dark:text-surface-0">Available Balance</div>
              <div class="text-sm text-surface-600 dark:text-surface-400">Current balance</div>
            </div>
            <div class="text-lg font-semibold text-surface-900 dark:text-surface-0">
              {{ formatCurrency(transactions.current_balance || 0) }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Info Box -->
    <div class="bg-surface-100 dark:bg-surface-700 rounded-lg p-4">
      <div class="flex items-start gap-3">
        <i class="pi pi-info-circle text-primary-500 mt-1"></i>
        <div class="text-sm text-surface-600 dark:text-surface-300">
          Credit is generated when you return equipment. This balance can be used in future purchases.
        </div>
      </div>
    </div>

    <Link :href="route('public-store.portal.dashboard')" class="inline-block mt-6">
      <Button label="Back to dashboard" icon="pi pi-home" class="p-button-outlined" />
    </Link>
  </PortalLayout>
</template>

<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import Button from "primevue/button";
import PortalLayout from "@/Layouts/PortalLayout.vue";
import { useCurrency } from "@/Composables/useCurrency";

const { formatCurrency, currentCurrency } = useCurrency();

defineProps<{
  balance: number;
  currency: string;
  transactions: {
    credit_from_returns: number;
    current_balance: number;
  };
}>();

</script>
