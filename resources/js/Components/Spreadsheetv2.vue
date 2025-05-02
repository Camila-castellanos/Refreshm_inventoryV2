<template>
  <Toast></Toast>
  <section class="flex flex-col w-full px-4 relative">
    <section>
      <Button :loading="isLoading" :disabled="isLoading" v-if="!props.initialData?.length" @click="createDevices">Save
        devices</Button>
      <Button :loading="isLoading" :disabled="isLoading" v-else @click="editDevices">Update devices</Button>
    </section>

    <div class="spreadsheet-wrapper mt-8 w-full" ref="wrapper">
      <RevoGrid ref="revogrid" :columns="columns" :source="tableData" :columnTypes="columnTypes" :canFocus="!isLoading"
        :range="true" class="h-[80vh] w-full" :resize="true" theme="default" @beforeedit="handleBeforeEdit"
        @beforeRowAdd="onInsertRow" @universal-cell-contextmenu="showContextMenu" @beforeRowDelete="onDeleteRow"
        :clipboard="{ copyable: true, pasteable: true }" />

      <ContextMenu ref="menuRef" :model="menuItems" />
    </div>
  </section>
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
import { Button, ContextMenu, useDialog, useToast } from "primevue";
import { useConfirm } from "primevue/useconfirm";
import { nextTick, onMounted, ref, onBeforeUnmount, watch } from "vue";
import UniversalCell from "./UniversalCell.vue";
import { format } from "date-fns";

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
    pin: "colPinStart",
  },
  { prop: "date", name: "Date", columnType: "date", size: 200, cellTemplate: VGridVueTemplate(UniversalCell) },
  {
    prop: "vendor",
    name: "Vendor",
    size: 200,
    columnType: "select",
    source: [...vendorOptions.value],
    labelKey: "label",
    valueKey: "value",
    cellTemplate: VGridVueTemplate(UniversalCell),
  },
  { prop: "manufacturer", name: "Manufacturer", size: 200, cellTemplate: VGridVueTemplate(UniversalCell) },
  { prop: "model", name: "Model", size: 150, cellTemplate: VGridVueTemplate(UniversalCell) },
  { prop: "colour", name: "Colour", size: 150, cellTemplate: VGridVueTemplate(UniversalCell) },
  { prop: "battery", name: "Battery", size: 80, cellTemplate: VGridVueTemplate(UniversalCell) },
  { prop: "grade", name: "Grade", size: 80, cellTemplate: VGridVueTemplate(UniversalCell) },
  { prop: "issues", name: "Issues", size: 150, cellTemplate: VGridVueTemplate(UniversalCell) },
  { prop: "imei", name: "IMEI", size: 150, cellTemplate: VGridVueTemplate(UniversalCell) },
  { prop: "location", name: "Location", size: 100, readonly: true, cellTemplate: VGridVueTemplate(UniversalCell) },
  { prop: "cost", name: "Cost", columnType: "number", size: 120, cellTemplate: VGridVueTemplate(UniversalCell) },
  { prop: "selling_price", name: "Selling Price", columnType: "number", size: 120, cellTemplate: VGridVueTemplate(UniversalCell) },
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

  await nextTick();
  if (!props.initialData?.length) renderPositions(1);
});

onBeforeUnmount(() => {
  window.removeEventListener('resize', adjustColumnSizes);
  window.removeEventListener('paste', handleGlobalPaste);
  window.removeEventListener('keydown', onKeyDown, {capture: true});
});

function showContextMenu(e: CustomEvent<{ event: MouseEvent; rowIndex: number; prop: string }>) {
  e.preventDefault();
  if (props.initialData?.length) return;
  contextRow.value = e.detail.rowIndex;
  menuRef.value.show(e.detail.event);
}

function insertRow(position: "above" | "below" | "bulk") {
  const rowIndex = contextRow.value ?? 0;
  console.log(rowIndex);
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
  console.log("new rows to assign", newRowsToAssign);
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
    const vendorId = vendorsList.value.find((v) => v.vendor === row.vendor)?.id;
    let cost = row.cost;
    let selling_price = row.selling_price;

    if (cost?.toString().startsWith("$")) {
      cost = Number(cost.toString().slice(1));
    }
    if (selling_price?.toString().startsWith("$")) {
      selling_price = Number(selling_price.toString().slice(1));
    }
    return { ...row, storage_id: storageId, vendor_id: vendorId, date: format(row.date, "yyyy-MM-dd"), cost, selling_price };
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
async function adjustColumnSizes() {
  await nextTick();
  if (!wrapper.value) return;
  const totalWidth = wrapper.value.clientWidth;
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
</script>
