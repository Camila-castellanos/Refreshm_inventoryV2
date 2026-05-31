<template>
  <div class="min-h-screen bg-surface-50 dark:bg-surface-900">
    <!-- Header -->
    <div class="bg-white dark:bg-surface-800 shadow">
      <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
        <div class="flex items-center gap-4">
          <Link :href="route('public-store.portal.dashboard')" class="text-surface-600 hover:text-surface-900 dark:text-surface-300">
            <i class="pi pi-arrow-left text-xl"></i>
          </Link>
          <h1 class="text-2xl font-semibold text-surface-900 dark:text-surface-0">My Orders</h1>
        </div>
        <Button label="Sign out" icon="pi pi-sign-out" @click="logout" class="p-button-outlined p-button-sm" />
      </div>
    </div>

    <!-- Content -->
    <div class="max-w-7xl mx-auto px-4 py-8">
      <div v-if="orders.data?.length === 0" class="text-center py-8">
        <div class="text-surface-500">You have no orders</div>
      </div>

      <div v-else class="space-y-4">
        <div v-for="order in orders.data" :key="order.id" class="bg-white dark:bg-surface-800 rounded-lg shadow p-6">
          <div class="flex justify-between items-start">
            <div>
              <div class="text-lg font-medium text-surface-900 dark:text-surface-0">Order #{{ order.id }}</div>
              <div class="text-surface-600 dark:text-surface-400">{{ order.email || '-' }}</div>
              <div class="text-sm text-surface-500 dark:text-surface-500 mt-1">{{ formatDate(order.created_at) }}</div>
            </div>
            <div class="text-right">
              <div class="text-lg font-semibold text-surface-900 dark:text-surface-0">{{ formatCurrency(order.total) }}</div>
              <span v-if="order.status" class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">{{ order.status }}</span>
            </div>
          </div>
          <div v-if="order.items?.length > 0" class="mt-4">
            <div class="text-sm text-surface-600 dark:text-surface-400 mb-2">{{ order.items.length }} items</div>
            <div class="flex flex-wrap gap-2">
              <span v-for="item in order.items.slice(0, 5)" :key="item.id" class="px-2 py-1 bg-surface-100 dark:bg-surface-700 rounded text-sm text-surface-700 dark:text-surface-300">
                {{ item.model || 'Item #' + item.id }}
              </span>
              <span v-if="order.items.length > 5" class="px-2 py-1 text-surface-500 text-sm">
                +{{ order.items.length - 5 }} more
              </span>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="orders.last_page > 1" class="flex justify-center gap-2 mt-6">
          <Button v-if="orders.current_page > 1" icon="pi pi-chevron-left" @click="loadPage(orders.current_page - 1)" class="p-button-outlined" />
          <span class="px-4 py-2 text-surface-600">{{ orders.current_page }} / {{ orders.last_page }}</span>
          <Button v-if="orders.current_page < orders.last_page" icon="pi pi-chevron-right" @click="loadPage(orders.current_page + 1)" class="p-button-outlined" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Link, router } from "@inertiajs/vue3";
import Button from "primevue/button";
import { useCurrency } from "@/Composables/useCurrency";

const { formatCurrency } = useCurrency();

defineProps<{
  orders: {
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
  router.get(route('public-store.portal.orders', { page }));
};

const logout = () => {
  router.post(route('public-store.portal.logout'));
};
</script>