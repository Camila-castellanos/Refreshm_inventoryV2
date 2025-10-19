<template>
  <div class="flex justify-between">
    <div class="flex items-start mb-2 w-1/3">
      <div>
        <h2 class="text-xl font-medium mb-1">Printable Invoice Fields</h2>
        <p class="text-sm">Select the fields you want to show on the invoice</p>
      </div>
    </div>

    <Card class="shadow-none w-2/3">
      <template #content>
        <div class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div v-for="item in headers" :key="item.name" class="flex items-center">
              <Checkbox :value="item.name" v-model="selectedFields" />
              <label class="ml-2">{{ item.label }}</label>
            </div>
          </div>
          <div class="flex justify-end">
            <Button label="Save" icon="pi pi-save" @click="submit" :loading="loading" />
          </div>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import axios from 'axios'
import Checkbox from 'primevue/checkbox'
import Button from 'primevue/button'
import Card from 'primevue/card'
import { useToast } from 'primevue'

const headers = [
  { name: 'logo', label: 'Logo' },
  { name: 'header', label: 'Header' },
  { name: 'billing_address', label: 'Billing Address' },
  { name: 'invoice_number', label: 'Invoice Number' },
  { name: 'invoice_due', label: 'Invoice Due' },
  { name: 'payment_due', label: 'Payment Due' },
  { name: 'amount_due', label: 'Amount Due' },
  { name: 'items', label: 'Items' },
  { name: 'table_device', label: 'Device' },
  { name: 'table_grade', label: 'Grade' },
  { name: 'table_issues', label: 'Issues' },
  { name: 'table_imei', label: 'IMEI' },
  { name: 'table_price', label: 'Price' },
  { name: 'subtotal', label: 'Subtotal' },
  { name: 'tax', label: 'Tax' },
  { name: 'total', label: 'Total' },
  { name: 'credit', label: 'Credit' },
  { name: 'footer', label: 'Footer' },
]

const toast = useToast()
const loading = ref(false)
const selectedFields = ref<string[]>([])

onMounted(async () => {
  try {
    const { data } = await axios.get(route('user.printableInvoiceFields'))
    selectedFields.value = data
  } catch (e) {
    console.error('Error loading printable invoice fields', e)
    toast.add({ severity: 'error', summary: 'Error', detail: 'Could not load invoice fields', life: 3000 })
  }
})

async function submit() {
  loading.value = true
try {
    await axios.put(route('user.updatePrintableInvoiceFields'), { fields: selectedFields.value })
    toast.add({ severity: 'success', summary: 'Saved', detail: 'Invoice fields updated', life: 3000 })
} catch (e) {
    console.error('Error saving printable invoice fields', e)
    toast.add({ severity: 'error', summary: 'Error', detail: 'Could not save the invoice fields', life: 3000 })
  } finally {
    loading.value = false
  }
}
</script>
