<template>
  <StoragesAssign :items="selectedItems" ref="assignStorageVisible"></StoragesAssign>

  <div>
    <section class="w-[95%] mx-auto mt-4">
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
  <Dialog v-model:visible="showDialog" header="Assign Customer" :modal="true">
    <div class="p-4">
      <label for="customer-name" class="block text-sm font-medium">Customer Name:</label>
      <InputText id="customer-name" v-model="customerName" class="w-full mt-2" />
    </div>
    <template #footer>
      <Button label="Cancel" icon="pi pi-times" @click="showDialog = false" class="p-button-text" />
      <Button label="Confirm" icon="pi pi-check" @click="confirmHold" class="p-button-primary" />
    </template>
  </Dialog>
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
import { Button, ConfirmDialog, Dialog, InputText, useConfirm, useToast } from "primevue";

const confirm = useConfirm();
const toast = useToast();
const dialog = useDialog();
const showDialog = ref(false);
const customerName = ref("");
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
              label: "Label",
              icon: "pi pi-file",
              action: (item: Item) => {
                window.location.assign(route("items.label", item.id));
              },
            },
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
            label: "Label",
            icon: "pi pi-file",
            action: (item: Item) => {
              window.location.assign(route("items.label", item.id));
            },
          },
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
      router.reload();
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
    action: () => {onDeleteMultiple()},
    disable: (selectedItems: Item[]) => selectedItems.length == 0,
  },
  {
    label: "Edit Items",
    icon: "pi pi-pencil",
    action: () => {
      onEdit();
    },
    disable: (selectedItems: Item[]) => selectedItems.length === 0,
  },
  {
    label: "Place on hold",
    icon: "pi pi-lock",
    action: () => onClickHold(),
    disable: (selectedItems: Item[]) => selectedItems.length == 0,
  },
];

const onClickHold = () => {
  confirm.require({
    message: "Are you sure you want to place these items on hold?",
    header: "Confirmation",
    icon: "pi pi-exclamation-triangle",
    accept: () => {
      showDialog.value = true; // Show dialog for customer input
      confirm.close();
    },
  });
};

const confirmHold = async () => {
  if (!customerName.value) {
    toast.add({ severity: "warn", summary: "Warning", detail: "Customer name is required", life: 3000 });
    return;
  }

  try {
    await axios.put(route("items.hold"), {
      data: selectedItems.value,
      customer: customerName.value,
    });

    toast.add({ severity: "success", summary: "Success", detail: "Items placed on hold!", life: 3000 });
    showDialog.value = false;
  } catch (error: any) {
    toast.add({
      severity: "error",
      summary: "Error",
      detail: error.response?.data || "An error occurred",
      life: 5000,
    });
  }
};

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
</script>
