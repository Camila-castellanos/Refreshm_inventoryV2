<template>
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden hover:shadow-lg transition-all duration-200 hover:border-gray-300">
        <div class="flex flex-col lg:flex-row">
            <!-- Product Image (Top on mobile, Left on desktop) - Larger -->
            <div class="flex-none w-full lg:w-2/5 flex flex-col items-center justify-center p-8 bg-white border-b lg:border-b-0 lg:border-r border-gray-200">
                <!-- Product Image -->
                <div class="w-64 h-64 bg-white flex items-center justify-center rounded-lg mb-6 flex-shrink-0 overflow-hidden border border-gray-100">
                    <img v-if="item.main_photo_thumb" 
                         :src="item.main_photo_thumb" 
                         :alt="item.model"
                         class="w-full h-full object-contain"
                    />
                    <svg v-else class="w-32 h-32 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                </div>
                <!-- Product Name -->
                <h4 class="font-semibold text-lg text-gray-900 text-center line-clamp-2">{{ item.model }}</h4>
            </div>

            <!-- Product Details (Bottom on mobile, Right on desktop) -->
            <div class="flex-1 w-full lg:w-3/5 p-8 flex flex-col justify-between">
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
                            <button @click="emit('toggle-grade', item.id)" class="text-xs px-2 py-1 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition-colors">
                                <i class="pi pi-info-circle text-xs"></i>
                            </button>
                        </div>
                        <!-- Grade Detail (Hidden by default) -->
                        <div v-if="showGradeDetail" class="pb-3 border-b border-blue-100 bg-blue-50 px-3 py-2 rounded">
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

                    <!-- Description -->
                    <div v-if="item.description" class="mb-6">
                        <p class="text-sm text-gray-600 italic">{{ item.description }}</p>
                    </div>

                    <!-- Price -->
                    <div class="mb-8 pt-4 border-t-2 border-gray-200">
                        <p class="text-sm text-gray-600 mb-2">Price</p>
                        <p class="text-4xl font-bold text-gray-900">
                            {{ currencySymbol }}{{ formatPrice(item.selling_price) }}
                        </p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex gap-3 pt-4 border-t border-gray-200">
                    <button @click="emit('view-product', item.id)" 
                            class="flex-1 px-6 py-3 bg-slate-100 text-gray-700 rounded-lg hover:bg-slate-200 font-semibold text-base transition-colors border border-gray-200 hover:border-gray-300 hover:shadow-md">
                        <i class="pi pi-eye text-sm mr-2"></i> View
                    </button>
                    <button @click="emit('add-to-cart', item)" 
                            :disabled="isLoading"
                            class="flex-1 px-6 py-3 bg-slate-100 text-gray-700 rounded-lg hover:bg-slate-200 font-semibold text-base transition-colors border border-gray-200 hover:border-gray-300 disabled:opacity-50 disabled:cursor-not-allowed hover:shadow-md">
                        <i v-if="isLoading" class="pi pi-spin pi-spinner text-sm mr-2"></i>
                        <i v-else class="pi pi-shopping-cart text-sm mr-2"></i>
                        Add
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
    item: {
        type: Object,
        required: true
    },
    currencySymbol: {
        type: String,
        required: true
    },
    isLoading: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['toggle-grade', 'view-product', 'add-to-cart'])

const showGradeDetail = ref(false)

const formatColorName = (color) => {
    return color ? color.charAt(0).toUpperCase() + color.slice(1).toLowerCase() : ''
}

const formatPrice = (price) => {
    return new Intl.NumberFormat().format(price)
}
</script>

<style scoped>
</style>
