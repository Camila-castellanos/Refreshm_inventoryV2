<template>
  <StoragesAssign :items="selectedItems" ref="assignStorageVisible"></StoragesAssign>
  <Dialog v-model:visible="showCustomFields" header="Edit fields" :modal="true">
    <CustomFields :headers="headers" :custom-headers="fields ?? []" @update-headers="updateTableHeaders" />
  </Dialog>
  <div>
    <section class="w-[95%] mx-auto mt-4">
      <ItemsTabs :custom-tabs="tabs">
        <DataTable
          title="Active Inventory"
          @update:selected="handleSelection"
          :actions="tableActions"
          :items="tableData"
          :headers="allHeaders"></DataTable>
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
import { Item, Tab as ITab, Field, CustomField } from "@/Lib/types";
import axios from "axios";
import MoveItem from "./Modals/MoveItem.vue";
import ItemsTabs from "@/Components/ItemsTabs.vue";
import { Button, ConfirmDialog, Dialog, InputText, useConfirm, useToast } from "primevue";
import CustomFields from "./Modals/CustomFields.vue";

const confirm = useConfirm();
const toast = useToast();
const dialog = useDialog();
const showDialog = ref(false);
const customerName = ref("");
const showCustomFields = ref(false);
const props = defineProps({
  items: Array<Item>,
  customers: Array,
  fields: Array<Field>,
  tabs: { type: Array<ITab>, required: true },
});

const assignStorageVisible: Ref<any> = ref(null);

const toggleAssignStorageVisible = () => {
  assignStorageVisible.value.openDialog();
};

let selectedItems: Ref<Item[]> = ref([]);
const allHeaders: Ref<CustomField[]> = ref([]);

const handleSelection = (selected: Item[]) => {
  selectedItems.value = selected;
};

const tableData: Ref<any[]> = ref([]);
function parseItemsData() {
  allHeaders.value = [
    ...headers.value,
    ...(props.fields?.map((field) => ({ name: field.value, label: field.text, type: field.type })) ?? []),
  ];
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
        };
      }
      return {
        ...item,
        location: "No storage information",
        vendor: item.vendor.vendor,
      };
    });
}

onMounted(() => {
  parseItemsData();
});

watchEffect(() => {
  if (tableData.value || props.fields) {
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
      router.reload();
    },
  });
}

function openMoveItemsModal() {
  dialog.open(MoveItem, {
    data: {
      tabs: props.tabs,
      items: selectedItems.value,
    },
    props: {
      modal: true,
      header: "Move items",
    },
    onClose: () => {
      router.reload({ only: ["items"] });
    },
  });
}

const tableActions = [
  {
    label: "Edit fields",
    icon: "pi pi-pen-to-square",
    action: () => {
      showCustomFields.value = true;
    },
  },
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
    label: "Delete selected",
    icon: "pi pi-trash",
    severity: "danger",
    action: () => {
      onDeleteMultiple();
    },
    disable: (selectedItems: Item[]) => selectedItems.length == 0,
  },
  {
    label: "Edit selected",
    icon: "pi pi-pencil",
    action: () => {
      onEdit();
    },
    disable: (selectedItems: Item[]) => selectedItems.length === 0,
  },
  {
    label: "Move Tab",
    icon: "pi pi-arrow-right-arrow-left",
    extraClasses: "!font-black",
    action: () => {
      openMoveItemsModal();
    },
  },
];

const onEdit = () => {
  const currentPaginate = document.getElementById("currentPaginate")?.getAttribute("data-id") || "";
  const filter = document.getElementsByClassName("filter--value")[0]?.value || "";

  document.cookie = `paginate=${currentPaginate}`;
  document.cookie = `pagefilter=${filter}`;

  let items = selectedItems.value.map((item: any) => item.id).join(";");

  router.get(route("items.edit", btoa(items)));
};

const onDeleteMultiple = () => {
  confirm.require({
    message: "Are you sure? You won't be able to revert this!",
    header: "Delete Confirmation",
    icon: "pi pi-exclamation-triangle",
    accept: async () => {
      try {
        const response = await axios.delete(route("items.obliterate"), { data: selectedItems.value });
        if (response.status >= 200 && response.status < 400) {
          toast.add({ severity: "success", summary: "Deleted", detail: "Items deleted successfully", life: 3000 });
          location.reload();
        }
      } catch (error: any) {
        toast.add({
          severity: "error",
          summary: "Error",
          detail: error.response?.data || error.message || "An error occurred",
          life: 5000,
        });
      }
    },
  });
};

const updateTableHeaders = (updatedHeaders: CustomField[]) => {
  allHeaders.value = updatedHeaders;
};
</script>
