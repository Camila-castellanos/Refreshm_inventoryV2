<template>
    <div class="stripe-payment-container">
        <!-- Loading State -->
        <div v-if="isLoading" class="flex items-center justify-center p-8">
            <div class="text-center">
                <i class="pi pi-spin pi-spinner text-4xl text-gray-600 mb-4"></i>
                <p class="text-gray-600">Loading payment form...</p>
            </div>
        </div>

        <!-- Payment Form -->
        <div v-else-if="!isComplete" class="space-y-6">
            <!-- Order Summary -->
            <div class="bg-slate-50 rounded-lg p-4 border border-gray-200">
                <h3 class="font-semibold text-gray-900 mb-3">Order Summary</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Subtotal ({{ itemCount }} items)</span>
                        <span class="font-medium">{{ currencySymbol }}{{ formatPrice(subtotal) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Tax</span>
                        <span class="font-medium">{{ currencySymbol }}{{ formatPrice(tax) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Shipping</span>
                        <span class="font-medium">{{ currencySymbol }}{{ formatPrice(shipping) }}</span>
                    </div>
                    <div class="border-t border-gray-200 pt-2 mt-2">
                        <div class="flex justify-between text-lg font-bold">
                            <span>Total</span>
                            <span>{{ currencySymbol }}{{ formatPrice(total) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stripe Card Element -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Card Details
                </label>
                <div id="stripe-card-element" class="p-4 border border-gray-300 rounded-lg bg-white"></div>
                <p v-if="cardError" class="mt-2 text-sm text-red-600">{{ cardError }}</p>
            </div>

            <!-- Pay Button -->
            <button
                @click="handlePayment"
                :disabled="isProcessing || !stripe || !elements"
                class="w-full flex items-center justify-center px-6 py-4 rounded-lg bg-gray-900 text-white font-semibold text-lg transition-all duration-200 hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <i v-if="isProcessing" class="pi pi-spin pi-spinner text-lg mr-2"></i>
                <i v-else class="pi pi-lock text-lg mr-2"></i>
                {{ isProcessing ? 'Processing...' : `Pay ${currencySymbol}${formatPrice(total)}` }}
            </button>

            <!-- Security Note -->
            <div class="flex items-center justify-center text-xs text-gray-500">
                <i class="pi pi-shield mr-1"></i>
                <span>Secured by Stripe. Your payment info is encrypted.</span>
            </div>
        </div>

        <!-- Success State -->
        <div v-else class="text-center py-8">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="pi pi-check text-2xl text-green-600"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">Payment Successful!</h3>
            <p class="text-gray-600 mb-6">Your order #{{ orderId }} has been placed.</p>
            <button
                @click="continueShopping"
                class="inline-flex items-center justify-center px-6 py-3 rounded-lg bg-gray-900 text-white font-semibold transition-all duration-200 hover:bg-gray-800"
            >
                Continue Shopping
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, onUnmounted, computed, nextTick, watch } from 'vue'
import { loadStripe } from '@stripe/stripe-js'
import { router } from '@inertiajs/vue3'

const props = defineProps({
    market: {
        type: Object,
        required: true
    },
    clientSecret: {
        type: String,
        required: true
    },
    subtotal: {
        type: Number,
        required: true
    },
    tax: {
        type: Number,
        default: 0
    },
    shipping: {
        type: Number,
        default: 0
    },
    items: {
        type: Array,
        required: true
    },
    customer: {
        type: Object,
        required: true
    },
    isVisible: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['payment-success', 'payment-error'])

// State
const stripe = ref(null)
const elements = ref(null)
const cardError = ref('')
const isLoading = ref(true)
const isProcessing = ref(false)
const isComplete = ref(false)
const orderId = ref(null)

// Computed
const total = computed(() => props.subtotal + props.tax + props.shipping)
const itemCount = computed(() => props.items.length)
const currencySymbol = computed(() => {
    const symbols = { usd: '$', eur: '€', gbp: '£' }
    return symbols[props.market.currency?.toLowerCase()] || '$'
})

// Methods
const formatPrice = (price) => {
    return new Intl.NumberFormat().format(price || 0)
}

const initializeStripe = async () => {
    try {
        // Get Stripe key from server
        const response = await fetch(`/market/${props.market.slug}/checkout/stripe-key`)
        const data = await response.json()
        
        if (!data.publishableKey) {
            throw new Error('Failed to load Stripe key')
        }

        // Load Stripe with English locale
        stripe.value = await loadStripe(data.publishableKey, {
            locale: 'en'
        })
        
        if (!stripe.value) {
            throw new Error('Failed to initialize Stripe')
        }

        // Set loading to false to render the form container
        isLoading.value = false
        
        // Wait for DOM to be ready - poll until element exists
        let attempts = 0
        const maxAttempts = 20
        let container = null
        
        while (!container && attempts < maxAttempts) {
            await nextTick() // Ensure Vue has updated the DOM
            await new Promise(resolve => setTimeout(resolve, 100))
            container = document.getElementById('stripe-card-element')
            attempts++
        }

        if (!container) {
            throw new Error('Card element container not found after ' + maxAttempts + ' attempts')
        }

        // Create Elements
        elements.value = stripe.value.elements()
        
        const card = elements.value.create('card', {
            style: {
                base: {
                    fontSize: '16px',
                    color: '#32325d',
                    fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif',
                    '::placeholder': {
                        color: '#aab7c4',
                    },
                },
                invalid: {
                    color: '#fa755a',
                    iconColor: '#fa755a',
                },
            },
        })

        // Mount to the DOM element by ID
        card.mount('#stripe-card-element')
        
        card.on('change', (event) => {
            cardError.value = event.error ? event.error.message : ''
        })

    } catch (error) {
        console.error('Stripe initialization error:', error)
        cardError.value = 'Failed to load payment form. Please refresh the page.'
        isLoading.value = false // Ensure loading is false on error
    }
}

const handlePayment = async () => {
    if (!stripe.value || !elements.value) return

    isProcessing.value = true
    cardError.value = ''

    try {
        const { error, paymentIntent } = await stripe.value.confirmCardPayment(props.clientSecret, {
            payment_method: {
                card: elements.value.getElement('card'),
                billing_details: {
                    name: `${props.customer.firstName} ${props.customer.lastName}`,
                    email: props.customer.email,
                    phone: props.customer.phone,
                },
            },
        })

        if (error) {
            cardError.value = error.message
            emit('payment-error', error.message)
            isProcessing.value = false
            return
        }

        if (paymentIntent.status === 'succeeded') {
            // Finalize order on server
            const response = await fetch(`/market/${props.market.slug}/checkout/finalize`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    paymentIntentId: paymentIntent.id,
                    customer: props.customer,
                    items: props.items,
                    subtotal: props.subtotal,
                    tax: props.tax,
                    shipping: props.shipping,
                    total: total.value,
                }),
            })

            const result = await response.json()

            if (result.success) {
                isComplete.value = true
                orderId.value = result.order_id
                emit('payment-success', result)
                // Note: Redirection is handled by parent component or here if parent doesn't handle it
            } else {
                throw new Error(result.error || 'Failed to finalize order')
            }
        }
    } catch (error) {
        console.error('Payment error:', error)
        cardError.value = error.message || 'An unexpected error occurred'
        emit('payment-error', error.message)
    } finally {
        isProcessing.value = false
    }
}

const continueShopping = () => {
    router.visit(`/market/${props.market.slug}/products-list`)
}

// Watch for visibility changes to initialize Stripe
watch(() => props.isVisible, async (isVisible) => {
    if (isVisible && props.clientSecret) {
        // Wait for DOM to be rendered
        await nextTick()
        await new Promise(resolve => setTimeout(resolve, 500))
        
        // Only initialize if not already initialized
        if (!stripe.value) {
            initializeStripe()
        }
    }
}, { immediate: true })

// Also watch clientSecret changes
watch(() => props.clientSecret, async (newVal) => {
    if (newVal && props.isVisible) {
        await nextTick()
        await new Promise(resolve => setTimeout(resolve, 500))
        if (!stripe.value) {
            initializeStripe()
        }
    }
})

onUnmounted(() => {
    // Cleanup Stripe elements
    if (elements.value) {
        elements.value = null
    }
})
</script>

<style scoped>
.stripe-payment-container {
    max-width: 500px;
    margin: 0 auto;
}
</style>
