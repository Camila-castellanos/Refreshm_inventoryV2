<template>
  <ConfirmDialog></ConfirmDialog>
  <StoragesAssign :items="selectedItems" ref="assignStorageVisible"></StoragesAssign>
  <div>
    <section class="w-[90%] mx-auto mt-4">
      <Tabs value="0">
        <TabList>
          <Tab value="0">Customers</Tab>
          <Tab value="1">Prospects</Tab>
          <Tab value="2">Mailing list</Tab>
          <Tab value="3">Email editor</Tab>
        </TabList>
        <TabPanels>
          <TabPanel value="0">
            <DataTable
              title="Customers"
              @update:selected="handleSelection"
              :actions="tableActions"
              :items="tableData"
              :headers="headers"
              @edit="editCustomer"
              @delete="confirmDelete"></DataTable>
          </TabPanel>
          <TabPanel value="1">
            <DataTable
              title="Prospects"
              @update:selected="handleSelection"
              :items="prospectTableData"
              :headers="prospectHeaders"
              :actions="prospectTableActions"
              @edit="editProspect"
              @delete="confirmDeleteProspect"></DataTable>
          </TabPanel>
          <TabPanel value="2">
            <DataTable title="Mailing List" @update:selected="handleSelection" :items="[]" :headers="[]"></DataTable>
          </TabPanel>
          <TabPanel value="3">
            <DataTable title="Email editor" @update:selected="handleSelection" :items="[]" :headers="[]"></DataTable>
          </TabPanel>
        </TabPanels>
      </Tabs>
    </section>
  </div>
</template>

<script setup>
import DataTable from "@/Components/DataTable.vue";
import { router } from "@inertiajs/vue3";
import Tab from "primevue/tab";
import TabList from "primevue/tablist";
import TabPanel from "primevue/tabpanel";
import TabPanels from "primevue/tabpanels";
import Tabs from "primevue/tabs";
import { defineProps, onMounted, ref } from "vue";
import StoragesAssign from "../Storages/StoragesAssign/StoragesAssign.vue";
import { headers, prospectHeaders } from "./IndexData";
import AppLayout from "@/Layouts/AppLayout.vue";
import { ConfirmDialog, useConfirm, useDialog } from "primevue";
import axios from "axios";
import CreateEditModal from "./Prospects/CreateEditModal.vue";

const props = defineProps({
  customers: Array,
  prospects: Array,
});
const confirm = useConfirm();
const dialog = useDialog();

const tableActions = [
  {
    label: "Add customer",
    icon: "pi pi-plus",
    action: () => {
      router.visit("/contacts/customers/create");
    },
  },
  {
    label: "Delete customer",
    icon: "pi pi-trash",
    severity: "danger",
    action: () => {},
    disable: (selectedItems) => selectedItems.length == 0,
  },
  {
    label: "Edit customers",
    icon: "pi pi-pencil",
    action: () => {
      console.log("hi");
    },
    disable: (selectedItems) => selectedItems.length !== 1,
  },
  {
    label: "Export",
    icon: "pi pi-file-export",
    action: () => {
      console.log("hi");
    },
    disable: (selectedItems) => selectedItems.length !== 1,
  },
];

const prospectTableActions = [
  {
    label: "Add prospect",
    icon: "pi pi-plus",
    action: () => openAddProspect(),
  },
  {
    label: "Delete prospects",
    icon: "pi pi-trash",
    severity: "danger",
    action: () => {},
    disable: (selectedItems) => selectedItems.length == 0,
  },
];

const assignStorageVisible = ref(null);

let selectedItems = ref([]);

const handleSelection = (selected) => {
  selectedItems.value = selected;
};

const tableData = ref([]);
const prospectTableData = ref([]);
function parseItemsData() {
  console.log(props);
  if (!props.customers) {
    return;
  }

  tableData.value = props.customers.map((customer) => {
    return {
      ...customer,
      name: `${customer.first_name} ${customer.last_name}`,
    };
  });

  if (!props.prospects) {
    return;
  }
  prospectTableData.value = props.prospects.map((prospect) => {
    return {
      ...prospect,
      name: `${prospect.first_name} ${prospect.last_name}`,
      contact_type: prospect.contact_type[0].toUpperCase() + prospect.contact_type.slice(1),
    };
  });
}

const editCustomer = (customer) => {
  router.visit(route("customers.edit", { customer }));
};

const deleteCustomer = (customer) => {
  axios.delete(route("customers.destroy", { customer })).then(() => {
    window.location.reload();
  });
};

const deleteProspect = (prospect) => {
  axios.delete(route("prospects.destroy", { prospect })).then(() => {
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

function confirmDeleteProspect(prospect) {
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
      deleteProspect(prospect);
    },
    reject: () => {},
  });
}

onMounted(() => {
  parseItemsData();
});

function openAddProspect() {
  dialog.open(CreateEditModal, {
    props: { modal: true },
  });
}

function editProspect(prospect) {
  dialog.open(CreateEditModal, {
    props: { modal: true },
    data: { prospect },
  });
}

defineOptions({ layout: AppLayout });
</script>
