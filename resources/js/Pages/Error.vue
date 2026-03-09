<template>
  <div class="flex flex-col items-center justify-center min-h-screen p-6 text-center bg-gray-50">
    <div class="relative mb-6 flex items-center justify-center">
      <!-- Smartphone SVG -->
      <svg xmlns="http://www.w3.org/2000/svg" width="120" height="120" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="text-gray-300">
        <rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect>
        <line x1="12" y1="18" x2="12.01" y2="18"></line>
      </svg>
      <!-- Error Overlay Icon -->
      <div class="absolute -bottom-2 -right-2 bg-white rounded-full w-12 h-12 flex items-center justify-center shadow-md border border-gray-50">
        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="12" y1="8" x2="12" y2="12"></line>
          <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
      </div>
    </div>
    
    <h1 class="text-3xl font-bold text-gray-800 mb-2">
      {{ title }}
    </h1>
    
    <p class="text-gray-600 max-w-md mb-8">
      {{ description }}
    </p>

    <div class="flex gap-4">
      <Button 
        label="Go Back" 
        icon="pi pi-arrow-left" 
        class="p-button-secondary p-button-outlined" 
        @click="goBack" 
      />
      <Button 
        label="Refresh Dashboard" 
        icon="pi pi-refresh" 
        @click="reload" 
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue';
import Button from 'primevue/button';

const props = defineProps<{
  status: number;
  message?: string;
  debug?: any;
}>();

const title = computed(() => {
  return {
    503: 'Service Unavailable',
    500: 'Server Error',
    404: 'Page Not Found',
    403: 'Access Denied',
  }[props.status] || 'Unexpected Error';
});

const description = computed(() => {
  return {
    503: 'Sorry, we are doing some maintenance. Please check back soon.',
    500: 'Whoops, something went wrong on our servers.',
    404: 'Sorry, the page you are looking for could not be found.',
    403: 'Sorry, you do not have permission to access this page.',
  }[props.status] || 'An unexpected error occurred. Our team has been notified.';
});

const goBack = () => {
  window.history.back();
};

const reload = () => {
  window.location.href = '/dashboard';
};

onMounted(() => {
  if (props.debug || props.message) {
    console.group('%c 🚫 Application Error Trace', 'color: white; background: #ef4444; padding: 4px; font-weight: bold;');
    console.error('Status:', props.status);
    console.error('Message:', props.message || 'No message provided');
    if (props.debug) {
      console.error('Debug Info:', props.debug);
    }
    console.groupEnd();
  }
});
</script>
