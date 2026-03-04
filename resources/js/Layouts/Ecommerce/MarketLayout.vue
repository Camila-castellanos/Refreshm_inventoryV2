<template>
    <div class="min-h-screen bg-white flex flex-col md:flex-row relative">
        <!-- Mobile Top Bar (Visible only on small screens) -->
        <div class="md:hidden flex items-center justify-between p-4 bg-white border-b border-gray-200 sticky top-0 z-40">
            <div class="flex items-center space-x-3">
                <button @click="toggleMobileMenu" class="text-gray-600 hover:text-gray-900 focus:outline-none">
                    <i class="pi pi-bars text-xl"></i>
                </button>
                <Link :href="route('market.index', market.slug)" class="flex items-center space-x-2">
                    <div v-if="market.logo_url" class="h-8 max-w-[120px] flex items-center">
                        <img :src="market.logo_url" :alt="market.name" class="h-full w-auto object-contain">
                    </div>
                    <template v-else>
                        <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center border border-gray-200">
                            <i class="pi pi-shop text-sm text-gray-600"></i>
                        </div>
                        <span class="font-bold text-gray-900 truncate max-w-[150px]">{{ market.name }}</span>
                    </template>
                </Link>
            </div>
            <button @click="toggleCart" class="relative text-gray-600 hover:text-gray-900 focus:outline-none">
                <i class="pi pi-shopping-cart text-xl"></i>
                <span v-if="cartCount > 0" class="absolute -top-2 -right-2 inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-gray-600 rounded-full border-2 border-white">
                    {{ cartCount > 99 ? '99+' : cartCount }}
                </span>
            </button>
        </div>

        <!-- Mobile Menu Overlay -->
        <div v-if="showMobileMenu" @click="closeMobileMenu" class="fixed inset-0 bg-gray-900/50 z-40 md:hidden transition-opacity"></div>

        <!-- Sidebar -->
        <div 
            :class="[
                'fixed inset-y-0 left-0 z-50 transform transition-transform duration-300 ease-in-out md:relative md:translate-x-0',
                showMobileMenu ? 'translate-x-0' : '-translate-x-full'
            ]"
        >
            <MarketSidebar 
                :market="market" 
                :cart-count="cartCount"
                @close-mobile-menu="closeMobileMenu"
            />
        </div>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0">
            <main class="flex-1">
                <slot />
            </main>

            <!-- Footer -->
            <footer class="bg-white border-t border-gray-200 mt-auto">
                <div class="w-full px-6 py-12">
                    <div class="max-w-7xl mx-auto">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                            <!-- Company Info -->
                            <div class="md:col-span-2">
                                <div class="flex items-center space-x-3 mb-4">
                                    <div class="w-8 h-8 rounded-lg bg-slate-100 border border-gray-200 flex items-center justify-center">
                                        <i class="pi pi-shop text-sm text-gray-600"></i>
                                    </div>
                                    <h3 class="text-lg font-bold text-gray-900">{{ market.name }}</h3>
                                </div>
                                <p v-if="market.description" class="text-gray-600 mb-4 max-w-md">
                                    {{ market.description }}
                                </p>
                            </div>

                            <!-- Quick Links -->
                            <div>
                                <h4 class="text-sm font-semibold text-gray-900 mb-4">Quick Links</h4>
                                <ul class="space-y-2 text-sm text-gray-600">
                                    <li>
                                        <Link :href="route('market.index', market.slug)" class="hover:text-gray-900 transition-colors duration-200">
                                            Home
                                        </Link>
                                    </li>
                                    <li>
                                        <Link :href="route('market.products-list', market.slug)" class="hover:text-gray-900 transition-colors duration-200">
                                            Products
                                        </Link>
                                    </li>
                                    <li v-if="market.has_categories">
                                        <Link :href="route('market.categories', market.slug)" class="hover:text-gray-900 transition-colors duration-200">
                                            Categories
                                        </Link>
                                    </li>
                                    <li v-if="market.contact_email">
                                        <Link :href="route('market.contact', market.slug)" class="hover:text-gray-900 transition-colors duration-200">
                                            Contact
                                        </Link>
                                    </li>
                                    <li>
                                        <Link :href="route('market.faq', market.slug)" class="hover:text-gray-900 transition-colors duration-200">
                                            FAQ
                                        </Link>
                                    </li>
                                </ul>
                            </div>

                            <!-- Business Hours -->
                            <div v-if="market.business_hours">
                                <h4 class="text-sm font-semibold text-gray-900 mb-4">Business Hours</h4>
                                <p class="text-sm text-gray-600">{{ market.business_hours }}</p>
                            </div>
                        </div>

                        <!-- Bottom Bar -->
                        <div class="border-t border-gray-200 mt-8 pt-8 flex flex-col md:flex-row items-center justify-between">
                            <p class="text-sm text-gray-600">
                                © {{ currentYear }} {{ market.name }}. All rights reserved.
                            </p>
                            <div class="flex items-center space-x-6 mt-4 md:mt-0">
                                <span class="text-xs text-gray-500">Powered by RefreshM Inventory</span>
                            </div>
                        </div>
                    </div>
                </div>
            </footer>
        </div>

        <!-- Floating Cart Button (Visible on all screens) -->
        <button 
            @click="toggleCart" 
            class="fixed top-6 right-6 z-[60] hidden md:flex items-center justify-center w-14 h-14 bg-gray-900 text-white rounded-full shadow-lg hover:bg-gray-800 hover:shadow-xl hover:scale-105 transition-all duration-200 group"
            v-if="!showCart"
        >
            <i class="pi pi-shopping-cart text-xl group-hover:animate-wiggle"></i>
            <span v-if="cartCount > 0" class="absolute -top-1 -right-1 inline-flex items-center justify-center w-6 h-6 text-xs font-bold text-white bg-red-500 rounded-full border-2 border-white">
                {{ cartCount > 99 ? '99+' : cartCount }}
            </span>
        </button>

        <!-- Cart Drawer -->
        <Cart
            ref="cartComponent"
            :visible="showCart"
            :market="market"
            @close="closeCart"
            @checkout="handleCheckout"
            @item-updated="handleCartItemUpdated"
            @item-removed="handleCartItemRemoved"
        />
    </div>
</template>

<script setup>
import { ref, computed, onMounted, provide } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import Cart from '@/Components/Ecommerce/Cart.vue'
import MarketSidebar from '@/Components/Ecommerce/MarketSidebar.vue'
import { useCartStore } from '@/stores/cartStore'

// Props
const props = defineProps({
    market: {
        type: Object,
        required: true
    }
})

// Initialize cart store
const cartStore = useCartStore()
const page = usePage()

// Reactive state
const showMobileMenu = ref(false)
const showCart = ref(false)
const cartComponent = ref(null)

// Computed
const cartCount = computed(() => cartStore.itemCount)
const currentYear = computed(() => new Date().getFullYear())

// Methods
const toggleMobileMenu = () => {
    showMobileMenu.value = !showMobileMenu.value
}

const closeMobileMenu = () => {
    showMobileMenu.value = false
}

const toggleCart = () => {
    showCart.value = !showCart.value
}

const closeCart = () => {
    showCart.value = false
}

const addItemToCart = (item) => {
    const success = cartStore.addItem(item)
    if (success) {
        showCart.value = true
    }
    return success
}

const removeItemFromCart = (productId) => {
    cartStore.removeItem(productId)
}

const isItemInCart = (productId) => {
    return cartStore.hasItem(productId)
}

// Provide cart functions for child components and Sidebar
provide('addToCart', addItemToCart)
provide('removeFromCart', removeItemFromCart)
provide('isItemInCart', isItemInCart)
provide('toggleCart', toggleCart)

const handleCheckout = (checkoutData) => {
    console.log('Proceeding to checkout:', checkoutData)
    // TODO: Implement checkout logic
    closeCart()
}

const handleCartItemUpdated = (updateData) => {
    // Handled by store
}

const handleCartItemRemoved = (data) => {
    // Handled by store
    return false
}

// Lifecycle
onMounted(() => {
    if (props.market?.slug) {
        cartStore.setMarket(props.market.slug)
    }
})
</script>

<style scoped>
/* Ensure proper stacking context */
main {
    z-index: 10;
}
</style>