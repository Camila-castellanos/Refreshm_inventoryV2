<template>
    <!-- Meta Head -->
    <Head title="Order Confirmed - Thank You" />

    <div class="min-h-screen bg-slate-50 py-12">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Success Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <!-- Header -->
                <div class="bg-green-50 px-8 py-12 text-center border-b border-green-100">
                    <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="pi pi-check text-4xl text-green-600"></i>
                    </div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Thank you for your order!</h1>
                    <p class="text-gray-600">
                        A confirmation email has been sent to you.
                    </p>
                </div>

                <!-- Order Details -->
                <div class="px-8 py-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-1">Order Number</h3>
                            <p class="text-lg font-bold text-gray-900">#{{ order.id }}</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-1">Date</h3>
                            <p class="text-lg font-medium text-gray-900">{{ order.date }}</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-1">Customer</h3>
                            <p class="text-lg font-medium text-gray-900">
                                <span v-if="order.customer_details && order.customer_details.firstName">
                                    {{ order.customer_details.firstName }} {{ order.customer_details.lastName }}
                                </span>
                                <span v-else>{{ order.customer }}</span>
                            </p>
                            <div v-if="order.customer_details" class="mt-2 space-y-1">
                                <p v-if="order.customer_details.email" class="text-sm text-gray-600 flex items-center">
                                    <i class="pi pi-envelope mr-2 text-gray-400"></i>
                                    {{ order.customer_details.email }}
                                </p>
                                <p v-if="order.customer_details.phone" class="text-sm text-gray-600 flex items-center">
                                    <i class="pi pi-phone mr-2 text-gray-400"></i>
                                    {{ order.customer_details.phone }}
                                </p>
                            </div>
                        </div>
                        <div v-if="order.customer_details && order.customer_details.notes">
                            <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-1">Notes</h3>
                            <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
                                <p class="text-sm text-gray-600 italic">
                                    "{{ order.customer_details.notes }}"
                                </p>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-1">Payment Method</h3>
                            <p class="text-lg font-medium text-gray-900">
                                <i class="pi pi-credit-card mr-2 text-gray-400"></i>
                                Credit Card
                            </p>
                        </div>
                    </div>

                    <!-- Items List -->
                    <div class="border-t border-gray-200 pt-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-6">Order Items</h3>
                        <div class="space-y-4">
                            <div v-for="item in order.items" :key="item.id" class="flex items-center space-x-4">
                                <div class="w-16 h-16 bg-gray-100 rounded-lg flex-shrink-0 overflow-hidden border border-gray-200">
                                    <img 
                                        v-if="item.image_url" 
                                        :src="item.image_url" 
                                        class="w-full h-full object-cover"
                                        alt="Product image"
                                    />
                                    <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                                        <i class="pi pi-image text-xl"></i>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-sm font-medium text-gray-900 truncate">{{ item.model }}</h4>
                                    <p class="text-sm text-gray-500">{{ item.manufacturer }}</p>
                                    <p class="text-xs text-gray-400 font-mono">IMEI: {{ maskIMEI(item.imei) }}</p>
                                </div>
                                <div class="text-sm font-medium text-gray-900">
                                    {{ currencySymbol }}{{ formatPrice(item.price) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Totals -->
                    <div class="border-t border-gray-200 mt-8 pt-8">
                        <div class="space-y-3">
                            <div class="flex justify-between text-sm text-gray-600">
                                <span>Subtotal</span>
                                <span>{{ currencySymbol }}{{ formatPrice(order.subtotal) }}</span>
                            </div>
                            <div class="flex justify-between text-sm text-gray-600">
                                <span>Tax</span>
                                <span>{{ currencySymbol }}{{ formatPrice(order.tax) }}</span>
                            </div>
                            <div class="flex justify-between text-base font-bold text-gray-900 pt-3 border-t border-gray-100">
                                <span>Total</span>
                                <span>{{ currencySymbol }}{{ formatPrice(order.total) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="bg-gray-50 px-8 py-6 border-t border-gray-200 flex justify-center">
                    <button 
                        @click="continueShopping"
                        class="inline-flex items-center justify-center px-6 py-3 rounded-lg bg-gray-900 text-white font-semibold hover:bg-gray-800 transition-all duration-200"
                    >
                        <i class="pi pi-shopping-bag mr-2"></i>
                        Continue Shopping
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3'
import MarketLayout from '@/Layouts/Ecommerce/MarketLayout.vue'
import { getCurrencySymbol } from '@/utils/currency'
import { computed } from 'vue'

defineOptions({ layout: MarketLayout })

const props = defineProps({
    market: Object,
    order: Object
})

const currencySymbol = computed(() => {
    const symbols = { usd: '$', eur: '€', gbp: '£' }
    return symbols[props.market.currency?.toLowerCase()] || '$'
})

const formatPrice = (price) => {
    return new Intl.NumberFormat().format(price || 0)
}

const maskIMEI = (imei) => {
    if (!imei || imei.length < 8) return imei
    return `${imei.substring(0, 4)}****${imei.substring(imei.length - 4)}`
}

const continueShopping = () => {
    router.visit(`/market/${props.market.slug}/products-list`)
}
</script>
