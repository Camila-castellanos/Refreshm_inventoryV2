<template>
    <Dialog
        v-model:visible="isVisible"
        modal
        :closable="true"
        :style="{ width: '90vw', maxWidth: '1400px' }"
        @update:visible="handleClose"
    >
        <Toast />
        <template #header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">{{ model?.model || 'Items' }}</h3>
                    <p class="text-sm text-gray-600 mt-1">
                        {{ model?.manufacturer || 'N/A' }} • {{ totalItems }} items
                    </p>
                </div>
            </div>
        </template>

        <div v-if="items.length > 0" class="space-y-4">
            <!-- Filters -->
            <div class="flex gap-2 flex-wrap">
                <div class="flex-1 min-w-64">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search by IMEI, type, color, price..."
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                </div>
                <select
                    v-model="filterStatus"
                    class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">All statuses</option>
                    <option value="available">Available</option>
                    <option value="sold">Sold</option>
                    <option value="hold">On Hold</option>
                </select>
            </div>

            <!-- Items Table -->
            <div class="overflow-x-auto border border-gray-200 rounded-lg">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-900">IMEI</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-900">Type</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-900">Color</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-900">Condition</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-900">Market Price</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-900">Status</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-900">Photos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="item in filteredItems"
                            :key="item.id"
                            class="border-b border-gray-200 hover:bg-gray-50 transition-colors"
                        >
                            <td class="px-4 py-3 text-gray-900 font-mono text-xs">
                                {{ item.imei || 'N/A' }}
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ item.type || 'N/A' }}</td>
                            <td class="px-4 py-3">
                                <span
                                    v-if="item.colour"
                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800"
                                >
                                    {{ item.colour }}
                                </span>
                                <span v-else class="text-gray-500 text-xs">N/A</span>
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ item.condition || 'N/A' }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-gray-900">
                                <div class="flex items-center justify-end gap-2">
                                    <span class="text-xs text-gray-400" v-if="item.has_custom_price" title="Custom market price"><i class="pi pi-star text-yellow-400"></i></span>
                                    <input
                                        v-model.number="item.market_price"
                                        @blur="updatePrice(item)"
                                        @keyup.enter="updatePrice(item)"
                                        type="number"
                                        :disabled="savingPrices[item.id]"
                                        class="w-24 px-2 py-1 border border-gray-300 rounded text-sm text-right focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100"
                                        placeholder="0.00"
                                    >
                                    <span v-if="savingPrices[item.id]" class="text-xs text-gray-500">
                                        <i class="pi pi-spin pi-spinner"></i>
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    :class="[
                                        'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium',
                                        item.status === 'available'
                                            ? 'bg-green-100 text-green-800'
                                            : item.status === 'sold'
                                                ? 'bg-blue-100 text-blue-800'
                                                : item.status === 'hold'
                                                    ? 'bg-yellow-100 text-yellow-800'
                                                    : 'bg-red-100 text-red-800'
                                    ]"
                                >
                                    {{ getStatusLabel(item.status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <i class="pi pi-images text-gray-400"></i>
                                    <span class="text-gray-600 font-medium">{{ item.photo_count || 0 }}</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Summary -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 bg-gray-50 p-4 rounded-lg">
                <div>
                    <p class="text-xs text-gray-600 uppercase font-semibold">Total Items</p>
                    <p class="text-2xl font-bold text-gray-900">{{ totalItems }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-600 uppercase font-semibold">Available</p>
                    <p class="text-2xl font-bold text-green-600">{{ availableItems }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-600 uppercase font-semibold">Sold</p>
                    <p class="text-2xl font-bold text-blue-600">{{ soldItems }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-600 uppercase font-semibold">On Hold</p>
                    <p class="text-2xl font-bold text-yellow-600">{{ onHoldItems }}</p>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-12">
            <div class="w-16 h-16 mx-auto mb-4 rounded-lg flex items-center justify-center bg-gray-100">
                <i class="pi pi-inbox text-2xl text-gray-500"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No items found</h3>
            <p class="text-gray-600">There are no items for this model.</p>
        </div>

        <template #footer>
            <div class="flex justify-end">
                <Button
                    @click="handleClose"
                    label="Close"
                    severity="secondary"
                    outlined
                />
            </div>
        </template>
    </Dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import Toast from 'primevue/toast'
import axios from 'axios'

const props = defineProps({
    visible: {
        type: Boolean,
        default: false,
    },
    model: {
        type: Object,
        default: null,
    },
    items: {
        type: Array,
        default: () => [],
    },
    market: {
        type: Object,
        default: null,
    },
})

const emit = defineEmits(['update:visible', 'view-item', 'edit-item'])

const isVisible = ref(props.visible)
const searchQuery = ref('')
const filterStatus = ref('')
const toast = useToast()
const savingPrices = ref({})  // Track which items are saving

watch(() => props.visible, (newVal) => {
    isVisible.value = newVal
})

watch(isVisible, (newVal) => {
    if (!newVal) {
        emit('update:visible', false)
    }
})

const filteredItems = computed(() => {
    return props.items.filter(item => {
        const matchSearch =
            !searchQuery.value ||
            (item.imei && item.imei.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
            (item.type && item.type.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
            (item.colour && item.colour.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
            (item.market_price && item.market_price.toString().includes(searchQuery.value))

        const matchStatus = !filterStatus.value || item.status === filterStatus.value

        return matchSearch && matchStatus
    })
})

const totalItems = computed(() => props.items.length)

const availableItems = computed(() => {
    return props.items.filter(item => item.status === 'available').length
})

const soldItems = computed(() => {
    return props.items.filter(item => item.status === 'sold').length
})

const onHoldItems = computed(() => {
    return props.items.filter(item => item.status === 'hold').length
})

const getStatusLabel = (status) => {
    const labels = {
        available: 'Available',
        sold: 'Sold',
        hold: 'On Hold',
        damaged: 'Damaged',
    }
    return labels[status] || status
}

const handleClose = () => {
    isVisible.value = false
}

const updatePrice = async (item) => {
    if (!item || !item.id || !props.market || !props.market.id) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Missing required data for price update',
            life: 3000
        })
        return
    }
    
    savingPrices.value[item.id] = true
    
    try {
        await axios.post(
            route('ecommerce.items.update-price', {
                market: props.market.id,
                item: item.id
            }),
            { price: item.market_price }
        )
        
        toast.add({
            severity: 'success',
            summary: 'Price Updated',
            detail: `Price updated successfully`,
            life: 3000
        })
        
        item.has_custom_price = true
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.response?.data?.message || 'Failed to update price',
            life: 3000
        })
        console.error('Price update error:', error)
    } finally {
        savingPrices.value[item.id] = false
    }
}
</script>

<style scoped>
table {
    border-collapse: collapse;
}
</style>
