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
                                <small class="text-gray-500 mt-1">If set, your clients will access the market at this custom domain.</small>
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

                            <!-- Visual Configuration -->
                            <div class="col-span-2 border-t pt-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Visual Configuration</h3>
                            </div>

                            <!-- Logo -->
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-gray-900 mb-4">
                                    Market Logo
                                </label>
                                <div class="flex items-center space-x-6">
                                    <div class="w-24 h-24 rounded-lg bg-gray-100 border border-gray-200 flex items-center justify-center overflow-hidden shrink-0">
                                        <img v-if="logoPreview || form.logo_url" :src="logoPreview || form.logo_url" class="w-full h-full object-contain" alt="Logo preview">
                                        <i v-else class="pi pi-image text-3xl text-gray-400"></i>
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-3">
                                            <Button
                                                type="button"
                                                label="Choose Logo"
                                                icon="pi pi-upload"
                                                severity="secondary"
                                                outlined
                                                size="small"
                                                @click="$refs.logoInput.click()"
                                            />
                                            <Button
                                                v-if="logoPreview"
                                                type="button"
                                                icon="pi pi-times"
                                                severity="danger"
                                                text
                                                rounded
                                                @click="removeLogo"
                                                title="Remove new logo"
                                            />
                                        </div>
                                        <p class="text-xs text-gray-500 mt-2">Recommended: Square image (e.g. 512x512px), max 2MB.</p>
                                        <input
                                            type="file"
                                            ref="logoInput"
                                            class="hidden"
                                            accept="image/*"
                                            @change="handleLogoUpload"
                                        />
                                        <small v-if="form.errors.logo" class="p-error block mt-1">{{ form.errors.logo }}</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Banners -->
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-gray-900 mb-4">
                                    Market Banners
                                </label>

                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    <!-- Existing Banners -->
                                    <div 
                                        v-for="banner in existingBanners" 
                                        :key="banner.id"
                                        class="relative group aspect-video bg-gray-100 rounded-lg overflow-hidden border border-gray-200"
                                    >
                                        <img 
                                            :src="banner.thumb || banner.url" 
                                            class="w-full h-full object-cover"
                                        >
                                        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                            <Button
                                                type="button"
                                                icon="pi pi-trash"
                                                severity="danger"
                                                rounded
                                                @click="markBannerForDeletion(banner.id)"
                                                title="Remove banner"
                                            />
                                        </div>
                                    </div>

                                    <!-- New Uploads -->
                                    <div 
                                        v-for="(file, index) in newBanners" 
                                        :key="`new-${index}`"
                                        class="relative group aspect-video bg-gray-100 rounded-lg overflow-hidden border border-blue-200 ring-2 ring-blue-100"
                                    >
                                        <img 
                                            :src="file.preview" 
                                            class="w-full h-full object-cover"
                                            @load="revokePreview(file.preview)"
                                        >
                                        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                            <Button
                                                type="button"
                                                icon="pi pi-times"
                                                severity="danger"
                                                rounded
                                                @click="removeNewBanner(index)"
                                                title="Remove upload"
                                            />
                                        </div>
                                        <div class="absolute bottom-0 left-0 right-0 bg-blue-500 text-white text-xs px-2 py-1 text-center bg-opacity-90">
                                            New Upload
                                        </div>
                                    </div>

                                    <!-- Add Banner Button -->
                                    <div 
                                        class="aspect-video bg-gray-50 border-2 border-dashed border-gray-300 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-colors cursor-pointer flex flex-col items-center justify-center text-gray-400 hover:text-blue-500"
                                        @click="$refs.bannerInput.click()"
                                    >
                                        <i class="pi pi-plus text-2xl mb-2"></i>
                                        <span class="text-sm font-medium">Add Image</span>
                                    </div>
                                </div>

                                <input
                                    type="file"
                                    ref="bannerInput"
                                    class="hidden"
                                    multiple
                                    accept="image/*"
                                    @change="handleBannerUpload"
                                />
                                
                                <small v-if="form.errors.banners" class="p-error block mt-2">{{ form.errors.banners }}</small>
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

                            <!-- FAQ Settings -->
                            <div class="col-span-2 border-t pt-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">FAQ Page</h3>
                            </div>

                            <!-- FAQ Title -->
                            <div class="col-span-2">
                                <label for="faq_title" class="block text-sm font-medium text-gray-900 mb-2">
                                    FAQ Title
                                </label>
                                <InputText
                                    id="faq_title"
                                    v-model="form.faq.title"
                                    placeholder="e.g., Frequently Asked Questions"
                                    class="w-full"
                                />
                            </div>

                            <!-- FAQ Description -->
                            <div class="col-span-2">
                                <label for="faq_description" class="block text-sm font-medium text-gray-900 mb-2">
                                    FAQ Description
                                </label>
                                <Textarea
                                    id="faq_description"
                                    v-model="form.faq.description"
                                    placeholder="Brief FAQ description..."
                                    rows="2"
                                    class="w-full"
                                />
                            </div>

                            <!-- FAQ Questions -->
                            <div class="col-span-2">
                                <div class="flex items-center justify-between mb-4">
                                    <label class="block text-sm font-medium text-gray-900">
                                        Questions
                                    </label>
                                    <Button
                                        type="button"
                                        label="Add Question"
                                        icon="pi pi-plus"
                                        severity="info"
                                        size="small"
                                        @click="addQuestion"
                                    />
                                </div>

                                <!-- Questions List -->
                                <div v-if="form.faq.questions.length > 0" class="space-y-4">
                                    <div
                                        v-for="(question, index) in form.faq.questions"
                                        :key="index"
                                        class="p-4 border border-gray-200 rounded-lg bg-gray-50"
                                    >
                                        <!-- Question Number and Actions -->
                                        <div class="flex items-center justify-between mb-4">
                                            <span class="text-sm font-medium text-gray-600">Question {{ index + 1 }}</span>
                                            <div class="flex gap-2">
                                                <!-- Move Up Button -->
                                                <Button
                                                    v-if="index > 0"
                                                    type="button"
                                                    icon="pi pi-arrow-up"
                                                    severity="secondary"
                                                    size="small"
                                                    text
                                                    @click="moveQuestionUp(index)"
                                                    title="Move up"
                                                />

                                                <!-- Move Down Button -->
                                                <Button
                                                    v-if="index < form.faq.questions.length - 1"
                                                    type="button"
                                                    icon="pi pi-arrow-down"
                                                    severity="secondary"
                                                    size="small"
                                                    text
                                                    @click="moveQuestionDown(index)"
                                                    title="Move down"
                                                />

                                                <!-- Delete Button -->
                                                <Button
                                                    type="button"
                                                    icon="pi pi-trash"
                                                    severity="danger"
                                                    size="small"
                                                    text
                                                    @click="removeQuestion(index)"
                                                    title="Delete question"
                                                />
                                            </div>
                                        </div>

                                        <!-- Question Text -->
                                        <div class="mb-4">
                                            <label :for="`question_${index}`" class="block text-sm font-medium text-gray-700 mb-2">
                                                Question
                                            </label>
                                            <InputText
                                                :id="`question_${index}`"
                                                v-model="question.question"
                                                placeholder="What is your question?"
                                                class="w-full"
                                            />
                                        </div>

                                        <!-- Answer Text -->
                                        <div>
                                            <label :for="`answer_${index}`" class="block text-sm font-medium text-gray-700 mb-2">
                                                Answer
                                            </label>
                                            <Textarea
                                                :id="`answer_${index}`"
                                                v-model="question.answer"
                                                placeholder="Your answer..."
                                                rows="3"
                                                class="w-full"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- Empty State -->
                                <div v-else class="p-6 border border-dashed border-gray-300 rounded-lg text-center">
                                    <i class="pi pi-inbox text-3xl text-gray-400 mb-2"></i>
                                    <p class="text-gray-500">No questions added yet. Click "Add Question" to get started.</p>
                                </div>
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
import { ref, computed, onMounted } from 'vue';
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
    custom_domain: '',
    shop_id: '',
    description: '',
    tagline: '',
    currency: 'USD',
    logo: null,
    logo_url: null,
    banners: [],
    deleted_banners: [],
    show_inventory_count: false,
    is_active: true,
    contact_email: '',
    contact_phone: '',
    address: '',
    meta_title: '',
    meta_description: '',
    faq: {
        title: '',
        description: '',
        questions: []
    }
});

const existingBanners = ref([]);
const newBanners = ref([]);
const logoPreview = ref(null);

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
        
        // Load existing media banners
        if (props.market.media_banners && props.market.media_banners.length > 0) {
            existingBanners.value = props.market.media_banners;
        }
        
        form.show_inventory_count = props.market.show_inventory_count || false;
        form.is_active = props.market.is_active || true;
        form.contact_email = props.market.contact_email || '';
        form.contact_phone = props.market.contact_phone || '';
        form.address = props.market.address || '';
        form.meta_title = props.market.meta_title || '';
        form.meta_description = props.market.meta_description || '';
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

const marketUrl = computed(() => {
    if (!props.market || !props.appUrl) return '';
    return `${props.appUrl}/market/${props.market.slug}`;
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

const revokePreview = (url) => {
    // Optional: Revoke immediately on load to save memory, 
    // or keep it if you need to re-render. 
    // For now, we'll keep it simple and rely on browser garbage collection 
    // or revoke on remove.
    // URL.revokeObjectURL(url); 
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
    // When uploading files with Inertia using PUT/PATCH, we must use POST
    // and include a _method field with 'PUT'.
    // However, Inertia's useForm helper with form.post() sends the data as FormData.
    // We need to append _method manually if we use form.post() to a PUT route?
    // Actually, Inertia recommends using router.post for file uploads to PUT routes with method spoofing.
    // But since we are using useForm, we can just use form.post and ensure _method is in the data.
    
    // Let's add _method to the form data manually before submitting
    form.transform((data) => ({
        ...data,
        _method: 'PUT',
    })).post(route('ecommerce.markets.update', props.market.id), {
        preserveScroll: true,
        onSuccess: () => {
            newBanners.value = [];
            // Optional: reset logo preview to use the actual URL returned from server if we had it,
            // but page reload via Inertia usually handles updating `market.logo_url` for us.
            logoPreview.value = null; 
            form.logo = null;
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
    // Reorder questions
    form.faq.questions.forEach((q, i) => {
        q.order = i + 1;
    });
};

const moveQuestionUp = (index) => {
    if (index > 0) {
        const temp = form.faq.questions[index];
        form.faq.questions[index] = form.faq.questions[index - 1];
        form.faq.questions[index - 1] = temp;
        // Reorder
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
        // Reorder
        form.faq.questions.forEach((q, i) => {
            q.order = i + 1;
        });
    }
};
</script>