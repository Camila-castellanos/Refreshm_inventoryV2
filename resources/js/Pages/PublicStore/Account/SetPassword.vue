<template>
  <PortalLayout title="Set Password">
    <div class="min-h-screen flex items-center justify-center py-12 px-4">
      <div class="w-full max-w-md">
        <div class="text-center mb-8">
          <h1 class="text-3xl font-medium text-surface-900 dark:text-surface-0 mb-2">Create Password</h1>
          <p class="text-surface-600 dark:text-surface-400">Enter your new password</p>
        </div>

        <div class="bg-white dark:bg-surface-800 rounded-lg shadow p-6">
          <div v-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ error }}
          </div>

          <form @submit.prevent="submitPassword">
            <div class="mb-4">
              <label for="email" class="block text-surface-900 dark:text-surface-0 font-medium mb-2">Email</label>
              <InputText
                id="email"
                type="email"
                v-model="email"
                disabled
                class="w-full bg-surface-100"
              />
            </div>

            <div class="mb-4">
              <label for="password" class="block text-surface-900 dark:text-surface-0 font-medium mb-2">New Password</label>
              <InputText
                id="password"
                type="password"
                v-model="form.password"
                placeholder="Minimum 8 characters, 1 letter, 1 number"
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
              label="Save password"
              type="submit"
              icon="pi pi-check"
              class="w-full"
              :loading="isLoading"
            />
          </form>
        </div>
      </div>
    </div>
  </PortalLayout>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import PortalLayout from "@/Layouts/PortalLayout.vue";

defineProps<{
  email?: string | null;
  token: string;
  error?: string | null;
}>();

const isLoading = ref(false);
const errors = ref<Record<string, string>>({});

const form = useForm({
  password: '',
  password_confirmation: '',
});

const submitPassword = () => {
  isLoading.value = true;
  errors.value = {};

  form.post('/publicstore/account/set-password', {
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