<template>
  <StoragesAssign :items="selectedItems" ref="assignStorageVisible"></StoragesAssign>
  <div>
    <section class="w-[90%] mx-auto mt-4">
      <ItemsTabs :custom-tabs="tabs">
        <DataTable
          title="Active Inventory"
          @update:selected="handleSelection"
          :actions="tableActions"
          :items="tableData"
          :headers="headers"></DataTable>
      </ItemsTabs>
    </section>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref, Ref, watchEffect } from "vue";
import DataTable from "@/Components/DataTable.vue";
import { headers } from "./IndexData";
import { router } from "@inertiajs/vue3";
import { defineProps } from "vue";
import StoragesAssign from "../Storages/StoragesAssign/StoragesAssign.vue";
import { useDialog } from "primevue/usedialog";
import ItemsSell from "./Modals/ItemsSell.vue";
import { Item, Tab as ITab } from "@/Lib/types";
import axios from "axios";
import MoveItem from "./Modals/MoveItem.vue";
import ItemsTabs from "@/Components/ItemsTabs.vue";

const dialog = useDialog();
const props = defineProps({
  items: Array<Item>,
  customers: Array,
  tabs: { type: Array<ITab>, required: true },
});

const assignStorageVisible: Ref<any> = ref(null);

const toggleAssignStorageVisible = () => {
  assignStorageVisible.value.openDialog();
};

let selectedItems: Ref<Item[]> = ref([]);

const handleSelection = (selected: Item[]) => {
  selectedItems.value = selected;
};

const tableData: Ref<any[]> = ref([]);
function parseItemsData() {
  tableData.value = props
    .items!.filter((item) => item.sold === null)
    .map((item: any) => {
      if (item.storage) {
        const { name, limit } = item.storage;
        const { position } = item;
        return {
          ...item,
          location: `${name} - ${position}/${limit}`,
          vendor: item.vendor.vendor,
          actions: [
            {
              label: "Move Tab",
              icon: "pi pi-arrow-right-arrow-left",
              extraClasses: "!font-black",
              action: (item: Item) => {
                openMoveItemsModal(item);
              },
            },
          ],
        };
      }
      return {
        ...item,
        location: "No storage information",
        vendor: item.vendor.vendor,
        actions: [
          {
            label: "Move Tab",
            icon: "pi pi-arrow-right-arrow-left",
            extraClasses: "!font-black",
            action: (item: Item) => {
              openMoveItemsModal(item);
            },
          },
        ],
      };
    });
}

onMounted(() => {
  parseItemsData();
});

watchEffect(() => {
  if (tableData.value) {
    parseItemsData();
  }
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
    onClose: () => {
      router.reload()
    },
  });
}

function openMoveItemsModal(item: Item) {
  dialog.open(MoveItem, {
    data: {
      tabs: props.tabs,
      item: item,
    },
    props: {
      modal: true,
    },
    onClose: () => {
      router.reload({ only: ["items"] });
    },
  });
}

const tableActions = [
  {
    label: "Add Items",
    icon: "pi pi-plus",
    action: () => {
      router.visit("/inventory/items/excel/create");
    },
  },
  {
    label: "Reassign location",
    icon: "pi pi-arrow-up",
    action: () => {
      toggleAssignStorageVisible();
    },
    disable: (selectedItems: Item[]) => selectedItems.length == 0,
  },
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
  {
    label: "Place on hold",
    icon: "pi pi-lock",
    action: () => console.log("hi"),
    disable: (selectedItems: Item[]) => selectedItems.length == 0,
  },
];
</script>
