import { CustomField } from "@/Lib/types";
import { ref, Ref } from "vue";

export const headers: Ref<CustomField[]> = ref([
  {
    label: "Date",
    name: "date",
    type: "string",
  },
  {
    label: "Vendor",
    name: "vendor",
    type: "string",
  },
  {
    label: "Manufacturer",
    name: "manufacturer",
    type: "string",
  },
  {
    label: "Model",
    name: "model",
    type: "string",
  },
  {
    label: "Colour",
    name: "colour",
    type: "string",
  },
  {
    label: "Battery",
    name: "battery",
    type: "string",
  },
  {
    label: "Grade",
    name: "grade",
    type: "string",
  },
  {
    label: "Issues",
    name: "issues",
    type: "string",
  },
  {
    label: "IMEI/Serial",
    name: "imei",
    type: "string",
  },
  {
    label: "Cost",
    name: "cost",
    type: "number",
  },
  {
    label: "Selling Price",
    name: "selling_price",
    type: "number",
  },
  {
    label: "Location",
    name: "location",
    type: "string",
  },
]);

export const soldHeaders: Ref<CustomField[]> = ref([
  {
    label: "Sale Date",
    name: "sold",
    type: "string",
  },
  {
    label: "Purchase Date",
    name: "date",
    type: "date",
  },
  {
    label: "Vendor",
    name: "vendor",
    type: "string",
  },
  {
    label: "Customer",
    name: "customer",
    type: "string",
  },
  {
    label: "Manufacturer",
    name: "manufacturer",
    type: "string",
  },
  {
    label: "Model",
    name: "model",
    type: "string",
  },
  {
    label: "Colour",
    name: "colour",
    type: "string",
  },
  {
    label: "Battery",
    name: "battery",
    type: "string",
  },
  {
    label: "Grade",
    name: "grade",
    type: "string",
  },
  {
    label: "Issues",
    name: "issues",
    type: "string",
  },
  {
    label: "IMEI/Serial",
    name: "imei",
    type: "string",
  },
  {
    label: "Location",
    name: "location",
    type: "string",
  },
  {
    label: "Cost",
    name: "cost",
    type: "string",
  },
  {
    label: "Subtotal",
    name: "subtotal",
    type: "string",
  },
  {
    label: "Total",
    name: "total",
    type: "string",
  },
  {
    label: "Profit",
    name: "profit",
    type: "string",
  },
  {
    label: "Actions",
    name: "actions",
    type: "string",
  },
]);

export const onHoldHeaders: Ref<CustomField[]> = ref([
  {
    label: "Date",
    name: "date",
    type: "date",
  },
  {
    label: "Vendor",
    name: "vendor",
    type: "string",
  },
  {
    label: "Manufacturer",
    name: "manufacturer",
    type: "string",
  },
  {
    label: "Customer",
    name: "customer",
    type: "string",
  },
  {
    label: "Manufacturer",
    name: "manufacturer",
    type: "string",
  },
  {
    label: "Model",
    name: "model",
    type: "string",
  },
  {
    label: "Colour",
    name: "colour",
    type: "string",
  },
  {
    label: "Battery",
    name: "battery",
    type: "string",
  },
  {
    label: "Grade",
    name: "grade",
    type: "string",
  },
  {
    label: "Issues",
    name: "issues",
    type: "string",
  },
  {
    label: "IMEI/Serial",
    name: "imei",
    type: "string",
  },
  {
    label: "Cost",
    name: "cost",
    type: "number",
  },
  {
    label: "Selling Price",
    name: "selling_price",
    type: "number",
  },
  {
    label: "Location",
    name: "location",
    type: "string",
  },
]);