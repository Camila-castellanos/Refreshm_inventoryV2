<template>
  <SessionExpiredDialog />

  <div class="h-[12vh] bg-surface-0 bg-[white] dark:bg-surface-950 ">
    <div class="w-full h-full flex lg:pl-12 pt-8 flex lg:justify-start justify-center">
      <img src="/images/swiftstock_logo.jpeg" draggable="false"
        class="object-contain max-h-full max-w-full pointer-events-none" alt="" />
    </div>
  </div>
  <div
    class="bg-surface-0 bg-[white] dark:bg-surface-950 lg:px-12 flex justify-center lg:items-center pt-24 lg:flex-row h-screen w-[100vw] h-[86vh]">
    <div class="hidden lg:flex flex-col lg:w-1/2 h-full items-center  justify-center">
      <div class="h-[80%] w-full">
        <img src="/images/iphone12.png" draggable="false" class="object-contain h-full w-full pointer-events-none "
          alt="" />
      </div>
    </div>
    <!-- Sección Derecha (Formulario de Registro) -->
    <Transition name="fade">
      <div v-if="loaded"
        class="w-full lg:w-1/3 bg-surface-50 dark:bg-surface-900 p-6 md:w-1/2 shadow rounded-border mx-auto">
        <div class="text-center mb-8">
          <div class="text-surface-900 dark:text-surface-0 text-3xl font-medium mb-4">Create an Account</div>
        </div>

        <form @submit.prevent="submit">
          <!-- Company name -->


          <label v-if="companyName" for="companyName"
            class="text-surface-900 dark:text-surface-0 font-medium mb-2 block">Invitation
            to join</label>

          <label v-else for="companyName" class="text-surface-900 dark:text-surface-0 font-medium mb-2 block">Company
            Name</label>




          <InputText v-if="companyName" id="companyName" type="text" v-model="companyName"
            placeholder="Enter your company name" disabled="true" class="w-full mb-4" />

          <InputText v-else id="companyName" type="text" v-model="form.companyName"
            placeholder="Enter your company name" class="w-full mb-4" />


          <div v-if="form.errors.companyName" class="text-red-500 text-sm">{{ form.errors.companyName }}</div>
          <!-- Nombre -->
          <label for="name" class="text-surface-900 dark:text-surface-0 font-medium mb-2 block">Full Name</label>
          <InputText id="name" type="text" v-model="form.name" placeholder="Enter your full name" class="w-full mb-4" />
          <div v-if="form.errors.name" class="text-red-500 text-sm">{{ form.errors.name }}</div>

          <!-- Email -->
          <label for="email" class="text-surface-900 dark:text-surface-0 font-medium mb-2 block">Email</label>
          <InputText id="email" type="email" v-model="form.email" placeholder="Enter your email" class="w-full mb-4" />
          <div v-if="form.errors.email" class="text-red-500 text-sm">{{ form.errors.email }}</div>

          <!-- Contraseña -->
          <label for="password" class="text-surface-900 dark:text-surface-0 font-medium mb-2 block">Password</label>
          <Password toggle-mask fluid id="password" v-model="form.password" placeholder="Enter your password"
            class="w-full mb-4" />
          <div v-if="form.errors.password" class="text-red-500 text-sm">{{ form.errors.password }}</div>

          <!-- Confirmación de Contraseña -->
          <label for="password_confirmation" class="text-surface-900 dark:text-surface-0 font-medium mb-2 block">Confirm
            Password</label>
          <Password toggle-mask fluid id="password_confirmation" :feedback="false" v-model="form.password_confirmation"
            placeholder="Re-enter your password" class="w-full mb-4" />
          <div v-if="form.errors.password_confirmation" class="text-red-500 text-sm">{{
            form.errors.password_confirmation }}</div>

          <!-- Aceptación de términos -->
          <div v-if="$page.props.jetstream.hasTermsAndPrivacyPolicyFeature" class="flex items-center mt-4">
            <Checkbox id="terms" v-model:checked="form.terms" name="terms" required class="mr-2" />
            <label for="terms" class="text-sm text-gray-600 dark:text-gray-400">
              I agree to the
              <a target="_blank" :href="route('terms.show')" class="underline text-indigo-600">Terms</a> and
              <a target="_blank" :href="route('policy.show')" class="underline text-indigo-600">Privacy Policy</a>.
            </label>
          </div>
          <div v-if="form.errors.terms" class="text-red-500 text-sm">{{ form.errors.terms }}</div>

          <!-- Botón de registro -->
          <Button label="Register" type="submit" icon="pi pi-user" class="w-full mt-4" :disabled="form.processing" />

          <!-- Enlace a login -->
          <div class="text-center mt-4">
            <span class="text-gray-600 dark:text-gray-400">Already have an account?</span>
            <a :href="route('login')" class="text-indigo-600 underline ml-1">Sign in</a>
          </div>
        </form>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import Checkbox from "@/Components/Checkbox.vue";
import { Password } from "primevue";
import axios from "axios";
import SessionExpiredDialog from "@/Components/SessionExpiredDialog.vue";

const loaded = ref(false);
const companyName = ref()

onMounted(() => {
  loaded.value = true;
  const urlParams = new URLSearchParams(window.location.search);
  const encodedCompany = urlParams.get("company")
  companyName.value = encodedCompany ? atob(encodedCompany) : "";
  console.log(companyName)
});

const form = useForm({
  name: "",
  companyName: "",
  email: "",
  password: "",
  password_confirmation: "",
  invitation: false,
  terms: false,
});

const submit = () => {
  if (companyName.value.length > 0) {
    form.invitation = true
    form.companyName = companyName.value
  }

  form.post(route("register"), {
    onFinish: () => form.reset("password", "password_confirmation"),
  });
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
