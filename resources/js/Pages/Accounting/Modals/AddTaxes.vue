<template>
  <form @submit.prevent="formSubmit" class="flex flex-col gap-4 w-50">
    <div v-for="(item, index) in newTaxes" :key="index" class="flex justify-evenly gap-4 items-center">
      <InputText v-model="item.name" placeholder="Name" class="w-full" />
      <InputNumber v-model="item.percentage" placeholder="Percentage" class="w-full" suffix="%" />
      <Button icon="pi pi-trash" class="p-button-danger p-button-text" @click="removeTax(index)" />
    </div>
    <Button icon="pi pi-plus" label="Add" class="p-button-outlined" @click="addNewTax" v-show="!isEditing" />
    <div class="flex justify-around mt-4">
      <Button type="submit" label="Confirm" class="p-button-primary" />
    </div>
  </form>
</template>

<script setup lang="ts">
import { inject, Ref, ref, watchEffect } from 'vue';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Button from 'primevue/button';
import axios from 'axios';
import { Tax } from '@/Lib/types';

const addDialog: any = inject('dialogRef');
const newTaxes: Ref<Tax[]> = ref([
  { id: Date.now(), name: '', percentage: 0, collected: 0, paid: 0, total_sales: 0, total_purchases: 0 }
]);
const isEditing = ref(false);
const shouldReturnData = ref(false);

// Watch for changes in addDialog and populate newTaxes when editing
watchEffect(() => {
  console.log(addDialog.value.data)
  if (addDialog.value?.data) {
    newTaxes.value = addDialog.value.data.taxes && addDialog.value.data.taxes.map((tax: Tax) => ({ ...tax, percentage: String(tax.percentage).split(' %')[0] })) || newTaxes.value;
    isEditing.value = addDialog.value.data.taxes && addDialog.value.data.taxes.length > 0;
    shouldReturnData.value = addDialog.value.data.shouldReturnData || false;
  }
});

const addNewTax = () => {
  newTaxes.value.push({ id: Date.now(), name: '', percentage: 0, collected: 0, paid: 0, total_sales: 0, total_purchases: 0 });
};

const removeTax = (index: number) => {
  newTaxes.value.splice(index, 1);
};

const formSubmit = async () => {
  try {
    const requestUrl = isEditing.value ? route('taxes.update') : route('taxes.store');
    const response = await axios.post(requestUrl, { taxes: newTaxes.value });

    console.log(shouldReturnData.value, response.data);
    if (shouldReturnData.value) {
      addDialog.value.close(response.data);
    } else {
      addDialog.value.close();
    }
  } catch (error) {
    console.error('Error processing taxes:', error);
  }
};
</script>
