<template>
    <div class="bg-surface-50 dark:bg-surface-950 px-6 py-20 md:px-12 flex justify-center items-center w-[100vw] h-[100vh]">
      <Transition name="fade">
        <div v-if="loaded" class="bg-surface-0 dark:bg-surface-900 p-6 shadow rounded-border w-full lg:w-1/3 mx-auto">
          <div class="text-center mb-8">
            <svg class="mb-4 mx-auto fill-surface-600 dark:fill-surface-200 h-16" viewBox="0 0 30 32">
              <path fill-rule="evenodd" clip-rule="evenodd" d="M20.7207 6.18211L14.9944 3.11148L3.46855 9.28678L0.579749 7.73444L14.9944 0L23.6242 4.62977L20.7207 6.18211ZM14.9996 12.3574L26.5182 6.1821L29.4216 7.73443L14.9996 15.4621L6.37724 10.8391L9.27337 9.28677L14.9996 12.3574Z" />
            </svg>
            <div class="text-surface-900 dark:text-surface-0 text-3xl font-medium mb-4">Welcome Back</div>
          </div>
  
          <div>
            <label for="email1" class="text-surface-900 dark:text-surface-0 font-medium mb-2 block">Email</label>
            <InputText id="email1" type="text" placeholder="Email address" class="w-full mb-4" />
  
            <label for="password1" class="text-surface-900 dark:text-surface-0 font-medium mb-2 block">Password</label>
            <InputText id="password1" type="password" placeholder="Password" class="w-full mb-4" />
  
            <Button label="Sign In" icon="pi pi-user" class="w-full" />
          </div>
        </div>
      </Transition>
    </div>
  </template>
  
  <script setup>
  import { ref, onMounted } from 'vue';
  import Button from 'primevue/button';
  import InputText from 'primevue/inputtext';
  
  const loaded = ref(false);
  
  onMounted(() => {
    loaded.value = true;
  });
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
  