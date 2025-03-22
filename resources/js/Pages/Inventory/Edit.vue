<template>
    <div class="w-full py-8 h-[90vh]">
        <NewSpreadsheet :initial-data="editing"/>
    </div>
</template>

<script setup lang="ts">
import NewSpreadsheet from '@/Components/Spreadsheetv2.vue';
import { Item } from '@/Lib/types';

const props = defineProps<{ editing: Item[] }>();
</script>