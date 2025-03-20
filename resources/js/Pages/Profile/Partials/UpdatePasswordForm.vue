<template>
  <div class="flex justify-between">
    <div class="flex items-start mb-2 w-1/3">
      <div>
        <h2 class="text-xl font-medium mb-1">Update Password</h2>
        <p class="text-sm">Ensure your account is using a long, random password to stay secure.</p>
      </div>
    </div>

    <Card class="shadow-none w-2/3">
      <template #content>
        <form @submit.prevent="updatePassword" class="space-y-6">
          <!-- Current Password -->
          <div class="field">
            <label for="current_password" class="block text-sm font-medium mb-1">Current Password</label>
            <Password
              id="current_password"
              ref="currentPasswordInput"
              v-model="form.current_password"
              toggleMask
              :feedback="false"
              class="w-full"
              inputClass="w-full"
              autocomplete="current-password" />
            <small v-if="form.errors.current_password" class="text-red-500">{{ form.errors.current_password }}</small>
          </div>

          <!-- New Password -->
          <div class="field">
            <label for="password" class="block text-sm font-medium mb-1">New Password</label>
            <Password
              id="password"
              ref="passwordInput"
              v-model="form.password"
              toggleMask
              class="w-full"
              inputClass="w-full"
              autocomplete="new-password" />
            <small v-if="form.errors.password" class="text-red-500">{{ form.errors.password }}</small>
          </div>

          <!-- Confirm Password -->
          <div class="field">
            <label for="password_confirmation" class="block text-sm font-medium mb-1">Confirm Password</label>
            <Password
              id="password_confirmation"
              v-model="form.password_confirmation"
              toggleMask
              :feedback="false"
              class="w-full"
              inputClass="w-full"
              autocomplete="new-password" />
            <small v-if="form.errors.password_confirmation" class="text-red-500">{{ form.errors.password_confirmation }}</small>
          </div>

          <div class="flex justify-end gap-2">
            <span v-if="form.recentlySuccessful" class="text-green-500 self-center mr-2">Saved successfully</span>
            <Button type="submit" :loading="form.processing" :disabled="form.processing" label="Save" icon="pi pi-save" />
          </div>
        </form>
      </template>
    </Card>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Password, Button, Card, useToast } from 'primevue';
import axios from 'axios';

const toast = useToast();
const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    axios.put(route('user-password.update'), form.data())
    .then(() => {
        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Password updated successfully',
            life: 3000
        });
        form.reset();
    })
    .catch((error) => {
        if (error.response?.status === 422) {
            form.setError(error.response.data.errors);
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }

            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        }
    });
};
</script>

<style scoped>
:deep(.p-card) {
  box-shadow: none;
  border: none;
}

:deep(.p-card-content) {
  padding: 1.5rem;
}

:deep(.p-card .p-card-body) {
  padding: 0;
}

:deep(.p-password) {
  width: 100%;
}

:deep(.p-password-input) {
  width: 100%;
}
</style>
