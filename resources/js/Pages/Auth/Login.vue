<template>
    <div class="bg-surface-0 bg-[white] dark:bg-surface-950 px-6 py-20 md:px-12 flex justify-center items-center lg:flex-row h-screen w-[100vw] h-[100vh]">
      <div class="hidden lg:block lg:w-1/2  ">
            <img src="/images/BiggerLogo.png" class="mx-auto" alt="">
            <img src="/images/iphone12.png" class="" alt="">
        </div>
      <Transition name="fade">
        <div v-if="loaded" class="sm:w-full lg:w-1/3 bg-surface-50 dark:bg-surface-900 p-6 shadow rounded-border  mx-auto">
          <div class="text-center mb-8">
            <div class="text-surface-900 dark:text-surface-0 text-3xl font-medium mb-4">Welcome Back</div>
          </div>
  
          <form @submit.prevent="submitLogin" >
            <label for="email" class="text-surface-900 dark:text-surface-0 font-medium mb-2 block">Email</label>
            <InputText id="email" type="text" v-model="form.email" placeholder="Email address" class="w-full mb-4" />
  

          <div v-if="errors.email" class="text-red-500 text-sm">
            {{ errors.email }}
          </div>

            <label for="password" class="text-surface-900 dark:text-surface-0 font-medium mb-2 block">Password</label>
            <InputText id="password" type="password" v-model="form.password" placeholder="Password" class="w-full mb-4" />
  
            <Button label="Sign In" type="submit" icon="pi pi-user" class="w-full" />
          </form>

        </div>
      </Transition>
    </div>
  </template>
  
  <script setup>
  import { ref, onMounted, computed } from 'vue';
  import { router, useForm, usePage } from '@inertiajs/vue3';
  import Button from 'primevue/button';
  import InputText from 'primevue/inputtext';
  

  const loaded = ref(false);
  
  onMounted(() => {
    loaded.value = true;
  });

  const form = useForm({
    email: '',
    password: ''
});

const errors = computed(() => usePage().props.errors || {});

const submitLogin = () => {
    router.post('/login', form, {
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    },
        onFinish: () => form.reset('password')
    });
};

  </script>
  

  
  <style scoped>
  .fade-enter-from, .fade-leave-to {
    opacity: 0;
    transform: translateY(-10px);
  }
  
  .fade-enter-active, .fade-leave-active {
    transition: all 1.5s ease;
  }
  </style>
  