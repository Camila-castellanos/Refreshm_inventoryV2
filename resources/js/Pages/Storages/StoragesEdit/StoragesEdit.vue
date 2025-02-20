<template>
    <form class="grid grid-cols-2 gap-4" @submit.prevent="edit">
    
                  <div class="col-span-2">
                    <label class="block font-medium">Name <span class="text-red-500">*</span></label>
                    <InputText v-model="form.name" class="w-full" placeholder="Storage name" />
                    <!-- <small class="text-red-500" v-if="errors.vendor">{{ errors.vendor }}</small> -->
                </div>
    
                <div class="col-span-2">
                    <label class="block font-medium">Limit</label>
                    <InputNumber v-model="form.limit" class="w-full" placeholder="Storage limit" />
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
    import { InputText, InputNumber } from 'primevue';
    import { ref, defineEmits, defineProps, Ref, onMounted } from 'vue';
    import axios from 'axios';
    import { editStorage } from '../StoragesIndexData';
    import { IStorage } from '../StoragesIndexData';


    const emit = defineEmits(['success']);
    const props = defineProps<{
    item: any,
}>();
    const form = useForm({
        id: 0,
        name: '',
        limit: 0
    })
    
    const edit = async () => {
            try {
                const response = await editStorage(form.id, form.name, form.limit);
                emit('success')
            } catch (error) {
                console.error('Error fetching storages:', error);
            }
        };

    onMounted(() => {
        form.name = props.item[0].name;
        form.limit = props.item[0].limit;
        form.id = props.item[0].id
    })
    </script>