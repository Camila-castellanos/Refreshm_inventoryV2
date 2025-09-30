<template>
    <Drawer 
        v-model:visible="isVisible" 
        :header="false"
        position="right" 
        class="cart-drawer"
        :style="{ width: '420px' }"
        :modal="true"
        :dismissable-mask="true"
        @hide="handleClose"
    >
        <!-- Cart Header -->
        <div class="flex items-center justify-between p-4 border-b border-gray-200 bg-white">
            <div class="flex items-center space-x-2">
                <div class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-slate-100 text-gray-500">
                    <i class="pi pi-shopping-cart text-sm"></i>
                </div>
                <h2 class="text-lg font-semibold text-gray-900">
                    Shopping Cart {{ itemCount > 0 ? `(${itemCount})` : '' }}
                </h2>
            </div>
            <button 
                @click="closeCart"
                class="inline-flex items-center justify-center w-8 h-8 rounded-md text-gray-500 hover:text-gray-700 bg-slate-100 border border-gray-200 hover:border-gray-300 transition-all duration-200"
            >
                <i class="pi pi-times text-sm"></i>
            </button>
        </div>

        <!-- Cart Content -->
        <div class="flex flex-col h-full">
            
            <!-- Cart Items -->
            <div v-if="items.length > 0" class="flex-1 overflow-y-auto p-4 space-y-4">
                <div 
                    v-for="item in items" 
                    :key="item.id"
                    class="bg-slate-100 rounded-lg border border-gray-200 p-4 transition-all duration-200"
                >
                    <div class="flex space-x-4">
                        <!-- Product Image -->
                        <div class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center flex-shrink-0">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        </div>
                        
                        <!-- Product Info -->
                        <div class="flex-1 min-w-0">
                            <h4 class="text-sm font-medium text-gray-900 truncate">{{ item.model }}</h4>
                            <p v-if="item.manufacturer" class="text-xs text-gray-600 mt-1">{{ item.manufacturer }}</p>
                            
                            <!-- Price and Quantity -->
                            <div class="flex items-center justify-between mt-2">
                                <div class="text-lg font-bold text-gray-800">
                                    {{ market.currency }} {{ formatPrice(item.selling_price) }}
                                </div>
                                
                                <!-- Quantity Controls - Commented out for refurbished individual items -->
                                <!-- 
                                <div class="flex items-center space-x-2">
                                    <button 
                                        @click="updateQuantity(item.id, item.quantity - 1)"
                                        :disabled="item.quantity <= 1"
                                        class="inline-flex items-center justify-center w-6 h-6 rounded-md text-gray-500 hover:text-gray-700 bg-white border border-gray-200 hover:border-gray-300 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        <i class="pi pi-minus text-xs"></i>
                                    </button>
                                    
                                    <span class="text-sm font-medium text-gray-900 min-w-[20px] text-center">
                                        {{ item.quantity }}
                                    </span>
                                    
                                    <button 
                                        @click="updateQuantity(item.id, item.quantity + 1)"
                                        class="inline-flex items-center justify-center w-6 h-6 rounded-md text-gray-500 hover:text-gray-700 bg-white border border-gray-200 hover:border-gray-300 transition-all duration-200"
                                    >
                                        <i class="pi pi-plus text-xs"></i>
                                    </button>
                                </div>
                                -->
                                
                                <!-- Quantity Display (Fixed at 1) -->
                                <div class="flex items-center">
                                    <span class="text-sm font-medium text-gray-600 px-3 py-1 bg-slate-100 rounded-md border border-gray-200">
                                        Qty: {{ item.quantity }}
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Subtotal and Remove -->
                            <div class="flex items-center justify-between mt-3">
                                <div class="text-sm text-gray-600">
                                    Price: {{ market.currency }} {{ formatPrice(item.selling_price) }}
                                </div>
                                
                                <button 
                                    @click="removeItem(item.id)"
                                    class="inline-flex items-center justify-center w-6 h-6 rounded-md text-gray-400 hover:text-red-500 transition-colors duration-200"
                                    v-tooltip.top="'Remove item'"
                                >
                                    <i class="pi pi-trash text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="flex-1 flex items-center justify-center p-8">
                <div class="text-center">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-lg flex items-center justify-center bg-slate-100">
                        <i class="pi pi-shopping-cart text-2xl text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Your cart is empty</h3>
                    <p class="text-gray-600 mb-6">Add some products to get started!</p>
                    <button 
                        @click="closeCart"
                        class="inline-flex items-center justify-center px-6 py-3 rounded-lg bg-slate-100 text-gray-700 hover:text-gray-900 border border-gray-200 hover:border-gray-300 font-medium transition-all duration-200"
                    >
                        Continue Shopping
                    </button>
                </div>
            </div>

            <!-- Cart Footer -->
            <div v-if="items.length > 0" class="border-t border-gray-200 bg-white p-4 space-y-4">
                
                <!-- Order Summary -->
                <div class="space-y-2">
                    <div class="flex justify-between text-sm text-gray-600">
                        <span>Subtotal ({{ itemCount }} {{ itemCount === 1 ? 'item' : 'items' }})</span>
                        <span>{{ market.currency }} {{ formatPrice(subtotal) }}</span>
                    </div>
                    <div class="flex justify-between text-sm text-gray-600">
                        <span>Shipping</span>
                        <span>Calculated at checkout</span>
                    </div>
                    <div class="border-t border-gray-200 pt-2">
                        <div class="flex justify-between text-lg font-semibold text-gray-900">
                            <span>Total</span>
                            <span>{{ market.currency }} {{ formatPrice(total) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="space-y-3">
                    <button 
                        @click="proceedToCheckout"
                        :disabled="isLoading"
                        class="w-full inline-flex items-center justify-center px-6 py-3 rounded-lg bg-slate-100 text-gray-700 hover:text-gray-900 border border-gray-200 hover:border-gray-300 font-semibold transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <i v-if="isLoading" class="pi pi-spin pi-spinner text-sm mr-2"></i>
                        <i v-else class="pi pi-credit-card text-sm mr-2"></i>
                        {{ isLoading ? 'Processing...' : 'Proceed to Checkout' }}
                    </button>
                    
                    <div class="flex space-x-3">
                        <button 
                            @click="clearCart"
                            class="flex-1 inline-flex items-center justify-center px-4 py-2 rounded-lg bg-slate-100 text-gray-700 hover:text-gray-900 border border-gray-200 hover:border-gray-300 text-sm font-medium transition-all duration-200"
                        >
                            <i class="pi pi-trash text-sm mr-2"></i>
                            Clear Cart
                        </button>
                        
                        <button 
                            @click="saveForLater"
                            class="flex-1 inline-flex items-center justify-center px-4 py-2 rounded-lg bg-slate-100 text-gray-700 hover:text-gray-900 border border-gray-200 hover:border-gray-300 text-sm font-medium transition-all duration-200"
                        >
                            <i class="pi pi-heart text-sm mr-2"></i>
                            Save for Later
                        </button>
                    </div>
                </div>

                <!-- Contact Info -->
                <div v-if="market.contact_email" class="text-center pt-3 border-t border-gray-200">
                    <p class="text-xs text-gray-600">
                        Need help? 
                        <a :href="`mailto:${market.contact_email}`" class="text-gray-700 hover:text-gray-900 font-medium">
                            Contact us
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </Drawer>

    <!-- Clear Cart Confirmation Dialog -->
    <Dialog 
        v-model:visible="showClearConfirm" 
        modal 
        :header="false"
        :closable="false"
        :style="{ width: '400px' }"
        class="clear-confirm-dialog"
    >
        <div class="p-6">
            <div class="flex items-center space-x-4 mb-4">
                <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
                    <i class="pi pi-exclamation-triangle text-red-500 text-xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Clear Cart</h3>
                    <p class="text-gray-600 text-sm">This action cannot be undone</p>
                </div>
            </div>
            
            <p class="text-gray-700 mb-6">
                Are you sure you want to remove all items from your cart? 
                You will lose {{ itemCount }} {{ itemCount === 1 ? 'item' : 'items' }} from your cart.
            </p>
            
            <div class="flex space-x-3 justify-end">
                <button 
                    @click="showClearConfirm = false"
                    class="px-4 py-2 rounded-lg bg-slate-100 text-gray-700 hover:text-gray-900 border border-gray-200 hover:border-gray-300 font-medium transition-all duration-200"
                >
                    Cancel
                </button>
                <button 
                    @click="confirmClearCart"
                    class="px-4 py-2 rounded-lg bg-red-500 text-white hover:bg-red-600 font-medium transition-all duration-200"
                >
                    Clear Cart
                </button>
            </div>
        </div>
    </Dialog>
</template>

<script setup>
import { ref, computed, watch, defineExpose } from 'vue'
import Drawer from 'primevue/drawer'
import Dialog from 'primevue/dialog'

// Props
const props = defineProps({
    visible: {
        type: Boolean,
        default: false
    },
    market: {
        type: Object,
        required: true
    }
})

// Emits
const emit = defineEmits(['close', 'checkout', 'item-updated', 'item-removed', 'cart-loaded'])

// Local state
const isVisible = ref(props.visible)
const isLoading = ref(false)
const showClearConfirm = ref(false)

// Cart items - starts empty
const items = ref([])

// Computed properties
const itemCount = computed(() => {
    return items.value.reduce((total, item) => total + item.quantity, 0)
})

const subtotal = computed(() => {
    return items.value.reduce((total, item) => total + (item.selling_price * item.quantity), 0)
})

const total = computed(() => {
    // For now, total equals subtotal (no shipping, taxes, etc.)
    return subtotal.value
})

// Watch for prop changes
watch(() => props.visible, (newValue) => {
    isVisible.value = newValue
})

watch(isVisible, (newValue) => {
    if (!newValue) {
        emit('close')
    } else {
        // When cart opens, emit current count to sync with layout
        emit('cart-loaded', itemCount.value)
    }
})

// Methods
const formatPrice = (price) => {
    return new Intl.NumberFormat().format(price)
}

const closeCart = () => {
    isVisible.value = false
}

const handleClose = () => {
    emit('close')
}

const addItem = (product) => {
    console.log('Cart: Adding item', product)
    
    // Check if item already exists in cart
    const existingItem = items.value.find(item => item.id === product.id)
    
    if (existingItem) {
        // For refurbished items, don't allow multiple quantities
        // Just show a message that item is already in cart
        console.log(`Cart: ${product.model} is already in cart`)
        // Don't show alert here, let ProductCard handle the UI
        return false // Return false to indicate item wasn't added
    } else {
        // If new item, add to cart with quantity 1 (fixed)
        const cartItem = {
            id: product.id,
            model: product.model,
            manufacturer: product.manufacturer,
            selling_price: product.selling_price,
            quantity: 1, // Always 1 for refurbished items
            type: product.type,
            // Add any other necessary product fields
            imei: product.imei,
            issues: product.issues
        }
        items.value.push(cartItem)
        console.log(`Cart: Added new item ${product.model}`)
        
        // Emit the updated count to sync with layout
        emit('item-updated', { 
            itemId: product.id,
            quantity: 1,
            totalItemCount: itemCount.value 
        })
        
        // Dispatch global event for ProductCards to listen
        window.dispatchEvent(new CustomEvent('cart-item-added', {
            detail: { itemId: product.id }
        }))
        
        return true // Return success
    }
}

// Quantity update method - Commented out for refurbished items
/*
const updateQuantity = (itemId, newQuantity) => {
    if (newQuantity < 1) return
    
    const item = items.value.find(item => item.id === itemId)
    if (item) {
        item.quantity = newQuantity
        // Emit both the update details and the new total count
        emit('item-updated', { 
            itemId, 
            quantity: newQuantity,
            totalItemCount: itemCount.value 
        })
    }
}
*/

const removeItem = (itemId) => {
    const index = items.value.findIndex(item => item.id === itemId)
    if (index !== -1) {
        const removedItem = items.value[index]
        items.value.splice(index, 1)
        
        // Emit the total count after removal
        emit('item-removed', {
            itemId,
            totalItemCount: itemCount.value
        })
        
        // Dispatch global event for ProductCards to listen
        window.dispatchEvent(new CustomEvent('cart-item-removed', {
            detail: { itemId: itemId }
        }))
        
        console.log(`Cart: Removed ${removedItem.model} from cart`)
    }
}

const clearCart = () => {
    showClearConfirm.value = true
}

const confirmClearCart = () => {
    items.value = []
    emit('item-removed', 'all')
    
    // Dispatch global event for ProductCards to listen
    window.dispatchEvent(new CustomEvent('cart-cleared'))
    
    // Close confirmation dialog
    showClearConfirm.value = false
    
    console.log('Cart: Cart cleared successfully')
}

const saveForLater = () => {
    // TODO: Implement save for later functionality
    alert('Save for later functionality coming soon!')
}

const proceedToCheckout = () => {
    if (items.value.length === 0) return
    
    isLoading.value = true
    
    // Simulate API call
    setTimeout(() => {
        isLoading.value = false
        emit('checkout', {
            items: items.value,
            total: total.value,
            market: props.market
        })
        
        // In real implementation, this would redirect to checkout page
        alert('Redirecting to checkout... (Not implemented yet)')
    }, 1500)
}

// Expose methods to parent component
defineExpose({
    addItem,
    hasItem: (productId) => {
        return items.value.some(item => item.id === productId)
    },
    removeItem // Use the existing removeItem method
})
</script>

<style scoped>
/* Custom drawer styles */
:deep(.cart-drawer .p-drawer-content) {
    padding: 0;
    display: flex;
    flex-direction: column;
    height: 100%;
}

:deep(.cart-drawer .p-drawer-header) {
    display: none;
}

/* Scrollbar styling */
:deep(.cart-drawer) {
    scrollbar-width: thin;
    scrollbar-color: #e2e8f0 transparent;
}

:deep(.cart-drawer)::-webkit-scrollbar {
    width: 6px;
}

:deep(.cart-drawer)::-webkit-scrollbar-track {
    background: transparent;
}

:deep(.cart-drawer)::-webkit-scrollbar-thumb {
    background-color: #e2e8f0;
    border-radius: 3px;
}

:deep(.cart-drawer)::-webkit-scrollbar-thumb:hover {
    background-color: #cbd5e1;
}
</style>