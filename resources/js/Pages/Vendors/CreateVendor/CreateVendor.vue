<template>
    <form @submit.prevent="submitForm" class="p-6 rounded-lg w-full max-w-2xl">
      <div class="grid grid-cols-2 gap-4">
        <div class="col-span-2">
          <label class="block font-medium">Vendor <span class="text-red-500">*</span></label>
          <InputText v-model="form.vendor" class="w-full" placeholder="Vendor name" />
          <small class="text-red-500" v-if="errors.vendor">{{ errors.vendor }}</small>
        </div>

        <div>
          <label class="block font-medium">First Name</label>
          <InputText v-model="form.first_name" class="w-full" placeholder="First Name" />
          <small class="text-red-500" v-if="errors.first_name">{{ errors.first_name }}</small>
        </div>

        <div>
          <label class="block font-medium">Last Name</label>
          <InputText v-model="form.last_name" class="w-full" placeholder="Last Name" />
          <small class="text-red-500" v-if="errors.last_name">{{ errors.last_name }}</small>
        </div>

        <div>
          <label class="block font-medium">Email</label>
          <InputText v-model="form.email" class="w-full" placeholder="Email" />
          <small class="text-red-500" v-if="errors.email">{{ errors.email }}</small>
        </div>

        <div>
          <label class="block font-medium">Phone</label>
          <InputText v-model="form.phone" class="w-full" placeholder="Phone" />
          <small class="text-red-500" v-if="errors.phone">{{ errors.phone }}</small>
        </div>

        <div>
          <label class="block font-medium">Optional Phone</label>
          <InputText v-model="form.optional_phone" class="w-full" placeholder="Optional Phone" />
        </div>

        <div>
          <label class="block font-medium">Currency</label>
          <Select v-model="form.currency" :options="currencies" class="w-full" placeholder="Select Currency" />
          <small class="text-red-500" v-if="errors.currency">{{ errors.currency }}</small>
        </div>

        <div class="col-span-2">
          <label class="block font-medium">Street Address</label>
          <Textarea v-model="form.address" class="w-full" placeholder="Address" rows="2" />
          <small class="text-red-500" v-if="errors.address">{{ errors.address }}</small>
        </div>

        <div class="col-span-2">
          <label class="block font-medium"> Unit / Floor # </label>
          <InputText class="w-full" />
        </div>

        <div>
          <label class="block font-medium">Country</label>
          <Textarea v-model="form.country" class="w-full" placeholder="Country" rows="2" />
          <small class="text-red-500" v-if="errors.country">{{ errors.country }}</small>
        </div>

        <div>
          <label class="block font-medium">State / Province</label>
          <InputText v-model="form.state" class="w-full" placeholder="State" />
        </div>

        <div>
          <label class="block font-medium">City</label>
          <InputText v-model="form.city" class="w-full" placeholder="City" />
        </div>

        <div>
          <label class="block font-medium">Zip / Postal Code</label>
          <InputText v-model="form.postal_code" class="w-full" placeholder="Postal Code" />
        </div>

        <div class="col-span-2">
          <label class="block font-medium">Website</label>
          <InputText v-model="form.website" class="w-full" placeholder="Website" />
          <small class="text-red-500" v-if="errors.website">{{ errors.website }}</small>
        </div>
      </div>

      <div class="flex justify-end mt-6 space-x-4">
        <Button label="Reset" severity="secondary" @click="form.reset()" />
        <Button label="Confirm" type="submit" severity="primary" :loading="form.processing" />
      </div>
    </form>
</template>

<script setup lang="ts">
import { inject, Ref, ref } from 'vue';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import { useForm } from '@inertiajs/vue3';
import { DynamicDialogInstance } from 'primevue/dynamicdialogoptions';
import axios from 'axios';
import { Select, Textarea, useToast } from 'primevue';
import Dropdown from '@/Components/Dropdown.vue';

const toast = useToast();
const dialogRef = inject('dialogRef') as Ref<DynamicDialogInstance>;

const form = useForm({
  vendor: "",
  first_name: "",
  last_name: "",
  email: "",
  phone: "",
  optional_phone: "",
  currency: "",
  address: "",
  country: "",
  state: "",
  city: "",
  postal_code: "",
  website: "",
});

const errors: Ref<any> = ref({});
const currencies = ref(["USD", "CAD"]);

function submitForm() {
  axios.post(route("vendor.store"), { ...form, vendor: form.vendor })
    .then(() => {
      form.reset();
      toast.add({
        severity: "success",
        summary: "Success",
        detail: `Vendor created successfully!`,
        life: 3000,
      });
      dialogRef.value.close();
    })
    .catch((err) => {
      errors.value = err.response?.data?.errors || {};
      toast.add({ severity: "error", summary: "Error", detail: "Please try again", life: 3000 });
    });
}
</script>
