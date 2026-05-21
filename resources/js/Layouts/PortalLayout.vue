<template>
  <div class="min-h-screen max-w-screen">
<header class="bg-white dark:bg-surface-800 shadow">
      <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
        <div class="flex items-center gap-4">
          <img src="/images/swiftstock_logo.jpeg" class="h-10" alt="Logo" />
          <span class="text-surface-500 text-sm">{{ title }}</span>
        </div>
        <div class="flex items-center gap-4">
          <template v-if="auth?.user">
            <span class="text-surface-600 dark:text-surface-300 text-sm">{{ auth.user.email }}</span>
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

    <main class="min-h-screen">
      <slot />
    </main>

    <Toast />
    <ConfirmDialog />
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { Link, usePage, router } from "@inertiajs/vue3";
import Button from "primevue/button";
import Toast from "primevue/toast";
import ConfirmDialog from "primevue/confirmdialog";

const props = defineProps<{
  title?: string;
}>();

const page = usePage();
const auth = computed(() => page.props.customer_auth);

const logout = () => {
  router.post('/publicstore/account/logout');
};

const login = () => {
  window.location.href = '/publicstore/account/login';
};

const dashboard = () => {
  window.location.href = '/publicstore/account/dashboard';
};
</script>