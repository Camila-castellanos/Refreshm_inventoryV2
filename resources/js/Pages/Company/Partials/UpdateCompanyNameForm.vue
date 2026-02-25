<template>
  <div class="col-span-6 sm:col-span-4">
    <div class="flex justify-between">
      <div class="flex items-start mb-2 w-1/3">
        <div>
          <h2 class="text-xl font-medium mb-1">Company Details</h2>
          <p class="text-sm text-gray-600">Update your company's profile information.</p>
        </div>
      </div>

      <Card class="shadow-none w-2/3">
        <template #content>
          <div class="space-y-6">
            <!-- Company Name -->
            <div class="col-span-6 sm:col-span-4">
              <label for="name" class="block text-sm font-medium text-gray-700">Company Name</label>
              <InputText
                id="name"
                v-model="form.name"
                type="text"
                class="mt-1 block w-full"
                autocomplete="organization"
              />
              <small v-if="form.errors.name" class="text-red-500 block mt-1">{{ form.errors.name }}</small>
            </div>

            <div class="flex justify-end gap-2 pt-4">
              <Button
                type="button"
                :loading="form.processing"
                :disabled="form.processing"
                label="Save"
                icon="pi pi-save"
                @click="updateCompanyDetails"
              />
            </div>
          </div>
        </template>
      </Card>
    </div>
  </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import { useToast, Button, Card, InputText } from 'primevue';

const props = defineProps({
  company: Object,
});

const toast = useToast();

const form = useForm({
  name: props.company.name,
});

const updateCompanyDetails = () => {
  form.put(route('company.update'), {
    preserveScroll: true,
    onSuccess: () => {
      toast.add({ severity: 'success', summary: 'Success', detail: 'Company details updated successfully', life: 3000 });
    },
    onError: () => {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to update company details', life: 3000 });
    }
  });
};
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
