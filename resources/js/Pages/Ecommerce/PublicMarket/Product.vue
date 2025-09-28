<template>
    <div>
        <!-- Meta Head -->
        <Head 
            :title="`${item.model} - ${market.name}`"
            :description="`${item.model} ${item.manufacturer || ''} available at ${market.name}. ${item.issues ? item.issues.substring(0, 150) : 'Quality refurbished device.'}`"
        />

        <!-- Navigation Header -->
        <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-50">
            <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center space-x-4">
                        <button @click="goBack" class="inline-flex items-center justify-center w-8 h-8 rounded-md text-gray-500 hover:text-gray-700 bg-slate-100 border border-gray-200 hover:border-gray-300 transition-all duration-200">
                            <i class="pi pi-arrow-left text-sm"></i>
                        </button>
                        <div>
                            <h1 class="text-lg font-bold text-gray-900">{{ market.name }}</h1>
                            <span v-if="market.tagline" class="text-xs text-gray-600">{{ market.tagline }}</span>
                        </div>
                    </div>
                </div>
            </nav>
        </header>

        <!-- Product Details Section -->
        <main class="py-8 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                    
                    <!-- Product Image -->
                    <div class="space-y-4">
                        <div class="aspect-w-4 aspect-h-3 bg-gray-100 rounded-lg flex items-center justify-center h-96">
                            <div class="text-center p-8">
                                <svg class="w-24 h-24 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                                <p class="text-lg text-gray-500 font-medium">{{ item.manufacturer || 'Device' }}</p>
                                <p class="text-sm text-gray-400">{{ item.model }}</p>
                            </div>
                        </div>
                        
                        <!-- Additional Info Cards -->
                        <div class="grid grid-cols-2 gap-4">
                            <div v-if="item.imei" class="p-4 bg-slate-100 rounded-lg border border-gray-200">
                                <h4 class="text-sm font-medium text-gray-900 mb-1">IMEI</h4>
                                <p class="text-sm text-gray-600 font-mono">{{ maskIMEI(item.imei) }}</p>
                            </div>
                            <div v-if="item.type" class="p-4 bg-slate-100 rounded-lg border border-gray-200">
                                <h4 class="text-sm font-medium text-gray-900 mb-1">Category</h4>
                                <p class="text-sm text-gray-600">{{ formatCategoryName(item.type) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Product Information -->
                    <div class="space-y-6">
                        
                        <!-- Product Title & Price -->
                        <div>
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ item.model }}</h1>
                                    <p v-if="item.manufacturer" class="text-lg text-gray-600">{{ item.manufacturer }}</p>
                                </div>
                                <div v-if="item.type" class="inline-block px-3 py-1 text-xs font-medium text-gray-700 bg-slate-100 border border-gray-200 rounded-md">
                                    {{ formatCategoryName(item.type) }}
                                </div>
                            </div>
                            
                            <div class="flex items-center space-x-4 mb-6">
                                <div class="text-4xl font-bold text-gray-800">
                                    {{ market.currency }} {{ formatPrice(item.selling_price) }}
                                </div>
                                <div v-if="market.show_inventory_count" class="inline-flex items-center space-x-1 px-3 py-1 bg-green-100 text-green-800 rounded-md border border-green-200">
                                    <i class="pi pi-check text-sm"></i>
                                    <span class="text-sm font-medium">In Stock</span>
                                </div>
                            </div>
                        </div>

                        <!-- Product Condition/Issues -->
                        <div v-if="item.issues" class="p-6 bg-slate-100 rounded-lg border border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900 mb-3">Product Condition</h3>
                            <p class="text-gray-700 leading-relaxed">{{ item.issues }}</p>
                        </div>

                        <!-- Product Specifications -->
                        <div class="p-6 bg-slate-100 rounded-lg border border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Specifications</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div v-if="item.manufacturer">
                                    <dt class="text-sm font-medium text-gray-600">Brand</dt>
                                    <dd class="text-sm text-gray-900 mt-1">{{ item.manufacturer }}</dd>
                                </div>
                                <div v-if="item.model">
                                    <dt class="text-sm font-medium text-gray-600">Model</dt>
                                    <dd class="text-sm text-gray-900 mt-1">{{ item.model }}</dd>
                                </div>
                                <div v-if="item.type">
                                    <dt class="text-sm font-medium text-gray-600">Type</dt>
                                    <dd class="text-sm text-gray-900 mt-1">{{ formatCategoryName(item.type) }}</dd>
                                </div>
                                <div v-if="item.color">
                                    <dt class="text-sm font-medium text-gray-600">Color</dt>
                                    <dd class="text-sm text-gray-900 mt-1">{{ item.color }}</dd>
                                </div>
                                <div v-if="item.capacity">
                                    <dt class="text-sm font-medium text-gray-600">Capacity</dt>
                                    <dd class="text-sm text-gray-900 mt-1">{{ item.capacity }}</dd>
                                </div>
                                <div v-if="item.carrier">
                                    <dt class="text-sm font-medium text-gray-600">Carrier</dt>
                                    <dd class="text-sm text-gray-900 mt-1">{{ item.carrier }}</dd>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="space-y-4">
                            <button @click="contactSeller" 
                                   class="w-full inline-flex items-center justify-center px-8 py-4 rounded-lg font-semibold text-lg bg-slate-100 text-gray-700 hover:text-gray-900 border border-gray-200 hover:border-gray-300 transition-all duration-200 shadow-sm hover:shadow-md">
                                <i class="pi pi-envelope text-sm mr-2"></i>
                                Contact Seller
                            </button>
                            
                            <div class="flex space-x-3">
                                <button @click="goBack" 
                                       class="flex-1 inline-flex items-center justify-center px-6 py-3 rounded-lg bg-slate-100 text-gray-700 hover:text-gray-900 border border-gray-200 hover:border-gray-300 font-medium transition-all duration-200">
                                    <i class="pi pi-arrow-left text-sm mr-2"></i>
                                    Back to Products
                                </button>
                                
                                <button @click="shareProduct" 
                                       class="flex-1 inline-flex items-center justify-center px-6 py-3 rounded-lg bg-slate-100 text-gray-700 hover:text-gray-900 border border-gray-200 hover:border-gray-300 font-medium transition-all duration-200">
                                    <i class="pi pi-share-alt text-sm mr-2"></i>
                                    Share
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Related Products Section -->
        <section v-if="relatedItems.length > 0" class="py-16 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-gray-900 mb-4">Related Products</h2>
                    <p class="text-lg text-gray-600">You might also be interested in these items</p>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div v-for="relatedItem in relatedItems" :key="relatedItem.id"
                         @click="viewProduct(relatedItem.id)"
                         class="bg-slate-100 rounded-lg border border-gray-200 hover:border-gray-300 overflow-hidden transition-all duration-200 hover:shadow-md cursor-pointer">
                        
                        <!-- Product Image Placeholder -->
                        <div class="aspect-w-16 aspect-h-12 bg-gray-100 flex items-center justify-center h-40">
                            <div class="text-center p-6">
                                <svg class="w-12 h-12 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                                <p class="text-xs text-gray-500">{{ relatedItem.manufacturer || 'Device' }}</p>
                            </div>
                        </div>

                        <!-- Product Content -->
                        <div class="p-4">
                            <h3 class="font-semibold text-sm text-gray-900 mb-2 line-clamp-2">
                                {{ relatedItem.model }}
                            </h3>
                            
                            <div class="flex items-center justify-between">
                                <div class="text-lg font-bold text-gray-800">
                                    {{ market.currency }} {{ formatPrice(relatedItem.selling_price) }}
                                </div>
                                <div v-if="relatedItem.type" class="text-xs text-gray-600 bg-slate-100 px-2 py-1 rounded border border-gray-200">
                                    {{ formatCategoryName(relatedItem.type) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="bg-gray-900 text-white py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div>
                        <h3 class="text-lg font-semibold mb-4">{{ market.name }}</h3>
                        <p v-if="market.description" class="text-gray-400">{{ market.description }}</p>
                    </div>
                    
                    <div>
                        <h4 class="text-md font-semibold mb-4">Contact</h4>
                        <ul class="space-y-2 text-gray-400">
                            <li v-if="market.contact_email">
                                <a :href="`mailto:${market.contact_email}`" class="hover:text-white transition-colors">
                                    {{ market.contact_email }}
                                </a>
                            </li>
                            <li v-if="market.contact_phone">{{ market.contact_phone }}</li>
                            <li v-if="market.address">{{ market.address }}</li>
                        </ul>
                    </div>
                    
                    <div>
                        <h4 class="text-md font-semibold mb-4">Quick Actions</h4>
                        <ul class="space-y-2 text-gray-400">
                            <li><button @click="goBack" class="hover:text-white transition-colors">Back to Products</button></li>
                            <li><button @click="contactSeller" class="hover:text-white transition-colors">Contact Seller</button></li>
                            <li><button @click="shareProduct" class="hover:text-white transition-colors">Share Product</button></li>
                        </ul>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Head, router } from '@inertiajs/vue3'

// Props
const props = defineProps({
    market: Object,
    item: Object,
    relatedItems: {
        type: Array,
        default: () => []
    }
})

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

const goBack = () => {
    // Try to go back in history first
    if (window.history.length > 1) {
        window.history.back()
    } else {
        // Fallback to market home
        window.location.href = `/market/${props.market.slug}`
    }
}

const viewProduct = (productId) => {
    router.visit(`/market/${props.market.slug}/product/${productId}`)
}

const contactSeller = () => {
    if (props.market.contact_email) {
        const subject = encodeURIComponent(`Inquiry about ${props.item.model}`)
        const body = encodeURIComponent(`Hi,\n\nI'm interested in the ${props.item.model} listed on ${props.market.name}.\n\nPlease let me know if it's still available.\n\nThanks!`)
        window.location.href = `mailto:${props.market.contact_email}?subject=${subject}&body=${body}`
    } else {
        alert('Contact information not available. Please try again later.')
    }
}

const shareProduct = () => {
    if (navigator.share) {
        navigator.share({
            title: `${props.item.model} - ${props.market.name}`,
            text: `Check out this ${props.item.model} at ${props.market.name}`,
            url: window.location.href
        }).catch(err => console.log('Error sharing:', err))
    } else {
        // Fallback: copy to clipboard
        navigator.clipboard.writeText(window.location.href).then(() => {
            alert('Product link copied to clipboard!')
        }).catch(() => {
            alert('Unable to share. Please copy the URL manually.')
        })
    }
}

// Lifecycle
onMounted(() => {
    // Scroll to top when component mounts
    window.scrollTo(0, 0)
})
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>