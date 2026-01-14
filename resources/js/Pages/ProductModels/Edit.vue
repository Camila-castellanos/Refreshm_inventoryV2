<template>
  <AppLayout :title="`Edit ${model.name}`">
    <div class="py-12">
      <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
          <!-- Header -->
          <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <div>
              <h3 class="text-lg font-medium text-gray-900">Edit Model</h3>
              <p class="text-sm text-gray-600">Update model information and photos</p>
            </div>
            <Link :href="route('product-models.index')" class="text-gray-600 hover:text-gray-800">
              <Button label="Back" icon="pi pi-arrow-left" severity="secondary" outlined />
            </Link>
          </div>

          <!-- Form -->
          <form @submit.prevent="submit" class="p-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Name -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Model Name *</label>
                <input
                  v-model="form.name"
                  type="text"
                  required
                  class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
                <p v-if="errors.name" class="text-red-500 text-sm mt-1">{{ errors.name }}</p>
              </div>

              <!-- Manufacturer -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Manufacturer</label>
                <input
                  v-model="form.manufacturer"
                  type="text"
                  class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>

               <!-- Type -->
               <div>
                 <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                 <select
                   v-model="form.type"
                   class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                 >
                   <option value="">Select type</option>
                   <option value="device">Device</option>
                   <option value="tablet">Tablet</option>
                   <option value="accessory">Accessory</option>
                   <option value="other">Other</option>
                 </select>
               </div>
             </div>

            <!-- Colours -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Colors</label>
              <div class="flex gap-2 mb-2">
                <input
                  v-model="newColour"
                  type="text"
                  placeholder="e.g., Midnight Blue"
                  class="flex-1 border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  @keydown.enter.prevent="addColour"
                />
                <Button type="button" label="Add" icon="pi pi-plus" @click="addColour" severity="secondary" />
              </div>
              <div v-if="form.colours.length > 0" class="flex flex-wrap gap-2">
                <span
                  v-for="(colour, index) in form.colours"
                  :key="index"
                  class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full flex items-center gap-2"
                >
                  {{ colour }}
                  <button type="button" @click="removeColour(index)" class="hover:text-red-600">
                    <i class="pi pi-times"></i>
                  </button>
                </span>
              </div>
              <p v-else class="text-gray-500 text-sm">No colors added</p>
            </div>

            <!-- Capacities -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Capacities</label>
              <div class="flex gap-2 mb-2">
                <input
                  v-model="newCapacity"
                  type="text"
                  placeholder="e.g., 128GB, 256GB"
                  class="flex-1 border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  @keydown.enter.prevent="addCapacity"
                />
                <Button type="button" label="Add" icon="pi pi-plus" @click="addCapacity" severity="secondary" />
              </div>
              <div v-if="form.capacities.length > 0" class="flex flex-wrap gap-2">
                <span
                  v-for="(capacity, index) in form.capacities"
                  :key="index"
                  class="px-3 py-1 bg-green-100 text-green-800 rounded-full flex items-center gap-2"
                >
                  {{ capacity }}
                  <button type="button" @click="removeCapacity(index)" class="hover:text-red-600">
                    <i class="pi pi-times"></i>
                  </button>
                </span>
              </div>
              <p v-else class="text-gray-500 text-sm">No capacities added</p>
            </div>

            <!-- Description -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
              <textarea
                v-model="form.description"
                rows="3"
                class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              ></textarea>
            </div>

            <!-- Photos -->
            <div>
              <div class="flex justify-between items-center mb-3">
                <label class="block text-sm font-medium text-gray-700">Photos</label>
                <Button label="Upload Photo" icon="pi pi-upload" size="small" @click="showUploadModal = true" />
              </div>

              <!-- Upload Form -->
              <form
                v-if="showUploadModal"
                @submit.prevent="uploadPhoto"
                class="mb-4 p-4 border border-dashed border-gray-300 rounded-lg"
              >
                <div class="grid grid-cols-2 gap-3 mb-3">
                  <div>
                    <label class="block text-xs text-gray-600 mb-1">Photo</label>
                    <input
                      type="file"
                      accept="image/*"
                      @change="handlePhotoSelect"
                      class="w-full text-sm border rounded px-2 py-1"
                    />
                  </div>
                  <div>
                    <label class="block text-xs text-gray-600 mb-1">Color (optional)</label>
                    <select v-model="photoForm.colour" class="w-full text-sm border rounded px-2 py-1">
                      <option value="">Not specified</option>
                      <option v-for="c in form.colours" :key="c" :value="c">{{ c }}</option>
                    </select>
                  </div>
                </div>
                <div class="flex gap-2">
                  <Button type="submit" label="Upload" icon="pi pi-check" size="small" :loading="uploading" />
                  <Button type="button" label="Cancel" size="small" severity="secondary" outlined @click="showUploadModal = false" />
                </div>
              </form>

              <!-- Photos Grid -->
              <div v-if="model.photo_urls && model.photo_urls.length > 0" class="grid grid-cols-4 gap-3">
                <div
                  v-for="photo in model.photo_urls"
                  :key="photo.id"
                  class="relative group"
                >
                  <img
                    :src="photo.preview"
                    :alt="photo.name"
                    class="w-full h-24 object-cover rounded"
                  />
                  <div class="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                    <Button
                      icon="pi pi-trash"
                      severity="danger"
                      text
                      rounded
                      @click="deletePhoto(photo.id)"
                    />
                  </div>
                  <div v-if="photo.colour" class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-70 text-white text-xs p-1 text-center">
                    {{ photo.colour }}
                  </div>
                </div>
              </div>
              <p v-else class="text-sm text-gray-400">No photos uploaded</p>
            </div>

            <!-- Submit -->
            <div class="flex justify-end gap-4 pt-4 border-t">
              <Link :href="route('product-models.index')">
                <Button type="button" label="Cancel" severity="secondary" outlined />
              </Link>
              <Button
                type="submit"
                label="Save Changes"
                icon="pi pi-check"
                :loading="processing"
              />
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from 'primevue/button'

const props = defineProps({
  model: Object,
  errors: Object,
})

const form = useForm({
  name: props.model.name,
  manufacturer: props.model.manufacturer,
  type: props.model.type,
  colours: props.model.colours || [],
  capacities: props.model.capacities || [],
  description: props.model.description,
})

const newColour = ref('')
const newCapacity = ref('')
const processing = ref(false)
const showUploadModal = ref(false)
const uploading = ref(false)

const photoForm = useForm({
  photo: null,
  colour: '',
})

const addColour = () => {
  if (newColour.value.trim() && !form.colours.includes(newColour.value.trim())) {
    form.colours.push(newColour.value.trim())
    newColour.value = ''
  }
}

const removeColour = (index) => {
  form.colours.splice(index, 1)
}

const addCapacity = () => {
  if (newCapacity.value.trim() && !form.capacities.includes(newCapacity.value.trim())) {
    form.capacities.push(newCapacity.value.trim())
    newCapacity.value = ''
  }
}

const removeCapacity = (index) => {
  form.capacities.splice(index, 1)
}

const submit = () => {
  processing.value = true
  form.put(route('product-models.update', props.model.id), {
    onFinish: () => {
      processing.value = false
    },
  })
}

const handlePhotoSelect = (event) => {
  photoForm.photo = event.target.files[0]
}

const uploadPhoto = () => {
  if (!photoForm.photo) return

  uploading.value = true
  const formData = new FormData()
  formData.append('photo', photoForm.photo)
  if (photoForm.colour) formData.append('colour', photoForm.colour)

  router.post(route('product-models.photos.upload', props.model.id), formData, {
    onFinish: () => {
      uploading.value = false
      showUploadModal.value = false
      photoForm.reset()
    },
  })
}

const deletePhoto = (photoId) => {
  if (confirm('Delete this photo?')) {
    router.delete(route('product-models.photos.delete', [props.model.id, photoId]))
  }
}
</script>
