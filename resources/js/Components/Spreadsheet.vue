<template>
    <div>
      <button @click="addRow">➕ Add Row</button>
      <button @click="getData">📋 Get Data</button>
      <div ref="spreadsheet"></div>
    </div>
  </template>
  
  <script setup>
  import { ref, onMounted } from "vue";
  import jspreadsheet from "jspreadsheet-ce";
  
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
  const columns = [
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
  ];
  
  onMounted(() => {
    instance = jspreadsheet(spreadsheet.value, {
      data: tableData.value.map(row => columns.map(col => row[col.data] || "")), // Convertimos los objetos en arrays para JSSpreadsheet
      columns: columns.map(col => ({ type: col.type, title: col.title, width: col.width, source: col.source || null })),
      allowInsertRow: true,
      allowInsertColumn: false,
      allowManualInsertRow: true,
      editable: true,
      onselection: (instance, cell, x, y, newValue) => {
        console.log(`Cell [${x}, ${y}] changed to:`, newValue);
      },
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
  </script>
  
  <style>
  /* Opcional: Ajuste del diseño */
  .jss_container {
    width: 100%;
    overflow-x: auto;
  }
  </style>
  