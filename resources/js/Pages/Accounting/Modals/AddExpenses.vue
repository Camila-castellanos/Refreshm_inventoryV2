<template>
  <form @submit.prevent="submitForm" class="flex flex-col gap-6">
    <div class="flex flex-col gap-4 max-h-96 overflow-y-auto p-2 border rounded">
      <div v-for="(expense, index) in expenses" :key="index" class="grid grid-cols-8 gap-4 border-b pb-4 relative">
        <Button
          :class="`!absolute bottom-2 py-2 right-0 ${index === 0 || isEditing ? '!hidden' : ''}`"
          severity="danger"
          size="small"
          icon="pi pi-times"
          @click="deleteExpense(index)" />
        <div class="col-span-2">
          <label class="block text-sm font-medium">Date:</label>
          <DatePicker v-model="(expense.date as Date)" class="w-full" showIcon dateFormat="yy-mm-dd" :max-date="new Date()" />
        </div>
        <div class="col-span-2">
          <label class="block text-sm font-medium">Name:</label>
          <InputText v-model="expense.name" class="w-full" placeholder="Enter name" />
        </div>
        <div class="col-span-2">
          <label class="block text-sm font-medium">Category:</label>
          <InputText v-model="expense.category" class="w-full" placeholder="Enter category" />
        </div>
        <div class="col-span-2">
          <label class="block text-sm font-medium">Amount:</label>
          <InputNumber v-model="expense.amount" class="w-full" mode="currency" currency="USD" @change="calcTotal" />
        </div>
        <div class="col-span-2">
          <label class="block text-sm font-medium">Tax (%):</label>
          <Select v-model="expense.tax" :options="taxes" optionLabel="name" class="w-full" placeholder="Add Tax" @change="calcTotal">
            <template #footer>
              <Button label="Add New Tax" icon="pi pi-plus" class="p-button-sm w-full mt-2" @click="addTax" />
            </template>
          </Select>
        </div>
        <div class="col-span-2">
          <label class="block text-sm font-medium">Total:</label>
          <InputNumber v-model="(expense.total as number)" class="w-full" mode="currency" currency="USD" disabled />
        </div>
      </div>
    </div>

    <div class="flex justify-center gap-3 mt-4">
      <Button label="Add More" icon="pi pi-plus" class="p-button-primary" @click="addExpense" v-show="!isEditing" />
      <Button label="Reset" class="p-button-danger" @click="resetForm" />
      <Button type="submit" label="Confirm" :loading="loading" />
    </div>

    <Divider align="center" v-if="!isEditing">OR</Divider>

    <div class="flex flex-col items-center mt-4" v-if="!isEditing">
      <label class="block text-sm font-medium mb-2">Upload Expenses via Excel/CSV:</label>
      <div class="flex justify-center gap-3 items-center">
        <FileUpload
          mode="basic"
          choose-label="Choose and upload"
          auto
          name="file"
          accept=".csv,.xls,.xlsx"
          class="!col-span-2"
          customUpload
          @select="handleFileUpload" />
        <Button label="Download Excel Demo" class="p-button-secondary col-span-1" @click="downloadDemo" :loading="loadingUpload" />
      </div>
    </div>
  </form>
</template>

<script setup lang="ts">
import { Expense, Tax } from "@/Lib/types";
import axios from "axios";
import { format } from "date-fns";
import { FileUpload, FileUploadSelectEvent, FileUploadUploadEvent, Select, useDialog } from "primevue";
import Button from "primevue/button";
import DatePicker from "primevue/datepicker";
import Divider from "primevue/divider";
import Dropdown from "primevue/dropdown";
import InputNumber from "primevue/inputnumber";
import InputText from "primevue/inputtext";
import { useToast } from "primevue/usetoast";
import { inject, onMounted, Ref, ref, watch, watchEffect } from "vue";
import AddTaxes from "./AddTaxes.vue";

const dialogRef: any = inject("dialogRef");
const expenses: Ref<Expense[]> = ref([]);
const selectedFile = ref(null);
const loadingUpload = ref(false);
const loading = ref(false);
const isEditing = ref(false);
const toast = useToast();
const dialog = useDialog();
const taxes: Ref<Tax[]> = ref([]);

onMounted(async () => {
  const taxResponse = await axios.get<Tax[]>(route("tax.list"));
  taxes.value = taxResponse.data.map((tax: Tax) => ({ ...tax, name: `${tax.name} (${tax.percentage}%)` }));

  if (dialogRef.value?.data?.expenses) {
    expenses.value = dialogRef.value.data.expenses.map((expense: any) => {
      console.log(expense, taxes.value);
      return { ...expense, tax: taxes.value.find((tax: Tax) => tax.id === expense.tax_id) };
    });
    isEditing.value = true;
  } else if (expenses.value.length === 0) {
    expenses.value = [{ date: null, name: "", category: "", amount: 0, tax: null, total: 0 }];
  }
});

const addExpense = () => {
  expenses.value.push({ date: null, name: "", category: "", amount: 0, tax: null, total: 0 });
};

const resetForm = () => {
  expenses.value = [{ date: null, name: "", category: "", amount: 0, tax: null, total: 0 }];
};

const handleFileUpload = (event: FileUploadSelectEvent) => {
  selectedFile.value = event.files[0];
  uploadFile();
};

const deleteExpense = (index: number) => {
  expenses.value.splice(index, 1);
};

const uploadFile = async () => {
  if (!selectedFile.value) {
    return;
  }

  loadingUpload.value = true;
  const formData = new FormData();
  formData.append("file", selectedFile.value);

  try {
    const response = await axios.post(route("expenses.excel.store"), formData, {
      headers: { "Content-Type": "multipart/form-data" },
    });
    toast.add({ severity: "success", summary: "Success", detail: "File uploaded successfully.", life: 3000 });
    dialogRef.value?.close();
  } catch (error: any) {
    toast.add({ severity: "error", summary: "Error", detail: error.response?.data?.message || "Upload failed", life: 3000 });
  } finally {
    loadingUpload.value = false;
  }
};

const submitForm = async () => {
  loading.value = true;
  console.log(expenses.value);
  const payload = {
    items: expenses.value.map((expense: any) => ({
      ...expense,
      date: format(expense.date, "yyyy-MM-dd"),
      tax_id: expense.tax.id,
      tax: expense.tax.percentage,
    })),
  };
  let routeUrl = route("expenses.store");
  if (isEditing.value) {
    routeUrl = route("expenses.update");
  }

  try {
    const response = await axios.post(routeUrl, payload, { responseType: "blob" });
    if (response.status >= 200 && response.status < 400) {
      toast.add({ severity: "success", summary: "Success", detail: "Expenses submitted successfully.", life: 3000 });
      dialogRef.value?.close();
    }
  } catch (error: any) {
    toast.add({ severity: "error", summary: "Error", detail: error.response?.data?.message || "Submission failed", life: 3000 });
  } finally {
    loading.value = false;
  }
};

function calcTotal() {
  expenses.value.forEach((expense: any) => {
    const tax = expense.tax ? Number(expense.tax.percentage) / 100 : 0;
    expense.total = expense.amount + expense.amount * tax;
  });
}

watch(
  () => expenses.value.map((bill) => ({ amount: bill.amount, tax: bill.tax })),
  (newAmounts, oldAmounts) => {
    if (newAmounts !== oldAmounts) {
      calcTotal();
    }
  }
);

const addTax = () => {
  dialog.open(AddTaxes, {
    data: { shouldReturnData: true },
    props: { header: "Add New Tax", style: { width: "450px" }, modal: true },
    onClose: (data) => {
      if (data?.data) {
        taxes.value.push(...data.data);
      }
    },
  });
};

const downloadDemo = () => {
  window.location.href = route("expenses.excel.demo.download");
};
</script>
