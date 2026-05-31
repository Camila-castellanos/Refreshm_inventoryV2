<template>
    <div @click="handleViewProduct" class="bg-white rounded-lg border border-gray-200 hover:border-gray-300 overflow-hidden transition-all duration-200 hover:shadow-lg group cursor-pointer flex flex-col h-full">
        <!-- Product Image -->
        <div 
            class="aspect-square bg-white flex items-center justify-center transition-colors duration-200 overflow-hidden w-full relative"
        >
            <!-- Show actual image if available -->
            <img 
                v-if="productImage && !imageError"
                :src="productImage"
                :alt="item.model"
                class="absolute inset-0 w-full h-full object-contain p-4 group-hover:scale-105 transition-transform duration-300"
                @error="imageError = true"
            />
            <!-- Fallback placeholder if no image or error -->
            <div v-else class="text-center absolute inset-0 flex flex-col items-center justify-center p-4">
                <svg 
                    class="text-gray-300 mx-auto mb-2" 
                    :class="compact ? 'w-20 h-28' : 'w-24 h-36'"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24"
                >
                    <!-- Celular estilo moderno, más ancho -->
                    <rect x="5" y="1" width="14" height="22" rx="2" stroke-width="1.5" />
                    <line x1="12" y1="19" x2="12" y2="19" stroke-width="3" stroke-linecap="round" />
                </svg>

                <p :class="compact ? 'text-xs' : 'text-sm'" class="text-gray-400">{{ item.manufacturer || 'Device' }}</p>
            </div>
        </div>

        <!-- Product Content -->
        <div class="flex flex-col flex-1" :class="compact ? 'p-4' : 'p-5'">
            <!-- Content Wrapper to push action button to bottom -->
            <div class="flex-1">
                <!-- Manufacturer -->
                <p v-if="item.manufacturer" class="text-xs text-gray-500 mb-1 font-medium tracking-wide">
                    {{ item.manufacturer }}
                </p>

                <!-- Product Title -->
                <h3 
                    class="font-semibold text-gray-900 mb-3 line-clamp-2 group-hover:text-gray-700 transition-colors duration-200 leading-tight"
                    :class="compact ? 'text-sm' : 'text-base'"
                >
                    {{ item.model }}
                </h3>

                <!-- Price and Stock -->
                <div class="flex items-end justify-between mb-4 mt-auto">
                    <div class="flex flex-col">
                        <!-- Show price range for grouped models (with min_price/max_price) -->
                        <div v-if="isGroupedModel" 
                             class="font-bold text-gray-900 leading-none"
                             :class="compact ? 'text-base' : 'text-xl'"
                        >
                            <span class="text-xs text-gray-500 font-normal mr-1">from</span>{{ formatCurrency(item.min_price, market.currency) }}
                        </div>
                        
                        <!-- Show single price for individual items -->
                        <div v-else 
                             class="font-bold text-gray-900 leading-none"
                             :class="compact ? 'text-base' : 'text-xl'"
                        >
                            {{ formatCurrency(item.selling_price, market.currency) }}
                        </div>

                    </div>
                    
                    <span v-if="market.show_inventory_count && !compact" class="text-xs text-green-600 font-medium bg-green-50 px-2 py-1 rounded-md">
                        {{ isGroupedModel ? `${item.total_stock} in stock` : 'In Stock' }}
                    </span>
                </div>
            </div>

            <!-- Action Button - Always at bottom -->
            <div class="mt-auto pt-4 border-t border-gray-100">
                <button 
                    @click.stop="handleViewProduct" 
                    class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-lg bg-gray-50 text-gray-700 hover:text-gray-900 hover:bg-gray-100 border border-transparent font-medium text-sm transition-all duration-200"
                >
                    View Details
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useCurrency } from '@/Composables/useCurrency'

const { formatCurrency } = useCurrency()

// Props
const props = defineProps({
    item: {
        type: Object,
        required: true
    },
    market: {
        type: Object,
        required: true
    },
    compact: {
        type: Boolean,
        default: false
    }
})

// State for image errors
const imageError = ref(false)

// Emits
const emit = defineEmits(['view-product'])

// Detect if this is a grouped model (has min_price/max_price) or individual item (has selling_price)
const isGroupedModel = computed(() => {
    return props.item.min_price !== undefined && props.item.max_price !== undefined
})

// Get product image (from grouped model or individual item)
const productImage = computed(() => {
    // For grouped models, use the 'photo' field
    if (props.item.photo) {
        return props.item.photo
    }
    // For individual items, use main_photo_thumb
    if (props.item.main_photo_thumb) {
        return props.item.main_photo_thumb
    }
    return null
})

// Methods
const formatCategoryName = (category) => {
    return category.charAt(0).toUpperCase() + category.slice(1)
}

const maskIMEI = (imei) => {
    if (!imei || imei.length < 8) return imei
    const start = imei.substring(0, 4)
    const end = imei.substring(imei.length - 4)
    return `${start}****${end}`
}

const handleViewProduct = () => {
    // If it's a grouped model, navigate to the model variants page
    if (isGroupedModel.value) {
        window.location.href = `/market/${props.market.slug}/model/${encodeURIComponent(props.item.model)}/variants`
        return
    }
    
    // Otherwise, navigate to the individual product detail page
    emit('view-product', props.item.id || props.item.sample_item_id)
}

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