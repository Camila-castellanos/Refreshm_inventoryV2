<template>
  <div>
    <section class="w-full px-2 py-1 mx-auto mt-4">
      <Tabs v-model:value="currentTab">
        <TabList>
          <Tab value="/payments" @click="redirectToTab('/payments')">Payments</Tab>
          <Tab value="/expenses" @click="redirectToTab('/expenses')">Expenses</Tab>
          <Tab value="/bills" @click="redirectToTab('/bills')">Bills</Tab>
          <Tab value="/taxes" @click="redirectToTab('/taxes')">Taxes</Tab>
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
import { onMounted, ref } from "vue";
import { router } from "@inertiajs/vue3";

const currentTab = ref("/payments");

const redirectToTab = (tab: string) => {
  router.visit("/accounting"+tab);
};

const getCurrentTab = () => {
   currentTab.value = window.location.pathname.split("/")[2];
  console.log(currentTab.value);
};

onMounted(() => {
  getCurrentTab();
});

</script>
