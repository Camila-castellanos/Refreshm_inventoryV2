<template>
  <Toast />
  <TabView v-if="view === 'all'">
    <TabPanel header="Record Payment" :value="'record'">
      <form @submit.prevent="invoicePaid" class="flex flex-col gap-4 w-50">
        <div class="grid grid-cols-8 gap-4 bg-white p-3 rounded">
          <div class="col-span-12 flex items-center gap-2">
            <label class="w-2/6 text-right">Payment Date:</label>
            <Calendar v-model="paidDate" class="w-4/6" showIcon dateFormat="yy-mm-dd" :max-date="new Date()" />
          </div>
          <div class="col-span-12 flex items-center gap-2">
            <label class="w-2/6 text-right">Amount:</label>
            <InputNumber v-model="paidAmount" class="w-4/6" mode="currency" currency="USD" />
          </div>
          <div class="col-span-12 flex items-center gap-2">
            <label class="w-2/6 text-right">Payment Method:</label>
            <Dropdown v-model="paidPaymentMethod" :options="paymentMethods" class="w-4/6" />
          </div>
          <div class="col-span-12 flex items-center gap-2">
            <label class="w-2/6 text-right">Payment Account:</label>
            <Dropdown v-model="paidPaymentAccount" :options="paymentAccounts" class="w-4/6" />
          </div>
          <div class="col-span-12 flex items-center gap-2">
            <label class="w-2/6 text-right">Memo / notes:</label>
            <Textarea v-model="paidNotes" class="w-4/6" autoResize />
          </div>
        </div>
        <div class="flex justify-around mt-4">
          <Button type="submit" label="Confirm" class="p-button-primary" />
        </div>
      </form>
    </TabPanel>
    <TabPanel header="Payments Recorded" :value="'payments'">
      <DataTable title="Payments recorded" :headers="headers" :items="tableData" />
    </TabPanel>
  </TabView>
  <DataTable v-if="view === 'view'" title="Payments recorded" :headers="headers" :items="tableData" />
  <form @submit.prevent="invoicePaid" class="flex flex-col gap-4 w-50" v-if="view === 'record'">
    <div class="grid grid-cols-8 gap-4 bg-white p-3 rounded">
      <div class="col-span-12 flex items-center gap-2">
        <label class="w-2/6 text-right">Payment Date:</label>
        <Calendar v-model="paidDate" class="w-4/6" showIcon dateFormat="yy-mm-dd" :max-date="new Date()" />
      </div>
      <div class="col-span-12 flex items-center gap-2">
        <label class="w-2/6 text-right">Amount:</label>
        <InputNumber v-model="paidAmount" class="w-4/6" mode="currency" currency="USD" />
      </div>
      <div class="col-span-12 flex items-center gap-2">
        <label class="w-2/6 text-right">Payment Method:</label>
        <Dropdown v-model="paidPaymentMethod" :options="paymentMethods" class="w-4/6" />
      </div>
      <div class="col-span-12 flex items-center gap-2">
        <label class="w-2/6 text-right">Payment Account:</label>
        <Dropdown v-model="paidPaymentAccount" :options="paymentAccounts" class="w-4/6" />
      </div>
      <div class="col-span-12 flex items-center gap-2">
        <label class="w-2/6 text-right">Memo / notes:</label>
        <Textarea v-model="paidNotes" class="w-4/6" autoResize />
      </div>
    </div>
    <div class="flex justify-around mt-4">
      <Button type="submit" label="Confirm" class="p-button-primary" />
    </div>
  </form>
</template>

<script setup lang="ts">
import { inject, Ref, ref, watchEffect } from "vue";
import { useToast } from "primevue/usetoast";
import axios from "axios";
import DataTable from "@/Components/DataTable.vue";
import Calendar from "primevue/calendar";
import InputNumber from "primevue/inputnumber";
import Dropdown from "primevue/dropdown";
import Textarea from "primevue/textarea";
import Button from "primevue/button";
import TabView from "primevue/tabview";
import TabPanel from "primevue/tabpanel";
import Toast from "primevue/toast";
import { PaymentResponse } from "@/Lib/types";
import { format } from "date-fns";

const toast = useToast();
const dialogRef: any = inject("dialogRef");
const view = ref("all");
const paidId = ref(null);
const saleId = ref(null);
const paidDate: Ref<Date | null> = ref(null);
const paidAmount = ref(null);
const paidPaymentMethod = ref(null);
const paidPaymentAccount = ref(null);
const paidNotes = ref("");
const paymentMethods = ref(["Cash", "Bank Payment"]);
const paymentAccounts = ref(["Cash on Hand"]);
const tableData = ref<(PaymentResponse & any)[]>([]);

watchEffect(() => {
  if (dialogRef.value?.data) {
    view.value = dialogRef.value.data.view || "all";
    console.log(dialogRef.value.data);
    paidId.value = dialogRef.value.data.paidId || null;
    saleId.value = dialogRef.value.data.saleId || null;
    tableData.value = dialogRef.value.data.paidPayments.map((payment: PaymentResponse) => ({
      ...payment,
    }));
  }
});

const headers = [
  { label: "Date", name: "date", type: "text" },
  { label: "Amount", name: "paid", type: "text" },
  { label: "Payment Method", name: "payment_method", type: "text" },
  { label: "Actions", name: "actions", type: "actions" },
];

const invoicePaid = async () => {
  if (!paidAmount.value || paidAmount.value <= 0) {
    toast.add({ severity: "warn", summary: "Warning", detail: "Value Must be Valid !!", life: 3000 });
    return;
  }
  toast.add({
    severity: "info",
    summary: "Processing",
    detail: "This Payment transfer to paid...",
    life: 3000,
  });
  try {
    console.log(saleId.value);
    await axios.post(route("reports.payments.invoice.paid", { id: paidId.value }), {
      id: paidId.value,
      sale_id: saleId.value,
      amount: String(paidAmount.value),
      paidDate: format(paidDate.value as Date, "yyyy-MM-dd"),
      paidPaymentMethod: paidPaymentMethod.value,
      paidPaymentAccount: paidPaymentAccount.value,
      paidNotes: paidNotes.value,
    });
    toast.add({ severity: "success", summary: "Success", detail: "Paid Successful!", life: 3000 });
    dialogRef.value.close();
  } catch (error: any) {
    console.error(error);
    toast.add({ severity: "error", summary: "Error", detail: error.message, life: 3000 });
  }
};
</script>
