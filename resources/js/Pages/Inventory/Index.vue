<template>
    <header>
     
        </header>
    <div>

        <section class="w-[90%] mx-auto mt-4">
    
            <DataTable title="Active Inventory" class="mt-8" :items="data" :headers="headers" ></DataTable>

        </section>
    
</div>
</template>

<script setup>
import { ref } from 'vue'; 
import DataTable from '@/Components/DataTable.vue';
import { headers } from './IndexData';
import { data } from './IndexData';

  
     

</script>