<template>
  <div class="flex justify-between">
    <div class="flex items-start mb-2 w-1/3">
      <div>
        <h2 class="text-xl font-medium mb-1">Browser Sessions</h2>
        <p class="text-sm">Manage and log out your active sessions on other browsers and devices.</p>
      </div>
    </div>

    <Card class="shadow-none w-2/3">
      <template #content>
        <div class="space-y-6">
          <div class="max-w-xl text-sm">
            If necessary, you may log out of all of your other browser sessions across all of your devices. 
            Some of your recent sessions are listed below; however, this list may not be exhaustive. 
            If you feel your account has been compromised, you should also update your password.
          </div>

          <!-- Other Browser Sessions -->
          <div v-if="sessions.length > 0" class="space-y-4">
            <Card v-for="(session, i) in sessions" :key="i" class="!bg-[var(--bg-item)]">
              <template #content>
                <div class="flex items-center">
                  <div>
                    <i v-if="session.agent.is_desktop" class="pi pi-desktop text-gray-500 text-2xl"></i>
                    <i v-else class="pi pi-mobile text-gray-500 text-2xl"></i>
                  </div>

                  <div class="ms-3">
                    <div class="text-sm font-medium">
                      {{ session.agent.platform ? session.agent.platform : 'Unknown' }} - {{ session.agent.browser ? session.agent.browser : 'Unknown' }}
                    </div>

                    <div>
                      <div class="text-xs text-gray-500">
                        {{ session.ip_address }},

                        <span v-if="session.is_current_device" class="text-green-500 font-semibold">This device</span>
                        <span v-else>Last active {{ session.last_active }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </template>
            </Card>
          </div>

          <div class="flex items-center">
            <Button 
              label="Log Out Other Browser Sessions" 
              icon="pi pi-sign-out" 
              @click="confirmLogout"
            />
            <span v-if="form.recentlySuccessful" class="text-green-500 ml-3">Done.</span>
          </div>
        </div>
      </template>
    </Card>
  </div>

  <!-- Log Out Confirmation Dialog -->
  <Dialog 
    v-model:visible="confirmingLogout" 
    modal 
    header="Log Out Other Browser Sessions" 
    class="max-w-md"
  >
    <div class="p-4">
      <p class="mb-4">
        Please enter your password to confirm you would like to log out of your other browser sessions across all of your devices.
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
          @keyup.enter="logoutOtherBrowserSessions"
        />
        <small v-if="form.errors.password" class="text-red-500">{{ form.errors.password }}</small>
      </div>
    </div>

    <template #footer>
      <Button label="Cancel" icon="pi pi-times" text @click="closeModal" />
      <Button 
        label="Log Out Other Browser Sessions" 
        icon="pi pi-sign-out" 
        :loading="form.processing"
        :disabled="form.processing"
        @click="logoutOtherBrowserSessions" 
      />
    </template>
  </Dialog>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Button, Dialog, Card, Password } from 'primevue';
import axios from 'axios';

const props = defineProps({
  sessions: Array,
});

const confirmingLogout = ref(false);
const passwordInput = ref(null);

const form = useForm({
  password: '',
});

const confirmLogout = () => {
  confirmingLogout.value = true;

  setTimeout(() => passwordInput.value?.$el.querySelector('input').focus(), 250);
};

const logoutOtherBrowserSessions = () => {
  axios.delete(route('other-browser-sessions.destroy'))
    .then(() => {
      closeModal();
      form.reset();
    })
    .catch((error) => {
      if (error.response?.status === 422) {
        form.setError(error.response.data.errors);
      }
    });
};

const closeModal = () => {
  confirmingLogout.value = false;
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