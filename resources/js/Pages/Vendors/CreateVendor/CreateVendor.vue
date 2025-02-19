<template>
    <Dialog>
        <form @submit.prevent="submitForm">
        <div class="mb-4">
            <label class="block text-sm font-medium">Vendor Name</label>
            <InputText v-model="form.vendor_name" class="w-full" />
        </div>
        
        <div class="mb-4">
            <label class="block text-sm font-medium">Email</label>
            <InputText v-model="form.email" class="w-full" />
        </div>

        <Button label="Create Vendor" class="w-full" type="submit" />
    </form>
    </Dialog>
</template>

<script setup>
import { ref } from 'vue';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import { Dialog } from 'primevue'


const form = ref({
    vendor_name: '',
    email: ''
});

const submitForm = () => {
    console.log('Submitting:', form.value);
    // Aquí iría la lógica para enviar los datos
};
</script>
