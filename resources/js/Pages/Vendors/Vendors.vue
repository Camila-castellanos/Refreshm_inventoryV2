<template>
  <div>
    <section class="w-[90%] mx-auto mt-4">
      <ContactTabs>
            <DataTable
              title="Vendors"
              @update:selected="handleSelection"
              :items="vendorTableData"
              :headers="VendorHeaders"
              :actions="tableActions"
              @edit=""
              @delete="confirmDeleteVendor">
              <div class="w-full">
            <form class="flex flex-row justify-around" @submit.prevent="onDateRangeSubmit">
              <DatePicker
                v-model="dates"
                :max-date="new Date()"
                selectionMode="range"
                dateFormat="dd/mm/yy"
                class="w-56"
                id="date"
                placeholder="Date range for calculations"></DatePicker>
              <Button label="Update" class="mx-2" size="large" type="submit" />
            </form>
          </div>
            </DataTable>
      </ContactTabs>
    </section>
  </div>

</template>

<script setup lang="ts">
import ContactTabs from "@/Components/ContactTabs.vue";
import DataTable, { ITableActions } from "@/Components/DataTable.vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import { Vendor } from "@/Lib/types";
import { router, useForm } from "@inertiajs/vue3";
import axios from "axios";
import { ConfirmDialog, Dialog, InputText, Textarea, useConfirm } from "primevue";
import Dropdown from "primevue/dropdown";
import { useToast } from "primevue/usetoast";
import { onMounted, ref, Ref, watchEffect } from "vue";
import { VendorHeaders } from "./VendorsData";
import CreateEdit from "./CreateEdit.vue";
import { useDialog, DatePicker } from "primevue";
import { format } from "date-fns";
import { formatPercentage } from "@/Utils/FormatUtils";

const dialog = useDialog();

const toast = useToast();
const confirm = useConfirm();

const props = defineProps({ vendors: Array<Vendor> });



const selectedVendors: Ref<Vendor[]> = ref([]);



// function editVendor(vendor: Vendor) {
//   formType.value = "Edit";
//   editVendorId.value = vendor.id;
//   form.defaults(vendor);
//   form.reset();
//   showCreateModal.value = true;
// }

const handleSelection = (selected: Vendor[]) => {
  selectedVendors.value = selected;
};

// const vendorList: Ref<Vendor[]> = ref([]);
const vendorTableData: Ref<any[]> = ref([]);
const parseItemsData = () => {
  vendorTableData.value =
    props.vendors?.map((vendor) => ({
      ...vendor,
      margin: formatPercentage(vendor.margin),
      actions: [
        {
          label: "Edit",
          icon: "pi pi-pencil",
          action: () => editVendor(vendor),
        },
        {
          label: "Delete",
          icon: "pi pi-trash",
          severity: "danger",
          action: () => confirmDeleteVendor(vendor),
        },
      ],
    })) || [];
}


onMounted(() => {
  console.log("vendors in props: ", props.vendors);
  parseItemsData();
  console.log("vendorTableData after parse: ", vendorTableData.value);
});

watchEffect(() => {
  if (props.vendors) {
    parseItemsData();
  }
});

const dates: Ref<Date | Date[] | (Date | null)[] | null | undefined> = ref([]);
async function onDateRangeSubmit(): Promise<void> {
  // validation
  if (!dates.value || dates.value.length !== 2 || !dates.value[0] || !dates.value[1]) {
    console.error("Invalid date range selected");
    return;
  }

  // Format
  const startDate: string = format(dates.value[0], "y-M-d");
  const endDate: string = format(dates.value[1], "y-M-d");
  try {
    // sent request
    const response = await axios.post(route("vendor.datewise"), { startDate, endDate });

    // transform utility
    vendorTableData.value = transformVendorData(response.data);

    console.log("Data successfully fetched and updated");
  } catch (error) {
    // errors 
    console.error("Error fetching data:", error);
  }
}

// utility
function transformVendorData(data: Vendor[]): any[] {
  return data.map((vendor: Vendor) => ({
    ...vendor,
    margin: formatPercentage(vendor.margin),
    actions: [
      {
        label: "Edit",
        icon: "pi pi-pencil",
        action: () => editVendor(vendor),
      },
      {
        label: "Delete",
        icon: "pi pi-trash",
        severity: "danger",
        action: () => confirmDeleteVendor(vendor),
      },
    ],
  }));
};

const tableActions: ITableActions[] = [
  {
    label: "New Vendor",
    icon: "pi pi-plus",
    action: () => openAddVendor()
  },
  {
    label: "Delete vendors",
    icon: "pi pi-trash",
    severity: "danger",
    action: () => {
      confirmDeleteVendors();
    },
    disable: (selectedItems) => selectedItems.length == 0,
  },
];

function confirmDeleteVendor(vendor: any) {
  confirm.require({
    message: "Are you sure you want to delete the selected items?",
    header: "Confirmation",
    icon: "pi pi-exclamation-triangle",
    rejectProps: {
      label: "Cancel",
      severity: "secondary",
      outlined: true,
    },
    acceptProps: {
      label: "Save",
    },
    accept: () => {
      deleteVendor(vendor);
    },
    reject: () => {},
  });
}


function confirmDeleteVendors() {
  confirm.require({
    message: "Are you sure you want to delete the selected items?",
    header: "Confirmation",
    icon: "pi pi-exclamation-triangle",
    rejectProps: {
      label: "Cancel",
      severity: "secondary",
      outlined: true,
    },
    acceptProps: {
      label: "Save",
    },
    accept: () => {
      destroyVendors();
    },
    reject: () => {},
  });
}

const deleteVendor = (vendor: Vendor) => {
  axios.delete(route("vendor.destroy", { vendor: vendor.id })).then(() => {
    router.reload();
  });
};

async function destroyVendors() {
  try {
    // Ejecutamos todas las peticiones en paralelo
    await Promise.all(selectedVendors.value.map((vendor) => axios.delete(route("vendor.destroy", { vendor: vendor.id }))));

    // Mostramos la notificación de éxito
    toast.add({
      severity: "success",
      summary: "Items deleted",
      detail: "Vendors deleted successfully",
      life: 1500,
    });
    router.reload({ only: ["vendors"] });
  } catch (err) {
    // Mostramos error solo si alguna petición falla
    toast.add({
      severity: "error",
      summary: "Error",
      detail: "Failed to delete some vendors",
      life: 1500,
    });
    console.error("Error deleting vendors:", err);
  }
}

function openAddVendor() {
  dialog.open(CreateEdit, {
    props: { modal: true },
    onClose: () => {
      router.reload();
    }
  });
}

function editVendor(vendor: any) {
  dialog.open(CreateEdit, {
    props: { modal: true },
    data: { vendor },
    onClose: () => {
      router.reload();
    }
  });
}

defineOptions({ layout: AppLayout });
</script>
