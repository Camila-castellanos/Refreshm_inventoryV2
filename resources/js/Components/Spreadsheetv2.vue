<template>
  <Toast></Toast>
  <!-- PrimeVue confirmation dialog -->
  <ConfirmDialog />
  <section class="flex flex-col w-full px-4 relative">
    <section>
      <div class="flex flex-row justify-between" id="spreadsheet-header">
        <div id="spreadsheet-buttons" class="flex flex-row items-center">
      <Button icon="pi pi-save" :loading="isLoading"  :disabled="isLoading" v-if="!props.initialData?.length" @click="showOptions = true" label="Save devices" />
      <Button :loading="isLoading"  :disabled="isLoading" v-else @click="editDevices" icon="pi pi-save" label="Update devices" />
      <Button
          icon="pi pi-print"
          class="ml-2 !w-fit !h-fit !px-2"
          @click="openLabelsFromTable(mapSpreadsheetData(tableData))"
          :disabled="
            tableData.length < 1 ||
            (tableData.length === 1 && isEmptyRow(tableData[0]))
          "
          label="Print Labels"
        />
        <div class="flex space-x-2 px-2">
    <label for="save-as-bill" class="font-medium">Save as Bill</label>
    <ToggleSwitch
      id="save-as-bill"
      v-model="saveAsBill"
      :disabled="isLoading"
       @change="val => { if (val) showBillModal = true }"
    >
    <template #handle="{ checked }">
        <i :class="['!text-xs pi', { 'pi-check': checked, 'pi-times': !checked }]" />
    </template>
    </ToggleSwitch>
  </div>
  </div>
         <!-- Global data section for all items-->
        <div id="spreadsheet-global-data" class="flex flex-row gap-4">
          <!-- temp clean button -->
          <Button
         icon="pi pi-clock"
         class="!h-fit"
         @click="handleCleanLocalSave"
         aria-label="Clean Local Save"
         label="Clean Local Save"
         />
          <!-- Draft modal button -->
          <Button
          icon="pi pi-folder-open"
          class="!h-fit"
          :disabled="isLoading"
          @click="showLoadDraft = true"
          aria-label="Load Draft"
          label="Load Draft"
          />
            <!-- Auto generate sales price button -->
          <Button
            icon="pi pi-dollar"
            class="!h-fit ml-2"
            :disabled="isLoading"
            @click="autoGenerateSalesPrices"
            aria-label="Auto Generate Sales Prices"
            label="Auto Generate Sales Prices"
          />
            <!-- Vendors Dropdown -->
          <Select
            v-model="selectedVendor"
            :options="vendorOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="Select Vendor"
            :disabled="isLoading"
            class="w-60"
            filter                      
            filterBy="label"            
            filterPlaceholder="Search Vendor..."
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
        @beforesetrange="handleBeforeSetRange"
        @beforecellfocus="handleBeforeCellFocus"
        row-key="location"/>

      <ContextMenu ref="menuRef" :model="menuItems" />
    </div>
  </section>
  <CreateTax
    v-model:visible="showTaxDialog"
    @created="handleTaxCreated"/>
  <SaveAsBillModal
    v-model:visible="showBillModal"
    @save="updateBillTitle"
    @cancel="saveAsBill = false"
  />
  <SaveDevicesOptions
    v-model:visible="showOptions"
    @toInventory="createDevices"
    @asDraft="saveAsDraft"
    @cancel="showOptions = false"
  />
  <DraftNameModal
    v-model:visible="showDraftName"
    @save="updateDraftName"
    @cancel="showDraftName = false"
  />
  <LoadDraftModal
    v-model:visible="showLoadDraft"
    @load="handleLoadDraft"
    @update:visible="showLoadDraft = $event"
  />
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
import { Button, ContextMenu, useDialog, useToast, Select, DatePicker, ToggleSwitch } from "primevue";
import { useConfirm } from "primevue/useconfirm";
import { nextTick, onMounted, ref, onBeforeUnmount, watch, computed } from "vue";
import UniversalCell from "./UniversalCell.vue";
import { format } from "date-fns";
import CreateTax from "@/Pages/Accounting/Modals/CreateTax.vue";
import SaveAsBillModal from "./SaveAsBillModal.vue";
import SaveDevicesOptions from "./SaveDevicesOptions.vue";
import DraftNameModal from "./DraftNameModal.vue";
import LoadDraftModal from "./LoadDraftModal.vue";

type VendorOption = { label: string; value: string };
type ContextMenu = { visible: boolean; x: number; y: number; row: number | null };

type ItemWithLocation = Item & { location: string } & { subtotal: number|string; selling_price?: number|string; total?: number|string };

const props = defineProps<{ initialData?: Item[] }>();

const confirm = useConfirm();
const toast = useToast();
const revogrid = ref<InstanceType<typeof RevoGrid> | null>(null);
const storagesList = ref<any[]>([]);
const vendorsList = ref<any[]>([]);
const vendorOptions = ref<VendorOption[]>([]);
const tableData = ref<ItemWithLocation[]>([]);
const isLoading = ref(false);
const selectedVendor = ref<string | number | null>(null);
const selectedDate = ref<Date | null>(null);
const taxOptions = ref<{ label: string; value: number; percentage: number }[]>([]);
const selectedTax = ref<string | null>(null);
const showTaxDialog = ref(false);
const isMounted = ref(false)
const saveAsBill = ref(false);
const showBillModal = ref(false);
const BillTitle = ref("");
const showOptions = ref(false);
const showDraftName = ref(false)
const currentLoadedDraftId = ref<number | null>(null);
const showLoadDraft = ref(false);
const dontSaveDraft = ref(false);
const selectedRows = ref<number[]>([]);
const lastRightClickAt = ref<number | null>(null);
const isRightClickActive = ref(false);
const selectedRangeLimits = ref<{
  minRow: number | null;
  maxRow: number | null;
  minCol: number | null;
  maxCol: number | null;
} | null>(null);

const menuItems = computed(() => {
  const nSelected = selectedRows.value.length;
  const items: any[] = [];

  // Solo mostrar opciones de inserción si NO hay múltiples filas seleccionadas
  if (nSelected <= 1) {
    items.push(
      { label: "Insert Row Above", command: () => insertRow("above") },
      { label: "Insert Row Below", command: () => insertRow("below") },
      { label: "Insert 50 Rows Below", command: () => insertRow("bulk") }
    );
  }

  items.push({ separator: true });

  // Delete label dinámico (singular/plural) y comando para borrar múltiples filas
  const deleteLabel = nSelected > 1 ? `Delete Rows (${nSelected})` : "Delete Row";
  items.push({
    label: deleteLabel,
    icon: "pi pi-trash",
    class: "text-red-600",
    command: () => deleteRow(),
  });

  // Print labels: calcular lista de filas a imprimir (seleccionadas o fila de contexto)
  const printCount = nSelected > 0 ? nSelected : 1;
  const printLabel = printCount > 1 ? `Print Labels (${printCount})` : `Print Label (${printCount})`;
  items.push({
    label: printLabel,
    icon: "pi pi-print",
    command: () => {
      const rows = selectedRows.value.length > 0
        ? selectedRows.value.map(i => tableData.value[i]).filter(Boolean)
        : [tableData.value[contextRow.value ?? 0]];
      openLabelsFromTable(mapSpreadsheetData(rows));
    },
  });

  return items;
});

const contextRow = ref<number | null>(null);
const menuRef = ref<any>(null);
const wrapper = ref<HTMLElement|null>(null);
const headerObserver = ref<MutationObserver|null>(null);
const bodyTooltips = ref<Record<string, HTMLElement>>({});
const columns = ref<ColumnRegular[]>([
  {
    prop: "id",
    name: '',
    size: 20,
    readonly: true,
    cellTemplate: VGridVueTemplate(UniversalCell),
  },
  { prop: "manufacturer", name: "Manufacturer", size: 200, cellTemplate: VGridVueTemplate(UniversalCell) },
  { prop: "model", name: "Model", size: 150, cellTemplate: VGridVueTemplate(UniversalCell) },
  { prop: "colour", name: "Colour", size: 150, cellTemplate: VGridVueTemplate(UniversalCell) },
  { prop: "battery", name: "Battery", size: 20, cellTemplate: VGridVueTemplate(UniversalCell) },
  { prop: "grade", name: "Grade", size: 80, cellTemplate: VGridVueTemplate(UniversalCell) },
  { prop: "issues", name: "Issues", size: 150, cellTemplate: VGridVueTemplate(UniversalCell) },
  { prop: "imei", name: "IMEI", size: 150, cellTemplate: VGridVueTemplate(UniversalCell) },
  { prop: "location", name: "Location", size: 100, readonly: true, cellTemplate: VGridVueTemplate(UniversalCell) },
  { prop: "subtotal", name: "Subtotal", columnType: "number", size: 120, cellTemplate: VGridVueTemplate(UniversalCell) },
  { prop: "cost", name: "Total", columnType: "number", size: 120, cellTemplate: VGridVueTemplate(UniversalCell) },
  { prop: "selling_price", name: "Selling Price", columnType: "number", size: 120, cellTemplate: VGridVueTemplate(UniversalCell) },
]);

// Optional tooltips for column headers: map column prop -> tooltip text
const headerTooltips = ref<Record<string, string>>({
  manufacturer: 'Device manufacturer',
  model: 'Device model name',
  colour: 'Device colour',
  battery: 'Battery state',
  grade: 'Device grade',
  issues: 'Known issues or notes',
  imei: 'IMEI / serial number',
  location: 'Assigned storage location (auto)',
  subtotal: 'Item subtotal (before tax)',
  cost: 'Total cost (with tax)',
  selling_price: 'Selling price',
});

function applyHeaderTooltips() {
  // RevoGrid renders header cells with class 'rgHeaderCell' according to README
  // We select all header cells and match them to columns by data-col-index attribute or order
  const gridEl = revogrid.value?.$el as HTMLElement | undefined | null;
  if (!gridEl) return;

  // header cells may be inside shadow DOM or light DOM; try both
  const headerCells = Array.from(gridEl.querySelectorAll('.rgHeaderCell')) as HTMLElement[];

  if (headerCells.length === 0) {
    // fallback: try to find any element with data-col-index attribute
    const fallback = Array.from(gridEl.querySelectorAll('[data-col-index]')) as HTMLElement[];
    fallback.forEach((cell) => {
      const idx = Number(cell.getAttribute('data-col-index'));
      const col = columns.value[idx];
      if (col && col.prop && headerTooltips.value[col.prop as string]) {
        cell.setAttribute('title', headerTooltips.value[col.prop as string]);
        cell.setAttribute('aria-label', headerTooltips.value[col.prop as string]);
      }
    });
    return;
  }

  // If headerCells exist, attempt to map by visual order to columns
  headerCells.forEach((cell, index) => {
    const col = columns.value[index];
    const text = col && col.prop ? headerTooltips.value[col.prop as string] : null;
    if (!text) return;

    // set accessible attribute
    cell.setAttribute('aria-label', text);

    // create or reuse a body-level tooltip element (prevents clipping)
    const key = String(index);
    let tip = bodyTooltips.value[key];
    if (!tip) {
      tip = document.createElement('div');
      tip.className = 'revogrid-header-tooltip-body';
      tip.style.position = 'fixed';
      tip.style.pointerEvents = 'none';
      tip.style.opacity = '0';
      tip.style.transition = 'opacity 120ms ease, transform 120ms ease';
      document.body.appendChild(tip);
      bodyTooltips.value[key] = tip;
    }
    tip.textContent = text;

    // ensure we don't attach duplicate handlers
    if ((cell as any).__revogrid_tooltip_attached) return;

    const show = (e: MouseEvent) => {
      const rect = cell.getBoundingClientRect();
      const left = rect.left + rect.width / 2 + 5;
      const top = rect.top - 20; // moved further up
      tip.style.left = left + 'px';
      tip.style.top = top + 'px';
      tip.style.transform = 'translate(-55%, -20px)';
      tip.style.zIndex = '20000';
      tip.style.opacity = '1';
    };

    const move = (e: MouseEvent) => {
      const rect = cell.getBoundingClientRect();
      const left = rect.left + rect.width / 2 + 5;
      const top = rect.top - 20;
      tip.style.left = left + 'px';
      tip.style.top = top + 'px';
    };

    const hide = () => {
      tip.style.opacity = '0';
      tip.style.transform = 'translate(-50%, 0)';
    };

    cell.addEventListener('mouseenter', show);
    cell.addEventListener('mousemove', move);
    cell.addEventListener('mouseleave', hide);
    (cell as any).__revogrid_tooltip_attached = true;
    (cell as any).__revogrid_tooltip_handlers = { show, move, hide };
  });
}

const columnTypes = ref<any>({
  date: new DateTypePlugin({ format: "yyyy-MM-dd" }),
  select: new SelectTypePlugin(),
  number: new NumberTypePlugin({ format: "#,##0.00" }),
});

// function handleRangeChanged(e: RevoGridCustomEvent<any>) {
//   console.log("RevoGrid range-changed event detail:", e);
// }

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
      console.log("item", item);
      return {
        ...item,
        location: `${storage?.name} - ${item.position}/${storage?.limit}`,
        vendor: vendors.data.find((v: Vendor) => v.id === item.vendor_id)?.vendor || "",
        vendor_id: item.vendor_id || null,
        date: format(item.date, "yyyy-MM-dd"),
      };
    })
    : [{}] as ItemWithLocation[];
    console.log("Initial table data:", tableData.value);
    await nextTick();
    // Apply header tooltips once grid has rendered
    applyHeaderTooltips();
    if (!props.initialData?.length) renderPositions(1);
// Re-apply tooltips when columns change (e.g., sizing, reorder)
watch(columns, async () => {
  await nextTick();
  applyHeaderTooltips();
}, { deep: true });
    // load draft from local storage if exists
    loadDraftFromLocalStorage();
    // save draft to local storage every 60 seconds
    draftSaveInterval = setInterval(saveDraftToLocalStorage, 60000);
    // save draft before unload
    window.addEventListener('beforeunload', saveDraftToLocalStorage);
   
      // Capturar eventos INMEDIATAMENTE en el elemento revo-grid
  const revogridElement = revogrid.value?.$el;
  if (revogridElement) {
    console.log("Setting up immediate RevoGrid right-click detection");
    
    // Capturar mousedown con máxima prioridad
    revogridElement.addEventListener('mousedown', (e: MouseEvent) => {
      if (e.button === 2) {
        isRightClickActive.value = true;
        lastRightClickAt.value = Date.now();
        console.log("IMMEDIATE right-click detected on RevoGrid");
        
        // Resetear después de 150ms
        setTimeout(() => {
          isRightClickActive.value = false;
        }, 150);
      } else {
        isRightClickActive.value = false;
      }
    }, true);
   } // useCapture = true - MÁXIMA PRIORIDAD

   // Observe header changes to reapply tooltips when RevoGrid rerenders headers
   try {
     const headersRoot = revogridElement.querySelector('.rgHeader');
     if (headersRoot) {
       headerObserver.value = new MutationObserver(() => {
         // small debounce
         setTimeout(() => applyHeaderTooltips(), 50);
       });
       headerObserver.value.observe(headersRoot, { childList: true, subtree: true, attributes: true });
     }
   } catch (err) {
     console.warn('Header observer setup failed', err);
   }

    if (route().current('items.edit')){
      isMounted.value = true;
      return;
    }
    if(props.initialData?.length) {
      // selectedVendor
      selectedVendor.value = vendors.data.find((v: Vendor) => v.id === props.initialData[0].vendor_id)?.vendor || "";
      selectedDate.value = props.initialData[0].date ? new Date(props.initialData[0].date) : null;
    }

    if ((props.initialData?.length ?? 0) > 0 && tableData.value[0].tax != null) {
    selectedTax.value = tableData.value[0].tax;
    }  

    // finish mounting
    isMounted.value = true;
});

onBeforeUnmount(() => {
  window.removeEventListener('resize', adjustColumnSizes);
  window.removeEventListener('paste', handleGlobalPaste);
  window.removeEventListener('keydown', onKeyDown, {capture: true});
  window.removeEventListener('beforeunload', saveDraftToLocalStorage);
  // clear the draft save interval
  clearInterval(draftSaveInterval);
  // disconnect header observer
  if (headerObserver.value) {
    headerObserver.value.disconnect();
    headerObserver.value = null;
  }

  // remove any body-level tooltips and header handlers
  try {
    Object.values(bodyTooltips.value).forEach((el) => {
      if (el && el.parentNode) el.parentNode.removeChild(el);
    });
    bodyTooltips.value = {};
  } catch (err) {
    console.warn('Failed to clean body tooltips', err);
  }

  // remove attached handlers on header cells
  try {
    const gridEl = revogrid.value?.$el as HTMLElement | null;
    const headerCells = gridEl ? Array.from(gridEl.querySelectorAll('.rgHeaderCell')) as HTMLElement[] : [];
    headerCells.forEach((cell) => {
      const attached = (cell as any).__revogrid_tooltip_attached;
      const handlers = (cell as any).__revogrid_tooltip_handlers;
      if (attached && handlers) {
        cell.removeEventListener('mouseenter', handlers.show);
        cell.removeEventListener('mousemove', handlers.move);
        cell.removeEventListener('mouseleave', handlers.hide);
        delete (cell as any).__revogrid_tooltip_attached;
        delete (cell as any).__revogrid_tooltip_handlers;
      }
    });
  } catch (err) {
    console.warn('Failed to remove header tooltip handlers', err);
  }
});

async function getUserTaxes() {
    try {
      let taxes;
      const { data } = await axios.get(route('tax.list'));
      console.log("Taxes data:", data);
      taxes = data.map((tax: any) => ({
        label: `${tax.name || 'N/A'} - (${tax.percentage + '%' || 'N/A'})`,
        value: tax.id,
        percentage: tax.percentage
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
  console.log("context menu event:", e.detail.event);
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
  // Si hay múltiples filas seleccionadas, borrarlas todas; si no, usar contextRow
  const rowsToDelete = selectedRows.value.length > 1
    ? [...selectedRows.value]
    : [contextRow.value ?? 0];

  // Normalizar: índices únicos y orden descendente para evitar desplazamientos al eliminar
  const unique = Array.from(new Set(rowsToDelete)).filter(Number.isFinite).map(Number).sort((a, b) => b - a);

  if (unique.length === 0) return;

  // Eliminar cada índice válido
  for (const idx of unique) {
    if (idx >= 0 && idx < tableData.value.length) {
      tableData.value.splice(idx, 1);
    }
  }
  // force reference update
  tableData.value = [...tableData.value];

  //clean selection
  selectedRows.value = [];

  // Asegurarnos de que siempre quede al menos una fila
  if (tableData.value.length === 0) {
    tableData.value = [{}] as ItemWithLocation[];
    // Llamamos a renderPositions porque agregamos una fila nueva vacía
    renderPositions(1);
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
    console.log("storageList", storagesList.value);
    for (const storage of storagesList.value) {
      const dbPositions = storage.items.map((item: Item) => item.position);
      // include positions reserved by drafts
      const draftPositions = storage.draft_items?.map((d: any) => d.storage_position) || [];
      console.log("Draft positions:", draftPositions);
      console.log("DB positions:", dbPositions);
      const inTablePositions: number[] = [];
      tableData.value.forEach((r) => {
        if (r.location?.startsWith(storage.name) && r !== row) { // Exclude the current row being assigned
          const parts = r.location.split(" - ")[1]?.split(" / ");
          if (parts && parts[0]) {
            inTablePositions.push(parseInt(parts[0]));
          }
        }
      });

      const occupiedPositions = [...dbPositions, ...draftPositions, ...inTablePositions];
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

async function saveAsDraft() {
  if (!BillTitle.value) {
    showDraftName.value = true;
    return;
  }
  try {
  
    const formattedDate = format(selectedDate.value, "yyyy-MM-dd");
    const payload = {
      id: currentLoadedDraftId.value,
      date: formattedDate,
      vendor: selectedVendor.value,
      title: BillTitle.value,
      items: mapSpreadsheetData(tableData.value).map(item => {
        const [_, rest] = item.location?.split(' - ') || [];
        const pos = rest?.split(' / ')[0] || null;
        return {
          ...item,
          storage_position: pos ? parseInt(pos, 10) : null
        };
      })
    };
    console.log("Saving draft with payload:", payload);
    const {data} = await axios.post(route('drafts.store'), payload);
    currentLoadedDraftId.value = data.id;
    toast.add({ severity:'success', summary:'Draft saved' });
    clearLocalDraft();
    showOptions.value = false;
    router.visit("/inventory/items", { only: ["items", "tabs"] });
  } catch(e) {
    toast.add({ severity:'error', summary:'Error saving draft' });
    console.error("Error saving draft:", e);
  }
}

function createDevices(): void {
  confirm.require({
    message: `Are you sure you want to add these devices?`,
    header: "Confirmation",
    icon: "pi pi-exclamation-triangle",
    rejectProps: { label: "Cancel", severity: "secondary", outlined: true },
    acceptProps: { label: "Save" },
    accept: () => verifySpreadsheetRequired(() => submitSpreadsheet(mapSpreadsheetData(tableData.value))),
  });
}
  function verifySpreadsheetRequired(callback: () => void): void {
    if (!selectedVendor.value) {
      toast.add({
        severity: 'error',
        summary: 'Validation Error',
        detail: 'A Vendor must be selected',
        life: 3000,
      });
      return;
    }
    if (!selectedDate.value) {
      toast.add({
        severity: 'error',
        summary: 'Validation Error',
        detail: 'A Date must be selected',
        life: 3000,
      });
      return;
    }
    if (!selectedTax.value) {
      toast.add({
        severity: 'error',
        summary: 'Validation Error',
        detail: 'A Tax must be selected',
        life: 3000,
      });
      return;
    }
    callback();
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
    const vendorId = selectedVendor.value != null
      ? vendorsList.value.find(v => v.vendor === selectedVendor.value)?.id
      : row.vendor_id;
    let cost = row.cost;
    let selling_price = row.selling_price;
    let subtotal = row.subtotal;
    if (subtotal?.toString().startsWith("$")) {
      subtotal = Number(subtotal.toString().slice(1));
    }
    if (cost?.toString().startsWith("$")) {
      cost = Number(cost.toString().slice(1));
    }
    if (selling_price?.toString().startsWith("$")) {
      selling_price = Number(selling_price.toString().slice(1));
    }
    const date = selectedDate.value
      ? format(selectedDate.value, "yyyy-MM-dd")
      : row.date
        ? row.date
        : format(new Date(), "yyyy-MM-dd");

     const tax =
      selectedTax.value != null ? selectedTax.value : row.tax;  
    
    // parse position from location string (format "StorageName - X/Y")
    const locParts = row.location?.split(' - ');
    const positionParsed = locParts && locParts[1]
      ? parseInt(locParts[1].split('/')[0].trim(), 10)
      : row.position;

    // ensure subtotal is a number
    if (typeof subtotal === 'string') {
      subtotal = parseFloat(subtotal.replace(/[^0-9.-]+/g, ''))
    }

    // include parsed position in payload
    return {
      ...row,
      storage_id: storageId,
      position: positionParsed,
      vendor_id: vendorId,
      date: date,
      cost,
      selling_price,
      tax,
      subtotal
    };
  });
}

async function submitSpreadsheet(body: any[]): Promise<void> {
  const endpoint = saveAsBill.value ? "items.storeWithBill" : "items.store";
  const payload = saveAsBill.value
    ? {
        items: body,
        bill: {
          date: selectedDate.value ? format(selectedDate.value, "yyyy-MM-dd") : format(new Date(), "yyyy-MM-dd"),
          vendor_id: findVendorId(selectedVendor.value),
          tax_id: selectedTax.value,
          title: BillTitle.value || "New Bill",
        },
      }
    : { items: body };
  console.log("Submitting data to endpoint:", endpoint, "with payload:", payload);
  try {
    isLoading.value = true;
    await axios.post(route(endpoint), payload, { responseType: "blob" });
    isLoading.value = false;
    toast.add({ severity: "success", summary: "Success", detail: "Devices created successfully", life: 3000 });
    // Delete current draft from database
    if (currentLoadedDraftId.value) {
      try {
        await axios.post(route('drafts.purge', currentLoadedDraftId.value));
        console.log("Draft purged successfully");
      } catch (err) {
        console.warn('Failed to purge draft:', err);
      }
      currentLoadedDraftId.value = null;
    }
    // Clear local storage and navigate
    clearLocalDraft();
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
    columns.value = initialColumns.value.map(col => ({ ...col }));
    return;
  }

  // Definir anchos fijos (si una columna está aquí, su tamaño será reservado)
  const fixedWidths: Record<string, number> = {
    id: 40,
    battery: 50,
    grade: 50,
    subtotal: 120,
    cost: 120,
    selling_price: 120
  };

  // Calcular ancho reservado por las columnas fijas que existen actualmente
  let reserved = 0;
  const variableCols: ColumnRegular[] = [];
  for (const col of columns.value) {
    const key = col.prop as string;
    if (fixedWidths[key]) {
      col.size = fixedWidths[key];
      reserved += fixedWidths[key];
    } else {
      variableCols.push(col);
    }
  }

  // margen opcional para scrollbar/padding (puedes poner 0 si no quieres margen)
  const scrollbarMargin = 0;
  const remaining = Math.max(0, totalWidth - reserved - scrollbarMargin);

  // Si no hay columnas variables, ajustar la última para llenar el espacio
  if (variableCols.length === 0) {
    const lastIndex = columns.value.length - 1;
    if (lastIndex >= 0) {
      columns.value[lastIndex].size = (columns.value[lastIndex].size || 100) + remaining;
    }
    return;
  }

  // Repartir el espacio restante entre las columnas variables de forma equitativa
  const perCol = Math.floor(remaining / variableCols.length);
  let leftover = remaining - perCol * variableCols.length;

  // Construir el nuevo array asignando extra (uno por columna) hasta agotar 'leftover'
  const newCols = columns.value.map((col) => {
    const key = col.prop as string;
    if (fixedWidths[key]) {
      return { ...col, size: fixedWidths[key] };
    }
    const extra = leftover > 0 ? 1 : 0;
    if (extra === 1) leftover -= 1;
    return { ...col, size: perCol + extra };
  });

  // Si por redondeo sigue habiendo una pequeña diferencia, añadirla a la última columna
  const assignedTotal = newCols.reduce((s, c) => s + (c.size || 0), 0);
  const gap = totalWidth - assignedTotal;
  if (gap > 0) {
    // preferimos añadir el gap a la última columna (última del array)
    const lastIdx = newCols.length - 1;
    newCols[lastIdx].size = (newCols[lastIdx].size || 0) + gap;
  } else if (gap < 0) {
    // en caso extremo de overflow, recortamos la última columna
    const lastIdx = newCols.length - 1;
    newCols[lastIdx].size = Math.max(40, (newCols[lastIdx].size || 0) + gap); // no bajar de 40px
  }

  columns.value = newCols;
}
// handle paste event globally

const pasteOrder = [
  "manufacturer",
  "model",
  "colour",
  "battery",
  "grade",
  "issues",
  "subtotal",
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
async function openLabelsFromTable(items) {
  if (!items.length) return;
  console.log("Creating labels for items:", items);
  try {
    const res = await axios.post(
      route('items.newlabels'),
      { records: items },
      { responseType: 'blob' }
    );
    const blob = new Blob([res.data], { type: 'application/pdf' });
    const url = URL.createObjectURL(blob);
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
    value: tax.id,
    percentage: tax.percentage
  });
  selectedTax.value = tax.id;
}
// function to calculate row total
function calculateTotal(cost: number|null, taxPerc: number|null): number|null {
  if (cost == null || taxPerc == null) return null;
  return parseFloat((cost * (1 + taxPerc / 100)).toFixed(2));
}
function getTaxPercentageById(id: number | string): number | null {
  const option = taxOptions.value.find(opt => opt.value === id)
  return option?.percentage ?? null
}

// function to put new totals in the table
function updateTotals() {
  skipSnapshot = true; // evitar snapshot en este cambio
  tableData.value.forEach(row => {
   // si no hay subtotal, no hacemos nada
   if (!row.subtotal) return;
    row.cost = calculateTotal(
      // row.cost puede ser string si usas formato "$123", convier̃telo a número:
      typeof row.subtotal === 'string'
        ? Number(row.subtotal.replace(/[^0-9.-]+/g, ''))
        : row.subtotal,
      selectedTax.value ? getTaxPercentageById(selectedTax.value) : getTaxPercentageById(row.tax) || null
    );
    console.log("Updated row tax:", row.tax);
  });
}

// recalculate totals when tax changes or costs change
watch(selectedTax, () => {
  updateTotals();
});

watch(
  () => tableData.value.map(r => r.subtotal),
  () => {
    if (!isMounted.value) return; // evita recalcular antes de montar
    updateTotals();
  }
);

// function to update the bill title from the modal
function updateBillTitle(title: string) {
  BillTitle.value = title;
  showBillModal.value = false;
}
// function to update the draft name from the modal
function updateDraftName(title: string) {
  BillTitle.value = title;
  showDraftName.value = false;
  saveAsDraft(); // call save as draft function
}
// function to find vendor id
function findVendorId(vendorName: string): number | null {
  const vendor = vendorsList.value.find((v: Vendor) => v.vendor === vendorName);
  return vendor ? vendor.id : null;
}

// function to handle drafts loaded from the modal
function handleLoadDraft(draft: any) {
  currentLoadedDraftId.value = draft.id;
  // Carga campos del draft
  console.log("Loading draft:", draft);
  selectedVendor.value = draft.vendor ? draft.vendor : vendorsList.value.find(v => v.id === draft.items[0]?.vendor_id)?.vendor || null;
  selectedDate.value   = draft.date ? new Date(draft.date) : new Date();
  selectedTax.value    = draft.items[0]?.tax_id ?? null;
  BillTitle.value      = draft.title;
  
  // Mapear items y construir 'location'
  tableData.value = draft.items.map((item: any) => {
    const storage = storagesList.value.find(s => s.id === item.storage_id);
    let location = '';
    
    // Si el item tiene storage_id y storage_position, construir la location
    if (storage && item.storage_position) {
      location = `${storage.name} - ${item.storage_position} / ${storage.limit}`;
    }
    // Si no tiene location, se dejará vacío para que renderPositions lo asigne
    
    return {
      ...item,
      location: location,
      vendor: selectedVendor.value as string,
      date: draft.date,
      tax: draft.tax_id,
      subtotal: item.subtotal,
      cost: item.cost,
      selling_price: item.selling_price,
    };
  });
  
  // Verificar si hay items sin location y asignarles posiciones automáticamente
  const itemsWithoutLocation = tableData.value.filter(item => !item.location);
  if (itemsWithoutLocation.length > 0) {
    console.log(`Found ${itemsWithoutLocation.length} items without location, assigning positions...`);
    renderPositions(itemsWithoutLocation.length);
  }
}

// local draft management
const STORAGE_KEY = 'draft_auto_save';
// function to load draft from local storage
function loadDraftFromLocalStorage() {
  if (route().current('items.edit')) {
    // si estamos en la edición de un item, no cargamos el draft
    return;
  }
  const stored = localStorage.getItem(STORAGE_KEY);
  if (stored) {
    try {
      const draft = JSON.parse(stored);
      handleLoadDraft(draft);
    } catch (e) {
      console.error('Failed to parse local draft', e);
    }
  }
}

let draftSaveInterval: ReturnType<typeof setInterval>;
// function to save draft to local storage every 60 seconds
function saveDraftToLocalStorage() {
  if (route().current('items.edit') || dontSaveDraft.value) {
    // si estamos en la edición de un item, no guardamos el draft
    return;
  }
  if (!selectedDate.value) {
    selectedDate.value = new Date();
  }
  const items = mapSpreadsheetData(tableData.value).map(item => {
        const [_, rest] = item.location?.split(' - ') || [];
        const pos = rest?.split(' / ')[0] || null;
        return {
          ...item,
          storage_position: pos ? parseInt(pos, 10) : null
        };
      });
  const draft = {
    id: currentLoadedDraftId.value,
    vendor: selectedVendor.value,
    date: selectedDate.value.toISOString().split('T')[0],
    title: BillTitle.value,
    tax_id: selectedTax.value,
    items: items
  };
  localStorage.setItem(STORAGE_KEY, JSON.stringify(draft));
}
// cleanup function to remove the draft from local storage
function clearLocalDraft() {
  localStorage.removeItem(STORAGE_KEY);
}

// handler to clear draft flag and reload page
function handleCleanLocalSave() {
  clearLocalDraft();
  dontSaveDraft.value = true;
  window.location.reload();
}

// Auto-generate selling prices for rows (stub - user can extend)
async function autoGenerateSalesPrices() {
  console.log('Requesting server-side auto-generate of selling prices');
  try {
    isLoading.value = true;
    // send mapped data (same shape as submission)
    const payload = mapSpreadsheetData(tableData.value);
    const res = await axios.post(route('items.generateSellingPrices'), { items: payload });
    console.log('Server responded with:', res.data);
    // Expect response to contain updated items array
    if (res && res.data && Array.isArray(res.data.items)) {
      // merge selling_price from response into tableData preserving other fields
      const updated = res.data.items as any[];
      let updatedCount = 0;

      // Work on a copy of rows for safe updates
      const rows = tableData.value.map(r => ({ ...r }));

      for (const u of updated) {
        let rowIndex = -1;

        // 1) If backend tells us it matched by id (matched_item_id), use that
        if (u.matched_on === 'id' && u.matched_item_id != null) {
          rowIndex = rows.findIndex(r => r.id === u.matched_item_id || r.id === u.id);
        }

        // 2) fallback: match by incoming item's id field
        if (rowIndex === -1 && u.id != null) {
          rowIndex = rows.findIndex(r => r.id === u.id);
        }

        // 3) match by imei (explicit)
        if (rowIndex === -1 && u.matched_on === 'imei' && u.imei) {
          rowIndex = rows.findIndex(r => r.imei && r.imei === u.imei);
        }

        // 4) fallback: any returned imei
        if (rowIndex === -1 && u.imei) {
          rowIndex = rows.findIndex(r => r.imei && r.imei === u.imei);
        }

        // 5) match by model (case-insensitive contains) when provided
        if (rowIndex === -1 && u.model) {
          const uModel = String(u.model).toLowerCase().trim();
          rowIndex = rows.findIndex(r => r.model && String(r.model).toLowerCase().includes(uModel));
        }

        // 6) final fallback: incoming_index provided by server
        if (rowIndex === -1 && typeof u.incoming_index === 'number' && u.incoming_index >= 0 && u.incoming_index < rows.length) {
          rowIndex = u.incoming_index;
        }

        // apply update if found
        if (rowIndex !== -1) {
          const target = rows[rowIndex];
          if (u.selling_price != null) {
            target.selling_price = u.selling_price;
            updatedCount++;
          }
          // attach metadata for UI/debug if needed
          if (u.fields_used) target._selling_price_fields_used = u.fields_used;
          if (u.matched_on) target._selling_price_matched_on = u.matched_on;
        }
      }

      // replace table rows with updated copy
      tableData.value = rows;
      toast.add({ severity: 'success', summary: 'Success', detail: `${updatedCount} rows updated with generated prices.`, life: 3000 });
    } else {
      toast.add({ severity: 'warn', summary: 'No data', detail: 'Server did not return generated prices.', life: 3000 });
    }
  } catch (err) {
    console.error('Failed to generate selling prices:', err);
    toast.add({ severity: 'error', summary: 'Error', detail: 'Could not auto-generate selling prices.', life: 4000 });
  } finally {
    isLoading.value = false;
  }
}

async function handleBeforeSetRange(e: RevoGridCustomEvent<any>) {
  const range = e.detail;
  // normalizar/obtener índices de filas seleccionadas
  // actualizar la variable global del componente
  const rows = getSelectedRowIndicesFromRange(range);
  selectedRows.value = rows;
  // save range
  const limits = getRangeLimits(range);
  selectedRangeLimits.value = limits;
}

function handleBeforeCellFocus(e: RevoGridCustomEvent<any>) {
  const clickedRowIndex = e?.detail?.rowIndex ?? null;
  const clickedColIndex = e?.detail?.colIndex ?? null;

  if (clickedRowIndex === null) {
    // no hay rowIndex válido; dejamos pasar por defecto
    return;
  }

  // Si tenemos límites y la fila clickeada está dentro del rango seleccionado...
  const limits = selectedRangeLimits.value;
  const inSelectedRange = limits &&
    clickedColIndex <= limits.maxCol &&
    clickedRowIndex <= limits.maxRow;

  console.log("selected rows more than one:", selectedRows.value.length > 1, "is right-click?:", isRightClickActive.value, "is selected range?:", inSelectedRange);
  // SI hay múltiples filas seleccionadas Y es right-click, NO hacer focus
  if (selectedRows.value.length > 1 && isRightClickActive.value && inSelectedRange) {
    console.log("🚫 PREVENTING FOCUS - Right-click on multiple selection");
    e.preventDefault();
    return;
  }
  
  console.log("✅ ALLOWING FOCUS - Single selection or right-click context menu");
  selectedRows.value = [];
}

function getSelectedRowIndicesFromRange(range: any): number[] {
  // Si no hay rango o está vacío, retornar array vacío
  if (!range || !Array.isArray(range) || range.length === 0) {
    return [];
  }

  // Extraer todos los rowIndex únicos del array de objetos
  const rowIndices = [...new Set(range.map((item: any) => item.rowIndex))].filter(idx => typeof idx === 'number');
  
  // Si no hay rowIndex válidos, retornar vacío
  if (rowIndices.length === 0) {
    return [];
  }

  // Encontrar el rango continuo desde el mínimo hasta el máximo rowIndex
  const minRow = Math.min(...rowIndices);
  const maxRow = Math.max(...rowIndices);
  
  // Generar array consecutivo de índices de filas
  const selectedRows: number[] = [];
  for (let i = minRow; i <= maxRow; i++) {
    selectedRows.push(i);
  }
  
  return selectedRows;
}

function getRangeLimits(range: any) {
  if (!range || !Array.isArray(range) || range.length === 0) return null;

  let minRow = Infinity;
  let maxRow = -Infinity;
  let minCol = Infinity;
  let maxCol = -Infinity;
  let found = false;

  for (const item of range) {
    const rRaw = item?.rowIndex ?? item?.row ?? item?.r;
    const cRaw = item?.colIndex ?? item?.col ?? item?.c;

    const r = Number.isFinite(Number(rRaw)) ? Number(rRaw) : null;
    const c = Number.isFinite(Number(cRaw)) ? Number(cRaw) : null;

    if (r !== null) {
      found = true;
      if (r < minRow) minRow = r;
      if (r > maxRow) maxRow = r;
    }

    if (c !== null) {
      found = true;
      if (c < minCol) minCol = c;
      if (c > maxCol) maxCol = c;
    }
  }

  if (!found) return null;

  return {
    minRow: minRow === Infinity ? null : minRow,
    maxRow: maxRow === -Infinity ? null : maxRow,
    minCol: minCol === Infinity ? null : minCol,
    maxCol: maxCol === -Infinity ? null : maxCol,
  };
}
      
</script>

<style>
/* Minimalist, professional header tooltip styles
   - Supports both in-header spans (.revogrid-header-tooltip)
     and body-level floating tooltips (.revogrid-header-tooltip-body)
*/
/* Shared base */
.revogrid-header-tooltip,
.revogrid-header-tooltip-body {
  font-family: Inter, ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
  font-size: 13px;
  font-weight: 500;
  line-height: 1;
  color: #0f172a; /* slate-900 text for contrast on light background */
  background: #ffffff; /* light card background for minimal look */
  border: 1px solid rgba(15, 23, 42, 0.06);
  box-shadow: 0 8px 24px rgba(2,6,23,0.12);
  padding: 8px 12px;
  border-radius: 8px;
  white-space: nowrap;
  pointer-events: none;
  transform-origin: center bottom;
}

/* Body-level tooltip (positioned in JS) */
.revogrid-header-tooltip-body {
  position: fixed;
  opacity: 0;
  transform: translateY(-16px) translateX(-50%);
  transition: opacity 180ms cubic-bezier(.2,.9,.2,1), transform 180ms cubic-bezier(.2,.9,.2,1);
  z-index: 20000;
  will-change: transform, opacity;
}

/* Decorative arrow for body tooltips */
.revogrid-header-tooltip-body::after {
  content: "";
  position: absolute;
  left: 50%;
  transform: translateX(-50%);
  bottom: -6px;
  width: 10px;
  height: 10px;
  background: inherit;
  border-left: 1px solid rgba(15,23,42,0.06);
  border-bottom: 1px solid rgba(15,23,42,0.06);
  transform-origin: center;
  transform: translateX(-50%) rotate(45deg);
}

/* Inline/in-header fallback tooltip (positioned via CSS) */
.rgHeaderCell .revogrid-header-tooltip,
[data-col-index] .revogrid-header-tooltip {
  position: absolute;
  left: 50%;
  top: 0;
  transform: translate(-50%, -18px) translateY(-16px);
  opacity: 0;
  transition: opacity 180ms cubic-bezier(.2,.9,.2,1), transform 180ms cubic-bezier(.2,.9,.2,1);
  z-index: 10002;
}

.rgHeaderCell { position: relative; overflow: visible; }
.rgHeaderCell:hover .revogrid-header-tooltip { opacity: 1; transform: translate(-50%, -28px) translateY(0); }

[data-col-index] { position: relative; overflow: visible; }
[data-col-index]:hover .revogrid-header-tooltip { opacity: 1; transform: translate(-50%, -28px) translateY(0); }

</style>
