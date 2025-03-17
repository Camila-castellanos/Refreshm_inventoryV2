<template>
  <form @submit.prevent="formSubmit" class="flex flex-col gap-4 w-50">
    <div class="flex justify-evenly gap-4 items-center">
      <InputText v-model="location.name" placeholder="Name" class="w-full" />
      <InputText v-model="location.address" placeholder="Address" class="w-full" />
    </div>
    <div class="flex justify-around mt-4">
      <Button type="submit" label="Confirm" class="p-button-primary" />
    </div>
  </form>
</template>

<script setup lang="ts">
import axios from "axios";
import Button from "primevue/button";
import { DynamicDialogInstance } from "primevue/dynamicdialogoptions";
import InputText from "primevue/inputtext";
import { inject, reactive, Ref, ref, watchEffect } from "vue";
import { router } from "@inertiajs/vue3";

const addDialog = inject("dialogRef") as Ref<DynamicDialogInstance>;
const locationId = ref(0);
const storeId = ref(0);
const location = reactive({
  name: "",
  address: "",
});

watchEffect(() => {
  storeId.value = addDialog.value?.data.storeId;
  locationId.value = addDialog.value?.data?.id ?? 0;
  location.name = addDialog.value.data?.name ?? "";
  location.address = addDialog.value.data?.address ?? "";
});

const formSubmit = async () => {
  try {
    if (locationId.value !== 0) {
      const response = await axios.put(route("locations.update", locationId.value), { ...location });
      addDialog.value.close();
      router.reload();
      return;
    }

    const response = await axios.post(route("stores.locations.store", storeId.value), { ...location });
    addDialog.value.close();
    router.reload();
  } catch (error) {
    console.error("Error processing taxes:", error);
  }
};
</script>
