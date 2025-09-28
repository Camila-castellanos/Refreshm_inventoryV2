<template>
    <AppLayout title="Edit Market">
        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 shadow-xl rounded-lg">
                    <!-- Header -->
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                            Edit Market: {{ form.name }}
                        </h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Current URL: <a :href="marketUrl" target="_blank" class="text-blue-600 hover:underline">{{ marketUrl }}</a>
                        </p>
                    </div>

                    <!-- Form -->
                    <form @submit.prevent="submitForm" class="p-6">
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <!-- Market Name -->
                            <div class="sm:col-span-2">
                                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Market Name *
                                </label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    id="name"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                                    :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': form.errors.name }"
                                    required
                                />
                                <div v-if="form.errors.name" class="mt-2 text-sm text-red-600">
                                    {{ form.errors.name }}
                                </div>
                                <div v-if="form.name" class="mt-1 text-xs text-gray-500">
                                    URL Slug: {{ generatedSlug }}
                                </div>
                            </div>

                            <!-- Shop Selection -->
                            <div class="sm:col-span-2">
                                <label for="shop_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Shop *
                                </label>
                                <select
                                    v-model="form.shop_id"
                                    id="shop_id"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': form.errors.shop_id }"
                                    required
                                >
                                    <option value="" disabled>Select a Shop</option>
                                    <option v-for="shop in shops" :key="shop.id" :value="shop.id">
                                        {{ shop.name }}
                                    </option>
                                </select>
                                <div v-if="form.errors.shop_id" class="mt-2 text-sm text-red-600">
                                    {{ form.errors.shop_id }}
                                </div>
                            </div>

                            <!-- Tagline -->
                            <div class="sm:col-span-2">
                                <label for="tagline" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Tagline
                                </label>
                                <input
                                    v-model="form.tagline"
                                    type="text"
                                    id="tagline"
                                    placeholder="e.g., Premium Quality Products"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                                    :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': form.errors.tagline }"
                                />
                                <div v-if="form.errors.tagline" class="mt-2 text-sm text-red-600">
                                    {{ form.errors.tagline }}
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="sm:col-span-2">
                                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Description
                                </label>
                                <textarea
                                    v-model="form.description"
                                    id="description"
                                    rows="3"
                                    placeholder="Describe your market and what customers can expect..."
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                                    :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': form.errors.description }"
                                ></textarea>
                                <div v-if="form.errors.description" class="mt-2 text-sm text-red-600">
                                    {{ form.errors.description }}
                                </div>
                            </div>

                            <!-- Currency -->
                            <div>
                                <label for="currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Currency *
                                </label>
                                <select
                                    v-model="form.currency"
                                    id="currency"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': form.errors.currency }"
                                    required
                                >
                                    <option value="" disabled>Select Currency</option>
                                    <option value="USD">USD - US Dollar</option>
                                    <option value="EUR">EUR - Euro</option>
                                    <option value="GBP">GBP - British Pound</option>
                                    <option value="CAD">CAD - Canadian Dollar</option>
                                    <option value="AUD">AUD - Australian Dollar</option>
                                </select>
                                <div v-if="form.errors.currency" class="mt-2 text-sm text-red-600">
                                    {{ form.errors.currency }}
                                </div>
                            </div>

                            <!-- Settings -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Settings
                                </label>
                                <div class="space-y-2">
                                    <label class="flex items-center">
                                        <input
                                            v-model="form.show_inventory_count"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                        />
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Show inventory count</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input
                                            v-model="form.is_active"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                        />
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Market is active</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Contact Information -->
                        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Contact Information</h3>
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div>
                                    <label for="contact_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Contact Email
                                    </label>
                                    <input
                                        v-model="form.contact_email"
                                        type="email"
                                        id="contact_email"
                                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                                        :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': form.errors.contact_email }"
                                    />
                                    <div v-if="form.errors.contact_email" class="mt-2 text-sm text-red-600">
                                        {{ form.errors.contact_email }}
                                    </div>
                                </div>

                                <div>
                                    <label for="contact_phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Contact Phone
                                    </label>
                                    <input
                                        v-model="form.contact_phone"
                                        type="text"
                                        id="contact_phone"
                                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                                        :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': form.errors.contact_phone }"
                                    />
                                    <div v-if="form.errors.contact_phone" class="mt-2 text-sm text-red-600">
                                        {{ form.errors.contact_phone }}
                                    </div>
                                </div>

                                <div class="sm:col-span-2">
                                    <label for="address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Address
                                    </label>
                                    <textarea
                                        v-model="form.address"
                                        id="address"
                                        rows="2"
                                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                                        :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': form.errors.address }"
                                    ></textarea>
                                    <div v-if="form.errors.address" class="mt-2 text-sm text-red-600">
                                        {{ form.errors.address }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SEO Settings -->
                        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">SEO Settings</h3>
                            <div class="grid grid-cols-1 gap-6">
                                <div>
                                    <label for="meta_title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Meta Title
                                    </label>
                                    <input
                                        v-model="form.meta_title"
                                        type="text"
                                        id="meta_title"
                                        :placeholder="form.name ? `${form.name} - Online Market` : 'Your Market - Online Market'"
                                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                                        :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': form.errors.meta_title }"
                                    />
                                    <div v-if="form.errors.meta_title" class="mt-2 text-sm text-red-600">
                                        {{ form.errors.meta_title }}
                                    </div>
                                </div>

                                <div>
                                    <label for="meta_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Meta Description
                                    </label>
                                    <textarea
                                        v-model="form.meta_description"
                                        id="meta_description"
                                        rows="2"
                                        :placeholder="form.name ? `Browse and shop ${form.name} collection of quality products.` : 'Browse and shop our collection of quality products.'"
                                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                                        :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': form.errors.meta_description }"
                                    ></textarea>
                                    <div v-if="form.errors.meta_description" class="mt-2 text-sm text-red-600">
                                        {{ form.errors.meta_description }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                            <Link
                                :href="route('ecommerce.admin.markets.index')"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-600"
                            >
                                Cancel
                            </Link>
                            
                            <div class="flex space-x-3">
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ form.processing ? 'Updating...' : 'Update Market' }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    market: Object,
    shops: Array,
    appUrl: String
});

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
    meta_description: ''
});

// Load market data into form
onMounted(() => {
    if (props.market) {
        form.name = props.market.name || '';
        form.shop_id = props.market.shop_id || '';
        form.description = props.market.description || '';
        form.tagline = props.market.tagline || '';
        form.currency = props.market.currency || 'USD';
        form.show_inventory_count = props.market.show_inventory_count || false;
        form.is_active = props.market.is_active || true;
        form.contact_email = props.market.contact_email || '';
        form.contact_phone = props.market.contact_phone || '';
        form.address = props.market.address || '';
        form.meta_title = props.market.meta_title || '';
        form.meta_description = props.market.meta_description || '';
    }
});

const generatedSlug = computed(() => {
    if (!form.name) return '';
    return form.name
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)/g, '');
});

const marketUrl = computed(() => {
    if (!props.market || !props.appUrl) return '';
    return `${props.appUrl}/market/${props.market.slug}`;
});

const submitForm = () => {
    form.put(route('ecommerce.admin.markets.update', props.market.id), {
        preserveScroll: true,
        onSuccess: () => {
            // Redirect will be handled by the controller
        }
    });
};
</script>