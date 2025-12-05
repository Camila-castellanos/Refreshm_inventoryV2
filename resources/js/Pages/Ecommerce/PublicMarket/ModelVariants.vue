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
                        <div v-for="item in filteredVariants" :key="item.id" class="bg-white rounded-lg border border-gray-200 overflow-hidden hover:shadow-lg transition-all duration-200 hover:border-gray-300">
                            <div class="flex flex-row">
                                <!-- Product Image and Name (Left) - 30% width -->
                                <div class="flex-none w-1/3 flex flex-col items-center justify-center p-8 bg-gray-50 border-r border-gray-200">
                                    <!-- Product Image -->
                                    <div class="w-32 h-32 bg-gray-100 flex items-center justify-center rounded-lg mb-6 flex-shrink-0 overflow-hidden">
                                        <img v-if="item.main_photo_thumb" 
                                             :src="item.main_photo_thumb" 
                                             :alt="item.model"
                                             class="w-full h-full object-cover"
                                        />
                                        <svg v-else class="w-24 h-24 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <!-- Product Name -->
                                    <h4 class="font-semibold text-lg text-gray-900 text-center line-clamp-2">{{ item.model }}</h4>
                                </div>

                                <!-- Product Details (Right) - 70% width -->
                                <div class="flex-1 w-2/3 p-8 flex flex-col justify-between">
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
                                                <span class="font-semibold text-gray-900">{{ formatColorName(item.colour) }}</span>
                                            </div>
                                            <div v-if="item.grade" class="flex justify-between items-center pb-3 border-b border-gray-100 group">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-gray-600 font-medium">Grade:</span>
                                                    <span class="font-semibold text-gray-900">{{ item.grade }}</span>
                                                </div>
                                                <button @click="toggleGradeDetail(item.id)" class="text-xs px-2 py-1 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition-colors">
                                                    <i class="pi pi-info-circle text-xs"></i>
                                                </button>
                                            </div>
                                            <!-- Grade Detail (Hidden by default) -->
                                            <div v-if="expandedGrades[item.id]" class="pb-3 border-b border-blue-100 bg-blue-50 px-3 py-2 rounded">
                                                <p class="text-xs text-blue-600">Specific condition: <span class="font-semibold">{{ item.gradeRaw }}</span></p>
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

                                        <!-- Price -->
                                        <div class="mb-8 pt-4 border-t-2 border-gray-200">
                                            <p class="text-sm text-gray-600 mb-2">Price</p>
                                            <p class="text-4xl font-bold text-gray-900">
                                                {{ getCurrencySymbol(market.currency) }}{{ formatPrice(item.selling_price) }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex gap-3 pt-4 border-t border-gray-200">
                                        <button @click="viewProductDetail(item.id)" 
                                                class="flex-1 px-6 py-3 bg-slate-100 text-gray-700 rounded-lg hover:bg-slate-200 font-semibold text-base transition-colors border border-gray-200 hover:border-gray-300 hover:shadow-md">
                                            <i class="pi pi-eye text-sm mr-2"></i> View
                                        </button>
                                        <button @click="addToCart(item)" 
                                                :disabled="isAddingToCart === item.id"
                                                class="flex-1 px-6 py-3 bg-slate-100 text-gray-700 rounded-lg hover:bg-slate-200 font-semibold text-base transition-colors border border-gray-200 hover:border-gray-300 disabled:opacity-50 disabled:cursor-not-allowed hover:shadow-md">
                                            <i v-if="isAddingToCart === item.id" class="pi pi-spin pi-spinner text-sm mr-2"></i>
                                            <i v-else class="pi pi-shopping-cart text-sm mr-2"></i>
                                            Add
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
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

// Track expanded grade details
const expandedGrades = ref({})

// Get all items from variants structure (now nested with storage)
const allItems = computed(() => {
    const items = []
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
                            grade: simplifyGrade(gradeGroup.grade), // Use simplified grade
                            gradeRaw: gradeGroup.grade, // Keep original for reference
                            battery: Number(battery), // Ensure battery is always a number
                            issues: issueItem.issues,
                            selling_price: issueItem.selling_price || 0,
                            // Media data from backend
                            photo_count: issueItem.photo_count || 0,
                            main_photo_thumb: issueItem.main_photo_thumb || null,
                            main_photo_url: issueItem.main_photo_url || null,
                            photos: issueItem.photos || []
                        }
                        // Debug: log items con imágenes
                        if (item.main_photo_thumb) {
                            console.log('Item con imagen:', item.id, item.main_photo_thumb)
                        }
                        items.push(item)
                    })
                })
            })
        })
    })
    console.log('Total items:', items.length, 'Con imágenes:', items.filter(i => i.main_photo_thumb).length)
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

// Helper methods
const formatColorName = (color) => {
    return color ? color.charAt(0).toUpperCase() + color.slice(1).toLowerCase() : ''
}

const formatGradeName = (grade) => {
    const gradeMap = {
        'New': 'New (Brand New Sealed)',
        'Excellent': 'Excellent (A-, A, A+)',
        'Good': 'Good (B+)',
        'Fair': 'Fair (B, C, D...)'
    }
    return gradeMap[grade] || grade
}

const formatPrice = (price) => {
    return new Intl.NumberFormat().format(price)
}

const resetFilters = () => {
    filters.value = {
        storage: '',
        colour: '',
        grade: '',
        battery: '',
        issues: ''
    }
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

const toggleGradeDetail = (itemId) => {
    expandedGrades.value[itemId] = !expandedGrades.value[itemId]
}

</script>

<style scoped>
</style>
