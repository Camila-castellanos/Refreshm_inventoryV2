<template>
  <div class="w-full" :class="{ 'my-datatable': hasOverflow }" ref="tableWrapper">
  <DataTable v-model:filters="filters" :value="items" v-model:selection="selectedItems" dataKey="id" stripedRows
    ref="dt" paginator resizableColumns column-resize-mode="fit" :rows="20" :rowsPerPageOptions="[5, 10, 20, 50]"
    :globalFilterFields="headers.filter((header) => header.name !== 'actions').map((header) => header.name)"
    :class="inventory ? 'text-xs' : ''" :selection-mode="selectionMode":sortField="sortField"
      :sortOrder="sortOrder">
    <template #header>
      <div class="flex flex-col sm:flex-row sm:flex-no-wrap items-center justify-between gap-2">
        <div :class="title !== '' ? 'flex flex-col gap-3 sm:flex-row justify-center sm:justify-between items-center sm:gap-12' + ' w-full sm:w-auto' : 'flex justify-start items-center'">
          <IconField class="w-full">
            <InputIcon>
              <i class="pi pi-search" />
            </InputIcon>
            <InputText v-model="filters['global'].value" placeholder="Search in all fields" class="w-full"/>
          </IconField>
          <slot />
        </div>
        <div class="sm:flex grid grid-cols-2 mt-2 w-full sm:mt-0 sm:flex-row gap-2 sm:gap-4  sm:w-auto">


          <Button v-for="action in computedPrimaryActions" :key="action.label"
            :severity="action.severity ? action.severity : 'primary'"
            :class="[action.extraClasses, `rounded-md !text-xs sm:!text-base`].join(' ')" :icon="action.icon ? action.icon : ''"
            :label="action?.label" @click="action.action()" :disabled="action.disable" />

          <Button v-if="computedSecondaryActions.length > 0" type="button" label="More" @click="toggle" class="col-span-2 w-full sm:w-auto sm:min-w-48"
            icon="pi pi-angle-down" icon-pos="right" />
          <Popover ref="op">
            <div class="flex gap-4">
              <div class="max-w-96 grid grid-cols-2 gap-6">
                <Button v-for="action in computedSecondaryActions" :key="action.label"
                  :severity="action.severity ? action.severity : 'primary'"
                  :class="[action.extraClasses, `rounded-md`].join(' ')" :icon="action.icon ? action.icon : ''"
                  :label="action?.label" @click="action.action" :disabled="action.disable" />
              </div>
            </div>
          </Popover>

        </div>
      </div>
    </template>
    <template v-for="(header, index) in headers" :key="header.name">
      <Column v-if="index === 0" selectionMode="multiple" field="select" headerStyle="text-align: left;"
        bodyStyle=" text-align: left;" style="width: 3rem;">
      </Column>
      <Column :field="header.name" sortable :header="header.label" v-if="header.name !== 'actions'" :style="getColumnStyle(header.name)">
        <template #body="slotProps" v-if="header.name == 'status'">
          <Tag :value="slotProps.data[header.name]"
            :severity="slotProps.data[header.name] == 'Paid' ? 'success' : 'danger'"></Tag>
        </template>
        <template #body="slotProps" v-if="header.type === 'number'">
          {{ formatCurrency(slotProps.data[header.name]) }}
        </template>
        <!-- Formatear campo de tipo date para mostrar solo la fecha -->
        <template #body="slotProps" v-if="header.type === 'date'">
          {{ formatDate(slotProps.data[header.name]) }}
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
  sortField?: string;
  sortOrder?: number;
  selected?: any[];
}>();

const emit = defineEmits<{
  (e: "update:selected", value: any[]): void
  (e: "search-back"): void
}>();

const selectionMode = ref(props?.selectionMode ?? "multiple");

const dt = ref();
const selectedItems = ref<any[]>(props.selected || []);
const menuRefs: Ref<any[]> = ref([]);
const tableWrapper = ref<HTMLElement|null>(null)
const hasOverflow    = ref(false)

watch(() => props.selected, (newVal) => {
  if (newVal !== undefined) {
    selectedItems.value = newVal;
  }
});

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

watch(selectedItems, (newSelection) => {
  emit("update:selected", newSelection);
});

// Helper that returns all actions (props + exportAction)
function getAllActions(): ITableActions[] {
  const base = props.actions ?? []
  const exportAction: ITableActions = {
  icon: 'pi pi-file-export',
  label: 'Export CSV',
  severity: 'primary',
  action: exportCSV,
  extraClasses: '',
  // opcionalmente: important: false
}
  return [...base, exportAction]
}

// helper that returns the primary actions (those that are important or the first 5)
function getPrimaryList(all: ITableActions[]) {
  // explicit important actions first
  const important = all.filter(a => a.important === true)
  const primary = [...important]

  // then add the next actions until we reach 5
  if (primary.length < 5) {
    const needed = 5 - primary.length
    primary.push(...all.filter(a => !a.important).slice(0, needed))
  }
  return primary
}

// computed properties for primary and secondary actions
const computedPrimaryActions = computed(() => {
  const all = getAllActions()
  const primary = getPrimaryList(all)
  return primary.map(a => ({
    ...a,
    disable: a.disable ? a.disable(selectedItems.value) : false,
  }))
})

const computedSecondaryActions = computed(() => {
  const all = getAllActions()
  const primary = getPrimaryList(all)
  return all
    .filter(a => !primary.includes(a))
    .map(a => ({
      ...a,
      disable: a.disable ? a.disable(selectedItems.value) : false,
    }))
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

onMounted(() => {
  nextTick(() => {
    checkOverflow();
    console.log("hasOverflow", hasOverflow.value)
    // window.addEventListener('resize', checkOverflow)
  });
  onBeforeUnmount(() => {
    window.removeEventListener('resize', checkOverflow)
  });
});


//utilities

// adjust column widths to screen size section

function getColumnStyle(header: string) {
  switch (header) {
    case "imei":
      return { width: "130px", padding: "5px" };
    case "vendor":
      return { width: "120px", padding: "5px" };
    case "customer":
      return { width: "130px", padding: "5px" };
    default:
      return {};
  }
}
function formatCurrency(value: number | string | undefined): string {
  const num = Number(value)
  if (isNaN(num) || num <= 0) return '$ 0.00'
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(num).replace(/^/, '$ ')
}

// Función auxiliar para formatear fechas y eliminar hora
function formatDate(value: string | null | undefined): string {
  if (!value) return '';
  // Si el valor es tipo ISO, separa por 'T' y toma la primera parte
  if (value.includes('T')) {
    return value.split('T')[0];
  }
  // Si el valor tiene espacio, separa por espacio y toma la primera parte
  return value.split(' ')[0];
}

function checkOverflow() {
  const tbl = tableWrapper.value?.querySelector('table')
  if (tbl instanceof HTMLTableElement) {
    const tableWidth = tbl.getBoundingClientRect().width
    const wrapperWidth = tableWrapper.value?.clientWidth ?? 0
    hasOverflow.value = tableWidth > wrapperWidth
  }
}

// watch for changes on the search filter, if there is not results with the actual data dispatch the fetch event to father
// has debounce
let debounceTimeout: ReturnType<typeof setTimeout> | null = null;
watch(
  () => filters.value.global.value,
  () => {
   if (debounceTimeout) clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
      nextTick(() => {
        if (dt.value.processedData.length < 1) {
         emit('search-back', filters.value.global.value)
        }
      });
    }, 700); // 700ms
  }
);

</script>

<style>


/* fixed layout test */
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
