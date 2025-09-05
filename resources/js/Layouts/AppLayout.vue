<template>
  <div class="min-h-screen w-screen">
    <Navbar />

    <main class="min-h-screen">
      <slot />
    </main>

    <ConfirmDialog />
    <Toast />
    <DynamicDialog />
    <SessionExpiredDialog />
  </div>
</template>

<script setup>
import Navbar from '@/Components/Navbar.vue';
import SessionExpiredDialog from '@/Components/SessionExpiredDialog.vue';
</script>
