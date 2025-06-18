<template>
  
  <div>
    <section class="w-[90%] mx-auto mt-4">
      <ContactTabs>
        <DataTable
          title="Customers"
          @update:selected="handleSelection"
          :actions="tableActions"
          :items="tableData"
          :headers="headers">
          <div class="w-full">
            <form class="flex flex-row justify-around" @submit.prevent="onDateRangeSubmit">
              <DatePicker
                v-model="dates"
                :max-date="new Date()"
                selectionMode="range"
                dateFormat="dd/mm/yy"
                class="w-full"
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

<script setup>
import ContactTabs from "@/Components/ContactTabs.vue";
import DataTable from "@/Components/DataTable.vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import { router } from "@inertiajs/vue3";
import axios from "axios";
import { ConfirmDialog, useConfirm, useDialog, DatePicker } from "primevue";
import { defineProps, onMounted, ref, watchEffect, watch } from "vue";
import { headers } from "./IndexData";
import CreateEdit from "./CreateEdit.vue";
import { format } from "date-fns";
import { formatPercentage } from "@/Utils/FormatUtils";
const props = defineProps({
  customers: Array,
});
const confirm = useConfirm();
const dialog = useDialog();

const tableActions = [
  {
    label: "Add customer",
    icon: "pi pi-plus",
    action: () => {
      dialog.open(CreateEdit, {
        props: {
          modal: true,
          header: "Create customer",
        },
        onClose: () => {
      router.reload();
        }
      });
    },
  },
];

let selectedItems = ref([]);

const handleSelection = (selected) => {
  selectedItems.value = selected;
};

const dates = ref([]);
async function onDateRangeSubmit() {
  // validation
  if (!dates.value || dates.value.length !== 2 || !dates.value[0] || !dates.value[1]) {
    console.error("Invalid date range selected");
    return;
  }

  // Format
  const startDate = format(dates.value[0], "y-m-d");
  const endDate = format(dates.value[1], "y-m-d");

  try {
    // sent request
    const response = await axios.post(route("customer.datewise"), { startDate, endDate });

    // transform utility
    tableData.value = transformCustomerData(response.data);

    console.log("Data successfully fetched and updated");
  } catch (error) {
    // errors 
    console.error("Error fetching data:", error);
  }
}

// utility
function transformCustomerData(data) {
  return data
    .map((customer) => ({
      ...customer,
      name: `${customer.first_name} ${customer.last_name}`,
      margin: formatPercentage(customer.margin),
      actions: [
      {
          label: "Edit",
          icon: "pi pi-pencil",
          action: () => editCustomer(customer),
        },
        {
          label: "Delete",
          icon: "pi pi-trash",
          severity: "danger",
          action: () => confirmDelete(customer.id),
        },
      ],
    }));
}

const tableData = ref([]);
function parseItemsData() {
  if (!props.customers) {
    return;
  }
  tableData.value = props.customers.map((customer) => {
    return {
      ...customer,
      name: `${customer.first_name} ${customer.last_name}`,
      margin: formatPercentage(customer.margin),
      actions: [
        {
          label: "Edit",
          icon: "pi pi-pencil",
          action: () => editCustomer(customer),
        },
        {
          label: "Delete",
          icon: "pi pi-trash",
          severity: "danger",
          action: () => confirmDelete(customer.id),
        },
      ],
    };
  });
}

const editCustomer = (customer) => {
  dialog.open(CreateEdit, {
    data: {
      customer: customer,
    },
    props: {
      modal: true,
      header: "Edit customer",
    },
    onClose: () => {
      router.reload();
    }
  });
};

const deleteCustomer = (customer) => {
  axios.delete(route("customer.destroy", { customer })).then(() => {
    router.reload();
  });
};

function confirmDelete(customer) {
  confirm.require({
    message: "Are you sure you want to delete the selected item?",
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
      deleteCustomer(customer);
    },
    reject: () => {},
  });
}



onMounted(() => {
  parseItemsData();
});

defineOptions({ layout: AppLayout });
</script>
