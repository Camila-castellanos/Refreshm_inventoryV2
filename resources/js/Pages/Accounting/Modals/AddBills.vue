<template>
  <form @submit.prevent="submitForm" class="flex flex-col gap-6">
    <div class="flex flex-col gap-4 max-h-96 overflow-y-auto p-2 border rounded">
      <div v-for="(bill, index) in bills" :key="index" class="grid grid-cols-8 gap-4 border-b pb-4 relative">
        <Button
          :class="`!absolute bottom-2 py-2 right-0 ${index === 0 || isEditing ? '!hidden' : ''}`"
          severity="danger"
          size="small"
          icon="pi pi-times"
          @click="deleteBill(index)" />
        <div class="col-span-2">
          <label class="block text-sm font-medium">Date:</label>
          <DatePicker v-model="(bill.date as Date)" class="w-full" showIcon dateFormat="yy-mm-dd" :max-date="new Date()" />
        </div>
        <div class="col-span-2">
          <label class="block text-sm font-medium">Vendor:</label>
          <Select v-model="bill.vendor" :options="vendorOptions" optionLabel="vendor" class="w-full" placeholder="Select Vendor">
            <template #footer>
              <Button label="Add New Vendor" icon="pi pi-plus" class="p-button-sm w-full mt-2" @click="addVendor" />
            </template>
          </Select>
        </div>
        <div class="col-span-2">
          <label class="block text-sm font-medium">Subtotal:</label>
          <InputNumber v-model="bill.subtotal" class="w-full" mode="currency" currency="USD" />
        </div>
        <div class="col-span-2">
          <label class="block text-sm font-medium">Tax (%):</label>
          <Select v-model="bill.tax" :options="taxOptions" optionLabel="name" class="w-full" placeholder="Select Tax">
             <template #footer>
              <Button label="Add New Tax" icon="pi pi-plus" class="p-button-sm w-full mt-2" @click="addTax" />
            </template>
          </Select>
        </div>
        <div class="col-span-2">
          <label class="block text-sm font-medium">Total:</label>
          <InputNumber v-model="bill.total" class="w-full" mode="currency" currency="USD" disabled />
        </div>
        <div class="col-span-2">
          <label class="block text-sm font-medium">Bill/Invoice:</label>
          <InputText v-model="bill.invoice" class="w-full" placeholder="Enter Bill/Invoice" />
        </div>
      </div>
    </div>

    <div class="flex justify-center gap-3 mt-4">
      <Button label="Add More" class="p-button-primary" @click="addBill" icon="pi pi-plus" v-show="!isEditing" />
      <Button label="Reset" class="p-button-danger" @click="resetForm" />
      <Button type="submit" label="Confirm" :loading="loading" />
    </div>
  </form>
</template>

<script setup lang="ts">
import type { Tax, Vendor } from "@/Lib/types";
import CreateVendor from "@/Pages/Vendors/CreateVendor/CreateVendor.vue";
import fetchVendors from "@/Pages/Vendors/VendorsData";
import axios from "axios";
import { format } from "date-fns";
import { DatePicker, useDialog } from "primevue";
import Button from "primevue/button";
import InputNumber from "primevue/inputnumber";
import InputText from "primevue/inputtext";
import Select from "primevue/select";
import { useToast } from "primevue/usetoast";
import { inject, onMounted, ref, watch } from "vue";
import AddTaxes from "./AddTaxes.vue";

const dialogRef: any = inject("dialogRef");
const toast = useToast();
const dialog = useDialog();
const bills = ref<any[]>([]);
const taxOptions = ref<Tax[]>([]);
const vendorOptions = ref<Vendor[]>([]);
const loading = ref(false);
const isEditing = ref(false);

onMounted(async () => {
  try {
    const taxResponse = await axios.get<Tax[]>(route("tax.list"));
    taxOptions.value = taxResponse.data.map((tax: Tax) => ({ ...tax, name: `${tax.name} (${tax.percentage}%)` }));
    console.log(taxResponse.data);

    const vendorResponse = await axios.get<Vendor[]>(route("vendor.list"));
    vendorOptions.value = vendorResponse.data;
  } catch (error) {
    toast.add({ severity: "error", summary: "Error", detail: "Failed to load tax and vendor data", life: 3000 });
  } finally {
    if (dialogRef.value?.data?.bills) {
      bills.value = dialogRef.value.data.bills.map((bill: any) => ({
        ...bill,
        vendor: vendorOptions.value.find((vendor: any) => vendor.id == bill.vendor_id),
        subtotal: Number(bill.subtotal),
        tax: taxOptions.value.find((tax: any) => tax.id == bill.tax_id),
      }));
      isEditing.value = true;
      console.log(bills.value);
    } else {
      bills.value = [{ date: null, vendor: "", subtotal: 0, tax: null, total: 0, invoice: "" }];
    }
  }
});

const addBill = () => {
  bills.value.push({ date: null, vendor: "", subtotal: 0, tax: null, total: 0, invoice: "" });
};

const resetForm = () => {
  bills.value = [{ date: null, vendor: "", subtotal: 0, tax: null, total: 0, invoice: "" }];
};

const deleteBill = (index: number) => {
  bills.value.splice(index, 1);
};

const addVendor = () => {
  dialog.open(CreateVendor, {
    props: { header: "Add New Vendor", style: { width: "450px" }, modal: true },
    onClose: async () => {
      const { data } = await fetchVendors();
      vendorOptions.value = data;
    },
  });
};

const addTax = () => {
  dialog.open(AddTaxes, {
    data: { shouldReturnData: true },
    props: { header: "Add New Tax", style: { width: "450px" }, modal: true },
    onClose: (data) => {
      if (data?.data) {
        taxOptions.value.push(...data.data);
      }
    },
  });
};

const submitForm = async () => {
  loading.value = true;
  const payload = {
    items: bills.value.map((bill: any) => ({
      ...bill,
      date: format(bill.date, "yyyy-MM-dd"),
      tax: Number(bill.tax.percentage),
      vendor: bill.vendor.vendor,
      vendor_id: bill.vendor.id,
      status: bill.status === "Paid" ? 1 : 0,
    })),
  };
  let routeUrl = route("bills.store");
  if (isEditing.value) {
    routeUrl = route("bills.update");
  }

  try {
    const response = await axios.post(routeUrl, payload, { responseType: "blob" });
    if (response.status >= 200 && response.status < 400) {
      window.location.href = route("reports.bills.show");
    }
  } catch (error: any) {
    toast.add({ severity: "error", summary: "Error", detail: error.response?.data?.message || "Submission failed", life: 3000 });
  } finally {
    loading.value = false;
  }
};

function calcTotal() {
  bills.value.forEach((bill: any) => {
    const tax = bill.tax ? Number(bill.tax.percentage) / 100 : 0;
    bill.total = bill.subtotal + bill.subtotal * tax;
  });
}

watch(
  () => bills.value.map((bill) => ({ subtotal: bill.subtotal, tax: bill.tax })),
  (newAmounts, oldAmounts) => {
    if (newAmounts !== oldAmounts) {
      calcTotal();
    }
  }
);
</script>
