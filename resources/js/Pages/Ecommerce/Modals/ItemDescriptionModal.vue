<template>
    <Dialog
        v-model:visible="internalVisible"
        modal
        :closable="true"
        :style="{ width: '1200px', maxWidth: '90vw' }"
        :header="`Edit Description - ${localItem?.model || ''}`"
        @hide="handleClose"
    >
        <div v-if="descriptionLoading" class="flex justify-center items-center p-8">
            <i class="pi pi-spin pi-spinner text-3xl text-gray-400"></i>
        </div>
        <div v-else-if="localItem" class="space-y-4">
            <div class="bg-gray-50 rounded-lg p-3 text-sm text-gray-700">
                <p class="font-semibold text-gray-900">{{ localItem.model }}</p>
                <p v-if="localItem.manufacturer" class="text-gray-600">{{ localItem.manufacturer }}</p>
                <p v-if="localItem.imei" class="text-gray-500 text-xs mt-1">IMEI: {{ localItem.imei }}</p>
            </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <Editor
                        v-model="descriptionForm.description"
                        editorStyle="height: 240px"
                        class="border border-gray-300 rounded-md"
                    />
                    <p v-if="descriptionForm.errors.description" class="text-xs text-red-600 mt-1">
                        {{ descriptionForm.errors.description }}
                    </p>
                </div>
        </div>

        <template #footer>
            <div class="flex justify-end space-x-2">
                <Button
                    @click="handleClose"
                    label="Cancel"
                    severity="secondary"
                    outlined
                    :disabled="descriptionForm.processing"
                />
                <Button
                    @click="saveDescription"
                    label="Save Description"
                    icon="pi pi-check"
                    :loading="descriptionForm.processing"
                />
            </div>
        </template>
    </Dialog>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import Editor from 'primevue/editor'
import axios from 'axios'

const props = defineProps({
    visible: {
        type: Boolean,
        default: false,
    },
    item: {
        type: Object,
        default: null,
    },
    market: {
        type: Object,
        default: null,
    },
})

const emit = defineEmits(['update:visible', 'refresh', 'saved'])

const internalVisible = ref(props.visible)
const localItem = ref(props.item)
const descriptionLoading = ref(false)
const descriptionForm = useForm({
    description: '',
})
const editorModules = {
    toolbar: [
        ['bold', 'italic', 'underline', 'strike'],
        [{ list: 'ordered' }, { list: 'bullet' }],
        [{ header: [1, 2, 3, false] }],
        ['link', 'image'],
        ['clean'],
    ],
}

const marketId = computed(() => props.market?.id)

watch(() => props.visible, (newVal) => {
    internalVisible.value = newVal
})

watch(() => props.item, async (newVal) => {
    localItem.value = newVal
    if (internalVisible.value && newVal?.id) {
        await loadItemData(newVal)
    }
})

watch(internalVisible, async (newVal) => {
    emit('update:visible', newVal)
    if (newVal && localItem.value?.id) {
        await loadItemData(localItem.value)
    }
})

const loadItemData = async (item) => {
    if (!item?.id || !marketId.value) return

    descriptionLoading.value = true
    descriptionForm.clearErrors()

    try {
        const response = await axios.get(
            route('ecommerce.items.edit', {
                market: marketId.value,
                item: item.id,
                format: 'json'
            }),
            { headers: { Accept: 'application/json' } }
        )

        if (response.data.props && response.data.props.item) {
            const itemData = response.data.props.item
            localItem.value = { ...localItem.value, ...itemData }
            descriptionForm.description = itemData.description || ''
        } else {
            descriptionForm.description = item.description || ''
        }
    } catch (error) {
        descriptionForm.description = item.description || ''
    } finally {
        descriptionLoading.value = false
    }
}

const handleClose = () => {
    resetState()
    emit('update:visible', false)
}

const resetState = () => {
    internalVisible.value = false
    descriptionLoading.value = false
    localItem.value = null
    descriptionForm.reset()
    descriptionForm.clearErrors()
}

const saveDescription = () => {
    if (!localItem.value) return

    descriptionForm.processing = true
    descriptionForm.clearErrors()

    axios.post(
        route('ecommerce.items.update-description', {
            market: marketId.value,
            item: localItem.value.id
        }),
        { description: descriptionForm.description },
        { headers: { Accept: 'application/json' } }
    )
    .then(() => {
        emit('saved', {
            id: localItem.value.id,
            description: descriptionForm.description,
        })
        emit('refresh')
        handleClose()
    })
    .catch((error) => {
        if (error.response?.status === 422 && error.response.data?.errors) {
            descriptionForm.setErrors(error.response.data.errors)
        } else {
            console.error('Description update error:', error)
            alert('Failed to update description. Please try again.')
        }
    })
    .finally(() => {
        descriptionForm.processing = false
    })
}
</script>
