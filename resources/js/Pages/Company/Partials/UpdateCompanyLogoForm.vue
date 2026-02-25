<template>
  <div class="col-span-6 sm:col-span-4">
    <div class="flex justify-between">
      <div class="flex items-start mb-2 w-1/3">
        <div>
          <h2 class="text-xl font-medium mb-1">Company Wide Logo</h2>
          <p class="text-sm">Set a default logo for all invoices in your company. Users can override this with their personal logo.</p>
        </div>
      </div>

      <Card class="shadow-none w-2/3">
        <template #content>
          <div class="space-y-6">
            <!-- Current Logo -->
            <div v-if="logoPreview || currentLogoUrl" class="mb-4">
              <div class="block w-40 h-40 bg-contain bg-no-repeat bg-center border border-gray-200 rounded"
                   :style="'background-image: url(\'' + (logoPreview || currentLogoUrl) + '\');'">
              </div>
            </div>
            
            <div v-else class="mb-4">
              <div class="block w-40 h-40 bg-contain bg-no-repeat bg-center border border-gray-200 rounded opacity-50"
                   style="background-image: url('/img/_REFRESHMOBILE.png');">
              </div>
              <p class="text-sm text-gray-500 mt-2">Using System Default</p>
            </div>

            <div class="flex items-center gap-2">
              <input ref="logoInput" type="file" class="hidden" accept="image/*" @change="updateLogoPreview" />
              
              <Button type="button" outlined size="small" icon="pi pi-upload" label="Select Company Logo" 
                      @click.prevent="selectNewLogo" />

              <Button v-if="logoPreview" type="button" severity="secondary" size="small" 
                      icon="pi pi-times" label="Cancel" @click.prevent="cancelUpload" />
                      
              <Button v-if="currentLogoUrl && !logoPreview" type="button" outlined severity="danger" size="small"
                      icon="pi pi-trash" label="Remove Logo" @click.prevent="deleteLogo" />
            </div>

            <small v-if="form.errors.photo" class="text-red-500 block">{{ form.errors.photo }}</small>

            <div class="flex justify-end gap-2 pt-4" v-if="logoPreview">
              <Button type="button" :loading="form.processing" :disabled="form.processing" 
                      label="Save" icon="pi pi-save" @click="saveLogo" />
            </div>
          </div>
        </template>
      </Card>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { useToast, Button, Card, useConfirm } from 'primevue';
import axios from 'axios';

const toast = useToast();
const confirm = useConfirm();
const logoInput = ref(null);
const logoPreview = ref(null);
const currentLogoUrl = ref(null);

const form = useForm({
  photo: null,
});

const fetchCurrentLogo = async () => {
  try {
    const response = await axios.get(route('company.invoice-logo.get'), { validateStatus: false });
    if (response.status === 200 && response.data) {
        if (response.headers['content-type'].startsWith('image/')) {
             currentLogoUrl.value = route('company.invoice-logo.get') + '?t=' + new Date().getTime();
        }
    } else {
        currentLogoUrl.value = null;
    }
  } catch (e) {
    currentLogoUrl.value = null;
  }
};

const selectNewLogo = () => {
  logoInput.value.click();
};

const updateLogoPreview = () => {
  const photo = logoInput.value.files[0];

  if (!photo) return;

  if (photo.size > 1024 * 1024) {
      toast.add({ severity: 'error', summary: 'Error', detail: 'Image size must be less than 1MB', life: 3000 });
      return;
  }

  const reader = new FileReader();

  reader.onload = (e) => {
    logoPreview.value = e.target.result;
  };

  reader.readAsDataURL(photo);
  form.photo = photo;
};

const cancelUpload = () => {
  logoPreview.value = null;
  form.photo = null;
  if (logoInput.value) {
    logoInput.value.value = null;
  }
};

const saveLogo = () => {
  if (!form.photo) return;

  form.post(route('company.invoice-logo.update'), {
    preserveScroll: true,
    onSuccess: () => {
      toast.add({ severity: 'success', summary: 'Success', detail: 'Company logo updated successfully', life: 3000 });
      logoPreview.value = null;
      if (logoInput.value) logoInput.value.value = null;
      fetchCurrentLogo();
    },
    onError: () => {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to upload logo', life: 3000 });
    }
  });
};

const deleteLogo = () => {
  confirm.require({
    message: 'Are you sure you want to remove the company logo? This will affect all users who do not have a personal logo set.',
    header: 'Confirmation',
    icon: 'pi pi-exclamation-triangle',
    accept: () => {
      router.delete(route('company.invoice-logo.delete'), {
        preserveScroll: true,
        onSuccess: () => {
          toast.add({ severity: 'success', summary: 'Success', detail: 'Company logo removed', life: 3000 });
          currentLogoUrl.value = null;
        },
      });
    }
  });
};

onMounted(() => {
  fetchCurrentLogo();
});
</script>

<style scoped>
:deep(.p-card) {
  box-shadow: none;
  border: none;
}

:deep(.p-card-content) {
  padding: 1.5rem;
}

:deep(.p-card .p-card-body) {
  padding: 0;
}
</style>
