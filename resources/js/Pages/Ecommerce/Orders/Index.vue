<template>
    <AppLayout :title="`Ecommerce Orders - ${market?.name || 'Market'}`">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Ecommerce Orders - {{ market?.name }}
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
                        <Column field="payment_intent" header="Payment">
                            <template #body="slotProps">
                                <a 
                                    v-if="slotProps.data.payment_intent"
                                    :href="getStripeUrl(slotProps.data.payment_intent)"
                                    target="_blank"
                                    class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 rounded transition-colors"
                                >
                                    <i class="pi pi-external-link"></i>
                                    View in Stripe
                                </a>
                                <span v-else class="text-gray-400">-</span>
                            </template>
                        </Column>
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
    market: Object,
    filters: Object,
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(value);
};

const getStripeUrl = (paymentIntentId) => {
    return `https://dashboard.stripe.com/payments/${paymentIntentId}`;
};
</script>
