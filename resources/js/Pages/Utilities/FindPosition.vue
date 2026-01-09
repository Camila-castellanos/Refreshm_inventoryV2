<template>
  <AppLayout>
    <div class="container mx-auto px-4 py-8">
      <div class="max-w-2xl mx-auto">
        <h1 class="text-3xl font-bold mb-8">Find Item by Position</h1>

        <!-- Search Form -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
          <div class="space-y-4">
            <!-- Storage Selection -->
            <div>
              <label for="storage" class="block text-sm font-medium text-gray-700 mb-2">
                Storage / Bin
              </label>
              <Dropdown
                v-model="form.storage_id"
                :options="storages"
                option-label="name"
                option-value="id"
                placeholder="Select a storage"
                class="w-full"
                @change="clearResults"
              />
            </div>

            <!-- Position Input -->
            <div>
              <label for="position" class="block text-sm font-medium text-gray-700 mb-2">
                Position
              </label>
              <InputNumber
                v-model="form.position"
                :use-grouping="false"
                placeholder="Enter position number"
                class="w-full"
                @input="clearResults"
              />
            </div>

            <!-- Search Button -->
            <div class="flex gap-2">
              <Button
                label="Search"
                icon="pi pi-search"
                @click="searchPosition"
                :loading="loading"
                class="w-full"
              />
            </div>
          </div>
        </div>

        <!-- Results Section -->
        <div v-if="searched" class="bg-white rounded-lg shadow-md p-6">
          <!-- Found Result -->
          <div v-if="result.found" class="space-y-6">
            <div class="border-l-4 border-green-500 pl-4">
              <p class="text-sm text-gray-500 mb-1">Result found in: <strong>{{ result.type }}</strong></p>
              <p class="text-lg font-semibold text-green-700">✓ Item Found</p>
            </div>

            <!-- Item Details Card -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

              <div class="bg-gray-50 rounded p-4">
                <p class="text-sm text-gray-500 mb-1">Model</p>
                <p class="font-semibold">{{ result.item.model || 'N/A' }}</p>
              </div>

              <div class="bg-gray-50 rounded p-4">
                <p class="text-sm text-gray-500 mb-1">Manufacturer</p>
                <p class="font-semibold">{{ result.item.manufacturer || 'N/A' }}</p>
              </div>

              <div class="bg-gray-50 rounded p-4">
                <p class="text-sm text-gray-500 mb-1">Serial Number</p>
                <p class="font-semibold">{{ result.item.serial_number || 'N/A' }}</p>
              </div>

              <div class="bg-gray-50 rounded p-4">
                <p class="text-sm text-gray-500 mb-1">Purchase Price</p>
                <p class="font-semibold">${{ formatPrice(result.item.purchase_price) }}</p>
              </div>

              <div class="bg-gray-50 rounded p-4">
                <p class="text-sm text-gray-500 mb-1">Sale Price</p>
                <p class="font-semibold">${{ formatPrice(result.item.sale_price) }}</p>
              </div>

              <div v-if="result.item.type" class="bg-gray-50 rounded p-4">
                <p class="text-sm text-gray-500 mb-1">Type</p>
                <p class="font-semibold">{{ result.item.type }}</p>
              </div>

              <div v-if="result.item.condition" class="bg-gray-50 rounded p-4">
                <p class="text-sm text-gray-500 mb-1">Condition</p>
                <p class="font-semibold">{{ result.item.condition }}</p>
              </div>
            </div>

            <!-- Draft Information (if applicable) -->
            <div v-if="result.draft" class="border-l-4 border-blue-500 pl-4 bg-blue-50 rounded p-4">
              <p class="text-sm text-gray-600 mb-2">This item is in a draft:</p>
              <div class="space-y-1">
                <p class="font-semibold text-blue-900">{{ formatDraftLabel(result.draft) }}</p>
                <p class="text-sm text-gray-600">ID: {{ result.draft.id }}</p>
              </div>
            </div>
          </div>

          <!-- Not Found Result -->
          <div v-else class="border-l-4 border-red-500 pl-4">
            <p class="text-lg font-semibold text-red-700">✗ No Item Found</p>
            <p class="text-gray-600 mt-2">{{ result.message }}</p>
          </div>
        </div>

        <!-- Info Message -->
        <div v-if="!searched" class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-blue-800">
          <p class="font-semibold mb-2">How to use:</p>
          <ul class="list-disc list-inside space-y-1 text-sm">
            <li>Select a storage/bin from the dropdown</li>
            <li>Enter the position number</li>
            <li>Click "Search" to find what item is at that position</li>
            <li>Results show active inventory, sold items, and draft items</li>
          </ul>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Dropdown, InputNumber } from 'primevue'
import Button from 'primevue/button'

const page = usePage()
const storages = computed(() => page.props.storages || [])

const form = ref({
  storage_id: null,
  position: null
})

const result = ref({
  found: false,
  type: null,
  item: {},
  draft: null,
  message: ''
})

const loading = ref(false)
const searched = ref(false)

const selectedStorageName = computed(() => {
  const storage = storages.value.find(s => s.id === form.value.storage_id)
  return storage ? storage.name : 'Unknown'
})

const searchPosition = async () => {
  if (!form.value.storage_id || form.value.position === null) {
    return
  }

  loading.value = true
  searched.value = false

  try {
    const response = await fetch('/utilities/search-position', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        storage_id: form.value.storage_id,
        position: form.value.position
      })
    })

    if (!response.ok) {
      throw new Error('Search failed')
    }

    const data = await response.json()
    result.value = data
    searched.value = true
  } catch (error) {
    console.error('Error searching position:', error)
    result.value = {
      found: false,
      type: null,
      item: {},
      draft: null,
      message: 'An error occurred while searching. Please try again.'
    }
    searched.value = true
  } finally {
    loading.value = false
  }
}

const clearResults = () => {
  searched.value = false
  result.value = {
    found: false,
    type: null,
    item: {},
    draft: null,
    message: ''
  }
}

const formatPrice = (price) => {
  if (!price) return '0.00'
  return parseFloat(price).toFixed(2)
}

const formatDraftLabel = (draft) => {
  if (!draft) return 'Unknown Draft'
  const date = new Date(draft.created_at).toISOString().split('T')[0]
  return `${draft.title} (${date})`
}
</script>
