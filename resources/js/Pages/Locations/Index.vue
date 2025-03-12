<template>
  <ConfirmDialog />
  <div>
    <section class="w-[90%] mx-auto mt-4">
      <DataTable
        title="Locations"
        @update:selected="handleSelection"
        :actions="tableActions"
        :items="tableData"
        :headers="headers"></DataTable>
    </section>
  </div>
</template>
<script lang="ts" setup>
import DataTable from "@/Components/DataTable.vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import { Location, Store } from "@/Lib/types";
import { onMounted, Ref, ref, watchEffect } from "vue";
import { router } from "@inertiajs/vue3";
import { ConfirmDialog, useConfirm, useDialog, useToast } from "primevue";
import axios from "axios";
import CreateEdit from "./Modals/CreateEdit.vue";
import SelectUsers from "./Modals/SelectUsers.vue";

const toast = useToast();
const confirm = useConfirm();
const dialog = useDialog();

const props = defineProps<{ store: Store; locations: Location[] }>();

const tableData: Ref<Location[]> = ref([]);
const selectedItems: Ref<Location[]> = ref([]);
const tableActions = ref([
  {
    label: "Create location",
    icon: "pi pi-plus",
    action: () => {
      dialog.open(CreateEdit, {
        data: {
          storeId: props.store.id,
        },
        props: {
          modal: true,
          header: "Add location",
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
    label: "Address",
    name: "address",
    type: "string",
  },
  {
    label: "Actions",
    name: "actions",
    type: "any",
  },
];

const handleSelection = (items: Location[]) => {
  selectedItems.value = items;
};

onMounted(() => {
  parseItems();
});

function parseItems() {
  tableData.value = props.locations.map((location) => ({
    ...location,
    actions: [
      {
        label: "Users",
        icon: "pi pi-users",
        action: () => {
          dialog.open(SelectUsers, {
            data: {
              id: location.id,
              getUsers: 'locations.usersList',
              updateUsers: 'locations.users'
            },
            props: {
              modal: true,
              header: "Select users"
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
              storeId: props.store.id,
              id: location.id,
              name: location.name,
              address: location.address,
            },
            props: {
              modal: true,
              header: "Edit location",
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
          onDelete(route("locations.destroy", location.id));
        },
      },
    ],
  }));
}

watchEffect(() => {
  if (tableData.value) {
    parseItems();
  }
});

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
    reject: () => {},
  });
};

defineOptions({ layout: AppLayout });
</script>
