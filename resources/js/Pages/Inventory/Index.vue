<template>
    <header>
     
        </header>
    <div>

        <section class="w-[90%] mx-auto mt-4">
    
            <DataTable title="Active Inventory" class="mt-8" :actions="tableActions" :items="data" :headers="headers" ></DataTable>

        </section>
    
</div>
</template>

<script setup>
import { ref } from 'vue'; 
import DataTable from '@/Components/DataTable.vue';
import { headers } from './IndexData';
import { data } from './IndexData';
import { router } from '@inertiajs/vue3';


const tableActions = [
    {
        label: 'Create Items',
        icon: 'pi pi-plus',
        action: () => { 
            router.visit('/inventory/items/bulk')
        },
   
    },
    {
        label: 'Delete Items',
        icon: 'pi pi-trash',
        severity: 'danger',
        action: () => { },
        disable: (selectedItems) => selectedItems.length == 0
        
    },
    {
        label: 'Edit Items',
        icon: 'pi pi-pencil',
        action: () => {console.log('hi')},
        disable: (selectedItems) => selectedItems.length !== 1
        
    }
]
  
     

</script>