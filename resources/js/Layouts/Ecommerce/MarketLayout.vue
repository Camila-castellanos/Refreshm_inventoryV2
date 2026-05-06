<template>
    <div class="min-h-screen bg-white flex flex-col md:flex-row relative">
        <!-- Mobile Top Bar (Visible only on small screens) -->
        <div class="md:hidden flex items-center justify-between px-3 py-2 bg-white border-b border-gray-200 sticky top-0 z-40 min-h-[56px]">
            <button @click="toggleMobileMenu" class="text-gray-600 hover:text-gray-900 focus:outline-none flex-shrink-0 p-1">
                <i class="pi pi-bars text-xl"></i>
            </button>
            <Link :href="route('market.index', market.slug)" class="flex-1 flex items-center justify-center px-3">
                <img v-if="market.logo_url" :src="market.logo_url" :alt="market.name" class="block h-4 w-auto object-contain">
                <span v-else class="font-bold text-gray-900 text-base truncate">{{ market.name }}</span>
            </Link>
            <button @click="toggleCart" class="relative text-gray-600 hover:text-gray-900 focus:outline-none flex-shrink-0 p-1">
                <i class="pi pi-shopping-cart text-xl"></i>
                <span v-if="cartCount > 0" class="absolute -top-2 -right-1 inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-gray-600 rounded-full border-2 border-white">
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
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-8 text-center md:text-left">
                            <!-- Company Info -->
                            <div class="md:col-span-2 flex flex-col items-center md:items-start">
                                <Link :href="route('market.index', market.slug)" class="flex items-center justify-center md:justify-start mb-6">
                                    <img v-if="market.logo_url" :src="market.logo_url" :alt="market.name" class="block h-12 w-auto object-contain">
                                    <div v-else class="flex items-center space-x-3">
                                        <div class="w-12 h-12 rounded-lg bg-slate-100 border border-gray-200 flex items-center justify-center">
                                            <i class="pi pi-shop text-lg text-gray-600"></i>
                                        </div>
                                        <h3 class="text-2xl font-bold text-gray-900">{{ market.name }}</h3>
                                    </div>
                                </Link>
                                <p v-if="market.description" class="text-gray-600 mb-4 max-w-md">
                                    {{ market.description }}
                                </p>
                            </div>

                            <!-- Quick Links -->
                            <div class="flex flex-col items-center md:items-start">
                                <h4 class="text-sm font-semibold text-gray-900 mb-4 uppercase tracking-wider">Quick Links</h4>
                                <ul class="space-y-3 md:space-y-2 text-sm text-gray-600 text-center md:text-left">
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
                            <div v-if="market.business_hours" class="flex flex-col items-center md:items-start">
                                <h4 class="text-sm font-semibold text-gray-900 mb-4 uppercase tracking-wider">Business Hours</h4>
                                <p class="text-sm text-gray-600 whitespace-pre-line text-center md:text-left">{{ market.business_hours }}</p>
                            </div>
                        </div>

                        <!-- Bottom Bar -->
                        <div class="border-t border-gray-200 mt-10 pt-8 flex flex-col md:flex-row items-center justify-between text-center md:text-left">
                            <p class="text-sm text-gray-600 mb-4 md:mb-0">
                                © {{ currentYear }} {{ market.name }}. All rights reserved.
                            </p>
                            <div class="flex items-center space-x-6">
                                <span class="text-xs text-gray-400">Powered by RefreshM Inventory</span>
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
import { ref, computed, onMounted, provide, watch, onUnmounted } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'
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

// GTAG Tracking logic
let unregisterRouterListener = null

const injectGtagScript = (id) => {
    if (!id || typeof window === 'undefined') return

    if (!window.gtag) {
        // Create script tag for gtag.js
        const script = document.createElement('script')
        script.async = true
        script.src = `https://www.googletagmanager.com/gtag/js?id=${id}`
        document.head.appendChild(script)

        // Initialize dataLayer and gtag function
        window.dataLayer = window.dataLayer || []
        window.gtag = function () {
            window.dataLayer.push(arguments)
        }
        window.gtag('js', new Date())
    }

    // Configure the tag
    window.gtag('config', id, {
        page_path: page.url,
        send_page_view: true
    })
}

const trackPageView = () => {
    if (props.market?.google_tag_id && window.gtag) {
        window.gtag('config', props.market.google_tag_id, {
            page_path: page.url
        })
    }
}

// Computed
const cartCount = computed(() => cartStore.itemCount)
const currentYear = computed(() => new Date().getFullYear())

// Methods
const updateFavicon = (url) => {
    if (!url) return
    
    let link = document.querySelector("link[rel~='icon']")
    if (!link) {
        link = document.createElement('link')
        link.rel = 'icon'
        document.head.appendChild(link)
    }
    link.href = url
}

// Watch for market favicon changes to update favicon
watch(() => props.market?.favicon_url, (newFavicon) => {
    if (newFavicon) {
        updateFavicon(newFavicon)
    }
}, { immediate: true })

// Watch for GTAG ID changes
watch(() => props.market?.google_tag_id, (newId, oldId) => {
    if (newId && newId !== oldId) {
        injectGtagScript(newId)
    }
}, { immediate: true })

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

    // SPA tracking for Inertia navigations
    unregisterRouterListener = router.on('finish', () => {
        trackPageView()
    })
})

onUnmounted(() => {
    if (unregisterRouterListener) {
        unregisterRouterListener()
    }
})
</script>

<style scoped>
/* Ensure proper stacking context */
main {
    z-index: 10;
}
</style>