<template>
  <div>
    <section class="w-full px-2 py-1 mx-auto mt-4">
      <Tabs v-model:value="currentTab">
        <TabList>
          <Tab value="/customer" @click="redirectToTab('/customer')">Customers</Tab>
          <Tab value="/prospects" @click="redirectToTab('/prospects')">Prospects</Tab>
          <Tab value="/vendor" @click="redirectToTab('/vendor')">Vendors</Tab>
          <Tab value="/mailing_list" @click="redirectToTab('/mailing_list')">Mailing list</Tab>
          <Tab value="/email_templates" @click="redirectToTab('/email_templates')">Email editor</Tab>
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

const currentTab = ref("/customer");

const redirectToTab = (tab: string) => {
  router.visit(tab);
};

const getCurrentTab = () => {
  currentTab.value = window.location.pathname;
  console.log(currentTab.value);
};

onMounted(() => {
  getCurrentTab();
});

</script>
