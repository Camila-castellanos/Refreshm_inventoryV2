<template>
    <div class="bg-white rounded-lg border border-gray-200 hover:border-gray-300 overflow-hidden transition-all duration-200 hover:shadow-lg group">
        <!-- Product Image -->
        <div 
            class="aspect-w-16 aspect-h-12 bg-white flex items-center justify-center transition-colors duration-200 overflow-hidden"
            :class="compact ? 'h-40' : 'h-48'"
        >
            <!-- Show actual image if available -->
            <img 
                v-if="productImage"
                :src="productImage"
                :alt="item.model"
                class="w-full h-full object-contain group-hover:scale-105 transition-transform duration-300"
            />
            <!-- Fallback placeholder if no image -->
            <div v-else class="text-center" :class="compact ? 'p-4' : 'p-6'">
                <svg 
                    class="text-gray-400 mx-auto mb-2 group-hover:text-gray-500 transition-colors duration-200" 
                    :class="compact ? 'w-12 h-12' : 'w-16 h-16'"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                <p :class="compact ? 'text-xs' : 'text-sm'" class="text-gray-500">{{ item.manufacturer || 'Device' }}</p>
            </div>
        </div>

        <!-- Product Content -->
        <div :class="compact ? 'p-4' : 'p-6'">
            <!-- Category Badge -->
            <div class="mb-3">
                <span v-if="item.type" 
                      class="inline-block text-xs font-medium text-gray-700 bg-slate-100 border border-gray-200 rounded-md"
                      :class="compact ? 'px-2 py-1' : 'px-3 py-1'"
                >
                    {{ formatCategoryName(item.type) }}
                </span>
            </div>
            
            <!-- Product Title -->
            <h3 
                class="font-semibold text-gray-900 mb-2 line-clamp-2 group-hover:text-gray-700 transition-colors duration-200"
                :class="compact ? 'text-sm' : 'text-lg'"
            >
                {{ item.model }}
            </h3>
            
            <!-- Manufacturer (only in full mode) -->
            <p v-if="item.manufacturer && !compact" class="text-sm text-gray-600 mb-3">
                {{ item.manufacturer }}
            </p>

            <!-- Issues/Description (only in full mode) -->
            <p v-if="item.issues && !compact" class="text-sm text-gray-600 mb-4 line-clamp-2">
                {{ item.issues.length > 100 ? item.issues.substring(0, 100) + '...' : item.issues }}
            </p>

            <!-- Price and Stock -->
            <div class="flex items-center justify-between" :class="compact ? 'mb-3' : 'mb-4'">
                <div class="flex flex-col">
                    <!-- Show price range for grouped models (with min_price/max_price) -->
                    <div v-if="isGroupedModel" 
                         class="font-bold text-gray-800"
                         :class="compact ? 'text-lg' : 'text-2xl'"
                    >
                        Starting from {{ getCurrencySymbol(market.currency) }}{{ formatPrice(item.min_price) }}
                    </div>
                    
                    <!-- Show single price for individual items -->
                    <div v-else 
                         class="font-bold text-gray-800"
                         :class="compact ? 'text-lg' : 'text-2xl'"
                    >
                        {{ getCurrencySymbol(market.currency) }}{{ formatPrice(item.selling_price) }}
                    </div>

                    <!-- Show color and condition options for grouped models -->
                    <div v-if="isGroupedModel" class="text-xs text-gray-600 mt-1">
                        {{ item.color_options }} color(s) • {{ item.grade_options }} condition(s)
                    </div>
                </div>
                
                <span v-if="market.show_inventory_count && !compact" class="text-sm text-green-600 font-medium">
                    {{ isGroupedModel ? `${item.total_stock} in stock` : 'In Stock' }}
                </span>
            </div>

            <!-- Action Button -->
            <div class="flex">
                <button 
                    @click="handleViewProduct" 
                    class="flex-1 inline-flex items-center justify-center px-4 py-2 rounded-lg bg-slate-100 text-gray-700 hover:text-gray-900 border border-gray-200 hover:border-gray-300 font-medium text-sm transition-all duration-200"
                >
                    <i class="pi pi-eye text-xs mr-2"></i> See all options
                </button>
            </div>

            <!-- Additional Info (only in full mode) -->
            <div v-if="item.imei && !compact" class="mt-4 pt-4 border-t border-gray-100">
                <p class="text-xs text-gray-500">
                    IMEI: {{ maskIMEI(item.imei) }}
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { getCurrencySymbol } from '@/utils/currency'

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