<template>
<Dialog
    v-model:visible="dialogVisible"
    header="Bill Details"
    modal
    :closable="false"
    class="w-80 max-w-full mx-auto bg-white"
>
    <div class="p-5 space-y-4">
        <div class="flex flex-col">
            <label for="bill-title" class="mb-2 text-sm font-medium text-gray-800">
                Bill Title
            </label>
            <InputText
                id="bill-title"
                v-model="title"
                placeholder="Enter bill title"
                class="w-full px-3 py-2 border border-gray-300 bg-gray-50 rounded focus:outline-none focus:ring-2 focus:ring-gray-400"
            />
        </div>
    </div>

    <template #footer>
        <div class="flex justify-end space-x-2 p-4 border-t border-gray-200">
            <Button
                label="Cancel"
                class="p-button-text text-gray-600 hover:text-gray-800"
                @click="onCancel"
            />
            <Button
                label="Save"
                :disabled="!title"
                @click="save"
                class="bg-gray-800 text-white px-4 py-2 rounded hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
            />
        </div>
    </template>
</Dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';

const props = defineProps({
  visible: { type: Boolean, required: true }
});

const emit = defineEmits<{
  (e: 'update:visible', v: boolean): void;
  (e: 'save', title: string): void;
  (e: 'cancel'): void;
}>();

const title = ref('');
const dialogVisible = computed({
  get: () => props.visible,
  set: v => emit('update:visible', v)
});

function onCancel() {
  emit('cancel');
  dialogVisible.value = false;
}

function save() {
  emit('save', title.value);
  close();
}

function close() {
  dialogVisible.value = false;
}

// reset al cerrar
watch(() => props.visible, v => { if (!v) title.value = '' });
</script>