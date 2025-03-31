<template>
    <SessionExpiredDialog />

    <div class="h-[12vh] bg-surface-0 bg-[white] dark:bg-surface-950 ">
      <div class="w-full h-full flex lg:pl-12 pt-8 flex lg:justify-start justify-center">
            <img src="/images/swiftstock_logo.jpeg" draggable="false"  class="object-contain max-h-full max-w-full pointer-events-none" alt="" />
        </div>
    </div>
  <div
    class="bg-surface-0 bg-[white] dark:bg-surface-950 lg:px-12 flex justify-center lg:items-center pt-24 lg:flex-row w-[100vw] h-[88vh]">
    <div class="hidden lg:flex flex-col lg:w-1/2 h-full items-center  justify-center">
        <div class="w-full">
            <img src="/images/iphone12.png" draggable="false" class="object-contain h-full w-full pointer-events-none " alt="" />
        </div>
    </div>
    <Transition name="fade">
      <div v-if="loaded" class="w-full lg:w-1/3 bg-surface-50 dark:bg-surface-900 p-6 md:w-1/2 shadow rounded-border mx-auto">
        <div class="text-center mb-8">
          <div class="text-surface-900 dark:text-surface-0 text-3xl font-medium mb-4">Login</div>
        </div>

        <form @submit.prevent="submitLogin">
          <label for="email" class="text-surface-900 dark:text-surface-0 font-medium mb-2 block">Email</label>
          <InputText id="email" type="text" v-model="form.email" placeholder="Email address" class="w-full mb-4" />

          <div v-if="errors.email" class="text-red-500 text-sm">
            {{ errors.email }}
          </div>

          <label for="password" class="text-surface-900 dark:text-surface-0 font-medium mb-2 block">Password</label>
          <InputText id="password" type="password" v-model="form.password" placeholder="Password" class="w-full mb-4" />

          
          <div class="flex items-center mt-3">
            <Checkbox inputId="remember" name="remember" v-model="form.remember" :binary="true" class="mr-2" />
            <label class="text-surface-900 dark:text-surface-0 font-medium " for="remember">
              Remember Me
            </label>
          </div>
        
        
          

          <Button label="Sign In" type="submit" icon="pi pi-user" class="w-full mt-6" />
          <Button label="Sign Up" icon="pi pi-user-add" @click="handleClick" :disabled="isLoading" class="w-full mt-6" />
        </form>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, onBeforeUnmount } from "vue";
import { router, useForm, usePage } from "@inertiajs/vue3";
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import { Checkbox } from "primevue";
import axios from "axios";
import SessionExpiredDialog from "@/Components/SessionExpiredDialog.vue";
import { renewCsrfToken } from '@/Utils/csrf.js'; 

const loaded = ref(false);

onMounted(() => {
  loaded.value = true;
  window.addEventListener("beforeunload", handleBeforeUnload);
});

const form = useForm({
  email: "",
  password: "",
  remember: false,
});

const errors = computed(() => usePage().props.errors || {});

const isLoading = ref(false);

onBeforeUnmount(() => {
  window.removeEventListener("beforeunload", handleBeforeUnload);
});

function handleBeforeUnload(event: Event) {
  // Solo si el usuario está autenticado (puedes agregar más lógica si es necesario)
  if (window.location.pathname !== "/login" && window.location.pathname !== "/register") {
    navigator.sendBeacon(route("logout"));
  }
}

const handleClick = async () => {
  isLoading.value = true;
  await axios.post(route('logout'), {}, { headers: { 'X-Inertia': false } });
  router.visit('/register');
  isLoading.value = false;
};


const submitLogin = () => {
  form.post("/login", {
    onFinish: () => form.reset("password"),
    onSuccess: async () => {
      await renewCsrfToken();
      router.visit(route('dashboard')); 
    },
    onError: (errors) => {
            console.error('Login failed:', errors);
        },
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
