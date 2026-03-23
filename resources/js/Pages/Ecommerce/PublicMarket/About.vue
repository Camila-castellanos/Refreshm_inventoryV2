<template>
    <Head :title="`About Us - ${market.name}`" />

    <div class="min-h-screen bg-gray-50 py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="text-center mb-12">
                <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4 font-avenir">
                    {{ aboutData.title || 'About Us' }}
                </h1>
                <div class="w-24 h-1 bg-gray-900 mx-auto rounded-full"></div>
            </div>

            <!-- Content Area -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <!-- Optional Header Image -->
                <div v-if="aboutData.image_url" class="w-full h-64 md:h-96 relative">
                    <img :src="aboutData.image_url" :alt="market.name" class="w-full h-full object-cover">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
                </div>

                <div class="p-8 md:p-12">
                    <div class="prose max-w-none text-gray-700 leading-relaxed text-lg" v-html="formattedContent">
                    </div>
                </div>
            </div>

            <!-- Back to Shop -->
            <div class="mt-12 text-center">
                <Link 
                    :href="route('market.index', market.slug)"
                    class="inline-flex items-center justify-center px-8 py-4 text-base font-bold rounded-xl text-white bg-gray-900 hover:bg-gray-800 transition-all duration-200 shadow-lg hover:shadow-xl font-avenir"
                >
                    <i class="pi pi-shopping-bag mr-3"></i>
                    Return to Shop
                </Link>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import MarketLayout from '@/Layouts/Ecommerce/MarketLayout.vue'

defineOptions({ layout: MarketLayout })

const props = defineProps({
    market: {
        type: Object,
        required: true
    },
    aboutData: {
        type: Object,
        default: () => ({
            title: 'About Us',
            content: '',
            image_url: null
        })
    }
})

// Simple formatter to preserve line breaks if HTML is not provided
const formattedContent = computed(() => {
    if (!props.aboutData.content) return '';
    // If it looks like HTML, return as is
    if (props.aboutData.content.includes('<p>') || props.aboutData.content.includes('<h1>')) {
        return props.aboutData.content;
    }
    // Otherwise, replace newlines with <br>
    return props.aboutData.content.replace(/\n/g, '<br>');
})
</script>

<style scoped>
.prose :deep(h1), .prose :deep(h2), .prose :deep(h3) {
    color: #111827;
    font-weight: 700;
    margin-top: 2rem;
    margin-bottom: 1rem;
    font-family: 'Avenir Next', Avenir, sans-serif;
}
.prose :deep(p) {
    margin-bottom: 1.5rem;
}
</style>
