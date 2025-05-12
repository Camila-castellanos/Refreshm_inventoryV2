<template>
  <div class="flex justify-between">
    <div class="flex items-start mb-2 w-1/3">
      <div>
        <h2 class="text-xl font-medium mb-1">Printable Label Fields</h2>
        <p class="text-sm">Select the fields you want to print on the printable label</p>
      </div>
    </div>

    <Card class="shadow-none w-2/3">
      <template #content>
        <div class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div
              v-for="(label, key) in allFields"
              :key="key"
              class="flex items-center"
            >
              <Checkbox
                :value="key"
                v-model="selectedFields"
              />
              <label class="ml-2">{{ label }}</label>
            </div>
          </div>
          <div class="flex justify-end">
            <Button
              label="Guardar"
              icon="pi pi-save"
              @click="submit"
              :loading="loading"
            />
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
import Button   from 'primevue/button'
import Card     from 'primevue/card'
import { useToast } from 'primevue'

const toast = useToast()
const loading = ref(false)
const selectedFields = ref<string[]>([])

const allFields: Record<string,string> = {
  manufacturer: 'Manufacturer',
  model:        'Model',
  storage:      'Storage',
  colour:       'Colour',
  battery:      'Battery Health',
  imei:         'IMEI',
}

onMounted(async () => {
  try {
    const { data } = await axios.get(route('user.printableTagFields'))
    selectedFields.value = data
  } catch (e) {
    console.error('Error loading printable fields', e)
    toast.add({ severity:'error', summary:'Error', detail:'No se pudieron cargar los campos', life: 3000 })
  }
})

async function submit() {
  loading.value = true
  try {
    await axios.put(route('user.updatePrintableTagFields'), {
      fields: selectedFields.value
    })
    toast.add({ severity:'success', summary:'Saved', detail:'Fields updated', life: 3000 })
  } catch (e) {
    console.error('Error saving printable fields', e)
    toast.add({ severity:'error', summary:'Error', detail:'Could not save selection', life: 3000 })
  } finally {
    loading.value = false
  }
}
</script>