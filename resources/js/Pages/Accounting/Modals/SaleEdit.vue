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

  <!-- Diálogo de Input de Crédito -->
  <Dialog 
    v-model:visible="creditDialogVisible" 
    modal 
    :header="creditDialogMode === 'add' ? `Usable Credit: $${usableCredit.toFixed(2)}` : 'Edit Credit'" 
    :style="{ width: '28rem' }"
    class="p-fluid"
  >
    <div class="flex flex-col gap-4 p-4">
      <div class="flex flex-col gap-3">
        <label for="credit-amount" class="font-semibold text-center block">
          {{ creditDialogMode === 'add' ? 'Credit Amount' : 'Current Credit Amount' }}
        </label>
        <InputNumber 
          id="credit-amount"
          v-model="creditInputValue" 
          fluid
          class="relative left-1"
          mode="currency" 
          currency="USD" 
          locale="en-US"
          :max="creditDialogMode === 'add' ? usableCredit : undefined"
          :min="0"
          showButtons
          :step="0.01"
        />
      </div>
      <small v-if="creditDialogMode === 'add'" class="text-surface-500 text-center block">
        Maximum credit available: ${{ usableCredit.toFixed(2) }}
      </small>
      <small v-else class="text-surface-500 text-center block">
        Current credit: ${{ parseFloat(final_credit).toFixed(2) }}
      </small>
    </div>
    
    <template #footer>
      <Button 
        label="Cancel" 
        variant="outlined"
        severity="secondary" 
        @click="creditDialogVisible = false" 
      />
      <Button 
        label="Confirm" 
        variant="outlined"
        severity="secondary"
        :disabled="
          creditDialogMode === 'add'
            ? (creditInputValue === null || creditInputValue === undefined || creditInputValue <= 0 || creditInputValue > usableCredit)
            : (creditInputValue === null || creditInputValue === undefined || creditInputValue < 0)
        "
        @click="confirmCreditApplication" 
      />
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, onMounted, inject, computed, Ref, watch } from "vue";
import axios from "axios";
import { useToast } from "primevue/usetoast";
import { Button, Column, ConfirmDialog, DataTable, DatePicker, InputNumber, Select, Textarea, useConfirm, useDialog, InputText, Dialog } from "primevue";
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
    form.value.payment_method = payment.value.payment_method;
    form.value.payment_account = payment.value.payment_account;
    form.value.tax = taxes.value.find((tax) => tax.id === payment.value.tax_id);
    form.value.customer = customers.value.find((customer) => customer.customer === payment.value.customer);
    form.value.memo_notes = payment.value.notes || "";
    form.value.discount = dialogRef.value.data.discount || 0;
    form.value.status = dialogRef.value.data.status || "";
    form.value.customer_id = dialogRef.value.data.customer_id || null;
    form.value.credit = payment.value.credit;
    form.value.customer_credit = payment.value.customer_credit;
    console.log(balance_remaining.value, form.value.customer_credit)
    console.log("add credit bollean:", Number(balance_remaining) > 0 && form.customer_credit > 0)
    if (itemsResponse.data.length > 0) {
      form.value.date = new Date(itemsResponse.data[0].sold || new Date());
      form.value.sale_credit = itemsResponse.data[0].credit || 0;
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
     // El backend calculará flatTax y total basado en subtotal, crédito y tax
     paid: payment.value.status === "paid" ? 1 : 0,
     amount_paid: parseFloat(String(amount_paid.value)).toFixed(2),
     balance_remaining: balance_remaining.value,
     // Enviar el crédito SIN IVA - el backend aplicará la lógica de cálculo
     credit: parseFloat(final_credit.value).toFixed(2),
     credit_added: creditAdded.value.toFixed(2),
     tax_id: form.value.tax?.id || null,
   };
   console.log("Submitting sale update:", sale);
   try {
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
   return round(form.value.tax?.percentage ? ((subtotal.value - parseFloat(final_credit.value)) * form.value.tax.percentage) / 100 : 0);
});

const total = computed(() => round(subtotal.value - (parseFloat(final_credit.value) - taxAmount.value)));

const final_credit = computed(() => {
  // Solo devolver el crédito sin IVA para evitar doble aplicación
  const credit = Math.max(0, parseFloat(form.value.credit?.toString() || '0'));
  const removed = Math.max(0, parseFloat(form.value.removed_credit?.toString() || '0'));
  const net = credit - removed;
  // Garantizar que el crédito no sea negativo
  return Math.max(0, isNaN(net) ? 0 : net).toFixed(2);
})

// Si necesitas mostrar el crédito CON IVA en la UI, crea un computed separado:
const final_credit_with_tax = computed(() => {
  const credit = Math.max(0, parseFloat(form.value.credit?.toString() || '0'));
  const removed = Math.max(0, parseFloat(form.value.removed_credit?.toString() || '0'));
  const taxPct = Math.max(0, parseFloat(form.value.tax?.percentage?.toString() || '0'));
  const creditWithTax = credit + (credit * taxPct) / 100;
  const net = creditWithTax - removed;
  // Garantizar que el crédito no sea negativo (aun con IVA considerado para UI)
  return Math.max(0, isNaN(net) ? 0 : net).toFixed(2);
})

const amount_paid = computed(() => {
  const raw = dialogRef.value.data.payment?.amount_paid;
  return raw ? round(Number(raw)) : 0;
});

const balance_remaining = computed(() => {
  const totalValue = isNaN(total.value) ? 0 : total.value;
  const amountPaidValue = isNaN(amount_paid.value) ? 0 : amount_paid.value;
  
  let balance = totalValue - amountPaidValue;
  
  const result = round(balance);
  const finalResult = isNaN(result) ? 0 : result;
  
  return finalResult;
});

// Simple watcher para imprimir los valores solicitados
watch([subtotal, final_credit, taxAmount, total], ([s, fc, t, tot]) => {
  try {
    console.log("subtotal:", s);
    console.log("credit applied:", fc);
    console.log("tax amount:", t);
    console.log("resta de credito menos tax amount:", round((parseFloat(fc as unknown as string) || 0) - t));
    console.log("total:", tot);
  } catch (e) {
    console.warn('[SaleEdit] watch simple logging error', e);
  }
});

const creditDialogVisible = ref(false);
const creditInputValue = ref(0);
const creditDialogMode = ref<'add' | 'edit'>('add');
// Nueva variable para trackear solo el crédito agregado
const creditAdded = ref(0);

const usableCredit = computed(() => {
  const customerCreditNum = parseFloat(form.value.customer_credit.toString());
  const balanceNum = parseFloat(balance_remaining.value.toString());
  return customerCreditNum >= balanceNum ? balanceNum : customerCreditNum;
});

const addCredit = () => {
  if (Number(balance_remaining.value) > 0 && form.value.customer_credit > 0) {
    creditDialogMode.value = 'add';
    creditInputValue.value = 0;
    creditDialogVisible.value = true;
  }
};

const editCredit = () => {
  creditDialogMode.value = 'edit';
  creditInputValue.value = parseFloat(form.value.credit?.toString() || '0');
  creditDialogVisible.value = true;
};

const confirmCreditApplication = () => {
  if (creditDialogMode.value === 'add') {
    if (creditInputValue.value > 0) {
      // Agregar al crédito existente
      const sum = parseFloat(creditInputValue.value.toString()) + parseFloat(form.value.credit?.toString() || '0');
      // Asegurar que el crédito no quede negativo por ningún motivo
      form.value.credit = Math.max(0, sum);
      
      // Actualizar solo el crédito agregado (acumulativo)
      creditAdded.value += creditInputValue.value;
      
      // Reducir el crédito del cliente
      form.value.customer_credit = form.value.customer_credit - creditInputValue.value;
      
      toast.add({ 
        severity: "success", 
        summary: "Credit Applied", 
        detail: `$${creditInputValue.value.toFixed(2)} credit has been applied successfully.`, 
        life: 3000 
      });
    }
  } else {
    // Modo edición - calcular la diferencia con el crédito original
    const originalCredit = parseFloat(payment.value.credit?.toString() || '0');
    const newCredit = Math.max(0, creditInputValue.value || 0);
    
    form.value.credit = newCredit;
    
    // Calcular solo el crédito agregado respecto al original
    creditAdded.value = newCredit - originalCredit;
    
    toast.add({ 
      severity: "success", 
      summary: "Credit Updated", 
      detail: `Credit has been updated to $${creditInputValue.value.toFixed(2)}.`, 
      life: 3000 
    });
  }
  
  creditDialogVisible.value = false;
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
