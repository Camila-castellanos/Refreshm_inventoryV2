<template>
        <!-- Meta Head -->
        <Head 
            :title="market.meta_title || `${market.name} - Online Market`"
            :description="market.meta_description || `Browse and shop ${market.name} collection of quality refurbished devices and electronics.`"
        />

        <!-- Hero Carousel Section -->
        <section class="relative overflow-hidden">
            <Carousel v-model:page="currentSlide" :value="heroSlides" :numVisible="1" :numScroll="1" 
                     :autoplayInterval="5000" :circular="true" class="hero-carousel">
                <template #item="{ data: slide }">
                    <div class="relative h-96 md:h-[500px] flex items-center justify-center" 
                         :style="{ background: slide.background }">
                        
                        <!-- Background Image Overlay -->
                        <div v-if="slide.image" class="absolute inset-0">
                            <img :src="slide.image" :alt="slide.title" 
                                 class="w-full h-full object-cover opacity-20">
                        </div>
                        
                        <!-- Content -->
                        <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                            <h1 class="text-4xl md:text-6xl font-bold mb-6 text-gray-800">
                                {{ slide.title }}
                            </h1>
                            
                            <p class="text-xl md:text-2xl mb-8 text-gray-700 max-w-2xl mx-auto">
                                {{ slide.subtitle }}
                            </p>
                            
                            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                                <button @click="slide.primaryAction.action" 
                                        class="inline-flex items-center justify-center px-8 py-4 rounded-lg font-semibold text-lg bg-gray-800 text-white hover:bg-gray-900 transition-all duration-200 shadow-lg hover:shadow-xl">
                                    {{ slide.primaryAction.text }}
                                    <i class="pi pi-arrow-right ml-2"></i>
                                </button>
                                
                                <button v-if="slide.secondaryAction" @click="slide.secondaryAction.action"
                                        class="inline-flex items-center justify-center px-8 py-4 rounded-lg font-semibold text-lg bg-slate-100 text-gray-700 hover:text-gray-900 border border-gray-200 hover:border-gray-300 transition-all duration-200 shadow-sm hover:shadow-md">
                                    {{ slide.secondaryAction.text }}
                                </button>
                            </div>
                            
                            <!-- Stats Badge -->
                            <div v-if="slide.showStats" class="mt-8 inline-flex items-center space-x-2 bg-white/90 backdrop-blur-sm px-6 py-3 rounded-full border border-gray-200 shadow-sm">
                                <i class="pi pi-check-circle text-green-500"></i>
                                <span class="font-medium text-gray-700">{{ stats.total_products }} Products • {{ stats.categories_count }} Categories</span>
                            </div>
                        </div>
                    </div>
                </template>
            </Carousel>
        </section>

        <!-- Quick Access Section -->
        <section class="py-12 bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <button @click="browseByBrand('Apple')" 
                            class="group p-6 rounded-lg bg-slate-100 border border-gray-200 hover:border-gray-300 transition-all duration-200 text-center hover:shadow-md">
                        <div class="w-12 h-12 bg-gradient-to-br from-gray-600 to-gray-800 text-white rounded-lg mx-auto mb-3 flex items-center justify-center group-hover:from-gray-700 group-hover:to-gray-900 transition-all duration-200">
                            <i class="pi pi-apple text-xl"></i>
                        </div>
                        <div class="text-lg font-bold text-gray-800">iPhone</div>
                        <div class="text-sm font-medium text-gray-600">Apple Devices</div>
                    </button>
                    
                    <button @click="browseByBrand('Samsung')" 
                            class="group p-6 rounded-lg bg-slate-100 border border-gray-200 hover:border-gray-300 transition-all duration-200 text-center hover:shadow-md">
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-700 text-white rounded-lg mx-auto mb-3 flex items-center justify-center group-hover:from-blue-600 group-hover:to-blue-800 transition-all duration-200">
                            <i class="pi pi-mobile text-xl"></i>
                        </div>
                        <div class="text-lg font-bold text-gray-800">Samsung</div>
                        <div class="text-sm font-medium text-gray-600">Galaxy Series</div>
                    </button>
                    
                    <button @click="browseByBrand('Google')" 
                            class="group p-6 rounded-lg bg-slate-100 border border-gray-200 hover:border-gray-300 transition-all duration-200 text-center hover:shadow-md">
                        <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-700 text-white rounded-lg mx-auto mb-3 flex items-center justify-center group-hover:from-green-600 group-hover:to-green-800 transition-all duration-200">
                            <i class="pi pi-android text-xl"></i>
                        </div>
                        <div class="text-lg font-bold text-gray-800">Google</div>
                        <div class="text-sm font-medium text-gray-600">Pixel Phones</div>
                    </button>
                    
                    <Link :href="`/market/${market.slug}/contact`"
                          class="group p-6 rounded-lg bg-slate-100 border border-gray-200 hover:border-gray-300 transition-all duration-200 text-center hover:shadow-md">
                        <div class="w-12 h-12 bg-green-500 text-white rounded-lg mx-auto mb-3 flex items-center justify-center group-hover:bg-green-600 transition-colors duration-200">
                            <i class="pi pi-phone text-xl"></i>
                        </div>
                        <div class="text-lg font-bold text-gray-800">Contact</div>
                        <div class="text-sm font-medium text-gray-600">Get in Touch</div>
                    </Link>
                </div>
            </div>
        </section>

        <!-- Featured Brands Section -->
        <section class="py-16 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Shop by Brand</h2>
                    <p class="text-lg text-gray-600">Discover premium devices from top smartphone brands</p>
                </div>
                
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
                    <button v-for="brand in topBrands" :key="brand.name"
                           @click="browseByBrand(brand.name)"
                           class="group block p-6 bg-white rounded-lg border border-gray-200 hover:border-gray-300 transition-all duration-200 text-center hover:shadow-md">
                        <div class="w-16 h-16 bg-gradient-to-br rounded-lg mx-auto mb-4 flex items-center justify-center border border-gray-200 group-hover:border-gray-300 transition-all duration-200"
                             :style="{ background: brand.gradient }">
                            <i :class="brand.icon" class="text-2xl text-white drop-shadow-sm"></i>
                        </div>
                        <h3 class="font-semibold text-gray-700 group-hover:text-gray-900 transition-colors duration-200">
                            {{ brand.name }}
                        </h3>
                        <p class="text-xs text-gray-500 mt-1">{{ brand.description }}</p>
                    </button>
                </div>
                
                <div class="text-center mt-8">
                    <button @click="browseAllProducts" 
                            class="inline-flex items-center justify-center px-8 py-3 rounded-lg bg-gray-800 text-white hover:bg-gray-900 font-semibold transition-all duration-200 shadow-lg hover:shadow-xl">
                        View All Brands
                        <i class="pi pi-arrow-right ml-2"></i>
                    </button>
                </div>
            </div>
        </section>

        <!-- Promotional Banners Section -->
        <section class="py-16 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid md:grid-cols-2 gap-8">
                    <!-- Quality Guarantee Banner -->
                    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-blue-50 to-indigo-100 p-8 border border-gray-200">
                        <div class="relative z-10">
                            <div class="w-12 h-12 bg-blue-500 text-white rounded-lg mb-4 flex items-center justify-center">
                                <i class="pi pi-shield text-xl"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900 mb-3">Quality Guaranteed</h3>
                            <p class="text-gray-600 mb-6">All our refurbished devices undergo rigorous testing and come with warranty protection.</p>
                            <button @click="browseAllProducts" 
                                    class="inline-flex items-center px-6 py-3 rounded-lg bg-blue-500 text-white hover:bg-blue-600 font-semibold transition-all duration-200">
                                Learn More
                                <i class="pi pi-arrow-right ml-2"></i>
                            </button>
                        </div>
                        <div class="absolute -right-4 -bottom-4 w-32 h-32 bg-blue-200 rounded-full opacity-20"></div>
                    </div>

                    <!-- Best Deals Banner -->
                    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-green-50 to-emerald-100 p-8 border border-gray-200">
                        <div class="relative z-10">
                            <div class="w-12 h-12 bg-green-500 text-white rounded-lg mb-4 flex items-center justify-center">
                                <i class="pi pi-tag text-xl"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900 mb-3">Best Deals</h3>
                            <p class="text-gray-600 mb-6">Save up to 70% on premium refurbished devices. Limited time offers available now.</p>
                            <button @click="browseBySort('price_low')" 
                                    class="inline-flex items-center px-6 py-3 rounded-lg bg-green-500 text-white hover:bg-green-600 font-semibold transition-all duration-200">
                                Shop Deals
                                <i class="pi pi-arrow-right ml-2"></i>
                            </button>
                        </div>
                        <div class="absolute -right-4 -bottom-4 w-32 h-32 bg-green-200 rounded-full opacity-20"></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Featured Products Preview -->
        <section class="py-16 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Featured Products</h2>
                    <p class="text-lg text-gray-600">Hand-picked items from our latest inventory</p>
                </div>

                <!-- Featured Products Grid (Limited to 8 items) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <ProductCard
                        v-for="item in featuredItems" 
                        :key="item.id"
                        :item="item"
                        :market="market"
                        :compact="true"
                        @view-product="viewProduct"
                    />
                </div>

                <div class="text-center">
                    <button @click="browseAllProducts" 
                            class="inline-flex items-center justify-center px-8 py-4 rounded-lg bg-gray-800 text-white hover:bg-gray-900 font-semibold text-lg transition-all duration-200 shadow-lg hover:shadow-xl">
                        Browse All Products
                        <i class="pi pi-arrow-right ml-2"></i>
                    </button>
                </div>
            </div>
        </section>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { Head, router, Link } from '@inertiajs/vue3'
import Carousel from 'primevue/carousel'
import MarketLayout from '@/Layouts/Ecommerce/MarketLayout.vue'
import ProductCard from '@/Components/Ecommerce/ProductCard.vue'
import { getCurrencySymbol } from '@/utils/currency'

defineOptions({layout: MarketLayout})
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
const currentSlide = ref(0)
const featuredItems = computed(() => props.initialItems.slice(0, 8)) // Show only 8 featured items

// Top smartphone brands data
const topBrands = ref([
    {
        name: 'Apple',
        icon: 'pi pi-apple',
        gradient: 'linear-gradient(135deg, #6b7280 0%, #374151 100%)',
        description: 'iPhone Series'
    },
    {
        name: 'Samsung',
        icon: 'pi pi-mobile',
        gradient: 'linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%)',
        description: 'Galaxy Collection'
    },
    {
        name: 'Google',
        icon: 'pi pi-android',
        gradient: 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
        description: 'Pixel Phones'
    },
    {
        name: 'Xiaomi',
        icon: 'pi pi-mobile',
        gradient: 'linear-gradient(135deg, #f97316 0%, #ea580c 100%)',
        description: 'Mi & Redmi'
    },
    {
        name: 'OnePlus',
        icon: 'pi pi-mobile',
        gradient: 'linear-gradient(135deg, #dc2626 0%, #b91c1c 100%)',
        description: 'Premium Android'
    },
    {
        name: 'Huawei',
        icon: 'pi pi-mobile',
        gradient: 'linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%)',
        description: 'P & Mate Series'
    }
])

// Hero Slides Data
const heroSlides = ref([
    {
        title: `Welcome to ${props.market.name}`,
        subtitle: props.market.description || 'Browse our collection of quality refurbished devices and electronics',
        background: 'linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%)',
        image: props.market.banner_url,
        showStats: true,
        primaryAction: {
            text: 'Shop Now',
            action: () => browseAllProducts()
        },
        secondaryAction: {
            text: 'Learn More',
            action: () => router.visit(`/market/${props.market.slug}/contact`)
        }
    },
    {
        title: 'Premium Smartphone Brands',
        subtitle: 'Apple, Samsung, Google, and more premium refurbished devices at unbeatable prices',
        background: 'linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%)',
        showStats: false,
        primaryAction: {
            text: 'Browse iPhones',
            action: () => browseByBrand('Apple')
        },
        secondaryAction: {
            text: 'See All Brands',
            action: () => browseAllProducts()
        }
    },
    {
        title: 'Best Deals Available',
        subtitle: `Save up to 70% on premium devices. Starting from ${getCurrencySymbol(props.market.currency)} ${props.stats.price_range?.min || '0'}`,
        background: 'linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%)',
        showStats: false,
        primaryAction: {
            text: 'View Deals',
            action: () => browseBySort('price_low')
        },
        secondaryAction: {
            text: 'Browse Samsung',
            action: () => browseByBrand('Samsung')
        }
    }
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

const getCategoryIcon = (category) => {
    const icons = {
        'phone': 'pi pi-mobile',
        'tablet': 'pi pi-tablet',
        'laptop': 'pi pi-desktop',
        'computer': 'pi pi-desktop',
        'watch': 'pi pi-clock',
        'headphones': 'pi pi-volume-up',
        'camera': 'pi pi-camera',
        'gaming': 'pi pi-play',
        'accessory': 'pi pi-cog',
        'other': 'pi pi-box'
    }
    return icons[category?.toLowerCase()] || 'pi pi-box'
}

const viewProduct = (productId) => {
    window.open(`/market/${props.market.slug}/product/${productId}`, '_blank')
}

const browseAllProducts = () => {
    router.visit(`/market/${props.market.slug}/products-list`)
}

const browseByCategory = (category) => {
    router.visit(`/market/${props.market.slug}/products-list?category=${category}`)
}

const browseByBrand = (brand) => {
    router.visit(`/market/${props.market.slug}/products-list?brand=${brand}`)
}

const browseBySort = (sort) => {
    router.visit(`/market/${props.market.slug}/products-list?sort=${sort}`)
}

// Lifecycle
onMounted(() => {
    // Any initialization logic here
})
</script>

<style scoped>
.hero-carousel :deep(.p-carousel-content) {
    border-radius: 0;
}

.hero-carousel :deep(.p-carousel-indicators) {
    position: absolute;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
}

.hero-carousel :deep(.p-carousel-indicator) {
    background-color: rgba(255, 255, 255, 0.5);
    border: 2px solid rgba(255, 255, 255, 0.8);
    width: 12px;
    height: 12px;
    margin: 0 4px;
    transition: all 0.3s ease;
}

.hero-carousel :deep(.p-carousel-indicator.p-highlight) {
    background-color: white;
    border-color: white;
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>