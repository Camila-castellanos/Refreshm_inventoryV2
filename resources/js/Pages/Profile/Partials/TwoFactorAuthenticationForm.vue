<template>
  <div class="flex justify-between">
    <div class="flex items-start mb-2 w-1/3">
      <div>
        <h2 class="text-xl font-medium mb-1">Two Factor Authentication</h2>
        <p class="text-sm">Add additional security to your account using two-factor authentication.</p>
      </div>
    </div>

    <Card class="shadow-none w-2/3">
      <template #content>
        <div class="space-y-6">
          <h3 v-if="twoFactorEnabled" class="text-lg font-medium">You have enabled two-factor authentication.</h3>
          <h3 v-else class="text-lg font-medium">You have not enabled two-factor authentication.</h3>

          <div class="max-w-xl text-sm">
            <p>
              When two-factor authentication is enabled, you will be prompted for a secure, random token during authentication. 
              You may retrieve this token from your phone's Google Authenticator application.
            </p>
          </div>

          <!-- Only shown when 2FA is enabled -->
          <template v-if="twoFactorEnabled">
            <div v-if="qrCode" class="space-y-4">
              <div class="max-w-xl text-sm">
                <p>
                  Two-factor authentication is now enabled. Scan the following QR code using your phone's authenticator 
                  application or enter the setup key.
                </p>
              </div>

              <div class="p-2 inline-block bg-white border border-gray-300 shadow-md" v-html="qrCode"></div>

              <div v-if="setupKey" class="max-w-xl text-sm">
                <p class="font-semibold">
                  Setup Key: <span>{{ setupKey }}</span>
                </p>
              </div>
            </div>

            <div v-if="recoveryCodes.length > 0" class="space-y-4">
              <div class="max-w-xl text-sm">
                <p class="font-semibold">
                  Store these recovery codes in a secure password manager. They can be used to recover access to your account 
                  if your two-factor authentication device is lost.
                </p>
              </div>

              <div class="grid gap-1 max-w-xl px-4 py-4 font-mono text-sm bg-gray-100 rounded-lg">
                <div v-for="code in recoveryCodes" :key="code">
                  {{ code }}
                </div>
              </div>
            </div>
          </template>

          <div class="flex gap-3">
            <Button
              v-if="!twoFactorEnabled"
              label="Enable"
              icon="pi pi-lock"
              :loading="enabling"
              :disabled="enabling"
              @click="checkPasswordConfirmation" />

            <Button
              v-if="twoFactorEnabled && recoveryCodes.length > 0"
              label="Regenerate Recovery Codes"
              icon="pi pi-refresh"
              severity="secondary"
              @click="regenerateRecoveryCodes" />

            <Button
              v-if="twoFactorEnabled"
              label="Disable"
              icon="pi pi-times"
              severity="danger"
              :loading="disabling"
              :disabled="disabling"
              @click="disableTwoFactorAuthentication" />
          </div>
        </div>
      </template>
    </Card>
  </div>

  <!-- Password Confirmation Dialog -->
  <Dialog v-model:visible="showPasswordDialog" modal header="Confirm Your Password" class="max-w-md">
    <div class="p-4">
      <label for="password" class="block text-sm font-medium mb-1">Password</label>
      <Password
        id="password"
        v-model="passwordForm.password"
        toggleMask
        :feedback="false"
        class="w-full"
        inputClass="w-full"
        autocomplete="current-password"
      />
      <small v-if="passwordForm.errors.password" class="text-red-500">{{ passwordForm.errors.password }}</small>
    </div>

    <template #footer>
      <Button label="Cancel" icon="pi pi-times" text @click="showPasswordDialog = false" />
      <Button label="Confirm" icon="pi pi-check" @click="confirmPasswordAndEnable2FA" />
    </template>
  </Dialog>
</template>

<script setup>
import { ref, watchEffect } from "vue";
import axios from "axios";
import { useForm, usePage, router } from "@inertiajs/vue3";
import { Button, Dialog, Card, Password } from "primevue";

const page = usePage();
const enabling = ref(false);
const disabling = ref(false);
const showPasswordDialog = ref(false);
const qrCode = ref(null);
const setupKey = ref(null);
const recoveryCodes = ref([]);

const passwordForm = useForm({ password: "" });

const twoFactorEnabled = ref(false);

const checkPasswordConfirmation = async () => {
  enabling.value = true;

  try {
    const { data } = await axios.get(route("password.confirmation"));

    if (data.confirm) {
      enableTwoFactorAuthentication();
    } else {
      showPasswordDialog.value = true;
    }
  } finally {
    enabling.value = false;
  }
};

const confirmPasswordAndEnable2FA = async () => {
  try {
    await axios.post(route("password.confirm.store"), { password: passwordForm.password });

    showPasswordDialog.value = false;
    passwordForm.reset();
    enableTwoFactorAuthentication();
  } catch (error) {
    console.error(error);
  }
};

const enableTwoFactorAuthentication = async () => {
  try {
    await axios.post(route("two-factor.enable"));
    router.reload(); // Reload data after enabling 2FA
  } catch (error) {
    console.error("Error enabling 2FA:", error);
  }
};

const regenerateRecoveryCodes = async () => {
  try {
    await axios.post(route("two-factor.recovery-codes"));
    await showRecoveryCodes();
  } catch (error) {
    console.error("Error regenerating recovery codes:", error);
  }
};

const disableTwoFactorAuthentication = async () => {
  disabling.value = true;
  try {
    await axios.delete(route("two-factor.disable"));
    router.reload(); // Reload to update `twoFactorEnabled`
  } catch (error) {
    console.error("Error disabling 2FA:", error);
  } finally {
    disabling.value = false;
  }
};

const loadTwoFactorDetails = async () => {
  await Promise.all([showQrCode(), showSetupKey(), showRecoveryCodes()]);
};

const showQrCode = async () => {
  try {
    const { data } = await axios.get(route("two-factor.qr-code"));
    qrCode.value = data.svg;
  } catch (error) {
    console.error("Error fetching QR Code:", error);
  }
};

const showSetupKey = async () => {
  try {
    const { data } = await axios.get(route("two-factor.secret-key"));
    setupKey.value = data.secretKey;
  } catch (error) {
    console.error("Error fetching Setup Key:", error);
  }
};

const showRecoveryCodes = async () => {
  try {
    const { data } = await axios.get(route("two-factor.recovery-codes"));
    recoveryCodes.value = data;
  } catch (error) {
    console.error("Error fetching Recovery Codes:", error);
  }
};

watchEffect(() => {
  twoFactorEnabled.value = page.props.auth.user?.two_factor_enabled ?? false;

  if (twoFactorEnabled.value) {
    loadTwoFactorDetails(); // Load QR and codes ONLY when 2FA is activated
  } else {
    qrCode.value = null;
    setupKey.value = null;
    recoveryCodes.value = [];
  }
});
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