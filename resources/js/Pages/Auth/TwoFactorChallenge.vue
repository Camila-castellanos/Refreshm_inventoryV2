<template>
  <div
    class="bg-surface-0 bg-[white] dark:bg-surface-950 px-6 py-20 md:px-12 flex justify-center items-center lg:flex-row h-screen w-[100vw] h-[100vh]">
    <div class="hidden lg:block lg:w-1/2">
      <img src="/images/BiggerLogo.png" class="mx-auto" alt="" />
      <img src="/images/iphone12.png" class="" alt="" />
    </div>
    <Transition name="fade">
      <div v-if="loaded" class="sm:w-full lg:w-1/3 bg-surface-50 dark:bg-surface-900 p-6 shadow rounded-border mx-auto">
        <div class="text-center mb-8">
          <div class="text-surface-900 dark:text-surface-0 text-3xl font-medium mb-4">Two-Factor Authentication</div>
        </div>

        <div class="mb-4 text-surface-700 dark:text-surface-300 text-sm">
          <template v-if="!recovery">
            Please confirm access to your account by entering the authentication code provided by your authenticator application.
          </template>
          <template v-else> Please confirm access to your account by entering one of your emergency recovery codes. </template>
        </div>

        <form @submit.prevent="submit">
          <div v-if="!recovery">
            <label for="code" class="text-surface-900 dark:text-surface-0 font-medium mb-2 block">Code</label>
            <InputText
              id="code"
              ref="codeInput"
              v-model="form.code"
              type="text"
              inputmode="numeric"
              class="w-full mb-4"
              autofocus
              autocomplete="one-time-code"
              placeholder="Enter authentication code" />
            <div v-if="form.errors.code" class="text-red-500 text-sm mb-4">
              {{ form.errors.code }}
            </div>
          </div>

          <div v-else>
            <label for="recovery_code" class="text-surface-900 dark:text-surface-0 font-medium mb-2 block">Recovery Code</label>
            <InputText
              id="recovery_code"
              ref="recoveryCodeInput"
              v-model="form.recovery_code"
              type="text"
              class="w-full mb-4"
              autocomplete="one-time-code"
              placeholder="Enter recovery code" />
            <div v-if="form.errors.recovery_code" class="text-red-500 text-sm mb-4">
              {{ form.errors.recovery_code }}
            </div>
          </div>

          <Button
            type="submit"
            :label="'Log in'"
            icon="pi pi-lock-open"
            class="w-full"
            :disabled="form.processing"
            :class="{ 'opacity-25': form.processing }" />

          <div class="flex justify-center mt-4">
            <Button
              type="button"
              :label="recovery ? 'Use an authentication code' : 'Use a recovery code'"
              @click.prevent="toggleRecovery"
              class="p-button-text p-button-sm"
              icon="pi pi-sync" />
          </div>
        </form>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { nextTick, ref, onMounted } from "vue";
import { Head, useForm } from "@inertiajs/vue3";
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import axios from "axios";

const loaded = ref(false);
const recovery = ref(false);

onMounted(() => {
  loaded.value = true;
});

const form = useForm({
  code: "",
  recovery_code: "",
});

const recoveryCodeInput = ref(null);
const codeInput = ref(null);

const toggleRecovery = async () => {
  recovery.value = !recovery.value;

  await nextTick();

  if (recovery.value) {
    recoveryCodeInput.value.focus();
    form.code = "";
  } else {
    codeInput.value.focus();
    form.recovery_code = "";
  }
};

const submit = () => {
  form.post(route("two-factor.login"));
};
</script>

<style scoped>
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

.fade-enter-active,
.fade-leave-active {
  transition: all 1.5s ease;
}
</style>
