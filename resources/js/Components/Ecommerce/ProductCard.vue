<template>
    <div class="bg-white rounded-lg border border-gray-200 hover:border-gray-300 overflow-hidden transition-all duration-200 hover:shadow-lg group">
        <!-- Product Image Placeholder -->
        <div 
            class="aspect-w-16 aspect-h-12 bg-gray-100 flex items-center justify-center group-hover:bg-gray-50 transition-colors duration-200"
            :class="compact ? 'h-40' : 'h-48'"
        >
            <div class="text-center" :class="compact ? 'p-4' : 'p-6'">
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
                <div 
                    class="font-bold text-gray-800"
                    :class="compact ? 'text-lg' : 'text-2xl'"
                >
                    {{ market.currency }}{{ formatPrice(item.selling_price) }}
                </div>
                
                <span v-if="market.show_inventory_count && !compact" class="text-sm text-green-600 font-medium">
                    In Stock
                </span>
            </div>

            <!-- Action Buttons -->
            <div v-if="compact" class="flex space-x-2">
                <button 
                    @click="handleViewProduct" 
                    class="flex-1 inline-flex items-center justify-center px-3 py-2 rounded-lg bg-slate-100 text-gray-700 hover:text-gray-900 border border-gray-200 hover:border-gray-300 font-medium text-sm transition-all duration-200"
                >
                    View Details
                </button>
                
                <button 
                    @click="handleAddToCart"
                    :disabled="isAddingToCart"
                    :class="[
                        'inline-flex items-center justify-center px-3 py-2 rounded-lg border transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed text-sm font-medium',
                        isInCart 
                            ? 'bg-red-50 text-red-700 hover:text-red-900 border-red-200 hover:border-red-300' 
                            : 'bg-slate-100 text-gray-700 hover:text-gray-900 border-gray-200 hover:border-gray-300'
                    ]"
                    :title="isInCart ? 'Remove from Cart' : 'Add to Cart'"
                >
                    <i v-if="isAddingToCart" class="pi pi-spin pi-spinner text-xs mr-1"></i>
                    <i v-else-if="isInCart" class="pi pi-trash text-xs mr-1"></i>
                    <i v-else class="pi pi-shopping-cart text-xs mr-1"></i>
                    {{ isInCart ? 'Remove' : 'Add' }}
                </button>
            </div>
            
            <div v-else class="flex space-x-2">
                <button 
                    @click="handleViewProduct" 
                    class="flex-1 inline-flex items-center justify-center px-4 py-2 rounded-lg bg-slate-100 text-gray-700 hover:text-gray-900 border border-gray-200 hover:border-gray-300 font-medium text-sm transition-all duration-200"
                >
                    <i class="pi pi-eye text-xs mr-2"></i>
                    View Details
                </button>
                
                <button 
                    @click="handleAddToCart"
                    :disabled="isAddingToCart"
                    :class="[
                        'inline-flex items-center justify-center px-3 py-2 rounded-lg border transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed',
                        isInCart 
                            ? 'bg-red-50 text-red-700 hover:text-red-900 border-red-200 hover:border-red-300' 
                            : 'bg-slate-100 text-gray-700 hover:text-gray-900 border-gray-200 hover:border-gray-300'
                    ]"
                    :title="isInCart ? 'Remove from Cart' : 'Add to Cart'"
                >
                    <i v-if="isAddingToCart" class="pi pi-spin pi-spinner text-xs"></i>
                    <i v-else-if="isInCart" class="pi pi-trash text-xs"></i>
                    <i v-else class="pi pi-shopping-cart text-xs"></i>
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
import { ref, computed, watchEffect, onMounted } from 'vue'
import { useCart } from '@/composables/useCart'

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

// Use cart composable
const { addItem, removeItem, hasItem, items } = useCart()

// Local state for loading feedback
const isAddingToCart = ref(false)
const isInCart = ref(false)

// Watch items changes and update isInCart state
watchEffect(() => {
    // This will automatically re-run whenever items.value changes
    isInCart.value = items.value.some(item => item.id === props.item.id)
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
    emit('view-product', props.item.id)
}

const handleAddToCart = async () => {
    if (isAddingToCart.value) return
    
    isAddingToCart.value = true
    
    try {
        if (isInCart.value) {
            // Remove from cart
            removeItem(props.item.id)
        } else {
            // Add to cart
            addItem(props.item)
        }
        
        // Brief loading animation
        await new Promise(resolve => setTimeout(resolve, 150))
        
    } catch (error) {
        console.error('Error with cart operation:', error)
        alert('Error with cart operation. Please try again.')
    } finally {
        isAddingToCart.value = false
    }
}

// Check initial state on mount
onMounted(() => {
    isInCart.value = items.value.some(item => item.id === props.item.id)
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