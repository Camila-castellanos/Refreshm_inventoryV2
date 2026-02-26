<template>
  <div>
    <section class="w-full px-2 py-1 mx-auto mt-4">
      <Tabs v-model:value="currentTab">
        <TabList>
          <Tab v-if="isTabAllowed('Customers')" value="/customer" @click="redirectToTab('/customer')">Customers</Tab>
          <Tab v-if="isTabAllowed('Prospects')" value="/prospects" @click="redirectToTab('/prospects')">Prospects</Tab>
          <Tab v-if="isTabAllowed('Vendors')" value="/vendor" @click="redirectToTab('/vendor')">Vendors</Tab>
          <Tab v-if="isTabAllowed('Mailing list')" value="/mailing_list" @click="redirectToTab('/mailing_list')">Mailing list</Tab>
          <Tab v-if="isTabAllowed('Email editor')" value="/email_templates" @click="redirectToTab('/email_templates')">Email editor</Tab>
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
    
    if (!perms || typeof perms !== 'object' || Array.isArray(perms)) {
        return true;
    }
    
    const contactPerms = perms['Contacts'];
    if (!contactPerms || !Array.isArray(contactPerms)) {
        return true;
    }
    
    return contactPerms.includes(tabName);
};

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
