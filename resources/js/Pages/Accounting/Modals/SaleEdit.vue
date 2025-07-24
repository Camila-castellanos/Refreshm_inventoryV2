<template>
  
  <form class="w-full p-6 rounded-lg" @submit.prevent="onEdit">
    <div class="grid w-full grid-cols-6 gap-4">
      <div class="col-span-2">
        <label class="block mb-2 font-bold"> Date </label>
        <DatePicker v-model="form.date" showIcon fluid iconDisplay="input" />
      </div>

      <div class="col-span-2">
        <label class="block mb-2 font-bold"> Tax % </label>
        <Select v-model="form.tax" :options="taxes" optionLabel="name" placeholder="Select" class="w-full">
          <template #footer>
            <div class="p-3">
              <Button label="Add New Tax" fluid severity="secondary" text size="small" icon="pi pi-plus" @click="addTax" />
            </div>
          </template>
        </Select>
      </div>

      <div class="col-span-2">
        <label class="block mb-2 font-bold"> Customer </label>
          <Select
            v-model="form.customer"
            :options="customers"
            optionLabel="customer"
            placeholder="Select"
            class="w-full"
            filter
          >
            <template #option="slotProps">
              <div class="flex items-center">
                <div>{{ `${slotProps.option.customer}` }}</div>
              </div>
            </template>
            <template #footer>
              <div class="p-3">
                <Button label="Add New Customer" fluid severity="secondary" text size="small" icon="pi pi-plus" @click="addCustomer" />
              </div>
            </template>
          </Select>
      </div>

      <div class="col-span-3">
        <label for="icondisplay" class="block mb-2 font-bold"> Payment Method </label>
        <Select
          v-model="form.payment_method"
          :options="payment_method"
          option-value="name"
          optionLabel="name"
          placeholder="Select"
          class="w-full">
        </Select>
      </div>

      <div class="col-span-3">
        <label for="icondisplay" class="block mb-2 font-bold"> Payment Account </label>
        <Select
          v-model="form.payment_account"
          :options="payment_account"
          optionLabel="name"
          option-value="name"
          placeholder="Select"
          class="w-full">
        </Select>
      </div>

      <div class="col-span-6">
        <DataTable :value="tableData" tableStyle="min-width: 50rem" selection-mode="multiple" v-model:selection="selectedItems">
          <Column
            selection-mode="multiple"
            header-style="width: 3rem; text-align: center;"
            body-style="width: 3rem; text-align: center;"></Column>

             <!-- Columna para el tipo de item -->
  <Column field="type" header="Type">
    <template #body="slotProps">
      <span v-if="!slotProps.data.isNew">{{ getItemTypeLabel(slotProps.data.type) }}</span>
      <Dropdown
  v-else
  v-model="slotProps.data.type"
  :options="ITEM_TYPE_OPTIONS"
  optionLabel="label"
  optionValue="value"
/>
    </template>
  </Column>
          <Column field="model" header="Device">
  <template #body="slotProps">
    <template v-if="slotProps.data.isNew">
      <InputText v-model="slotProps.data.model" class="w-full" />
    </template>
    <template v-else>
      {{ slotProps.data.model }}
    </template>
  </template>
</Column>
          <Column field="issues" header="Issue">
            <template #body="slotProps">
              <template v-if="slotProps.data.isNew">
                <InputText v-model="slotProps.data.issues" class="w-full" />
              </template>
              <template v-else>
                {{ slotProps.data.issues }}
              </template>
            </template>
          </Column>
          <Column field="imei" header="IMEI">
            <template #body="slotProps">
              <template v-if="slotProps.data.isNew">
                <InputText v-model="slotProps.data.imei" class="w-full" />
              </template>
              <template v-else>
                {{ slotProps.data.imei }}
              </template>
            </template>
          </Column>
          <Column field="selling_price" header="Selling Price">
            <template #body="slotProps">
              <InputNumber v-model="slotProps.data.selling_price" class="w-full" mode="currency" currency="USD" locale="en-US" />
            </template>
          </Column>
          <Column header="Actions">
            <template #body="slotProps">
              <Button icon="pi pi-trash" class="p-button-danger p-button-sm ml-2" @click="returnItem(slotProps.data)" />
              <Button icon="pi pi-undo" class="p-button-secondary p-button-sm ml-2" @click="refundItem(slotProps.data)" />
            </template>
          </Column>
        </DataTable>
        <div class="flex space-x-2 my-3">
           <Button label="" icon="pi pi-plus" @click="addNewRow" />
          <Button label="Select Items" class="p-button-outlined" @click="addItem" />
          <Button v-if="form.credit > 0" label="Edit Credit" class="p-button-sm p-button-warning" @click="editCredit" />
          <Button
            v-if="Number(balance_remaining) > 0 && form.customer_credit > 0"
            label="Add Credit"
            class="p-button-sm p-button-success ml-2"
            @click="addCredit" />
          <Button
            v-if="selectedItems.length > 0"
            label="Delete Items"
            class="p-button-danger"
            icon="pi pi-trash"
            @click="
              () => {
                returnItems();
                deleteItems();
              }
            "/>
        </div>
      </div>

      <div class="col-span-6">
        <label class="block font-medium">Memo Notes</label>
        <Textarea v-model="form.memo_notes" class="w-full" placeholder="" rows="2" />
      </div>
      <div class="col-span-6">
        <div class="flex flex-col items-end justify-between">
          <span class="mt-1">
            <div>Subtotal: $ {{ subtotal.toFixed(2) }}</div>
          </span>
          <span class="mt-1">
            <div>Tax: $ {{ taxAmount.toFixed(2) }}</div>
          </span>
          <span class="mt-1">
            <div>Total: $ {{ total.toFixed(2) }}</div>
          </span>
          <span v-if="form.credit > 0" class="mt-1">
            <div>Credit: $ {{ parseFloat(final_credit).toFixed(2) }}</div>
          </span>
          <span class="mt-1">
            <div>Amount Paid: $ {{ amount_paid.toFixed(2) }}</div>
          </span>
          <span class="mt-1">
            <div>Balance Remaining: $ {{ balance_remaining }}</div>
          </span>
        </div>
      </div>

      <div class="col-span-6">
        <Button label="Save" class="w-full" type="submit" />
      </div>
    </div>
  </form>
</template>

<script setup lang="ts">
import { ref, onMounted, inject, computed, Ref } from "vue";
import axios from "axios";
import { useToast } from "primevue/usetoast";
import { Button, Column, ConfirmDialog, DataTable, DatePicker, InputNumber, Select, Textarea, useConfirm, useDialog, InputText } from "primevue";
import { Customer, Item, PaymentResponse, Tax } from "@/Lib/types";
import { DynamicDialogCloseOptions, DynamicDialogInstance } from "primevue/dynamicdialogoptions";
import AddTaxes from "./AddTaxes.vue";
import CreateEdit from "@/Pages/Customers/CreateEdit.vue";
import { format } from "date-fns";
import { ITEM_TYPE_OPTIONS, ItemType, getItemTypeLabel } from '@/Enums/ItemType';
import Dropdown from 'primevue/dropdown';

const toast = useToast();
const dialog = useDialog();
const confirm = useConfirm();
const dialogRef = inject("dialogRef") as Ref<DynamicDialogInstance>;

const saleId: Ref<number | string> = ref(dialogRef.value.data.saleId);
const customers: Ref<Customer[]> = ref([]);
const taxes: Ref<Tax[]> = ref([]);
const payment: Ref<PaymentResponse> = ref(dialogRef.value.data.payment);
const tableData: Ref<Item[]> = ref([]);
const form: Ref<{
  date: Date;
  tax: any;
  customer: any;
  payment_method: string;
  payment_account: string;
  memo_notes: string;
  credit: number;
  removed_credit: number;
  customer_credit: number;
}> = ref({
  date: new Date(),
  tax: "",
  customer: "",
  payment_method: "",
  payment_account: "",
  memo_notes: "",
  credit: 0,
  removed_credit: 0,
  customer_credit: 0,
});
const selectedItems: Ref<Item[]> = ref([]);

const payment_method = [
  {
    name: "Cash",
    id: 1,
  },
  {
    name: "Bank payment",
    id: 2,
  },
];

const payment_account = [
  {
    name: "Cash on hand",
    id: 1,
  },
];

onMounted(async () => {
  saleId.value = dialogRef.value.data.saleId;
  try {
    const [customersResponse, itemsResponse, taxesResponse] = await Promise.all([
      axios.get(route("customer.list")),
      axios.get(route("sales.sold", saleId.value)),
      axios.get(route("tax.list")),
    ]);
    customers.value = customersResponse.data;
    tableData.value = itemsResponse.data;
    taxes.value = taxesResponse.data.map((tax: Tax) => ({ ...tax, name: `${tax.name} (${tax.percentage}%)` }));
    payment.value = dialogRef.value.data.payment;
    console.log("Payment data:", payment.value);
    form.value.customer = payment.value.customer;
    form.value.payment_method = payment.value.payment_method;
    form.value.payment_account = payment.value.payment_account;
    form.value.tax = taxes.value.find((tax) => tax.id === payment.value.tax_id);
    form.value.customer = customers.value.find((customer) => customer.customer === payment.value.customer);
    form.value.credit = payment.value.customer_credit;
    form.value.memo_notes = payment.value.notes || "";
    form.value.discount = dialogRef.value.data.discount || 0;
    form.value.status = dialogRef.value.data.status || "";
    form.value.customer_id = dialogRef.value.data.customer_id || null;
    if (itemsResponse.data.length > 0) {
      form.value.date = new Date(itemsResponse.data[0].sold || new Date());
    }
  } catch (error) {
    console.error("Error fetching data:", error);
  }
});

function addCustomer() {
  dialog.open(CreateEdit, {
    props: {
      modal: true,
      header: "Create customer",
    },
    onClose: (newCustomer: DynamicDialogCloseOptions) => {
      if (newCustomer?.data) {
        customers.value.push({
          ...newCustomer.data,
          name: newCustomer.data.customer,
        });
      }
    },
  });
}

function addTax() {
  dialog.open(AddTaxes, {
    data: { shouldReturnData: true },
    props: { modal: true, header: "Add new tax" },
    onClose: (data) => {
      if (data?.data) {
        taxes.value.push(...data.data);
      }
    },
  });
}

const deleteItem = (item: Item) => {
  tableData.value = tableData.value.filter((i) => i !== item);
};

const deleteItems = () => {
  tableData.value = tableData.value.filter((item) => {
    let exclude = false;
    for (const selectedItem of selectedItems.value) {
      if (item.id == selectedItem.id) exclude = true;
    }
    return exclude;
  });
};

const returnItem = async (item: Item) => {
  if (item.isNew) {
    deleteItem(item);
    return;
  }
  confirm.require({
    message: `Are you sure you want to return the item "${item.model}"?`,
    header: "Confirmation",
    icon: "pi pi-exclamation-triangle",
    accept: async () => {
      try {
        const response = await axios.put(route("items.return"), { item });
        if (response.status >= 200 && response.status < 400) {
          toast.add({ severity: "success", summary: "Item Returned", detail: "The item has been returned successfully.", life: 3000 });
          deleteItem(item);
        }
      } catch (error: any) {
        toast.add({ severity: "error", summary: "Error", detail: error.response?.data || error.message, life: 4000 });
      }
    },
  });
};

const returnItems = async () => {
  if (selectedItems.value.length === 0) return;
  confirm.require({
    message: "Are you sure you want to return the selected items?",
    header: "Confirmation",
    icon: "pi pi-exclamation-triangle",
    accept: async () => {
      try {
        const response = await axios.put(route("items.return"), { selectedItems: selectedItems.value });
        if (response.status >= 200 && response.status < 400) {
          toast.add({
            severity: "success",
            summary: "Items Returned",
            detail: "Selected items have been returned successfully.",
            life: 3000,
          });
        }
      } catch (error: any) {
        console.error(error);
        toast.add({ severity: "error", summary: "Error", detail: error.response?.data || error.message, life: 4000 });
      }
    },
  });
};

const addItem = () => {
  window.location.assign(route("payments.edit", saleId.value));
};

const onEdit = async () => {
  let subTotal = subtotal.value;
  let flatTax = taxAmount.value;
  let totalAmount = total.value;

  const sale = {
    id: saleId.value,
    date: format(form.value.date, "yyyy-MM-dd"),
    discount: 0,
    customer: form.value.customer.customer,
    payment_method: form.value.payment_method,
    payment_account: form.value.payment_account,
    notes: form.value.memo_notes,
    items: tableData.value.filter(item => !item.isNew),
    newItems: tableData.value.filter(item => item.isNew),
    subtotal: subTotal,
    tax: form.value.tax.percentage,
    flatTax: flatTax.toFixed(2),
    total: totalAmount.toFixed(2),
    paid: payment.value.status === "paid" ? 1 : 0,
    amount_paid: parseFloat(String(amount_paid.value)).toFixed(2),
    balance_remaining: balance_remaining.value,
    credit: parseFloat(final_credit.value).toFixed(2),
    removed_credit: form.value.removed_credit || 0,
    tax_id: form.value.tax?.id || null,
  };

  try {
    console.log("Sale data to update:", sale);
    const response = await axios.post(route("sales.update"), sale);
    if (response.status >= 200 && response.status < 400) {
      toast.add({
        severity: "success",
        summary: "Sale Updated",
        detail: "The sale has been updated successfully.",
        life: 3000,
      });
      dialogRef.value.close();
    }
  } catch (error: any) {
    console.error("Error updating sale:", error);
    toast.add({ severity: "error", summary: "Error", detail: error.response?.data || error.message, life: 4000 });
  }
};

const refundItem = async (item: Item) => {
  confirm.require({
    message: `Are you sure you want to refund the item ${item.model}?`,
    header: "Confirmation",
    icon: "pi pi-exclamation-triangle",
    accept: async () => {
      try {
        const response = await axios.put(route("items.refund"), { item });
        if (response.status >= 200 && response.status < 400) {
          toast.add({ severity: "success", summary: "Item Refunded", detail: "The item has been refunded successfully.", life: 3000 });
          deleteItem(item);
        }
      } catch (error: any) {
        console.error(error);
        toast.add({ severity: "error", summary: "Error", detail: error.response?.data || error.message, life: 4000 });
      }
    },
  });
};

function round(num: number): number {
  return Math.round((num + Number.EPSILON) * 100) / 100;
}

const subtotal = computed(() => {
  return round(tableData.value.reduce((sum, item) => sum + (item.selling_price || 0), 0));
});

const taxAmount = computed(() => {
  return round(form.value.tax?.percentage ? (subtotal.value * form.value.tax.percentage) / 100 : 0);
});

const total = computed(() => round(subtotal.value + taxAmount.value));

const final_credit = computed(() => {
  const credit = parseFloat(form.value.credit?.toString() || '0')
  const removed = parseFloat(form.value.removed_credit?.toString() || '0')
  const taxPct = parseFloat(form.value.tax?.percentage?.toString() || '0')
  const creditWithTax = credit + (credit * taxPct) / 100
  return (creditWithTax - removed).toFixed(2)
})

const amount_paid = computed(() => {
  const raw = dialogRef.value.data.payment?.amount_paid;
  return raw ? round(Number(raw)) : 0;
});

const balance_remaining = computed(() => {
  let balance = total.value - amount_paid.value;
  balance -= parseFloat(final_credit.value);
  return round(balance);
});

const editCredit = () => {
  toast.add({ severity: "info", summary: "Edit Credit", detail: "Credit editing not implemented yet.", life: 3000 });
};

const addCredit = () => {
  if (Number(balance_remaining.value) > 0 && form.value.customer_credit > 0) {
    toast.add({ severity: "success", summary: "Credit Applied", detail: "Customer credit applied successfully.", life: 3000 });
  }
};

const newRowTemplate: Item = {
  ...{} as Item,
  issues: '',
  isNew: true,
};

function addNewRow() {
  tableData.value = [...tableData.value, { ...newRowTemplate }];
}
</script>
