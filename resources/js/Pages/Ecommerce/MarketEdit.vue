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
                        <Tabs value="0">
                            <TabList>
                                <Tab value="0">General</Tab>
                                <Tab value="1">Branding & Media</Tab>
                                <Tab value="2">Store Profile</Tab>
                                <Tab value="3">About Us</Tab>
                                <Tab value="4">FAQ</Tab>
                                <Tab value="5">SEO & Contact</Tab>
                            </TabList>

                            <TabPanels>
                                <!-- Tab 0: General -->
                                <TabPanel value="0">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4">
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
                                            <small v-if="generatedSlug" class="text-gray-500 mt-1 block">URL Slug: {{ generatedSlug }}</small>
                                        </div>

                                        <!-- Custom Domain -->
                                        <div class="col-span-2">
                                            <label for="custom_domain" class="block text-sm font-medium text-gray-900 mb-2">
                                                Custom Domain
                                            </label>
                                            <InputText
                                                id="custom_domain"
                                                v-model="form.custom_domain"
                                                placeholder="e.g., store.example.com (leave empty to use /market/:slug)"
                                                :class="{ 'p-invalid': form.errors.custom_domain }"
                                                class="w-full"
                                            />
                                            <small v-if="form.errors.custom_domain" class="p-error">{{ form.errors.custom_domain }}</small>
                                            <small class="text-gray-500 mt-1 block">If set, your clients will access the market at this custom domain.</small>
                                        </div>

                                        <!-- Shop Selection -->
                                        <div>
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
                                        <div class="col-span-2 flex flex-col gap-4 bg-gray-50 p-4 rounded-lg border">
                                            <h4 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Store Settings</h4>
                                            <div class="flex flex-col sm:flex-row gap-6">
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
                                            </div>
                                        </div>
                                    </div>
                                </TabPanel>

                                <!-- Tab 1: Branding & Media -->
                                <TabPanel value="1">
                                    <div class="flex flex-col gap-8 pt-4">
                                        <!-- Logo & Favicon -->
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                            <!-- Logo -->
                                            <div class="p-4 border rounded-xl bg-white shadow-sm">
                                                <label class="block text-sm font-bold text-gray-700 mb-4">Market Logo</label>
                                                <div class="flex flex-col items-center gap-4">
                                                    <div class="w-32 h-32 rounded-lg bg-gray-100 border border-gray-200 flex items-center justify-center overflow-hidden">
                                                        <img v-if="logoPreview || form.logo_url" :src="logoPreview || form.logo_url" class="w-full h-full object-contain" alt="Logo preview">
                                                        <i v-else class="pi pi-image text-4xl text-gray-400"></i>
                                                    </div>
                                                    <div class="flex flex-col items-center w-full">
                                                        <Button
                                                            type="button"
                                                            label="Choose Logo"
                                                            icon="pi pi-upload"
                                                            severity="secondary"
                                                            outlined
                                                            class="w-full"
                                                            @click="$refs.logoInput.click()"
                                                        />
                                                        <p class="text-[10px] text-gray-500 mt-2">Recommended: Square, max 2MB.</p>
                                                        <input type="file" ref="logoInput" class="hidden" accept="image/*" @change="handleLogoUpload" />
                                                        <small v-if="form.errors.logo" class="p-error mt-1">{{ form.errors.logo }}</small>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Favicon -->
                                            <div class="p-4 border rounded-xl bg-white shadow-sm">
                                                <label class="block text-sm font-bold text-gray-700 mb-4">Market Favicon</label>
                                                <div class="flex flex-col items-center gap-4">
                                                    <div class="w-32 h-32 rounded-lg bg-gray-100 border border-gray-200 flex items-center justify-center overflow-hidden">
                                                        <img v-if="faviconPreview || form.favicon_url" :src="faviconPreview || form.favicon_url" class="w-full h-full object-contain" alt="Favicon preview">
                                                        <i v-else class="pi pi-image text-4xl text-gray-400"></i>
                                                    </div>
                                                    <div class="flex flex-col items-center w-full">
                                                        <Button
                                                            type="button"
                                                            label="Choose Favicon"
                                                            icon="pi pi-upload"
                                                            severity="secondary"
                                                            outlined
                                                            class="w-full"
                                                            @click="$refs.faviconInput.click()"
                                                        />
                                                        <p class="text-[10px] text-gray-500 mt-2">Recommended: .ico/.png (32x32px), max 1MB.</p>
                                                        <input type="file" ref="faviconInput" class="hidden" accept="image/*" @change="handleFaviconUpload" />
                                                        <small v-if="form.errors.favicon" class="p-error mt-1">{{ form.errors.favicon }}</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Banners -->
                                        <div class="col-span-2 border-t pt-6">
                                            <div class="flex items-center justify-between mb-4">
                                                <label class="block text-sm font-bold text-gray-700">Market Banners</label>
                                                <Button type="button" label="Add Images" icon="pi pi-plus" severity="info" size="small" @click="$refs.bannerInput.click()" />
                                            </div>
                                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                                                <!-- Existing Banners -->
                                                <div v-for="banner in existingBanners" :key="banner.id" class="relative group aspect-video bg-gray-100 rounded-lg overflow-hidden border">
                                                    <img :src="banner.thumb || banner.url" class="w-full h-full object-cover">
                                                    <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                                        <Button type="button" icon="pi pi-trash" severity="danger" rounded @click="markBannerForDeletion(banner.id)" />
                                                    </div>
                                                </div>
                                                <!-- New Uploads -->
                                                <div v-for="(file, index) in newBanners" :key="`new-${index}`" class="relative group aspect-video bg-gray-100 rounded-lg overflow-hidden border border-blue-200 ring-2 ring-blue-100">
                                                    <img :src="file.preview || URL.createObjectURL(file)" class="w-full h-full object-cover">
                                                    <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                                        <Button type="button" icon="pi pi-times" severity="danger" rounded @click="removeNewBanner(index)" />
                                                    </div>
                                                    <div class="absolute bottom-0 left-0 right-0 bg-blue-500 text-white text-[10px] py-0.5 text-center">New Upload</div>
                                                </div>
                                            </div>
                                            <input type="file" ref="bannerInput" class="hidden" multiple accept="image/*" @change="handleBannerUpload" />
                                            <small v-if="form.errors.banners" class="p-error">{{ form.errors.banners }}</small>
                                        </div>
                                    </div>
                                </TabPanel>

                                <!-- Tab 2: Content -->
                                <TabPanel value="2">
                                    <div class="flex flex-col gap-6 pt-4">
                                        <!-- Tagline & Description -->
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div class="col-span-2">
                                                <label for="tagline" class="block text-sm font-medium text-gray-900 mb-2">Store Tagline</label>
                                                <InputText id="tagline" v-model="form.tagline" placeholder="e.g., Premium Quality Products" class="w-full" />
                                            </div>
                                            <div class="col-span-2">
                                                <label for="description" class="block text-sm font-medium text-gray-900 mb-2">Short Description</label>
                                                <Textarea id="description" v-model="form.description" placeholder="Describe your market..." class="w-full" rows="2" />
                                            </div>
                                        </div>
                                    </div>
                                </TabPanel>

                                <!-- Tab 3: About Us -->
                                <TabPanel value="3">
                                    <div class="border-t pt-4">
                                        <h4 class="text-md font-bold text-gray-800 mb-4">About Us Page Content</h4>
                                        <div class="grid grid-cols-1 gap-4">
                                            <div>
                                                <label for="about_title" class="block text-sm font-medium text-gray-700 mb-2">Page Title</label>
                                                <InputText id="about_title" v-model="form.about_us.title" placeholder="e.g. About Our Shop" class="w-full" />
                                            </div>
                                            <div>
                                                <label for="about_content" class="block text-sm font-medium text-gray-700 mb-2">Content (Supports HTML)</label>
                                                <Textarea id="about_content" v-model="form.about_us.content" rows="10" placeholder="Tell your story..." class="w-full" />
                                            </div>
                                        </div>
                                    </div>
                                </TabPanel>

                                <!-- Tab 4: FAQ -->
                                <TabPanel value="4">
                                    <div class="flex flex-col gap-6 pt-4">
                                        <div class="grid grid-cols-1 gap-4">
                                            <div>
                                                <label for="faq_title" class="block text-sm font-medium text-gray-900 mb-2">FAQ Page Title</label>
                                                <InputText id="faq_title" v-model="form.faq.title" placeholder="e.g., Frequently Asked Questions" class="w-full" />
                                            </div>
                                            <div>
                                                <label for="faq_description" class="block text-sm font-medium text-gray-900 mb-2">FAQ Description</label>
                                                <Textarea id="faq_description" v-model="form.faq.description" placeholder="Brief FAQ description..." rows="2" class="w-full" />
                                            </div>
                                        </div>

                                        <div class="border-t pt-6">
                                            <div class="flex items-center justify-between mb-4">
                                                <h4 class="text-md font-bold text-gray-800">Questions & Answers</h4>
                                                <Button type="button" label="Add Question" icon="pi pi-plus" severity="info" size="small" @click="addQuestion" />
                                            </div>

                                            <div v-if="form.faq.questions.length > 0" class="space-y-4">
                                                <div v-for="(question, index) in form.faq.questions" :key="index" class="p-4 border rounded-xl bg-gray-50 shadow-sm relative">
                                                    <div class="flex items-center justify-between mb-4">
                                                        <span class="text-xs font-bold text-gray-500 uppercase">Q&A #{{ index + 1 }}</span>
                                                        <div class="flex gap-1">
                                                            <Button v-if="index > 0" type="button" icon="pi pi-arrow-up" severity="secondary" size="small" text @click="moveQuestionUp(index)" />
                                                            <Button v-if="index < form.faq.questions.length - 1" type="button" icon="pi pi-arrow-down" severity="secondary" size="small" text @click="moveQuestionDown(index)" />
                                                            <Button type="button" icon="pi pi-trash" severity="danger" size="small" text @click="removeQuestion(index)" />
                                                        </div>
                                                    </div>
                                                    <div class="space-y-4">
                                                        <InputText v-model="question.question" placeholder="Question" class="w-full" />
                                                        <Textarea v-model="question.answer" placeholder="Answer" rows="3" class="w-full" />
                                                    </div>
                                                </div>
                                            </div>
                                            <div v-else class="p-12 border-2 border-dashed rounded-xl text-center text-gray-400">
                                                <i class="pi pi-question-circle text-4xl mb-4"></i>
                                                <p>No questions added yet.</p>
                                            </div>
                                        </div>
                                    </div>
                                </TabPanel>

                                <!-- Tab 5: SEO & Contact -->
                                <TabPanel value="5">
                                    <div class="flex flex-col gap-8 pt-4">
                                        <!-- SEO Section -->
                                        <div class="space-y-4">
                                            <h4 class="text-md font-bold text-gray-800 border-b pb-2">Search Engine Optimization (SEO)</h4>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div>
                                                    <label for="meta_title" class="block text-sm font-medium text-gray-700 mb-2">Meta Title</label>
                                                    <InputText id="meta_title" v-model="form.meta_title" class="w-full" />
                                                </div>
                                                <div>
                                                    <label for="meta_description" class="block text-sm font-medium text-gray-700 mb-2">Meta Description</label>
                                                    <Textarea id="meta_description" v-model="form.meta_description" rows="2" class="w-full" />
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Contact Section -->
                                        <div class="space-y-4">
                                            <h4 class="text-md font-bold text-gray-800 border-b pb-2">Public Contact Information</h4>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div>
                                                    <label for="contact_email" class="block text-sm font-medium text-gray-700 mb-2">Contact Email</label>
                                                    <InputText id="contact_email" v-model="form.contact_email" class="w-full" />
                                                </div>
                                                <div>
                                                    <label for="contact_phone" class="block text-sm font-medium text-gray-700 mb-2">Contact Phone</label>
                                                    <InputText id="contact_phone" v-model="form.contact_phone" class="w-full" />
                                                </div>
                                                <div class="col-span-2">
                                                    <label for="address" class="block text-sm font-medium text-gray-700 mb-2">Physical Address</label>
                                                    <Textarea id="address" v-model="form.address" rows="2" class="w-full" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </TabPanel>
                            </TabPanels>
                        </Tabs>

                        <!-- Form Actions -->
                        <div class="flex items-center justify-end gap-4 mt-8 pt-6 border-t">
                            <Link :href="route('ecommerce.markets.index')">
                                <Button label="Cancel" severity="secondary" outlined />
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
import { ref, computed, onMounted } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Dropdown from 'primevue/dropdown';
import Checkbox from 'primevue/checkbox';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';

const toast = useToast();

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
    custom_domain: '',
    shop_id: '',
    description: '',
    tagline: '',
    currency: 'USD',
    logo: null,
    logo_url: null,
    favicon: null,
    favicon_url: null,
    banners: [],
    deleted_banners: [],
    is_active: true,
    contact_email: '',
    contact_phone: '',
    address: '',
    meta_title: '',
    meta_description: '',
    about_us: {
        title: 'About Us',
        content: '',
        image_url: null
    },
    faq: {
        title: 'Frequently Asked Questions',
        description: 'Find answers to common questions about our products and services.',
        questions: []
    },
    deleted_banners: []
});

const existingBanners = ref([]);
const newBanners = ref([]);
const logoPreview = ref(null);
const faviconPreview = ref(null);

// Load market data into form
onMounted(() => {
    if (props.market) {
        form.name = props.market.name || '';
        form.custom_domain = props.market.custom_domain || '';
        form.shop_id = props.market.shop_id || '';
        form.description = props.market.description || '';
        form.tagline = props.market.tagline || '';
        form.currency = props.market.currency || 'USD';
        form.logo_url = props.market.logo_url || null;
        form.favicon_url = props.market.favicon_url || null;
        
        // Load existing media banners
        if (props.market.media_banners && props.market.media_banners.length > 0) {
            existingBanners.value = props.market.media_banners;
        }
        
        form.is_active = props.market.is_active || true;
        form.contact_email = props.market.contact_email || '';
        form.contact_phone = props.market.contact_phone || '';
        form.address = props.market.address || '';
        form.meta_title = props.market.meta_title || '';
        form.meta_description = props.market.meta_description || '';
        form.about_us = props.market.about_us || {
            title: 'About Us',
            content: '',
            image_url: null
        };
        form.faq = props.market.faq || {
            title: 'Frequently Asked Questions',
            description: '',
            questions: []
        };
    }
});

const generatedSlug = computed(() => {
    if (!form.name) return '';
    return form.name
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)/g, '');
});

const handleLogoUpload = (event) => {
    const file = event.target.files[0];
    if (!file) return;

    form.logo = file;
    logoPreview.value = URL.createObjectURL(file);
    
    // Reset input
    event.target.value = '';
};

const removeLogo = () => {
    if (logoPreview.value) {
        URL.revokeObjectURL(logoPreview.value);
    }
    logoPreview.value = null;
    form.logo = null;
};

const handleFaviconUpload = (event) => {
    const file = event.target.files[0];
    if (!file) return;

    form.favicon = file;
    faviconPreview.value = URL.createObjectURL(file);
    
    // Reset input
    event.target.value = '';
};

const removeFavicon = () => {
    if (faviconPreview.value) {
        URL.revokeObjectURL(faviconPreview.value);
    }
    faviconPreview.value = null;
    form.favicon = null;
};

const handleBannerUpload = (event) => {
    const files = Array.from(event.target.files);
    
    // Add preview URL to each file
    files.forEach(file => {
        file.preview = URL.createObjectURL(file);
    });

    newBanners.value = [...newBanners.value, ...files];
    form.banners = newBanners.value;
    
    // Reset input so same files can be selected again if needed
    event.target.value = '';
};

const removeNewBanner = (index) => {
    // Revoke the object URL to free memory
    if (newBanners.value[index].preview) {
        URL.revokeObjectURL(newBanners.value[index].preview);
    }
    
    newBanners.value.splice(index, 1);
    form.banners = newBanners.value;
};

const markBannerForDeletion = (id) => {
    existingBanners.value = existingBanners.value.filter(b => b.id !== id);
    form.deleted_banners.push(id);
};

const submitForm = () => {
    form.transform((data) => ({
        ...data,
        _method: 'PUT',
    })).post(route('ecommerce.markets.update', props.market.id), {
        preserveScroll: true,
        onSuccess: () => {
            newBanners.value = [];
            logoPreview.value = null; 
            form.logo = null;
            faviconPreview.value = null;
            form.favicon = null;
            toast.add({ severity: 'success', summary: 'Success', detail: 'Market updated successfully!', life: 3000 });
        }
    });
};

const addQuestion = () => {
    form.faq.questions.push({
        id: `faq-${Date.now()}`,
        question: '',
        answer: '',
        order: form.faq.questions.length + 1
    });
};

const removeQuestion = (index) => {
    form.faq.questions.splice(index, 1);
    form.faq.questions.forEach((q, i) => {
        q.order = i + 1;
    });
};

const moveQuestionUp = (index) => {
    if (index > 0) {
        const temp = form.faq.questions[index];
        form.faq.questions[index] = form.faq.questions[index - 1];
        form.faq.questions[index - 1] = temp;
        form.faq.questions.forEach((q, i) => {
            q.order = i + 1;
        });
    }
};

const moveQuestionDown = (index) => {
    if (index < form.faq.questions.length - 1) {
        const temp = form.faq.questions[index];
        form.faq.questions[index] = form.faq.questions[index + 1];
        form.faq.questions[index + 1] = temp;
        form.faq.questions.forEach((q, i) => {
            q.order = i + 1;
        });
    }
};
</script>
