<template>
  <ConfirmDialog></ConfirmDialog>
  <div>
    <section class="w-[90%] mx-auto mt-4">
      <ContactTabs>
        <DataTable
        title="Customers"
        @update:selected="handleSelection"
        :actions="tableActions"
        :items="tableData"
        :headers="headers"></DataTable>
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
import { ConfirmDialog, useConfirm } from "primevue";
import { defineProps, onMounted, ref } from "vue";
import { headers } from "./IndexData";

const props = defineProps({
  customers: Array,
  prospects: Array,
});
const confirm = useConfirm();

const tableActions = [
  {
    label: "Add customer",
    icon: "pi pi-plus",
    action: () => {
      router.visit("/contacts/customers/create");
    },
  },
];

let selectedItems = ref([]);

const handleSelection = (selected) => {
  selectedItems.value = selected;
};

const tableData = ref([]);
function parseItemsData() {
  if (!props.customers) {
    return;
  }

  tableData.value = props.customers.map((customer) => {
    return {
      ...customer,
      name: `${customer.first_name} ${customer.last_name}`,
      actions: [
        {
          label: "",
          icon: "pi pi-pencil",
          outlined: true,
          action: () => editCustomer(customer),
        },
        {
          label: "",
          icon: "pi pi-trash",
          severity: "danger",
          action: () => confirmDelete(customer),
        },
      ],
    };
  });
}

const editCustomer = (customer) => {
  router.visit(route("customer.edit", { customer }));
};

const deleteCustomer = (customer) => {
  axios.delete(route("customers.destroy", { customer })).then(() => {
    window.location.reload();
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
