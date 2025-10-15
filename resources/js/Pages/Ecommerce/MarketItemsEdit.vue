<template>
    <AppLayout title="Manage Item Photos">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">Manage Market Items</h2>
                            <p class="text-sm text-gray-600 mt-1">
                                Market: <span class="font-medium">{{ market.name }}</span>
                            </p>
                        </div>
                        <Link
                            :href="route('ecommerce.markets.index')"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                        >
                            <i class="pi pi-arrow-left text-xs mr-2"></i>
                            Back to Markets
                        </Link>
                    </div>

                    <!-- Search Bar -->
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="pi pi-search text-gray-400 text-sm"></i>
                        </div>
                        <input
                            v-model="searchQuery"
                            @input="handleSearch"
                            type="text"
                            placeholder="Search items by model, manufacturer, IMEI, type, colour..."
                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-gray-500 focus:border-gray-500 sm:text-sm"
                        >
                        <div v-if="searchQuery" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <button
                                @click="clearSearch"
                                class="text-gray-400 hover:text-gray-600"
                                type="button"
                            >
                                <i class="pi pi-times text-sm"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Items List -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <div v-if="items.data && items.data.length > 0">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 p-6">
                            <div
                                v-for="item in items.data"
                                :key="item.id"
                                class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow duration-200"
                            >
                                <!-- Item Image -->
                                <div class="aspect-square bg-gray-100 rounded-lg mb-3 overflow-hidden">
                                    <img
                                        v-if="item.main_photo_thumb"
                                        :src="item.main_photo_thumb"
                                        :alt="item.model"
                                        class="w-full h-full object-cover"
                                    >
                                    <div v-else class="w-full h-full flex items-center justify-center">
                                        <i class="pi pi-mobile text-4xl text-gray-400"></i>
                                    </div>
                                </div>

                                <!-- Item Info -->
                                <div class="mb-3">
                                    <h3 class="text-sm font-semibold text-gray-900 truncate">{{ item.model }}</h3>
                                    <p v-if="item.manufacturer" class="text-xs text-gray-500 truncate">
                                        {{ item.manufacturer }}
                                    </p>
                                    <div class="flex items-center mt-2 text-xs text-gray-600">
                                        <i class="pi pi-images text-xs mr-1"></i>
                                        <span>{{ item.photo_count || 0 }} {{ item.photo_count === 1 ? 'photo' : 'photos' }}</span>
                                    </div>
                                </div>

                                <!-- Manage Photos Button -->
                                <Button
                                    @click="openPhotoModal(item)"
                                    label="Manage Photos"
                                    icon="pi pi-images"
                                    class="w-full"
                                    size="small"
                                    severity="secondary"
                                    outlined
                                />
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div v-if="items.links && items.links.length > 3" class="border-t border-gray-200 px-6 py-4">
                            <nav class="flex items-center justify-between">
                                <!-- Mobile Pagination -->
                                <div class="flex-1 flex justify-between sm:hidden">
                                    <Link
                                        v-if="items.prev_page_url"
                                        :href="items.prev_page_url"
                                        class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                    >
                                        Previous
                                    </Link>
                                    <Link
                                        v-if="items.next_page_url"
                                        :href="items.next_page_url"
                                        class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                    >
                                        Next
                                    </Link>
                                </div>
                                
                                <!-- Desktop Pagination -->
                                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                    <div>
                                        <p class="text-sm text-gray-700">
                                            Showing
                                            <span class="font-medium">{{ items.from || 0 }}</span>
                                            to
                                            <span class="font-medium">{{ items.to || 0 }}</span>
                                            of
                                            <span class="font-medium">{{ items.total || 0 }}</span>
                                            items
                                        </p>
                                    </div>
                                    <div>
                                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                            <template v-for="(link, index) in items.links" :key="index">
                                                <!-- Previous Button -->
                                                <Link
                                                    v-if="index === 0"
                                                    :href="link.url"
                                                    :class="[
                                                        'relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 text-sm font-medium',
                                                        link.url ? 'bg-white text-gray-500 hover:bg-gray-50' : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                                    ]"
                                                    :disabled="!link.url"
                                                    preserve-scroll
                                                >
                                                    <span class="sr-only">Previous</span>
                                                    <i class="pi pi-chevron-left text-xs"></i>
                                                </Link>
                                                
                                                <!-- Page Numbers -->
                                                <Link
                                                    v-else-if="index !== items.links.length - 1"
                                                    :href="link.url"
                                                    :class="[
                                                        'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                                                        link.active 
                                                            ? 'z-10 bg-gray-800 border-gray-800 text-white' 
                                                            : link.url 
                                                                ? 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'
                                                                : 'bg-gray-100 border-gray-300 text-gray-400 cursor-not-allowed'
                                                    ]"
                                                    :disabled="!link.url"
                                                    preserve-scroll
                                                    v-html="link.label"
                                                />
                                                
                                                <!-- Next Button -->
                                                <Link
                                                    v-else
                                                    :href="link.url"
                                                    :class="[
                                                        'relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 text-sm font-medium',
                                                        link.url ? 'bg-white text-gray-500 hover:bg-gray-50' : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                                    ]"
                                                    :disabled="!link.url"
                                                    preserve-scroll
                                                >
                                                    <span class="sr-only">Next</span>
                                                    <i class="pi pi-chevron-right text-xs"></i>
                                                </Link>
                                            </template>
                                        </nav>
                                    </div>
                                </div>
                            </nav>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="text-center py-12">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-lg flex items-center justify-center bg-gray-100">
                            <i class="pi pi-inbox text-2xl text-gray-500"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No items found</h3>
                        <p class="text-gray-600">This market doesn't have any items yet.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Photo Management Modal -->
        <Dialog
            v-model:visible="showPhotoModal"
            modal
            :closable="true"
            :style="{ width: '90vw', maxWidth: '1200px' }"
            :header="`Manage Photos - ${selectedItem?.model || ''}`"
        >
            <div v-if="selectedItem" class="space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Left Column - Item Info -->
                    <div class="lg:col-span-1">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="text-sm font-semibold text-gray-900 mb-3">Item Information</h4>
                            
                            <!-- Item Preview -->
                            <div class="aspect-square bg-white rounded-lg mb-3 overflow-hidden border border-gray-200">
                                <img
                                    v-if="selectedItem.main_photo_url"
                                    :src="selectedItem.main_photo_url"
                                    :alt="selectedItem.model"
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
                                    <p class="text-gray-900">{{ selectedItem.model }}</p>
                                </div>
                                <div v-if="selectedItem.manufacturer">
                                    <label class="font-medium text-gray-500 uppercase">Manufacturer</label>
                                    <p class="text-gray-900">{{ selectedItem.manufacturer }}</p>
                                </div>
                                <div v-if="selectedItem.type">
                                    <label class="font-medium text-gray-500 uppercase">Type</label>
                                    <p class="text-gray-900">{{ selectedItem.type }}</p>
                                </div>
                                <div>
                                    <label class="font-medium text-gray-500 uppercase">Photos</label>
                                    <p class="text-gray-900">
                                        {{ selectedItem.photo_count || 0 }} {{ selectedItem.photo_count === 1 ? 'photo' : 'photos' }}
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
                        @click="closePhotoModal"
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
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { useForm, router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import FileUpload from 'primevue/fileupload'
import axios from 'axios'

// Props
const props = defineProps({
    market: Object,
    items: Object,
    filters: Object,
})

// State
const showPhotoModal = ref(false)
const selectedItem = ref(null)
const modalPhotos = ref([])
const uploading = ref(false)
const showDeleteConfirm = ref(false)
const photoToDelete = ref(null)
const draggedIndex = ref(null)
const searchQuery = ref(props.filters?.search || '')
const fileUploadRef = ref(null)
let searchTimeout = null

// Forms
const deleteForm = useForm({})

// Methods
const handleSearch = () => {
    // Clear existing timeout
    if (searchTimeout) {
        clearTimeout(searchTimeout)
    }
    
    // Set new timeout for debounced search
    searchTimeout = setTimeout(() => {
        router.get(
            route('ecommerce.items.index', props.market.id),
            { search: searchQuery.value },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            }
        )
    }, 300) // 300ms debounce
}

const clearSearch = () => {
    searchQuery.value = ''
    router.get(
        route('ecommerce.items.index', props.market.id),
        {},
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    )
}

// Methods
const openPhotoModal = async (item) => {
    selectedItem.value = item
    showPhotoModal.value = true
    
    // Load item photos
    try {
        const response = await axios.get(route('ecommerce.items.edit', {
            market: props.market.id,
            item: item.id
        }))
        
        // Extract photos from response
        if (response.data.props && response.data.props.item) {
            modalPhotos.value = response.data.props.item.photo_urls || []
            selectedItem.value = { ...selectedItem.value, ...response.data.props.item }
        }
    } catch (error) {
        console.error('Error loading photos:', error)
        modalPhotos.value = []
    }
}

const closePhotoModal = () => {
    showPhotoModal.value = false
    selectedItem.value = null
    modalPhotos.value = []
    
    // Reload the page to refresh item photo counts
    router.reload({ only: ['items'] })
}

const triggerUpload = () => {
    console.log('triggerUpload called')
    console.log('fileUploadRef:', fileUploadRef.value)
    if (fileUploadRef.value) {
        fileUploadRef.value.upload()
    }
}

const handleUpload = async (event) => {
    console.log('Uploading files')
    const files = event.files


    if (!files || files.length === 0 || !selectedItem.value) return

    console.log('Uploading files verification passed:', files)

    uploading.value = true

    const formData = new FormData()
    files.forEach(file => {
        formData.append('photos[]', file)
    })

    try {
        await router.post(
            route('ecommerce.items.upload', {
                market: props.market.id,
                item: selectedItem.value.id
            }),
            formData,
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: async () => {
                    // Reload photos
                    await openPhotoModal(selectedItem.value)
                    
                    // Clear the FileUpload component
                    event.files.length = 0
                },
                onError: (errors) => {
                    console.error('Upload error:', errors)
                    alert('Failed to upload photos. Please try again.')
                },
                onFinish: () => {
                    uploading.value = false
                }
            }
        )
    } catch (error) {
        console.error('Upload error:', error)
        alert('Failed to upload photos. Please try again.')
        uploading.value = false
    }
}

const confirmDelete = (photo) => {
    photoToDelete.value = photo
    showDeleteConfirm.value = true
}

const deletePhoto = async () => {
    if (!photoToDelete.value || !selectedItem.value) return

    deleteForm.delete(
        route('ecommerce.items.delete', {
            market: props.market.id,
            item: selectedItem.value.id,
            media: photoToDelete.value.id
        }),
        {
            onSuccess: async () => {
                // Reload photos
                await openPhotoModal(selectedItem.value)
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
    
    // Visual feedback
    const items = [...modalPhotos.value]
    const draggedItem = items[draggedIndex.value]
    items.splice(draggedIndex.value, 1)
    items.splice(index, 0, draggedItem)
    
    modalPhotos.value = items
    draggedIndex.value = index
}

const handlePhotoDrop = (index) => {
    if (draggedIndex.value === null) return
    
    // Send new order to backend
    handleReorder()
}

const handleDragEnd = () => {
    draggedIndex.value = null
}

const handleReorder = async () => {
    if (!selectedItem.value) return
    
    // Send new order to backend
    const photoIds = modalPhotos.value.map(photo => photo.id)
    
    try {
        await axios.post(
            route('ecommerce.items.reorder', {
                market: props.market.id,
                item: selectedItem.value.id
            }),
            { photo_ids: photoIds }
        )
    } catch (error) {
        console.error('Reorder error:', error)
        // Reload photos on error
        await openPhotoModal(selectedItem.value)
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
