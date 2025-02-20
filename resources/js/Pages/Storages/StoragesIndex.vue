<template>
      <Toast />
      <ConfirmDialog></ConfirmDialog>
                <section class="w-[90%] mx-auto mt-4">    
                    <DataTable title="Active Storages Location" @update:selected="handleSelection" class="mt-8" :actions="tableActions" :items="data":headers="StoragesIndexHeaders" ></DataTable>
                </section>


                <Dialog v-model:visible="showCreationModal" modal>
                        <StoragesCreate @success="handleSuccess"></StoragesCreate>
                </Dialog>

                <Dialog v-model:visible="showEditModal" modal>
                        <StoragesEdit :item="selectedStorages" @success="handleSuccess"></StoragesEdit>
                </Dialog>
</template>


<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import { StoragesIndexHeaders, fetchStorages } from './StoragesIndexData';
import StoragesCreate from './StoragesCreate/StoragesCreate.vue';
import StoragesEdit from './StoragesEdit/StoragesEdit.vue';
import { Dialog } from 'primevue';
import {ITableActions} from '@/Components/DataTable.vue'; 
import { onMounted, ref } from 'vue';
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";
import {Toast} from 'primevue';
import {ConfirmDialog} from 'primevue';

const confirm = useConfirm();
const toast = useToast();

const confirmDelete = () => {
    confirm.require({
        message: 'Are you sure you want to delete the selected items?',
        header: 'Confirmation',
        icon: 'pi pi-exclamation-triangle',
        rejectProps: {
            label: 'Cancel',
            severity: 'secondary',
            outlined: true
        },
        acceptProps: {
            label: 'Save'
        },
        accept: () => {
            // toast.add({ severity: 'info', summary: 'Confirmed', detail: 'You have accepted', life: 1500  });
            toast.add({ severity: 'error', summary: 'Error', detail: 'Feature not allowed at the moment', life: 1500 });

        },
        reject: () => {
            toast.add({ severity: 'error', summary: 'Canceled', detail: 'Storage Deletion Canceled', life: 1500 });
        }
    });
};

const selectedStorages = ref([])

defineOptions({ layout: AppLayout })

const showCreationModal = ref(false)
const showEditModal = ref(false)

const handleSelection = (selected: any) => {
    selectedStorages.value = selected;
    console.log('Selected Vendors:', selectedStorages.value);
    console.log()
};

function handleSuccess() {
    loadStorages()
    showCreationModal.value = false;
    showEditModal.value = false;
}
 
const tableActions: ITableActions[] = [
    {
        label: 'New Storage',
        icon: 'pi pi-plus',
        action: () => { showCreationModal.value = true },
   
    },
    {
        label: 'Delete Storage',
        icon: 'pi pi-trash',
        severity: 'danger',
        action: () => { confirmDelete(); },
        disable: (selectedItems) => selectedItems.length == 0
        
    },
    {
        label: 'Edit Storage',
        icon: 'pi pi-pencil',
        action: () => { showEditModal.value = true },
        disable: (selectedItems) => selectedItems.length !== 1
        
    }
]
const loadStorages = async () => {
    try {
        const response = await fetchStorages();
        data.value = response.data;
    } catch (error) {
        console.error('Error fetching storages:', error);
    }
};

const data = ref([]);

onMounted(() => {
    loadStorages()
})
</script>