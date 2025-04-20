<template>
  <div class="flex justify-center w-full px-4 py-6">
    <Card class="w-full max-w-[800px]">
      <template #title>
        <div class="flex items-center">
          <Button icon="pi pi-arrow-left" link class="mr-2" @click="router.visit(route('stores.index'))" />
          <h2 class="text-xl font-semibold">{{ storeEdit?.id ? "Edit" : "Add" }} Location</h2>
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
              <Button label="Confirm" @click="onFormSubmit" />
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
    form.header = props.storeEdit.header || "";
    form.footer = props.storeEdit.footer || "";

    // Si hay un logo guardado, mantenlo
    form.logo = props.storeEdit.logo ? props.storeEdit.logo : null;
  }
});

const form: {
  name: string;
  address: string;
  header: string;
  footer: string;
  logo: string | null;
} = reactive({
  name: "",
  address: "",
  header: "",
  footer: "",
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


  if (form.logo) formData.append("logo", form.logo);

  if (!props?.storeEdit?.id) {
    // if no id is provided, create a new store
    axios
      .post(requestUrl.value, formData, { headers: { "Content-Type": "multipart/form-data", Accept: "application/json" } })
      .then((response) => {
        toast.add({ severity: "success", summary: "Success", detail: `Location saved successfully: ${response.data.name}`, life: 3000 });
        router.visit(route("stores.index"));
      })
      .catch((error) => {
        let textMsg = error.response ? Object.values(error.response.data.errors).join("\n") : "An error occurred";
        toast.add({ severity: "error", summary: "Error", detail: textMsg, life: 5000 });
      });
    return;
  }
  formData.append("_method", "PUT");
  // if id is provided, update the existing store
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
    header: "",
    footer: "",
    logo: null,
  });
};

defineOptions({ layout: AppLayout });
</script>
