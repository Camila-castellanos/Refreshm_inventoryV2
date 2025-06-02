<template>
    <Dialog
        v-model:visible="dialogVisible"
        modal
        :closable="false"
        class="w-full max-w-md mx-auto p-6 bg-white rounded-lg shadow-lg"
    >
        <div class="flex flex-col space-y-4">
            <h2 class="text-xl font-semibold text-gray-800">Save Draft</h2>

            <div class="flex flex-col">
                <label for="draft-name" class="mb-1 text-sm text-gray-700">
                    Draft Name
                </label>
                <InputText
                    id="draft-name"
                    v-model="name"
                    placeholder="Enter a name for your draft"
                    class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-200"
                />
            </div>

            <div class="flex justify-end space-x-2">
                <Button
                    label="Cancel"
                    @click="onCancel"
                    class="px-4 py-2 text-gray-700 rounded hover:bg-gray-100 focus:outline-none"
                />
                <Button
                    label="Save"
                    :disabled="!name"
                    @click="onSave"
                    class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed"
                />
            </div>
        </div>
    </Dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'

const props = defineProps({
  visible: { type: Boolean, required: true }
})
const emit = defineEmits<{
  (e: 'update:visible', v: boolean): void;
  (e: 'save', name: string): void;
  (e: 'cancel'): void;
}>()

const name = ref('')
const dialogVisible = computed({
  get: () => props.visible,
  set: v => emit('update:visible', v)
})

watch(() => props.visible, v => {
  if (!v) name.value = ''
})

function onCancel() {
  emit('cancel')
  dialogVisible.value = false
}

function onSave() {
  emit('save', name.value)
  dialogVisible.value = false
}
</script>