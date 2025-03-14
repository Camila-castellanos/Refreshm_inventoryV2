<template>
  
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
  <form @submit.prevent="submitPaymentEdit" class="flex flex-col gap-4 w-50" v-if="view === 'record'">
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
import DataTable from "@/Components/DataTable.vue";
import { PaymentResponse } from "@/Lib/types";
import axios from "axios";
import { format } from "date-fns";
import { ConfirmDialog, useConfirm, useDialog } from "primevue";
import Button from "primevue/button";
import Calendar from "primevue/calendar";
import Dropdown from "primevue/dropdown";
import InputNumber from "primevue/inputnumber";
import TabPanel from "primevue/tabpanel";
import TabView from "primevue/tabview";
import Textarea from "primevue/textarea";
import { useToast } from "primevue/usetoast";
import { inject, onMounted, Ref, ref } from "vue";
import ShowBillPayments from "./ShowBillPayments.vue";
import {router} from "@inertiajs/vue3"

const toast = useToast();
const dialog = useDialog();
const confirm = useConfirm();

const dialogRef: any = inject("dialogRef");
const view = ref("all");
const paidId: Ref<any> = ref(null);
const paidDate: Ref<Date | null> = ref(null);
const paidAmount: Ref<number | null> = ref(null);
const paidPaymentMethod: Ref<string | null> = ref(null);
const paymentId: Ref<number | null> = ref(null);
const paidPaymentAccount: Ref<string | null> = ref(null);
const paidNotes = ref("");
const paymentMethods = ref(["Cash", "Bank Payment"]);
const paymentAccounts = ref(["Cash on hand"]);
const tableData = ref<(PaymentResponse & any)[]>([]);

onMounted(() => {
  if (dialogRef.value?.data) {
    view.value = dialogRef.value.data.view || "all";
    paidId.value = dialogRef.value.data.paidId || null;
    if (view.value !== "record") {
      tableData.value = dialogRef.value.data.paidPayments.map((payment: PaymentResponse) => ({
        ...payment,
        date: format(new Date(payment.date), "yyyy-MM-dd"),
        actions: [
          {
            label: "",
            icon: "pi pi-pencil",
            action: () => {
              dialog.open(ShowBillPayments, {
                data: {
                  payment: payment,
                  view: "record",
                },
                props: {
                  modal: true,
                },
                onClose: () => {
                  dialogRef.value.close();
                },
              });
            },
          },
          {
            label: "",
            icon: "pi pi-trash",
            severity: "danger",
            action: () => {
              removePayment(payment.id, Number(payment.amount_paid));
            },
          },
        ],
      }));
    }

    if (dialogRef.value.data.payment) {
      const payment: PaymentResponse = dialogRef.value.data.payment;
      paymentId.value = payment.id;
      paidDate.value = payment.date ? new Date(payment.date) : null;
      paidAmount.value = Number(payment.amount_paid) || null;
      paidPaymentMethod.value = payment.payment_method || null;
      paidPaymentAccount.value = payment.payment_account || null;
    }
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
    await axios.post(route("bills.record.payment", { id: paidId.value }), {
      id: paidId.value,
      amount: String(paidAmount.value),
      payment_date: format(paidDate.value as Date, "yyyy-MM-dd"),
      payment_method: paidPaymentMethod.value,
      payment_account: paidPaymentAccount.value,
      payment_notes: paidNotes.value,
    });
    toast.add({ severity: "success", summary: "Success", detail: "Paid Successful!", life: 3000 });
    dialogRef.value.close();
  } catch (error: any) {
    console.error(error);
    toast.add({ severity: "error", summary: "Error", detail: error.message, life: 3000 });
  }
};

const submitPaymentEdit = async () => {
  try {
    toast.add({
      severity: "info",
      summary: "Processing...",
      detail: "Updating invoice payment...",
      life: 3000,
    });

    const response = await axios.post(route("bills.edit.payment"), {
      id: paymentId.value,
      payment_amount: paidAmount.value,
      payment_method: paidPaymentMethod.value,
      payment_account: paidPaymentAccount.value,
      payment_date: format(paidDate.value as Date, "yyyy-MM-dd"),
    });

    if (response.status >= 200 && response.status < 400) {
      toast.add({
        severity: "success",
        summary: "Edition Successful!",
        detail: "The invoice payment has been updated.",
        life: 3000,
      });

      setTimeout(() => {
        dialogRef.value.close();
      }, 1500);
    }
  } catch (error: any) {
    let errorMessage = "An unexpected error occurred.";
    if (error.response) {
      errorMessage = error.response.data;
    } else if (error.request) {
      errorMessage = "No response from the server.";
    } else {
      errorMessage = error.message;
    }

    toast.add({
      severity: "error",
      summary: "An Error has occurred!",
      detail: "Please try again or report the issue.",
      life: 4000,
    });

    paidId.value = "";
  }
};

const removePayment = async (paymentId: number, paymentAmount: number) => {
  confirm.require({
    message: "Are you sure you want to remove this payment?",
    header: "Confirm Removal",
    icon: "pi pi-exclamation-triangle",
    acceptClass: "p-button-danger",
    rejectClass: "p-button-secondary",
    accept: async () => {
      try {
        toast.add({
          severity: "info",
          summary: "Processing...",
          detail: "Removing invoice payment...",
          life: 3000,
        });

        const response = await axios.post(route("bills.remove.payment"), {
          id: paymentId,
          payment_amount: paymentAmount,
        });

        if (response.status >= 200 && response.status < 400) {
          toast.add({
            severity: "success",
            summary: "Removed!",
            detail: "The invoice payment has been removed successfully.",
            life: 3000,
          });
          tableData.value = tableData.value.filter(value => value.id !== paymentId)
          router.reload();
        }
      } catch (error: any) {
        let errorMessage = "An unexpected error occurred.";
        if (error.response) {
          errorMessage = error.response.data;
        } else if (error.request) {
          errorMessage = "No response from the server.";
        } else {
          errorMessage = error.message;
        }

        toast.add({
          severity: "error",
          summary: "An Error has occurred!",
          detail: errorMessage,
          life: 4000,
        });

        paidId.value = "";
      }
    },
    reject: () => {
      toast.add({
        severity: "info",
        summary: "Cancelled",
        detail: "The removal action was cancelled.",
        life: 3000,
      });
    },
  });
};
</script>
