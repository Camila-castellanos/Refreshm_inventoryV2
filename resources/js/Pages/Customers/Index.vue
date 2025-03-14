<template>
  
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
import { ConfirmDialog, useConfirm, useDialog } from "primevue";
import { defineProps, onMounted, ref, watchEffect } from "vue";
import { headers } from "./IndexData";
import CreateEdit from "./CreateEdit.vue";

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
          label: "Edit",
          icon: "pi pi-pencil",
          action: () => editCustomer(customer),
        },
        {
          label: "Delete",
          icon: "pi pi-trash",
          severity: "danger",
          action: () => confirmDelete(customer),
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
  axios.delete(route("customers.destroy", { customer })).then(() => {
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

watchEffect(() => {
  if (props.customers) {
    parseItemsData();
  }
});

defineOptions({ layout: AppLayout });
</script>
