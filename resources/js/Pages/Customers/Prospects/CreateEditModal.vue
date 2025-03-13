<template>
  <form class="w-full p-6 rounded-lg" @submit.prevent="submitForm">
    <div class="grid w-full grid-cols-6 gap-4">
      <div class="col-span-3">
        <label for="first_name" class="block mb-2 font-bold"> First name </label>
        <InputText v-model="form.first_name" type="text" placeholder="" inputId="first_name" class="w-full" />
      </div>

      <div class="col-span-3">
        <label for="last_name" class="block mb-2 font-bold"> Last name </label>
        <InputText v-model="form.last_name" type="text" placeholder="" inputId="last_name" class="w-full" />
      </div>

      <div class="col-span-3">
        <label for="email" class="block mb-2 font-bold"> Email </label>
        <InputText v-model="form.email" type="email" placeholder="" inputId="email" class="w-full" />
      </div>
      <div class="col-span-3">
        <label for="phone" class="block mb-2 font-bold"> Phone </label>
        <InputText v-model="form.phone" type="tel" placeholder="" inputId="email" class="w-full" />
      </div>

      <div class="col-span-3">
        <label for="company" class="block mb-2 font-bold"> Company name </label>
        <InputText v-model="form.company" type="text" placeholder="" inputId="company" class="w-full" />
      </div>
      <div class="col-span-3">
        <label for="contact_type" class="block mb-2 font-bold"> Contact type </label>
        <Select
          v-model="form.contact_type"
          :options="contactTypeOptions"
          optionLabel="label"
          placeholder="Select"
          class="w-full"
          option-value="value">
        </Select>
      </div>
      <div class="col-span-2">
        <label for="country" class="block mb-2 font-bold"> Country </label>
        <InputText v-model="form.country" type="text" placeholder="" inputId="country" class="w-full" />
      </div>
      <div class="col-span-2">
        <label for="state" class="block mb-2 font-bold"> Province/State </label>
        <InputText v-model="form.state" type="text" placeholder="" inputId="state" class="w-full" />
      </div>
      <div class="col-span-2">
        <label for="city" class="block mb-2 font-bold"> City </label>
        <InputText v-model="form.city" type="text" placeholder="" inputId="city" class="w-full" />
      </div>
      <div class="col-span-6">
        <label class="block font-medium">Address</label>
        <Textarea v-model="form.address"  class="w-full" placeholder="" rows="2" />
      </div>

      <div class="flex w-full justify-center col-span-6 gap-2">
        <Button type="submit" label="Confirm" class="w-1/2"></Button>
      </div>
    </div>
  </form>
</template>

<script setup lang="ts">
import { router, useForm } from "@inertiajs/vue3";
import axios from "axios";
import { InputText, Select, Textarea } from "primevue";
import { inject, onMounted, Ref, ref } from "vue";

const dialogRef: any = inject("dialogRef");

const prospect: Ref<any> = ref({});

onMounted(() => {
  prospect.value = dialogRef.value.data?.prospect;
  console.log(prospect.value);
  if (prospect.value) {
    form.first_name = prospect.value.first_name;
    form.last_name = prospect.value.last_name;
    form.email = prospect.value.email;
    form.company = prospect.value.company_name;
    form.city = prospect.value.city;
    form.state = prospect.value.state;
    form.country = prospect.value.country;
    form.address = prospect.value.address;
    form.phone = prospect.value.phone_number;
    form.contact_type = prospect.value.contact_type;
  }
});

const form = useForm({
  email: "",
  first_name: "",
  last_name: "",
  company: "",
  city: "",
  state: "",
  country: "",
  address: "",
  phone: "",
  contact_type: "Buyer",
});

const contactTypeOptions = ref([
  { label: "Buyer", value: "Buyer" },
  { label: "Supplier", value: "Supplier" },
]);

async function submitForm() {
  const payload = {
    first_name: form.first_name,
    last_name: form.last_name,
    email: form.email,
    company_name: form.company,
    city: form.city,
    state: form.state,
    country: form.country,
    address: form.address,
    phone_number: form.phone,
    contact_type: form.contact_type,
  };

  try {
    if (prospect.value) {
      await axios.put(route("prospects.update", { prospect: prospect.value.id }), payload);
    } else {
      await axios.post(route("prospects.store"), payload);
    }
    router.reload();
  } catch (error) {
    console.error("Error fetching storages:", error);
  }
}
</script>
