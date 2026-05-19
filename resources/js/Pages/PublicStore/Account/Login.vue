<template>
  <PortalLayout title="Sign in">
    <div class="min-h-screen flex items-center justify-center py-12 px-4">
      <div class="w-full max-w-md">
        <div class="text-center mb-8">
          <h1 class="text-3xl font-medium text-surface-900 dark:text-surface-0 mb-2">Sign in to Portal</h1>
          <p class="text-surface-600 dark:text-surface-400">Enter your email to receive a magic link</p>
        </div>

        <div class="bg-white dark:bg-surface-800 rounded-lg shadow p-6">
          <div v-if="successMessage" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ successMessage }}
          </div>

          <form @submit.prevent="submitLogin">
            <div class="mb-4">
              <label for="email" class="block text-surface-900 dark:text-surface-0 font-medium mb-2">Email</label>
              <InputText
                id="email"
                type="email"
                v-model="form.email"
                placeholder="your@email.com"
                class="w-full"
              />
              <div v-if="errors.email" class="text-red-500 text-sm mt-2">{{ errors.email }}</div>
            </div>

            <Button
              label="Send link"
              type="submit"
              icon="pi pi-envelope"
              class="w-full"
              :loading="isLoading"
            />
          </form>

          <div class="mt-6 text-center">
            <span class="text-surface-600 dark:text-surface-400">Don't have an account?</span>
            <Link href="/publicstore/account/register" class="text-primary-500 hover:text-primary-600 ml-1">Sign up here</Link>
          </div>
        </div>
      </div>
    </div>
  </PortalLayout>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import PortalLayout from "@/Layouts/PortalLayout.vue";

const page = usePage();
const successMessage = computed(() => page.props.flash?.success);

const loaded = ref(false);
const isLoading = ref(false);

const form = useForm({
  email: '',
});

const errors = computed(() => page.props.errors || {});

const submitLogin = () => {
  isLoading.value = true;
  form.post('/publicstore/account/login', {
    onSuccess: () => {
      form.reset();
      isLoading.value = false;
    },
    onError: () => {
      isLoading.value = false;
    },
  });
};
</script>