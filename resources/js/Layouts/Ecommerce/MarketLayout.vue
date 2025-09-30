<template>
    <div class="min-h-screen bg-slate-100">
        <!-- Header -->
        <header class="sticky top-0 z-50 bg-white border-b border-gray-200 shadow-sm">
            <div class="w-full">
                <!-- Top Bar -->
                <div class="hidden md:flex items-center justify-between px-6 py-2 text-sm text-gray-600 bg-slate-50 border-b border-gray-200">
                    <div class="flex items-center space-x-4">
                        <span v-if="market.contact_phone" class="flex items-center space-x-1">
                            <i class="pi pi-phone text-xs text-gray-500"></i>
                            <span>{{ market.contact_phone }}</span>
                        </span>
                        <span v-if="market.contact_email" class="flex items-center space-x-1">
                            <i class="pi pi-envelope text-xs text-gray-500"></i>
                            <span>{{ market.contact_email }}</span>
                        </span>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span v-if="market.business_hours" class="flex items-center space-x-1">
                            <i class="pi pi-clock text-xs text-gray-500"></i>
                            <span>{{ market.business_hours }}</span>
                        </span>
                        <span v-if="market.location" class="flex items-center space-x-1">
                            <i class="pi pi-map-marker text-xs text-gray-500"></i>
                            <span>{{ market.location }}</span>
                        </span>
                    </div>
                </div>

                <!-- Main Header -->
                <div class="flex items-center justify-between px-6 py-4">
                    <!-- Logo & Brand -->
                    <div class="flex items-center space-x-4">
                        <Link :href="route('market.index', market.slug)" class="flex items-center space-x-3 group">
                            <div class="w-10 h-10 rounded-lg bg-slate-100 border border-gray-200 flex items-center justify-center group-hover:border-gray-300 group-hover:bg-slate-200 transition-all duration-200">
                                <i class="pi pi-shop text-lg text-gray-600 group-hover:text-gray-700"></i>
                            </div>
                            <div>
                                <h1 class="text-xl font-bold text-gray-900 group-hover:text-gray-700 transition-colors duration-200">
                                    {{ market.name }}
                                </h1>
                                <p v-if="market.tagline" class="text-sm text-gray-600">{{ market.tagline }}</p>
                            </div>
                        </Link>
                    </div>

                    <!-- Search Bar -->
                    <div class="hidden md:flex flex-1 max-w-xl mx-8">
                        <div class="relative w-full">
                            <input
                                v-model="searchQuery"
                                @keyup.enter="performSearch"
                                type="text"
                                placeholder="Search products..."
                                class="w-full px-4 py-3 pl-10 pr-12 text-gray-900 bg-slate-100 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-300 focus:border-gray-300 placeholder-gray-500 transition-all duration-200"
                            >
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <i class="pi pi-search text-sm text-gray-400"></i>
                            </div>
                            <button
                                v-if="searchQuery"
                                @click="clearSearch"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 transition-colors duration-200"
                            >
                                <i class="pi pi-times text-sm"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center space-x-4">
                        <!-- Search Mobile -->
                        <button
                            @click="toggleMobileSearch"
                            class="md:hidden inline-flex items-center justify-center w-10 h-10 rounded-lg bg-slate-100 text-gray-600 hover:text-gray-700 hover:bg-slate-200 border border-gray-200 hover:border-gray-300 transition-all duration-200 shadow-sm hover:shadow-md"
                        >
                            <i class="pi pi-search text-sm"></i>
                        </button>

                        <!-- Wishlist -->
                        <button
                            @click="toggleWishlist"
                            class="relative inline-flex items-center justify-center w-10 h-10 rounded-lg bg-slate-100 text-gray-600 hover:text-gray-700 hover:bg-slate-200 border border-gray-200 hover:border-gray-300 transition-all duration-200 shadow-sm hover:shadow-md group"
                        >
                            <i class="pi pi-heart text-sm transition-colors duration-200"></i>
                            <span v-if="wishlistCount > 0" class="absolute -top-1 -right-1 inline-flex items-center justify-center min-w-[18px] h-[18px] text-xs font-semibold text-white bg-gray-600 rounded-full shadow-sm border-2 border-white transition-transform duration-200 group-hover:scale-110">
                                {{ wishlistCount > 99 ? '99+' : wishlistCount }}
                            </span>
                        </button>

                        <!-- Cart Button -->
                        <button
                            @click="toggleCart"
                            class="relative inline-flex items-center justify-center w-10 h-10 rounded-lg bg-slate-100 text-gray-600 hover:text-gray-700 hover:bg-slate-200 border border-gray-200 hover:border-gray-300 transition-all duration-200 shadow-sm hover:shadow-md group"
                        >
                            <i class="pi pi-shopping-cart text-sm transition-colors duration-200"></i>
                            <span v-if="cartCount > 0" class="absolute -top-1 -right-1 inline-flex items-center justify-center min-w-[18px] h-[18px] text-xs font-semibold text-white bg-gray-600 rounded-full shadow-sm border-2 border-white transition-transform duration-200 group-hover:scale-110">
                                {{ cartCount > 99 ? '99+' : cartCount }}
                            </span>
                        </button>

                        <!-- Menu Mobile -->
                        <button
                            @click="toggleMobileMenu"
                            class="md:hidden inline-flex items-center justify-center w-10 h-10 rounded-lg bg-slate-100 text-gray-600 hover:text-gray-700 hover:bg-slate-200 border border-gray-200 hover:border-gray-300 transition-all duration-200 shadow-sm hover:shadow-md"
                        >
                            <i class="pi pi-bars text-sm"></i>
                        </button>
                    </div>
                </div>

                <!-- Mobile Search -->
                <div v-if="showMobileSearch" class="md:hidden px-6 pb-4 border-t border-gray-200">
                    <div class="relative">
                        <input
                            v-model="searchQuery"
                            @keyup.enter="performSearch"
                            type="text"
                            placeholder="Search products..."
                            class="w-full px-4 py-3 pl-10 text-gray-900 bg-slate-100 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-300 focus:border-gray-300 placeholder-gray-500"
                        >
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <i class="pi pi-search text-sm text-gray-400"></i>
                        </div>
                    </div>
                </div>

                <!-- Navigation -->
                <nav class="hidden md:flex items-center px-6 py-3 border-t border-gray-200 bg-slate-50">
                    <div class="flex items-center space-x-8">
                        <Link 
                            :href="route('market.index', market.slug)"
                            :class="['text-sm font-medium transition-colors duration-200', 
                                    isCurrentRoute('market.index') ? 'text-gray-900' : 'text-gray-600 hover:text-gray-900']"
                        >
                            Home
                        </Link>
                        <Link 
                            :href="route('market.products', market.slug)"
                            :class="['text-sm font-medium transition-colors duration-200', 
                                    isCurrentRoute('market.products') ? 'text-gray-900' : 'text-gray-600 hover:text-gray-900']"
                        >
                            Products
                        </Link>
                        <Link 
                            v-if="market.has_categories"
                            :href="route('market.categories', market.slug)"
                            :class="['text-sm font-medium transition-colors duration-200', 
                                    isCurrentRoute('market.categories') ? 'text-gray-900' : 'text-gray-600 hover:text-gray-900']"
                        >
                            Categories
                        </Link>
                        <Link 
                            v-if="market.contact_email"
                            :href="route('market.contact', market.slug)"
                            :class="['text-sm font-medium transition-colors duration-200', 
                                    isCurrentRoute('market.contact') ? 'text-gray-900' : 'text-gray-600 hover:text-gray-900']"
                        >
                            Contact
                        </Link>
                    </div>
                </nav>

                <!-- Mobile Menu -->
                <div v-if="showMobileMenu" class="md:hidden border-t border-gray-200 bg-slate-50">
                    <div class="px-6 py-4 space-y-3">
                        <Link 
                            :href="route('market.index', market.slug)"
                            :class="['block text-sm font-medium transition-colors duration-200', 
                                    isCurrentRoute('market.index') ? 'text-gray-900' : 'text-gray-600 hover:text-gray-900']"
                            @click="closeMobileMenu"
                        >
                            Home
                        </Link>
                        <Link 
                            :href="route('market.products', market.slug)"
                            :class="['block text-sm font-medium transition-colors duration-200', 
                                    isCurrentRoute('market.products') ? 'text-gray-900' : 'text-gray-600 hover:text-gray-900']"
                            @click="closeMobileMenu"
                        >
                            Products
                        </Link>
                        <Link 
                            v-if="market.has_categories"
                            :href="route('market.categories', market.slug)"
                            :class="['block text-sm font-medium transition-colors duration-200', 
                                    isCurrentRoute('market.categories') ? 'text-gray-900' : 'text-gray-600 hover:text-gray-900']"
                            @click="closeMobileMenu"
                        >
                            Categories
                        </Link>
                        <Link 
                            v-if="market.contact_email"
                            :href="route('market.contact', market.slug)"
                            :class="['block text-sm font-medium transition-colors duration-200', 
                                    isCurrentRoute('market.contact') ? 'text-gray-900' : 'text-gray-600 hover:text-gray-900']"
                            @click="closeMobileMenu"
                        >
                            Contact
                        </Link>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="relative">
            <slot />
        </main>

        <!-- Footer -->
        <footer class="bg-white border-t border-gray-200 mt-16">
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
                        <div class="space-y-2 text-sm text-gray-600">
                            <div v-if="market.contact_email" class="flex items-center space-x-2">
                                <i class="pi pi-envelope text-sm text-gray-400"></i>
                                <a :href="`mailto:${market.contact_email}`" class="hover:text-gray-900 transition-colors duration-200">
                                    {{ market.contact_email }}
                                </a>
                            </div>
                            <div v-if="market.contact_phone" class="flex items-center space-x-2">
                                <i class="pi pi-phone text-sm text-gray-400"></i>
                                <a :href="`tel:${market.contact_phone}`" class="hover:text-gray-900 transition-colors duration-200">
                                    {{ market.contact_phone }}
                                </a>
                            </div>
                            <div v-if="market.location" class="flex items-center space-x-2">
                                <i class="pi pi-map-marker text-sm text-gray-400"></i>
                                <span>{{ market.location }}</span>
                            </div>
                        </div>
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
                                <Link :href="route('market.products', market.slug)" class="hover:text-gray-900 transition-colors duration-200">
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

        <!-- Cart Drawer -->
        <Cart
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
import { ref, computed, onMounted } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import Cart from '@/Components/Ecommerce/Cart.vue'

// Props
const props = defineProps({
    market: {
        type: Object,
        required: true
    }
})

// Page context
const page = usePage()

// Reactive state
const searchQuery = ref('')
const showMobileSearch = ref(false)
const showMobileMenu = ref(false)
const showCart = ref(false)

// Mock data - Replace with actual stores/API calls
const cartCount = ref(3)
const wishlistCount = ref(5)

// Computed
const currentYear = computed(() => new Date().getFullYear())

// Methods
const isCurrentRoute = (routeName) => {
    // Check component name first
    if (page.component.value === routeName) {
        return true
    }
    
    // Safely check URL if available
    const currentUrl = page.url?.value || window.location.pathname
    if (currentUrl && typeof currentUrl === 'string') {
        return currentUrl.includes(routeName.replace('.', '/'))
    }
    
    return false
}

const toggleMobileSearch = () => {
    showMobileSearch.value = !showMobileSearch.value
    if (showMobileSearch.value) {
        showMobileMenu.value = false
    }
}

const toggleMobileMenu = () => {
    showMobileMenu.value = !showMobileMenu.value
    if (showMobileMenu.value) {
        showMobileSearch.value = false
    }
}

const closeMobileMenu = () => {
    showMobileMenu.value = false
}

const performSearch = () => {
    if (searchQuery.value.trim()) {
        // Redirect to search results page
        window.location.href = route('market.search', { market: props.market.slug, q: searchQuery.value.trim() })
    }
}

const clearSearch = () => {
    searchQuery.value = ''
}

const toggleWishlist = () => {
    // TODO: Implement wishlist functionality
    alert('Wishlist functionality coming soon!')
}

const toggleCart = () => {
    showCart.value = true
}

const closeCart = () => {
    showCart.value = false
}

const handleCheckout = (checkoutData) => {
    console.log('Proceeding to checkout:', checkoutData)
    // TODO: Implement checkout logic
    closeCart()
}

const handleCartItemUpdated = (updateData) => {
    console.log('Cart item updated:', updateData)
    // TODO: Update cart state in store
}

const handleCartItemRemoved = (itemId) => {
    console.log('Cart item removed:', itemId)
    if (itemId === 'all') {
        cartCount.value = 0
    } else {
        cartCount.value = Math.max(0, cartCount.value - 1)
    }
    // TODO: Update cart state in store
}

// Lifecycle
onMounted(() => {
    // Initialize any required data
    console.log('Market Layout mounted for:', props.market.name)
})
</script>

<style scoped>
/* Custom transitions and animations */
.slide-fade-enter-active {
    transition: all 0.3s ease-out;
}

.slide-fade-leave-active {
    transition: all 0.3s cubic-bezier(1.0, 0.5, 0.8, 1.0);
}

.slide-fade-enter-from,
.slide-fade-leave-to {
    transform: translateX(20px);
    opacity: 0;
}

/* Ensure proper stacking context */
header {
    z-index: 40;
}

main {
    z-index: 10;
}

footer {
    z-index: 10;
}
</style>