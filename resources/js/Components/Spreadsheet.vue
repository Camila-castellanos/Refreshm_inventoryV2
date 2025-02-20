<template>

  <div class="spreadsheet-wrapper">
    <div ref="spreadsheet" class="spreadsheet-container"></div>
  </div>


  </template>
  
  <script setup>
  import { ref, onMounted } from "vue";
  import jspreadsheet from "jspreadsheet-ce";
  import fetchVendors from "@/Pages/Vendors/VendorsData";
  import { fetchStorages } from "@/Pages/Storages/StoragesIndexData";
  import "jsuites/dist/jsuites.css";
import "jspreadsheet-ce/dist/jspreadsheet.css";


  const spreadsheet = ref(null);
  let instance = null;
  
  // Datos iniciales como objetos
  const tableData = ref([
    {
      date: "2024-02-20",
      vendor: "Vendor A",
      manufacturer: "Apple",
      model: "iPhone 12",
      colour: "Black",
      battery: "3000mAh",
      grade: "A",
      issues: "None",
      imei: "123456789012345",
      location: "Bin #7 - 8 / 20",
      cost: 300,
      selling_price: 500,
    },
  ]);
  
  // Columnas configuradas con claves de los objetos
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
      onbeforeinsertrow: (el, rowNumber, numOfRows) => {
        if (true) {
            console.warn("⚠ Fila no permitida");
            alert('error')
            return false;
        }
    },
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
        console.log(`Cell [${x}, ${y}] changed to:`, newValue);
      },
      oninsertrow: () => renderPositions(),
      ondeleterow: () => renderPositions(),
    });

    }).catch(error => {
        console.error('Error fetching data:', error);
    });


  });
  
  // Agregar una nueva fila basada en la estructura de los objetos
  const addRow = () => {
    const newRow = {
      date: "",
      vendor: "",
      manufacturer: "",
      model: "",
      colour: "",
      battery: "",
      grade: "",
      issues: "",
      imei: "",
      location: "Pending",
      cost: 0,
      selling_price: 0,
    };
  
    // Convertir el objeto en array de valores según el orden de columnas
    const rowArray = columns.map(col => newRow[col.data] || "");
    instance.insertRow(rowArray);
  };
  
  // Obtener los datos de la tabla y convertirlos en objetos nuevamente
  const getData = () => {
    const rawData = instance.getData();
    const objectData = rawData.map(row => {
      let obj = {};
      columns.forEach((col, index) => {
        obj[col.data] = row[index];
      });
      return obj;
    });
    console.log("Spreadsheet Data as Objects:", objectData);
  };


  function renderPositions() {
    const hotInstance = this.$refs.hotTable.hotInstance;
    const data = hotInstance.getSourceData();

    const assignedPositions = {};

    function getFirstAvailablePosition(occupiedPositions) {
        let position = 1;
        while (occupiedPositions.includes(position)) {
            position++;
        }
        return position;
    }

    hotInstance.batch(() => {
        for (let index = 0; index < data.length; index++) {
            let row = data[index];
            let storage = this.storages_list.find(storage => {
                const itemsInDB = storage.items.length;
                const itemsInTable = assignedPositions[storage.name] ? assignedPositions[storage.name].length : 0;
                return (itemsInDB + itemsInTable) < storage.limit;
            });

            if (!storage) {
                console.warn(`Not enough storage space for ${index}`);

                Swal.fire({
                    title: "Storage Limit Exceeded",
                    text: "The spreadsheet has reached its storage capacity. Would you like to increase your storage limit?",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Yes",
                   }).then((result) => {
                    if(result.isConfirmed) {
                      const newTabUrl = `${window.location.origin}/user/profile#locations`;
                      window.open(newTabUrl, "_blank");
                    }
                   }) 

                break;
            }

            if (!assignedPositions[storage.name]) {
                assignedPositions[storage.name] = [];
            }

            const dbPositions = storage.items.map(item => item.position);
            const newAvaliablePosition = getFirstAvailablePosition([...assignedPositions[storage.name], ...dbPositions]);
            assignedPositions[storage.name].push(newAvaliablePosition);

            let label = `${storage.name} - ${newAvaliablePosition} / ${storage.limit}`;
            hotInstance.setDataAtCell(index, 9, label);
        }
    });

    hotInstance.render();
}



  </script>
  
  <style>
 .spreadsheet-wrapper {
    width: 100%;
    height: 90vh;
    overflow: hidden; /* Evita que el contenedor crezca fuera de la pantalla */
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}

.spreadsheet-container {
    flex-grow: 1; /* Permite que la tabla crezca dentro del contenedor */
    overflow-y: auto; /* Activa el scroll vertical si el contenido excede el espacio */
    max-height: 90vh; /* Asegura que la tabla no sobrepase el contenedor */
}
  </style>
  