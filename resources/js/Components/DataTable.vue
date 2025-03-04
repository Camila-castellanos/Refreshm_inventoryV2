<template>
    <DataTable v-model:filters="filters" :value="items" v-model:selection="selectedItems"
        dataKey="id" stripedRows ref="dt" paginator :rows="20" :rowsPerPageOptions="[5, 10, 20, 50]"
        tableStyle="min-width: 50rem" filterDisplay="row" :globalFilterFields="headers.filter(header => header.name !== 'actions').map(header => header.name)" :selection-mode="selectionMode">
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div :class="title !== '' ? 'flex justify-between gap-12' : 'flex justify-start'">
                    <span class="text-xl font-bold" v-show="title !== ''">{{ title }}</span>
                    <IconField>
                        <InputIcon>
                            <i class="pi pi-search" />
                        </InputIcon>
                        <InputText v-model="filters['global'].value" placeholder="Search" />
                    </IconField>
                </div>
                <div class="flex gap-8">
                    <Button v-for="action in computedActions" :key="action.label"
                        :severity="action.severity ? action.severity : 'primary'"
                        :class="[action.extraClasses, 'px-4 py-2 rounded-md'].join(' ')"
                        :icon="action.icon ? action.icon : ''" :label="action?.label" @click="action.action" :disabled="action.disable"  />

                        <Button icon="pi pi-file-export" label="Export CSV" severity="primary" @click="exportCSV" />

                </div>

            </div>
        </template>

        <template v-for="header in headers">
            <Column :field="header.name" sortable :header="header.label" v-if="header.name !== 'actions'">
            </Column>
        </template>

        <Column header="Actions" name="actions" v-if="headers.filter(header => header.name === 'actions').length > 0">
            <template #body="slotProps">
                <Button icon="pi pi-pencil" rounded variant="outlined" aria-label="Edit" @click="onEditItem(slotProps.data)" class="mr-2"/>
                <Button icon="pi pi-times" severity="danger" rounded variant="outlined" aria-label="Cancel" @click="onDeleteItem(slotProps.data)" />
            </template>
        </Column>
        <template #footer> In total there are {{ items ? items.length : 0 }} items. </template>
    </DataTable>

</template>

<script setup lang="ts">
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import { ref, watch, computed } from 'vue';
import Row from 'primevue/row';
import { FilterMatchMode } from '@primevue/core/api';
import { InputText, IconField, InputIcon, OrganizationChartStyle } from 'primevue';

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
    severity?: string;
    action: () => void;
    disable?: (selectedItems: any[]) => boolean; 
}

const props = defineProps<{
    title: string,
    headers: ITableHeaders[],
    items: any[],
    actions?: ITableActions[],
    selectionMode?: 'single' | 'multiple'
}>();

const selectionMode = ref(props.items?.length === 0 ? undefined : (props.selectionMode ? props.selectionMode : 'multiple'));

const dt = ref();
const selectedItems = ref<any[]>([]); 

const exportCSV = () => {
    dt.value.exportCSV();
};

const emit = defineEmits(['update:selected', 'edit', 'delete']);

watch(selectedItems, (newSelection) => {
    emit('update:selected', newSelection);
});

function onEditItem(row: any) {
    emit('edit', row);
}

function onDeleteItem(row: any) {
    emit('delete', row);
}


const computedActions = computed(() => {
    return props.actions?.map(action => ({
        ...action,
        disable: action.disable ? action.disable(selectedItems.value) : false
    }));
});


</script>