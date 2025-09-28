<template>
    <AppLayout title="Create Market">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Create New Market
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <form @submit.prevent="createMarket" class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Basic Information -->
                            <div class="col-span-2">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                            </div>

                            <!-- Market Name -->
                            <div>
                                <InputLabel for="name" value="Market Name" />
                                <TextInput
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    required
                                    autofocus
                                />
                                <InputError class="mt-2" :message="form.errors.name" />
                            </div>

                            <!-- Shop Selection -->
                            <div>
                                <InputLabel for="shop_id" value="Associated Shop" />
                                <select
                                    id="shop_id"
                                    v-model="form.shop_id"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required
                                >
                                    <option value="">Select a shop</option>
                                    <option v-for="shop in shops" :key="shop.id" :value="shop.id">
                                        {{ shop.name }}
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.shop_id" />
                            </div>

                            <!-- Description -->
                            <div class="col-span-2">
                                <InputLabel for="description" value="Description" />
                                <textarea
                                    id="description"
                                    v-model="form.description"
                                    rows="3"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Brief description of your market..."
                                ></textarea>
                                <InputError class="mt-2" :message="form.errors.description" />
                            </div>

                            <!-- Tagline -->
                            <div>
                                <InputLabel for="tagline" value="Tagline" />
                                <TextInput
                                    id="tagline"
                                    v-model="form.tagline"
                                    type="text"
                                    class="mt-1 block w-full"
                                    placeholder="e.g., Quality devices at great prices"
                                />
                                <InputError class="mt-2" :message="form.errors.tagline" />
                            </div>

                            <!-- Currency -->
                            <div>
                                <InputLabel for="currency" value="Currency" />
                                <select
                                    id="currency"
                                    v-model="form.currency"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="USD">USD - US Dollar</option>
                                    <option value="EUR">EUR - Euro</option>
                                    <option value="GBP">GBP - British Pound</option>
                                    <option value="CAD">CAD - Canadian Dollar</option>
                                    <option value="AUD">AUD - Australian Dollar</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.currency" />
                            </div>

                            <!-- Settings -->
                            <div class="col-span-2 border-t pt-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Settings</h3>
                            </div>

                            <!-- Show Inventory Count -->
                            <div class="flex items-center">
                                <input
                                    id="show_inventory_count"
                                    v-model="form.show_inventory_count"
                                    type="checkbox"
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                />
                                <label for="show_inventory_count" class="ml-2 block text-sm text-gray-900">
                                    Show inventory count to customers
                                </label>
                            </div>

                            <!-- Active Status -->
                            <div class="flex items-center">
                                <input
                                    id="is_active"
                                    v-model="form.is_active"
                                    type="checkbox"
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                />
                                <label for="is_active" class="ml-2 block text-sm text-gray-900">
                                    Market is active and publicly accessible
                                </label>
                            </div>

                            <!-- Contact Information -->
                            <div class="col-span-2 border-t pt-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Contact Information</h3>
                            </div>

                            <!-- Contact Email -->
                            <div>
                                <InputLabel for="contact_email" value="Contact Email" />
                                <TextInput
                                    id="contact_email"
                                    v-model="form.contact_email"
                                    type="email"
                                    class="mt-1 block w-full"
                                    placeholder="contact@yourmarket.com"
                                />
                                <InputError class="mt-2" :message="form.errors.contact_email" />
                            </div>

                            <!-- Contact Phone -->
                            <div>
                                <InputLabel for="contact_phone" value="Contact Phone" />
                                <TextInput
                                    id="contact_phone"
                                    v-model="form.contact_phone"
                                    type="tel"
                                    class="mt-1 block w-full"
                                    placeholder="+1 (555) 123-4567"
                                />
                                <InputError class="mt-2" :message="form.errors.contact_phone" />
                            </div>

                            <!-- Address -->
                            <div class="col-span-2">
                                <InputLabel for="address" value="Business Address" />
                                <textarea
                                    id="address"
                                    v-model="form.address"
                                    rows="2"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="123 Main Street, City, State 12345"
                                ></textarea>
                                <InputError class="mt-2" :message="form.errors.address" />
                            </div>

                            <!-- SEO Settings -->
                            <div class="col-span-2 border-t pt-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">SEO Settings</h3>
                            </div>

                            <!-- Meta Title -->
                            <div>
                                <InputLabel for="meta_title" value="Meta Title" />
                                <TextInput
                                    id="meta_title"
                                    v-model="form.meta_title"
                                    type="text"
                                    class="mt-1 block w-full"
                                    placeholder="Leave empty to auto-generate"
                                />
                                <InputError class="mt-2" :message="form.errors.meta_title" />
                            </div>

                            <!-- Meta Description -->
                            <div>
                                <InputLabel for="meta_description" value="Meta Description" />
                                <textarea
                                    id="meta_description"
                                    v-model="form.meta_description"
                                    rows="2"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Leave empty to auto-generate"
                                ></textarea>
                                <InputError class="mt-2" :message="form.errors.meta_description" />
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex items-center justify-end mt-8 pt-6 border-t">
                            <Link 
                                :href="route('ecommerce.markets.index')" 
                                class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            >
                                Cancel
                            </Link>

                            <PrimaryButton
                                class="ml-3"
                                :class="{ 'opacity-25': form.processing }"
                                :disabled="form.processing"
                            >
                                <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ form.processing ? 'Creating...' : 'Create Market' }}
                            </PrimaryButton>
                        </div>

                        <!-- Preview URL -->
                        <div v-if="form.name && marketSlug" class="mt-4 p-4 bg-gray-50 rounded-lg">
                            <p class="text-sm text-gray-600">
                                <span class="font-medium">Market URL:</span>
                                <span class="ml-2 text-blue-600 font-mono">
                                    {{ appUrl }}/market/{{ marketSlug }}
                                </span>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import TextInput from '@/Components/TextInput.vue'
import { Link } from '@inertiajs/vue3'

// Props
const props = defineProps({
    shops: Array,
    appUrl: String,
})

// Form
const form = useForm({
    name: '',
    shop_id: '',
    description: '',
    tagline: '',
    currency: 'USD',
    show_inventory_count: false,
    is_active: true,
    contact_email: '',
    contact_phone: '',
    address: '',
    meta_title: '',
    meta_description: '',
})

// Computed
const marketSlug = computed(() => {
    if (!form.name) return ''
    return form.name
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)/g, '')
})

// Methods
const createMarket = () => {
    form.post(route('ecommerce.markets.store'), {
        onSuccess: () => {
            // Handle success
        },
        onError: () => {
            // Handle error
        }
    })
}
</script>