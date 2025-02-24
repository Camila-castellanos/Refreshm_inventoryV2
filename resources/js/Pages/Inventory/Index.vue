<template>
<StoragesAssign :items="selectedItems" ref="assignStorageVisible"></StoragesAssign>
    <div>

        <section class="w-[90%] mx-auto mt-4">
    
            <DataTable title="Active Inventory" class="mt-8" @update:selected="handleSelection" :actions="tableActions" :items="tableData" :headers="headers" ></DataTable>

        </section>
    
</div>
</template>

<script setup>
import { onMounted, ref } from 'vue'; 
import DataTable from '@/Components/DataTable.vue';
import { headers } from './IndexData';
import { data } from './IndexData';
import { router } from '@inertiajs/vue3';
import { defineProps } from 'vue';
import StoragesAssign from '../Storages/StoragesAssign/StoragesAssign.vue';
import { Dialog } from 'primevue';

const props = defineProps({
    items: Array
});

const assignStorageVisible = ref(null);

const toggleAssignStorageVisible = () => {
    assignStorageVisible.value.openDialog();
};

let selectedItems = ref([])

const handleSelection = (selected) => {
    selectedItems.value = selected;
};

const tableData = ref([])
function parseItemsData() {
    tableData.value = props.items.map(item => {
        const {name, limit} = item.storage;
        const {position} = item;
        return {
            ...item,
            location: `${name} - ${position}/${limit}`,
        }
    })
}
onMounted(() => {
    parseItemsData()
}) 

const tableActions = [
    {
        label: 'Create Items',
        icon: 'pi pi-plus',
        action: () => { 
            router.visit('/inventory/items/bulk')
        },
   
    },
    {
        label: 'Reassign location',
        icon: 'pi pi-arrow-up',
        action: () => { 
            toggleAssignStorageVisible()
        },
        disable: (selectedItems) => selectedItems.length == 1

   
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