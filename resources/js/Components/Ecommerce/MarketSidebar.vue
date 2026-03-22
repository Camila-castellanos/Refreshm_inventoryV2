<template>
    <aside class="flex flex-col w-72 md:w-[500px] bg-white min-h-screen sticky top-0 h-screen">
        <!-- Logo -->
        <div class="h-[220px] px-8 pt-8 flex items-center justify-center relative">
            <Link :href="route('market.index', market.slug)" class="block w-full">
                <div v-if="market.logo_url" class="w-full flex items-center justify-center md:justify-start">
                    <img :src="market.logo_url" :alt="market.name" class="w-full max-h-[140px] object-contain object-center md:object-left">
                </div>
                <div v-else class="w-full flex items-center justify-center py-4 bg-gray-50 rounded-lg border border-gray-100">
                    <i class="pi pi-shop text-3xl text-gray-400"></i>
                </div>
            </Link>
            <!-- Botón cerrar (solo visible en móvil) -->
            <button @click="$emit('close-mobile-menu')" class="md:hidden absolute top-4 right-4 text-gray-500 hover:text-gray-700">
                <i class="pi pi-times text-xl"></i>
            </button>
        </div>

        <!-- Scrollable Content -->
        <div class="flex-1 overflow-y-auto">
            <!-- Search Bar -->
            <div class="p-4">
                <div class="relative w-full">
                    <input
                        v-model="searchQuery"
                        @keyup.enter="performSearch"
                        type="text"
                        placeholder="Search products..."
                        class="w-full px-4 py-2 pl-10 pr-10 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-gray-300 focus:border-gray-300 placeholder-gray-500 transition-all duration-200"
                    >
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <i class="pi pi-search text-sm text-gray-400"></i>
                    </div>
                    <button
                        v-if="searchQuery"
                        @click="clearSearch"
                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 transition-colors duration-200"
                    >
                        <i class="pi pi-times text-xs"></i>
                    </button>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="p-4 space-y-6">
                <!-- Brands -->
                <div>
                    <h3 class="px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Shop</h3>
                    <div class="space-y-1">
                        <Link 
                            v-for="brand in phoneBrands"
                            :key="brand.name"
                            :href="brand.filterValue 
                                ? route('market.products-list', { market: market.slug, brand: brand.filterValue })
                                : route('market.products-list', market.slug)"
                            class="flex items-center space-x-3 px-2 py-2 text-sm font-medium rounded-md transition-colors duration-200 text-gray-600 hover:text-gray-900 hover:bg-white"
                            @click="$emit('close-mobile-menu')"
                        >
                            <i :class="['pi text-gray-400', brand.icon]"></i>
                            <span>{{ brand.name }}</span>
                        </Link>
                    </div>
                </div>

                <!-- Main Menu 
                <div>
                    <h3 class="px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Menu</h3>
                    <div class="space-y-1">
                        <Link 
                            v-if="market.has_categories"
                            :href="route('market.categories', market.slug)"
                            :class="['flex items-center space-x-3 px-2 py-2 text-sm font-medium rounded-md transition-colors duration-200', 
                                    isCurrentRoute('market.categories') ? 'text-gray-900 bg-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-white']"
                            @click="$emit('close-mobile-menu')"
                        >
                            <i class="pi pi-tags text-gray-400"></i>
                            <span>Categories</span>
                        </Link>
                        <Link 
                            :href="route('market.faq', market.slug)"
                            :class="['flex items-center space-x-3 px-2 py-2 text-sm font-medium rounded-md transition-colors duration-200', 
                                    isCurrentRoute('market.faq') ? 'text-gray-900 bg-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-white']"
                            @click="$emit('close-mobile-menu')"
                        >
                            <i class="pi pi-question-circle text-gray-400"></i>
                            <span>FAQ</span>
                        </Link>
                        <Link 
                            :href="route('market.contact', market.slug)"
                            :class="['flex items-center space-x-3 px-2 py-2 text-sm font-medium rounded-md transition-colors duration-200', 
                                    isCurrentRoute('market.contact') ? 'text-gray-900 bg-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-white']"
                            @click="$emit('close-mobile-menu')"
                        >
                            <i class="pi pi-envelope text-gray-400"></i>
                            <span>Contact</span>
                        </Link>
                    </div>
                </div> -->

                <!-- Actions 
                <div>
                    <h3 class="px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Quick Actions</h3>
                    <div class="space-y-1">
                        <button
                            @click="toggleWishlist"
                            class="w-full flex items-center justify-between px-2 py-2 text-sm font-medium rounded-md transition-colors duration-200 text-gray-600 hover:text-gray-900 hover:bg-white group"
                        >
                            <div class="flex items-center space-x-3">
                                <i class="pi pi-heart text-gray-400 group-hover:text-red-500 transition-colors"></i>
                                <span>Wishlist</span>
                            </div>
                            <span v-if="wishlistCount > 0" class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-semibold text-white bg-gray-600 rounded-full">
                                {{ wishlistCount > 99 ? '99+' : wishlistCount }}
                            </span>
                        </button>

                        <button
                            @click="toggleCart"
                            class="w-full flex items-center justify-between px-2 py-2 text-sm font-medium rounded-md transition-colors duration-200 text-gray-600 hover:text-gray-900 hover:bg-white group"
                        >
                            <div class="flex items-center space-x-3">
                                <i class="pi pi-shopping-cart text-gray-400 group-hover:text-blue-500 transition-colors"></i>
                                <span>Cart</span>
                            </div>
                            <span v-if="cartCount > 0" class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-semibold text-white bg-gray-600 rounded-full">
                                {{ cartCount > 99 ? '99+' : cartCount }}
                            </span>
                        </button>
                    </div>
                </div> -->
            </nav>
        </div>

        <!-- Sidebar Footer (Links) -->
        <div class="p-4 bg-white mt-auto">
            <div class="space-y-3">
                <Link 
                    :href="route('market.faq', market.slug)"
                    class="block text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors"
                >
                    FAQ
                </Link>
                <!-- Retuns is currently pointing to FAQ as placeholder, update route when Returns page exists -->
                <Link 
                    :href="route('market.faq', market.slug)" 
                    class="block text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors"
                >
                    Returns
                </Link>
                <Link 
                    :href="route('market.contact', market.slug)"
                    class="block text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors"
                >
                    Contact
                </Link>
            </div>
        </div>
    </aside>
</template>

<script setup>
import { ref, computed, inject } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'

const props = defineProps({
    market: {
        type: Object,
        required: true
    },
    cartCount: {
        type: Number,
        default: 0
    }
})

const emit = defineEmits(['close-mobile-menu'])

const page = usePage()
const searchQuery = ref('')
const wishlistCount = ref(5)

// Injected functions from layout
const toggleCart = inject('toggleCart', () => console.warn('toggleCart not provided'))

// Phone brands constant
const phoneBrands = [
    { name: 'Shop All', icon: 'pi-shopping-bag', filterValue: null },
    { name: 'iPhone', icon: 'pi-apple', filterValue: 'Apple' },
    { name: 'Samsung', icon: 'pi-android', filterValue: 'Samsung' },
    { name: 'Pixel', icon: 'pi-google', filterValue: 'Google' },
    { name: 'More', icon: 'pi-ellipsis-h', filterValue: 'More' },
]

const isCurrentRoute = (routeName) => {
    if (page.component.value === routeName) {
        return true
    }
    const currentUrl = page.url?.value || window.location.pathname
    if (currentUrl && typeof currentUrl === 'string') {
        return currentUrl.includes(routeName.replace('.', '/'))
    }
    return false
}

const performSearch = () => {
    if (searchQuery.value.trim()) {
        emit('close-mobile-menu')
        router.visit(route('market.products-list', { 
            market: props.market.slug,
            search: searchQuery.value.trim() 
        }), {
            preserveState: false,
            preserveScroll: false
        })
    }
}

const clearSearch = () => {
    searchQuery.value = ''
}

const toggleWishlist = () => {
    alert('Wishlist functionality coming soon!')
}
</script>