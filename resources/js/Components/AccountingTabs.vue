<template>
  <div>
    <section class="w-full px-2 py-1 mx-auto mt-4">
      <Tabs v-model:value="currentTab">
        <TabList>
          <Tab v-if="isTabAllowed('Payments')" value="/payments" @click="redirectToTab('/payments')">Invoices</Tab>
          <Tab v-if="isTabAllowed('Expenses')" value="/expenses" @click="redirectToTab('/expenses')">Expenses</Tab>
          <Tab v-if="isTabAllowed('Bills')" value="/bills" @click="redirectToTab('/bills')">Bills</Tab>
          <Tab v-if="isTabAllowed('Taxes')" value="/taxes" @click="redirectToTab('/taxes')">Taxes</Tab>
        </TabList>
      </Tabs>
      <slot />
    </section>
  </div>
</template>

<script setup lang="ts">
import Tab from "primevue/tab";
import TabList from "primevue/tablist";
import Tabs from "primevue/tabs";
import { onMounted, ref, computed } from "vue";
import { router, usePage } from "@inertiajs/vue3";

const page = usePage();
const userPermissions = computed(() => page.props.auth?.user?.page_permissions);

const isTabAllowed = (tabName: string) => {
    const perms = userPermissions.value;
    
    // If permissions is not an object or owner, allow all
    if (!perms || typeof perms !== 'object' || Array.isArray(perms)) {
        return true;
    }
    
    const accountingPerms = perms['Accounting'];
    if (!accountingPerms || !Array.isArray(accountingPerms)) {
        return true; // No specific restriction
    }
    
    return accountingPerms.includes(tabName);
};

const currentTab = ref("/payments");

const redirectToTab = (tab: string) => {
  router.visit("/accounting"+tab);
};

const getCurrentTab = () => {
   currentTab.value = "/" + window.location.pathname.split("/")[2];
  console.log(currentTab.value);
};

onMounted(() => {
  getCurrentTab();
});

</script>
