<template>
  <PortalLayout>
    <!-- Header bar -->
    <div class="flex items-center gap-4 mb-6">
      <Link :href="route('public-store.portal.dashboard')" class="text-surface-600 hover:text-surface-900 dark:text-surface-300">
        <i class="pi pi-arrow-left text-xl"></i>
      </Link>
      <h1 class="text-2xl font-semibold text-surface-900 dark:text-surface-0">Returns / Exchanges</h1>
    </div>

    <!-- Credit Summary -->
    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-6 mb-6">
      <div class="text-surface-600 dark:text-surface-400 text-sm">Total credit generated</div>
      <div class="text-3xl font-bold text-green-600">{{ formatCurrency(credit_total) }}</div>
    </div>

    <div v-if="returns.data?.length === 0" class="text-center py-8">
      <div class="text-surface-500">You have no returns</div>
    </div>

    <div v-else class="space-y-4">
      <div v-for="ret in returns.data" :key="ret.id" class="bg-white dark:bg-surface-800 rounded-lg shadow p-6">
        <div class="flex justify-between items-start">
          <div>
            <div class="text-lg font-medium text-surface-900 dark:text-surface-0">{{ ret.model || 'No model' }}</div>
            <div class="text-surface-600 dark:text-surface-400">IMEI: {{ ret.imei || '-' }}</div>
            <div class="text-sm text-surface-500 dark:text-surface-500 mt-1">{{ formatDate(ret.created_at) }}</div>
          </div>
          <div class="text-right">
            <div class="text-lg font-semibold text-green-600">+{{ formatCurrency(ret.credit) }}</div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="returns.last_page > 1" class="flex justify-center gap-2 mt-6">
        <Button v-if="returns.current_page > 1" icon="pi pi-chevron-left" @click="loadPage(returns.current_page - 1)" class="p-button-outlined" />
        <span class="px-4 py-2 text-surface-600">{{ returns.current_page }} / {{ returns.last_page }}</span>
        <Button v-if="returns.current_page < returns.last_page" icon="pi pi-chevron-right" @click="loadPage(returns.current_page + 1)" class="p-button-outlined" />
      </div>
    </div>
  </PortalLayout>
</template>

<script setup lang="ts">
import { Link, router } from "@inertiajs/vue3";
import Button from "primevue/button";
import PortalLayout from "@/Layouts/PortalLayout.vue";
import { useCurrency } from "@/Composables/useCurrency";

const { formatCurrency } = useCurrency();

defineProps<{
  returns: {
    data: any[];
    current_page: number;
    last_page: number;
  };
  credit_total: number;
}>();

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-CA', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });
};

const loadPage = (page: number) => {
  router.get(route('public-store.portal.returns', { page }));
};
</script>