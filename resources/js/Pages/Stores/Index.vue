<template>
  <div>
    <section class="w-[90%] mx-auto mt-4">
      <DataTable
        title="Stores"
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
import { Store } from "@/Lib/types";
import { onMounted, Ref, ref } from "vue";
import {router} from "@inertiajs/vue3"

const props = defineProps<{ stores: Store[] }>();

const tableData: Ref<Store[]> = ref([]);
const selectedItems: Ref<Store[]> = ref([]);
const tableActions = ref([
  {
    label: "Create store",
    icon: "pi pi-plus",
    action: () => {router.visit(route('stores.create'))},
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
    label: "Actions",
    name: "actions",
    type: "any",
  },
];

const handleSelection = (items: any[]) => {
  selectedItems.value = items;
};

onMounted(() => {
  tableData.value = props.stores.map(store => ({
    ...store,
    actions: [
      {
        label: "Users",
        icon: "pi pi-users",
        action: () => {}
      },
      {
        label: "Locations",
        icon: "pi pi-map-marker",
        action: () => {}
      },
      {
        label: "Edit",
        icon: "pi pi-pencil",
        action: () => {}
      },
      {
        label: "Delete",
        icon: "pi pi-trash",
        severity: "danger",
        action: () => {}
      }
    ]
  }))
})

defineOptions({ layout: AppLayout });
</script>
