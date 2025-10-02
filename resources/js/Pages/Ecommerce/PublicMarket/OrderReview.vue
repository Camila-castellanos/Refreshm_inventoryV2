<template>
    <!-- Meta Head -->
    <Head title="Shopping Cart - Review Your Order" />

    <div class="min-h-screen bg-slate-100">
        <!-- Header Section -->
        <section class="py-12 bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2">
                            Shopping Cart
                        </h1>
                        <p class="text-gray-600">
                            Review your items before checkout
                        </p>
                    </div>
                    <div class="hidden md:flex items-center space-x-2 text-sm">
                        <span class="px-3 py-1 bg-slate-100 text-gray-700 rounded-lg border border-gray-200">
                            <i class="pi pi-shopping-cart mr-1"></i>
                            {{ itemCount }} {{ itemCount === 1 ? 'item' : 'items' }}
                        </span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main Content -->
        <section class="py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div v-if="cartItems.length > 0" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <!-- Left Column: Customer Info + Cart Items -->
                    <div class="lg:col-span-2 space-y-6">
                        
                        <!-- Customer Information Form -->
                        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                            <!-- Form Header -->
                            <div class="px-6 py-4 bg-slate-100 border-b border-gray-200">
                                <h2 class="text-lg font-semibold text-gray-900">
                                    <i class="pi pi-user text-sm mr-2"></i>
                                    Customer Information
                                </h2>
                                <p class="text-sm text-gray-600 mt-1">Please provide your contact details</p>
                            </div>

                            <!-- Form Content -->
                            <div class="p-6">
                                <form @submit.prevent="validateCustomerInfo" class="space-y-4">
                                    <!-- Name Fields -->
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label for="firstName" class="block text-sm font-medium text-gray-700 mb-2">
                                                First Name <span class="text-red-500">*</span>
                                            </label>
                                            <input
                                                id="firstName"
                                                v-model="customerInfo.firstName"
                                                type="text"
                                                required
                                                placeholder="John"
                                                class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-gray-400 focus:ring-2 focus:ring-gray-100 transition-all duration-200"
                                                :class="{'border-red-500': errors.firstName}"
                                            />
                                            <span v-if="errors.firstName" class="text-sm text-red-500 mt-1">{{ errors.firstName }}</span>
                                        </div>
                                        <div>
                                            <label for="lastName" class="block text-sm font-medium text-gray-700 mb-2">
                                                Last Name <span class="text-red-500">*</span>
                                            </label>
                                            <input
                                                id="lastName"
                                                v-model="customerInfo.lastName"
                                                type="text"
                                                required
                                                placeholder="Doe"
                                                class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-gray-400 focus:ring-2 focus:ring-gray-100 transition-all duration-200"
                                                :class="{'border-red-500': errors.lastName}"
                                            />
                                            <span v-if="errors.lastName" class="text-sm text-red-500 mt-1">{{ errors.lastName }}</span>
                                        </div>
                                    </div>

                                    <!-- Email -->
                                    <div>
                                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                            Email Address <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            id="email"
                                            v-model="customerInfo.email"
                                            type="email"
                                            required
                                            placeholder="john.doe@example.com"
                                            class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-gray-400 focus:ring-2 focus:ring-gray-100 transition-all duration-200"
                                            :class="{'border-red-500': errors.email}"
                                        />
                                        <span v-if="errors.email" class="text-sm text-red-500 mt-1">{{ errors.email }}</span>
                                    </div>

                                    <!-- Phone -->
                                    <div>
                                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                                            Phone Number <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            id="phone"
                                            v-model="customerInfo.phone"
                                            type="tel"
                                            required
                                            placeholder="+1 (555) 123-4567"
                                            class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-gray-400 focus:ring-2 focus:ring-gray-100 transition-all duration-200"
                                            :class="{'border-red-500': errors.phone}"
                                        />
                                        <span v-if="errors.phone" class="text-sm text-red-500 mt-1">{{ errors.phone }}</span>
                                    </div>

                                    <!-- Additional Notes (Optional) -->
                                    <div>
                                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                                            Additional Notes (Optional)
                                        </label>
                                        <textarea
                                            id="notes"
                                            v-model="customerInfo.notes"
                                            rows="3"
                                            placeholder="Any special instructions or comments..."
                                            class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-gray-400 focus:ring-2 focus:ring-gray-100 transition-all duration-200 resize-none"
                                        ></textarea>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Cart Items List -->
                        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                            <!-- Cart Items Header -->
                            <div class="px-6 py-4 bg-slate-100 border-b border-gray-200 flex items-center justify-between">
                                <h2 class="text-lg font-semibold text-gray-900">
                                    Cart Items ({{ itemCount }})
                                </h2>
                                <button 
                                    v-if="cartItems.length > 0"
                                    @click="confirmClearCart"
                                    class="text-sm text-red-600 hover:text-red-700 font-medium transition-colors duration-200"
                                >
                                    <i class="pi pi-trash mr-1"></i>
                                    Clear Cart
                                </button>
                            </div>

                            <!-- Cart Items -->
                            <div class="divide-y divide-gray-200">
                                <div 
                                    v-for="item in cartItems" 
                                    :key="item.id"
                                    class="p-6 hover:bg-slate-50 transition-colors duration-200"
                                >
                                    <div class="flex gap-6">
                                        <!-- Product Image -->
                                        <div class="flex-shrink-0">
                                            <div class="w-24 h-24 bg-slate-100 rounded-lg border border-gray-200 flex items-center justify-center overflow-hidden">
                                                <img 
                                                    v-if="item.image_url" 
                                                    :src="item.image_url" 
                                                    :alt="item.model"
                                                    class="w-full h-full object-cover"
                                                />
                                                <i v-else class="pi pi-mobile text-3xl text-gray-400"></i>
                                            </div>
                                        </div>

                                        <!-- Product Info -->
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-start justify-between mb-3">
                                                <div class="flex-1 pr-4">
                                                    <h3 class="text-lg font-semibold text-gray-900 mb-1">
                                                        {{ item.model }}
                                                    </h3>
                                                    <p class="text-sm text-gray-600 mb-2">
                                                        {{ item.manufacturer }}
                                                    </p>
                                                </div>
                                                <button
                                                    @click="removeItem(item.id)"
                                                    class="text-gray-400 hover:text-red-600 transition-colors duration-200 p-2"
                                                    title="Remove item"
                                                >
                                                    <i class="pi pi-times text-lg"></i>
                                                </button>
                                            </div>

                                            <!-- Item Details -->
                                            <div class="grid grid-cols-2 gap-3 mb-3">
                                                <div class="flex items-center text-sm text-gray-600">
                                                    <i class="pi pi-id-card text-gray-400 mr-2"></i>
                                                    <span>IMEI: {{ maskIMEI(item.imei) }}</span>
                                                </div>
                                                <div class="flex items-center text-sm text-gray-600">
                                                    <i class="pi pi-info-circle text-gray-400 mr-2"></i>
                                                    <span>{{ item.item_condition || 'Good' }}</span>
                                                </div>
                                                <div v-if="item.storage" class="flex items-center text-sm text-gray-600">
                                                    <i class="pi pi-database text-gray-400 mr-2"></i>
                                                    <span>{{ item.storage }}</span>
                                                </div>
                                                <div v-if="item.color" class="flex items-center text-sm text-gray-600">
                                                    <i class="pi pi-palette text-gray-400 mr-2"></i>
                                                    <span>{{ item.color }}</span>
                                                </div>
                                            </div>

                                            <!-- Price -->
                                            <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-200">
                                                <span class="text-sm text-gray-600">Price:</span>
                                                <span class="text-2xl font-bold text-gray-900">
                                                    {{ market.currency }}{{ formatPrice(item.price) }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Continue Shopping Button -->
                        <button
                            @click="continueShopping"
                            class="w-full inline-flex items-center justify-center px-6 py-3 rounded-lg bg-white text-gray-700 hover:text-gray-900 border border-gray-200 hover:border-gray-300 font-semibold transition-all duration-200"
                        >
                            <i class="pi pi-arrow-left text-sm mr-2"></i>
                            Continue Shopping
                        </button>
                    </div>

                    <!-- Order Summary Sidebar -->
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-lg border border-gray-200 p-6 sticky top-6">
                            <h2 class="text-xl font-bold text-gray-900 mb-6">
                                Order Summary
                            </h2>

                            <!-- Summary Details -->
                            <div class="space-y-4 mb-6">
                                <div class="flex items-center justify-between text-gray-600">
                                    <span>Subtotal ({{ itemCount }} items)</span>
                                    <span class="font-medium">{{ market.currency }}{{ formatPrice(subtotal) }}</span>
                                </div>
                                <div class="flex items-center justify-between text-gray-600">
                                    <span>Shipping</span>
                                    <span class="font-medium text-gray-400">Calculated at checkout</span>
                                </div>
                                <div class="flex items-center justify-between text-gray-600">
                                    <span>Tax</span>
                                    <span class="font-medium text-gray-400">Calculated at checkout</span>
                                </div>
                                
                                <div class="border-t border-gray-200 pt-4">
                                    <div class="flex items-center justify-between">
                                        <span class="text-lg font-semibold text-gray-900">Total</span>
                                        <span class="text-2xl font-bold text-gray-900">
                                            {{ market.currency }}{{ formatPrice(total) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Checkout Button -->
                            <button
                                @click="proceedToCheckout"
                                class="w-full inline-flex items-center justify-center px-6 py-4 rounded-lg bg-gray-800 text-white hover:bg-gray-900 font-semibold text-lg transition-all duration-200 shadow-lg hover:shadow-xl mb-4"
                            >
                                Proceed to Checkout
                                <i class="pi pi-arrow-right text-sm ml-2"></i>
                            </button>

                            <!-- Security Badge -->
                            <div class="flex items-center justify-center text-sm text-gray-500 mt-4">
                                <i class="pi pi-lock mr-2"></i>
                                <span>Secure Checkout</span>
                            </div>

                            <!-- Payment Methods -->
                            <div class="mt-6 pt-6 border-t border-gray-200">
                                <p class="text-xs text-gray-500 text-center mb-3">We accept</p>
                                <div class="flex items-center justify-center gap-3">
                                    <div class="w-12 h-8 bg-slate-100 rounded border border-gray-200 flex items-center justify-center">
                                        <i class="pi pi-credit-card text-gray-400"></i>
                                    </div>
                                    <div class="w-12 h-8 bg-slate-100 rounded border border-gray-200 flex items-center justify-center">
                                        <i class="pi pi-paypal text-gray-400"></i>
                                    </div>
                                    <div class="w-12 h-8 bg-slate-100 rounded border border-gray-200 flex items-center justify-center">
                                        <i class="pi pi-money-bill text-gray-400"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty Cart State -->
                <div v-else class="text-center py-16">
                    <div class="bg-white rounded-lg border border-gray-200 p-12 max-w-md mx-auto">
                        <div class="w-20 h-20 bg-slate-100 rounded-full mx-auto mb-6 flex items-center justify-center">
                            <i class="pi pi-shopping-cart text-4xl text-gray-400"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-3">Your cart is empty</h3>
                        <p class="text-gray-600 mb-8">
                            Looks like you haven't added any items to your cart yet.
                        </p>
                        <button
                            @click="continueShopping"
                            class="inline-flex items-center justify-center px-8 py-4 rounded-lg bg-gray-800 text-white hover:bg-gray-900 font-semibold transition-all duration-200 shadow-lg hover:shadow-xl"
                        >
                            <i class="pi pi-shopping-bag text-sm mr-2"></i>
                            Start Shopping
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Confirmation Dialog -->
        <Dialog v-model:visible="showClearConfirmation" modal :style="{ width: '450px' }" :pt="{
            root: 'rounded-lg',
            header: 'bg-white border-b border-gray-200 px-6 py-4',
            content: 'bg-white px-6 py-4',
            footer: 'bg-white border-t border-gray-200 px-6 py-4'
        }">
            <template #header>
                <h3 class="text-xl font-bold text-gray-900">Clear Cart</h3>
            </template>
            
            <div class="py-4">
                <p class="text-gray-600">
                    Are you sure you want to remove all items from your cart? This action cannot be undone.
                </p>
            </div>

            <template #footer>
                <div class="flex items-center justify-end gap-3">
                    <button
                        @click="showClearConfirmation = false"
                        class="px-6 py-2 rounded-lg bg-slate-100 text-gray-700 hover:text-gray-900 border border-gray-200 hover:border-gray-300 font-medium transition-all duration-200"
                    >
                        Cancel
                    </button>
                    <button
                        @click="clearCart"
                        class="px-6 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700 font-medium transition-all duration-200"
                    >
                        <i class="pi pi-trash mr-2"></i>
                        Clear Cart
                    </button>
                </div>
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import Dialog from 'primevue/dialog'
import MarketLayout from '@/Layouts/Ecommerce/MarketLayout.vue'
import { useCart } from '@/composables/useCart'

defineOptions({ layout: MarketLayout })

// Props
const props = defineProps({
    market: {
        type: Object,
        required: true
    }
})

// Use cart composable instead of store directly
const { 
    items,
    itemCount, 
    subtotal, 
    removeItem: removeItemFromCart, 
    clearCart: clearCartStore,
    setMarket 
} = useCart()

// Reactive state
const showClearConfirmation = ref(false)

// Customer information form
const customerInfo = ref({
    firstName: '',
    lastName: '',
    email: '',
    phone: '',
    notes: ''
})

// Form validation errors
const errors = ref({
    firstName: '',
    lastName: '',
    email: '',
    phone: ''
})

// Computed properties from store
const cartItems = computed(() => {
    // Transform store items to match the template structure
    return items.value.map(item => ({
        id: item.id,
        model: item.model,
        manufacturer: item.manufacturer,
        price: item.selling_price,
        selling_price: item.selling_price,
        quantity: item.quantity,
        imei: item.imei,
        issues: item.issues,
        type: item.type,
        // Add optional fields with defaults
        image_url: null,
        item_condition: 'Refurbished',
        storage: null,
        color: null
    }))
})

const shipping = computed(() => {
    // TODO: Calculate shipping based on items/location
    return 0
})

const tax = computed(() => {
    // TODO: Calculate tax based on location
    return 0
})

const total = computed(() => {
    return subtotal.value + shipping.value + tax.value
})

// Initialize market on mount
onMounted(() => {
    if (props.market?.slug) {
        setMarket(props.market.slug)
    }
})

// Methods
const formatPrice = (price) => {
    return new Intl.NumberFormat().format(price)
}

const maskIMEI = (imei) => {
    if (!imei || imei.length < 8) return imei
    const start = imei.substring(0, 4)
    const end = imei.substring(imei.length - 4)
    return `${start}****${end}`
}

const removeItem = (itemId) => {
    removeItemFromCart(itemId)
}

const confirmClearCart = () => {
    showClearConfirmation.value = true
}

const clearCart = () => {
    clearCartStore()
    showClearConfirmation.value = false
}

const continueShopping = () => {
    router.visit(`/market/${props.market.slug}/products-list`)
}

const validateCustomerInfo = () => {
    // Reset errors
    errors.value = {
        firstName: '',
        lastName: '',
        email: '',
        phone: ''
    }

    let isValid = true

    // Validate first name
    if (!customerInfo.value.firstName.trim()) {
        errors.value.firstName = 'First name is required'
        isValid = false
    }

    // Validate last name
    if (!customerInfo.value.lastName.trim()) {
        errors.value.lastName = 'Last name is required'
        isValid = false
    }

    // Validate email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    if (!customerInfo.value.email.trim()) {
        errors.value.email = 'Email is required'
        isValid = false
    } else if (!emailRegex.test(customerInfo.value.email)) {
        errors.value.email = 'Please enter a valid email address'
        isValid = false
    }

    // Validate phone
    if (!customerInfo.value.phone.trim()) {
        errors.value.phone = 'Phone number is required'
        isValid = false
    } else if (customerInfo.value.phone.replace(/\D/g, '').length < 10) {
        errors.value.phone = 'Please enter a valid phone number'
        isValid = false
    }

    return isValid
}

const proceedToCheckout = () => {
    // Validate customer information first
    if (!validateCustomerInfo()) {
        alert('Please fill in all required customer information fields correctly.')
        return
    }

    // TODO: Navigate to checkout page with customer info
    // router.visit(`/market/${props.market.slug}/checkout`, {
    //     data: {
    //         customerInfo: customerInfo.value,
    //         items: cartItems.value
    //     }
    // })
    
    console.log('Customer Info:', customerInfo.value)
    console.log('Cart Items:', cartItems.value)
    alert('Checkout functionality will be implemented next!')
}
</script>

<style scoped>
/* Additional custom styles if needed */
</style>
