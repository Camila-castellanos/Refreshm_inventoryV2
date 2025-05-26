<template>
  <div class="w-full">
  <DataTable v-model:filters="filters" :value="items" v-model:selection="selectedItems" dataKey="id" stripedRows
    ref="dt" paginator resizableColumns column-resize-mode="fit" :rows="20" :rowsPerPageOptions="[5, 10, 20, 50]"
    :globalFilterFields="headers.filter((header) => header.name !== 'actions').map((header) => header.name)"
    :class="inventory ? 'text-xs my-datatable' : 'my-datatable'" :selection-mode="selectionMode">
    <template #header>
      <div class="flex flex-col sm:flex-row sm:flex-no-wrap items-center justify-between gap-2">
        <div :class="title !== '' ? 'flex justify-center sm:justify-between items-center gap-12' + ' w-full sm:w-auto' : 'flex justify-start items-center'">
          <IconField class="w-full">
            <InputIcon>
              <i class="pi pi-search" />
            </InputIcon>
            <InputText v-model="filters['global'].value" placeholder="Search in all fields" class="w-full"/>
          </IconField>
          <slot />
        </div>
        <div class="sm:flex grid grid-cols-2 w-full mt-2 sm:mt-0 sm:flex-row gap-2 sm:gap-4  sm:w-auto">


          <Button v-for="action in computedPrimaryActions" :key="action.label"
            :severity="action.severity ? action.severity : 'primary'"
            :class="[action.extraClasses, `rounded-md !text-xs sm:!text`].join(' ')" :icon="action.icon ? action.icon : ''"
            :label="action?.label" @click="action.action(exportCSV)" :disabled="action.disable" />

          <Button v-if="computedSecondaryActions.length > 0" type="button" label="More" @click="toggle" class="col-span-2 w-full sm:min-w-48"
            icon="pi pi-angle-down" icon-pos="right" />
          <Popover ref="op">
            <div class="flex gap-4">
              <div class="max-w-96 grid grid-cols-2 gap-6">
                <Button v-for="action in computedSecondaryActions" :key="action.label"
                  :severity="action.severity ? action.severity : 'primary'"
                  :class="[action.extraClasses, `rounded-md`].join(' ')" :icon="action.icon ? action.icon : ''"
                  :label="action?.label" @click="action.action" :disabled="action.disable" />

                <Button icon="pi pi-file-export" label="Export CSV" severity="primary" @click="exportCSV" class="" />
              </div>
            </div>
          </Popover>

        </div>
      </div>
    </template>
    <template v-for="(header, index) in headers" :key="header.name">
      <Column v-if="index === 0" selectionMode="multiple" field="select" headerStyle="width: 3rem; text-align: center;"
        bodyStyle="width: 3rem; text-align: center;">
      </Column>
      <Column :field="header.name" sortable :header="header.label" v-if="header.name !== 'actions'" :style="getColumnStyle(header.name)">
        <template #body="slotProps" v-if="header.name == 'status'">
          <Tag :value="slotProps.data[header.name]"
            :severity="slotProps.data[header.name] == 'Paid' ? 'success' : 'danger'"></Tag>
        </template>
        <template #body="slotProps" v-if="header.type === 'number'">
          $ {{ slotProps.data[header.name] && slotProps.data[header.name] > 0 ?
            Number(slotProps.data[header.name]).toFixed(2) : 0 }}
        </template>
      </Column>
    </template>
    <Column header="Actions" name="actions" v-if="headers.filter((header) => header.name === 'actions').length > 0">
      <template #body="slotProps">
        <Button icon="pi pi-ellipsis-v" class="p-button-text p-0 w-8 h-8"
          @click="toggleMenu($event, slotProps.index)" />

        <!-- Menú contextual -->
        <Menu :ref="(el) => (menuRefs[slotProps.index] = el)" :model="getMenuItems(slotProps.data)" popup />
      </template>
    </Column>
    <template #footer> In total there are {{ items ? items.length : 0 }} items. </template>
    <template #empty> No data found. </template>
  </DataTable>
  </div>
</template>

<script setup lang="ts">
import { FilterMatchMode } from "@primevue/core/api";
import { IconField, InputIcon, InputText, Menu } from "primevue";
import Button from "primevue/button";
import Column from "primevue/column";
import DataTable from "primevue/datatable";
import { computed, Ref, ref, shallowRef, watch } from "vue";
import Popover from 'primevue/popover';
import { useDialog } from "primevue/usedialog";
import ExportCSV from "@/Pages/Inventory/Modals/ExportCSV.vue";
import Tag from 'primevue/tag';
import { onMounted, onBeforeUnmount, nextTick } from "vue";
//Popover actions logic
const op = ref();
const dialog = useDialog();
const toggle = (event: any) => {
  op.value.toggle(event);
}
//end Popover

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
const menuRefs: Ref<any[]> = ref([]);

const exportCSV = () => {
  if (selectedItems.value.length === 0) {

    dt.value.exportCSV();

  } else {
    dialog.open(ExportCSV, { // <-- Pass options object as the SECOND argument
      props: {
        modal: true,
        header: 'Export Options', // Good practice to add a header
        style: { width: '25rem' }, // Example style
      },
      data: {
        dt: dt,                      // Pass the REF itself
      },
    });
  }
};

const emit = defineEmits(["update:selected"]);

watch(selectedItems, (newSelection) => {
  emit("update:selected", newSelection);
});

const computedPrimaryActions = computed(() => {
  if (!props.actions || props.actions.length === 0) {
    return [];
  }

  return props.actions
    .filter(action => action.important === true)
    .map((action) => ({
      ...action,
      disable: action.disable ? action.disable(selectedItems.value) : false,
    }));
});

const computedSecondaryActions = computed(() => {
  if (!props.actions || props.actions.length === 0) {
    return [];
  }

  return props.actions
    .filter(action => !action.important)
    .map((action) => ({
      ...action,
      disable: action.disable ? action.disable(selectedItems.value) : false,
    }));
});


function toggleMenu(event: Event, index: number) {
  menuRefs.value[index]?.toggle(event);
}

function getMenuItems(data: any) {
  return data.actions.map((action: ITableActions) => ({
    label: action.label,
    icon: action.icon,
    disabled: typeof action.disable === "function" ? action.disable(selectedItems.value) : false,
    command: () => action.action(),
    class: action.extraClasses || "",
  }));
}

// adjust column widths to screen size section

function getColumnStyle(header: string) {
  switch (header) {
    case "imei":
      return { width: "130px", padding: "5px" };
    case "vendor":
      return { width: "110px", padding: "5px" };
    case "customer":
      return { width: "130px", padding: "5px" };
    default:
      return {};
  }
}

</script>

<style>

.my-datatable table {
  table-layout: fixed;
  width: 100%;
}

.my-datatable td {
  white-space: normal  !important;    
  word-break: keep-all !important;
}

@media (max-width: 767px) {
  .my-datatable table {
    table-layout: auto !important;
  }
}
</style>
