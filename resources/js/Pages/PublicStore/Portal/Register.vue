<template>
  <PortalLayout title="Sign up">
    <div class="min-h-screen flex items-center justify-center py-12 px-4">
      <div class="w-full max-w-md">
        <div class="text-center mb-8">
          <h1 class="text-3xl font-medium text-surface-900 dark:text-surface-0 mb-2">Create Account</h1>
          <p class="text-surface-600 dark:text-surface-400">Sign up to access the customer portal</p>
        </div>

        <div class="bg-white dark:bg-surface-800 rounded-lg shadow p-6">
          <div v-if="successMessage" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ successMessage }}
          </div>

          <form @submit.prevent="submitRegister">
            <div class="mb-4">
              <label for="name" class="block text-surface-900 dark:text-surface-0 font-medium mb-2">Name</label>
              <InputText
                id="name"
                type="text"
                v-model="form.name"
                placeholder="Your name"
                class="w-full"
              />
            </div>

            <div class="mb-4">
              <label for="email" class="block text-surface-900 dark:text-surface-0 font-medium mb-2">Email</label>
              <InputText
                id="email"
                type="email"
                v-model="form.email"
                placeholder="your@email.com"
                class="w-full"
              />
            </div>

            <div class="mb-4">
              <label for="password" class="block text-surface-900 dark:text-surface-0 font-medium mb-2">Password</label>
              <InputText
                id="password"
                type="password"
                v-model="form.password"
                placeholder="Minimum 8 characters"
                class="w-full"
              />
            </div>

            <div class="mb-4">
              <label for="password_confirmation" class="block text-surface-900 dark:text-surface-0 font-medium mb-2">Confirm Password</label>
              <InputText
                id="password_confirmation"
                type="password"
                v-model="form.password_confirmation"
                placeholder="Repeat your password"
                class="w-full"
              />
            </div>

            <div v-if="Object.keys(errors).length > 0" class="text-red-500 text-sm mb-4">
              <div v-for="(error, key) in errors" :key="key">{{ error }}</div>
            </div>

            <Button
              label="Create account"
              type="submit"
              icon="pi pi-user-plus"
              class="w-full"
              :loading="isLoading"
            />
          </form>

          <div class="mt-6 text-center">
            <span class="text-surface-600 dark:text-surface-400">Already have an account?</span>
            <Link :href="route('public-store.portal.login.show')" class="text-primary-500 hover:text-primary-600 ml-1">Sign in</Link>
          </div>
        </div>
      </div>
    </div>
  </PortalLayout>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { Link, useForm, usePage } from "@inertiajs/vue3";
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import PortalLayout from "@/Layouts/PortalLayout.vue";

const page = usePage();
const successMessage = ref(page.props.flash?.success || '');
const errors = ref<Record<string, string>>({});
const isLoading = ref(false);

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
});

const submitRegister = () => {
  isLoading.value = true;
  errors.value = {};

  form.post(route('public-store.portal.register'), {
    onSuccess: () => {
      isLoading.value = false;
    },
    onError: (err) => {
      errors.value = err;
      isLoading.value = false;
    },
  });
};
</script>