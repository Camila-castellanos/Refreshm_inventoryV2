<template>
    <Dialog
        v-model:visible="internalVisible"
        modal
        :closable="true"
        :style="{ width: '90vw', maxWidth: '1200px' }"
        :header="`Manage Photos - ${localItem?.model || ''}`"
        @hide="handleClose"
    >
        <div v-if="loadingPhotos" class="flex justify-center items-center p-12">
            <i class="pi pi-spin pi-spinner text-4xl text-gray-400"></i>
        </div>
        <div v-else-if="localItem" class="space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column - Item Info -->
                <div class="lg:col-span-1">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="text-sm font-semibold text-gray-900 mb-3">Item Information</h4>
                        
                        <!-- Item Preview -->
                        <div class="aspect-square bg-white rounded-lg mb-3 overflow-hidden border border-gray-200">
                            <img
                                v-if="localItem.main_photo_url"
                                :src="localItem.main_photo_url"
                                :alt="localItem.model"
                                class="w-full h-full object-cover"
                            >
                            <div v-else class="w-full h-full flex items-center justify-center">
                                <i class="pi pi-image text-3xl text-gray-400"></i>
                            </div>
                        </div>

                        <!-- Item Details -->
                        <div class="space-y-2 text-xs">
                            <div>
                                <label class="font-medium text-gray-500 uppercase">Model</label>
                                <p class="text-gray-900">{{ localItem.model }}</p>
                            </div>
                            <div v-if="localItem.manufacturer">
                                <label class="font-medium text-gray-500 uppercase">Manufacturer</label>
                                <p class="text-gray-900">{{ localItem.manufacturer }}</p>
                            </div>
                            <div v-if="localItem.type">
                                <label class="font-medium text-gray-500 uppercase">Type</label>
                                <p class="text-gray-900">{{ localItem.type }}</p>
                            </div>
                            <div>
                                <label class="font-medium text-gray-500 uppercase">Photos</label>
                                <p class="text-gray-900">
                                    {{ localItem.photo_count || 0 }} {{ localItem.photo_count === 1 ? 'photo' : 'photos' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Photo Management -->
                <div class="lg:col-span-2">
                    <!-- Upload Area -->
                    <div class="mb-6">
                        <FileUpload
                            ref="fileUploadRef"
                            name="photos[]"
                            :multiple="true"
                            accept="image/*"
                            :maxFileSize="10000000"
                            :customUpload="true"
                            @uploader="handleUpload"
                            @select="onFileSelect"
                            :auto="false"
                            chooseLabel="Select Photos"
                            uploadLabel="Upload"
                            cancelLabel="Clear"
                            :showUploadButton="false"
                            :showCancelButton="false"
                        >
                            <template #empty>
                                <div class="flex flex-col items-center justify-center py-6">
                                    <i class="pi pi-cloud-upload text-3xl text-gray-400 mb-2"></i>
                                    <p class="text-sm text-gray-600 mb-1">Drag and drop files here</p>
                                    <p class="text-xs text-gray-500">PNG, JPG, GIF up to 10MB each</p>
                                </div>
                            </template>
                            
                            <template #header="{ chooseCallback, clearCallback, files }">
                                <div class="flex flex-wrap justify-between items-center gap-2">
                                    <div class="flex gap-2">
                                        <Button
                                            @click="chooseCallback()"
                                            icon="pi pi-images"
                                            label="Choose"
                                            outlined
                                            size="small"
                                        />
                                        <Button
                                            @click="triggerUpload"
                                            icon="pi pi-cloud-upload"
                                            label="Upload"
                                            :disabled="!files || files.length === 0"
                                            :loading="uploading"
                                            :severity="files && files.length > 0 ? 'success' : 'secondary'"
                                            size="small"
                                        />
                                    </div>
                                    <Button
                                        @click="clearCallback()"
                                        icon="pi pi-times"
                                        label="Clear"
                                        outlined
                                        severity="danger"
                                        :disabled="!files || files.length === 0"
                                        size="small"
                                    />
                                </div>
                            </template>
                        </FileUpload>
                    </div>

                    <!-- Photos Grid -->
                    <div v-if="modalPhotos.length > 0">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-sm font-semibold text-gray-900">Current Photos</h4>
                            <span class="text-xs text-gray-500">{{ modalPhotos.length }} total</span>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 max-h-96 overflow-y-auto">
                            <div
                                v-for="(photo, index) in modalPhotos"
                                :key="photo.id"
                                class="relative group bg-gray-100 rounded-lg overflow-hidden aspect-square"
                                draggable="true"
                                @dragstart="handleDragStart(index, $event)"
                                @dragover.prevent="handleDragOver(index)"
                                @drop="handlePhotoDrop(index)"
                                @dragend="handleDragEnd"
                            >
                                <!-- Photo -->
                                <img
                                    :src="photo.preview"
                                    :alt="photo.name"
                                    class="w-full h-full object-cover"
                                >

                                <!-- Overlay -->
                                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-40 transition-all duration-200">
                                    <!-- Actions -->
                                    <div class="absolute top-2 right-2 flex space-x-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <!-- Drag Handle -->
                                        <button
                                            type="button"
                                            class="p-1.5 bg-white rounded-md shadow-sm hover:bg-gray-50 cursor-move"
                                            title="Drag to reorder"
                                        >
                                            <i class="pi pi-arrows-alt text-xs text-gray-600"></i>
                                        </button>

                                        <!-- Delete -->
                                        <button
                                            type="button"
                                            @click="confirmDelete(photo)"
                                            class="p-1.5 bg-white rounded-md shadow-sm hover:bg-red-50"
                                            title="Delete photo"
                                        >
                                            <i class="pi pi-trash text-xs text-red-600"></i>
                                        </button>
                                    </div>

                                    <!-- Primary Badge -->
                                    <div v-if="index === 0" class="absolute bottom-2 left-2">
                                        <span class="inline-flex items-center px-2 py-1 rounded-md bg-blue-100 text-blue-800 text-xs font-medium">
                                            <i class="pi pi-star-fill text-xs mr-1"></i>
                                            Primary
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="text-center py-8 bg-gray-50 rounded-lg">
                        <i class="pi pi-images text-3xl text-gray-300 mb-2"></i>
                        <h4 class="text-sm font-medium text-gray-900 mb-1">No photos yet</h4>
                        <p class="text-xs text-gray-600">Upload photos to showcase this item</p>
                    </div>
                </div>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end">
                <Button
                    @click="handleClose"
                    label="Close"
                    severity="secondary"
                    outlined
                />
            </div>
        </template>
    </Dialog>

    <!-- Delete Confirmation Dialog -->
    <Dialog
        v-model:visible="showDeleteConfirm"
        modal
        :closable="true"
        :style="{ width: '450px' }"
        header="Delete Photo"
    >
        <div class="space-y-4">
            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                        <i class="pi pi-exclamation-triangle text-red-600"></i>
                    </div>
                </div>
                <div class="flex-1">
                    <p class="text-sm text-gray-700">
                        Are you sure you want to delete this photo?
                    </p>
                    <p class="text-sm text-gray-600 mt-2">
                        This action cannot be undone.
                    </p>
                </div>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end space-x-2">
                <Button
                    @click="showDeleteConfirm = false"
                    label="Cancel"
                    severity="secondary"
                    outlined
                    :disabled="deleteForm.processing"
                />

                <Button
                    @click="deletePhoto"
                    label="Delete Photo"
                    icon="pi pi-trash"
                    severity="danger"
                    :loading="deleteForm.processing"
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
import FileUpload from 'primevue/fileupload'
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

const emit = defineEmits(['update:visible', 'refresh'])

const internalVisible = ref(props.visible)
const localItem = ref(props.item)
const modalPhotos = ref([])
const loadingPhotos = ref(false)
const uploading = ref(false)
const showDeleteConfirm = ref(false)
const photoToDelete = ref(null)
const draggedIndex = ref(null)
const fileUploadRef = ref(null)
const deleteForm = useForm({})

const marketId = computed(() => props.market?.id)

watch(() => props.visible, (newVal) => {
    internalVisible.value = newVal
})

watch(() => props.item, (newVal) => {
    localItem.value = newVal
    if (internalVisible.value && newVal?.id) {
        fetchPhotos(newVal)
    }
})

watch(internalVisible, (newVal) => {
    emit('update:visible', newVal)
    if (newVal && localItem.value?.id) {
        fetchPhotos(localItem.value)
    }
})

const fetchPhotos = async (item) => {
    if (!item?.id || !marketId.value) return

    loadingPhotos.value = true
    try {
        const url = route('ecommerce.items.edit', {
            market: marketId.value,
            item: item.id,
            format: 'json'
        })

        const response = await axios.get(url, {
            headers: { Accept: 'application/json' }
        })

        if (response.data.props && response.data.props.item) {
            modalPhotos.value = response.data.props.item.photo_urls || []
            localItem.value = { ...localItem.value, ...response.data.props.item }
        }
    } catch (error) {
        modalPhotos.value = []
    } finally {
        loadingPhotos.value = false
    }
}

const handleClose = () => {
    resetState()
    emit('update:visible', false)
    emit('refresh')
}

const resetState = () => {
    modalPhotos.value = []
    loadingPhotos.value = false
    uploading.value = false
    showDeleteConfirm.value = false
    photoToDelete.value = null
    draggedIndex.value = null
    internalVisible.value = false
    localItem.value = null
}

const onFileSelect = () => {
    // No-op hook for future use
}

const triggerUpload = () => {
    if (fileUploadRef.value) {
        const files = fileUploadRef.value.files
        if (files && files.length > 0) {
            handleUpload({ files })
        }
    }
}

const handleUpload = async (event) => {
    const files = event.files

    if (!files || files.length === 0 || !localItem.value) return

    uploading.value = true

    const formData = new FormData()
    files.forEach(file => {
        formData.append('photos[]', file)
    })

    try {
        const uploadUrl = route('ecommerce.items.upload', {
            market: marketId.value,
            item: localItem.value.id
        })
        
        await axios.post(
            uploadUrl,
            formData,
            {
                headers: {
                    'Content-Type': 'multipart/form-data',
                    'Accept': 'application/json'
                }
            }
        )

        await fetchPhotos(localItem.value)
        event.files.length = 0
    } catch (error) {
        alert('Failed to upload photos. Please try again.')
    } finally {
        uploading.value = false
    }
}

const confirmDelete = (photo) => {
    photoToDelete.value = photo
    showDeleteConfirm.value = true
}

const deletePhoto = async () => {
    if (!photoToDelete.value || !localItem.value) return

    deleteForm.delete(
        route('ecommerce.items.delete', {
            market: marketId.value,
            item: localItem.value.id,
            media: photoToDelete.value.id
        }),
        {
            onSuccess: async () => {
                await fetchPhotos(localItem.value)
                showDeleteConfirm.value = false
                photoToDelete.value = null
            },
            onFinish: () => {
                deleteForm.reset()
            }
        }
    )
}

// Drag and drop for reordering photos
const handleDragStart = (index, event) => {
    draggedIndex.value = index
    event.dataTransfer.effectAllowed = 'move'
}

const handleDragOver = (index) => {
    if (draggedIndex.value === null || draggedIndex.value === index) return
    
    const items = [...modalPhotos.value]
    const draggedItem = items[draggedIndex.value]
    items.splice(draggedIndex.value, 1)
    items.splice(index, 0, draggedItem)
    
    modalPhotos.value = items
    draggedIndex.value = index
}

const handlePhotoDrop = (index) => {
    if (draggedIndex.value === null) return
    handleReorder()
}

const handleDragEnd = () => {
    draggedIndex.value = null
}

const handleReorder = async () => {
    if (!localItem.value) return
    
    const photoIds = modalPhotos.value.map(photo => photo.id)
    
    try {
        await axios.post(
            route('ecommerce.items.reorder', {
                market: marketId.value,
                item: localItem.value.id
            }),
            { photo_ids: photoIds }
        )
    } catch (error) {
        await fetchPhotos(localItem.value)
    }
}
</script>

<style scoped>
/* Drag cursor */
[draggable="true"] {
    cursor: move;
}

/* Drag states */
[draggable="true"]:active {
    opacity: 0.5;
}
</style>
