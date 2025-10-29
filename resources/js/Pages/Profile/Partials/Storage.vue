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
          <div v-for="location in storageLocations" :key="location.id"
            class="flex items-center justify-between py-2 px-5 bg-[var(--bg-item)] rounded-3xl cursor-grab"
            draggable="true"
            @dragstart="onDragStart($event, location.id)"
            @dragend="onDragEnd($event, location.id)"
            @dragover.prevent="onDragOver($event, location.id)"
            @drop.prevent="onDrop($event, location.id)">
            <span>
              {{ location.name }} - (
              {{
                (location.occupied_count ?? ((location.items?.length || 0) + (location.draftItems?.length || 0)))
              }}/{{ location.limit }})
            </span>

            <div class="flex items-center space-x-2">
              <Button type="button" icon="pi pi-ellipsis-v" text class="!p-2"
                @click="(event) => toggleMenu(event, location.id)" aria-haspopup="true"
                :aria-controls="`overlay_menu_${location.id}`" />
              <Menu :ref="(el) => (menuRefs[location.id] = el)" :id="`overlay_menu_${location.id}`"
                :model="getMenuItems(location)" :popup="true" />
            </div>
          </div>
        </div>

        <div class="flex justify-end mb-4 space-x-2">
          <Button label="Save Order" icon="pi pi-save" @click="saveOrder" />
          <Button label="Create" icon="pi pi-plus" @click="openCreateDialog" />
        </div>

        <!-- single Create button kept above next to Save Order -->
      </template>
    </Card>

    <!-- Create/Edit Dialog -->
    <Dialog v-model:visible="dialogVisible" :modal="true"
      :header="editingLocation ? 'Edit Storage Location' : 'Create Storage Location'" class="w-full max-w-lg">
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

const draggingId = ref<number|null>(null);
const dragOverId = ref<number|null>(null);

// Fetch storages on mount
const fetchStorages = async () => {
  try {
    const { data } = await axios.get("/storages");
    storageLocations.value = data;
  } catch (error) {
    toast.add({ severity: "error", summary: "Error", detail: "Failed to load storages", life: 3000 });
  }
};

onMounted(fetchStorages);

const toggleMenu = (event: Event, locationId: number) => {
  menuRefs[locationId]?.toggle(event);
};

function onDragStart(e: DragEvent, id: number) {
  draggingId.value = id;
  const target = (e.currentTarget || e.target) as HTMLElement | null;
  if (target) target.style.opacity = '0.6';
  try {
    if (e.dataTransfer) {
      e.dataTransfer.setData('text/plain', String(id));
      e.dataTransfer.effectAllowed = 'move';
    }
  } catch (err) {
    // ignore dataTransfer errors in some browsers
  }
}

function onDragEnd(e: DragEvent, id: number) {
  const target = (e.currentTarget || e.target) as HTMLElement | null;
  if (target) target.style.opacity = '1';
  draggingId.value = null;
  dragOverId.value = null;
}

function onDragOver(e: DragEvent, id: number) {
  dragOverId.value = id;
}

function onDrop(e: DragEvent, id: number) {
  const fromId = draggingId.value;
  const toId = id;
  if (!fromId || !toId || fromId === toId) return;

  const arr = [...storageLocations.value];
  const fromIndex = arr.findIndex(s => s.id === fromId);
  const toIndex = arr.findIndex(s => s.id === toId);
  if (fromIndex === -1 || toIndex === -1) return;

  const [moved] = arr.splice(fromIndex, 1);
  arr.splice(toIndex, 0, moved);

  // reassign local priorities for immediate feedback
  storageLocations.value = arr.map((s, idx) => ({ ...s, priority: idx + 1 }));

  draggingId.value = null;
  dragOverId.value = null;
}

async function saveOrder() {
  try {
    const order = storageLocations.value.map(s => s.id);
    await axios.post('/storages/reorder', { order });
    toast.add({ severity: 'success', summary: 'Saved', detail: 'Order saved', life: 3000 });
    // refresh to get server canonical state
    fetchStorages();
  } catch (err) {
    console.error('Failed to save order', err);
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to save order', life: 3000 });
  }
}

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
