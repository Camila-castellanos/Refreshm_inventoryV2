<template>
  <ConfirmDialog />
  <div>
    <section class="w-[90%] mx-auto mt-4">
      <DataTable title="Users" @update:selected="handleSelection" :actions="tableActions" :items="tableData" :headers="headers"></DataTable>
    </section>
  </div>
</template>
<script lang="ts" setup>
import DataTable from "@/Components/DataTable.vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import { Store, User } from "@/Lib/types";
import { onMounted, Ref, ref, watch, watchEffect } from "vue";
import { router } from "@inertiajs/vue3";
import { ConfirmDialog, useConfirm, useDialog, useToast } from "primevue";
import axios from "axios";
import SelectUsers from "../Locations/Modals/SelectUsers.vue";
import SelectRole from "./Modals/SelectRole.vue";
import CreateEdit from "./Modals/CreateEdit.vue";

const toast = useToast();
const confirm = useConfirm();
const dialog = useDialog();

const props = defineProps<{ users: User[] }>();

const tableData: Ref<User[]> = ref([]);
const selectedItems: Ref<User[]> = ref([]);
const tableActions = ref([
  {
    label: "Create user",
    icon: "pi pi-user-plus",
    action: () => {
       dialog.open(CreateEdit, {
            props: {
              modal: true,
              header: "Add user",
            },
            onClose: () => {
              router.reload();
            },
          });
    },
  },
]);

const headers = [
  {
    label: "Name",
    name: "name",
    type: "string",
  },
  {
    label: "Email",
    name: "email",
    type: "string",
  },
  {
    label: "Role",
    name: "role",
    type: "string",
  },
  {
    label: "Actions",
    name: "actions",
    type: "any",
  },
];

const handleSelection = (items: any[]) => {
  selectedItems.value = items;
};

onMounted(() => {
  parseItems();
});

watchEffect(() => {
  if (tableData.value) {
    parseItems();
  }
});

function parseItems() {
  tableData.value = props.users.map((user) => ({
    ...user,
    actions: [
      {
        label: "Change Role",
        icon: "pi pi-key",
        action: () => {
          dialog.open(SelectRole, {
            data: {
              id: user.id,
            },
            props: {
              modal: true,
              header: "Select role",
            },
            onClose: () => {
              router.reload();
            },
          });
        },
      },
      {
        label: "Edit",
        icon: "pi pi-pencil",
        action: () => {
          dialog.open(CreateEdit, {
            data: {
              userEdit: user,
            },
            props: {
              modal: true,
              header: "Edit user",
            },
            onClose: () => {
              router.reload();
            },
          });
        },
      },
      {
        label: "Delete",
        icon: "pi pi-trash",
        severity: "danger",
        action: () => {
          onDelete(route("users.destroy", user.id));
        },
      },
    ],
  }));
}

const onDelete = (url: string) => {
  confirm.require({
    message: "Are you sure? You won't be able to revert this!",
    header: "Confirmation",
    icon: "pi pi-exclamation-triangle",
    acceptClass: "p-button-danger",
    accept: async () => {
      try {
        const response = await axios.delete(url);

        if (response.status >= 200 && response.status < 400) {
          toast.add({ severity: "success", summary: "Deleted", detail: "The item has been deleted successfully.", life: 3000 });
          router.reload();
        }
      } catch (error: any) {
        let errorMessage = "An unknown error occurred.";

        if (error.response) {
          errorMessage = error.response.data;
        } else if (error.request) {
          errorMessage = "No response received from the server.";
        } else {
          errorMessage = error.message;
        }

        toast.add({ severity: "error", summary: "Error", detail: errorMessage, life: 5000 });
      }
    },
    reject: () => {
      toast.add({ severity: "info", summary: "Cancelled", detail: "Deletion cancelled.", life: 3000 });
    },
  });
};

defineOptions({ layout: AppLayout });
</script>
