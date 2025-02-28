<template>
    <form @submit.prevent="submitForm" class="p-6 rounded-lg w-full">
        <div class="grid grid-cols-2 gap-4 w-full">
            <div class="col-span-1">
                <label for="icondisplay" class="font-bold block mb-2"> Date </label>
                <DatePicker v-model="form.date" showIcon fluid iconDisplay="input" inputId="icondisplay" />
            </div>

            <div class="col-span-1">
                <label for="icondisplay" class="font-bold block mb-2"> Tax </label>
                <Select v-model="form.tax" :options="taxes" optionLabel="name" placeholder="Select"
                    class="w-full md:w-56">
                    <template #option="slotProps">
                        <div class="flex items-center">
                            <div>{{ `${slotProps.option.name} - ${slotProps.option.percentage}%` }}</div>
                        </div>
                    </template>

                    <template #footer>
                        <div class="p-3">
                            <Button label="Add New Tax" fluid severity="secondary" text size="small"
                                icon="pi pi-plus" />
                        </div>
                    </template>
                </Select>
            </div>

            <div class="col-span-1">
                <label for="icondisplay" class="font-bold block mb-2"> Payment Method </label>
                <Select v-model="form.payment_method" :options="payment_method" optionLabel="name" placeholder="Select"
                    class="w-full md:w-56">
                </Select>
            </div>

            <div class="col-span-1">
                <label for="icondisplay" class="font-bold block mb-2"> Payment Account </label>
                <Select v-model="form.payment_account" :options="payment_account" optionLabel="name"
                    placeholder="Select" class="w-full md:w-56">
                </Select>
            </div>

            <div class="col-span-2">
                <DataTable :value="params.items" tableStyle="min-width: 50rem">
                    <Column field="model" header="Device"></Column>
                    <Column field="issues" header="Issue"></Column>
                    <Column field="imei" header="IMEI"></Column>
                    <Column field="selling_price" header="Selling Price"></Column>
                </DataTable>
            </div>

            <div class="col-span-2">
                <label class="block font-medium">Memo Notes</label>
                <Textarea v-model="form.memo_notes" class="w-full" placeholder="Insert" rows="2" />
            </div>

            <div class="col-span-2 w-full flex gap-2">
                <Button label="Save" class="w-1/2"></Button>
                <Button label="Confirm" class="w-1/2"></Button>
            </div>
        </div>


    </form>
</template>

<script setup lang="ts">
import { useDialog } from 'primevue/usedialog';
import { InputText, Dialog, Textarea, AutoComplete, DatePicker } from 'primevue';
import { Select } from 'primevue';
import { ConfirmDialog, useConfirm } from 'primevue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import { defineProps } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { inject, onMounted } from "vue";    
import { ref, computed, watch } from 'vue';



// Subtotal: Sum of all selling prices
const subtotal = computed(() => {
    return params.value.items.reduce((sum, item) => sum + (item.selling_price || 0), 0);
});

// Tax: Calculate tax amount based on subtotal and selected tax percentage
const taxAmount = computed(() => {
    if (!form.tax || !form.tax.percentage) return 0;
    return parseFloat((subtotal.value * (form.tax.percentage / 100)).toFixed(2));
});

// Total: Subtotal + Tax
const total = computed(() => {
    return parseFloat((subtotal.value + taxAmount.value).toFixed(2));
});

const dialogRef = inject('dialogRef');

let params = ref<any>([]);

onMounted(() => {
    params.value = dialogRef.value.data;
    console.log(params.value)
})

const form = useForm({
    date: new Date(),
    tax: '',
    customer: '',
    payment_method: '',
    payment_account: '',
    memo_notes: '',
});

watch(() => form, (newForm) => {
    console.log('El formulario ha cambiado:', newForm);
}, { deep: true }); 

const taxes = [
    { id: 3, name: "HST", percentage: 13.00, user_id: 1 },
    { id: 4, name: "GST", percentage: 5.00, user_id: 1 },
    { id: 5, name: "No Tax", percentage: 0.00, user_id: 1 },
    { id: 9, name: "Tax#206", percentage: 5.00, user_id: 22 },
    { id: 10, name: "Tax#343", percentage: 0.00, user_id: 24 },
    { id: 11, name: "Tax#359", percentage: 0.00, user_id: 23 },
    { id: 12, name: "Tax#488", percentage: 5.00, user_id: 25 },
    { id: 14, name: "USA", percentage: 0.00, user_id: 1 }
];

const payment_method = [{
    name: 'Cash',
    id: 1
}, {
    name: 'Bank payment',
    id: 2
}]

const payment_account = [{
    name: 'Cash on hand',
    id: 1
}]

function submitForm(isConfirmed: boolean) {
    if (!params.value.items.length) {
        alert("No items selected for sale!");
        return;
    }

    const salePayload = {
        subtotal: subtotal.value,
        tax: taxAmount.value,
        total: total.value,
        date: form.date,
        memo_notes: form.memo_notes,
        payment_method: form.payment_method?.name || '',
        payment_account: form.payment_account?.name || '',
        tax_id: form.tax?.id || null,
        paid: isConfirmed ? 1 : 0,  // 1 = Fully Paid, 0 = Unpaid
        balance_remaining: isConfirmed ? 0 : total.value, 
        amount_paid: isConfirmed ? total.value : 0,
        items: params.value.items.map(item => ({
            id: item.id,
            model: item.model,
            imei: item.imei,
            selling_price: item.selling_price,
            issues: item.issues,
            sold: form.date,
            profit: item.selling_price - (item.cost || 0), // Ensure cost exists
        }))
    };

    console.log("Submitting Sale:", salePayload);

}


</script>
