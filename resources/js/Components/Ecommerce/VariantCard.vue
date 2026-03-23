<template>
    <div class="bg-white rounded-lg border border-gray-200 overflow-visible hover:shadow-lg transition-all duration-200 hover:border-gray-300 z-10 relative">
        <div class="flex flex-col lg:flex-row">
            <!-- Product Image (Top on mobile, Left on desktop) - Larger -->
            <div class="flex-none w-full lg:w-2/5 flex flex-col items-center justify-center p-8 bg-white border-b lg:border-b-0 lg:border-r border-gray-200">
                <!-- Product Image -->
                <div class="w-64 h-64 bg-white flex items-center justify-center rounded-lg mb-6 flex-shrink-0 overflow-hidden border border-gray-100 cursor-pointer hover:border-gray-300 transition-all duration-300 hover:shadow-xl hover:scale-105" @click="openImageModal" style="perspective: 1000px;">
                    <img v-if="item.main_photo_thumb && !imageError" 
                         :src="item.main_photo_thumb" 
                         :alt="item.model"
                         class="w-full h-full object-contain transition-transform duration-300"
                         @error="imageError = true"
                    />
                    <svg v-else class="w-24 h-36 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <rect x="5" y="1" width="14" height="22" rx="2" stroke-width="1.5" />
                        <line x1="12" y1="19" x2="12" y2="19" stroke-width="3" stroke-linecap="round" />
                    </svg>
                </div>
                <!-- Product Name -->
                <h4 class="font-semibold text-lg text-gray-900 text-center line-clamp-2">{{ item.model }}</h4>
            </div>

            <!-- Product Details (Bottom on mobile, Right on desktop) -->
            <div class="flex-1 w-full lg:w-3/5 p-8 flex flex-col justify-between">
                <!-- Characteristics -->
                <div>
                    <h5 class="text-sm font-semibold text-gray-600 uppercase tracking-wide mb-6">Specifications</h5>
                    <div class="space-y-4 text-base text-gray-700 mb-8">
                        <div v-if="item.storage" class="flex justify-between items-center pb-3 border-b border-gray-100">
                            <span class="text-gray-600 font-medium">Storage:</span>
                            <span class="font-semibold text-gray-900">{{ item.storage }}</span>
                        </div>
                        <div v-if="item.colour" class="flex justify-between items-center pb-3 border-b border-gray-100">
                            <span class="text-gray-600 font-medium">Color:</span>
                            <span class="font-semibold text-gray-900">{{ colour }}</span>
                        </div>
                        <div v-if="item.grade" class="flex justify-between items-center pb-3 border-b border-gray-100 group relative">
                            <div class="flex items-center gap-2">
                                <span class="text-gray-600 font-medium">Condition:</span>
                                <span class="font-semibold text-gray-900">{{ item.grade }}</span>
                            </div>
                            <div class="relative">
                                <button 
                                    class="text-gray-500 hover:text-gray-700 transition-colors"
                                >
                                    <i class="pi pi-info-circle"></i>
                                </button>
                                <!-- Hover Tooltip -->
                                <div 
                                    class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 w-64 bg-gray-900 text-white text-sm px-4 py-3 rounded-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-[100] shadow-xl text-center"
                                >
                                    <p class="text-sm leading-relaxed">{{ conditionDescription }}</p>
                                    <!-- Arrow -->
                                    <div class="absolute top-full left-1/2 transform -translate-x-1/2 border-4 border-transparent border-t-gray-900"></div>
                                </div>
                            </div>
                        </div>
                        <div v-if="item.battery" class="flex justify-between items-center pb-3 border-b border-gray-100">
                            <span class="text-gray-600 font-medium">Battery:</span>
                            <span class="font-semibold text-gray-900">{{ item.battery }}%</span>
                        </div>
                        <div v-if="item.issues" class="flex justify-between items-center pb-3 border-b border-red-100 bg-red-50 px-3 py-2 rounded">
                            <span class="text-red-700 font-medium">⚠️ Issues:</span>
                            <span class="font-semibold text-red-700">{{ item.issues }}</span>
                        </div>
                    </div>

                    <!-- Description -->
                    <div v-if="item.description" class="mb-6 prose prose-sm max-w-none text-gray-700">
                        <div v-html="item.description"></div>
                    </div>

                    <!-- Price -->
                    <div class="mb-8 pt-4 border-t-2 border-gray-200">
                        <p class="text-sm text-gray-600 mb-2">Price</p>
                        <p class="text-4xl font-bold text-gray-900">
                            {{ currencySymbol }}{{ formatPrice(item.selling_price) }}
                        </p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex gap-3 pt-4 border-t border-gray-200">
                    <button @click="openImageModal" 
                            class="flex-1 px-6 py-3 bg-slate-100 text-gray-700 rounded-lg hover:bg-slate-200 font-semibold text-base transition-colors border border-gray-200 hover:border-gray-300 hover:shadow-md">
                        <i class="pi pi-eye text-sm mr-2"></i> View
                    </button>
                    <button 
                        @click="handleAddToCart" 
                        :disabled="isLoading || isInCart"
                        :class="[
                            'flex-1 px-6 py-3 rounded-lg font-semibold text-base transition-all border',
                            isInCart 
                                ? 'bg-green-100 text-green-700 border-green-200 cursor-default'
                                : 'bg-slate-100 text-gray-700 border-gray-200 hover:bg-slate-200 hover:border-gray-300 hover:shadow-md disabled:opacity-50 disabled:cursor-not-allowed'
                        ]"
                    >
                        <template v-if="isLoading">
                            <i class="pi pi-spin pi-spinner text-sm mr-2"></i>
                            Adding...
                        </template>
                        <template v-else-if="isInCart">
                            <i class="pi pi-check text-sm mr-2"></i>
                            Added
                        </template>
                        <template v-else>
                            <i class="pi pi-shopping-cart text-sm mr-2"></i>
                            Add
                        </template>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <transition name="fade">
        <div v-if="showImageModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[99999] p-4 !mt-0" @click="closeImageModal">
            <div class="relative w-full h-[90vh] max-h-[90vh] content-center">
                <!-- Close Button -->
                <button
                    @click="closeImageModal"
                    class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition-colors z-10"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>

                <!-- Image Container -->
                <div class="flex-1 bg-transparent flex items-center justify-center p-4 perspective image-container-scroll w-full h-full max-h-[80vh] overflow-auto">
                    <img
                        :src="item.main_photo_url || item.main_photo_thumb"
                        :alt="item.model"
                        class="max-w-full max-h-full object-contain image-3d-effect"
                    />
                </div>
            </div>
        </div>
    </transition>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useCart } from '@/composables/useCart'

const props = defineProps({
    item: {
        type: Object,
        required: true
    },
    colour: {
        type: String,
        default: ''
    },
    currencySymbol: {
        type: String,
        required: true
    },
    isLoading: {
        type: Boolean,
        default: false
    }
})

// State for image errors
const imageError = ref(false)


const emit = defineEmits(['toggle-condition', 'view-product', 'add-to-cart'])

const { isItemInCart, addItem: addToCartStore } = useCart()
const showImageModal = ref(false)
const localIsInCart = ref(false)

const conditionDescription = computed(() => getConditionDescription(props.item.grade))

const isInCart = computed(() => {
    return localIsInCart.value || isItemInCart(props.item.id)
})

const handleAddToCart = () => {
    if (isInCart.value || props.isLoading) return
    
    localIsInCart.value = true
    emit('add-to-cart', props.item)
}

const getConditionDescription = (grade) => {
    const descriptions = {
        'Excellent': 'The phone is in "Like New" condition with no noticeable signs of previous use. This is as close to a new phone as you can get!',
        'Good': 'The phone shows very minor signs of previous use, may include light scratches on the screen & body. This is a phone that was well looked after by its previous owner.',
        'Fair': 'The phone shows previous signs of use, may include scratches on the screen & body. However, the phone is still in perfect working order without any issues!'
    }
    return descriptions[grade] || ''
}

const openImageModal = () => {
    showImageModal.value = true
}
    
const closeImageModal = () => {
    showImageModal.value = false
}
    
    
const formatPrice = (price) => {
    return new Intl.NumberFormat().format(price)
}

// Listen for cart updates to sync local state
const handleCartUpdate = (event) => {
    const { action, itemId } = event.detail
    if (itemId === props.item.id) {
        if (action === 'removed' || action === 'cleared') {
            localIsInCart.value = false
        }
    }
}

onMounted(() => {
    window.addEventListener('cart-updated', handleCartUpdate)
})

onUnmounted(() => {
    window.removeEventListener('cart-updated', handleCartUpdate)
})
</script>

<style scoped>
.fade-enter-active, .fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from, .fade-leave-to {
    opacity: 0;
}

.perspective {
    perspective: 1500px;
}

.image-3d-effect {
    transition: all 0.4s cubic-bezier(0.23, 1, 0.320, 1);
    transform-style: preserve-3d;
}

.image-3d-effect:hover {
    transform: rotateX(8deg) rotateY(-8deg) rotateZ(2deg) scale(1.05);
}

/* Hide scrollbars */
.image-container-scroll {
    overflow: auto;
    scrollbar-width: none;
    -ms-overflow-style: none;
}

.image-container-scroll::-webkit-scrollbar {
    display: none;
}
</style>
