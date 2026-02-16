<template>
    <AppLayout title="Ecommerce Orders">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Ecommerce Orders
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <DataTable :value="orders" :paginator="true" :rows="10">
                        <Column field="id" header="ID" sortable></Column>
                        <Column field="date" header="Date" sortable></Column>
                        <Column field="customer" header="Customer" sortable></Column>
                        <Column field="customer_email" header="Email" sortable></Column>
                        <Column field="items_count" header="Items" sortable></Column>
                        <Column field="total" header="Total" sortable>
                            <template #body="slotProps">
                                {{ formatCurrency(slotProps.data.total) }}
                            </template>
                        </Column>
                        <Column field="status" header="Status" sortable></Column>
                        <Column field="payment_intent" header="Payment ID"></Column>
                    </DataTable>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

defineProps({
    orders: Array,
    filters: Object,
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(value);
};
</script>
