<template>
    <div class="w-full flex justify-center items-center h-[90vh]">
        <Spreadsheet :initial-data="editing" class="mx-auto">

        </Spreadsheet>
    </div>
</template>

<script setup lang="ts">
import Spreadsheet from '@/Components/Spreadsheet.vue';
import { Item } from '@/Lib/types';

const props = defineProps<{ editing: Item[] }>();
</script>