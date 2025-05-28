<template>
<Dialog
    v-model:visible="dialogVisible"
    header="Create New Tax"
    :modal="true"
    :closable="false"
    class="w-full max-w-lg rounded-lg shadow-lg bg-white p-6"
>
    <div class="space-y-6">
        <div class="flex flex-col">
            <label for="tax-name" class="mb-2 text-gray-700 font-medium">Name</label>
            <InputText
                id="tax-name"
                v-model="name"
                :disabled="isSaving"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 "
            />
        </div>
        <div class="flex flex-col">
            <label for="tax-perc" class="mb-2 text-gray-700 font-medium">Percentage %</label>
            <InputNumber
                id="tax-perc"
                v-model="percentage"
                suffix="%"
                mode="decimal"
                :min="0"
                :max="100"
                :disabled="isSaving"
                class="w-full  rounded-lg  focus:outline-none focus:ring-2 "
            />
        </div>
    </div>
    <template #footer>
        <div class="flex justify-end space-x-3 mt-6">
            <Button
                label="Cancel"
                class="p-button-text text-gray-500 hover:text-gray-700"
                :disabled="isSaving"
                @click="close"
            />
            <Button
                label="Save"
                :loading="isSaving"
                :disabled="!name || percentage == null"
                @click="createTax"
                class="bg-black  text-white px-5 py-2 rounded-lg"
            />
        </div>
    </template>
</Dialog>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import axios from 'axios';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Button from 'primevue/button';

const props = defineProps({
  visible: { type: Boolean, required: true }
});

// puente entre prop y emit:
const dialogVisible = computed({
  get: () => props.visible,
  set: v => emit('update:visible', v)
});

const emit = defineEmits<{
  (e: 'update:visible', visible: boolean): void;
  (e: 'created', tax: { id: number; name: string; percentage: number }): void;
}>();

const name = ref('');
const percentage = ref<number | null>(null);
const isSaving = ref(false);

// Reset campos cuando se cierra
watch(() => props.visible, (isOpen) => {
  if (!isOpen) {
    name.value = '';
    percentage.value = null;
    isSaving.value = false;
  }
});

function close() {
  emit('update:visible', false);
}

async function createTax() {
  if (!name.value || percentage.value == null) return;
  isSaving.value = true;
  try {
    const { data } = await axios.post(route('tax.store'), {
      name: name.value,
      percentage: percentage.value
    });
    emit('created', data);
    console.log('Tax created:', data);
    close();
  } catch (error) {
    console.error(error);
  } finally {
    isSaving.value = false;
  }
}
</script>