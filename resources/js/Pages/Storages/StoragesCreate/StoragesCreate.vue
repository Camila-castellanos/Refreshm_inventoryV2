<template>
<form class="grid grid-cols-2 gap-4" @submit.prevent="submitForm">

              <div class="col-span-2">
                <label class="block font-medium">Name <span class="text-red-500">*</span></label>
                <InputText v-model="form.name" class="w-full" placeholder="Storage name" />
                <!-- <small class="text-red-500" v-if="errors.vendor">{{ errors.vendor }}</small> -->
            </div>

            <div class="col-span-2">
                <label class="block font-medium">Limit</label>
                <InputText v-model="form.limit" class="w-full" placeholder="Storage limit" />
                <!-- <small class="text-red-500" v-if="errors.first_name">{{ errors.first_name }}</small> -->
            </div>

            <div class="flex col-span-2 justify-end mt-6 space-x-4">
            <Button label="Reset" severity="secondary" @click="form.reset()" />
            <Button label="Save" type="submit" severity="primary" :loading="form.processing" />
          </div>


</form>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { InputText } from 'primevue';
import { ref, defineEmits } from 'vue';
import axios from 'axios';

const emit = defineEmits(['success']);

const form = useForm({
    name: '',
    limit: ''
})

function submitForm() {
    axios.post('/storages', { storages: [form] })
        .then(res => {
            console.log(res.data);
            emit('success');
        })
        .catch(error => console.error('Error creating storage:', error));
}
</script>