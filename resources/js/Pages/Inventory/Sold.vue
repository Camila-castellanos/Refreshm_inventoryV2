<template>
  <!-- Overlay de carga global -->
  <div 
    v-if="isLoading" 
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white p-6 rounded-lg shadow-lg flex items-center space-x-3">
      <i class="pi pi-spin pi-spinner text-2xl text-blue-500"></i>
      <span class="text-lg">{{ loadingMessage }}</span>
    </div>
  </div>

  <Dialog v-model:visible="showCustomFields" header="Edit fields" :modal="true">
    <CustomFields :headers="soldHeaders" :custom-headers="fields ?? []" @update-headers="updateTableHeaders" />
  </Dialog>
  
  <div :class="{ 'opacity-75 pointer-events-none': isLoading }">
    <section class="w-[95%] mx-auto mt-4">
      <ItemsTabs :custom-tabs="tabs">
        <DataTable
          title="Sold"
          v-model:selection="selectedItems"
          @update:selected="handleSelection"
          :items="tableData"
          inventory
          :headers="allHeaders"
          :actions="tableActions"
          :sortField="'sold'"
          :sortOrder="-1">
          <div class="w-full">
            <form class="flex flex-row justify-around" @submit.prevent="onDateRangeSubmit">
              <DatePicker
                v-model="dates"
                :max-date="new Date()"
                selectionMode="range"
                dateFormat="dd/mm/yy"
                class="w-56"
                id="date"
                fluid
                :disabled="isLoading"
                placeholder="Date range for report"></DatePicker>
              <Button 
                label="Generate" 
                class="mx-2" 
                size="large" 
                type="submit"
                :loading="isLoading"
                :disabled="isLoading" />
            </form>
          </div>
          
        </DataTable>
      </ItemsTabs>
    </section>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, Ref, computed } from "vue";
import DataTable from "@/Components/DataTable.vue";
import ItemsTabs from "@/Components/ItemsTabs.vue";
import { CustomField, Field, Tab as ITab, Item } from "@/Lib/types";
import { soldHeaders } from "./IndexData";
import Card from "primevue/card";
import Button from "primevue/button";
import { DatePicker, Dialog, useConfirm, useToast } from "primevue";
import { format } from "date-fns";
import axios from "axios";
import { router } from "@inertiajs/vue3";
import CustomFields from "./Modals/CustomFields.vue";

const confirm = useConfirm();
const toast = useToast();

const props = defineProps({
  tabs: { type: Array<ITab>, required: true },
  fields: Array<Field>,
  items: { type: Array<Item>, required: true },
});

const dates: Ref<Date | Date[] | (Date | null)[] | null | undefined> = ref([]);
const isLoading = ref(false); // Estado de carga
const loadingMessage = ref("Loading..."); // Mensaje dinámico

let selectedItems: Ref<Item[]> = ref([]);
const showCustomFields = ref(false);
const allHeaders: Ref<CustomField[]> = ref([]);

const updateTableHeaders = (updatedHeaders: CustomField[]) => {
  allHeaders.value = updatedHeaders;
};

const handleSelection = (selected: Item[]) => {
  selectedItems.value = selected;
  console.log("Selected items:", selectedItems.value);
};

const tableData: Ref<any[]> = ref([]);

function parseItemsData() {
  allHeaders.value = [
    ...soldHeaders.value,
    ...(props.fields?.filter((f) => f.active).map((field) => ({ name: field.value, label: field.text, type: field.type })) ?? []),
  ];
  updateTableData(props.items);
}

function updateTableData(data: any[]) {
  console.log("Data:", data);
  tableData.value = data.map((item: any) => {
    return {
      ...item,
      cost: `${item.cost}`,
      profit: `${item.profit}`,
      selling_price: `${item.selling_price}`,
      subtotal: `${item.subtotal ?? 'unknown'}`,
      total: `${item.total ?? 'unknown'}`,
      location: `${item.sold_storage_name ?? 'N/A'} - (${item.sold_position ?? 'N/A'})`,
      vendor: item.vendor?.vendor,
      battery: computed(() => {
        if (item.battery && !String(item.battery).endsWith("%")) {
          if (!isNaN(Number(item.battery)) && item.battery !== null) {
            return `${item.battery}%`;
          }
        }
        return item.battery;
      }),
      actions: [
        {
          label: "Edit Items",
          icon: "pi pi-pencil",
          action: () => {
            selectedItems.value = [item];
            onEdit();
          },
        },
        {
          label: "Print Label",
          icon: "pi pi-print",
          action: () => openLabels({value: [item]}),
        },
        {
          label: "Return",
          icon: "pi pi-undo",
          severity: "danger",
          action: () => {
            onReturn(item);
          },
        },
      ],
    };
  });
}

async function onDateRangeSubmit() {
  const start = format((dates.value as Date[])[0], "yyyy-MM-dd");
  const end = format((dates.value as Date[])[1], "yyyy-MM-dd");

  if (start && end) {
    // Usar router.visit con query parameters y callbacks de Inertia
    router.visit(route('sales.report'), {
      data: { start, end },
      only: ['items'],
      preserveScroll: true,
      onStart: () => {
        isLoading.value = true;
        loadingMessage.value = "Loading your report please wait...";
        console.log('Starting date range request...');
      },
      onProgress: (progress) => {
        loadingMessage.value = `Loading... ${Math.round(progress.percentage)}%`;
        console.log(`Progress: ${progress.percentage}%`);
      },
      onSuccess: (page) => {
        isLoading.value = false;
        toast.add({
          severity: 'success',
          summary: 'Success',
          detail: 'Data loaded successfully',
          life: 2000
        });
      },
      onError: (errors) => {
        isLoading.value = false;
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Error loading data',
          life: 3000
        });
        console.error('Error loading data:', errors);
      },
      onFinish: () => {
        isLoading.value = false;
        loadingMessage.value = "Loading data..."; // Reset message
      }
    });
  }
}

const onEdit = () => {
  const currentPaginate = document.getElementById("currentPaginate")?.getAttribute("data-id") || "";
  const filter = document.getElementsByClassName("filter--value")[0]?.value || "";

  document.cookie = `paginate=${currentPaginate}`;
  document.cookie = `pagefilter=${filter}`;

  let items = selectedItems.value.map((item: any) => item.id).join(";");

  router.get(route("items.edit", btoa(items)));
};

const onReturn = (items: Item | Item[]) => {
  const itemsArray = Array.isArray(items) ? items : [items];

  confirm.require({
    message: itemsArray.length > 1 ? `Are you sure you want to return ${itemsArray.length} items?` :
    `Are you sure you want to return this item?`,
    header: "Confirmation",
    icon: "pi pi-exclamation-triangle",
    accept: async () => {
      try {
        isLoading.value = true;
        loadingMessage.value = `Returning ${itemsArray.length} item(s)...`;
        
        const response = await axios.put(route("items.return"), { selectedItems: itemsArray });
        
        if (response.status >= 200 && response.status < 400) {
          toast.add({
            severity: "success",
            summary: "Success",
            detail: `${itemsArray.length} item(s) returned successfully!`,
            life: 3000,
          });
          
          handleSelection([]);
          
          // Recargar datos con callbacks de Inertia
          router.visit(route('sales.report'), {
            only: ['items'],
            preserveScroll: true,
            onStart: () => {
              loadingMessage.value = "Refreshing data...";
              console.log('Reloading after return...');
            },
            onProgress: (progress) => {
              loadingMessage.value = `Refreshing... ${Math.round(progress.percentage)}%`;
            },
            onSuccess: () => {
              isLoading.value = false;
            },
            onError: () => {
              isLoading.value = false;
              toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Error reloading data after return',
                life: 3000
              });
            },
            onFinish: () => {
              isLoading.value = false;
              loadingMessage.value = "Loading data..."; // Reset message
            }
          });
        }
      } catch (error: any) {
        isLoading.value = false;
        loadingMessage.value = "Loading data..."; // Reset message
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

onMounted(() => {
  parseItemsData();
  console.log("items:", props.items);
});

const tableActions = [
  {
    label: "Edit fields",
    icon: "pi pi-pen-to-square",
    action: () => {
      showCustomFields.value = true;
    },
  },
  {
    label: "Return items",
    icon: "pi pi-undo",
    important: true,
    disable: () => selectedItems.value.length === 0 || isLoading.value,
    action: () => {
      onReturn(selectedItems.value);
    },
  },
   {
    label: "Print Items Labels",
    icon: "pi pi-print",
    action: () => openLabels(selectedItems),
    disable: (selectedItems: Item[]) => selectedItems.length == 0 || isLoading.value,
  },
];

// Function to open the item labels in a new tab
async function openLabels(selectedItems) {
  if (selectedItems.value.length === 0 || isLoading.value) return;
  
  try {
    isLoading.value = true;
    loadingMessage.value = "Generating labels...";
    
    const itemsParam = selectedItems.value.map(item => item.id).join(',');
    const res = await axios.get(route('items.labels', { items: itemsParam }), {
      responseType: 'blob'
    });
    const blob = new Blob([res.data], { type: 'application/pdf' });
    const url  = URL.createObjectURL(blob);
    window.open(url, '_blank');
    URL.revokeObjectURL(url);
    
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Labels generated successfully',
      life: 2000
    });
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Error generating labels',
      life: 3000
    });
    console.error('Error generating labels:', error);
  } finally {
    isLoading.value = false;
    loadingMessage.value = "Loading data..."; // Reset message
  }
}
</script>
