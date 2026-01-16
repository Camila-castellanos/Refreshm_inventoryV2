<template>
  <AppLayout title="Product Models">
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
          <!-- Header with Create Button -->
          <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <div>
              <h3 class="text-lg font-medium text-gray-900">Product Models</h3>
              <p class="text-sm text-gray-600">Manage your product catalog</p>
            </div>
            <Link
              :href="route('product-models.create')"
            >
              <Button
                label="New Model"
                icon="pi pi-plus"
                severity="primary"
              />
            </Link>
          </div>

          <!-- Filters -->
          <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <div class="flex gap-4">
              <div class="flex-1">
                <input
                  v-model="filters.search"
                  type="text"
                  placeholder="Search by name or manufacturer..."
                  class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  @input="handleSearch"
                />
              </div>
              <div class="w-48">
                <select
                  v-model="filters.type"
                  class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  @change="handleFilter"
                >
                  <option value="">All types</option>
                  <option value="device">Devices</option>
                  <option value="tablet">Tablets</option>
                  <option value="accessory">Accessories</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Models Table -->
          <div v-if="models.data && models.data.length > 0" class="overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Model</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Manufacturer</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Colors</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Capacities</th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="model in models.data" :key="model.id" class="hover:bg-gray-50">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <img
                        v-if="model.main_photo_thumb"
                        :src="model.main_photo_thumb"
                        :alt="model.name"
                        class="w-10 h-10 rounded object-cover"
                      />
                      <div v-else class="w-10 h-10 rounded bg-gray-200 flex items-center justify-center">
                        <span class="text-gray-400 text-xs">N/A</span>
                      </div>
                      <div class="ml-4">
                        <div class="text-sm font-medium text-gray-900">{{ model.name }}</div>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ model.manufacturer || '-' }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ model.type || '-' }}</td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex gap-1 flex-wrap">
                      <span
                        v-for="colour in (model.colours || []).slice(0, 2)"
                        :key="colour"
                        class="px-2 py-1 bg-gray-100 rounded text-xs"
                      >
                        {{ colour }}
                      </span>
                      <span v-if="(model.colours || []).length > 2" class="text-xs text-gray-500">
                        +{{ model.colours.length - 2 }}
                      </span>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex gap-1 flex-wrap">
                      <span
                        v-for="capacity in (model.capacities || []).slice(0, 2)"
                        :key="capacity"
                        class="px-2 py-1 bg-gray-100 rounded text-xs"
                      >
                        {{ capacity }}
                      </span>
                      <span v-if="(model.capacities || []).length > 2" class="text-xs text-gray-500">
                        +{{ model.capacities.length - 2 }}
                      </span>
                    </div>
                    </td>
                   <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                      <div class="flex items-center justify-end gap-2">
                       <Link :href="route('product-models.edit', model.id)" class="text-gray-600 hover:text-gray-800">
                         <i class="pi pi-pencil"></i>
                       </Link>
                       <button @click="deleteModel(model)" class="text-red-600 hover:text-red-800">
                         <i class="pi pi-trash"></i>
                       </button>
                     </div>
                   </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Empty State -->
          <div v-else class="text-center py-12">
            <div class="w-16 h-16 mx-auto mb-4 rounded-lg flex items-center justify-center bg-gray-100">
              <i class="pi pi-box text-2xl text-gray-500"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No models yet</h3>
            <p class="text-gray-600 mb-6">Create your first product model to start building your catalog.</p>
            <Link :href="route('product-models.create')">
              <Button
                label="Create Model"
                icon="pi pi-plus"
                severity="primary"
              />
            </Link>
          </div>

          <!-- Pagination -->
          <div v-if="models.links && models.links.length > 3" class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            <nav class="flex items-center justify-between">
              <div class="flex-1 flex justify-between sm:hidden">
                <Link
                  v-if="models.prev_page_url"
                  :href="models.prev_page_url"
                  class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                >
                  Previous
                </Link>
                <Link
                  v-if="models.next_page_url"
                  :href="models.next_page_url"
                  class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                >
                  Next
                </Link>
              </div>
              <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                  <p class="text-sm text-gray-700">
                    Showing {{ models.from || 0 }} to {{ models.to || 0 }} of {{ models.total || 0 }} results
                  </p>
                </div>
                <div>
                  <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                    <template v-for="(link, index) in models.links" :key="index">
                      <Link
                        v-if="index === 0"
                        :href="link.url"
                        :class="[
                          'relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 text-sm font-medium',
                          link.url ? 'bg-white text-gray-500 hover:bg-gray-50' : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                        ]"
                        :disabled="!link.url"
                      >
                        <span class="sr-only">Previous</span>
                        <i class="pi pi-chevron-left"></i>
                      </Link>
                      <Link
                        v-else-if="index === models.links.length - 1"
                        :href="link.url"
                        :class="[
                          'relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 text-sm font-medium',
                          link.url ? 'bg-white text-gray-500 hover:bg-gray-50' : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                        ]"
                        :disabled="!link.url"
                      >
                        <span class="sr-only">Next</span>
                        <i class="pi pi-chevron-right"></i>
                      </Link>
                      <Link
                        v-else
                        :href="link.url"
                        :class="[
                          'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                          link.active
                            ? 'bg-blue-50 border-blue-500 text-blue-600 z-10'
                            : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                        ]"
                      >
                        {{ link.label }}
                      </Link>
                    </template>
                  </nav>
                </div>
              </div>
            </nav>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from 'primevue/button'

const props = defineProps({
  models: Object,
  filters: Object,
})

let searchTimeout = null

const handleSearch = () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    router.get(route('product-models.index'), { search: props.filters.search }, { preserveState: true })
  }, 300)
}

const handleFilter = () => {
  router.get(route('product-models.index'), { type: props.filters.type }, { preserveState: true })
}

const deleteModel = (model) => {
  if (confirm(`Are you sure you want to delete "${model.name}"?`)) {
    router.delete(route('product-models.destroy', model.id))
  }
}
</script>
