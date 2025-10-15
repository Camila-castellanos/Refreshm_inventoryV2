<template>
    <AppLayout title="Markets">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <!-- Header with Create Button -->
                    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Your Markets</h3>
                            <p class="text-sm text-gray-600">Manage your online marketplaces</p>
                        </div>
                        <Link
                            :href="route('ecommerce.markets.create')"
                            class=""
                        >
                            <Button 
                                label="Create Market" 
                                icon="pi pi-plus" 
                                severity="primary"
                            />
                        </Link>
                    </div>

                    <!-- Markets Table -->
                    <div v-if="markets.data && markets.data.length > 0" class="overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Market
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Shop
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Products
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Created
                                    </th>
                                    <th scope="col" class="relative px-6 py-3">
                                        <span class="sr-only">Actions</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="market in markets.data" :key="market.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <div class="h-10 w-10 rounded-lg bg-slate-100 flex items-center justify-center">
                                                    <i class="pi pi-shopping-bag text-lg text-gray-500"></i>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ market.name }}
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    /market/{{ market.slug }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ market.shop?.name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            :class="{
                                                'text-zinc-900': market.is_active,
                                                'text-zinc-600': !market.is_active
                                            }"
                                            class="inline-flex px-3 py-1 text-xs font-medium rounded-md bg-slate-100"
                                        >
                                            {{ market.is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ market.published_items_count || 0 }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ formatDate(market.created_at) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end space-x-1">
                                            <!-- Visit Market -->
                                            <a
                                                :href="`/market/${market.slug}`"
                                                target="_blank"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-md text-gray-500 hover:text-blue-500 transition-colors duration-200 bg-slate-100"
                                                v-tooltip.top="'Visit Market'"
                                            >
                                                <i class="pi pi-external-link text-sm"></i>
                                            </a>
                                            
                                            <!-- Manage Items -->
                                            <Link
                                                :href="route('ecommerce.items.index', market.id)"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-md text-gray-500 hover:text-purple-500 transition-colors duration-200 bg-slate-100"
                                                v-tooltip.top="'Manage Items'"
                                            >
                                                <i class="pi pi-box text-sm"></i>
                                            </Link>

                                            <!-- Edit Market -->
                                            <Link
                                                :href="route('ecommerce.markets.edit', market.id)"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-md text-gray-500 hover:text-gray-700 transition-colors duration-200 bg-slate-100"
                                                v-tooltip.top="'Edit Market'"
                                            >
                                                <i class="pi pi-pencil text-sm"></i>
                                            </Link>

                                            <!-- Delete Market -->
                                            <button
                                                @click="deleteMarket(market)"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-md text-gray-500 hover:text-red-500 transition-colors duration-200 bg-slate-100"
                                                v-tooltip.top="'Delete Market'"
                                            >
                                                <i class="pi pi-trash text-sm"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="text-center py-12">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-lg flex items-center justify-center bg-slate-100">
                            <i class="pi pi-shopping-bag text-2xl text-gray-500"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No markets yet</h3>
                        <p class="text-gray-600 mb-6">Create your first market to start selling online.</p>
                        <Link
                            :href="route('ecommerce.markets.create')"
                            class=""
                        >
                            <Button
                                label="Create Market"
                                icon="pi pi-plus"
                                severity="primary"
                            />
                        </Link>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="markets.links && markets.links.length > 3" class="mt-6">
                    <nav class="flex items-center justify-between">
                        <div class="flex-1 flex justify-between sm:hidden">
                            <Link
                                v-if="markets.prev_page_url"
                                :href="markets.prev_page_url"
                                class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                            >
                                Previous
                            </Link>
                            <Link
                                v-if="markets.next_page_url"
                                :href="markets.next_page_url"
                                class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                            >
                                Next
                            </Link>
                        </div>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <Dialog
            v-model:visible="confirmingMarketDeletion"
            modal
            :closable="true"
            :style="{ width: '500px' }"
            :header="`Delete Market`"
        >
            <div class="space-y-4">
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                            <i class="pi pi-exclamation-triangle text-red-600"></i>
                        </div>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm text-gray-700">
                            Are you sure you want to delete the market <span class="font-semibold">"{{ marketBeingDeleted?.name }}"</span>?
                        </p>
                        <p class="text-sm text-gray-600 mt-2">
                            This action cannot be undone and will make the market inaccessible to customers. All associated data will be permanently removed.
                        </p>
                    </div>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end space-x-2">
                    <Button 
                        @click="closeModal" 
                        label="Cancel" 
                        severity="secondary"
                        outlined
                        :disabled="deleteForm.processing"
                    />

                    <Button
                        label="Delete Market"
                        icon="pi pi-trash"
                        severity="danger"
                        :loading="deleteForm.processing"
                        @click="confirmDelete"
                    />
                </div>
            </template>
        </Dialog>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import { Link } from '@inertiajs/vue3'

// Props
const props = defineProps({
    markets: Object,
})

// State
const confirmingMarketDeletion = ref(false)
const marketBeingDeleted = ref(null)

// Form
const deleteForm = useForm({})

// Methods
const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString()
}

const deleteMarket = (market) => {
    marketBeingDeleted.value = market
    confirmingMarketDeletion.value = true
}

const confirmDelete = () => {
    deleteForm.delete(route('ecommerce.markets.destroy', marketBeingDeleted.value.id), {
        onSuccess: () => closeModal(),
        onFinish: () => deleteForm.reset()
    })
}

const closeModal = () => {
    confirmingMarketDeletion.value = false
    marketBeingDeleted.value = null
}
</script>