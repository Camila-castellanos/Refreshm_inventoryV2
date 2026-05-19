<template>
  <PortalLayout>
    <!-- Header bar -->
    <div class="flex items-center gap-4 mb-6">
      <Link href="/publicstore/account/dashboard" class="text-surface-600 hover:text-surface-900 dark:text-surface-300">
        <i class="pi pi-arrow-left text-xl"></i>
      </Link>
      <h1 class="text-2xl font-semibold text-surface-900 dark:text-surface-0">My Requests</h1>
    </div>

    <!-- Content -->
    <div v-if="requests.data?.length === 0" class="text-center py-8">
      <div class="text-surface-500">You have no requests</div>
    </div>

    <div v-else class="space-y-4">
      <div v-for="request in requests.data" :key="request.id"
        class="bg-white dark:bg-surface-800 rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
        <Link :href="`/publicstore/account/requests/${request.id}`" class="block">
          <div class="flex justify-between items-start">
            <div>
              <div class="text-lg font-medium text-surface-900 dark:text-surface-0">#{{ request.id }}</div>
              <div class="text-surface-600 dark:text-surface-400">{{ request.name || 'Unnamed' }}</div>
              <div class="text-sm text-surface-500 dark:text-surface-500">{{ request.shop_id || 'Store' }}</div>
              <div class="text-sm text-surface-500 dark:text-surface-500 mt-1">{{ formatDate(request.created_at) }}</div>
            </div>
            <div class="flex flex-col items-end gap-2">
              <span v-if="request.processed" class="px-3 py-1 bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded-full text-sm">Processed</span>
              <span v-else class="px-3 py-1 bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400 rounded-full text-sm">Pending</span>
              <span class="text-sm text-surface-500">{{ request.items?.length || 0 }} items</span>
            </div>
          </div>
        </Link>
      </div>

      <!-- Pagination -->
      <div v-if="requests.last_page > 1" class="flex justify-center gap-2 mt-6">
        <Button v-if="requests.current_page > 1" icon="pi pi-chevron-left" @click="loadPage(requests.current_page - 1)" class="p-button-outlined" />
        <span class="px-4 py-2 text-surface-600">{{ requests.current_page }} / {{ requests.last_page }}</span>
        <Button v-if="requests.current_page < requests.last_page" icon="pi pi-chevron-right" @click="loadPage(requests.current_page + 1)" class="p-button-outlined" />
      </div>
    </div>
  </PortalLayout>
</template>

<script setup lang="ts">
import { Link, router } from "@inertiajs/vue3";
import Button from "primevue/button";
import PortalLayout from "@/Layouts/PortalLayout.vue";

defineProps<{
  requests: {
    data: any[];
    current_page: number;
    last_page: number;
  };
}>();

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-CA', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });
};

const loadPage = (page: number) => {
  router.get(`/publicstore/account/requests?page=${page}`);
};
</script>