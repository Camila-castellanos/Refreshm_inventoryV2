<template>
    <AppLayout title="Edit Market">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Edit Market: {{ form.name }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <form @submit.prevent="submitForm" class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Basic Information -->
                            <div class="col-span-2">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                            </div>

                            <!-- Market Name -->
                            <div class="col-span-2">
                                <label for="name" class="block text-sm font-medium text-gray-900 mb-2">
                                    Market Name *
                                </label>
                                <InputText
                                    id="name"
                                    v-model="form.name"
                                    placeholder="Enter market name"
                                    :class="{ 'p-invalid': form.errors.name }"
                                    class="w-full"
                                />
                                <small v-if="form.errors.name" class="p-error">{{ form.errors.name }}</small>
                                <small v-if="generatedSlug" class="text-gray-500 mt-1">URL Slug: {{ generatedSlug }}</small>
                            </div>

                            <!-- Shop Selection -->
                            <div class="col-span-2">
                                <label for="shop_id" class="block text-sm font-medium text-gray-900 mb-2">
                                    Associated Shop *
                                </label>
                                <Dropdown
                                    id="shop_id"
                                    v-model="form.shop_id"
                                    :options="shops"
                                    optionLabel="name"
                                    optionValue="id"
                                    placeholder="Select a shop"
                                    :class="{ 'p-invalid': form.errors.shop_id }"
                                    class="w-full"
                                />
                                <small v-if="form.errors.shop_id" class="p-error">{{ form.errors.shop_id }}</small>
                            </div>

                            <!-- Description -->
                            <div class="col-span-2">
                                <label for="description" class="block text-sm font-medium text-gray-900 mb-2">
                                    Description
                                </label>
                                <Textarea
                                    id="description"
                                    v-model="form.description"
                                    placeholder="Describe your market..."
                                    :class="{ 'p-invalid': form.errors.description }"
                                    class="w-full"
                                    rows="3"
                                />
                                <small v-if="form.errors.description" class="p-error">{{ form.errors.description }}</small>
                            </div>

                            <!-- Tagline -->
                            <div>
                                <label for="tagline" class="block text-sm font-medium text-gray-900 mb-2">
                                    Tagline
                                </label>
                                <InputText
                                    id="tagline"
                                    v-model="form.tagline"
                                    placeholder="e.g., Premium Quality Products"
                                    :class="{ 'p-invalid': form.errors.tagline }"
                                    class="w-full"
                                />
                                <small v-if="form.errors.tagline" class="p-error">{{ form.errors.tagline }}</small>
                            </div>

                            <!-- Currency -->
                            <div>
                                <label for="currency" class="block text-sm font-medium text-gray-900 mb-2">
                                    Currency *
                                </label>
                                <Dropdown
                                    id="currency"
                                    v-model="form.currency"
                                    :options="currencyOptions"
                                    optionLabel="label"
                                    optionValue="value"
                                    placeholder="Select currency"
                                    :class="{ 'p-invalid': form.errors.currency }"
                                    class="w-full"
                                />
                                <small v-if="form.errors.currency" class="p-error">{{ form.errors.currency }}</small>
                            </div>

                            <!-- Settings -->
                            <div class="col-span-2 border-t pt-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Settings</h3>
                            </div>

                            <!-- Show Inventory Count -->
                            <div class="flex items-center gap-2">
                                <Checkbox
                                    id="show_inventory_count"
                                    v-model="form.show_inventory_count"
                                    :binary="true"
                                />
                                <label for="show_inventory_count" class="text-sm text-gray-900">
                                    Show inventory count to customers
                                </label>
                            </div>

                            <!-- Active Status -->
                            <div class="flex items-center gap-2">
                                <Checkbox
                                    id="is_active"
                                    v-model="form.is_active"
                                    :binary="true"
                                />
                                <label for="is_active" class="text-sm text-gray-900">
                                    Market is active and publicly accessible
                                </label>
                            </div>

                            <!-- Contact Information -->
                            <div class="col-span-2 border-t pt-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Contact Information</h3>
                            </div>

                            <!-- Contact Email -->
                            <div>
                                <label for="contact_email" class="block text-sm font-medium text-gray-900 mb-2">
                                    Contact Email
                                </label>
                                <InputText
                                    id="contact_email"
                                    v-model="form.contact_email"
                                    type="email"
                                    placeholder="contact@yourmarket.com"
                                    :class="{ 'p-invalid': form.errors.contact_email }"
                                    class="w-full"
                                />
                                <small v-if="form.errors.contact_email" class="p-error">{{ form.errors.contact_email }}</small>
                            </div>

                            <!-- Contact Phone -->
                            <div>
                                <label for="contact_phone" class="block text-sm font-medium text-gray-900 mb-2">
                                    Contact Phone
                                </label>
                                <InputText
                                    id="contact_phone"
                                    v-model="form.contact_phone"
                                    type="tel"
                                    placeholder="+1 (555) 123-4567"
                                    :class="{ 'p-invalid': form.errors.contact_phone }"
                                    class="w-full"
                                />
                                <small v-if="form.errors.contact_phone" class="p-error">{{ form.errors.contact_phone }}</small>
                            </div>

                            <!-- Address -->
                            <div class="col-span-2">
                                <label for="address" class="block text-sm font-medium text-gray-900 mb-2">
                                    Business Address
                                </label>
                                <Textarea
                                    id="address"
                                    v-model="form.address"
                                    rows="2"
                                    placeholder="123 Main Street, City, State 12345"
                                    :class="{ 'p-invalid': form.errors.address }"
                                    class="w-full"
                                />
                                <small v-if="form.errors.address" class="p-error">{{ form.errors.address }}</small>
                            </div>

                            <!-- SEO Settings -->
                            <div class="col-span-2 border-t pt-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">SEO Settings</h3>
                            </div>

                            <!-- Meta Title -->
                            <div>
                                <label for="meta_title" class="block text-sm font-medium text-gray-900 mb-2">
                                    Meta Title
                                </label>
                                <InputText
                                    id="meta_title"
                                    v-model="form.meta_title"
                                    :placeholder="form.name ? `${form.name} - Online Market` : 'Leave empty to auto-generate'"
                                    :class="{ 'p-invalid': form.errors.meta_title }"
                                    class="w-full"
                                />
                                <small v-if="form.errors.meta_title" class="p-error">{{ form.errors.meta_title }}</small>
                            </div>

                            <!-- Meta Description -->
                            <div>
                                <label for="meta_description" class="block text-sm font-medium text-gray-900 mb-2">
                                    Meta Description
                                </label>
                                <Textarea
                                    id="meta_description"
                                    v-model="form.meta_description"
                                    rows="2"
                                    :placeholder="form.name ? `Browse and shop ${form.name} collection of quality products.` : 'Leave empty to auto-generate'"
                                    :class="{ 'p-invalid': form.errors.meta_description }"
                                    class="w-full"
                                />
                                <small v-if="form.errors.meta_description" class="p-error">{{ form.errors.meta_description }}</small>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex items-center justify-end gap-4 mt-8 pt-6 border-t">
                            <Link 
                                :href="route('ecommerce.markets.index')" 
                                class=""
                            >
                                <Button 
                                    label="Cancel" 
                                    severity="secondary"
                                    outlined
                                />
                            </Link>

                            <Button
                                type="submit"
                                label="Update Market"
                                icon="pi pi-save"
                                severity="primary"
                                :loading="form.processing"
                                loadingIcon="pi pi-spin pi-spinner"
                            />
                        </div>

                        <!-- Market URL Preview -->
                        <div v-if="form.name && generatedSlug" class="mt-6 p-4 bg-gray-50 rounded-lg">
                            <p class="text-sm text-gray-600">
                                <span class="font-bold">Market URL:</span>
                                <span class="ml-2 text-gray-500 font-mono">
                                    {{ appUrl }}/market/{{ generatedSlug }}
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
import { computed, onMounted } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Dropdown from 'primevue/dropdown';
import Checkbox from 'primevue/checkbox';

const props = defineProps({
    market: Object,
    shops: Array,
    appUrl: String
});

// Currency options
const currencyOptions = [
    { label: 'USD - US Dollar', value: 'USD' },
    { label: 'EUR - Euro', value: 'EUR' },
    { label: 'GBP - British Pound', value: 'GBP' },
    { label: 'CAD - Canadian Dollar', value: 'CAD' },
    { label: 'AUD - Australian Dollar', value: 'AUD' }
];

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
    form.put(route('ecommerce.markets.update', props.market.id), {
        preserveScroll: true,
        onSuccess: () => {
            // Redirect will be handled by the controller
        }
    });
};
</script>