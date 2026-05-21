<template>
  <PortalLayout title="Account">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 bg-surface-50 dark:bg-surface-900">
      <div class="w-full max-w-md">

        <!-- Header -->
        <div class="text-center mb-8">
          <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-white dark:bg-surface-800 mb-4 shadow-lg border border-surface-100 dark:border-surface-700">
            <i class="pi pi-mobile text-surface-900 dark:text-surface-0 !text-3xl"></i>
          </div>
          <h1 class="text-3xl font-bold text-surface-900 dark:text-surface-0 mb-1">Customer Portal</h1>
          <p class="text-surface-500 dark:text-surface-400 text-sm">Manage your device requests and account</p>
        </div>

        <!-- Main tab switcher — sliding pill -->
        <div class="relative flex bg-surface-100 dark:bg-surface-800 rounded-2xl p-1 mb-6 shadow-inner">
          <div
            class="absolute top-1 bottom-1 bg-white dark:bg-surface-700 rounded-xl shadow transition-transform duration-300 ease-in-out"
            :style="{ width: 'calc(50% - 4px)', transform: activeTab === 'signin' ? 'translateX(0)' : 'translateX(calc(100% + 8px))' }"
          ></div>
          <button
            id="tab-signin"
            @click="activeTab = 'signin'"
            class="relative z-10 flex-1 py-2.5 text-sm font-semibold rounded-xl transition-colors duration-200"
            :class="activeTab === 'signin' ? 'text-primary-600 dark:text-primary-400' : 'text-surface-500 dark:text-surface-400 hover:text-surface-700 dark:hover:text-surface-200'"
          >
            <i class="pi pi-sign-in mr-1.5"></i>Sign In
          </button>
          <button
            id="tab-register"
            @click="activeTab = 'register'"
            class="relative z-10 flex-1 py-2.5 text-sm font-semibold rounded-xl transition-colors duration-200"
            :class="activeTab === 'register' ? 'text-primary-600 dark:text-primary-400' : 'text-surface-500 dark:text-surface-400 hover:text-surface-700 dark:hover:text-surface-200'"
          >
            <i class="pi pi-user-plus mr-1.5"></i>Create Account
          </button>
        </div>

        <!-- Card -->
        <div class="bg-white dark:bg-surface-800 rounded-2xl shadow-lg p-8 border border-surface-100 dark:border-surface-700">

          <!-- ══════ SIGN IN ══════ -->
          <Transition name="fade" mode="out-in">
            <div v-if="activeTab === 'signin'" key="signin">
              <div class="mb-6">
                <h2 class="text-xl font-bold text-surface-900 dark:text-surface-0">Welcome back</h2>
                <p class="text-surface-500 dark:text-surface-400 text-sm mt-1">Sign in with your email and password</p>
              </div>

              <div v-if="flashSuccess || activationSuccess || flashError" 
                   class="flex items-center gap-3 bg-white dark:bg-surface-800 border border-surface-200 dark:border-surface-700 p-4 rounded-xl mb-6 text-sm shadow-sm animate-fade-in"
                   :class="{ 'border-l-4 border-l-green-500': flashSuccess || activationSuccess, 'border-l-4 border-l-red-500': flashError }">
                <i :class="[
                    'pi text-lg flex-shrink-0',
                    (flashSuccess || activationSuccess) ? 'pi-check-circle text-green-500' : 'pi-exclamation-circle text-red-500'
                ]"></i>
                <span class="text-surface-700 dark:text-surface-200 font-medium">
                  {{ flashSuccess || activationSuccess || flashError }}
                </span>
              </div>

              <form @submit.prevent="submitLogin" class="space-y-4">
                <div>
                  <label for="login-email" class="block text-surface-700 dark:text-surface-200 text-sm font-medium mb-1.5">Email address</label>
                  <InputText id="login-email" type="email" v-model="loginForm.email" placeholder="your@email.com" class="w-full" :class="{ 'p-invalid': loginErrors.email }" />
                  <small v-if="loginErrors.email" class="text-red-500 mt-1 block">{{ loginErrors.email }}</small>
                </div>
                <div>
                  <label for="login-password" class="block text-surface-700 dark:text-surface-200 text-sm font-medium mb-1.5">Password</label>
                  <InputText id="login-password" type="password" v-model="loginForm.password" placeholder="Your password" class="w-full" :class="{ 'p-invalid': loginErrors.password }" />
                  <div class="flex justify-end mt-1">
                    <button type="button" @click="showForgotModal = true" class="text-xs text-primary-500 hover:text-primary-600 font-medium">Forgot password?</button>
                  </div>
                  <small v-if="loginErrors.password" class="text-red-500 mt-1 block">{{ loginErrors.password }}</small>
                </div>
                <Button label="Sign in" type="submit" icon="pi pi-sign-in" class="w-full" :loading="loginLoading" />
              </form>
            </div>

            <!-- ══════ CREATE ACCOUNT ══════ -->
            <div v-else key="register">
              <div class="mb-5">
                <h2 class="text-xl font-bold text-surface-900 dark:text-surface-0">Get access</h2>
                <p class="text-surface-500 dark:text-surface-400 text-sm mt-1">Choose the option that applies to you</p>
              </div>

              <!-- Sub-tab — sliding pill -->
              <div class="relative flex bg-surface-100 dark:bg-surface-700 rounded-xl p-1 mb-6">
                <div
                  class="absolute top-1 bottom-1 bg-white dark:bg-surface-600 rounded-lg shadow transition-transform duration-300 ease-in-out"
                  :style="{ width: 'calc(50% - 4px)', transform: registerMode === 'new' ? 'translateX(0)' : 'translateX(calc(100% + 8px))' }"
                ></div>
                <button
                  id="subtab-new"
                  @click="registerMode = 'new'"
                  class="relative z-10 flex-1 py-2 text-xs font-semibold rounded-lg transition-colors duration-200"
                  :class="registerMode === 'new' ? 'text-primary-600 dark:text-primary-400' : 'text-surface-500 dark:text-surface-300 hover:text-surface-700 dark:hover:text-surface-100'"
                >
                  New customer
                </button>
                <button
                  id="subtab-activate"
                  @click="registerMode = 'activate'"
                  class="relative z-10 flex-1 py-2 text-xs font-semibold rounded-lg transition-colors duration-200"
                  :class="registerMode === 'activate' ? 'text-primary-600 dark:text-primary-400' : 'text-surface-500 dark:text-surface-300 hover:text-surface-700 dark:hover:text-surface-100'"
                >
                  Already in our system
                </button>
              </div>

              <Transition name="fade" mode="out-in">
                <!-- New customer form -->
                <div v-if="registerMode === 'new'" key="new-form">
                  <form @submit.prevent="submitRegister" class="space-y-4">
                    <div>
                      <label for="reg-name" class="block text-surface-700 dark:text-surface-200 text-sm font-medium mb-1.5">Full name</label>
                      <InputText id="reg-name" type="text" v-model="registerForm.name" placeholder="John Doe" class="w-full" :class="{ 'p-invalid': registerErrors.name }" />
                      <small v-if="registerErrors.name" class="text-red-500 mt-1 block">{{ registerErrors.name }}</small>
                    </div>
                    <div>
                      <label for="reg-email" class="block text-surface-700 dark:text-surface-200 text-sm font-medium mb-1.5">Email address</label>
                      <InputText id="reg-email" type="email" v-model="registerForm.email" placeholder="your@email.com" class="w-full" :class="{ 'p-invalid': registerErrors.email }" />
                      <small v-if="registerErrors.email" class="text-red-500 mt-1 block">{{ registerErrors.email }}</small>
                    </div>
                    <div>
                      <label for="reg-password" class="block text-surface-700 dark:text-surface-200 text-sm font-medium mb-1.5">Password</label>
                      <InputText id="reg-password" type="password" v-model="registerForm.password" placeholder="Minimum 8 characters" class="w-full" :class="{ 'p-invalid': registerErrors.password }" />
                      <small v-if="registerErrors.password" class="text-red-500 mt-1 block">{{ registerErrors.password }}</small>
                    </div>
                    <div>
                      <label for="reg-confirm" class="block text-surface-700 dark:text-surface-200 text-sm font-medium mb-1.5">Confirm password</label>
                      <InputText id="reg-confirm" type="password" v-model="registerForm.password_confirmation" placeholder="Repeat your password" class="w-full" :class="{ 'p-invalid': registerErrors.password_confirmation }" />
                      <small v-if="registerErrors.password_confirmation" class="text-red-500 mt-1 block">{{ registerErrors.password_confirmation }}</small>
                    </div>
                    <Button label="Create account" type="submit" icon="pi pi-user-plus" class="w-full" :loading="registerLoading" />
                  </form>
                </div>

                <!-- Activation link form -->
                <div v-else key="activate-form">
                  <!-- Info box: Clean & Minimalist -->
                  <div class="flex items-start gap-4 bg-white dark:bg-surface-800 border border-surface-200 dark:border-surface-700 p-4 rounded-xl mb-6 shadow-sm">
                    <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-surface-50 dark:bg-surface-700 flex items-center justify-center">
                      <i class="pi pi-envelope text-blue-500 text-lg"></i>
                    </div>
                    <div class="text-surface-600 dark:text-surface-400 leading-relaxed text-sm">
                      <span class="font-bold text-surface-900 dark:text-surface-0 block mb-0.5">Activation Method</span>
                      Enter your email to receive a secure link to set your password and access your account.
                    </div>
                  </div>

                  <!-- Dynamic Status Message: White background, gray text -->
                  <div v-if="activationSuccess || activationErrors.activation_email" 
                       class="flex items-center gap-3 bg-white dark:bg-surface-800 border border-surface-200 dark:border-surface-700 p-4 rounded-xl mb-6 text-sm shadow-sm animate-fade-in"
                       :class="{ 'border-l-4 border-l-green-500': activationSuccess, 'border-l-4 border-l-red-500': activationErrors.activation_email }">
                    <i :class="[
                        'pi text-lg',
                        activationSuccess ? 'pi-check-circle text-green-500' : 'pi-exclamation-circle text-red-500'
                    ]"></i>
                    <span class="text-surface-700 dark:text-surface-200 font-medium">
                        {{ activationSuccess || activationErrors.activation_email }}
                    </span>
                  </div>

                  <form @submit.prevent="submitActivation" class="space-y-4">
                    <div>
                      <label for="activate-email" class="block text-surface-700 dark:text-surface-200 text-sm font-medium mb-1.5">Email address</label>
                      <InputText id="activate-email" type="email" v-model="activationForm.email" placeholder="your@email.com" class="w-full" :class="{ 'p-invalid': activationErrors.activation_email }" />
                      <small v-if="activationErrors.activation_email" class="text-red-500 mt-1 block">{{ activationErrors.activation_email }}</small>
                    </div>
                    <Button label="Send activation link" type="submit" icon="pi pi-envelope" class="w-full" :loading="activationLoading" />
                  </form>
                </div>
              </Transition>
            </div>
          </Transition>

        </div>
      </div>
    </div>

    <!-- Forgot Password Modal -->
    <Dialog v-model:visible="showForgotModal" header="Recover Password" modal class="w-full max-w-sm mx-4">
      <div class="mb-6">
        <p class="text-surface-600 dark:text-surface-400 text-sm leading-relaxed">
          Enter your email and we'll send you a secure link to reset your password.
        </p>
      </div>

      <form @submit.prevent="submitForgot" class="space-y-4">
        <div v-if="forgotForm.errors.email" 
             class="flex items-center gap-3 bg-white dark:bg-surface-800 border border-surface-200 dark:border-surface-700 p-3 rounded-xl mb-4 text-xs shadow-sm border-l-4 border-l-red-500 animate-fade-in">
          <i class="pi pi-exclamation-circle text-red-500"></i>
          <span class="text-surface-700 dark:text-surface-200 font-medium">{{ forgotForm.errors.email }}</span>
        </div>

        <div>
          <label for="forgot-email" class="block text-surface-700 dark:text-surface-200 text-sm font-medium mb-1.5">Email address</label>
          <InputText id="forgot-email" type="email" v-model="forgotForm.email" placeholder="your@email.com" class="w-full" :class="{ 'p-invalid': forgotForm.errors.email }" autofocus />
        </div>
        
        <div class="flex justify-end gap-2 pt-2">
          <Button type="button" label="Cancel" severity="secondary" text @click="showForgotModal = false" />
          <Button type="submit" label="Send link" icon="pi pi-paper-plane" :loading="forgotForm.processing" />
        </div>
      </form>
    </Dialog>
  </PortalLayout>
</template>

<script setup lang="ts">
import { ref, computed, watch } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import Dialog from "primevue/dialog";
import PortalLayout from "@/Layouts/PortalLayout.vue";
import { useToast } from "primevue/usetoast";

const page = usePage();
const toast = useToast();

const flashSuccess      = computed(() => (page.props.flash as any)?.success);
const activationSuccess = computed(() => (page.props.flash as any)?.activation_success);
const flashError        = computed(() => (page.props.flash as any)?.error);

// Watch for activation success to show a toast
watch(activationSuccess, (newVal) => {
  if (newVal) {
    toast.add({
      severity: 'success',
      summary: 'Information',
      detail: newVal,
      life: 5000
    });
  }
}, { immediate: true });

const activeTab    = ref<'signin' | 'register'>('signin');
const registerMode = ref<'new' | 'activate'>('new');
const showForgotModal = ref(false);

// Watch for modal visibility to clear form
watch(showForgotModal, (newVal) => {
  if (newVal) {
    forgotForm.clearErrors();
    forgotForm.reset();
  }
});

// ── Sign In ──────────────────────────────────────────
const loginLoading = ref(false);
const loginForm    = useForm({ email: '', password: '' });
const loginErrors  = computed(() => (page.props.errors as any) || {});

const submitLogin = () => {
  loginLoading.value = true;
  loginForm.post(route('public-store.portal.login'), {
    onSuccess: () => { loginLoading.value = false; },
    onError:   () => { loginLoading.value = false; },
  });
};

// ── Forgot Password ──────────────────────────────────
const forgotForm = useForm({ email: '' });

const submitForgot = () => {
  forgotForm.post(route('public-store.portal.login.forgot-password'), {
    onSuccess: () => {
      showForgotModal.value = false;
      forgotForm.reset();
    },
  });
};

// ── New Registration ─────────────────────────────────
const registerLoading = ref(false);
const registerErrors  = ref<Record<string, string>>({});
const registerForm    = useForm({ name: '', email: '', password: '', password_confirmation: '' });

const submitRegister = () => {
  registerLoading.value = true;
  registerErrors.value  = {};
  registerForm.post(route('public-store.portal.register'), {
    onSuccess: () => { registerLoading.value = false; },
    onError: (err) => {
      registerErrors.value  = err;
      registerLoading.value = false;
    },
  });
};

// ── Activation Link ──────────────────────────────────
const activationLoading = ref(false);
const activationErrors  = computed(() => (page.props.errors as any) || {});
const activationForm    = useForm({ email: '' });

const submitActivation = () => {
  activationLoading.value = true;
  // Clear previous flash messages if possible by manual reset or just let Inertia handle it
  activationForm.post(route('public-store.portal.login.activate'), {
    onSuccess: () => {
      activationForm.reset();
      activationLoading.value = false;
    },
    onError: () => { 
      activationLoading.value = false; 
    },
    onFinish: () => {
      activationLoading.value = false;
    }
  });
};
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}
.fade-enter-from {
  opacity: 0;
  transform: translateY(6px);
}
.fade-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}
</style>