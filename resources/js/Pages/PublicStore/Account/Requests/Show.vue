<template>
  <PortalLayout>
    <!-- Header bar -->
    <div class="flex items-center gap-4 mb-6">
      <Link href="/publicstore/account/requests" class="text-surface-600 hover:text-surface-900 dark:text-surface-300">
        <i class="pi pi-arrow-left text-xl"></i>
      </Link>
      <h1 class="text-2xl font-semibold text-surface-900 dark:text-surface-0">Request #{{ requestId }}</h1>
    </div>

    <!-- Content -->
    <div v-if="error" class="text-center py-8">
      <div class="text-red-500">{{ error }}</div>
      <Link href="/publicstore/account/requests" class="text-primary-500 hover:text-primary-600 mt-4 inline-block">Back to requests</Link>
    </div>

    <div v-else class="space-y-6">
      <!-- Request Info -->
      <div class="bg-white dark:bg-surface-800 rounded-lg shadow p-6">
        <div class="flex justify-between items-start">
          <div>
            <h2 class="text-xl font-semibold text-surface-900 dark:text-surface-0">{{ requestData.name || 'Unnamed' }}</h2>
            <div class="text-surface-600 dark:text-surface-400 mt-1">{{ requestData.shop_id || 'Store' }}</div>
            <div class="text-sm text-surface-500 dark:text-surface-500 mt-2">{{ formatDate(requestData.created_at) }}</div>
          </div>
          <span v-if="requestData.processed" class="px-3 py-1 bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded-full text-sm">Processed</span>
          <span v-else-if="requestData.deleted_at" class="px-3 py-1 bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 rounded-full text-sm">Cancelled</span>
          <span v-else class="px-3 py-1 bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400 rounded-full text-sm">Pending</span>
        </div>

        <div v-if="requestData.notes" class="mt-4 p-4 bg-surface-50 dark:bg-surface-700 rounded-lg">
          <div class="text-sm text-surface-600 dark:text-surface-400">Notes:</div>
          <div class="text-surface-900 dark:text-surface-0">{{ requestData.notes }}</div>
        </div>
      </div>

      <!-- Items -->
      <div class="bg-white dark:bg-surface-800 rounded-lg shadow">
        <div class="px-6 py-4 border-b border-surface-200 dark:border-surface-700">
          <h3 class="text-lg font-semibold text-surface-900 dark:text-surface-0">Items ({{ items.length }})</h3>
        </div>
        <div class="p-6">
          <div v-if="items.length === 0" class="text-center text-surface-500">No items</div>
          <div v-else class="overflow-x-auto">
            <table class="w-full">
              <thead>
                <tr class="border-b border-surface-200 dark:border-surface-700">
                  <th class="text-left py-3 px-4 text-surface-600">Model</th>
                  <th class="text-left py-3 px-4 text-surface-600">IMEI</th>
                  <th class="text-left py-3 px-4 text-surface-600">Status</th>
                  <th class="text-right py-3 px-4 text-surface-600">Cost</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in items" :key="item.id" class="border-b border-surface-100 dark:border-surface-700">
                  <td class="py-3 px-4 text-surface-900 dark:text-surface-0">{{ item.model || '-' }}</td>
                  <td class="py-3 px-4 text-surface-900 dark:text-surface-0 font-mono text-sm">{{ item.imei || '-' }}</td>
                  <td class="py-3 px-4">
                    <span :class="getGradeClass(item.grade)" class="px-2 py-1 rounded text-sm">{{ item.grade || 'N/A' }}</span>
                  </td>
                  <td class="py-3 px-4 text-right text-surface-900 dark:text-surface-0">{{ formatCurrency(item.cost) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <Link href="/publicstore/account/requests" class="inline-block">
        <Button label="Back to requests" icon="pi pi-arrow-left" class="p-button-outlined" />
      </Link>
    </div>
  </PortalLayout>
</template>

<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import Button from "primevue/button";
import PortalLayout from "@/Layouts/PortalLayout.vue";

const props = defineProps<{
  requestData: any;
  items: any[];
  error?: string;
}>();

const requestId = props.requestData?.id || 0;

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-CA', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });
};

const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('en-CA', { style: 'currency', currency: 'CAD' }).format(value || 0);
};

const getGradeClass = (grade: string) => {
  const classes: Record<string, string> = {
    'A': 'bg-green-100 text-green-800',
    'B': 'bg-blue-100 text-blue-800',
    'C': 'bg-yellow-100 text-yellow-800',
    'D': 'bg-red-100 text-red-800',
  };
  return classes[grade] || 'bg-surface-100 text-surface-800';
};
</script>