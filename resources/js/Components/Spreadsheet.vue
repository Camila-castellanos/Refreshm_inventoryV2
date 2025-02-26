<template>
<ConfirmDialog></ConfirmDialog>
<Toast></Toast>
<section class="flex flex-col mt-[200px]">
  <section>
    <Button @click="createDevices">Save devices</Button>
  </section>

  <div class="spreadsheet-wrapper mt-8">
    <div ref="spreadsheet" class="spreadsheet-container"></div>
  </div>


</section>

</template>

<script setup>
import { ref, onMounted } from "vue";
import jspreadsheet from "jspreadsheet-ce";
import fetchVendors from "@/Pages/Vendors/VendorsData";
import { fetchStorages } from "@/Pages/Storages/StoragesIndexData";
import "jsuites/dist/jsuites.css";
import "jspreadsheet-ce/dist/jspreadsheet.css";
import { useConfirm } from "primevue/useconfirm";
import ConfirmDialog from 'primevue/confirmdialog';
import { useToast } from "primevue";
import { router } from "@inertiajs/vue3";

const confirm = useConfirm();
const toast = useToast();
const spreadsheet = ref(null);
let instance = null;
const storagesList = ref([]);
const vendorsList = ref([]);

const tableData = ref([]);

const columns = ref([
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

  Promise.all([
    fetchStorages(),
    fetchVendors()
  ]).then(([resStorages, resVendors]) => {
    const vendorNames = resVendors.data.map(vendor => vendor.vendor);
    vendorsList.value = resVendors.data;
    storagesList.value = resStorages.data;
    const updatedColumns = [...columns.value];
    const vendorColumn = updatedColumns.find(col => col.data === "vendor");
    if (vendorColumn) {
      vendorColumn.source = vendorNames;
    }

    columns.value = updatedColumns;

    instance = jspreadsheet(spreadsheet.value, {
      data: tableData.value.map(row => columns.value.map(col => row[col.data] || "")),
      columns: columns.value.map(col => ({ type: col.type, title: col.title, width: col.width, source: col.source || null })),
      allowInsertRow: true,
      allowInsertColumn: false,
      allowManualInsertRow: true,
      editable: true,
      contextMenu: (obj, x, y, e) => {
        return [
          {
            title: "Insert Row Above",
            onclick: () => instance.insertRow(1, y)
          },
          {
            title: "Insert Row Below",
            onclick: () => instance.insertRow(1, y + 1)
          },
          {
            title: "Delete Row",
            onclick: () => instance.deleteRow(y)
          },
          {
            title: "Insert 50 Rows Below", // 🚀 Nueva opción personalizada
            onclick: () => instance.insertRow(50, y + 1)
          }
        ];
      },
      onselection: (instance, cell, x, y, newValue) => {
      },
      oneditionstart: (instance, cell, x, y) => {
        if (y === 9) { 
          return false;
        }
      },
      oninsertrow: (ele, rowIndex, numOfRows) => renderPositions(numOfRows),
      ondeleterow: (ele, rowIndex, numOfRows) => renderPositions(numOfRows),
    });


    instance.setData([{}])
    renderPositions(1)

  }).catch(error => {
    console.error('Error fetching data:', error);
  });


});


const getData = () => {
  const rawData = instance.getData();
  const objectData = rawData.map(row => {
    let obj = {};
    columns.value.forEach((col, index) => {
      obj[col.data] = row[index];
    });
    return obj;
  });
};

function mapSpreadsheetData(spreadsheetData) {

  return spreadsheetData.map(row => {
    let mappedRow = {};
    Object.keys(row).forEach(index => {
      const column = columns.value[index];
      if (column) {
        if(index == 9) {
          console.log(index)
          mappedRow['storage_id'] = storagesList.value.find(item => item.name == row[index].split('-')[0].trim()).id;
          return;
        }

        if(index == 1) {
          mappedRow['vendor_id'] = vendorsList.value.find(item => item.vendor == row[index]).id;
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
        header: 'Confirmation',
        icon: 'pi pi-exclamation-triangle',
        rejectProps: {
            label: 'Cancel',
            severity: 'secondary',
            outlined: true
        },
        acceptProps: {
            label: 'Save'
        },
        accept: () => {
          submitSpreadsheet(mapSpreadsheetData(instance.getJson())).then((res => {
            toast.add({ severity: 'success', summary: 'Success', detail: 'Devices created successfully', life: 3000 });
            router.visit('/inventory/items')
          })).catch(err => {
            console.error(err)
            toast.add({ severity: 'error', summary: 'Error', detail: 'Something went wrong! Please try again', life: 3000 });

          })
        },
        reject: () => {
              
        }
    });

}

function getAvailableStorage(numOfRows) {
  return storagesList.value.find(storage => {
    const itemsInDB = storage.items.length;
    const itemsInTable = assignedPositions[storage.name] ? assignedPositions[storage.name].length : 0;
    return (itemsInDB + itemsInTable + numOfRows) < storage.limit;
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

    let storage = storagesList.value.find(storage => {
        const itemsInDB = storage.items.length;
        const itemsInTable = data.filter(row => row[9]?.startsWith(storage.name)).length;
        return (itemsInDB + itemsInTable + numOfRows) <= storage.limit;
    });

    if (!storage) {
        console.warn("⚠ No hay suficiente espacio en ningún almacenamiento disponible.");
        alert("No hay suficiente espacio en ningún almacenamiento disponible.");
        return;
    }

    for (let index = 0; index < data.length; index++) {
        let row = data[index];

        if (!assignedPositions[storage.name]) {
            assignedPositions[storage.name] = [];
        }

        const dbPositions = storage.items.map(item => item.position);
        const newAvailablePosition = getFirstAvailablePosition([...assignedPositions[storage.name], ...dbPositions]);

        assignedPositions[storage.name].push(newAvailablePosition);

        let label = `${storage.name} - ${newAvailablePosition} / ${storage.limit}`;

        let rowData = instance.getRowData(index);
        rowData[9] = label;
        instance.setRowData(index, rowData);
    }
}


async function submitSpreadsheet(body) {
     return axios.post("/inventory/items", { items: body }, { responseType: "blob" })
    }

</script>

<style>
.spreadsheet-wrapper {
  width: 100%;
  height: 90vh;
  overflow: hidden;
  /* Evita que el contenedor crezca fuera de la pantalla */
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
}

.spreadsheet-container {
  flex-grow: 1;
  /* Permite que la tabla crezca dentro del contenedor */
  overflow-y: auto;
  /* Activa el scroll vertical si el contenido excede el espacio */
  max-height: 90vh;
  /* Asegura que la tabla no sobrepase el contenedor */
}

.jexcel_contextmenu {

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
    font-family:sans-serif;
    font-size: 16px;
}

.jexcel_contextmenu div:hover {
    color: white !important; 
    font-weight: bold;
}
</style>