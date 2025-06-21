<template>
    <Dialog
        v-model:visible="dialogVisible"
        header="Load Draft"
        modal
        dismissableMask
        :closable="false"
        class="w-full max-w-2xl mx-auto p-6 bg-white rounded-lg shadow-lg"
    >
        <div class="space-y-6">
            <div class="flex flex-col">
                <label
                    for="draft-select"
                    class="mb-2 text-sm font-medium text-gray-600"
                >
                    Select a draft
                </label>
                <Dropdown
                    id="draft-select"
                    v-model="selectedId"
                    :options="options"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Search drafts..."
                    filter
                    showClear
                    :disabled="loading"
                    class="w-full bg-gray-50 border border-gray-300 rounded px-3 py-2 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-300"
                />
            </div>

            <div v-if="loading" class="text-sm text-gray-500">
                Loading drafts…
            </div>
            <div v-if="error" class="text-sm text-red-600">
                Error loading drafts.
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end space-x-4 pt-4 border-t border-gray-200">
                <Button
                    label="Cancel"
                    class="p-button-text text-gray-600 hover:text-gray-800"
                    @click="onCancel"
                />
                <Button
                    label="Delete"
                    icon="pi pi-trash"
                    class="p-button-text text-red-600 hover:text-red-800"
                    :disabled="!selectedId || loading"
                    @click="onDelete"
                />
                <Button
                    label="Load"
                    :disabled="!selectedId || loading"
                    class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700"
                    @click="onLoad"
                />
            </div>
        </template>
    </Dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import axios from 'axios';

// IMPORTS que faltaban
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import Button from 'primevue/button';
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from 'primevue/useconfirm';

// declare global Ziggy route helper
declare const route: any;

const props = defineProps<{ visible: boolean }>();
const emit  = defineEmits(['update:visible','load']);

const dialogVisible = computed({
  get:  () => props.visible,
  set: v => emit('update:visible', v)
});

const drafts     = ref<{ id:number; title:string; created_at:string }[]>([]);
const selectedId = ref<number|null>(null);
const loading    = ref(false);
const error      = ref(false);

const options = computed(() =>
  drafts.value.map(d => ({
    label: `${d.title} (${d.created_at.split('T')[0]})`,
    value: d.id
  }))
);

watch(() => dialogVisible.value, async v => {
  if (!v) return;
  loading.value = true;
  error.value   = false;
  try {
    let data = await axios.get(route('drafts.simpleList')).then(r=>r.data);
    console.log("drafts que vienen del back: ", data);
    drafts.value = data
  } catch {
    error.value = true;
  } finally {
    loading.value = false;
  }
});

async function onLoad() {
  if (!selectedId.value) return;
  const draft = await axios.get(route('drafts.show', { id: selectedId.value })).then(r=>r.data);
  emit('load', draft);
  dialogVisible.value = false;
}

const confirm = useConfirm();

async function onDelete() {
  if (!selectedId.value) return;
  confirm.require({
    message: 'Are you sure you want to delete this draft?',
    header: 'Confirm Delete',
    icon: 'pi pi-exclamation-triangle',
    accept: async () => {
      // close the confirm dialog immediately
      confirm.close();
      loading.value = true;
      try {
        await axios.delete(route('drafts.destroy', { id: selectedId.value }));
        drafts.value = drafts.value.filter(d => d.id !== selectedId.value);
        selectedId.value = null;
      } catch (e) {
        console.error('Failed to delete draft', e);
      } finally {
        loading.value = false;
      }
    }
  });
}

function onCancel() {
  dialogVisible.value = false;
}
</script>