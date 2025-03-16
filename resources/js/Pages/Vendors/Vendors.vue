<template>
  <section class="w-[90%] mx-auto mt-24">
    <DataTable title="Vendors" :actions="tableActions" :items="vendorList" :headers="VendorHeaders" @update:selected="handleSelection" />
  </section>

  <Dialog v-model:visible="showCreateModal" modal :header="formType === 'Create' ? 'Vendor Creation Form' : 'Edit Vendor Form'">
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
        <Button label="Reset" severity="secondary" @click="formType === 'Edit' ? loadVendorData() : form.reset()" />
        <Button :label="formType === 'Create' ? 'Save' : 'Update'" type="submit" severity="primary" :loading="form.processing" />
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
import { onMounted, ref, Ref, watchEffect } from "vue";
import { VendorHeaders } from "./VendorsData";

const toast = useToast();
const confirm = useConfirm();

const props = defineProps({ vendors: Array<Vendor> });

const currencies = ref(["USD", "CAD"]);
const vendorList: Ref<Vendor[]> = ref([]);
const selectedVendors: Ref<Vendor[]> = ref([]);

const formType = ref<"Create" | "Edit">("Create");
const editVendorId = ref<number | null>(null);
const showCreateModal: Ref<boolean> = ref(false);

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

function submitForm() {
  const url = formType.value === "Create" ? route("vendor.store") : route("vendor.update", editVendorId.value);

  const method = formType.value === "Create" ? "post" : "put";

  axios({ method, url, data: form })
    .then(() => {
      showCreateModal.value = false;
      form.reset();
      toast.add({
        severity: "success",
        summary: "Success",
        detail: `Vendor ${formType.value === "Create" ? "created" : "updated"} successfully!`,
        life: 3000,
      });
      router.reload({ only: ["vendors"] });
    })
    .catch((err) => {
      errors.value = err.response?.data?.errors || {};
      toast.add({ severity: "error", summary: "Error", detail: "Please try again", life: 3000 });
    });
}

function loadVendorData() {
  const vendor = selectedVendors.value[0];
  if (vendor) {
    form.defaults(vendor);
    form.reset();
  }
}

function editVendor(vendor: Vendor) {
  formType.value = "Edit";
  editVendorId.value = vendor.id;
  form.defaults(vendor);
  form.reset();
  showCreateModal.value = true;
}

const handleSelection = (selected: Vendor[]) => {
  selectedVendors.value = selected;
};

const parseItemsData = () => {
  vendorList.value =
    props.vendors?.map((vendor) => ({
      ...vendor,
      actions: [
        {
          label: "Edit",
          icon: "pi pi-pencil",
          action: () => editVendor(vendor),
        },
      ],
    })) || [];
}

onMounted(() => {
  parseItemsData();
});

watchEffect(() => {
  if (props.vendors) {
    parseItemsData();
  }
});

const tableActions: ITableActions[] = [
  {
    label: "New Vendor",
    icon: "pi pi-plus",
    action: () => {
      formType.value = "Create";
      form.reset();
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
];

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

defineOptions({ layout: AppLayout });
</script>
