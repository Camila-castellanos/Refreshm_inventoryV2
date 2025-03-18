<template>
  <Toast></Toast>
  <section class="flex flex-col mt-[200px] w-full px-4">
    <section>
      <Button v-if="!props.initialData?.length" @click="createDevices">Save devices</Button>
      <Button v-else @click="editDevices">Update devices</Button>
    </section>

    <div class="spreadsheet-wrapper mt-8">
      <div ref="spreadsheet" class="spreadsheet-container"></div>
    </div>
  </section>
</template>

<script setup>
import { fetchStorages } from "@/Pages/Storages/StoragesIndexData";
import fetchVendors from "@/Pages/Vendors/VendorsData";
import { router } from "@inertiajs/vue3";
import jspreadsheet from "jspreadsheet-ce";
import "jspreadsheet-ce/dist/jspreadsheet.css";
import "jsuites/dist/jsuites.css";
import { useToast } from "primevue";
import { useConfirm } from "primevue/useconfirm";
import { onMounted, ref } from "vue";

const props = defineProps({
  initialData: { type: Array, required: false },
});

const confirm = useConfirm();
const toast = useToast();
const spreadsheet = ref(null);
let instance = null;
const storagesList = ref([]);
const vendorsList = ref([]);

const tableData = ref(props.initialData || []);

const columns = ref([
  { type: "hidden", data: "id" },
  { type: "calendar", title: "Date", data: "date", width: 120, options: { format: "YYYY-MM-DD" } },
  { type: "dropdown", title: "Vendor", data: "vendor", width: 150, source: ["Vendor A", "Vendor B"] },
  { type: "text", title: "Manufacturer", data: "manufacturer", width: 150 },
  { type: "text", title: "Model", data: "model", width: 150 },
  { type: "text", title: "Colour", data: "colour", width: 100 },
  { type: "text", title: "Battery", data: "battery", width: 120 },
  { type: "text", title: "Grade", data: "grade", width: 100 },
  { type: "text", title: "Issues", data: "issues", width: 150 },
  { type: "text", title: "IMEI", data: "imei", width: 150 },
  { type: "text", title: "Location", data: "location", width: 180, readOnly: true },
  { type: "numeric", title: "Cost", data: "cost", width: 100, mask: "#,##0.00", decimal: "." },
  { type: "numeric", title: "Selling Price", data: "selling_price", width: 120, mask: "#,##0.00", decimal: "." },
]);

onMounted(() => {
  Promise.all([fetchStorages(), fetchVendors()])
    .then(([resStorages, resVendors]) => {
      const vendorNames = resVendors.data.map((vendor) => vendor.vendor);
      vendorsList.value = resVendors.data;
      storagesList.value = resStorages.data;
      const updatedColumns = [...columns.value];
      const vendorColumn = updatedColumns.find((col) => col.data === "vendor");
      if (vendorColumn) {
        vendorColumn.source = vendorNames;
      }

      columns.value = updatedColumns;

      tableData.value =
        props.initialData && props.initialData.length > 0
          ? props.initialData.map((item) => {
              const storage = resStorages.data.find((s) => s.id === item.storage_id);
              return {
                ...item,
                location: `${storage?.name} - ${item.position}/${storage?.limit}`,
                vendor: resVendors.data.find((v) => v.id === item.vendor_id)?.vendor || "",
              };
            })
          : [];

      instance = jspreadsheet(spreadsheet.value, {
        data: tableData.value.map((row) => columns.value.map((col) => row[col.data] || "")),
        columns: columns.value.map((col) => ({
          type: col.type,
          title: col.title,
          width: col.width,
          source: col.source || null,
        })),
        allowInsertRow: !props.initialData || props.initialData?.length === 0,
        allowInsertColumn: false,
        allowManualInsertRow: !props.initialData || props.initialData?.length === 0,
        editable: true,
        contextMenu: (obj, x, y, e) => {
          return !props.initialData || props.initialData.length === 0
            ? [
                {
                  title: "Insert Row Above",
                  onclick: () => instance.insertRow(1, y),
                },
                {
                  title: "Insert Row Below",
                  onclick: () => instance.insertRow(1, y + 1),
                },
                {
                  title: "Delete Row",
                  onclick: () => instance.deleteRow(y),
                },
                {
                  title: "Insert 50 Rows Below",
                  onclick: () => instance.insertRow(50, y + 1),
                },
              ]
            : [];
        },
        onselection: (instance, cell, x, y, newValue) => {},
        onbeforechange: (instance, cell, x, y) => {
          if (x === "10") {
            return cell.value;
          }
        },
        oninsertrow: (ele, rowIndex, numOfRows) => renderPositions(numOfRows),
        ondeleterow: (ele, rowIndex, numOfRows) => renderPositions(numOfRows),
      });

      console.log(!props.initialData || props.initialData?.length === 0, props.initialData, props.initialData?.length);
      if (!props.initialData || props.initialData?.length === 0) {
        instance.setData([{}]);
        renderPositions(1);
      }
    })
    .catch((error) => {
      console.error("Error fetching data:", error);
    });
});

function mapSpreadsheetData(spreadsheetData) {
  return spreadsheetData.map((row) => {
    let mappedRow = {};
    Object.keys(row).forEach((index) => {
      const column = columns.value[index];
      if (column) {
        if (index == 10) {
          mappedRow["storage_id"] = storagesList.value.find((item) => item.name == row[index].split("-")[0].trim()).id;
          return;
        }

        if (index == 2) {
          mappedRow["vendor_id"] = vendorsList.value.find((item) => item.vendor == row[index])?.id;
          return;
        }
        mappedRow[column.data] = row[index];
      }
    });
    return mappedRow;
  });
}

function createDevices() {
  confirm.require({
    message: `Are you sure you want to add these devices?`,
    header: "Confirmation",
    icon: "pi pi-exclamation-triangle",
    rejectProps: {
      label: "Cancel",
      severity: "secondary",
      outlined: true,
    },
    acceptProps: {
      label: "Save",
    },
    accept: () => {
      submitSpreadsheet(mapSpreadsheetData(instance.getJson()))
        .then((res) => {
          toast.add({ severity: "success", summary: "Success", detail: "Devices created successfully", life: 3000 });
          router.visit("/inventory/items");
        })
        .catch((err) => {
          console.error(err);
          toast.add({ severity: "error", summary: "Error", detail: "Something went wrong! Please try again", life: 3000 });
        });
    },
    reject: () => {},
  });
}

function getFirstAvailablePosition(occupiedPositions) {
  let position = 1;
  while (occupiedPositions.includes(position)) {
    position++;
  }
  return position;
}

function renderPositions(numOfRows) {
  const data = instance.getData();
  const assignedPositions = [];

  let storage = storagesList.value.find((storage) => {
    const itemsInDB = storage.items.length;
    const itemsInTable = data.filter((row) => row[10]?.startsWith(storage.name)).length;
    return itemsInDB + itemsInTable + numOfRows <= storage.limit;
  });

  if (!storage) {
    console.warn("⚠ No hay suficiente espacio en ningún almacenamiento disponible.");
    alert("No hay suficiente espacio en ningún almacenamiento disponible.");
    return;
  }

  for (let index = 0; index < data.length; index++) {
    if (!assignedPositions[storage.name]) {
      assignedPositions[storage.name] = [];
    }

    const dbPositions = storage.items.map((item) => item.position);
    const newAvailablePosition = getFirstAvailablePosition([...assignedPositions[storage.name], ...dbPositions]);

    assignedPositions[storage.name].push(newAvailablePosition);

    let label = `${storage.name} - ${newAvailablePosition} / ${storage.limit}`;

    let rowData = instance.getRowData(index);
    rowData[10] = label;
    instance.setRowData(index, rowData);
  }
}

async function submitSpreadsheet(body) {
  return axios.post("/inventory/items", { items: body }, { responseType: "blob" });
}

const editDevices = async () => {
  if (!instance) return;

  const tableData = instance.getData().map((row, index) => {
    let obj = {};
    columns.value.forEach((col, colIndex) => {
      obj[col.data] = row[colIndex];
    });
    return obj;
  });

  const formattedData = tableData.map((row) => {
    return {
      id: row.id,
      manufacturer: row.manufacturer,
      model: row.model,
      colour: row.colour,
      battery: row.battery,
      grade: row.grade,
      issues: row.issues,
      imei: row.imei,
      storage_id: storagesList.value.find((item) => item.name === row.location.split("-")[0].trim())?.id,
      cost: parseFloat(row.cost),
      selling_price: row.selling_price,
      date: row.date,
      vendor_id: vendorsList.value.find((item) => item.vendor === row.vendor)?.id,
    };
  });

  try {
    await axios.post(route("items.update"), { items: formattedData }, { responseType: "blob" });
    toast.add({ severity: "success", summary: "Success", detail: "Devices updated successfully", life: 3000 });
    history.back();
  } catch (error) {
    toast.add({ severity: "error", summary: "Error", detail: "Something went wrong!", life: 3000 });
  }
};
</script>

<style>
.spreadsheet-wrapper {
  width: 100%;
  height: 90vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
}

.spreadsheet-container {
  flex-grow: 1;
  overflow-y: auto; /* Scroll vertical si la tabla es muy alta */
  overflow-x: auto; /* Agrega scroll horizontal en pantallas pequeñas */
  max-height: 90vh;
  max-width: 100%; /* Evita que se salga del viewport */
  white-space: nowrap; /* Evita que las celdas se rompan */
}

.jexcel_contextmenu div {
  color: #fff; /* Color de texto */
  padding: 8px 12px;
  color: white !important;
  font-weight: bold;
}

.jcontextmenu div {
  padding: 8px 12px;
  font-weight: bold !important;
  font-family: sans-serif;
  font-size: 16px;
}

.jexcel_contextmenu div:hover {
  color: white !important;
  font-weight: bold;
}
</style>
