<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Header Navigation -->
        <nav class="bg-white border-b border-gray-200 sticky top-0 z-40 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center space-x-6">
                        <router-link :to="{ name: 'market.index', params: { slug: market.slug } }" 
                                     class="text-gray-600 hover:text-gray-900 font-medium text-base transition-colors">
                            &larr; Back to Store
                        </router-link>
                        <span class="text-gray-300 text-lg">|</span>
                        <h1 class="text-3xl font-bold text-gray-900">{{ modelData.model }}</h1>
                    </div>
                    <a href="#cart" class="inline-flex items-center px-6 py-3 rounded-lg bg-slate-100 text-gray-700 hover:text-gray-900 border border-gray-200 hover:border-gray-300 font-medium transition-all hover:shadow-md">
                        <i class="pi pi-shopping-cart mr-3 text-lg"></i>
                        Cart
                    </a>
                </div>
                <div class="flex items-center space-x-4 text-base text-gray-600">
                    <span class="font-medium">{{ modelData.manufacturer }}</span>
                    <span class="text-gray-300">•</span>
                    <span class="text-green-600 font-semibold">{{ modelData.total_stock }} in stock</span>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-6  gap-10">
                <!-- Filters Sidebar -->
                <div class="col-span-2 order-2">
                    <div class="bg-white rounded-lg border border-gray-200 p-8 sticky top-32 shadow-sm">
                        <h3 class="text-xl font-bold text-gray-900 mb-8">Filter Options</h3>

                        <!-- Storage Filter -->
                        <div class="mb-8">
                            <label class="block text-base font-semibold text-gray-800 mb-4">Storage</label>
                            <select v-model="filters.storage" class="w-full px-4 py-3 border border-gray-300 rounded-lg text-base focus:ring-2 focus:ring-blue-500 focus:border-transparent hover:border-gray-400 transition-colors">
                                <option value="">All Storage</option>
                                <option v-for="storage in availableStorages" :key="storage" :value="storage">
                                    {{ storage }}
                                </option>
                            </select>
                        </div>

                        <!-- Color Filter -->
                        <div class="mb-8">
                            <label class="block text-base font-semibold text-gray-800 mb-4">Color</label>
                            <select v-model="filters.colour" class="w-full px-4 py-3 border border-gray-300 rounded-lg text-base focus:ring-2 focus:ring-blue-500 focus:border-transparent hover:border-gray-400 transition-colors">
                                <option value="">All Colors</option>
                                <option v-for="color in availableColors" :key="color" :value="color">
                                    {{ formatColorName(color) }}
                                </option>
                            </select>
                        </div>

                        <!-- Grade Filter -->
                        <div class="mb-8">
                            <label class="block text-base font-semibold text-gray-800 mb-4">Grade</label>
                            <select v-model="filters.grade" class="w-full px-4 py-3 border border-gray-300 rounded-lg text-base focus:ring-2 focus:ring-blue-500 focus:border-transparent hover:border-gray-400 transition-colors">
                                <option value="">All Grades</option>
                                <option v-for="grade in availableGrades" :key="grade" :value="grade">
                                    {{ grade }}
                                </option>
                            </select>
                        </div>
                        <div class="mb-8">
                            <label class="block text-base font-semibold text-gray-800 mb-4">Battery</label>
                            <select v-model="filters.battery" class="w-full px-4 py-3 border border-gray-300 rounded-lg text-base focus:ring-2 focus:ring-blue-500 focus:border-transparent hover:border-gray-400 transition-colors">
                                <option value="">All Battery</option>
                                <option v-for="battery in availableBattery" :key="battery" :value="battery">
                                    {{ battery }}%
                                </option>
                            </select>
                        </div>

                        <!-- Issues Filter -->
                        <div class="mb-8">
                            <label class="block text-base font-semibold text-gray-800 mb-4">Issues</label>
                            <select v-model="filters.issues" class="w-full px-4 py-3 border border-gray-300 rounded-lg text-base focus:ring-2 focus:ring-blue-500 focus:border-transparent hover:border-gray-400 transition-colors">
                                <option value="">All Issues</option>
                                <option value="__no_issues__">No Issues</option>
                                <option v-for="issue in availableIssues" :key="issue" :value="issue">
                                    {{ issue }}
                                </option>
                            </select>
                        </div>

                        <!-- Price Summary / Placeholder -->
                        <div v-if="filteredVariants.length > 0" class="mb-8 pb-6 border-b border-gray-200">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Price Range</p>
                            <p class="text-2xl font-light text-gray-900">
                                Starting from {{ getCurrencySymbol(market.currency) }}{{ formatPrice(Math.min(...filteredVariants.map(v => v.selling_price))) }}
                            </p>
                            <p class="text-xs text-gray-500 mt-3">{{ filteredVariants.length }} item(s)</p>
                        </div>
                        <div v-else class="mb-8 pb-6 border-b border-gray-200 text-center">
                            <p class="text-sm text-gray-500">Select specifications to view prices</p>
                        </div>

                        <!-- Reset Filters -->
                        <button @click="resetFilters" class="w-full px-6 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-semibold text-base transition-colors border border-gray-200 hover:border-gray-300">
                            Reset Filters
                        </button>
                    </div>
                </div>

                <!-- Products Grid -->
                <div class="col-span-4 row-span-2 order-1">
                    <!-- Results Info -->
                    <div class="mb-8">
                        <p class="text-base text-gray-600">
                            Showing <span class="font-semibold text-gray-900">{{ filteredVariants.length }}</span> item(s)
                        </p>
                    </div>

                    <!-- Products Grid -->
                    <div v-if="filteredVariants.length > 0" class="space-y-6">
                        <VariantCard
                            v-for="item in filteredVariants"
                            :key="item.id"
                            :item="item"
                            :currency-symbol="getCurrencySymbol(market.currency)"
                            :is-loading="isAddingToCart === item.id"
                            @toggle-grade="toggleGradeDetail"
                            @view-product="viewProductDetail"
                            @add-to-cart="addToCart"
                        />
                    </div>

                    <!-- No Results -->
                    <div v-else class="bg-white rounded-lg border border-gray-200 p-16 text-center shadow-sm">
                        <svg class="w-24 h-24 text-gray-400 mx-auto mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                        </svg>
                        <h3 class="text-2xl font-bold text-gray-900 mb-3">No items match your filters</h3>
                        <p class="text-lg text-gray-600 mb-8">Try adjusting your selection to find what you're looking for</p>
                        <button @click="resetFilters" class="inline-flex items-center px-6 py-3 bg-slate-100 text-gray-700 rounded-lg hover:bg-slate-200 font-semibold text-base transition-colors border border-gray-200 hover:border-gray-300 hover:shadow-md">
                            <i class="pi pi-refresh mr-2"></i> Reset Filters
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useCart } from '@/composables/useCart'
import { getCurrencySymbol } from '@/utils/currency'
import VariantCard from '@/Components/Ecommerce/VariantCard.vue'

// Props
const props = defineProps({
    market: {
        type: Object,
        required: true
    },
    modelData: {
        type: Object,
        required: true
    }
})

const router = useRouter()
const { addItem } = useCart()

// Simplify grade categories
const simplifyGrade = (grade) => {
    if (!grade) return grade
    const gradeUpper = grade.toUpperCase()
    // New
    if (gradeUpper === 'BRAND NEW SEALED') return 'New'
    // Excellent: A-, A, A+
    if (['A-', 'A', 'A+'].includes(gradeUpper)) return 'Excellent'
    // Good: B+
    if (gradeUpper === 'B+') return 'Good'
    // Fair: B, C, D, and others
    return 'Fair'
}

// Local state
const filters = ref({
    storage: '',
    colour: '',
    grade: '',
    battery: '',
    issues: ''
})

const isAddingToCart = ref(null)

// Get all items directly from the items array (unified method)
const allItems = computed(() => {
    // Use items array from new unified method
    if (props.modelData.items && Array.isArray(props.modelData.items)) {
        return props.modelData.items.map(item => ({
            id: item.id,
            model: item.model,
            manufacturer: item.manufacturer || props.modelData.manufacturer,
            type: item.type || props.modelData.type,
            storage: item.storage,
            colour: item.colour,
            grade: simplifyGrade(item.condition), // Use simplified grade
            gradeRaw: item.condition, // Keep original for reference
            battery: item.battery ? Number(item.battery) : null,
            issues: item.issues,
            description: item.description || null,
            selling_price: item.market_price || item.selling_price || 0,
            // Media data from backend
            photo_count: item.photo_count || 0,
            main_photo_thumb: item.main_photo_thumb || null,
            main_photo_url: item.main_photo_url || null,
            photos: item.photos || []
        }))
    }
    
    // Fallback to old variants structure for backwards compatibility
    const items = []
    if (props.modelData.variants) {
        props.modelData.variants.forEach(storageGroup => {
            storageGroup.colours.forEach(colorGroup => {
                colorGroup.grades.forEach(gradeGroup => {
                    gradeGroup.battery_options.forEach(battery => {
                        gradeGroup.issues.forEach(issueItem => {
                            const item = {
                                id: issueItem.id,
                                model: props.modelData.model,
                                manufacturer: props.modelData.manufacturer,
                                type: props.modelData.type,
                                storage: storageGroup.storage,
                                colour: colorGroup.colour,
                                grade: simplifyGrade(gradeGroup.grade),
                                gradeRaw: gradeGroup.grade,
                                battery: Number(battery),
                                issues: issueItem.issues,
                                description: issueItem.description || null,
                                selling_price: issueItem.selling_price || 0,
                                photo_count: issueItem.photo_count || 0,
                                main_photo_thumb: issueItem.main_photo_thumb || null,
                                main_photo_url: issueItem.main_photo_url || null,
                                photos: issueItem.photos || []
                            }
                            items.push(item)
                        })
                    })
                })
            })
        })
    }
    return items
})

// Available filter options - Parallel filtering (non-exclusive)
const availableStorages = computed(() => {
    return [...new Set(allItems.value.map(v => v.storage))].filter(Boolean)
})

const availableColors = computed(() => {
    const filtered = allItems.value.filter(item => {
        if (filters.value.storage && item.storage !== filters.value.storage) return false
        if (filters.value.grade && item.grade !== filters.value.grade) return false
        if (filters.value.battery && item.battery !== parseInt(filters.value.battery)) return false
        if (filters.value.issues && item.issues !== filters.value.issues) return false
        return true
    })
    return [...new Set(filtered.map(v => v.colour))].filter(Boolean)
})

const availableGrades = computed(() => {
    const filtered = allItems.value.filter(item => {
        if (filters.value.storage && item.storage !== filters.value.storage) return false
        if (filters.value.colour && item.colour !== filters.value.colour) return false
        if (filters.value.battery && item.battery !== parseInt(filters.value.battery)) return false
        if (filters.value.issues && item.issues !== filters.value.issues) return false
        return true
    })
    // Return unique simplified grades with proper order
    return [...new Set(filtered.map(v => v.grade))].filter(Boolean).sort((a, b) => {
        const order = { 'New': 0, 'Excellent': 1, 'Good': 2, 'Fair': 3 }
        return (order[a] || 4) - (order[b] || 4)
    })
})

const availableBattery = computed(() => {
    const filtered = allItems.value.filter(item => {
        if (filters.value.storage && item.storage !== filters.value.storage) return false
        if (filters.value.colour && item.colour !== filters.value.colour) return false
        if (filters.value.grade && item.grade !== filters.value.grade) return false
        if (filters.value.issues && item.issues !== filters.value.issues) return false
        return true
    })
    return [...new Set(filtered.map(v => v.battery))].filter(Boolean).sort((a, b) => a - b)
})

const availableIssues = computed(() => {
    const filtered = allItems.value.filter(item => {
        if (filters.value.storage && item.storage !== filters.value.storage) return false
        if (filters.value.colour && item.colour !== filters.value.colour) return false
        if (filters.value.grade && item.grade !== filters.value.grade) return false
        if (filters.value.battery && item.battery !== parseInt(filters.value.battery)) return false
        return true
    })
    // Only return actual issues (non-empty strings)
    return [...new Set(filtered.map(v => v.issues))].filter(issue => issue && issue.trim() !== '')
})

// Filtered variants based on selected filters
const filteredVariants = computed(() => {
    return allItems.value.filter(item => {
        if (filters.value.storage && item.storage !== filters.value.storage) return false
        if (filters.value.colour && item.colour !== filters.value.colour) return false
        if (filters.value.grade && item.grade !== filters.value.grade) return false
        if (filters.value.battery && item.battery !== parseInt(filters.value.battery)) return false
        // Handle issues filter: __no_issues__ means only items without issues
        if (filters.value.issues === '__no_issues__' && item.issues) return false
        if (filters.value.issues && filters.value.issues !== '__no_issues__' && item.issues !== filters.value.issues) return false
        return true
    })
})

const resetFilters = () => {
    filters.value = {
        storage: '',
        colour: '',
        grade: '',
        battery: '',
        issues: ''
    }
}

const formatColorName = (color) => {
    return color ? color.charAt(0).toUpperCase() + color.slice(1).toLowerCase() : ''
}

const formatPrice = (price) => {
    return new Intl.NumberFormat().format(price)
}

const viewProductDetail = (itemId) => {
    router.push({
        name: 'market.product',
        params: { item: itemId }
    })
}

const addToCart = async (item) => {
    isAddingToCart.value = item.id
    try {
        addItem(item)
        // Show brief feedback
        await new Promise(resolve => setTimeout(resolve, 150))
    } catch (error) {
        console.error('Error adding to cart:', error)
    } finally {
        isAddingToCart.value = null
    }
}

</script>

<style scoped>
</style>
