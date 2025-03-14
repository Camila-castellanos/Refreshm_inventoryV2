<template>
  
  <DataTable :value="items">
    <Column field="model" header="Device"></Column>
    <Column field="issues" header="Issue"></Column>
    <Column field="imei" header="IMEI"></Column>
    <Column field="selling_price" header="Selling Price">
      <template #body="slotProps">
        <InputNumber v-model="slotProps.data.selling_price" class="w-full" mode="currency" currency="USD" />
      </template>
    </Column>
    <Column header="Actions">
      <template #body="slotProps">
        <Button icon="pi pi-trash" class="p-button-danger p-button-sm ml-2" @click="remove(slotProps.data)" />
      </template>
    </Column>
  </DataTable>
  <section class="flex justify-center items-center gap-3">
    <Button label="Confirm" class="p-button mt-4 ml-2" @click="submit" />
  </section>
</template>
<script setup lang="ts">
import { Item } from "@/Lib/types";
import axios from "axios";
import { Button, Column, ConfirmDialog, DataTable, InputNumber, useConfirm, useToast } from "primevue";
import { DynamicDialogInstance } from "primevue/dynamicdialogoptions";
import { inject, onMounted, ref, Ref } from "vue";

const confirm = useConfirm();
const toast = useToast();
const items: Ref<Item[]> = ref([]);
const saleId: Ref<string | number> = ref(0);
const customer: Ref<string | null> = ref(null);
const saleDate: Ref<string | Date | null> = ref(null);
const dialogRef = inject("dialogRef") as Ref<DynamicDialogInstance>;

onMounted(() => {
  items.value = dialogRef.value.data.items;
  saleId.value = dialogRef.value.data.saleId;
  customer.value = dialogRef.value.data.customer;
});

const remove = (item: Item) => {
  const updatedItems = new Set([...items.value]);
  updatedItems.delete(item);
  items.value = [...updatedItems];
};

const submit = async () => {
  confirm.require({
    message: "Are you sure you want to add these items?",
    header: "Confirm Addition",
    icon: "pi pi-exclamation-triangle",
    acceptClass: "p-button-danger",
    rejectClass: "p-button-secondary",
    accept: async () => {
      try {
        const response = await axios.post(route("payments.addNewItems"), {
          items: items.value,
          sale_id: saleId.value,
          customer: customer.value,
          sale_date: saleDate.value,
        });
        toast.add({ severity: "success", summary: "Success", detail: "Items added successfully!", life: 3000 });
        dialogRef.value.close(true);
      } catch (error: any) {
        toast.add({ severity: "error", summary: "Error", detail: error.response?.data || error.message, life: 4000 });
      }
    },
  });
};
</script>
