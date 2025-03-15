<template>
  <div class="flex justify-between">
    <div class="flex items-start mb-2 w-1/3">
      <div>
        <h2 class="text-xl font-medium mb-1">Storage Locations</h2>
        <p class="text-sm ">Manage your current storage locations</p>
      </div>
    </div>

    <Card class="shadow-none w-2/3">
      <template #content>
        <div class="flex flex-wrap gap-4 mb-4">
          <div v-for="location in storageLocations" :key="location.id" class="flex items-center justify-between py-2 px-5 bg-[var(--bg-item)] rounded-3xl">
            <span> {{ location.name }} - ({{ location.items.length }}/{{ location.limit }}) </span>

            <Button
              type="button"
              icon="pi pi-ellipsis-v"
              text
              class="!p-2"
              @click="(event) => toggleMenu(event, location.id)"
              aria-haspopup="true"
              :aria-controls="`overlay_menu_${location.id}`" />
            <Menu
              :ref="(el) => (menuRefs[location.id] = el)"
              :id="`overlay_menu_${location.id}`"
              :model="getMenuItems(location)"
              :popup="true" />
          </div>
        </div>

        <div class="flex justify-end">
          <Button
            label="Create"
            icon="pi pi-plus"
            @click="openCreateDialog"/>
        </div>
      </template>
    </Card>

    <!-- Create/Edit Dialog -->
    <Dialog
      v-model:visible="dialogVisible"
      :modal="true"
      :header="editingLocation ? 'Edit Storage Location' : 'Create Storage Location'"
      class="w-full max-w-lg">
      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div class="field">
          <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Location Name</label>
          <InputText v-model="form.name" id="name" required class="w-full" />
        </div>

        <div class="field">
          <label for="limit" class="block text-sm font-medium text-gray-700 mb-1">Capacity</label>
          <InputNumber v-model="form.limit" id="limit" required class="w-full" :min="0" />
        </div>

        <div class="flex justify-end gap-2">
          <Button type="button" label="Cancel" outlined @click="dialogVisible = false" />
          <Button type="submit" :label="editingLocation ? 'Update' : 'Create'" />
        </div>
      </form>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, Ref } from "vue";
import axios from "axios";
import { useConfirm, useToast, Button, Dialog, Menu, InputText, InputNumber, Card } from "primevue";
import { Storage } from "@/Lib/types";

const storageLocations: Ref<Storage[]> = ref([]);
const confirm = useConfirm();
const toast = useToast();
const menuRefs = reactive<{ [key: number]: any }>({});
const dialogVisible = ref(false);
const editingLocation = ref<Storage | null>(null);

const form = reactive({
  name: "",
  limit: 0,
});

// Fetch storages on mount
const fetchStorages = async () => {
  try {
    const { data } = await axios.get("/storages");
    console.log(data);
    storageLocations.value = data;
  } catch (error) {
    toast.add({ severity: "error", summary: "Error", detail: "Failed to load storages", life: 3000 });
  }
};

onMounted(fetchStorages);

const toggleMenu = (event: Event, locationId: number) => {
  menuRefs[locationId]?.toggle(event);
};

const getMenuItems = (location: Storage) => [
  {
    label: "Edit",
    icon: "pi pi-pencil",
    command: () => openEditDialog(location),
  },
  {
    label: "Delete",
    icon: "pi pi-trash",
    class: "text-red-500",
    command: () => confirmDelete(location),
  },
];

const openCreateDialog = () => {
  editingLocation.value = null;
  form.name = "";
  form.limit = 0;
  dialogVisible.value = true;
};

const openEditDialog = (location: Storage) => {
  editingLocation.value = location;
  form.name = location.name;
  form.limit = location.limit;
  dialogVisible.value = true;
};

const handleSubmit = async () => {
  try {
    if (editingLocation.value) {
      await axios.put(`/storages/${editingLocation.value.id}`, {
        name: form.name,
        limit: form.limit, // Laravel might expect `limit` instead of `limit`
      });
    } else {
      const { data } = await axios.post("/storages", { storages: [{ name: form.name, limit: form.limit }] });
    }

    dialogVisible.value = false;
    toast.add({ severity: "success", summary: "Success", detail: `Storage location ${editingLocation.value ? "updated" : "created"} successfully`, life: 3000 });
    fetchStorages(); // Refresh data
  } catch (error) {
    toast.add({ severity: "error", summary: "Error", detail: `Failed to ${editingLocation.value ? "update" : "create"} storage location`, life: 3000 });
  }
};

const confirmDelete = (location: Storage) => {
  confirm.require({
    message: `Are you sure you want to delete ${location.name}?`,
    header: "Delete Confirmation",
    icon: "pi pi-exclamation-triangle",
    accept: () => handleDelete(location),
  });
};

const handleDelete = async (location: Storage) => {
  try {
    await axios.post("/storages/destroy", { storages: [location.id] });

    storageLocations.value = storageLocations.value.filter((l) => l.id !== location.id);
    toast.add({ severity: "success", summary: "Success", detail: "Storage location deleted successfully", life: 3000 });
    fetchStorages(); // Refresh data
  } catch (error) {
    toast.add({ severity: "error", summary: "Error", detail: "Failed to delete storage location", life: 3000 });
  }
};
</script>

<style scoped>
:deep(.p-card) {
  box-shadow: none;
  border: none;
}

:deep(.p-card-content) {
  padding: 1.5rem;
}

:deep(.p-card .p-card-body) {
  padding: 0;
}
</style>
