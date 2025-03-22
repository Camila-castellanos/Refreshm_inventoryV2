<template>
  <div ref="cell" class="flex items-center w-full h-full relative border-b border-gray-300" @contextmenu.prevent="handleContextMenu">
    <span v-if="props.colIndex === 0 && props.column.columnType !== 'date'" class="text-center w-full">{{ props.rowIndex + 1 }}</span>
    <DatePicker
      v-else-if="props.column.columnType === 'date'"
      showIcon
      v-model="props.model.date"
      class="!w-full"
      dateFormat="yy-mm-dd"
      :max-date="new Date()" />
    <Select
      v-else-if="props.column.columnType === 'select'"
      v-model="model[props.prop]"
      :options="props.column.source"
      optionLabel="label"
      optionValue="value"
      class="w-full">
      <template #footer>
        <Button label="Add New" icon="pi pi-plus" class="p-button-sm w-full mt-2" @click="openAddVendorDialog" />
      </template>
    </Select>
    <span v-else class="truncate w-full px-2">{{ displayValue }}</span>
  </div>
</template>

<script lang="ts" setup>
import { defineProps, ref, computed } from "vue";
import type { ColumnDataSchemaModel } from "@revolist/vue3-datagrid";
import { DatePicker, Select, useDialog } from "primevue";
import CreateVendor from "@/Pages/Vendors/CreateVendor/CreateVendor.vue";
import fetchVendors from "@/Pages/Vendors/VendorsData";
import { Vendor } from "@/Lib/types";

const dialog = useDialog();

const props = defineProps<ColumnDataSchemaModel>();
const cell = ref<HTMLElement>();

const displayValue = computed(() => {
  return props.model[props.prop] || "";
});

function handleContextMenu(event: MouseEvent) {
  const customEvent = new CustomEvent("universal-cell-contextmenu", {
    bubbles: true,
    detail: {
      event,
      rowIndex: props.rowIndex,
      prop: props.prop,
    },
  });
  cell.value?.dispatchEvent(customEvent);
}

function openAddVendorDialog(): void {
  dialog.open(CreateVendor, {
    props: { header: "Add New Vendor", style: { width: "450px" }, modal: true },
    onClose: async () => {
      const { data } = await fetchVendors();
      props.column.source = data.map((v: Vendor) => ({ label: v.vendor, value: v.vendor }));
    },
  });
}
</script>
<style>
[role="gridcell"] {
  padding: 0 !important;
}
</style>
