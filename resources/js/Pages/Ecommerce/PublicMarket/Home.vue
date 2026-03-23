<template>
        <!-- Meta Head -->
        <Head 
            :title="market.meta_title || `${market.name} - Online Market`"
            :description="market.meta_description || `Browse and shop ${market.name} collection of quality refurbished devices and electronics.`"
        />

        <!-- Featured Products Preview (Moved to top) -->
        <section class="py-4 md:pt-[220px] bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Featured Products Grid (Limited to 8 items) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 mb-8">
                    <ProductCard
                        v-for="item in featuredItems" 
                        :key="item.id"
                        :item="item"
                        :market="market"
                        :compact="false"
                        @view-product="viewProduct"
                    />
                </div>
            </div>
        </section>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, router } from '@inertiajs/vue3'
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
const featuredItems = computed(() => props.initialItems.slice(0, 8)) // Show only 8 featured items

// Methods
const formatPrice = (price) => {
    return new Intl.NumberFormat().format(price)
}

const viewProduct = (productId) => {
    window.open(`/market/${props.market.slug}/product/${productId}`, '_blank')
}

const browseAllProducts = () => {
    router.visit(`/market/${props.market.slug}/products-list`)
}

// Lifecycle
onMounted(() => {
    // Any initialization logic here
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