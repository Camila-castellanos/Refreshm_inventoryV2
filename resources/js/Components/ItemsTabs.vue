<template>
  <ConfirmDialog />
  <div>
    <section class="w-full px-2 py-1 mx-auto mt-4">
      <Tabs v-model:value="currentTab">
        <TabList>
          <Tab v-for="tab in tabs" :key="tab.order" :value="tab.order" @click="redirectToTab(tab)" class="!relative">
            <span>{{ tab.name }}</span>
            <Button
              v-if="tab.id"
              icon="pi pi-times"
              class="!absolute -top-1 -left-1 !p-2.5 !w-4 !h-4 flex items-center justify-center"
              icon-class="!text-xs !p-0"
              rounded
              variant="outlined"
              raised
              v-tooltip.bottom="`Remove ${tab.name} tab`"
              @click.stop="removeTab(tab)" />
          </Tab>
          <Tab :value="tabs.length + 1" @click="openAddTabModal">
            <i class="pi pi-plus"></i>
          </Tab>
        </TabList>
      </Tabs>
      <slot />
    </section>
  </div>
  <Dialog v-model:visible="addTabDialog" header="Add New Tab" modal @show="currentTab = lastTab">
    <div class="p-fluid">
      <div class="flex flex-col py-3">
        <label for="tabTitle">Tab Title</label>
        <InputText id="tabTitle" v-model="newTab.title" />
      </div>
      <div class="w-full mt-5">
        <Button label="Add" @click="addNewTab" class="w-full" />
      </div>
    </div>
  </Dialog>
</template>

<script setup lang="ts">
import { Tab as ITab } from "@/Lib/types";
import { router } from "@inertiajs/vue3";
import axios from "axios";
import { Button, ConfirmDialog, Dialog, useConfirm } from "primevue";
import InputText from "primevue/inputtext";
import Tab from "primevue/tab";
import TabList from "primevue/tablist";
import Tabs from "primevue/tabs";
import { defineProps, onMounted, reactive, ref, Ref, watch } from "vue";

const props = defineProps({
  customTabs: {
    type: Array<ITab>,
    required: true,
  },
});

const tabs: Ref<ITab[]> = ref([
  { name: "Active Inventory", order: 0 },
  { name: "On Hold", order: 1 },
  { name: "Sold", order: 2 },
]);

const currentTab = ref(0);
const lastTab = ref(0);
const confirm = useConfirm();

onMounted(() => {
  props.customTabs
    ?.sort((a, b) => a.order - b.order)
    .forEach((tab) => {
      tabs.value.push({ name: tab.name, order: tabs.value.length + tab.order, id: tab.id });
    });
  // get endpoint for current tab
  let url = window.location.href;
  let endpoint = url.split("/inventory/")[1];
  switch (endpoint) {
    case "items":
      currentTab.value = 0;
      break;
    case "items/hold":
      currentTab.value = 1;
      break;
    case "report":
      currentTab.value = 2;
      break;
    default:
      let tabId = endpoint.split("/tab/")[1];
      currentTab.value = tabs.value.find((tab) => tab.id == Number(tabId))?.order ?? 0;
      break;
  }
});

const addTabDialog = ref(false);
const newTab = reactive({ title: "", value: tabs.value.length.toString() });

function openAddTabModal() {
  addTabDialog.value = true;
}

function addNewTab() {
  if (newTab.title.trim() !== "") {
    axios.post(route("tab.store"), { tab: newTab.title }).then((response) => {
      tabs.value.push(response.data);
      addTabDialog.value = false;
      router.reload({ only: ["tabs"] });
    });
  }
}

watch(currentTab, (value) => {
  if (value != tabs.value.length + 1) {
    lastTab.value = value;
  }
});

function redirectToTab(tab: ITab) {
  let endpoint: string;
  switch (tab.order) {
    case 0:
      endpoint = "/items";
      break;
    case 1:
      endpoint = "/items/hold";
      break;
    case 2:
      endpoint = "/report";
      break;
    default:
      endpoint = `/items/tab/${tab.id}`;
      break;
  }
  const route = `/inventory${endpoint}`;
  router.visit(route);
}

function removeTab(tab: ITab) {
  confirm.require({
    message: `Are you sure you want to delete the tab "${tab.name}"?`,
    header: "Confirm Delete",
    icon: "pi pi-exclamation-triangle",
    acceptClass: "p-button-danger",
    accept: () => {
      axios.post(route("tab.remove"), { id: tab.id }).then((response) => {
        router.reload({ only: ["tabs"] });
      });
    },
  });
}
</script>
