<template>
  <ConfirmDialog></ConfirmDialog>
  <div>
    <section class="w-[90%] mx-auto mt-4">
      <ContactTabs>
            <DataTable
              title="Prospects"
              @update:selected="handleSelection"
              :items="prospectTableData"
              :headers="prospectHeaders"
              :actions="prospectTableActions"
              @edit=""
              @delete="confirmDeleteProspect"></DataTable>
      </ContactTabs>
    </section>
  </div>
</template>

<script setup lang="ts">
import DataTable from "@/Components/DataTable.vue";
import axios from "axios";
import { ConfirmDialog, useConfirm, useDialog } from "primevue";
import { defineProps, onMounted, Ref, ref } from "vue";
import { prospectHeaders } from "../IndexData";
import CreateEditModal from "./CreateEditModal.vue";
import ContactTabs from "@/Components/ContactTabs.vue";
import AppLayout from "@/Layouts/AppLayout.vue";

const props = defineProps({
  prospects: Array,
});
const confirm = useConfirm();
const dialog = useDialog();

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
    disable: (selectedItems: any[]) => selectedItems.length == 0,
  },
];

let selectedItems: Ref<any[]> = ref([]);

const handleSelection = (selected: any[]) => {
  selectedItems.value = selected;
};

const prospectTableData: Ref<any[]> = ref([]);
function parseItemsData() {
  console.log(props);

  if (!props.prospects) {
    return;
  }
  prospectTableData.value = props.prospects.map((prospect: any) => {
    return {
      ...prospect,
      name: `${prospect.first_name} ${prospect.last_name}`,
      contact_type: prospect.contact_type[0].toUpperCase() + prospect.contact_type.slice(1),
      actions: [
        {
          label: "",
          icon: "pi pi-pencil",
          outlined: true,
          action: () => editProspect(prospect),
        },
        {
          label: "",
          icon: "pi pi-trash",
          severity: "danger",
          action: () => confirmDeleteProspect(prospect),
        },
      ]
    };
  });
}

const deleteProspect = (prospect: any) => {
  axios.delete(route("prospects.destroy", { prospect })).then(() => {
    window.location.reload();
  });
};

function confirmDeleteProspect(prospect: any) {
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

function editProspect(prospect: any) {
  dialog.open(CreateEditModal, {
    props: { modal: true },
    data: { prospect },
  });
}

defineOptions({ layout: AppLayout });
</script>
