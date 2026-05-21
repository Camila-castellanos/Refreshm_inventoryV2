<template>
  <PortalLayout title="Set Password">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 bg-surface-50 dark:bg-surface-900">
      <div class="w-full max-w-md">
        
        <!-- Header -->
        <div class="text-center mb-8">
          <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-white dark:bg-surface-800 mb-4 shadow-lg border border-surface-100 dark:border-surface-700">
            <i :class="[
              'pi !text-3xl',
              type === 'reset' ? 'pi-key text-orange-500' : 'pi-lock text-surface-900 dark:text-surface-0'
            ]"></i>
          </div>
          <h1 class="text-3xl font-bold text-surface-900 dark:text-surface-0 mb-1">
            {{ type === 'reset' ? 'Reset Password' : 'Secure Activation' }}
          </h1>
          <p class="text-surface-500 dark:text-surface-400 text-sm">
            {{ type === 'reset' ? 'Choose a new secure password for your account' : 'Create a password to activate your account' }}
          </p>
        </div>

        <div class="bg-white dark:bg-surface-800 rounded-2xl shadow-lg p-8 border border-surface-100 dark:border-surface-700">
          
          <!-- Error Alert -->
          <div v-if="error" class="flex items-center gap-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-400 px-4 py-3 rounded-xl mb-6 text-sm">
            <i class="pi pi-exclamation-circle flex-shrink-0"></i>
            <span>{{ error }}</span>
          </div>

          <form @submit.prevent="submitPassword" class="space-y-5">
            <div>
              <label class="block text-surface-700 dark:text-surface-200 text-sm font-medium mb-1.5">Email address</label>
              <InputText
                type="email"
                :value="email"
                disabled
                class="w-full opacity-60 cursor-not-allowed bg-surface-50 dark:bg-surface-900"
              />
            </div>

            <div>
              <label for="password" class="block text-surface-700 dark:text-surface-200 text-sm font-medium mb-1.5">New password</label>
              <InputText
                id="password"
                type="password"
                v-model="form.password"
                placeholder="Minimum 8 characters"
                class="w-full"
                :class="{ 'p-invalid': form.errors?.password }"
                autofocus
              />
              <small v-if="form.errors?.password" class="text-red-500 mt-1 block">{{ form.errors.password }}</small>
            </div>

            <div>
              <label for="password_confirmation" class="block text-surface-700 dark:text-surface-200 text-sm font-medium mb-1.5">Confirm new password</label>
              <InputText
                id="password_confirmation"
                type="password"
                v-model="form.password_confirmation"
                placeholder="Repeat your password"
                class="w-full"
                :class="{ 'p-invalid': form.errors?.password_confirmation }"
              />
              <small v-if="form.errors?.password_confirmation" class="text-red-500 mt-1 block">{{ form.errors.password_confirmation }}</small>
            </div>

            <Button
              :label="type === 'reset' ? 'Update password' : 'Activate account'"
              type="submit"
              :icon="type === 'reset' ? 'pi pi-check' : 'pi pi-shield'"
              class="w-full mt-2"
              :loading="form.processing"
            />
          </form>
        </div>
      </div>
    </div>
  </PortalLayout>
</template>

<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import PortalLayout from "@/Layouts/PortalLayout.vue";

const props = defineProps<{
  email?: string | null;
  token: string;
  error?: string | null;
  type: 'activation' | 'reset' | 'access';
}>();

const form = useForm({
  token: props.token,
  password: '',
  password_confirmation: '',
});

const submitPassword = () => {
  form.post(route('publicstore.account.set-password'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  });
};
</script>
