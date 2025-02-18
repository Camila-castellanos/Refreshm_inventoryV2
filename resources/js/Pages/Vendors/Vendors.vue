<template>
    <h1>hello world</h1>

    <DataTable :items="[]" :headers="VendorHeaders"></DataTable>
</template>
<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import DataTable from '@/Components/DataTable.vue';
import { VendorHeaders } from './VendorsData';
defineOptions({ layout: AppLayout })
</script>