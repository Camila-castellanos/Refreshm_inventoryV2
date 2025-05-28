<template>
  <Toast></Toast>
  <section class="flex flex-col w-full px-4 relative">
    <section>
      <div class="flex flex-row justify-between" id="spreadsheet-header">
        <div id="spreadsheet-buttons">
      <Button :loading="isLoading" :disabled="isLoading" v-if="!props.initialData?.length" @click="createDevices">Save
        devices</Button>
      <Button :loading="isLoading" :disabled="isLoading" v-else @click="editDevices">Update devices</Button>
      <Button
          icon="pi pi-print"
          class="ml-2 !w-fit !h-fit !px-2"
          @click="openLabelsFromTable"
          :disabled="
            tableData.length < 1 ||
            (tableData.length === 1 && isEmptyRow(tableData[0]))
          "
        >
          Print Unsaved Items Labels
        </Button>
  </div>
         <!-- Global data section for all items-->
        <div id="spreadsheet-global-data" class="flex flex-row gap-4">
            <!-- Vendors Dropdown -->
          <Select
            v-model="selectedVendor"
            :options="vendorOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="Select Vendor"
            :disabled="isLoading"
            class="w-60"
          />
    
          <!-- Date Picker -->
          <DatePicker
            v-model="selectedDate"
            dateFormat="yy-mm-dd"
            placeholder="Select Date"
            show-icon
            :disabled="isLoading"
            class="w-50"
          />

           <!-- Tax Dropdown -->
           <Select
            v-model="selectedTax"
            :options="taxOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="Select Tax"
            :disabled="isLoading"
            class="w-60"
          >
          <template #footer>
        <div class="p-3">
            <Button label="Add New Tax" fluid severity="secondary" text size="small" icon="pi pi-plus" @click="showTaxDialog = true" />
        </div>
          </template>
        </Select>
    </div>
 </div>
    </section>

    <div class="spreadsheet-wrapper mt-8 w-full" ref="wrapper">
      <RevoGrid ref="revogrid" :columns="columns" :source="tableData" :columnTypes="columnTypes" :canFocus="!isLoading"
        :range="true" class="h-[80vh] w-full" :resize="true" theme="default" @beforeedit="handleBeforeEdit"
        @beforeRowAdd="onInsertRow" @universal-cell-contextmenu="showContextMenu" @beforeRowDelete="onDeleteRow"
        :clipboard="{ copyable: true, pasteable: true }" 
        row-key="location"/>

      <ContextMenu ref="menuRef" :model="menuItems" />
    </div>
  </section>
  <CreateTax
    v-model:visible="showTaxDialog"
    @created="handleTaxCreated"/>
</template>

<script lang="ts" setup>
import { Item, Storage, Vendor } from "@/Lib/types";
import { fetchStorages } from "@/Pages/Storages/StoragesIndexData";
import fetchVendors from "@/Pages/Vendors/VendorsData";
import { router } from "@inertiajs/vue3";
import DateTypePlugin from "@revolist/revogrid-column-date";
import NumberTypePlugin from "@revolist/revogrid-column-numeral";
import SelectTypePlugin from "@revolist/revogrid-column-select";
import RevoGrid, { BeforeSaveDataDetails, RevoGridCustomEvent, VGridVueTemplate, type ColumnRegular } from "@revolist/vue3-datagrid";
import axios from "axios";
import { Button, ContextMenu, useDialog, useToast, Select, DatePicker } from "primevue";
import { useConfirm } from "primevue/useconfirm";
import { nextTick, onMounted, ref, onBeforeUnmount, watch } from "vue";
import UniversalCell from "./UniversalCell.vue";
import { format } from "date-fns";
import CreateTax from "@/Pages/Accounting/Modals/CreateTax.vue";

type VendorOption = { label: string; value: string };
type ContextMenu = { visible: boolean; x: number; y: number; row: number | null };

type ItemWithLocation = Item & { location: string };

const props = defineProps<{ initialData?: Item[] }>();

const confirm = useConfirm();
const toast = useToast();
const revogrid = ref<InstanceType<typeof RevoGrid> | null>(null);
const storagesList = ref<any[]>([]);
const vendorsList = ref<any[]>([]);
const vendorOptions = ref<VendorOption[]>([]);
const tableData = ref<ItemWithLocation[]>([]);
const isLoading = ref(false);
const selectedVendor = ref<string | null>(null);
const selectedDate = ref<Date | null>(null);
const taxOptions = ref([]);
const selectedTax = ref<string | null>(null);
const showTaxDialog = ref(false);

const menuItems = [
  { label: "Insert Row Above", command: () => insertRow("above") },
  { label: "Insert Row Below", command: () => insertRow("below") },
  { label: "Insert 50 Rows Below", command: () => insertRow("bulk") },
  { separator: true },
  { label: "Delete Row", icon: "pi pi-trash", class: "text-red-600", command: () => deleteRow() },
];

const contextRow = ref<number | null>(null);
const menuRef = ref<any>(null);
const wrapper = ref<HTMLElement|null>(null);
const columns = ref<ColumnRegular[]>([
  {
    prop: "id",
    name: '',
    size: 50,
    readonly: true,
    cellTemplate: VGridVueTemplate(UniversalCell),
  },
  
  
  // { prop: "date", name: "Date", columnType: "date", size: 200, cellTemplate: VGridVueTemplate(UniversalCell) },
  // {
  //   prop: "vendor",
  //   name: "Vendor",
  //   size: 200,
  //   columnType: "select",
  //   source: [...vendorOptions.value],
  //   labelKey: "label",
  //   valueKey: "value",
  //   cellTemplate: VGridVueTemplate(UniversalCell),
  // },
  { prop: "manufacturer", name: "Manufacturer", size: 200, cellTemplate: VGridVueTemplate(UniversalCell) },
  { prop: "model", name: "Model", size: 150, cellTemplate: VGridVueTemplate(UniversalCell) },
  { prop: "colour", name: "Colour", size: 150, cellTemplate: VGridVueTemplate(UniversalCell) },
  { prop: "battery", name: "Battery", size: 80, cellTemplate: VGridVueTemplate(UniversalCell) },
  { prop: "grade", name: "Grade", size: 80, cellTemplate: VGridVueTemplate(UniversalCell) },
  { prop: "issues", name: "Issues", size: 150, cellTemplate: VGridVueTemplate(UniversalCell) },
  { prop: "imei", name: "IMEI", size: 150, cellTemplate: VGridVueTemplate(UniversalCell) },
  { prop: "location", name: "Location", size: 100, readonly: true, cellTemplate: VGridVueTemplate(UniversalCell) },
  { prop: "selling_price", name: "Selling Price", columnType: "number", size: 120, cellTemplate: VGridVueTemplate(UniversalCell) },
  { prop: "cost", name: "Subtotal", columnType: "number", size: 120, cellTemplate: VGridVueTemplate(UniversalCell) },
  { prop: "total", name: "Total", columnType: "number", size: 120, cellTemplate: VGridVueTemplate(UniversalCell) },
]);

const columnTypes = ref<any>({
  date: new DateTypePlugin({ format: "yyyy-MM-dd" }),
  select: new SelectTypePlugin(),
  number: new NumberTypePlugin({ format: "#,##0.00" }),
});

onMounted(async () => {
  const [storages, vendors] = await Promise.all([fetchStorages(), fetchVendors()]);
  storagesList.value = storages.data;
  vendorsList.value = vendors.data;
  vendorOptions.value = vendors.data.map((v: Vendor) => ({ label: v.vendor, value: v.vendor }));
  taxOptions.value = await getUserTaxes();
  columns.value = columns.value.map((col) => {
    if (col.prop === "vendor") {
      return {
        ...col,
        columnType: "select",
        source: [...vendorOptions.value],
        labelKey: "label",
        valueKey: "value",
      };
    }
    return col;
  });
   initialColumns.value = columns.value.map(col => ({ ...col }))
   await adjustColumnSizes();
  window.addEventListener('resize', adjustColumnSizes);
  window.addEventListener('paste', handleGlobalPaste);
  window.addEventListener('keydown', onKeyDown, {capture: true});

  tableData.value = props.initialData?.length
    ? props.initialData.map((item) => {
      const storage = storages.data.find((s: Storage) => s.id === item.storage_id);
      console.log(item)
      return {
        ...item,
        location: `${storage?.name} - ${item.position}/${storage?.limit}`,
        vendor: vendors.data.find((v: Vendor) => v.id === item.vendor_id)?.vendor || "",
        date: format(item.date, "yyyy-MM-dd"),
      };
    })
    : [{}] as ItemWithLocation[];

  if ((props.initialData?.length ?? 0) > 0 && tableData.value[0].tax != null) {
    selectedTax.value = tableData.value[0].tax;
  }  

  await nextTick();
  if (!props.initialData?.length) renderPositions(1);
});

onBeforeUnmount(() => {
  window.removeEventListener('resize', adjustColumnSizes);
  window.removeEventListener('paste', handleGlobalPaste);
  window.removeEventListener('keydown', onKeyDown, {capture: true});
});

async function getUserTaxes() {
    try {
      let taxes;
      const { data } = await axios.get(route('tax.list'));
      taxes = data.map((tax: any) => ({
        label: `${tax.name || 'N/A'} - (${tax.percentage + '%' || 'N/A'})`,
        value: tax.id,
      }));
      console.log("Taxes loaded:", taxes);
      return taxes;
    } catch (err) {
      console.error('Failed to load taxes:', err);
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: 'Could not fetch tax list',
        life: 3000,
      });
    }
  }

function showContextMenu(e: CustomEvent<{ event: MouseEvent; rowIndex: number; prop: string }>) {
  e.preventDefault();
  if (props.initialData?.length) return;
  contextRow.value = e.detail.rowIndex;
  menuRef.value.show(e.detail.event);
}

function insertRow(position: "above" | "below" | "bulk") {
  const rowIndex = contextRow.value ?? 0;
  if (position === "above") {
    tableData.value = [...tableData.value.slice(0, rowIndex), {} as any, ...tableData.value.slice(rowIndex)];
    renderPositions(1);
  }

  if (position === "below") {
    tableData.value = [...tableData.value.slice(0, rowIndex + 1), {} as any, ...tableData.value.slice(rowIndex + 1)];
    renderPositions(1);
  }

  if (position === "bulk") {
    const bulk: any[] = Array.from({ length: 50 }, () => ({}));
    tableData.value = [...tableData.value.slice(0, rowIndex + 1), ...bulk, ...tableData.value.slice(rowIndex + 1)];
    renderPositions(50);
  }
}

function deleteRow() {
  const rowIndex = contextRow.value ?? 0;
  if (tableData.value.length > 1) {
    tableData.value = [...tableData.value.slice(0, rowIndex), ...tableData.value.slice(rowIndex + 1)];
  }
}

function onInsertRow(e: CustomEvent<{ records: ItemWithLocation[] }>): void {
  const rows = e?.detail?.records?.length || 1;

  renderPositions(rows);
}

function onDeleteRow(e: CustomEvent<{ count: number }>): void {
  console.log(e.detail);
  const rows = e?.detail?.count || 1;
  tableData.value = tableData.value.filter((row, index) => index !== rows);
  renderPositions(rows);
}

function renderPositions(numOfRows: number): void {
  const newRowsToAssign = tableData.value.filter(row => !row.location);
  let rowsAssigned = 0;
  for (const row of newRowsToAssign) {
    if (row.location) continue; // Skip if already assigned in a previous iteration

    for (const storage of storagesList.value) {
      const dbPositions = storage.items.map((item: Item) => item.position);
      const inTablePositions: number[] = [];
      tableData.value.forEach((r) => {
        if (r.location?.startsWith(storage.name) && r !== row) { // Exclude the current row being assigned
          const parts = r.location.split(" - ")[1]?.split(" / ");
          if (parts && parts[0]) {
            inTablePositions.push(parseInt(parts[0]));
          }
        }
      });

      const occupiedPositions = [...dbPositions, ...inTablePositions];
      const availablePositions: number[] = [];
      for (let i = 1; i <= storage.limit; i++) {
        if (!occupiedPositions.includes(i)) {
          availablePositions.push(i);
        }
      }

      if (availablePositions.length > 0) {
        row.location = `${storage.name} - ${availablePositions[0]} / ${storage.limit}`;
        rowsAssigned++;
        break; // Move to the next new row
      }
    }

    if (!row.location) {
      console.warn("Could not find storage for a new device:", row);
    }
  }

  if (rowsAssigned < numOfRows) {
    toast.add({ severity: "error", summary: "Error", detail: "Not enough combined storage capacity for all new devices.", life: 5000 });
  }
}

function handleBeforeEdit(e: RevoGridCustomEvent<BeforeSaveDataDetails>): void {
  const { prop, rowIndex } = e.detail;
  if (prop === "location") {
    e.preventDefault(); // evita cambios manuales en 'location'
  }
}

function createDevices(): void {
  confirm.require({
    message: `Are you sure you want to add these devices?`,
    header: "Confirmation",
    icon: "pi pi-exclamation-triangle",
    rejectProps: { label: "Cancel", severity: "secondary", outlined: true },
    acceptProps: { label: "Save" },
    accept: () => submitSpreadsheet(mapSpreadsheetData(tableData.value)),
  });
}

function editDevices(): void {
  isLoading.value = true;
  const formattedData = mapSpreadsheetData(tableData.value);
  axios
    .post(route("items.update"), { items: formattedData }, { responseType: "blob" })
    .then(() => {
      isLoading.value = false;
      toast.add({ severity: "success", summary: "Success", detail: "Devices updated successfully", life: 3000 });
      router.visit("/inventory/items", { only: ["items", "tabs"] });
    })
    .catch(() => {
      toast.add({ severity: "error", summary: "Error", detail: "Something went wrong!", life: 3000 });
    });
}

function mapSpreadsheetData(data: ItemWithLocation[]): any[] {
  return data.map((row) => {
    const storageId = storagesList.value.find((s) => s.name === row.location?.split("-")[0].trim())?.id;
    const vendorId = vendorsList.value.find((v) => v.vendor === selectedVendor.value)?.id;
    let cost = row.cost;
    let selling_price = row.selling_price;

    if (cost?.toString().startsWith("$")) {
      cost = Number(cost.toString().slice(1));
    }
    if (selling_price?.toString().startsWith("$")) {
      selling_price = Number(selling_price.toString().slice(1));
    }
    const date = selectedDate.value
     ? format(selectedDate.value, "yyyy-MM-dd")
     : format(new Date(), "yyyy-MM-dd");

    return { ...row, storage_id: storageId, vendor_id: vendorId, date: date, cost, selling_price,  tax: selectedTax.value };
  });
}

async function submitSpreadsheet(body: any[]): Promise<void> {
  try {
    isLoading.value = true;
    await axios.post("/inventory/items", { items: body }, { responseType: "blob" });
    isLoading.value = false;
    toast.add({ severity: "success", summary: "Success", detail: "Devices created successfully", life: 3000 });
    router.visit("/inventory/items", { only: ["items", "tabs"] });
  } catch (err) {
    toast.add({ severity: "error", summary: "Error", detail: "Something went wrong", life: 3000 });
  }
}
// adjust column sizes to screen size
const initialColumns = ref<ColumnRegular[]>([])
async function adjustColumnSizes() {
  await nextTick();
  if (!wrapper.value) return;
  
  const totalWidth = wrapper.value.clientWidth;

  // si es móvil, volvemos a las columnas iniciales
  if (totalWidth < 768) {
    console.log("mobile", initialColumns.value);
    columns.value = initialColumns.value.map(col => ({ ...col }))
    return
  }

  const count = columns.value.length;
  const baseSize = Math.floor(totalWidth / count);
  const used = baseSize * count;
  const remainder = totalWidth - used;
  columns.value = columns.value.map((col, i) => ({
    ...col,
    size: i === count - 1 ? baseSize + remainder : baseSize
  }));
}
// handle paste event globally

const pasteOrder = [
  "manufacturer",
  "model",
  "colour",
  "battery",
  "grade",
  "issues",
  "cost",
  "imei",
];

// helper to check if a row is "empty"
function isEmptyRow(row: Record<string, any>): boolean {
  return pasteOrder.every(prop => {
    const v = row[prop]
    return v === undefined || v === null || v === ''
  })
}

function handleGlobalPaste(e: ClipboardEvent) {

  const activeFocusElement = document.activeElement as HTMLElement | null;
  // Si el foco está en un INPUT, TEXTAREA un elemento contentEditable o en una celda de la tabla, no interceptamos el evento de pegado
  // dejamos que el pegado se comporte por defecto en esa celda
  if (
    activeFocusElement &&
    (
      activeFocusElement.tagName === 'INPUT' ||
      activeFocusElement.tagName === 'TEXTAREA' ||
      activeFocusElement.isContentEditable ||
      activeFocusElement.getAttribute('role') === 'gridcell' ||
      activeFocusElement.classList.contains('rgCell')
    )
  ) {
    return;
  }
  // Si no, interceptamos el evento de pegado
  e.preventDefault()
  const text = e.clipboardData?.getData('text/plain') || ''
  const lines = text.split(/\r?\n/).filter(line => line.trim() !== '')
  if (!lines.length) return

  const newRows = lines.map(line => {
    const values = line.split('\t')
    const row: any = {}

    // assign values to the row based on the pasteOrder
     pasteOrder.forEach((prop, i) => {
      row[prop] = values[i] ?? "";
    });
    return row
  })
  if(isEmptyRow(tableData.value[0])) {
    tableData.value = newRows
  } else {
    tableData.value = [...tableData.value, ...newRows]
  }
  renderPositions(newRows.length)
}

// history system section
// history array
const history = ref<ItemWithLocation[][]>([])
// max history size
const MAX_HISTORY = 50
let skipSnapshot = false
// save a snapshot of the current table data
function snapshot(data: ItemWithLocation[]) {
  history.value.push(JSON.parse(JSON.stringify(data)))
  if (history.value.length > MAX_HISTORY) history.value.shift()
}
// function to comeback to the previous state of the table
function undo() {
  // imit of 2 to avoid going back to the initial state
  if (history.value.length > 2) {
    const prev = history.value.pop()
    if (prev) {
      skipSnapshot = true
      tableData.value = prev

    }
  }
}

// capture crtl + z to undo
function onKeyDown(e: KeyboardEvent) {
  if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'z') {
    e.preventDefault()
    e.stopImmediatePropagation()
    undo()
  }
}

// watcher to save a snapshot of the table data when it changes
// watcher automático
watch(
  // deep tabledata clone to save the orriginal previous state
  () => tableData.value.map(row => ({ ...row })),
  (newVal, oldVal) => {
    // evita hacer snapshopt en la inicialización y cuando se hace undo
    if (skipSnapshot) {
      skipSnapshot = false   // reseteamos el flag
      return
    }
    snapshot(oldVal)
  },
  { deep: true }
)

// function to create labels from the actual items in the table that are not saved yet
async function openLabelsFromTable() {
  if (!tableData.value.length) return;
  try {
    const res = await axios.post(
      route('items.newlabels'),
    {records: tableData.value},
      { responseType: 'blob' }
    );
    const blob = new Blob([res.data], { type: 'application/pdf' });
    const url  = URL.createObjectURL(blob);
    window.open(url, '_blank');
    URL.revokeObjectURL(url);
  } catch (err) {
    console.error(err);
  }
}

// function to add the newly created tax to the taxOptions
function handleTaxCreated(tax: { id: number; name: string; percentage: number }) {
  taxOptions.value.push({
    label: `${tax.name} - ${tax.percentage}%`,
    value: tax.id
  });
  selectedTax.value = tax.id;
}
// function to calculate row total
function calculateTotal(cost: number|null, taxPerc: number|null): number|null {
  if (cost == null || taxPerc == null) return null;
  return parseFloat((cost * (1 + taxPerc / 100)).toFixed(2));
}

// function to put new totals in the table
function updateTotals() {
  skipSnapshot = true; // evitar snapshot en este cambio
  tableData.value.forEach(row => {
    row.total = calculateTotal(
      // row.cost puede ser string si usas formato "$123", convier̃telo a número:
      typeof row.cost === 'string'
        ? Number(row.cost.replace(/[^0-9.-]+/g, ''))
        : row.cost,
      selectedTax.value ? Number(selectedTax.value) : null
    );
  });
}

// recalculate totals when tax changes or costs change
watch(selectedTax, () => {
  updateTotals();
});

watch(
  () => tableData.value.map(r => r.cost),
  () => {
    updateTotals();
  }
);
</script>
