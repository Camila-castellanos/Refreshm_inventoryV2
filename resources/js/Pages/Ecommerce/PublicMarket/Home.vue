<template>
    <MarketLayout :market="market">
        <!-- Meta Head -->
        <Head 
            :title="market.meta_title || `${market.name} - Online Market`"
            :description="market.meta_description || `Browse and shop ${market.name} collection of quality refurbished devices and electronics.`"
        />

        <!-- Hero Section -->
        <section class="gradient-bg text-gray-800 py-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <div v-if="market.banner_url" class="mb-8">
                    <img :src="market.banner_url" :alt="`${market.name} Banner`" class="mx-auto max-h-32 rounded-lg shadow-lg">
                </div>
                
                <h1 class="text-4xl md:text-6xl font-bold mb-6">
                    Welcome to {{ market.name }}
                </h1>
                
                <p v-if="market.description" class="text-xl mb-8 max-w-3xl mx-auto">
                    {{ market.description }}
                </p>
                
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <button @click="scrollToProducts" 
                            class="inline-flex items-center justify-center px-8 py-4 rounded-lg font-semibold text-lg bg-slate-100 text-gray-700 hover:text-gray-900 border border-gray-200 hover:border-gray-300 transition-all duration-200 shadow-sm hover:shadow-md">
                        Shop Now
                    </button>
                    
                    <div v-if="totalItems > 0" class="flex items-center justify-center space-x-2 bg-slate-100 px-6 py-4 rounded-lg border border-gray-200">
                        <div class="inline-flex items-center justify-center w-6 h-6 rounded-md bg-slate-100 text-gray-500">
                            <i class="pi pi-check text-sm"></i>
                        </div>
                        <span class="font-medium text-gray-700">{{ totalItems }} Products Available</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Statistics Section -->
        <section class="py-12 bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <div class="text-center p-4 rounded-lg bg-slate-100 border border-gray-200">
                        <div class="text-3xl font-bold text-gray-800">{{ stats.total_products }}</div>
                        <div class="text-sm font-medium text-gray-600">Products Available</div>
                    </div>
                    <div class="text-center p-4 rounded-lg bg-slate-100 border border-gray-200">
                        <div class="text-3xl font-bold text-gray-800">{{ stats.categories_count }}</div>
                        <div class="text-sm font-medium text-gray-600">Categories</div>
                    </div>
                    <div class="text-center p-4 rounded-lg bg-slate-100 border border-gray-200">
                        <div class="text-3xl font-bold text-gray-800">${{ formatPrice(stats.price_range.min) }}</div>
                        <div class="text-sm font-medium text-gray-600">Starting Price</div>
                    </div>
                    <div class="text-center p-4 rounded-lg bg-slate-100 border border-gray-200">
                        <div class="text-3xl font-bold text-gray-800">${{ formatPrice(stats.price_range.max) }}</div>
                        <div class="text-sm font-medium text-gray-600">Top Price</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Categories Section - Temporarily hidden -->
        <!-- 
        <section v-if="categories.length > 0" class="py-16 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Shop by Category</h2>
                    <p class="text-lg text-gray-600">Find exactly what you're looking for</p>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-6">
                    <button v-for="category in categories" :key="category"
                           @click="filterByCategory(category)"
                           class="group block p-6 bg-slate-100 rounded-lg border border-gray-200 hover:border-gray-300 transition-all duration-200 text-center hover:shadow-md">
                        <div class="w-12 h-12 bg-slate-100 rounded-lg mx-auto mb-3 flex items-center justify-center border border-gray-200 group-hover:border-gray-300 transition-all duration-200">
                            <svg class="w-6 h-6 text-gray-500 group-hover:text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-700 group-hover:text-gray-900 transition-colors duration-200">
                            {{ formatCategoryName(category) }}
                        </h3>
                    </button>
                </div>
            </div>
        </section>
        -->

        <!-- Products Section with Infinite Scroll -->
        <section ref="productsSection" id="products" class="py-16 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                        {{ currentCategory ? `${formatCategoryName(currentCategory)} Products` : 'Featured Products' }}
                    </h2>
                    <p class="text-lg text-gray-600">
                        {{ currentCategory ? `Browse our ${formatCategoryName(currentCategory)} collection` : 'Hand-picked items from our latest inventory' }}
                    </p>
                </div>

                <!-- Products Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                    <div v-for="item in items" :key="item.id"
                         class="bg-slate-50 rounded-lg border border-gray-200 hover:border-gray-300 overflow-hidden transition-all duration-200 hover:shadow-md">
                        
                        <!-- Product Image Placeholder -->
                        <div class="aspect-w-16 aspect-h-12 bg-gray-100 flex items-center justify-center h-48">
                            <div class="text-center p-8">
                                <svg class="w-16 h-16 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                                <p class="text-sm text-gray-500">{{ item.manufacturer || 'Device' }}</p>
                            </div>
                        </div>

                        <!-- Product Content -->
                        <div class="p-6">
                            <div class="mb-2">
                                <span v-if="item.type" class="inline-block px-3 py-1 text-xs font-medium text-gray-700 bg-slate-100 border border-gray-200 rounded-md">
                                    {{ formatCategoryName(item.type) }}
                                </span>
                            </div>
                            
                            <h3 class="font-semibold text-lg text-gray-900 mb-2 line-clamp-2">
                                {{ item.model }}
                            </h3>
                            
                            <p v-if="item.issues" class="text-sm text-gray-600 mb-3 line-clamp-2">
                                {{ item.issues.substring(0, 80) }}...
                            </p>

                            <div class="flex items-center justify-between mb-3">
                                <div class="text-2xl font-bold text-gray-800">
                                    {{ market.currency }} {{ formatPrice(item.selling_price) }}
                                </div>
                                
                                <span v-if="market.show_inventory_count" class="text-sm text-green-600 font-medium">
                                    In Stock
                                </span>
                            </div>

                            <button @click="viewProduct(item.id)" 
                                   class="inline-flex items-center justify-center w-full px-4 py-2 rounded-lg bg-slate-100 text-gray-700 hover:text-gray-900 border border-gray-200 hover:border-gray-300 font-medium text-sm text-center transition-all duration-200">
                                View Details
                            </button>

                            <div v-if="item.imei" class="mt-3 pt-3 border-t border-gray-100">
                                <p class="text-xs text-gray-500">
                                    IMEI: {{ maskIMEI(item.imei) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loading State -->
                <div v-if="loading" class="flex justify-center mt-12">
                    <div class="inline-flex items-center space-x-2 px-6 py-3 bg-slate-100 rounded-lg border border-gray-200">
                        <div class="animate-spin w-4 h-4 border-2 border-gray-300 border-t-gray-600 rounded-full"></div>
                        <span class="text-gray-600 font-medium">Loading more products...</span>
                    </div>
                </div>

                <!-- End Message -->
                <div v-if="!hasMoreItems && items.length > 0" class="text-center mt-12">
                    <div class="inline-flex items-center space-x-2 px-6 py-3 bg-slate-100 rounded-lg border border-gray-200">
                        <i class="pi pi-check-circle text-gray-500"></i>
                        <span class="text-gray-600 font-medium">You've seen all products</span>
                    </div>
                </div>

                <!-- No Products Message -->
                <div v-if="!loading && items.length === 0" class="text-center mt-12">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-lg flex items-center justify-center bg-slate-100">
                        <i class="pi pi-shopping-bag text-2xl text-gray-500"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No products found</h3>
                    <p class="text-gray-600 mb-6">Try browsing a different category.</p>
                </div>
            </div>
        </section>
    </MarketLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import Dropdown from 'primevue/dropdown'
import MarketLayout from '@/Layouts/Ecommerce/MarketLayout.vue'

// Props
const props = defineProps({
    market: Object,
    initialItems: Array,
    categories: Array,
    stats: Object,
    totalItems: Number,
    currentCategory: {
        type: String,
        default: null
    }
})

// Reactive Data
const items = ref([...props.initialItems])
const loading = ref(false)
const hasMoreItems = ref(true)
const currentPage = ref(1)
const selectedCategory = ref(props.currentCategory)
const productsSection = ref(null)

// Computed
const categoryOptions = computed(() => [
    { label: 'All Categories', value: null },
    ...props.categories.map(cat => ({
        label: formatCategoryName(cat),
        value: cat
    }))
])

// Methods
const formatPrice = (price) => {
    return new Intl.NumberFormat().format(price)
}

const formatCategoryName = (category) => {
    return category.charAt(0).toUpperCase() + category.slice(1)
}

const maskIMEI = (imei) => {
    if (!imei || imei.length < 8) return imei
    const start = imei.substring(0, 4)
    const end = imei.substring(imei.length - 4)
    return `${start}****${end}`
}

const scrollToProducts = () => {
    productsSection.value?.scrollIntoView({ behavior: 'smooth' })
}

const viewProduct = (productId) => {
    window.open(`/market/${props.market.slug}/product/${productId}`, '_blank')
}

const filterByCategory = (category) => {
    selectedCategory.value = category
    loading.value = true
    
    // Reset state
    currentPage.value = 1
    hasMoreItems.value = true
    
    // Make API call to filter by category
    const url = `/market/${props.market.slug}/products`
    const params = category ? { category, page: 1 } : { page: 1 }
    
    axios.get(url, { params })
        .then(response => {
            items.value = response.data.data
            hasMoreItems.value = response.data.has_more_pages
            loading.value = false
            
            // Update URL without page reload
            const queryString = category ? `?category=${category}` : ''
            window.history.pushState({}, '', `/market/${props.market.slug}${queryString}`)
        })
        .catch(error => {
            console.error('Error filtering products:', error)
            loading.value = false
        })
}

const loadMoreItems = () => {
    if (loading.value || !hasMoreItems.value) return
    
    loading.value = true
    currentPage.value++
    
    const url = `/market/${props.market.slug}/products`
    const params = {
        page: currentPage.value,
        ...(selectedCategory.value && { category: selectedCategory.value })
    }
    
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

// Lifecycle
onMounted(() => {
    window.addEventListener('scroll', handleScroll)
    
    // Set initial hasMoreItems state
    hasMoreItems.value = props.initialItems.length >= 12 // Assuming 12 items per page
})

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll)
})
</script>

<style scoped>
.gradient-bg {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>