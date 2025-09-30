<template>
    <MarketLayout :market="market">
        <!-- Meta Head -->
        <Head 
            :title="`Contact ${market.name} - Get in Touch`"
            :description="`Contact ${market.name} for inquiries about our products, services, and support. We're here to help with your questions.`"
        />

        <!-- Contact Hero Section -->
        <section class="py-16 bg-white">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6">
                        Contact {{ market.name }}
                    </h1>
                    <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                        Have questions about our products or services? We'd love to hear from you. 
                        Send us a message and we'll respond as soon as possible.
                    </p>
                </div>
            </div>
        </section>

        <!-- Contact Content -->
        <section class="py-16 bg-slate-100">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                    
                    <!-- Contact Information -->
                    <div class="bg-white rounded-lg border border-gray-200 p-8">
                        <h2 class="text-2xl font-bold text-gray-900 mb-6">Get in Touch</h2>
                        
                        <div class="space-y-6">
                            <!-- Email -->
                            <div v-if="market.contact_email" class="flex items-start space-x-4">
                                <div class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-slate-100 border border-gray-200">
                                    <i class="pi pi-envelope text-gray-600"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 mb-1">Email Us</h3>
                                    <p class="text-gray-600 mb-2">Send us an email anytime</p>
                                    <a 
                                        :href="`mailto:${market.contact_email}`" 
                                        class="text-gray-700 hover:text-gray-900 font-medium transition-colors duration-200"
                                    >
                                        {{ market.contact_email }}
                                    </a>
                                </div>
                            </div>

                            <!-- Phone -->
                            <div v-if="market.contact_phone" class="flex items-start space-x-4">
                                <div class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-slate-100 border border-gray-200">
                                    <i class="pi pi-phone text-gray-600"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 mb-1">Call Us</h3>
                                    <p class="text-gray-600 mb-2">Mon-Fri from 8am to 5pm</p>
                                    <a 
                                        :href="`tel:${market.contact_phone}`" 
                                        class="text-gray-700 hover:text-gray-900 font-medium transition-colors duration-200"
                                    >
                                        {{ market.contact_phone }}
                                    </a>
                                </div>
                            </div>

                            <!-- Address -->
                            <div v-if="market.location" class="flex items-start space-x-4">
                                <div class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-slate-100 border border-gray-200">
                                    <i class="pi pi-map-marker text-gray-600"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 mb-1">Visit Us</h3>
                                    <p class="text-gray-600 mb-2">Come say hello at our office</p>
                                    <p class="text-gray-700">{{ market.location }}</p>
                                </div>
                            </div>

                            <!-- Business Hours -->
                            <div v-if="market.business_hours" class="flex items-start space-x-4">
                                <div class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-slate-100 border border-gray-200">
                                    <i class="pi pi-clock text-gray-600"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 mb-1">Business Hours</h3>
                                    <p class="text-gray-600 mb-2">We're open during these times</p>
                                    <p class="text-gray-700">{{ market.business_hours }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Social Links (if available) -->
                        <div v-if="market.social_links" class="mt-8 pt-6 border-t border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Follow Us</h3>
                            <div class="flex space-x-4">
                                <a 
                                    v-for="(link, platform) in market.social_links" 
                                    :key="platform"
                                    :href="link"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-slate-100 text-gray-600 hover:text-gray-900 border border-gray-200 hover:border-gray-300 transition-all duration-200"
                                >
                                    <i :class="`pi pi-${platform}`"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Form -->
                    <div class="bg-white rounded-lg border border-gray-200 p-8">
                        <h2 class="text-2xl font-bold text-gray-900 mb-6">Send us a Message</h2>
                        
                        <form @submit.prevent="submitForm" class="space-y-6">
                            <!-- Name -->
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                    Full Name *
                                </label>
                                <input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    required
                                    class="w-full px-4 py-3 bg-slate-100 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-300 focus:border-gray-300 placeholder-gray-500 transition-all duration-200"
                                    placeholder="Your full name"
                                >
                            </div>

                            <!-- Email -->
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                    Email Address *
                                </label>
                                <input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    required
                                    class="w-full px-4 py-3 bg-slate-100 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-300 focus:border-gray-300 placeholder-gray-500 transition-all duration-200"
                                    placeholder="your@email.com"
                                >
                            </div>

                            <!-- Phone -->
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                                    Phone Number
                                </label>
                                <input
                                    id="phone"
                                    v-model="form.phone"
                                    type="tel"
                                    class="w-full px-4 py-3 bg-slate-100 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-300 focus:border-gray-300 placeholder-gray-500 transition-all duration-200"
                                    placeholder="Your phone number"
                                >
                            </div>

                            <!-- Subject -->
                            <div>
                                <label for="subject" class="block text-sm font-medium text-gray-700 mb-2">
                                    Subject *
                                </label>
                                <select
                                    id="subject"
                                    v-model="form.subject"
                                    required
                                    class="w-full px-4 py-3 bg-slate-100 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-300 focus:border-gray-300 transition-all duration-200"
                                >
                                    <option value="">Select a subject</option>
                                    <option value="general">General Inquiry</option>
                                    <option value="products">Product Information</option>
                                    <option value="support">Technical Support</option>
                                    <option value="partnership">Partnership Opportunities</option>
                                    <option value="feedback">Feedback</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>

                            <!-- Message -->
                            <div>
                                <label for="message" class="block text-sm font-medium text-gray-700 mb-2">
                                    Message *
                                </label>
                                <textarea
                                    id="message"
                                    v-model="form.message"
                                    rows="6"
                                    required
                                    class="w-full px-4 py-3 bg-slate-100 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-300 focus:border-gray-300 placeholder-gray-500 transition-all duration-200"
                                    placeholder="Tell us how we can help you..."
                                ></textarea>
                            </div>

                            <!-- Submit Button -->
                            <div class="pt-4">
                                <button
                                    type="submit"
                                    :disabled="isSubmitting"
                                    class="w-full inline-flex items-center justify-center px-6 py-3 rounded-lg bg-slate-100 text-gray-700 hover:text-gray-900 border border-gray-200 hover:border-gray-300 font-semibold transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <i v-if="isSubmitting" class="pi pi-spin pi-spinner text-sm mr-2"></i>
                                    <i v-else class="pi pi-send text-sm mr-2"></i>
                                    {{ isSubmitting ? 'Sending...' : 'Send Message' }}
                                </button>
                            </div>

                            <!-- Success Message -->
                            <div v-if="showSuccessMessage" class="mt-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                                <div class="flex items-center">
                                    <i class="pi pi-check-circle text-green-600 mr-2"></i>
                                    <p class="text-green-800 text-sm font-medium">
                                        Thank you! Your message has been sent successfully. We'll get back to you soon.
                                    </p>
                                </div>
                            </div>

                            <!-- Error Message -->
                            <div v-if="showErrorMessage" class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                                <div class="flex items-center">
                                    <i class="pi pi-exclamation-triangle text-red-600 mr-2"></i>
                                    <p class="text-red-800 text-sm font-medium">
                                        Sorry, there was an error sending your message. Please try again or contact us directly.
                                    </p>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>

        <!-- Additional Info Section -->
        <section v-if="market.description" class="py-16 bg-white">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl font-bold text-gray-900 mb-6">About {{ market.name }}</h2>
                <p class="text-lg text-gray-600 leading-relaxed">
                    {{ market.description }}
                </p>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="py-16 bg-slate-100">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl font-bold text-gray-900 mb-6">Ready to Explore Our Products?</h2>
                <p class="text-lg text-gray-600 mb-8">
                    Check out our latest inventory and find exactly what you're looking for.
                </p>
                <Link 
                    :href="`/market/${market.slug}/products`"
                    class="inline-flex items-center justify-center px-8 py-4 rounded-lg bg-slate-100 text-gray-700 hover:text-gray-900 border border-gray-200 hover:border-gray-300 font-semibold text-lg transition-all duration-200 shadow-sm hover:shadow-md"
                >
                    <i class="pi pi-shopping-bag text-sm mr-2"></i>
                    Browse Products
                </Link>
            </div>
        </section>
    </MarketLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import MarketLayout from '@/Layouts/Ecommerce/MarketLayout.vue'

// Props
const props = defineProps({
    market: {
        type: Object,
        required: true
    }
})

// Form state
const form = ref({
    name: '',
    email: '',
    phone: '',
    subject: '',
    message: ''
})

const isSubmitting = ref(false)
const showSuccessMessage = ref(false)
const showErrorMessage = ref(false)

// Methods
const submitForm = async () => {
    if (isSubmitting.value) return

    // Reset messages
    showSuccessMessage.value = false
    showErrorMessage.value = false
    
    // Validate required fields
    if (!form.value.name || !form.value.email || !form.value.subject || !form.value.message) {
        showErrorMessage.value = true
        return
    }

    isSubmitting.value = true

    try {
        // Here you would typically send the form data to your backend
        // For now, we'll simulate an API call
        await new Promise(resolve => setTimeout(resolve, 2000))
        
        // Simulate success
        showSuccessMessage.value = true
        
        // Reset form
        form.value = {
            name: '',
            email: '',
            phone: '',
            subject: '',
            message: ''
        }
        
        // Auto-hide success message after 5 seconds
        setTimeout(() => {
            showSuccessMessage.value = false
        }, 5000)
        
    } catch (error) {
        console.error('Error submitting form:', error)
        showErrorMessage.value = true
    } finally {
        isSubmitting.value = false
    }
}
</script>

<style scoped>
/* Additional form styling */
.form-field:focus-within {
    transform: translateY(-1px);
}

/* Smooth transitions for messages */
.success-message,
.error-message {
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>