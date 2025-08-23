<template>
  <StoragesAssign :items="selectedItems" ref="assignStorageVisible" @refreshTable="refreshTableData"></StoragesAssign>
  <Dialog v-model:visible="showCustomFields" header="Edit fields" :modal="true">
    <CustomFields :headers="headers" :custom-headers="fields ?? []" @update-headers="updateTableHeaders" />
  </Dialog>
  <div>
    <section class="w-[95%] mx-auto mt-4">
      <ItemsTabs :custom-tabs="tabs">
        <DataTable title="Active Inventory" @update:selected="handleSelection" :actions="tableActions"
          :items="tableData" inventory :headers="allHeaders" v-model:selection="selectedItems"></DataTable>
      </ItemsTabs>
    </section>
  </div>
  <AddItemsToSale
    v-model:visible="showAddItemsToSale"
    @add="addItemsToSale"
  />
</template>

<script setup lang="ts">
import DataTable from "@/Components/DataTable.vue";
import ItemsTabs from "@/Components/ItemsTabs.vue";
import { CustomField, Field, Tab as ITab, Item } from "@/Lib/types";
import { router } from "@inertiajs/vue3";
import axios from "axios";
import { Dialog, useConfirm, useToast } from "primevue";
import { useDialog } from "primevue/usedialog";
import { defineProps, onMounted, ref, Ref, watchEffect } from "vue";
import StoragesAssign from "../Storages/StoragesAssign/StoragesAssign.vue";
import { headers } from "./IndexData";
import CustomFields from "./Modals/CustomFields.vue";
import ItemsSell from "./Modals/ItemsSell.vue";
import MoveItem from "./Modals/MoveItem.vue";
import AddItemsToSale from "./Modals/AddItemsToSale.vue";
import { nextTick } from 'vue';

const confirm = useConfirm();
const toast = useToast();
const dialog = useDialog();
const showCustomFields = ref(false);
const showAddItemsToSale = ref(false);
const props = defineProps({
  items: Array<Item>,
  customers: Array,
  fields: Array<Field>,
  tabs: { type: Array<ITab>, required: true },
});

const tabs = ref(props.tabs);

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
    ...(props.fields?.filter((f) => f.active).map((field) => ({ name: field.value, label: field.text, type: field.type })) ?? []),
  ];
  tabs.value = props.tabs;
  console.log(props.items);
  tableData.value = props
    .items!.filter((item) => item.sold === null)
    .map((item: any) => {
      if (item.battery === null){
        item.battery = "";
      }
      else if (!String(item.battery).endsWith("%") && !isNaN(Number(item.battery)) && item.battery !== "") {
        item.battery = `${item.battery} %`;
      }
      if (item.storage) {
        const { name, limit, items_count } = item.storage;
        const { position } = item;
        return {
          ...item,
          location: `${name} - ${position}/${limit}`,
          vendor: item.vendor?.vendor,
        };
      }
      return {
        ...item,
        location: "N/A",
        vendor: item.vendor?.vendor,
      };
    });
}

function refreshTableData() {
  axios
    .get(route("items.getItems")) // Asegúrate de que esta ruta coincida con la definida en el backend
    .then((response) => {
      // Actualiza los datos de la tabla con los ítems obtenidos del backend
      tableData.value = response.data.map((item: any) => {
        if (item.storage) {
          const { name, limit } = item.storage;
          const { position } = item;
          return {
            ...item,
            location: `${name} - ${position}/${limit}`, // Formatea la columna "location"
            vendor: item.vendor?.vendor, // Incluye el nombre del vendor si está disponible
          };
        }
        return {
          ...item,
          location: "N/A", // Valor predeterminado si no hay almacenamiento
          vendor: item.vendor?.vendor,
        };
      });
    })
    .catch((error) => {
      console.error("Error refreshing table data:", error);
    });
}

onMounted(() => {
  parseItemsData();
});

watchEffect(() => {
  if (tableData.value || props.fields || props.tabs) {
    parseItemsData();
  }
});

function openSellItemsModal() {
  console.log('Opening ItemsSell dialog with items:', selectedItems.value);
  dialog.open(ItemsSell, {
    data: {
      items: selectedItems,
    },
    props: {
      modal: true,
    },
    onClose: (result) => {
      if (result?.data?.sold) {
        console.log("Items sold successfully");
        selectedItems.value.length = 0;
        router.reload(
          { only: ["items"] }
        );
      }
      // Si solo se cerró el modal, no limpiar la selección
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
    label: "Add Items",
    important: true,
    icon: "pi pi-plus",
    action: () => {
      router.visit("/inventory/items/excel/create");
    },
  },
  {
    label: "Sell",
    icon: "pi pi-dollar",
    important: true,
    action: () => {
      openSellItemsModal();
    },
  },
  {
    label: "Edit",
    icon: "pi pi-pencil",
    important: true,
    action: () => {
      onEdit();
    },
    disable: (selectedItems: Item[]) => selectedItems.length === 0,
  },
  {
    label: "Edit fields",
    icon: "pi pi-pen-to-square",
    action: () => {
      showCustomFields.value = true;
    },
  },
  {
    label: "Delete selected",
    icon: "pi pi-trash",
    severity: "danger",
    important: true,
    action: () => {
      onDeleteMultiple();
    },
    disable: (selectedItems: Item[]) => selectedItems.length == 0,
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
    label: "Move Tab",
    icon: "pi pi-arrow-right-arrow-left",
    extraClasses: "!font-black",
    action: () => {
      openMoveItemsModal();
    },
    disable: (selectedItems: Item[]) => selectedItems.length == 0,
  },
  {
    label: "Print Items Labels",
    icon: "pi pi-print",
    action: () => openLabels(),
    disable: (selectedItems: Item[]) => selectedItems.length == 0,
  },
  {
    label: "Add To Existing Invoice",
    icon: "pi pi-credit-card",
    action: () => {
      showAddItemsToSale.value = true;
    },
    disable: (selectedItems: Item[]) => selectedItems.length == 0,
  }
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

// Function to open the label in a new tab
async function openLabels() {
  if (selectedItems.value.length === 0) return;
  // Construye "1,2,5"
  const itemsParam = selectedItems.value.map(item => item.id).join(',');
  const res = await axios.get(route('items.labels', { items: itemsParam }), {
    responseType: 'blob'
  });
  const blob = new Blob([res.data], { type: 'application/pdf' });
  const url  = URL.createObjectURL(blob);
  window.open(url, '_blank');
  URL.revokeObjectURL(url);
}

async function addItemsToSale(saleId: number) {
  try {
    const payload = {
      sale_id: saleId,
      items: selectedItems.value.map(item => ({
        id:             item.id,
        storage_id:     item.storage_id,
        position:       item.position,
        selling_price:  item.selling_price,
        customer:       item.customer,
      }))
    };
    console.log('Adding items to sale:', payload);
    await axios.post(route('payments.addNewItems'), payload);

    toast.add({
      severity: 'success',
      summary: 'Added',
      detail: `Items assigned to sale #${saleId}`
    });
    selectedItems.value.splice(0); // Clear selected items after adding to sale
    
    showAddItemsToSale.value = false;

     await nextTick();
    // refresh your table
    router.reload({ only: ['items'] });

  } catch (e: any) {
    toast.add({
      severity: 'error',
      summary:  'Error',
      detail:   e.response?.data || e.message
    });
  }
}
</script>

<style scoped lang="css">
/* General style for all table cells */
:deep(.p-datatable .p-datatable-tbody > tr > td) {
  font-size: 0.85rem; 
}

/* General style for all table headers */
:deep(.p-datatable .p-datatable-thead > tr > th) {
  font-size: 0.85rem;
}
</style>
