<template>
  <StoragesAssign :items="selectedItems" ref="assignStorageVisible"></StoragesAssign>
  <div>
    <section class="w-[90%] mx-auto mt-4">
      <Tabs v-model:value="currentTab">
        <TabList>
          <Tab v-for="tab in tabs" :key="tab.value" :value="tab.value">
            {{ tab.title }}
          </Tab>
          <Tab :value="tabs.length + 1" @click="openAddTabModal">
            <i class="pi pi-plus"></i>
          </Tab>
        </TabList>
        <TabPanels>
          <TabPanel v-for="tab in tabs" :key="tab.value" :value="tab.value">
            <DataTable
              v-if="tab.value === '0'"
              title="Active Inventory"
              @update:selected="handleSelection"
              :actions="tableActions"
              :items="tableData"
              :headers="headers"></DataTable>
            <DataTable
              v-else-if="tab.value === '1'"
              title="On Hold"
              @update:selected="handleSelection"
              :items="[]"
              :headers="[]"></DataTable>
            <DataTable
              v-else-if="tab.value === '2'"
              title="Sold"
              @update:selected="handleSelection"
              :items="getSoldItems()"
              :headers="headers"></DataTable>
            <DataTable
              v-else
              :title="tab.title"
              @update:selected="handleSelection"
              :items="tableData"
              :headers="headers"
              :actions="tableActions"></DataTable>
          </TabPanel>
        </TabPanels>
      </Tabs>
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
import { onMounted, ref, reactive, watch, Ref } from "vue";
import DataTable from "@/Components/DataTable.vue";
import { headers } from "./IndexData";
import { data } from "./IndexData";
import { router } from "@inertiajs/vue3";
import { defineProps } from "vue";
import StoragesAssign from "../Storages/StoragesAssign/StoragesAssign.vue";
import { Dialog } from "primevue";
import Tabs from "primevue/tabs";
import TabList from "primevue/tablist";
import Tab from "primevue/tab";
import TabPanels from "primevue/tabpanels";
import TabPanel from "primevue/tabpanel";
import { useDialog } from "primevue/usedialog";
import ItemsSell from "./ItemsSell/ItemsSell.vue";
import InputText from "primevue/inputtext";
import { Item, Tab as ITab } from "@/Lib/types";
import axios from "axios";

const dialog = useDialog();
const props = defineProps({
  items: Array<Item>,
  customers: Array,
  tabs: Array<ITab>,
});

const tabs = ref([
  { title: "Active Directory", value: "0" },
  { title: "On Hold", value: "1" },
  { title: "Sold", value: "2" },
]);

const assignStorageVisible: Ref<any> = ref(null);

const toggleAssignStorageVisible = () => {
  assignStorageVisible.value.openDialog();
};

let selectedItems: Ref<Item[]> = ref([]);
const currentTab = ref("0");
const lastTab = ref("0");

const handleSelection = (selected: Item[]) => {
  selectedItems.value = selected;
};

const tableData: Ref<Item[]> = ref([]);
function parseItemsData() {
  console.log(props.items);
  props.tabs?.forEach((tab, i) => {
    tabs.value.push({ title: tab.name, value: (tabs.value.length + i).toString() });
  });
  tableData.value = props
    .items!.filter((item) => item.sold === null)
    .map((item) => {
      if (item.storage) {
        const { name, limit } = item.storage;
        const { position } = item;
        return {
          ...item,
          location: `${name} - ${position}/${limit}`,
          vendor: item.vendor.vendor,
        };
      }
      return {
        ...item,
        location: "No storage information",
      };
    });
}
onMounted(() => {
  parseItemsData();
});

function openSellItemsModal() {
  dialog.open(ItemsSell, {
    data: {
      items: selectedItems,
      customers: props.customers,
    },
    props: {
      modal: true,
    },
  });
}

const tableActions = [
  {
    label: "Add Items",
    icon: "pi pi-plus",
    action: () => {
      router.visit("/inventory/items/bulk");
    },
  },
  // {
  //   label: "Reassign location",
  //   icon: "pi pi-arrow-up",
  //   action: () => {
  //     toggleAssignStorageVisible();
  //   },
  //   disable: (selectedItems) => selectedItems.length == 1,
  // },
  {
    label: "Sell",
    icon: "pi pi-dollar",
    action: () => {
      openSellItemsModal();
    },
    disable: (selectedItems: Item[]) => selectedItems.length == 0,
  },
  {
    label: "Delete Items",
    icon: "pi pi-trash",
    severity: "danger",
    action: () => {},
    disable: (selectedItems: Item[]) => selectedItems.length == 0,
  },
  {
    label: "Edit Items",
    icon: "pi pi-pencil",
    action: () => {
      console.log("hi");
    },
    disable: (selectedItems: Item[]) => selectedItems.length !== 1,
  },
];

function getSoldItems() {
  return props
    .items!.filter((item) => item.sold !== null)
    .map((item) => {
      return {
        ...item,
        location: `${item.sold_storage_name} - (${item.sold_position})`,
        vendor: item.vendor.vendor,
      };
    });
}

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
      window.location.reload();
    });
  }
}

watch(currentTab, (value) => {
  if (value != String(tabs.value.length + 1)) {
    lastTab.value = value;
  }
});
</script>
