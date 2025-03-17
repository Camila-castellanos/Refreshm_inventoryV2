<template>
  <div class="flex justify-between">
    <div class="flex items-start mb-2 w-1/3">
      <div>
        <h2 class="text-xl font-medium mb-1">Delete Account</h2>
        <p class="text-sm">Permanently delete your account.</p>
      </div>
    </div>

    <Card class="shadow-none w-2/3">
      <template #content>
        <div class="space-y-6">
          <div class="max-w-xl text-sm">
            Once your account is deleted, all of its resources and data will be permanently deleted. 
            Before deleting your account, please download any data or information that you wish to retain.
          </div>

          <div>
            <Button 
              label="Delete Account" 
              icon="pi pi-trash" 
              severity="danger" 
              @click="confirmUserDeletion"
            />
          </div>
        </div>
      </template>
    </Card>
  </div>

  <!-- Delete Account Confirmation Dialog -->
  <Dialog 
    v-model:visible="confirmingUserDeletion" 
    modal 
    header="Delete Account" 
    class="max-w-md"
  >
    <div class="p-4">
      <p class="mb-4">
        Are you sure you want to delete your account? Once your account is deleted, all of its resources and data will be permanently deleted. 
        Please enter your password to confirm you would like to permanently delete your account.
      </p>

      <div class="mt-4">
        <label for="password" class="block text-sm font-medium mb-1">Password</label>
        <Password
          ref="passwordInput"
          v-model="form.password"
          toggleMask
          :feedback="false"
          class="w-full"
          inputClass="w-full"
          autocomplete="current-password"
          @keyup.enter="deleteUser"
        />
        <small v-if="form.errors.password" class="text-red-500">{{ form.errors.password }}</small>
      </div>
    </div>

    <template #footer>
      <Button label="Cancel" icon="pi pi-times" text @click="closeModal" />
      <Button 
        label="Delete Account" 
        icon="pi pi-trash" 
        severity="danger"
        :loading="form.processing"
        :disabled="form.processing"
        @click="deleteUser" 
      />
    </template>
  </Dialog>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Button, Dialog, Card, Password } from 'primevue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
  password: '',
});

const confirmUserDeletion = () => {
  confirmingUserDeletion.value = true;

  setTimeout(() => passwordInput.value?.$el.querySelector('input').focus(), 250);
};

const deleteUser = () => {
  form.delete(route('current-user.destroy'), {
    preserveScroll: true,
    onSuccess: () => closeModal(),
    onError: () => passwordInput.value?.$el.querySelector('input').focus(),
    onFinish: () => form.reset(),
  });
};

const closeModal = () => {
  confirmingUserDeletion.value = false;
  form.reset();
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