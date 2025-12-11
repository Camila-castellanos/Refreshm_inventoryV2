<template>
    <!-- Meta Head -->
    <Head
        :title="`${faqData.title} - ${market.name}`"
        :description="faqData.description || `Frequently Asked Questions about ${market.name}`"
    />

    <!-- FAQ Hero Section -->
    <section class="py-16 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6">
                    {{ faqData.title }}
                </h1>
                <p v-if="faqData.description" class="text-xl text-gray-600 max-w-2xl mx-auto">
                    {{ faqData.description }}
                </p>
            </div>
        </div>
    </section>

    <!-- FAQ Content -->
    <section class="py-16 bg-slate-100">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div v-if="faqData.questions && faqData.questions.length > 0" class="space-y-4">
                <!-- FAQ Item -->
                <div
                    v-for="(item, index) in sortedQuestions"
                    :key="item.id || index"
                    class="bg-white rounded-xl border border-gray-200 overflow-hidden transition-shadow duration-200 hover:shadow-md"
                >
                    <button
                        @click="toggleQuestion(index)"
                        class="w-full px-6 py-4 flex items-center justify-between hover:bg-gradient-to-r hover:from-slate-50 hover:to-slate-100 transition-all duration-200 text-left group"
                    >
                        <!-- Question -->
                        <div class="flex-1 mr-6">
                            <h3 class="text-lg font-semibold text-gray-900 group-hover:text-gray-700 transition-colors duration-200">
                                {{ item.question }}
                            </h3>
                        </div>

                        <!-- Toggle Icon -->
                        <div class="flex-shrink-0">
                            <div class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-slate-100 group-hover:bg-slate-200 transition-all duration-200">
                                <i
                                    :class="[
                                        'pi pi-chevron-down text-gray-600 transition-transform duration-300',
                                        activeQuestion === index && 'rotate-180'
                                    ]"
                                ></i>
                            </div>
                        </div>
                    </button>

                    <!-- Answer -->
                    <transition name="accordion">
                        <div
                            v-if="activeQuestion === index"
                            class="px-6 py-4 border-t border-gray-200 bg-gradient-to-b from-slate-50 to-white"
                        >
                            <p class="text-gray-700 leading-relaxed whitespace-pre-wrap text-base">
                                {{ item.answer }}
                            </p>
                        </div>
                    </transition>
                </div>

                <!-- No Questions Message -->
                <div v-if="!faqData.questions || faqData.questions.length === 0" class="text-center py-12">
                    <i class="pi pi-inbox text-4xl text-gray-300 mb-4"></i>
                    <p class="text-gray-600 text-lg">No questions available yet</p>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-16">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gradient-to-br from-slate-100 to-slate-200 mb-6 shadow-lg">
                    <i class="pi pi-question-circle text-4xl text-gray-600"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">No FAQ Available</h2>
                <p class="text-gray-600">
                    We don't have any frequently asked questions at the moment. Please contact us for more information.
                </p>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-16 bg-white">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-gradient-to-br from-slate-50 to-slate-100 rounded-xl border border-gray-200 p-8 md:p-12 text-center shadow-md hover:shadow-lg transition-shadow duration-300">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Didn't find what you're looking for?</h2>
                <p class="text-lg text-gray-600 mb-8">
                    Our support team is here to help. Get in touch with us for any additional questions.
                </p>
                <Link
                    :href="`/market/${market.slug}/contact`"
                    class="inline-flex items-center justify-center px-8 py-4 rounded-lg bg-slate-100 text-gray-700 hover:text-gray-900 border border-gray-200 hover:border-gray-300 font-semibold text-lg transition-colors duration-150 shadow-sm hover:shadow hover:bg-slate-200"
                >
                    <i class="pi pi-envelope text-sm mr-2"></i>
                    Contact Us
                </Link>
            </div>
        </div>
    </section>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import MarketLayout from '@/Layouts/Ecommerce/MarketLayout.vue'

defineOptions({ layout: MarketLayout })

// Props
const props = defineProps({
    market: {
        type: Object,
        required: true
    },
    faqData: {
        type: Object,
        required: true
    }
})

// State
const activeQuestion = ref(null)

// Computed
const sortedQuestions = computed(() => {
    if (!props.faqData.questions) return []
    
    return [...props.faqData.questions].sort((a, b) => {
        return (a.order || 0) - (b.order || 0)
    })
})

// Methods
const toggleQuestion = (index) => {
    activeQuestion.value = activeQuestion.value === index ? null : index
}
</script>

<style scoped>
/* GPU-accelerated accordion animation - only uses opacity and transform */
.accordion-enter-active,
.accordion-leave-active {
    transition: opacity 0.2s ease-out, transform 0.2s ease-out;
    will-change: opacity, transform;
}

.accordion-enter-from,
.accordion-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}

.accordion-enter-to,
.accordion-leave-from {
    opacity: 1;
    transform: translateY(0);
}

/* Icon rotation - GPU accelerated */
.pi {
    display: inline-block;
    will-change: transform;
}

.pi.rotate-180 {
    transform: rotate(180deg);
}

/* Simple fade-in on page load - reduced delays for snappier feel */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.space-y-4 > div {
    animation: fadeIn 0.3s ease-out both;
}

.space-y-4 > div:nth-child(1) { animation-delay: 0.05s; }
.space-y-4 > div:nth-child(2) { animation-delay: 0.1s; }
.space-y-4 > div:nth-child(3) { animation-delay: 0.15s; }
.space-y-4 > div:nth-child(4) { animation-delay: 0.2s; }
.space-y-4 > div:nth-child(n+5) { animation-delay: 0.25s; }
</style>
