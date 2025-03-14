<template>
  <Toast />
  

  <section class="w-[90%] mx-auto mt-24">
    <DataTable title="Vendors" :actions="tableActions" :items="vendorList" :headers="VendorHeaders" @update:selected="handleSelection" />
  </section>

  <Dialog v-model:visible="showCreateModal" modal header="Vendor Creation Form">
    <form @submit.prevent="submitForm" class="p-6 rounded-lg w-full max-w-2xl">
      <h2 class="text-xl font-semibold mb-4">Basic Information</h2>

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
      </div>

      <h2 class="text-xl font-semibold mt-6 mb-4">Company Information</h2>

      <div class="grid grid-cols-2 gap-4">
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
          <!-- <small class="text-red-500" v-if="errors.phone">{{ errors.phone }}</small> -->
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
        <Button label="Save" type="submit" severity="primary" :loading="form.processing" />
      </div>
    </form>
  </Dialog>
</template>
<script setup lang="ts">
import DataTable, { ITableActions } from "@/Components/DataTable.vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import { Vendor } from "@/Lib/types";
import { router, useForm } from "@inertiajs/vue3";
import axios from "axios";
import { ConfirmDialog, Dialog, InputText, Textarea, useConfirm } from "primevue";
import Dropdown from "primevue/dropdown";
import { useToast } from "primevue/usetoast";
import { onMounted, ref, Ref } from "vue";
import { VendorHeaders } from "./VendorsData";

defineOptions({ layout: AppLayout });

const toast = useToast();
const confirm = useConfirm();

const props = defineProps({
  vendors: Array<Vendor>,
});

const showSuccess = () => {
  toast.add({
    severity: "success",
    summary: "Success",
    detail: "Vendor created successfully!",
    life: 3000,
  });
};

const showError = () => {
  toast.add({ severity: "error", summary: "Error", detail: "Please try again", life: 3000 });
};

const currencies = ref(["USD", "CAD"]);
const countries = ref([]);
const vendorList: Ref<Vendor[]> = ref([]);
const selectedVendors: Ref<Vendor[]> = ref([]);

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

const showCreateModal: Ref<boolean> = ref(false);

function submitForm() {
  axios
    .post("/vendor/store", form)
    .then((res) => {
      console.log(res.data);
      showCreateModal.value = false;
      form.reset();
      showSuccess();
      router.reload({ only: ["vendors"] });
    })
    .catch((err) => {
      console.error(err);
      showError();
    });
}

function confirmDelete() {
  confirm.require({
    message: "Are you sure you want to delete the selected items?",
    header: "Confirmation",
    icon: "pi pi-exclamation-triangle",
    rejectProps: {
      label: "Cancel",
      severity: "secondary",
      outlined: true,
    },
    acceptProps: {
      label: "Save",
    },
    accept: () => {
      destroyVendors();
    },
    reject: () => {},
  });
}

async function destroyVendors() {
  try {
    // Ejecutamos todas las peticiones en paralelo
    await Promise.all(selectedVendors.value.map((vendor) => axios.delete(route("vendor.destroy", { vendor: vendor.id }))));

    // Mostramos la notificación de éxito
    toast.add({
      severity: "success",
      summary: "Items deleted",
      detail: "Vendors deleted successfully",
      life: 1500,
    });
    router.reload({ only: ["vendors"] });
  } catch (err) {
    // Mostramos error solo si alguna petición falla
    toast.add({
      severity: "error",
      summary: "Error",
      detail: "Failed to delete some vendors",
      life: 1500,
    });
    console.error("Error deleting vendors:", err);
  }
}

const handleSelection = (selected: Vendor[]) => {
  selectedVendors.value = selected;
  console.log("Selected Vendors:", selectedVendors.value);
  console.log();
};

const tableActions: ITableActions[] = [
  {
    label: "New Vendor",
    icon: "pi pi-plus",
    action: () => {
      showCreateModal.value = true;
    },
  },
  {
    label: "Delete Vendor",
    icon: "pi pi-trash",
    severity: "danger",
    action: () => {
      confirmDelete();
    },
    disable: (selectedItems) => selectedItems.length == 0,
  },
  {
    label: "Edit Vendor",
    icon: "pi pi-pencil",
    action: () => {
      console.log("hi");
    },
    disable: (selectedItems) => selectedItems.length !== 1,
  },
];

onMounted(() => {
  vendorList.value = props.vendors?.map((vendor) => vendor) as Vendor[];
});
</script>
