<template>
        <!-- Meta Head -->
        <Head 
            :title="`Products - ${market.name}`"
            :description="`Browse all products available at ${market.name}. Quality refurbished devices and electronics with detailed specifications.`"
        />

        <!-- Products Header -->
        <section class="py-12 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Title Section -->
                <div class="mb-8">
                    <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                        {{ getPageTitle() }}
                    </h1>
                    
                    <!-- Search Query Display -->
                    <div v-if="props.currentSearch" class="flex items-center gap-2 mt-2">
                        <span class="text-gray-600">
                            Search results for: <span class="font-medium text-gray-900">"{{ props.currentSearch }}"</span>
                        </span>
                        <button
                            @click="clearSearch"
                            class="text-sm text-gray-500 hover:text-gray-700 underline"
                        >
                            Clear search
                        </button>
                    </div>
                </div>
                
                <!-- Filters and Sort -->
                <div class="flex flex-col sm:flex-row gap-4 mb-12 items-center justify-between">
                    <div class="flex flex-col sm:flex-row gap-4 w-full sm:w-full">
                        <!-- Category Filter -->
                        <div class="relative">
                            <Dropdown
                                v-model="selectedCategory"
                                :options="categoryOptions"
                                option-label="label"
                                option-value="value"
                                placeholder="All Categories"
                                @change="updateFilters"
                                class="w-full sm:w-48"
                                :pt="{
                                    root: 'bg-slate-100 border border-gray-200 rounded-lg text-sm hover:border-gray-300 focus:border-gray-300 transition-all duration-200',
                                    input: 'px-4 py-2 text-gray-900 focus:outline-none',
                                    trigger: 'px-2 text-gray-500 hover:text-gray-700',
                                    panel: 'bg-white border border-gray-200 rounded-lg shadow-lg mt-1 z-50',
                                    item: 'px-4 py-2 hover:bg-slate-100 text-gray-900 cursor-pointer transition-colors duration-150',
                                    itemGroup: 'bg-gray-50 px-4 py-2 text-gray-600 text-xs font-medium'
                                }"
                            />
                        </div>

                        <!-- Brand Filter -->
                        <div class="relative">
                            <Dropdown
                                v-model="selectedBrand"
                                :options="brandOptions"
                                option-label="label"
                                option-value="value"
                                placeholder="All Brands"
                                @change="updateFilters"
                                class="w-full sm:w-48"
                                :pt="{
                                    root: 'bg-slate-100 border border-gray-200 rounded-lg text-sm hover:border-gray-300 focus:border-gray-300 transition-all duration-200',
                                    input: 'px-4 py-2 text-gray-900 focus:outline-none',
                                    trigger: 'px-2 text-gray-500 hover:text-gray-700',
                                    panel: 'bg-white border border-gray-200 rounded-lg shadow-lg mt-1 z-50',
                                    item: 'px-4 py-2 hover:bg-slate-100 text-gray-900 cursor-pointer transition-colors duration-150',
                                    itemGroup: 'bg-gray-50 px-4 py-2 text-gray-600 text-xs font-medium'
                                }"
                            />
                        </div>

                        <!-- Sort Options -->
                        <div class="relative">
                            <Dropdown
                                v-model="selectedSort"
                                :options="sortOptions"
                                option-label="label"
                                option-value="value"
                                placeholder="Sort by"
                                @change="updateFilters"
                                class="w-full sm:w-48"
                                :pt="{
                                    root: 'bg-slate-100 border border-gray-200 rounded-lg text-sm hover:border-gray-300 focus:border-gray-300 transition-all duration-200',
                                    input: 'px-4 py-2 text-gray-900 focus:outline-none',
                                    trigger: 'px-2 text-gray-500 hover:text-gray-700',
                                    panel: 'bg-white border border-gray-200 rounded-lg shadow-lg mt-1 z-50',
                                    item: 'px-4 py-2 hover:bg-slate-100 text-gray-900 cursor-pointer transition-colors duration-150',
                                    itemGroup: 'bg-gray-50 px-4 py-2 text-gray-600 text-xs font-medium'
                                }"
                            />
                        </div>

                        <!-- View Mode Toggle -->
                        <div class="flex items-center bg-slate-100 border border-gray-200 rounded-lg p-1 ml-auto">
                            <button
                                @click="toggleViewMode('individual')"
                                :class="[
                                    'flex items-center justify-center px-4 py-2 rounded font-medium transition-all duration-200 text-sm ml-auto',
                                    viewMode === 'individual'
                                        ? 'bg-white text-gray-900 shadow-sm'
                                        : 'text-gray-600 hover:text-gray-900'
                                ]"
                                title="Show individual items"
                            >
                                <i class="pi pi-list mr-2"></i>
                                Items
                            </button>
                            <button
                                @click="toggleViewMode('grouped')"
                                :class="[
                                    'flex items-center justify-center px-4 py-2 rounded font-medium transition-all duration-200 text-sm',
                                    viewMode === 'grouped'
                                        ? 'bg-white text-gray-900 shadow-sm'
                                        : 'text-gray-600 hover:text-gray-900'
                                ]"
                                title="Show models grouped"
                            >
                                <i class="pi pi-th mr-2"></i>
                                Models
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <div class="bg-slate-100 rounded-lg border border-gray-200 p-6 text-center">
                        <div class="text-3xl font-bold text-gray-800">{{ stats.total_products }}</div>
                        <div class="text-sm text-gray-600 mt-2">Total Products</div>
                    </div>
                    <div class="bg-slate-100 rounded-lg border border-gray-200 p-6 text-center">
                        <div class="text-3xl font-bold text-gray-800">{{ stats.categories_count }}</div>
                        <div class="text-sm text-gray-600 mt-2">Categories</div>
                    </div>
                    <div class="bg-slate-100 rounded-lg border border-gray-200 p-6 text-center">
                        <div class="text-3xl font-bold text-gray-800">{{ getCurrencySymbol(market.currency) }}{{ formatPrice(stats.price_range.min) }}</div>
                        <div class="text-sm text-gray-600 mt-2">Starting Price</div>
                    </div>
                    <div class="bg-slate-100 rounded-lg border border-gray-200 p-6 text-center">
                        <div class="text-3xl font-bold text-gray-800">{{ getCurrencySymbol(market.currency) }}{{ formatPrice(stats.price_range.max) }}</div>
                        <div class="text-sm text-gray-600 mt-2">Top Price</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Products Grid -->
        <section class="py-8 bg-slate-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Products Grid -->
                <div v-if="items && items.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-12">
                    <ProductCard
                        v-for="item in items" 
                        :key="item.id"
                        :item="item"
                        :market="market"
                        @view-product="viewProduct"
                    />
                </div>

                <!-- Loading State -->
                <div v-if="loading" class="flex justify-center mt-12">
                    <div class="inline-flex items-center space-x-2 px-6 py-3 bg-white rounded-lg border border-gray-200 shadow-sm">
                        <div class="animate-spin w-4 h-4 border-2 border-gray-300 border-t-gray-600 rounded-full"></div>
                        <span class="text-gray-600 font-medium">Loading more products...</span>
                    </div>
                </div>

                <!-- End Message -->
                <div v-if="!hasMoreItems && items.length > 0" class="text-center mt-12">
                    <div class="inline-flex items-center space-x-2 px-6 py-3 bg-white rounded-lg border border-gray-200 shadow-sm">
                        <i class="pi pi-check-circle text-gray-500"></i>
                        <span class="text-gray-600 font-medium">You've seen all {{ items.length }} products</span>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else-if="!loading && items.length === 0" class="text-center py-16">
                    <div class="w-16 h-16 mx-auto mb-6 rounded-lg flex items-center justify-center bg-slate-100">
                        <i class="pi pi-shopping-bag text-2xl text-gray-500"></i>
                    </div>
                    <h3 class="text-xl font-medium text-gray-900 mb-2">No products found</h3>
                    <p class="text-gray-600 mb-6">
                        {{ props.currentSearch 
                            ? `No products match your search for "${props.currentSearch}". Try a different search term.` 
                            : currentCategory 
                                ? 'Try browsing a different category.' 
                                : 'Check back later for new arrivals.' 
                        }}
                    </p>
                    <div class="flex justify-center gap-4">
                        <button 
                            v-if="props.currentSearch"
                            @click="clearSearch"
                            class="inline-flex items-center justify-center px-6 py-3 rounded-lg bg-gray-900 text-white hover:bg-gray-800 font-medium transition-all duration-200"
                        >
                            Clear Search
                        </button>
                        <button 
                            v-if="currentCategory || selectedBrand.value"
                            @click="clearFilters"
                            class="inline-flex items-center justify-center px-6 py-3 rounded-lg bg-slate-100 text-gray-700 hover:text-gray-900 border border-gray-200 hover:border-gray-300 font-medium transition-all duration-200"
                        >
                            View All Products
                        </button>
                    </div>
                </div>
            </div>
        </section>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import axios from 'axios'
import Dropdown from 'primevue/dropdown'
import MarketLayout from '@/Layouts/Ecommerce/MarketLayout.vue'
import ProductCard from '@/Components/Ecommerce/ProductCard.vue'
import { getCurrencySymbol } from '@/utils/currency'

defineOptions({ layout: MarketLayout })
// Props
const props = defineProps({
    market: Object,
    initialItems: Array, // Changed from items object to initialItems array
    categories: Array,
    stats: Object,
    currentCategory: String,
    currentBrand: String,
    currentSort: String,
    currentSearch: String,
    filters: Object
})

// Reactive state
const selectedCategory = ref(props.currentCategory || '')
const selectedBrand = ref(props.currentBrand || '')
const selectedSort = ref(props.currentSort || 'latest')
const items = ref([...props.initialItems]) // Local items array for infinite scroll
const loading = ref(false)
const hasMoreItems = ref(true)
const currentPage = ref(1)
const viewMode = ref('grouped') // 'grouped' for models, 'individual' for items

// Computed properties for dropdown options
const categoryOptions = computed(() => [
    { label: 'All Categories', value: '' },
    ...props.categories.map(category => ({
        label: formatCategoryName(category),
        value: category
    }))
])

const brandOptions = computed(() => [
    { label: 'All Brands', value: '' },
    { label: 'Apple', value: 'Apple' },
    { label: 'Samsung', value: 'Samsung' },
    { label: 'Google', value: 'Google' },
    { label: 'Xiaomi', value: 'Xiaomi' },
    { label: 'OnePlus', value: 'OnePlus' },
    { label: 'Huawei', value: 'Huawei' },
    { label: 'Sony', value: 'Sony' },
    { label: 'LG', value: 'LG' },
    { label: 'Motorola', value: 'Motorola' },
    { label: 'Nokia', value: 'Nokia' }
])

const sortOptions = computed(() => [
    { label: 'Latest First', value: 'latest' },
    { label: 'Price: Low to High', value: 'price_low' },
    { label: 'Price: High to Low', value: 'price_high' },
    { label: 'Name: A to Z', value: 'name' }
])

// Methods
const formatPrice = (price) => {
    return new Intl.NumberFormat().format(price)
}

const formatCategoryName = (category) => {
    return category.charAt(0).toUpperCase() + category.slice(1)
}

const getPageTitle = () => {
    if (selectedBrand.value && selectedCategory.value) {
        return `${selectedBrand.value} ${formatCategoryName(selectedCategory.value)} Products`
    } else if (selectedBrand.value) {
        return `${selectedBrand.value} Products`
    } else if (selectedCategory.value) {
        return `${formatCategoryName(selectedCategory.value)} Products`
    } else {
        return 'All Products'
    }
}

const maskIMEI = (imei) => {
    if (!imei || imei.length < 8) return imei
    const start = imei.substring(0, 4)
    const end = imei.substring(imei.length - 4)
    return `${start}****${end}`
}

const viewProduct = (productId) => {
    window.open(`/market/${props.market.slug}/product/${productId}`, '_blank')
}

const toggleViewMode = (mode) => {
    if (viewMode.value === mode) return
    
    viewMode.value = mode
    loading.value = true
    currentPage.value = 1
    hasMoreItems.value = true
    
    const params = new URLSearchParams()
    
    if (selectedCategory.value) {
        params.append('category', selectedCategory.value)
    }
    
    if (selectedBrand.value) {
        params.append('brand', selectedBrand.value)
    }
    
    if (selectedSort.value) {
        params.append('sort', selectedSort.value)
    }

    if (props.currentSearch) {
        params.append('search', props.currentSearch)
    }

    // Add group_by_model parameter based on view mode
    if (mode === 'grouped') {
        params.append('group_by_model', 'true')
    }

    // Make API call to get filtered results
    const url = `/market/${props.market.slug}/products`
    
    axios.get(url, { params: Object.fromEntries(params) })
        .then(response => {
            items.value = response.data.data
            hasMoreItems.value = response.data.has_more_pages
            loading.value = false
            
            // Update URL without page reload
            const queryString = params.toString()
            const newUrl = queryString ? `?${queryString}` : ''
            window.history.pushState({}, '', `/market/${props.market.slug}/products-list${newUrl}`)
        })
        .catch(error => {
            console.error('Error toggling view mode:', error)
            loading.value = false
        })
}

const updateFilters = () => {
    // Reset items and pagination for new filters
    loading.value = true
    currentPage.value = 1
    hasMoreItems.value = true
    
    const params = new URLSearchParams()
    
    if (selectedCategory.value) {
        params.append('category', selectedCategory.value)
    }
    
    if (selectedBrand.value) {
        params.append('brand', selectedBrand.value)
    }
    
    if (selectedSort.value) {
        params.append('sort', selectedSort.value)
    }

    if (props.currentSearch) {
        params.append('search', props.currentSearch)
    }

    // Add group_by_model parameter based on view mode
    if (viewMode.value === 'grouped') {
        params.append('group_by_model', 'true')
    }

    // Make API call to get filtered results
    const url = `/market/${props.market.slug}/products`
    
    axios.get(url, { params: Object.fromEntries(params) })
        .then(response => {
            items.value = response.data.data
            hasMoreItems.value = response.data.has_more_pages
            loading.value = false
            
            // Update URL without page reload
            const queryString = params.toString()
            const newUrl = queryString ? `?${queryString}` : ''
            window.history.pushState({}, '', `/market/${props.market.slug}/products-list${newUrl}`)
        })
        .catch(error => {
            console.error('Error updating filters:', error)
            loading.value = false
        })
}

const loadMoreItems = () => {
    if (loading.value || !hasMoreItems.value) return
    
    loading.value = true
    currentPage.value++
    
    const params = {
        page: currentPage.value,
        ...(selectedCategory.value && { category: selectedCategory.value }),
        ...(selectedBrand.value && { brand: selectedBrand.value }),
        ...(selectedSort.value && { sort: selectedSort.value }),
        ...(props.currentSearch && { search: props.currentSearch }),
        ...(viewMode.value === 'grouped' && { group_by_model: 'true' })
    }
    
    const url = `/market/${props.market.slug}/products`
    
    axios.get(url, { params })
        .then(response => {
            items.value = [...items.value, ...response.data.data]
            hasMoreItems.value = response.data.has_more_pages
            loading.value = false
        })
        .catch(error => {
            console.error('Error loading more items:', error)
            loading.value = false
            currentPage.value-- // Revert page increment on error
        })
}

const handleScroll = () => {
    const scrollPosition = window.innerHeight + window.scrollY
    const documentHeight = document.documentElement.offsetHeight
    const threshold = 1000 // Load more when 1000px from bottom
    
    if (scrollPosition >= documentHeight - threshold) {
        loadMoreItems()
    }
}

const clearFilters = () => {
    selectedCategory.value = ''
    selectedBrand.value = ''
    selectedSort.value = 'latest'
    
    // Reset items and reload
    loading.value = true
    currentPage.value = 1
    hasMoreItems.value = true
    
    const url = `/market/${props.market.slug}/products`
    
    axios.get(url, { params: { page: 1 } })
        .then(response => {
            items.value = response.data.data
            hasMoreItems.value = response.data.has_more_pages
            loading.value = false
            
            // Update URL
            window.history.pushState({}, '', `/market/${props.market.slug}/products-list`)
        })
        .catch(error => {
            console.error('Error clearing filters:', error)
            loading.value = false
        })
}

const clearSearch = () => {
    // Navigate to products list without search parameter
    const params = new URLSearchParams()
    
    if (selectedCategory.value) {
        params.append('category', selectedCategory.value)
    }
    
    if (selectedBrand.value) {
        params.append('brand', selectedBrand.value)
    }
    
    if (selectedSort.value && selectedSort.value !== 'latest') {
        params.append('sort', selectedSort.value)
    }
    
    const queryString = params.toString()
    const newUrl = queryString 
        ? `/market/${props.market.slug}/products-list?${queryString}` 
        : `/market/${props.market.slug}/products-list`
    
    window.location.href = newUrl
}

// Remove pagination methods - not needed for infinite scroll
// const getPaginationUrl = (page) => { ... }
// const getVisiblePages = () => { ... }

// Lifecycle
onMounted(() => {
    // Set initial hasMoreItems state
    hasMoreItems.value = props.initialItems.length >= 24 // Assuming 24 items per page
    
    // Update URL to include group_by_model parameter if not already present
    const url = new URL(window.location)
    if (!url.searchParams.has('group_by_model')) {
        url.searchParams.set('group_by_model', 'true')
        window.history.replaceState({}, '', url.toString())
    }
    
    // Add scroll listener
    window.addEventListener('scroll', handleScroll)
})

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll)
})
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>