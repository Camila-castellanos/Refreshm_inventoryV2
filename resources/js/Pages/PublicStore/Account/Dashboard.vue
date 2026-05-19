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

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
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
  </PortalLayout>
</template>

<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import PortalLayout from "@/Layouts/PortalLayout.vue";
import Divider from "primevue/divider";
import StatCard from "@/Components/StatCard.vue";

defineProps<{
  total_requests: number;
  pending_requests: number;
  credit_balance: number;
  credit_from_returns: number;
  recent_requests: any[];
}>();

const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('en-CA', {
    style: 'currency',
    currency: 'CAD'
  }).format(value || 0);
};
</script>