<template>
  <div class="bg-gray-100 min-h-screen">
    <header class="bg-white shadow-md">
      <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
        <div class="flex items-center">
          <img src="/images/swiftstock_logo.jpeg" alt="Logo" class="h-12">
        </div>
        <div class="flex items-center gap-4">
          <template v-if="auth?.user">
            <span class="text-surface-600 dark:text-surface-300 text-sm hidden sm:inline">{{ auth.user.email }}</span>
            <Button
              label="Dashboard"
              icon="pi pi-home"
              @click="dashboard"
              class="p-button-outlined p-button-sm"
            />
            <Button
              label="Sign out"
              icon="pi pi-sign-out"
              @click="logout"
              class="p-button-outlined p-button-sm"
            />
          </template>
          <template v-else>
            <Button
              label="Sign in"
              icon="pi pi-sign-in"
              @click="login"
              class="p-button-outlined p-button-sm"
            />
          </template>
        </div>
      </div>
    </header>

    <main class="max-w-7xl mx-auto py-8 px-4">
      <InventoryList :items="items" :shopName="shopName" :shopSlug="shopSlug" :userTabs="userTabs"></InventoryList>
    </main>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import InventoryList from './InventoryList.vue';

interface Props {
  items?: any[];
  shopName?: string;
  shopSlug?: string;
  userTabs?: { id: number; name: string; order: number }[];
}

defineProps<Props>();

const page = usePage();
const auth = computed(() => page.props.customer_auth);

const logout = () => {
  router.post(route('public-store.portal.logout'));
};

const login = () => {
  router.visit(route('public-store.portal.login.show'));
};

const dashboard = () => {
  router.visit(route('public-store.portal.dashboard'));
};
</script>