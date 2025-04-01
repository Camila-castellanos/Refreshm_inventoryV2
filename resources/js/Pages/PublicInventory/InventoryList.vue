<template>
    <div class="max-w-7xl mx-auto py-4">
      <Card class="shadow-sm">
        <template #content>
          <div class="flex flex-col">
            <div class="flex flex-col md:flex-row justify-between items-stretch md:items-center gap-4 mb-6">
              <span class="p-input-icon-left w-full md:w-2/3">
                
                <InputText v-model="searchQuery" placeholder="Search items..." class="w-full" />
              </span>
              <div class="flex items-center gap-2">
                <Button icon="pi pi-filter" label="Filter" class="p-button-outlined" />
                <Select
                  v-model="sortOption"
                  :options="sortOptions"
                  optionLabel="name"
                  placeholder="Sort by..."
                  class="w-full md:w-32"
                />
              </div>
            </div>
  
            <div class="mb-4 text-gray-500 text-xl">
              Displaying {{ items.length }} Items
            </div>
  
            <div class="space-y-4">
              <div v-for="item in items" :key="item.id">
                <Card class="hover:shadow-md transition-shadow duration-200">
                  <template #content>
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center p-2">
                      <div class="space-y-4">
                        <h2 class="text-4xl font-semibold">Item #{{ item.id }}</h2>
                        <div class="flex flex-wrap gap-2">
                          <Tag :value="item.manufacturer" severity="secondary" class="font-medium" />
                          <Tag :value="item.grade" severity="info" class="font-medium" />
                          <Tag :value="item.battery" severity="success" class="font-medium" />
                          <Tag :value="item.colour" :style="{ backgroundColor: getColourHex(item.colour), color: 'white' }" class="font-medium" />
                        </div>
                      </div>
                      <div class="flex flex-col md:flex-row justify-end items-start md:items-center gap-4 mt-4 md:mt-0 w-full md:w-auto">
                        <div v-if="item.issues && item.issues.length > 0" class="text-orange-500 text-sm italic mr-4">
                          Note: {{ item.issues.join(', ') }}
                        </div>
                        <div class="text-xl md:text-2xl font-bold text-black">${{ item.selling_price.toFixed(2) }}</div>
                        <Button
                          icon="pi pi-plus"
                          class="p-button-rounded p-button-outlined p-button-secondary"
                          style="border-color: black; color: black;"
                          @click="addItem(item)"
                        />
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
  import { ref } from 'vue';
  import Card from 'primevue/card';
  import InputText from 'primevue/inputtext';
  import Button from 'primevue/button';
  import Select from 'primevue/select';
  import Tag from 'primevue/tag'; // Import Tag component
  import { useToast } from 'primevue/usetoast';
  
  const searchQuery = ref('');
  const sortOption = ref(null);
  const sortOptions = ref([
    { name: 'Price: Low to High', value: 'price-asc' },
    { name: 'Price: High to Low', value: 'price-desc' },
    { name: 'Grade: A to Z', value: 'grade-asc' },
    { name: 'Grade: Z to A', value: 'grade-desc' },
    { name: 'Colour: A to Z', value: 'colour-asc' },
    { name: 'Colour: Z to A', value: 'colour-desc' },
    { name: 'Manufacturer: A to Z', value: 'manufacturer-asc' },
    { name: 'Manufacturer: Z to A', value: 'manufacturer-desc' },
  ]);
  
  const items = ref([
    {
      id: 101,
      manufacturer: 'Apple',
      selling_price: 499.99,
      issues: ['Minor scratch on screen'],
      colour: 'Space Grey',
      battery: '92%',
      grade: 'B'
    },
    {
      id: 102,
      manufacturer: 'Samsung',
      selling_price: 799.00,
      issues: [],
      colour: 'Midnight Green',
      battery: '98%',
      grade: 'A'
    },
    {
      id: 103,
      manufacturer: 'Google',
      selling_price: 249.50,
      issues: ['Cracked back glass', 'Speaker muffled'],
      colour: 'Red',
      battery: '85%',
      grade: 'C'
    },
    {
      id: 104,
      manufacturer: 'Apple',
      selling_price: 549.00,
      issues: [],
      colour: 'Silver',
      battery: '95%',
      grade: 'A'
    },
    {
      id: 105,
      manufacturer: 'OnePlus',
      selling_price: 399.00,
      issues: ['Slight dent on frame'],
      colour: 'Gold',
      battery: '88%',
      grade: 'B'
    },
  ]);
  
  const toast = useToast();
  
  const getColourHex = (colour) => {
    const colourMap = {
      'Space Grey': '#4a5568',
      'Midnight Green': '#1a2e35',
      'Red': '#e53e3e',
      'Silver': '#f7fafc',
      'Gold': '#facc15',
      // Add more mappings as needed
    };
    return colourMap[colour] || '#ccc';
  };
  
  const addItem = (item) => {
    console.log('Adding item:', item);
    toast.add({ severity: 'success', summary: 'Item Added', detail: `Item #${item.id} added successfully.`, life: 3000 });
  };
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