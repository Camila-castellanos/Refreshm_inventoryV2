<template>
  <div>
    <Toast />
    <section class="w-full px-2 py-1 mx-auto mt-4">
      <Tabs v-model:value="currentTab" scrollable>
        <TabList class="bg-white">
          <!-- Tabs fijas -->
          <Tab v-for="tab in staticTabs" :key="'static-' + tab.order" :value="tab.order" @click="redirectToTab(tab)">
            <span>{{ tab.name }}</span>
          </Tab>

          <!-- Custom tabs con drag nativo y menú contextual -->
          <Tab
            v-for="(tab, index) in customTabsDraggable"
            :key="'custom-' + tab.id"
            :value="staticTabs.length + index"
            @click="redirectToTab(tab)"
            @contextmenu.prevent="openTabMenu($event, tab, index)"
            class="!relative"
            draggable="true"
            @dragstart="onDragStartTab(tab, index)"
            @dragover.prevent="onDragOverTab(index)"
            @drop.prevent="onDropTab(index)"
            @dragend="onDragEnd">
            <div class="flex items-center space-x-2 cursor-move tab-handle">
              <span>{{ tab.name }}</span>
            </div>
          </Tab>

          <!-- Zona dinámica de eliminar / agregar -->
          <Tab
            :value="staticTabs.length + customTabsDraggable.length + 1"
            :class="[dropZoneActive || dragging ? 'bg-red-100 border border-red-500' : '', shakeDropZone ? 'shake' : '']"
            @click="addTabDialog = dragging ? false : true"
            @dragover.prevent="dropZoneActive = true"
            @dragleave="dropZoneActive = false"
            @drop.prevent="onDropToDelete"
            @dragend="onDragEnd">
            <div class="flex items-center justify-center space-x-2">
              <i :class="dropZoneActive ? 'pi pi-trash text-red-500' : 'pi pi-plus'" />
              <span v-if="dropZoneActive" class="text-red-500 text-sm">Drop to Delete</span>
            </div>
          </Tab>
        </TabList>
      </Tabs>
      <slot />
    </section>
  </div>

  <!-- Dialog agregar nueva tab -->
  <Dialog v-model:visible="addTabDialog" header="Add New Tab" modal @show="currentTab = lastTab">
    <div class="p-fluid">
      <div class="flex flex-col py-3">
        <label for="tabTitle">Tab Title</label>
        <InputText id="tabTitle" v-model="newTab.title" />
      </div>
      <div class="w-full mt-5">
        <Button label="Add" @click="addNewTab" class="w-full" />
      </div>
    </div>
  </Dialog>

  <!-- Dialog editar nombre de tab -->
  <Dialog v-model:visible="editTabDialog" header="Edit Tab Name" modal>
    <div class="p-fluid">
      <div class="flex flex-col py-3">
        <label for="editTabTitle">Tab Title</label>
        <InputText id="editTabTitle" v-model="editTab.name" @keyup.enter="saveTabName" />
      </div>
      <div class="flex gap-2 w-full mt-5">
        <Button label="Cancel" @click="editTabDialog = false" class="flex-1" severity="secondary" />
        <Button label="Save" @click="saveTabName" class="flex-1" />
      </div>
    </div>
  </Dialog>

  <!-- Context Menu para tabs -->
  <ContextMenu ref="tabContextMenu" :model="tabContextMenuItems" />
</template>

<script setup lang="ts">
import { Tab as ITab } from "@/Lib/types";
import { router } from "@inertiajs/vue3";
import axios from "axios";
import { Button, Dialog, Toast, useConfirm, useToast, ContextMenu } from "primevue";
import InputText from "primevue/inputtext";
import Tab from "primevue/tab";
import TabList from "primevue/tablist";
import Tabs from "primevue/tabs";
import { defineProps, onMounted, reactive, ref, Ref, watch, nextTick } from "vue";

const props = defineProps({
  customTabs: {
    type: Array<ITab>,
    required: true,
  },
});

const staticTabs = ref<ITab[]>([
  { name: "Active Inventory", order: 0 },
  { name: "On Hold", order: 1 },
  { name: "Sold", order: 2 },
]);

const customTabsDraggable = ref<ITab[]>([]);

const currentTab = ref(0);
const lastTab = ref(0);
const confirm = useConfirm();
const toast = useToast();
const dragging = ref(false);
const dropZoneActive = ref(false);
const shakeDropZone = ref(false);
const tabContextMenu = ref();
const editTabDialog = ref(false);
const editTab = reactive<ITab & { tempName?: string }>({ name: "", order: 0 });
const tabContextMenuItems = ref([
  {
    label: 'Edit Name',
    icon: 'pi pi-pen-to-square',
    command: () => openEditDialog()
  },
  {
    label: 'Delete',
    icon: 'pi pi-trash',
    command: () => deleteContextTab()
  }
]);

let draggedTab: ITab | null = null;
let draggedIndex: number | null = null;
let selectedContextTab: ITab | null = null;

onMounted(async () => {
  props.customTabs
    ?.sort((a, b) => a.order - b.order)
    .forEach((tab) => {
      customTabsDraggable.value.push({ name: tab.name, order: tab.order, id: tab.id });
    });

  let url = window.location.href;
  // normalize endpoint: remove query string and trailing slash so switches match
  let raw = (url.split("/inventory/")[1] || "").split("?")[0].replace(/\/$/, "");
  let endpoint = raw;
  switch (endpoint) {
    case "items":
      currentTab.value = 0;
      break;
    case "items/hold":
      currentTab.value = 1;
      break;
    case "report":
      currentTab.value = 2;
      break;
    default:
      let tabId = endpoint.split("/tab/")[1];
      let customIndex = customTabsDraggable.value.findIndex((tab) => tab.id == Number(tabId));
      currentTab.value = customIndex !== -1 ? staticTabs.value.length + customIndex : 0;
      break;
  }
  await nextTick();
  scrollToCurrentTab()
});

const addTabDialog = ref(false);
const newTab = reactive({ title: "", value: customTabsDraggable.value.length.toString() });

function openAddTabModal() {
  addTabDialog.value = true;
}

function addNewTab() {
  if (newTab.title.trim() !== "") {
    axios.post(route("tab.store"), { tab: newTab.title }).then((response) => {
      customTabsDraggable.value.push(response.data);
      addTabDialog.value = false;
      toast.add({ severity: "success", summary: "Tab Added", detail: "The new tab was created.", life: 3000 });
      location.reload();
    });
  }
}

function openTabMenu(event: MouseEvent, tab: ITab, index: number) {
  selectedContextTab = tab;
  tabContextMenu.value.show(event);
}

function openEditDialog() {
  if (selectedContextTab) {
    editTab.name = selectedContextTab.name;
    editTab.id = selectedContextTab.id;
    editTabDialog.value = true;
  }
}

function saveTabName() {
  if (editTab.name.trim() !== "" && editTab.id) {
    axios.post(route("user.updateTabName"), {
      tab_id: editTab.id,
      name: editTab.name
    }).then(() => {
      // Update the local array
      const tabIndex = customTabsDraggable.value.findIndex(t => t.id === editTab.id);
      if (tabIndex !== -1) {
        customTabsDraggable.value[tabIndex].name = editTab.name;
      }
      editTabDialog.value = false;
      toast.add({ severity: "success", summary: "Tab Updated", detail: "The tab name was updated.", life: 3000 });
    }).catch(() => {
      toast.add({ severity: "error", summary: "Error", detail: "Could not update tab name.", life: 3000 });
    });
  }
}

function deleteContextTab() {
  if (selectedContextTab) {
    confirm.require({
      message: `Are you sure you want to delete "${selectedContextTab.name}"?`,
      header: "Confirm Delete",
      icon: "pi pi-exclamation-triangle",
      acceptClass: "p-button-danger",
      accept: () => {
        removeTab(selectedContextTab);
      },
    });
  }
}

watch(currentTab, (value) => {
  if (value != staticTabs.value.length + customTabsDraggable.value.length + 1) {
    lastTab.value = value;
  }
});

// search the current active tab and scroll to it
function scrollToCurrentTab() {
    const tab = document.querySelector<HTMLElement>('[data-p-active="true"]')
    if (tab){
      tab.scrollIntoView({ behavior: 'smooth', inline: 'center' });
    }
}

function redirectToTab(tab: ITab) {
  let endpoint: string;
  switch (tab.order) {
    case 0:
      endpoint = "/items";
      break;
    case 1:
      endpoint = "/items/hold";
      break;
    case 2:
      endpoint = "/report";
      break;
    default:
      endpoint = `/items/tab/${tab.id}`;
      break;
  }
  const route = `/inventory${endpoint}`;
  router.visit(route);
}

// Drag nativo sobre tab custom
function onDragStartTab(tab: ITab, index: number) {
  draggedTab = tab;
  draggedIndex = index;
  dragging.value = true;
}

function onDragOverTab(overIndex: number) {
  if (draggedIndex === null || draggedIndex === overIndex) return;
  const tab = customTabsDraggable.value.splice(draggedIndex, 1)[0];
  customTabsDraggable.value.splice(overIndex, 0, tab);
  draggedIndex = overIndex;
}

function onDropTab() {
  dragging.value = false;
  draggedTab = null;
  draggedIndex = null;
  reorderTabs();
}

function onDropToDelete() {
  if (draggedTab !== null && draggedIndex !== null) {
    const tabToDelete = draggedTab; // Salvamos la tab antes de limpiar

    confirm.require({
      message: `Are you sure you want to delete "${tabToDelete.name}"?`,
      header: "Confirm Delete",
      icon: "pi pi-exclamation-triangle",
      acceptClass: "p-button-danger",
      accept: () => {
        removeTab(tabToDelete);
        if (currentTab.value === tabToDelete.id) {
          router.visit("/inventory/items");
        } else {
          router.reload({ only: ["tabs"] });
        }
      },
      reject: () => {
        // reset normal si se cancela
        resetDrag();
      },
    });
  } else {
    resetDrag();
  }
}

function resetDrag() {
  dragging.value = false;
  dropZoneActive.value = false;
  draggedTab = null;
  draggedIndex = null;
  shakeDropZone.value = false;
}

function onDragEnd() {
  if (!dropZoneActive.value) {
    // Shake si se suelta fuera de la zona roja
    shakeDropZone.value = true;
    setTimeout(() => (shakeDropZone.value = false), 500);
  }
  dragging.value = false;
  dropZoneActive.value = false;
  draggedTab = null;
  draggedIndex = null;
}

// API reorder
function reorderTabs() {
  const offset = staticTabs.value.length;

  // Revisamos si alguna tab cambió de posición real
  const reordered = customTabsDraggable.value.some((tab, index) => {
    return tab.order !== (offset + index);
  });

  if (!reordered) {
    // No hacemos petición si no hay cambios reales
    return;
  }

  const reorderedTabs = customTabsDraggable.value.map((tab, index) => ({
    id: tab.id,
    order: offset + index,
  }));

  axios.post(route("tab.reorder"), { tab: reorderedTabs }).then(() => {
    toast.add({ severity: "success", summary: "Tabs Reordered", detail: "The tabs have been reordered.", life: 3000 });
    location.reload();
  });
}

function removeTab(tab: ITab | null) {
  if (!tab) return;
  axios.post(route("tab.remove"), { id: tab.id }).then(() => {
    toast.add({ severity: "info", summary: "Tab Deleted", detail: `Tab "${tab.name}" was deleted.`, life: 3000 });
    router.reload({ only: ["tabs"] });
  });
}
</script>

<style scoped>
.tab-handle {
  user-select: none;
}

.shake {
  animation: shake 0.5s ease;
}

@keyframes shake {
  0% {
    transform: translateX(0);
  }
  20% {
    transform: translateX(-4px);
  }
  40% {
    transform: translateX(4px);
  }
  60% {
    transform: translateX(-4px);
  }
  80% {
    transform: translateX(4px);
  }
  100% {
    transform: translateX(0);
  }
}
</style>
