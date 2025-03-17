<template>
  <DataTable
    v-model:filters="filters"
    :value="items"
    v-model:selection="selectedItems"
    dataKey="id"
    stripedRows
    ref="dt"
    paginator
    scrollable
    scrollHeight="900px"
    :rows="20"
    :rowsPerPageOptions="[5, 10, 20, 50]"
    filterDisplay="menu"
    :globalFilterFields="headers.filter((header) => header.name !== 'actions').map((header) => header.name)"
    :class="inventory ? 'text-sm' : ''"
    :selection-mode="selectionMode">
    <template #header>
      <div class="flex flex-no-wrap items-center justify-between gap-2">
        <div :class="title !== '' ? 'flex justify-between items-center gap-12' : 'flex justify-start items-center'">
          <span class="text-xl font-bold" v-show="title !== ''">{{ title }}</span>
          <IconField>
            <InputIcon>
              <i class="pi pi-search" />
            </InputIcon>
            <InputText v-model="filters['global'].value" placeholder="Search in all fields" />
          </IconField>
          <slot />
        </div>
        <div class="flex flex-no-wrap overflow-x-auto w-auto max-w-[900px] gap-8 whitespace-nowrap">
          <Button
            v-for="action in computedActions"
            :key="action.label"
            :severity="action.severity ? action.severity : 'primary'"
            :class="[action.extraClasses, `rounded-md ${action.label.length > 0 ? 'min-w-fit' : 'min-w-[40px]'}`].join(' ')"
            :icon="action.icon ? action.icon : ''"
            :label="action?.label"
            @click="action.action"
            :disabled="action.disable" />

          <Button icon="pi pi-file-export" label="Export CSV" severity="primary" @click="exportCSV" class="min-w-[150px]" />
        </div>
      </div>
    </template>
    <template v-for="(header, index) in headers" :key="header.name">
      <Column
        v-if="index === 0"
        selectionMode="multiple"
        field="select"
        headerStyle="width: 3rem; text-align: center;"
        bodyStyle="width: 3rem; text-align: center;">
      </Column>
      <Column :field="header.name" sortable :header="header.label" v-if="header.name !== 'actions'">
        <template #body="slotProps" v-if="header.type === 'number'"> $ {{ slotProps.data[header.name] && slotProps.data[header.name] > 0 ? Number(slotProps.data[header.name]).toFixed(2) : 0 }} </template>
      </Column>
    </template>
    <Column header="Actions" name="actions" v-if="headers.filter((header) => header.name === 'actions').length > 0">
      <template #body="slotProps">
        <div class="flex gap-2">
          <Button
            v-for="action in slotProps.data.actions"
            :key="action.label"
            :severity="action.severity ? action.severity : 'primary'"
            :class="[action.extraClasses, 'px-4 py-2 rounded-md'].join(' ')"
            :icon="action.icon ? action.icon : ''"
            v-tooltip.bottom="action.label + ' '"
            :outlined="action?.outlined ?? false"
            :raised="action?.outlined ?? false"
            @click="() => action.action(slotProps.data)"
            :disabled="typeof action.disable === 'function' ? action.disable() : false"/> 
        </div>
      </template>
    </Column>
    <template #footer> In total there are {{ items ? items.length : 0 }} items. </template>
    <template #empty> No data found. </template>
  </DataTable>
</template>

<script setup lang="ts">
import { FilterMatchMode } from "@primevue/core/api";
import { IconField, InputIcon, InputText } from "primevue";
import Button from "primevue/button";
import Column from "primevue/column";
import DataTable from "primevue/datatable";
import { computed, ref, watch } from "vue";

const filters = ref({
  global: { value: null, matchMode: FilterMatchMode.CONTAINS },
  name: { value: null, matchMode: FilterMatchMode.STARTS_WITH },
  "country.name": { value: null, matchMode: FilterMatchMode.STARTS_WITH },
  representative: { value: null, matchMode: FilterMatchMode.IN },
  status: { value: null, matchMode: FilterMatchMode.EQUALS },
  verified: { value: null, matchMode: FilterMatchMode.EQUALS },
});

export interface ITableHeaders {
  label: string;
  name: string;
  type: string;
}

export interface ITableActions {
  extraClasses?: string;
  icon?: string;
  label: string;
  severity?: string;
  outlined?: boolean;
  action: () => void;
  disable?: (selectedItems: any[]) => boolean;
}

const props = defineProps<{
  title: string;
  headers: ITableHeaders[];
  items: any[];
  actions?: ITableActions[];
  selectionMode?: "single" | "multiple";
  inventory?: boolean;
}>();

const selectionMode = ref(props?.selectionMode ?? "multiple");

const dt = ref();
const selectedItems = ref<any[]>([]);

const exportCSV = () => {
  dt.value.exportCSV();
};

const emit = defineEmits(["update:selected"]);

watch(selectedItems, (newSelection) => {
  emit("update:selected", newSelection);
});

const computedActions = computed(() => {
  return props.actions?.map((action) => ({
    ...action,
    disable: action.disable ? action.disable(selectedItems.value) : false,
  }));
});
</script>
