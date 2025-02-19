<template>
    <Dialog v-model:visible="isOpen" modal :closable="false" :style="{ width: '50vw' }">
        <template #header>
            <h3 class="text-xl font-bold">Create Vendor</h3>
        </template>
        
        <component :is="modalComponent" v-bind="modalProps" />
        
        <template #footer>
            <Button label="Close" class="p-button-text" @click="close" />
        </template>
    </Dialog>
</template>

<script setup>
import { useModalService } from '@/composables/useModalService';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';

const { isOpen, modalComponent, modalProps, close } = useModalService();
</script>
