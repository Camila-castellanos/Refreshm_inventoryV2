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
                                        v-if="item.photo"
                                        :src="item.photo"
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

                                <!-- Buttons -->
                                <div class="space-y-2">
                                    <Button
                                        @click="openPhotoModal(item)"
                                        label="Manage Photos"
                                        icon="pi pi-images"
                                        class="w-full"
                                        size="small"
                                        severity="secondary"
                                        outlined
                                    />
                                    <Button
                                        @click="openModelItemsModal(item)"
                                        label="View All Items"
                                        icon="pi pi-th"
                                        class="w-full"
                                        size="small"
                                        severity="info"
                                        outlined
                                    />
                                </div>
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

        <ItemPhotosModal
            :visible="showPhotoModal"
            :item="photoItem"
            :market="market"
            @update:visible="handlePhotoModalVisible"
            @refresh="refreshItems"
        />

        <!-- Model Items Modal -->
        <ModelItemsModal
            :visible="showModelItemsModal"
            :model="selectedModel"
            :items="modelItems"
            :market="market"
            @update:visible="showModelItemsModal = $event"
            @view-item="handleViewItem"
            @edit-item="handleEditItem"
        />
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from 'primevue/button'
import ModelItemsModal from '@/Pages/Ecommerce/Modals/ModelItemsModal.vue'
import ItemPhotosModal from '@/Pages/Ecommerce/Modals/ItemPhotosModal.vue'
import axios from 'axios'

// Props
const props = defineProps({
    market: Object,
    models: Object,
    filters: Object,
})

// Computed - normalize models to items for compatibility
const items = computed(() => {
    const models = props.models
    
    if (!models) return { data: [] }
    
    // If models is an array, wrap it
    if (Array.isArray(models)) {
        return { data: models }
    }
    
    // If models already has data property, use it
    if (models.data) {
        return models
    }
    
    // Otherwise wrap it
    return { data: models }
})

// State
const showPhotoModal = ref(false)
const photoItem = ref(null)
const searchQuery = ref(props.filters?.search || '')
const showModelItemsModal = ref(false)
const selectedModel = ref(null)
const modelItems = ref([])
let searchTimeout = null

onMounted(() => {
    // Component mounted
})

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
const openPhotoModal = (item) => {
    photoItem.value = item
    showPhotoModal.value = true
}

const handlePhotoModalVisible = (value) => {
    showPhotoModal.value = value
    if (!value) {
        photoItem.value = null
    }
}

const refreshItems = () => {
    router.reload({ only: ['models'], preserveScroll: true })
}


const openModelItemsModal = async (item) => {
    selectedModel.value = item
    
    try {
        const response = await axios.get(route('ecommerce.items.by-model', {
            market: props.market.id,
            item: item.id
        }))
        
        if (response.data && response.data.items) {
            modelItems.value = response.data.items
        } else {
            modelItems.value = [item]
        }
        
        showModelItemsModal.value = true
    } catch (error) {
        modelItems.value = [item]
        showModelItemsModal.value = true
    }
}

const handleViewItem = (item) => {
    // View item
}

const handleEditItem = (item) => {
    // Edit item
}
</script>

<style scoped>
</style>
