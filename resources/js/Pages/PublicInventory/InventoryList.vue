<template>
  <Toast></Toast>
  <div class="max-w-7xl mx-auto py-4">
    <div class="w-full flex justify-end gap-4 pb-4">
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
              <div class="flex-1">
                <label for="shipping" class="block text-gray-700 text-sm font-bold mb-1">Shipping:</label>
                <Dropdown id="shipping" :options="shippingOptions" optionLabel="label" v-model="shipping" class="w-full"
                  placeholder="Select shipping" />
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
                    {{ data.issues }}
                  </div>
                </template>
              </Column>

              <Column field="model" header="Model" class="sm:hidden font-semibold" />
              <Column field="selling_price" header="Price">
                <template #body="{ data }" class="text-right">
                  <div class="text-xl font-bold text-black">${{ data.selling_price?.toFixed(2) }}</div>
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

        <h2 class="text-2xl justify-self-end font-black p-4 text-black">Total:
          {{selectedItems.reduce((accumulator, currentItem) => {
            return accumulator + currentItem.selling_price;
          }, 0).toFixed(2)}}
        </h2>

        <div class="flex w-full justify-around ">
          <Button severity="secondary" @click="showSelectedItems = false">CANCEL</Button>
          <Button @click="onSubmit">REQUEST DEVICES</Button>
        </div>

      </Dialog>



      <button @click="handleDownload" class="flex items-center">
        <span class="pi pi-download mr-2"></span>
        <p class="hidden lg:block">Download Spreadsheet</p>
      </button>

      <Button @click="getSelectedItems">REQUEST DEVICES</Button>

    </div>
    <div class="flex justify-end pb-4">

    </div>

    <div class="md:hidden mb-4 flex items-center gap-2">
      <Dialog v-model:visible="showFilterModal" header="Filter Items" :modal="true"
        :breakpoints="{ '960px': '75vw', '640px': '90vw' }">
        <div class="flex flex-col gap-4">
          <div>
            <label class="block text-gray-700 text-sm font-bold mb-1">Manufacturer:</label>
            <div class="flex flex-wrap gap-2">
              <div v-for="manufacturer in uniqueManufacturers" :key="manufacturer">
                <div class="flex items-center">
                  <Checkbox :inputId="'manufacturer-' + manufacturer" :value="manufacturer"
                    v-model="filters.manufacturer" />
                  <label :for="'manufacturer-' + manufacturer" class="ml-2">{{ manufacturer }}</label>
                </div>
              </div>
            </div>
          </div>
          <!-- Models filter: moved directly under Manufacturer for mobile -->
          <div v-if="filters.manufacturer.length > 0">
            <label class="block text-gray-700 text-sm font-bold mb-1">Model:</label>
            <div class="flex flex-col gap-2">
              <div v-for="opt in modelsOptions" :key="opt.value" class="flex items-center">
                <Checkbox :inputId="'model-mobile-' + opt.value" :value="opt.value" v-model="filters.model" />
                <label :for="'model-mobile-' + opt.value" class="ml-2">{{ opt.label }}</label>
              </div>
            </div>
          </div>
          <div>
            <label class="block text-gray-700 text-sm font-bold mb-1">Grade:</label>
            <div class="flex flex-wrap gap-2">
              <div v-for="grade in uniqueGrades" :key="grade">
                <div class="flex items-center">
                  <Checkbox :inputId="'grade-' + grade" :value="grade" v-model="filters.grade" />
                  <label :for="'grade-' + grade" class="ml-2">{{ grade }}</label>
                </div>
              </div>
            </div>
          </div>
          <div>
            <label class="block text-gray-700 text-sm font-bold mb-1">Has Issues:</label>
            <div class="flex flex-col gap-2">
              <div class="flex items-center">
                <RadioButton inputId="has-issues-all" value="all" v-model="filters.hasIssues" />
                <label for="has-issues-all" class="ml-2">All</label>
              </div>
              <div class="flex items-center">
                <RadioButton inputId="has-issues-yes" :value="true" v-model="filters.hasIssues" />
                <label for="has-issues-yes" class="ml-2">Yes</label>
              </div>
              <div class="flex items-center">
                <RadioButton inputId="has-issues-no" :value="false" v-model="filters.hasIssues" />
                <label for="has-issues-no" class="ml-2">No</label>
              </div>
            </div>
          </div>
          <Button label="Apply Filters" @click="showFilterModal = false" />
        </div>
      </Dialog>
    </div>

    <div class="flex flex-col md:flex-row gap-4">
      <div class="hidden sm:block md:w-1/4 ">
        <Card class="shadow-sm ">
          <template #content>
            <h2 class="text-xl font-semibold mb-2">Filter Items</h2>
            <div class="flex flex-col gap-4">
              <div>
                <label class="block text-gray-700 text-sm font-bold mb-1">Manufacturer:</label>
                <div class="flex flex-col gap-2">
                  <div v-for="manufacturer in uniqueManufacturers" :key="manufacturer">
                    <div class="flex items-center">
                      <Checkbox :inputId="'manufacturer-desktop-' + manufacturer" :value="manufacturer"
                        v-model="filters.manufacturer" />
                      <label :for="'manufacturer-desktop-' + manufacturer" class="ml-2">{{ manufacturer }}</label>
                    </div>
                  </div>
                </div>
              </div>
              <!-- Models filter: moved under Manufacturer for desktop -->
              <div v-if="filters.manufacturer.length > 0">
                <label class="block text-gray-700 text-sm font-bold mb-1">Model:</label>
                <div class="flex flex-col gap-2">
                  <div v-for="opt in modelsOptions" :key="opt.value" class="flex items-center">
                    <Checkbox :inputId="'model-' + opt.value" :value="opt.value" v-model="filters.model" />
                    <label :for="'model-' + opt.value" class="ml-2">{{ opt.label }}</label>
                  </div>
                </div>
              </div>
              <div>
                <label class="block text-gray-700 text-sm font-bold mb-1">Grade:</label>
                <div class="flex flex-col gap-2">
                  <div v-for="grade in uniqueGrades" :key="grade">
                    <div class="flex items-center">
                      <Checkbox :inputId="'grade-desktop-' + grade" :value="grade" v-model="filters.grade" />
                      <label :for="'grade-desktop-' + grade" class="ml-2">{{ grade }}</label>
                    </div>
                  </div>
                </div>
              </div>
              <div>
                <label class="block text-gray-700 text-sm font-bold mb-1">Has Issues:</label>
                <div class="flex flex-col gap-2">
                  <div class="flex items-center">
                    <RadioButton inputId="has-issues-all" value="all" v-model="filters.hasIssues" />
                    <label for="has-issues-all" class="ml-2">All</label>
                  </div>
                  <div class="flex items-center">
                    <RadioButton inputId="has-issues-yes" :value="true" v-model="filters.hasIssues" />
                    <label for="has-issues-yes" class="ml-2">Yes</label>
                  </div>
                  <div class="flex items-center">
                    <RadioButton inputId="has-issues-no" :value="false" v-model="filters.hasIssues" />
                    <label for="has-issues-no" class="ml-2">No</label>
                  </div>
                </div>
              </div>
              
            </div>
          </template>
        </Card>
      </div>
      <Card class="shadow-sm md:w-3/4">
        <template #content>
          <h1>Company: {{ companyName }}</h1>
          <h1>Shop: {{ shopName }}</h1>
          
          <GenericTabs
            :static-tabs="staticTabs"
            :custom-tabs="customTabs"
            v-model="activeTabValue"
            :enable-drag="false"
            :show-add-tab="false"
            :show-delete-drop-zone="false"
            @tab-click="onTabClick"
            @request-add="onRequestAdd"
            @reorder="onReorderTabs"
            @request-remove="onRequestRemove"
          >
          <div class="flex flex-col">
            <div class="flex flex-col md:flex-row justify-between items-stretch md:items-center gap-4 mb-6">

              <div class="w-full flex items-center justify-between">
                <IconField class="w-full md:w-auto pr-6">
                  <InputIcon>
                    <i class="pi pi-search" />
                  </InputIcon>
                  <InputText v-model="searchQuery" placeholder="Search" class="w-full" />
                </IconField>
                <button class="max-w-24 px-6 py-4  border rounded-full border-gray-400 font-semibold shadow"
                  @click="changeExchange()">
                  <span v-if="country.toLowerCase() == 'ca'" class="flex justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="16" viewBox="0 0 9600 4800">
                      <title>Flag of Canada</title>
                      <path fill="#f00" d="m0 0h2400l99 99h4602l99-99h2400v4800h-2400l-99-99h-4602l-99 99H0z" />
                      <path fill="#fff"
                        d="m2400 0h4800v4800h-4800zm2490 4430-45-863a95 95 0 0 1 111-98l859 151-116-320a65 65 0 0 1 20-73l941-762-212-99a65 65 0 0 1-34-79l186-572-542 115a65 65 0 0 1-73-38l-105-247-423 454a65 65 0 0 1-111-57l204-1052-327 189a65 65 0 0 1-91-27l-332-652-332 652a65 65 0 0 1-91 27l-327-189 204 1052a65 65 0 0 1-111 57l-423-454-105 247a65 65 0 0 1-73 38l-542-115 186 572a65 65 0 0 1-34 79l-212 99 941 762a65 65 0 0 1 20 73l-116 320 859-151a95 95 0 0 1 111 98l-45 863z" />
                    </svg>
                  </span>
                  <span v-else>
                    <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="32"
                      height="16" viewBox="0 0 7410 3900">
                      <path d="M0,0h7410v3900H0" fill="#b31942" />
                      <path d="M0,450H7410m0,600H0m0,600H7410m0,600H0m0,600H7410m0,600H0" stroke="#FFF"
                        stroke-width="300" />
                      <path d="M0,0h2964v2100H0" fill="#0a3161" />
                      <g fill="#FFF">
                        <g id="s18">
                          <g id="s9">
                            <g id="s5">
                              <g id="s4">
                                <path id="s"
                                  d="M247,90 317.534230,307.082039 132.873218,172.917961H361.126782L176.465770,307.082039z" />
                                <use xlink:href="#s" y="420" />
                                <use xlink:href="#s" y="840" />
                                <use xlink:href="#s" y="1260" />
                              </g>
                  
                              <use xlink:href="#s" y="1680" />
                            </g>
                            <use xlink:href="#s4" x="247" y="210" />
                          </g>
                          <use xlink:href="#s9" x="494" />
                        </g>
                        <use xlink:href="#s18" x="988" />
                        <use xlink:href="#s9" x="1976" />
                        <use xlink:href="#s5" x="2470" />
                      </g>
                    </svg>
                  </span>
                </button>
              </div>


              <div class="flex items-center gap-2 sm:hidden ">
                <Button icon="pi pi-filter" label="Filters" @click="showFilterModal = true" />
              </div>
            </div>

            <div class="mb-4 text-gray-500 text-xl">
              Displaying {{ filteredItemsWithFilters.length }} Items
            </div>

            <div class="space-y-4">
              <div v-for="item in filteredItemsWithFilters" :key="item.id">
                <Card class="hover:shadow-md transition-shadow duration-200">
                  <template #content>
                    <div class="flex flex-col h-full md:flex-row justify-between items-start md:items-center p-2">
                      <div class="space-y-4 ">
                        <h2 class="text-2xl font-semibold"> {{ item.model }}</h2>
                        <div class="flex flex-wrap gap-2">
                          <Tag :value="item.manufacturer" class="font-medium" />
                          <Tag v-if="item.grade" :value="item.grade" severity="info" class="font-medium" />
                          <Tag v-if="item.battery" :value="item.battery + '%'" severity="success" class="font-medium" />
                          <Tag v-if="item.colour" :value="item.colour" class="'font-medium" />
                        </div>
                      </div>

                      <div
                        class=" grow md:h-24 flex flex-col md:flex-row justify-end items-start md:items-center gap-4 mt-4 md:mt-0 w-full md:w-auto">
                        <div v-if="item.issues" class="h-full md:w-64 flex items-start pt-1">
                          <div
                            class="h-full text-orange-500  md:text-center md:flex justify-center items-center w-full text-sm italic font-semibold md:border border-black rounded-md md:p-4">
                            Issues: {{ item.issues }}
                          </div>
                        </div>
                        <div class="text-xl md:text-2xl font-bold text-black w-24">${{
                          item.selling_price?.toFixed(2) }}
                        </div>
                        <Button v-if="!item?.selected" icon="pi pi-plus"
                          class="p-button-rounded p-button-outlined  p-button-secondary md:self-center self-end cursor"
                          style="border-color: black; color: black;" @click="addItem(item)" />
                        <Button v-if="item.selected" icon="pi pi-check"
                          class="p-button-rounded p-button-outlined p-button-secondary md:self-center self-end cursor"
                          style="border-color: green; color: green;" @click="removeItem(item)" />
                      </div>
                    </div>
                  </template>
                </Card>
              </div>
            </div>
          </div>
          </GenericTabs>
        </template>
      </Card>
    </div>
  </div>
</template>

<script setup lang="ts">
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import { ref, computed, watch } from 'vue';
import { IconField, InputIcon, Dialog, Checkbox, RadioButton } from "primevue";
import Dropdown from 'primevue/dropdown';
import Card from 'primevue/card';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import GenericTabs from '@/Components/GenericTabs.vue';
import { useToast } from 'primevue/usetoast';
import { defineProps } from 'vue';
import { router } from "@inertiajs/vue3";
import axios from 'axios';
import Textarea from 'primevue/textarea';
import { onMounted } from 'vue';
import downloadSpreadsheet from '@/Utils/downloadSpreadsheet';

interface Props {
  items?: any[]; // Using 'any[]' for simplicity, you can be more specific
  shopName?: string;
  companyName?: string;
}

const props = defineProps<Props>();

const toast = useToast();

const country = ref("CA")

const exchangeRate = ref(1)
const exchangeActive = ref(false)

const changeExchange = () => {
  exchangeActive.value = !exchangeActive.value
}

watch(exchangeActive, (newValue) => {
  props?.items.forEach(item => {
    item.selling_price = newValue ? Math.round(item.selling_price / exchangeRate.value.toFixed(2)) : Math.round(item.selling_price * exchangeRate.value.toFixed(2));
  });

  country.value = country.value === "USA" ? "CA" : "USA";
});


const name = ref('');
const email = ref('');
const notes = ref('');
const store = ref('');
const shipping = ref<{ label: string; value: number } | null>({ label: 'Standard (Free)', value: 0 });
const shippingOptions = [
  { label: 'Standard (Free)', value: 0 },
  { label: 'Express (+$25)', value: 25 }
];





const searchQuery = ref('');
const showSelectedItems = ref(false);
const selectedItems = ref([]);
// IDs of items for the currently selected tab. These are populated by `fetchTabItems`.
const selectedTabItems = ref<Array<number>>(null);
const showFilterModal = ref(false); // For mobile filter modal
const filters = ref({
  manufacturer: [], // Use array for checkboxes
  grade: [], // Use array for checkboxes
  hasIssues: "all", // Can be true, false, or null
  model: [], // selected models (array)
});

// Tabs: Active Inventory, Sold, Hold (wrapping main listing)
const staticTabs = ref<Array<{ name: string; id?: number | string; order?: number }>>([
  { name: 'Active Inventory' },
]);
const customTabs = ref<Array<{ name: string; id?: number | string; order?: number }>>([]);
const activeTabValue = ref<string>('s:0');

// Dummy event handlers from GenericTabs
const onTabClick = async (payload: { kind: 'static' | 'custom'; tab: any; index: number; value: string }) => {
  console.log('Tab clicked:', payload.tab);
  if(!payload.tab.id){
    selectedTabItems.value = null;
    return;
  }
  // If a custom tab with an id is clicked, fetch its items and store the ids
  try {
    if (payload && payload.tab && (payload.tab.id || payload.tab.ID)) {
      const tabId = payload.tab.id ?? payload.tab.ID;
      try {
        const ids = await fetchTabItems(tabId);
        selectedTabItems.value = Array.isArray(ids) ? ids : [];
      } catch (err) {
        console.error('Error fetching tab items on tab click', err);
        selectedTabItems.value = [];
      }
    } else {
      // For static tabs or tabs without id, clear the selectedTabItems to show all items
      selectedTabItems.value = [];
    }
  } catch (e) {
    console.error('onTabClick handler error', e);
  }
};

// Models options populated when manufacturers are selected
const modelsOptions = ref<Array<{ label: string; value: string }>>([]);

// Fetch item IDs for a given tab id (calls route 'items.tabs.items')
async function fetchTabItems(tabId) {
  if (!tabId) return [];
  try {
    let laravelRoute = route('items.tabs.items', { id: tabId });
    const response = await axios.get(laravelRoute);
    // Expecting backend to return { item_ids: [...] } or { items: [...] }
    const ids = response?.data?.item_ids ?? (response?.data?.items ? response.data.items.map(i => i.id) : []);
    return ids;
  } catch (error) {
    console.error('Error fetching tab items:', error);
    // Let the caller handle the assignment/notification; just return an empty array on error
    return [];
  }
}

// Normalize model string to match server-side normalization
function normalizeModel(raw: any): string {
  if (!raw) return '';
  let m = String(raw).trim();
  // Remove parenthetical content
  m = m.replace(/\(.+?\)/gu, ' ');
  // Remove storage sizes like '128GB', '256 GB'
  m = m.replace(/\b\d+\s*gb\b/giu, ' ');
  m = m.replace(/\b\d+\s*g\b/giu, ' ');
  // Remove non-alphanumeric (keep spaces)
  m = m.replace(/[^\p{L}\p{N} ]+/gu, ' ');
  // Collapse whitespace
  m = m.replace(/\s+/gu, ' ').trim();
  return m.toLowerCase();
}

// Fetch models for selected manufacturers.
async function fetchModelsByManufacturers() {
  try {
    // If no manufacturers selected, clear modelsOptions and return early
    if (!filters.value.manufacturer || filters.value.manufacturer.length === 0) {
      modelsOptions.value = [];
      return;
    }
    // Build payload expected by the backend route
    const payload = { manufacturers: filters.value.manufacturer };

    // Build the Laravel route using the `route` helper.
    const laravelRoute = route('Items.getUniqueModelsByManufacturer')

    const response = await axios.post(laravelRoute, payload);

    // Backend returns { models: [...] }
    const models = response.data?.models ?? [];

    // Map to Dropdown expected format { label, value }
    modelsOptions.value = models.map(m => {
      if (typeof m === 'string') return { label: m, value: m };
      if (m && typeof m === 'object') return { label: m.label ?? m.value ?? '', value: m.value ?? m.label ?? '' };
      return { label: String(m), value: String(m) };
    });

  } catch (error) {
    console.error('Error fetching models by manufacturers:', error);
    let message = 'Error fetching models.';
    if (error.response && error.response.data && error.response.data.message) {
      message = error.response.data.message;
    }
    toast.add({ severity: 'error', summary: 'Error', detail: message, life: 5000 });
    modelsOptions.value = [];
  }
}

// Watch manufacturers selection and fetch models accordingly
watch(
  () => filters.value.manufacturer,
  (nv, ov) => {
    fetchModelsByManufacturers();
  },
  { deep: true }
);

const uniqueManufacturers = computed(() => Array.from([...new Set(props.items?.map(item => item.manufacturer))]).sort());
const uniqueGrades = computed(() => Array.from([...new Set(props.items?.map(item => item.grade).filter(Boolean))]).sort());

const filteredItemsBeforeFilters = computed(() => {
  const query = searchQuery.value.toLowerCase();
  return props.items.filter(item =>
    Object.values(item).some(value =>
      typeof value === 'string' && value.toLowerCase().includes(query)
    )
  );
});

// apply filters matching
const filteredItemsWithFilters = computed(() => {
  return filteredItemsBeforeFilters.value.filter(item => {
    // If tab-based selection exists, only include items whose id is in selectedTabItems
    if (selectedTabItems.value) {
      if (!selectedTabItems.value.includes(item.id)) return false;
    }
    const manufacturerMatch = filters.value.manufacturer.length === 0 || filters.value.manufacturer.includes(item.manufacturer);
    const gradeMatch = filters.value.grade.length === 0 || filters.value.grade.includes(item.grade);
    const issuesMatch = filters.value.hasIssues === 'all' ||
      (filters.value.hasIssues === true && item.issues) ||
      (filters.value.hasIssues === false && !item.issues);
    const modelMatch = filters.value.model.length === 0 || filters.value.model.includes(normalizeModel(item.model));  

    return manufacturerMatch && gradeMatch && issuesMatch && modelMatch;
  });
});

const addItem = (item) => {
  item.selected = true;
  selectedItems.value.push(item);
  toast.add({
    severity: 'success',
    summary: 'Item Added',
    detail: `${item.model} added successfully.`,
    life: 3000
  });
};

const removeItem = (item) => {
  selectedItems.value = selectedItems.value.filter(oldItem => oldItem.id !== item.id);
  delete item.selected;
};

const getSelectedItems = () => {
  showSelectedItems.value = true;
};

const onSubmit = async () => {
  let request = {
    name: name.value,
    email: email.value,
    store: store.value,
  shipping: shipping.value,
    notes: notes.value,
    items: selectedItems.value.map(item => ({ ...item })), // Create a new array of selected items
  };

  try {
    const laravelRoute = route("items.request");
    const response = await axios.post(laravelRoute, request);
    showSelectedItems.value = false;
    toast.add({ severity: 'success', summary: 'Success', detail: response.data.message || 'Request submitted successfully.', life: 3000 });
    selectedItems.value.forEach(item => delete item.selected); // Clear selected state after successful submission
    selectedItems.value = []; // Clear selected items array
  } catch (error) {
    console.error('terrible error submitting request:', error);
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
    } else if (error.request) {
      errorMessage = 'No response received from the server. Please check your network connection.';
      toast.add({ severity: 'error', summary: 'Error', detail: errorMessage, life: 5000 });
    } else {
      errorMessage = `Error setting up the request: ${error.message}`;
      toast.add({ severity: 'error', summary: 'Error', detail: errorMessage, life: 5000 });
    }
  }
};

const handleDownload = () => {
  const keysToDownload = ["manufacturer", "model", "colour", "battery", "grade", "issues", "selling_price"]
  try {
    downloadSpreadsheet(selectedItems.value, keysToDownload, "request.xlsx")
  } catch (error) {
    toast.add({ severity: 'warn', summary: 'Alert', detail: error.message, life: 5000 });
  }
}

// Fetch tabs for the authenticated user and populate `customTabs`.
async function fetchUserTabs() {
  try {
    // route
    let laravelRoute = route('tabs.user');

    const response = await axios.get(laravelRoute);
    const tabs = response.data?.tabs ?? [];

    // Map to the expected shape { id, name, order }
    customTabs.value = tabs.map((t: any) => ({
      id: t.id ?? t.ID ?? t._id ?? null,
      name: t.name ?? t.title ?? '',
      order: t.order ?? 0,
    }));
  } catch (error) {
    console.error('Error fetching user tabs:', error);
    toast.add({
      severity: 'warn',
      summary: 'Tabs',
      detail: 'No custom tabs could be loaded.',
      life: 3000,
    });
    customTabs.value = [];
  }
}

onMounted(async () => {

  try {
    const response = await fetch('https://v6.exchangerate-api.com/v6/90fafe5c1eca42cafc565fb7/latest/USD/');
    const data = await response.json();
    const conversionRates = data.conversion_rates;
    exchangeRate.value = parseFloat(conversionRates.CAD);
    exchangeRate.value -= (exchangeRate.value * 0.03);
  } catch (error) {
    console.error('Error fetching exchange rates:', error);

  }

  // Ensure that the 'selected' property is not initially set on the props.items
  console.log("Initial items:", props.items);
  if (props.items && props.items.length > 0) {
    props.items.forEach(item => {

      if (item.hasOwnProperty('selected')) {
        delete item.selected;
      }
    });
  }

  // Fetch user-specific tabs
  await fetchUserTabs();
});
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


.cursor {
  cursor: pointer;
}
</style>