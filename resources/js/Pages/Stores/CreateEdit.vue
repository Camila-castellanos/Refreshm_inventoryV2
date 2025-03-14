<template>
  <div class="flex justify-center w-full px-4 py-6">
    <Card class="w-full max-w-[800px]">
      <template #title>
        <div class="flex items-center">
          <Button icon="pi pi-arrow-left" link class="mr-2" @click="router.visit(route('stores.index'))" />
          <h2 class="text-xl font-semibold">{{ storeEdit?.id ? "Edit" : "Create" }} Store</h2>
        </div>
      </template>

      <template #content>
        <div class="space-y-6">
          <!-- Create Store Section -->
          <div class="space-y-4 flex justify-center gap-2 items-start">
            <div class="field w-full">
              <label class="block mb-2">Name:</label>
              <InputText v-model="form.name" placeholder="Enter store name" class="w-full" />
            </div>

            <div class="field w-full !mt-0">
              <label class="block mb-2">Address:</label>
              <InputText v-model="form.address" placeholder="Enter store address" class="w-full" />
            </div>
          </div>

          <!-- Create Default Admin Section -->
          <div class="space-y-4" v-if="!storeEdit?.id">
            <h3 class="text-lg font-medium">Create Default Admin</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="field">
                <label class="block mb-2">Admin Name:</label>
                <InputText v-model="form.adminname" placeholder="Enter admin name" class="w-full" />
              </div>

              <div class="field">
                <label class="block mb-2">Email:</label>
                <InputText v-model="form.email" type="email" placeholder="Enter admin email" class="w-full" />
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="field w-full">
                <label class="block mb-2">Password:</label>
                <Password v-model="form.password" placeholder="Enter password" class="w-full" toggleMask fluid :feedback="false" />
              </div>

              <div class="field w-full">
                <label class="block mb-2">Confirm Password:</label>
                <Password v-model="form.confirmPassword" placeholder="Confirm password" class="w-full" toggleMask fluid :feedback="false" />
                <Message severity="error" v-if="form.password !== form.confirmPassword">Password must match</Message>
              </div>
            </div>
          </div>

          <!-- Receipt Settings Section -->
          <div class="space-y-4">
            <h3 class="text-lg font-medium">Receipt Settings</h3>

            <div class="field">
              <label class="block mb-2">Header:</label>
              <Editor v-model="form.header" editorStyle="height: 200px" placeholder="Enter receipt header content" />
            </div>

            <div class="field">
              <label class="block mb-2">Footer:</label>
              <Editor v-model="form.footer" editorStyle="height: 200px" placeholder="Enter receipt footer content" />
            </div>

            <div class="field">
              <label class="block mb-2">Logo:</label>
              <div class="flex items-center">
                <FileUpload
                  mode="basic"
                  :multiple="false"
                  accept="image/*"
                  chooseLabel="Choose File"
                  class="w-auto"
                  @select="handleFileSelect" />
                <div class="ml-3" v-if="form.logo">
                  <img :src="imagePreview" alt="Logo Preview" class="h-16 rounded" />
                </div>
              </div>
            </div>

            <div class="flex justify-end gap-2 pt-4">
              <Button label="Reset" outlined @click="resetForm" />
              <Button label="Confirm" @click="onFormSubmit" :disabled="form.password !== '' && form.confirmPassword !== form.password" />
            </div>
          </div>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, Ref } from "vue";
import { useToast } from "primevue/usetoast";
import axios from "axios";
import { router } from "@inertiajs/vue3";
import Editor from "primevue/editor";
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import Password from "primevue/password";
import FileUpload from "primevue/fileupload";
import Card from "primevue/card";
import { Store } from "@/Lib/types";
import AppLayout from "@/Layouts/AppLayout.vue";
import { Message } from "primevue";

const props = defineProps<{ storeEdit: Store }>();
const toast = useToast();

const requestUrl: Ref<string> = ref(route("stores.store"));

onMounted(() => {
  console.log(props?.storeEdit);
  if (props?.storeEdit) {
    requestUrl.value = route("stores.update", props.storeEdit.id);

    form.name = props.storeEdit.name || "";
    form.address = props.storeEdit.address || "";
    form.email = props.storeEdit.email || "";
    form.header = props.storeEdit.header || "";
    form.footer = props.storeEdit.footer || "";

    // Si hay un logo guardado, mantenlo
    form.logo = props.storeEdit.logo ? props.storeEdit.logo : null;
  }
});

const form: {
  name: string;
  address: string;
  email: string;
  header: string;
  footer: string;
  adminname: string;
  password: string;
  confirmPassword: string;
  logo: string | null;
} = reactive({
  name: "",
  address: "",
  email: "",
  header: "",
  footer: "",
  adminname: "",
  password: "",
  confirmPassword: "",
  logo: null,
});

const handleFileSelect = (event: any) => {
  form.logo = event.files[0];
};

const imagePreview = computed(() => {
  console.log(form.logo);
  return form.logo && typeof form.logo === "object" ? URL.createObjectURL(form.logo) : "";
});

const onFormSubmit = () => {
  let formData = new FormData();
  formData.append("name", form.name);
  formData.append("address", form.address);
  formData.append("header", form.header);
  formData.append("footer", form.footer);
  formData.append("email", form.email);

  if (form.logo) formData.append("logo", form.logo);

  if (!props?.storeEdit?.id) {
    formData.append("adminname", form.adminname);
    formData.append("password", form.password);
    formData.append("password_confirmation", form.confirmPassword);

    axios
      .post(requestUrl.value, formData, { headers: { "Content-Type": "multipart/form-data", Accept: "application/json" } })
      .then((response) => {
        toast.add({ severity: "success", summary: "Success", detail: `Store saved successfully: ${response.data.name}`, life: 3000 });
        resetForm();
      })
      .catch((error) => {
        let textMsg = error.response ? Object.values(error.response.data.errors).join("\n") : "An error occurred";
        toast.add({ severity: "error", summary: "Error", detail: textMsg, life: 5000 });
      });
    return;
  }
  formData.append("_method", "PUT");

  axios
    .post(requestUrl.value, formData, { headers: { "Content-Type": "multipart/form-data", Accept: "application/json" } })
    .then((response) => {
      toast.add({ severity: "success", summary: "Success", detail: `Store saved successfully: ${response.data.name}`, life: 3000 });
      resetForm();
    })
    .catch((error) => {
      let textMsg = error.response ? Object.values(error.response.data.errors).join("\n") : "An error occurred";
      toast.add({ severity: "error", summary: "Error", detail: textMsg, life: 5000 });
    });
};

const resetForm = () => {
  Object.assign(form, {
    name: "",
    address: "",
    email: "",
    header: "",
    footer: "",
    adminname: "",
    password: "",
    confirmPassword: "",
    logo: null,
  });
};

defineOptions({ layout: AppLayout });
</script>
