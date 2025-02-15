<template>


<DataTable  v-model:filters="filters"  :value="items" v-model:selection="selectedProduct" selectionMode="multiple" dataKey="id" stripedRows ref="dt" paginator :rows="20" :rowsPerPageOptions="[5, 10, 20, 50]"  tableStyle="min-width: 50rem">
    <template #header>
        <div class="flex flex-wrap items-center justify-between gap-2">
            <span class="text-xl font-bold">Active inventory</span>
            <!-- <Button label="Sell" icon="pi pi-refresh"  raised />
            <Button label="Delete" icon="pi pi-refresh"  raised />
            <Button label="Place on Hold" icon="pi pi-refresh"  raised /> -->
            <div class="flex gap-2">
                <Button label="Assign Location" icon="pi pi-plus-circle"  raised />
                <Button label="Create Devices" icon="pi pi-plus"  raised />
                <Button icon="pi pi-external-link" label="Export" @click="exportCSV($event)" />
                <IconField>
                        <InputIcon>
                            <i class="pi pi-search" />
                        </InputIcon>
                        <InputText v-model="filters['global'].value" placeholder="Keyword Search" />
                </IconField>
            </div>
    

        </div>
    </template>
    <template  v-for="header in headers">
        <Column :field="header.name" sortable :header="header.label">
            
        </Column>

    </template>

   
    <template #footer> In total there are {{ items ? items.length : 0 }} products. </template>
</DataTable>

</template>

<script setup lang="ts">
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import { ref } from 'vue';
import ColumnGroup from 'primevue/columngroup';   // optional
import Row from 'primevue/row';   
import { FilterMatchMode } from '@primevue/core/api';
import { InputText, IconField, InputIcon } from 'primevue';

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    name: { value: null, matchMode: FilterMatchMode.STARTS_WITH },
    'country.name': { value: null, matchMode: FilterMatchMode.STARTS_WITH },
    representative: { value: null, matchMode: FilterMatchMode.IN },
    status: { value: null, matchMode: FilterMatchMode.EQUALS },
    verified: { value: null, matchMode: FilterMatchMode.EQUALS }
});

 interface ITableHeaders {
    label: string,
    name: string,
    type: string,
}

const dt = ref();
const exportCSV = () => {
    dt.value.exportCSV();
};

const selectedProduct = ref();

defineProps<{
    headers: ITableHeaders[],
    items:  any[],

}>();




</script>