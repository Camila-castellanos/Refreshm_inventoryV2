<template>
  <div class="flex justify-between">
    <div class="flex items-start mb-2 w-1/3">
      <div>
        <h2 class="text-xl font-medium mb-1">Update Timezone</h2>
        <p class="text-sm">Select your preferred timezone.</p>
      </div>
    </div>

    <Card class="shadow-none w-2/3">
      <template #content>
        <form @submit.prevent="updateTimezone" class="space-y-6">
          <div class="field">
            <label for="timezone" class="block text-sm font-medium mb-1">Timezone</label>
            <Select
              id="timezone"
              v-model="form.timezone"
              :options="timezones"
              optionLabel="label"
              optionValue="value"
              placeholder="Select timezone"
              class="w-full"
            />
            <small v-if="form.errors.timezone" class="text-red-500">{{ form.errors.timezone }}</small>
          </div>

          <div class="flex justify-end gap-2">
            <span v-if="form.recentlySuccessful" class="text-green-500 self-center mr-2">Saved successfully</span>
            <Button type="submit" :loading="form.processing" :disabled="form.processing" label="Save" icon="pi pi-save" />
          </div>
        </form>
      </template>
    </Card>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Select, Button, Card, useToast } from 'primevue';
import axios from 'axios';

const props = defineProps({
  timezone: { type: String, default: '' }
});

const toast = useToast();

const timezones = [
  { label: 'UTC', value: 'UTC' },
  { label: 'America/New York', value: 'America/New_York' },
  { label: 'America/Chicago', value: 'America/Chicago' },
  { label: 'America/Bogota', value: 'America/Bogota' },
  { label: 'America/Denver', value: 'America/Denver' },
  { label: 'America/Los Angeles', value: 'America/Los_Angeles' },
  { label: 'Europe/London', value: 'Europe/London' },
  { label: 'Europe/Paris', value: 'Europe/Paris' },
  { label: 'Europe/Berlin', value: 'Europe/Berlin' },
  { label: 'Asia/Tokyo', value: 'Asia/Tokyo' },
  { label: 'Asia/Shanghai', value: 'Asia/Shanghai' },
  { label: 'Asia/Kolkata', value: 'Asia/Kolkata' },
  { label: 'Australia/Sydney', value: 'Australia/Sydney' },
];

// Initialize form without timezone; will fetch on mount
const form = useForm({ _method: 'PUT', timezone: '' });

onMounted(async () => {
  try {
    const { data } = await axios.get(route('user.timezone.fetch'));
    form.timezone = data.timezone;
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo cargar la zona horaria', life: 3000 });
  }
});

const updateTimezone = () => {
  form.put(route('user.timezone.update'), {
    preserveScroll: true,
    onSuccess: () => {
      toast.add({ severity: 'success', summary: 'Success', detail: 'Timezone updated', life: 3000 });
    },
  });
};
</script>

<style scoped>
:deep(.p-card) { box-shadow: none; border: none; }
:deep(.p-card-content) { padding: 1.5rem; }
</style>
