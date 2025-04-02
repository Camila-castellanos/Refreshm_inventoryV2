<template>
  <Toast></Toast>
  <div class="max-w-7xl mx-auto py-4">
    <div class="w-full flex justify-end pb-4">
      <Dialog v-model:visible="showSelectedItems" header="Selected items" :modal="true" class="mx-4">

        <div class="max-w-7xl mx-auto ">

          <div class="flex flex-col gap-4">
            <div class="flex flex-col sm:flex-row gap-4">
              <div class="flex-1">
                <label for="name" class="block text-gray-700 text-sm font-bold mb-1">Name:</label>
                <InputText id="name" type="text" v-model="name"
                  class="w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
              </div>
              <div class="flex-1">
                <label for="email" class="block text-gray-700 text-sm font-bold mb-1">E-mail:</label>
                <InputText id="email" type="email" v-model="email"
                  class="w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
              </div>
            </div>
            <div class="flex flex-col sm:flex-row gap-4">
              <div class="flex-1">
                <label for="notes" class="block text-gray-700 text-sm font-bold mb-1">Memo / notes:</label>
                <Textarea id="notes" v-model="notes" rows="3"
                  class="w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></Textarea>
              </div>
              <div class="flex-1">
                <label for="store" class="block text-gray-700 text-sm font-bold mb-1">Store:</label>
                <InputText id="store" type="text" v-model="store"
                  class="w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
              </div>
            </div>
          </div>

          <div class="flex flex-col">


            <DataTable :value="selectedItems" responsiveLayout="scroll" class="p-datatable-striped">
              <Column field="model" header="Model" class="font-semibold hidden sm:table-cell" />
              <Column field="manufacturer" header="Manufacturer" class="hidden sm:table-cell" />
              <Column field="grade" header="Grade" class="hidden sm:table-cell">
                <template #body="{ data }">
                  <Tag v-if="data.grade" :value="data.grade" severity="info" class="font-medium" />
                </template>
              </Column>
              <Column field="battery" header="Battery" class="hidden sm:table-cell">
                <template #body="{ data }">
                  <Tag v-if="data.battery" :value="data.battery + '%'" severity="success" class="font-medium" />
                </template>
              </Column>
              <Column field="colour" header="Colour" class="hidden lg:table-cell">
                <template #body="{ data }">
                  <Tag v-if="data.colour" :value="data.colour" class="font-medium" />
                </template>
              </Column>
              <Column field="issues" header="Issues" class="hidden md:table-cell">
                <template #body="{ data }">
                  <div v-if="data.issues" class="text-orange-500 text-sm italic">
                    Note: {{ data.issues }}
                  </div>
                </template>
              </Column>

              <Column field="model" header="Model" class="sm:hidden font-semibold" />
              <Column field="selling_price" header="Price">
                <template #body="{ data }" class="text-right">
                  <div class="text-xl font-bold text-black">${{ data.selling_price.toFixed(2) }}</div>
                </template>
              </Column>

              <Column headerStyle="width:6rem" class="text-right">
                <template #body="{ data }">
                  <Button icon="pi pi-trash"
                    class="p-button-rounded p-button-outlined p-button-secondary cursor-pointer "
                    style="border-color: black; color: black;" @click="removeItem(data)" />
                </template>
              </Column>
            </DataTable>
          </div>


        </div>

        <h2 class="text-2xl justify-self-end font-black p-4 text-black">Total: ${{selectedItems.reduce((accumulator,
          currentItem) => {
          return accumulator + currentItem.selling_price;
        }, 0)}},00</h2>


        <div class="flex w-full justify-around">
          <Button severity="secondary" @click="showSelectedItems = false">CANCEL</Button>
          <Button @click="onSubmit">REQUEST DEVICES</Button>
        </div>

      </Dialog>
      <Button @click="getSelectedItems">REQUEST DEVICES</Button>
    </div>


    <Card class="shadow-sm">
      <template #content>
        <div class="flex flex-col">
          <div class="flex flex-col md:flex-row justify-between items-stretch md:items-center gap-4 mb-6">
            <IconField>
              <InputIcon>
                <i class="pi pi-search" />
              </InputIcon>
              <InputText v-model="searchQuery" placeholder="Search" class="w-full" />
            </IconField>
            <div class="flex items-center gap-2">
              <!-- <Button icon="pi pi-filter" label="Filter" class="p-button-outlined" /> -->
            </div>
          </div>

          <div class="mb-4 text-gray-500 text-xl">
            Displaying {{ filteredItems.length }} Items
          </div>

          <div class="space-y-4">


            <div v-for="item in filteredItems" :key="item.id">
              <Card class="hover:shadow-md transition-shadow duration-200">
                <template #content>
                  <div class="flex flex-col md:flex-row justify-between items-start md:items-center p-2">
                    <div class="space-y-4">
                      <h2 class="text-2xl font-semibold"> {{ item.model }}</h2>
                      <div class="flex flex-wrap gap-2">
                        <Tag :value="item.manufacturer" class="font-medium" />
                        <Tag v-if="item.grade" :value="item.grade" severity="info" class="font-medium" />
                        <Tag v-if="item.battery" :value="item.battery + '%'" severity="success" class="font-medium" />
                        <Tag v-if="item.colour" :value="item.colour" class="'font-medium" />
                      </div>
                    </div>
                    <div
                      class="flex flex-col md:flex-row justify-end items-start md:items-center gap-4 mt-4 md:mt-0 w-full md:w-auto">
                      <div v-if="item.issues" class="text-orange-500 text-sm italic mr-4">
                        Note: {{ item.issues }}
                      </div>
                      <div class="text-xl md:text-2xl font-bold text-black">${{ item.selling_price.toFixed(2) }}</div>
                      <Button v-if="item?.selected != true" icon="pi pi-plus"
                        class="p-button-rounded p-button-outlined p-button-secondary self-end cursor"
                        style="border-color: black; color: black;" @click="addItem(item)" />
                      <Button v-if="item.selected" icon="pi pi-check"
                        class="p-button-rounded p-button-outlined p-button-secondary self-end cursor"
                        style="border-color: green; color: green;" @click="removeItem(item)" />
                    </div>
                  </div>
                </template>
              </Card>
            </div>
          </div>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup>
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import { ref, computed } from 'vue';
import { IconField, InputIcon, Dialog } from "primevue";
import Card from 'primevue/card';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import { useToast } from 'primevue/usetoast';
import { defineProps } from 'vue';
import { router } from "@inertiajs/vue3";

import axios from 'axios';


import Textarea from 'primevue/textarea';

const toast = useToast();

const name = ref('');
const email = ref('');
const notes = ref('');
const store = ref('');

const props = defineProps({
  items: { type: Array }
});

const searchQuery = ref('');

const filteredItems = computed(() => {
  const query = searchQuery.value.toLowerCase();
  return props.items.filter(item => {
    // Check if the query matches any of the object's properties
    return Object.values(item).some(value => {
      if (typeof value === 'string') {
        return value.toLowerCase().includes(query);
      }
      return false; // Only search within string values for simplicity
    });
  });
});


const showSelectedItems = ref(false)

const selectedItems = ref([])

const addItem = (item) => {
  item.selected = true
  selectedItems.value.push(item)
  toast.add({
    severity: 'success', summary: 'Item Added', detail: `${item.model} 
added successfully.`, life: 3000
  });
};

const removeItem = (item) => {
  selectedItems.value = selectedItems.value.filter(oldItem => oldItem.id !== item.id);
  delete item.selected
}

const getSelectedItems = () => {
  showSelectedItems.value = true
}

const onSubmit = async () => {
  let request = {
    name: name.value,
    email: email.value,
    store: store.value,
    notes: notes.value,
    items: [],
  };

  for (const item of selectedItems.value) {
    request.items.push({
      ...item,
    });
  }


  try {
    const laravelRoute = route("items.request"); // Get the Laravel route URL using Inertia's helper

    const response = await axios.post(laravelRoute, request);
    showSelectedItems.value = false
    toast.add({ severity: 'success', summary: 'Success', detail: response.data.message || 'Request submitted successfully.', life: 3000 });
  } catch (error) {

    let errorMessage = 'An unexpected error occurred.';

    if (error.response) {
      if (error.response.data && error.response.data.errors) {
        errorMessage = Object.values(error.response.data.errors).flat().join('\n');
      } else if (error.response.data && error.response.data.message) {
        errorMessage = error.response.data.message;
      } else {
        errorMessage = `Server error with status code: ${error.response.status}`;
      }
      toast.add({ severity: 'error', summary: 'Error', detail: errorMessage, life: 5000 });
      // errorMessages.value = error.response.data.errors; // If Laravel returns validation errors in this format
    } else if (error.request) {
      // The request was made but no response was received
      errorMessage = 'No response received from the server. Please check your network connection.';
      toast.add({ severity: 'error', summary: 'Error', detail: errorMessage, life: 5000 });
      // Display a network error message to the user
    } else {
      // Something happened in setting up the request that triggered an Error
      errorMessage = `Error setting up the request: ${error.message}`;
      toast.add({ severity: 'error', summary: 'Error', detail: errorMessage, life: 5000 });
      // Display a generic error message
    }
  }

}

</script>

<style scoped>
/* Optional: Style the tooltip */
.p-tooltip {
  background-color: #333;
  color: #fff;
  padding: 0.5rem;
  border-radius: 4px;
  font-size: 0.875rem;
}
</style>