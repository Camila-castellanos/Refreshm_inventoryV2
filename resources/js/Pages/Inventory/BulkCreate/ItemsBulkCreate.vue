<template>
    <div class="w-full flex justify-center items-center h-[90vh]">
        <Spreadsheet class="mx-auto">

        </Spreadsheet>
    </div>
</template>

<script setup lang="ts">
import Spreadsheet from '@/Components/Spreadsheet.vue';
import { onMounted } from 'vue';
import { fetchStorages } from '@/Pages/Storages/StoragesIndexData';
import fetchVendors from '@/Pages/Vendors/VendorsData';


onMounted(() => {
    Promise.all([
        fetchStorages(),
        fetchVendors()
    ]).then(([resStorages, resVendors]) => { 
        console.log(resStorages.data, resVendors.data);
    }).catch(error => {
        console.error('Error fetching data:', error);
    });
});
</script>