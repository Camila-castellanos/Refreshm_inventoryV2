<template>
  <div class="bg-gray-100 min-h-screen">
    <nav class="bg-white shadow-md py-4">
      <div class="container mx-auto flex items-center justify-between px-4">
        <div class="flex items-center">
          <img src="/images/swiftstock_logo.jpeg" alt="Your Logo" class="h-12">
        </div>
        <div></div>
      </div>
    </nav>

    <main class="container mx-auto py-8 px-4">
      <InventoryList :items=items></InventoryList>
    </main>
  </div>
</template>

<script setup>

import { defineProps } from 'vue';
import InventoryList from './InventoryList.vue';

const props = defineProps({
  items: { type: Array }
});


</script>