<template>
    <form @submit.prevent="submitForm" class="p-6 rounded-lg w-full max-w-2xl">
      <h2 class="text-xl font-semibold mb-4">{{ formType === "Create" ? "Basic Information" : "Edit Vendor Details" }}</h2>

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
          <Dropdown v-model="form.currency" :options="currencies" class="w-full" placeholder="Select Currency" />
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
        <!-- <Button label="Reset" severity="secondary" @click="formType === 'Edit' ? loadVendorData() : form.reset()" /> -->
        <Button :label="formType === 'Create' ? 'Save' : 'Update'" type="submit" severity="primary" :loading="form.processing" />
      </div>
    </form>
</template>

<script setup lang="ts">
import { router, useForm } from "@inertiajs/vue3";
import axios from "axios";
import { InputText, Textarea } from "primevue";
import Dropdown from "primevue/dropdown";
import { useToast } from "primevue/usetoast";
import { inject, onMounted, ref, Ref, watchEffect } from "vue";
import { VendorHeaders } from "./VendorsData";
const toast = useToast();

const dialogRef: any = inject("dialogRef");
const vendor: Ref<any> = ref({});

onMounted(() => {
    vendor.value = dialogRef.value.data?.vendor;
    console.log(vendor.value);
    if (vendor.value) {

        form.vendor = vendor.value.vendor
        form.first_name = vendor.value.first_name;
        form.last_name = vendor.value.last_name;
        form.email = vendor.value.email;
        form.phone = vendor.value.phone;
        form.optional_phone = vendor.value.optional_phone;
        form.currency = vendor.value.currency
        form.address = vendor.value.address;
        form.country = vendor.value.country;
        form.city = vendor.value.city;
        form.state = vendor.value.state;  
        form.postal_code = vendor.value.postal_code;
        form.website = vendor.value.website
    }
});

const formType = ref<"Create" | "Edit">("Create");

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


async function submitForm() {
    
    const payload = {
        vendor: form.vendor,
        first_name: form.first_name,
        last_name: form.last_name,
        email: form.email,
        phone: form.phone,
        optional_phone: form.optional_phone,
        currency: form.currency,
        address: form.address,
        country: form.country,
        state: form.state,
        city: form.city,
        postal_code: form.postal_code,
        website: form.website,
    }

    try {
    if (vendor.value) {
      await axios.put(route("vendor.update", { vendor: vendor.value.id }), payload);
    } else {
      await axios.post(route("vendor.store"), payload);
    }
    router.reload();
    dialogRef.value.close()
  } catch (error) {
    console.error("Error fetching storages:", error);
  }



//   const url = formType.value === "Create" ? route("vendor.store") : route("vendor.update", editVendorId.value);
//   const method = formType.value === "Create" ? "post" : "put";
//   axios({ method, url, data: form })
//     .then(() => {
//       showCreateModal.value = false;
//       form.reset();
//       toast.add({
//         severity: "success",
//         summary: "Success",
//         detail: `Vendor ${formType.value === "Create" ? "created" : "updated"} successfully!`,
//         life: 3000,
//       });
//       router.reload({ only: ["vendors"] });
//     })
//     .catch((err) => {
//       errors.value = err.response?.data?.errors || {};
//       toast.add({ severity: "error", summary: "Error", detail: "Please try again", life: 3000 });
//     });
}

// function loadVendorData() {
//   const vendor = selectedVendors.value[0];
//   if (vendor) {
//     form.defaults(vendor);
//     form.reset();
//   }
// }

</script>