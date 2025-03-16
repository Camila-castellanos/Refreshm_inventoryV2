<template>
  <ActionSection>
    <template #title> Two Factor Authentication </template>

    <template #description> Add additional security to your account using two-factor authentication. </template>

    <template #content>
      <h3 v-if="twoFactorEnabled" class="text-lg font-medium">You have enabled two-factor authentication.</h3>

      <h3 v-else class="text-lg font-medium">You have not enabled two-factor authentication.</h3>

      <div class="mt-3 max-w-xl text-sm">
        <p>
          When two-factor authentication is enabled, you will be prompted for a secure, random token during authentication. You may retrieve
          this token from your phone's Google Authenticator application.
        </p>
      </div>

      <!-- ✅ SOLO SE MUESTRA SI 2FA ESTÁ ACTIVADO -->
      <template v-if="twoFactorEnabled">
        <div v-if="qrCode">
          <div class="mt-4 max-w-xl text-sm">
            <p>
              Two-factor authentication is now enabled. Scan the following QR code using your phone's authenticator application or enter the
              setup key.
            </p>
          </div>

          <div class="mt-4 p-2 inline-block bg-white border border-gray-300 shadow-md" v-html="qrCode"></div>

          <div v-if="setupKey" class="mt-4 max-w-xl text-sm">
            <p class="font-semibold">
              Setup Key: <span>{{ setupKey }}</span>
            </p>
          </div>
        </div>

        <div v-if="recoveryCodes.length > 0">
          <div class="mt-4 max-w-xl text-sm">
            <p class="font-semibold">
              Store these recovery codes in a secure password manager. They can be used to recover access to your account if your two-factor
              authentication device is lost.
            </p>
          </div>

          <div class="grid gap-1 max-w-xl mt-4 px-4 py-4 font-mono text-sm bg-gray-100 rounded-lg">
            <div v-for="code in recoveryCodes" :key="code">
              {{ code }}
            </div>
          </div>
        </div>
      </template>

      <div class="mt-5 flex gap-3">
        <Button
          v-if="!twoFactorEnabled"
          label="Enable"
          icon="pi pi-lock"
          class="p-button-primary"
          :disabled="enabling"
          @click="checkPasswordConfirmation" />

        <Button
          v-if="twoFactorEnabled && recoveryCodes.length > 0"
          label="Regenerate Recovery Codes"
          icon="pi pi-refresh"
          class="p-button-secondary"
          @click="regenerateRecoveryCodes" />

        <Button
          v-if="twoFactorEnabled"
          label="Disable"
          icon="pi pi-times"
          class="p-button-danger"
          :disabled="disabling"
          @click="disableTwoFactorAuthentication" />
      </div>
    </template>
  </ActionSection>

  <!-- PrimeVue Dialog for Password Confirmation -->
  <Dialog v-model:visible="showPasswordDialog" modal header="Confirm Your Password">
    <div class="p-4">
      <InputLabel for="password" value="Password" />
      <TextInput id="password" v-model="passwordForm.password" type="password" class="block mt-1 w-full" autocomplete="current-password" />
      <InputError :message="passwordForm.errors.password" class="mt-2" />
    </div>

    <template #footer>
      <Button label="Cancel" icon="pi pi-times" class="p-button-text" @click="showPasswordDialog = false" />
      <Button label="Confirm" icon="pi pi-check" class="p-button-primary" @click="confirmPasswordAndEnable2FA" />
    </template>
  </Dialog>
</template>

<script setup>
import { ref, computed, watchEffect } from "vue";
import axios from "axios";
import { useForm, usePage, router } from "@inertiajs/vue3";
import ActionSection from "@/Components/ActionSection.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import { Button, Dialog } from "primevue";

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
    router.reload(); // 🔄 Recargar datos después de habilitar 2FA
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
    router.reload(); // 🔄 Recargar para actualizar `twoFactorEnabled`
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
    loadTwoFactorDetails(); // 🔄 Cargar QR y códigos SOLO cuando se activa 2FA
  } else {
    qrCode.value = null;
    setupKey.value = null;
    recoveryCodes.value = [];
  }
});
</script>
