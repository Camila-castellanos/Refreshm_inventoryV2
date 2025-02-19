<template>
 <AppLayout>
    <div class="p-6">
        <h1 class="text-2xl font-bold">Dashboard</h1>

        <!-- Filtro de fecha y opciones -->
        <div class="flex justify-between items-center mt-4">
            <Dropdown v-model="selectedFilter" :options="filters" class="w-40" />
            <div class="flex gap-2">
                <Calendar v-model="startDate" showIcon dateFormat="mm/dd/yy" />
                <Calendar v-model="endDate" showIcon dateFormat="mm/dd/yy" />
                <Button label="Filter" icon="pi pi-filter" />
            </div>
        </div>

        <!-- Secciones -->
        <div class="grid grid-cols-3 gap-4 mt-6">
            <Card v-for="stat in inventoryStats" :key="stat.label" class="shadow-md">
                <template #title>{{ stat.label }}</template>
                <template #content>
                    <p class="text-xl font-semibold">{{ stat.value }}</p>
                </template>
            </Card>
        </div>

        <Divider />

        <div class="grid grid-cols-3 gap-4 mt-6">
            <Card v-for="stat in salesStats" :key="stat.label" class="shadow-md">
                <template #title>{{ stat.label }}</template>
                <template #content>
                    <p class="text-xl font-semibold">{{ stat.value }}</p>
                </template>
            </Card>
        </div>

        <Divider />

        <div class="grid grid-cols-3 gap-4 mt-6">
            <Card v-for="stat in accountingStats" :key="stat.label" class="shadow-md">
                <template #title>{{ stat.label }}</template>
                <template #content>
                    <p class="text-xl font-semibold">{{ stat.value }}</p>
                </template>
            </Card>
        </div>
    </div>
 </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import Dropdown from 'primevue/dropdown';
import Calendar from 'primevue/calendar';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Divider from 'primevue/divider';
import AppLayout from '@/Layouts/AppLayout.vue';

const filters = ref(["Current", "Historically"]);
const selectedFilter = ref("Current");
const startDate = ref(null);
const endDate = ref(null);

const inventoryStats = ref([
    { label: "Devices in Inventory", value: 546 },
    { label: "Devices Added", value: 308 },
    { label: "Devices Sold", value: 226 },
    { label: "Cost of Goods Sold", value: 34328 },
    { label: "Inventory Value ($)", value: 75306 },
    { label: "Est. Sale Value of Inventory ($)", value: 95714 }
]);

const salesStats = ref([
    { label: "Revenue ($)", value: 49159 },
    { label: "Gross Profit ($)", value: 14831 },
    { label: "Net Profit ($)", value: 13615 },
    { label: "Expenses ($)", value: 1216 }
]);

const accountingStats = ref([
    { label: "Accounts Receivable ($)", value: 63253 },
    { label: "Accounts Payable ($)", value: 23828 },
    { label: "Cash on Hand ($)", value: -25955.1868 },
    { label: "Sales Tax Collected ($)", value: 1739 },
    { label: "Taxed Sales ($)", value: 49159 },
    { label: "Non-taxed Sales ($)", value: 25309 }
]);
</script>
