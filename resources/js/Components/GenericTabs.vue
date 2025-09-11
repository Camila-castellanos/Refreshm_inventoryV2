<template>
  <div>
    <section class="w-full mx-auto">
      <Tabs v-model:value="currentValue" scrollable>
        <TabList class="bg-white">
          <!-- Static tabs -->
          <Tab
            v-for="(tab, index) in staticTabsNormalized"
            :key="'static-' + getStaticKey(index)"
            :value="getStaticValue(index)"
            @click="onTabClick(tab, 'static', index)"
          >
            <span>{{ tab.name }}</span>
          </Tab>

          <!-- Custom tabs (draggable optional) -->
          <Tab
            v-for="(tab, index) in customTabsDraggable"
            :key="'custom-' + tab.id"
            :value="getCustomValue(tab)"
            @click="onTabClick(tab, 'custom', index)"
            class="!relative"
            :draggable="enableDrag"
            @dragstart="enableDrag ? onDragStartTab(tab, index) : undefined"
            @dragover.prevent="enableDrag ? onDragOverTab(index) : undefined"
            @drop.prevent="enableDrag ? onDropTab() : undefined"
            @dragend="enableDrag ? onDragEnd() : undefined"
          >
            <div class="flex items-center space-x-2" :class="enableDrag ? 'cursor-move tab-handle' : ''">
              <span>{{ tab.name }}</span>
            </div>
          </Tab>

          <!-- Add / Delete drop-zone -->
          <Tab
            v-if="showAddTab || showDeleteDropZone"
            :value="'meta:add-delete'"
            :class="[dropZoneActive || dragging ? 'bg-red-100 border border-red-500' : '', shakeDropZone ? 'shake' : '']"
            @click="showAddTab && !dragging ? (addTabDialog = true) : undefined"
            @dragover.prevent="showDeleteDropZone ? (dropZoneActive = true) : undefined"
            @dragleave="showDeleteDropZone ? (dropZoneActive = false) : undefined"
            @drop.prevent="showDeleteDropZone ? onDropToDelete() : undefined"
            @dragend="showDeleteDropZone ? onDragEnd() : undefined"
          >
            <div class="flex items-center justify-center space-x-2">
              <i :class="dropZoneActive ? 'pi pi-trash text-red-500' : 'pi pi-plus'" />
              <span v-if="dropZoneActive" class="text-red-500 text-sm">Drop to Delete</span>
              <span v-else-if="showAddTab" class="text-sm">Add Tab</span>
            </div>
          </Tab>
        </TabList>
      </Tabs>

      <!-- Parent content goes here -->
      <slot />
    </section>
  </div>

  <!-- Add new tab dialog (optional) -->
  <Dialog
    v-if="showAddTab"
    v-model:visible="addTabDialog"
    :header="addDialogHeader"
    modal
  >
    <div class="p-fluid">
      <div class="flex flex-col py-3">
        <label for="tabTitle">Tab Title</label>
        <InputText id="tabTitle" v-model="newTab.title" @keydown.enter.prevent="addNewTab" />
      </div>
      <div class="w-full mt-5">
        <Button label="Add" @click="addNewTab" class="w-full" />
      </div>
    </div>
  </Dialog>
</template>

<script setup>
import Button from "primevue/button";
import Dialog from "primevue/dialog";
import InputText from "primevue/inputtext";
import Tab from "primevue/tab";
import TabList from "primevue/tablist";
import Tabs from "primevue/tabs";
import { computed, nextTick, onMounted, reactive, ref, watch } from "vue";

const props = defineProps({
  // Fixed tabs shown first
  staticTabs: {
  type: Array,
    default: () => [],
  },
  // Draggable/custom tabs from parent
  customTabs: {
  type: Array,
    required: true,
  },
  // v-model value for selected tab: 's:index' or 'c:id'
  modelValue: {
    type: String,
    default: "",
  },
  // Behavior flags
  enableDrag: {
    type: Boolean,
    default: true,
  },
  showAddTab: {
    type: Boolean,
    default: true,
  },
  showDeleteDropZone: {
    type: Boolean,
    default: true,
  },
  addDialogHeader: {
    type: String,
    default: "Add New Tab",
  },
});

const emit = defineEmits([
  'update:modelValue',
  'tab-click',
  'request-add',
  'reorder',
  'request-remove'
]);

// Normalized static tabs
const staticTabsNormalized = computed(() =>
  (props.staticTabs || []).map((t, i) => ({
    name: t.name,
    order: t.order ?? i,
    id: t.id,
  }))
);

// Local draggable copy of custom tabs
const customTabsDraggable = ref([]);
watch(
  () => props.customTabs,
  (tabs) => {
    const sorted = [...(tabs || [])].sort((a, b) => (a.order ?? 0) - (b.order ?? 0));
    customTabsDraggable.value = sorted.map((t) => ({ name: t.name, order: t.order, id: t.id }));
  },
  { immediate: true, deep: true }
);

// Helpers
function getStaticKey(index) {
  return index;
}
function getStaticValue(index) {
  return `s:${index}`;
}
function getCustomValue(tab) {
  return `c:${tab.id}`;
}

// Selected value control
const currentValue = ref("");
function computeInitialValue() {
  if (props.modelValue) return props.modelValue;
  if (staticTabsNormalized.value.length > 0) return getStaticValue(0);
  if (customTabsDraggable.value.length > 0) return getCustomValue(customTabsDraggable.value[0]);
  return "";
}
onMounted(async () => {
  currentValue.value = computeInitialValue();
  await nextTick();
  scrollToCurrentTab();
});
watch(
  () => props.modelValue,
  (val) => {
    if (val !== undefined && val !== currentValue.value) {
      currentValue.value = val;
    }
  }
);
watch(currentValue, async () => {
  await nextTick();
  scrollToCurrentTab();
});

// Add dialog state
const addTabDialog = ref(false);
const newTab = reactive({ title: "" });

// Drag & drop state
const dragging = ref(false);
const dropZoneActive = ref(false);
const shakeDropZone = ref(false);
let draggedTab = null;
let draggedIndex = null;

// UI helper
function scrollToCurrentTab() {
  const tab = document.querySelector<HTMLElement>('[data-p-active="true"]');
  if (tab) {
    tab.scrollIntoView({ behavior: "smooth", inline: "center" });
  }
}

// Interactions
function onTabClick(tab, kind, index) {
  const value = kind === "static" ? getStaticValue(index) : getCustomValue(tab);
  currentValue.value = value;
  emit("update:modelValue", value);
  emit("tab-click", { kind, tab, index, value });
}

function addNewTab() {
  if (newTab.title.trim() === "") return;
  emit("request-add", { title: newTab.title.trim() });
  addTabDialog.value = false;
  newTab.title = "";
}

function onDragStartTab(tab, index) {
  draggedTab = tab;
  draggedIndex = index;
  dragging.value = true;
}

function onDragOverTab(overIndex) {
  if (!props.enableDrag) return;
  if (draggedIndex === null || draggedIndex === overIndex) return;
  const tab = customTabsDraggable.value.splice(draggedIndex, 1)[0];
  customTabsDraggable.value.splice(overIndex, 0, tab);
  draggedIndex = overIndex;
}

function onDropTab() {
  dragging.value = false;
  draggedTab = null;
  draggedIndex = null;

  // Emit new order
  const offset = staticTabsNormalized.value.length;
  const reordered = customTabsDraggable.value.map((tab, index) => ({
    id: tab.id,
    order: offset + index,
  }));
  emit("reorder", reordered);
}

function onDropToDelete() {
  if (draggedTab && draggedIndex !== null) {
    emit("request-remove", { tab: draggedTab });
  }
  resetDrag();
}

function onDragEnd() {
  if (!dropZoneActive.value) {
    shakeDropZone.value = true;
    setTimeout(() => (shakeDropZone.value = false), 500);
  }
  resetDrag();
}

function resetDrag() {
  dragging.value = false;
  dropZoneActive.value = false;
  draggedTab = null;
  draggedIndex = null;
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
