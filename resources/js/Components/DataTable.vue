<template>


    <DataTable v-model:filters="filters" :value="items" v-model:selection="selectedItems" selectionMode="multiple"
        dataKey="id" stripedRows ref="dt" paginator :rows="20" :rowsPerPageOptions="[5, 10, 20, 50]"
        tableStyle="min-width: 50rem">
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <span class="text-xl font-bold">{{ title }}</span>

                <div class="flex gap-8">
                    <Button v-for="action in computedActions" :key="action.label"
                        :class="[action.extraClasses, 'px-4 py-2 rounded-md'].join(' ')"
                        :icon="action.icon ? action.icon : ''" :label="action?.label" @click="action.action" :disabled="action.disable"  />
                </div>

            </div>
        </template>
        <template v-for="header in headers">
            <Column :field="header.name" sortable :header="header.label">

            </Column>

        </template>


        <template #footer> In total there are {{ items ? items.length : 0 }} items. </template>
    </DataTable>

</template>

<script setup lang="ts">
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import { ref, watch, computed } from 'vue';
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

export interface ITableHeaders {
    label: string,
    name: string,
    type: string,
}

export interface ITableActions {
    extraClasses?: string,
    icon?: string,
    label: string;
    action: () => void;
    disable?: (selectedItems: any[]) => boolean; 
}

const props = defineProps<{
    title: string,
    headers: ITableHeaders[],
    items: any[],
    actions?: ITableActions[],
    
}>();

const dt = ref();
const selectedItems = ref<any[]>([]); 

const exportCSV = () => {
    dt.value.exportCSV();
};

const emit = defineEmits(['update:selected']);

watch(selectedItems, (newSelection) => {
    emit('update:selected', newSelection);
});


const computedActions = computed(() => {
    return props.actions?.map(action => ({
        ...action,
        disable: action.disable ? action.disable(selectedItems.value) : false
    }));
});


</script>