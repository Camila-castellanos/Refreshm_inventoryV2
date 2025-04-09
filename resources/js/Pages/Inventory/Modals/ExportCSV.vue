<template>
    <div class="flex flex-col items-center p-6 gap-y-4">
        <h3 class="text-lg font-semibold text-gray-700 mb-2">
            Export CSV
        </h3>

        <div class="flex justify-center gap-x-4 w-full">
            <Button label="Selected" icon="pi pi-check-square" severity="secondary" outlined class="min-w-[8rem]"
                @click="handleExportSelected" />

            <Button label="All" icon="pi pi-file-export" severity="primary" class="min-w-[8rem]"
                @click="handleExportAll" />
        </div>
    </div>
</template>

<script setup>
import Button from 'primevue/button';
import { inject } from "vue";

const dialogRef = inject("dialogRef");


const handleExportSelected = () => {
    dialogRef.value.data.dt.exportCSV({ selectionOnly: true })
    dialogRef.value.close()
};

const handleExportAll = () => {
    dialogRef.value.data.dt.exportCSV()
    dialogRef.value.close()
};


</script>
