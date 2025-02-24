<template>
    <Dialog v-model:visible="visible" modal header="Available Storage Locations" :style="{ width: '30rem' }">
      <span class="text-surface-500 dark:text-surface-400 block mb-4">
        Select the destination storage for {{ props.items.length }} items.
      </span>
  
      <div class="flex flex-wrap gap-2">
        <Chip
          v-for="storage in storages"
          :key="storage.id"
          class="cursor-pointer"
          :label="`${storage.name} - (${storage.items.length + (selectedStorage === storage.id ? props.items.length : 0)} / ${storage.limit})`"
          :class="{
            'bg-gray-300 text-gray-600 cursor-not-allowed opacity-50': exceedsLimit(storage),
            'bg-blue-500 text-white hover:bg-blue-600': selectedStorage === storage.id && !exceedsLimit(storage),
            'bg-gray-100 text-black': selectedStorage !== storage.id && !exceedsLimit(storage)
          }"
          @click="toggleSelection(storage)"
          :disabled="exceedsLimit(storage)"
        />
      </div>
  
      <template #footer>
        <button 
          :disabled="!selectedStorage || exceedsLimit(getSelectedStorage())"
          class="px-4 py-2 bg-blue-500 text-white rounded mt-4 w-full"
          :class="{ 'opacity-50 cursor-not-allowed': !selectedStorage || exceedsLimit(getSelectedStorage()) }"
          @click="assignLocation"
        >
          Assign
        </button>
      </template>
    </Dialog>
  </template>
  
  <script setup lang="ts">
  import { ref, defineExpose, defineProps } from 'vue';
  import Chip from 'primevue/chip';
  import Dialog from 'primevue/dialog';
  import { fetchStorages } from '../StoragesIndexData';
  
  const visible = ref(false);
  
  const props = defineProps<{
    items: number[]; // Assuming items are an array of item IDs
  }>();
  
  type Storage = {
    id: number;
    name: string;
    items: number[];
    limit: number;
  };
  
  const storages = ref<Storage[]>([]);
  const selectedStorage = ref<number | null>(null);
  
  // Function to open the modal and fetch storages
  function openDialog() {
    visible.value = true;
    fetch();
  }
  
  // Function to close the modal
  function closeDialog() {
    visible.value = false;
    selectedStorage.value = null; // Reset selection when closing
  }
  
  // Fetch storages from the API
  async function fetch() {
    try {
      const response = await fetchStorages();
      storages.value = response.data;
    } catch (error) {
      console.error('Error fetching storages:', error);
    }
  }
  
  // Function to check if adding items exceeds storage limit
  function exceedsLimit(storage: any): boolean {
    return storage.items.length + props.items.length > storage.limit;
  }
  
  // Toggle storage selection
  function toggleSelection(storage: Storage) {
    if (exceedsLimit(storage)) return;
  
    if (selectedStorage.value === storage.id) {
      selectedStorage.value = null; // Deselect if already selected
    } else {
      selectedStorage.value = storage.id; // Select new storage
    }
  }
  
  // Get currently selected storage
  function getSelectedStorage(){
    return storages.value.find(storage => storage.id === selectedStorage.value);
  }
  
  // Assign items to the selected storage
  function assignLocation() {
    if (!selectedStorage.value) return;
  
    const selected = storages.value.find(s => s.id === selectedStorage.value);
    if (selected) {
      selected.items = [...selected.items, ...props.items]; // Simulate adding items
    }
  
    closeDialog(); // Close dialog after assignment
  }
  
  // Expose functions to be controlled from the parent
  defineExpose({
    openDialog,
    closeDialog,
  });
  </script>
  
  <style scoped>
  .cursor-not-allowed {
    pointer-events: none;
  }
  </style>
  