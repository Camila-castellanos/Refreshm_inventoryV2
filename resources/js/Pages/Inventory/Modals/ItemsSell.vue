<template>
  <form class="w-full p-6 rounded-lg" @submit.prevent="submitForm">
    <div class="grid w-full grid-cols-6 gap-4">
      <div class="col-span-2">
        <label for="icondisplay" class="block mb-2 font-bold"> Date </label>
        <DatePicker v-model="form.date" showIcon fluid iconDisplay="input" inputId="icondisplay" />
      </div>

      <div class="col-span-2">
        <label for="icondisplay" class="block mb-2 font-bold"> Tax % </label>
        <Select v-model="form.tax" :options="taxes" optionLabel="name" placeholder="Select" class="w-full">
          <template #option="slotProps">
            <div class="flex items-center">
              <div>
                {{ `${slotProps.option.name} - ${slotProps.option.percentage}%` }}
              </div>
            </div>
          </template>

          <template #footer>
            <div class="p-3">
              <Button label="Add New Tax" fluid severity="secondary" text size="small" icon="pi pi-plus" @click="addTax" />
            </div>
          </template>
        </Select>
      </div>

      <div class="col-span-2">
        <label for="icondisplay" class="block mb-2 font-bold"> Customer </label>
        <Select v-model="form.customer" :options="customers" optionLabel="name" placeholder="Select" class="w-full">
          <template #option="slotProps">
            <div class="flex items-center">
              <div>{{ `${slotProps.option.name}` }}</div>
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
        <Select v-model="form.payment_method" :options="payment_method" optionLabel="name" placeholder="Select" class="w-full"> </Select>
      </div>

      <div class="col-span-3">
        <label for="icondisplay" class="block mb-2 font-bold"> Payment Account </label>
        <Select v-model="form.payment_account" :options="payment_account" optionLabel="name" placeholder="Select" class="w-full"> </Select>
      </div>

      <div class="col-span-6">
        <DataTable :value="params.items" tableStyle="min-width: 50rem">
          <Column field="model" header="Device"></Column>
          <Column field="issues" header="Issue"></Column>
          <Column field="imei" header="IMEI"></Column>
          <Column field="selling_price" header="Selling Price"></Column>
        </DataTable>
      </div>

      <div class="col-span-6">
        <label class="block font-medium">Memo Notes</label>
        <Textarea v-model="form.memo_notes" class="w-full" placeholder="Insert" rows="2" />
      </div>
      <div class="col-span-6">
        <div class="flex flex-col items-end justify-between">
          <div class="flex justify-end w-full gap-2 py-2">
            <label class="block font-medium">Subtotal</label>
            <div class="text-lg font-bold">{{ subtotal }}$</div>
          </div>
          <div class="flex justify-end w-full gap-2 py-2">
            <label class="block font-medium">Tax</label>
            <div class="text-lg font-bold">{{ taxAmount }}$</div>
          </div>
          <div class="flex justify-end w-full gap-2 py-2">
            <label class="block font-medium">Total</label>
            <div class="text-lg font-bold">{{ total }}$</div>
          </div>
        </div>
      </div>

      <div class="flex w-full col-span-6 gap-2">
        <Button type="submit" @click="(e) => submitForm(e, false)" label="Save" class="w-1/2"></Button>
        <Button type="submit" @click="(e) => submitForm(e, true)" label="Confirm" class="w-1/2"></Button>
      </div>
    </div>
  </form>
</template>

<script setup lang="ts">
import { Tab } from "@/Lib/types";
import AddTaxes from "@/Pages/Accounting/Modals/AddTaxes.vue";
import CreateEdit from "@/Pages/Customers/CreateEdit.vue";
import { router, useForm } from "@inertiajs/vue3";
import axios from "axios";
import { DatePicker, Select, Textarea, useDialog } from "primevue";
import Column from "primevue/column";
import DataTable from "primevue/datatable";
import { computed, inject, onMounted, Ref, ref, watch } from "vue";

const dialog = useDialog();
// Subtotal: Sum of all selling prices
const subtotal = computed(() => {
  if (!params.value?.items) return 0;
  return params.value.items.reduce((sum: number, item: any) => sum + (item.selling_price || 0), 0);
});

const flatTax = computed(() => {
  if (!form.tax || !form.tax.percentage) return 0;
  return parseFloat((subtotal.value * (form.tax.percentage / 100)).toFixed(2));
});

// Tax: Calculate tax amount based on subtotal and selected tax percentage
const taxAmount = computed(() => {
  if (!form.tax || !form.tax.percentage) return 0;
  return parseFloat((subtotal.value * (form.tax.percentage / 100)).toFixed(2));
});

// Total: Subtotal + Tax
const total = computed(() => {
  return parseFloat((subtotal.value + taxAmount.value).toFixed(2));
});

const dialogRef: any = inject("dialogRef");

let params = ref<any>([]);
let customers = ref<any>([]);

onMounted(() => {
  params.value = dialogRef.value.data;
  getTaxes();
  parseCustomersData();
});

async function getTaxes() {
  const response = await axios.get(route("tax.list"));
  taxes.value = response.data;
}

function parseCustomersData() {
  if (!params.value.customers || params.value.customers.length == 0) return;
  customers.value = params.value.customers.map((customer: any) => {
    return {
      ...customer,
      name: `${customer.first_name} ${customer.last_name}`,
    };
  });
}

const form = useForm({
  date: new Date(),
  tax: "",
  customer: Object,
  payment_method: "",
  payment_account: "",
  memo_notes: "",
});

const taxes: Ref<Tab[]> = ref([]);

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

const formatDate = (date: Date) => {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");
  const hours = String(date.getHours()).padStart(2, "0");
  const minutes = String(date.getMinutes()).padStart(2, "0");
  const seconds = String(date.getSeconds()).padStart(2, "0");
  return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
};

function addCustomer() {
  dialog.open(CreateEdit, {
    props: {
      modal: true,
      header: "Create customer",
    },
    onClose: (newCustomer) => {
      console.log(newCustomer?.data);
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
    data: {shouldReturnData: true},
    props: { modal: true, header: "Add new tax" },
    onClose: (data) => {
      console.log(data);
      if (data?.data) {
         taxes.value.push(...data.data); 
      }
    },
  });
}

async function submitForm(e: Event, isConfirmed: boolean) {
  e.preventDefault();
  if (!params.value.items.length) {
    alert("No items selected for sale!");
    return;
  }

  const salePayload = {
    subtotal: subtotal.value,
    tax: form?.tax?.percentage || 0,
    total: total.value,
    discount: 0,
    flatTax: flatTax.value,
    payment_date: formatDate(form.date),
    memo_notes: form.memo_notes,
    payment_method: form.payment_method?.name || "",
    payment_account: form.payment_account?.name || "",
    tax_id: form.tax?.id || null,
    paid: isConfirmed ? 1 : 0, // 1 = Fully Paid, 0 = Unpaid
    balance_remaining: isConfirmed ? 0 : total.value,
    amount_paid: isConfirmed ? total.value : 0,
    items: params.value.items.map((item: any) => ({
      id: item.id,
      model: item.model,
      imei: item.imei,
      selling_price: item.selling_price,
      issues: item.issues,
      sold: formatDate(form.date),
      customer: form.customer.name,
      position: item.position,
      storage_id: item.storage_id,
      profit: item.selling_price - (item.cost || 0), // Ensure cost exists
    })),
    newItems: [],
  };

  try {
    const { data } = await axios.post<string>(route("sales.store"), salePayload);
    const link = document.createElement("a");
    link.href = data;
    link.target = "_blank";
    link.rel = "noopener noreferrer";
    document.body.appendChild(link);
    link.download = "receipt.pdf";
    link.click();
    document.body.removeChild(link);
    router.reload({ only: ["items"] });
    dialogRef.value.close();
  } catch (error) {
    console.error("Error submitting sale:", error);
  }
}
</script>
