<template>
  <Toast></Toast>
  <section class="flex flex-col w-full px-4 relative">
    <section>
      <Button :loading="isLoading" :disabled="isLoading" v-if="!props.initialData?.length" @click="createDevices">Save devices</Button>
      <Button :loading="isLoading" :disabled="isLoading" v-else @click="editDevices">Update devices</Button>
    </section>

    <div class="spreadsheet-wrapper mt-8">
      <RevoGrid
        ref="revogrid"
        :columns="columns"
        :source="tableData"
        :columnTypes="columnTypes"
        :canFocus="!isLoading"
        :range="true"
        class="h-[80vh]"
        :resize="true"
        theme="default"
        @beforeedit="handleBeforeEdit"
        @beforeRowAdd="onInsertRow"
        @universal-cell-contextmenu="showContextMenu"
        @beforeRowDelete="onDeleteRow" />

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
import { nextTick, onMounted, ref } from "vue";
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
  const assignedPositions: Record<string, number[]> = {};
  const storage = storagesList.value.find((storage) => {
    const dbCount = storage.items.length;
    const inTable = tableData.value.filter((row) => row.location?.startsWith(storage.name)).length;
    return dbCount + inTable + numOfRows <= storage.limit;
  });
  if (!storage) return;
  tableData.value.forEach((row) => {
    if (!assignedPositions[storage.name]) assignedPositions[storage.name] = [];
    const dbPositions = storage.items.map((ItemWithLocation: ItemWithLocation) => ItemWithLocation.position);
    let pos = 1;
    while ([...assignedPositions[storage.name], ...dbPositions].includes(pos)) pos++;
    assignedPositions[storage.name].push(pos);
    row.location = `${storage.name} - ${pos} / ${storage.limit}`;
  });
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
      history.back();
    })
    .catch(() => {
      toast.add({ severity: "error", summary: "Error", detail: "Something went wrong!", life: 3000 });
    });
}

function mapSpreadsheetData(data: ItemWithLocation[]): any[] {
  return data.map((row) => {
    const storageId = storagesList.value.find((s) => s.name === row.location?.split("-")[0].trim())?.id;
    const vendorId = vendorsList.value.find((v) => v.vendor === row.vendor)?.id;
    return { ...row, storage_id: storageId, vendor_id: vendorId, date: format(row.date, "yyyy-MM-dd") };
  });
}

async function submitSpreadsheet(body: any[]): Promise<void> {
  try {
    isLoading.value = true;
    await axios.post("/inventory/items", { items: body }, { responseType: "blob" });
    isLoading.value = false;
    toast.add({ severity: "success", summary: "Success", detail: "Devices created successfully", life: 3000 });
    router.visit("/inventory/items");
  } catch (err) {
    toast.add({ severity: "error", summary: "Error", detail: "Something went wrong", life: 3000 });
  }
}
</script>
