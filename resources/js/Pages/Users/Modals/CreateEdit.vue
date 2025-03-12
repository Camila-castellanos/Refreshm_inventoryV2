<template>
  <form @submit.prevent="onFormSubmit" method="post">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div class="field">
        <label class="block mb-2">Name:</label>
        <InputText v-model="name" class="w-full" />
      </div>

      <div class="field">
        <label class="block mb-2">Email:</label>
        <InputText v-model="email" type="email" class="w-full" />
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div class="field w-full">
        <label class="block mb-2">Password:</label>
        <Password v-model="password" class="w-full" toggleMask fluid :feedback="false" />
      </div>

      <div class="field w-full">
        <label class="block mb-2">Confirm Password:</label>
        <Password v-model="confirmPassword" class="w-full" toggleMask fluid :feedback="false" />
        <Message severity="error" v-if="password !== confirmPassword">Password must match</Message>
      </div>
    </div>
    
    <div class="flex justify-end mt-4">
      <Button type="submit" label="Confirm" size="large" />
    </div>
  </form>
</template>

<script setup lang="ts">
import axios from "axios";
import { ref, computed, onMounted, inject, Ref } from "vue";
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";
import InputText from "primevue/inputtext";
import Password from "primevue/password";
import Button from "primevue/button";
import Message from "primevue/message";
import { User } from "@/Lib/types";
import { DynamicDialogInstance } from "primevue/dynamicdialogoptions";

const dialogRef = inject('dialogRef') as Ref<DynamicDialogInstance>;
const toast = useToast();

const name = ref("");
const email = ref("");
const password = ref("");
const confirmPassword = ref("");

const requestUrl = computed(() =>
  dialogRef.value?.data?.userEdit ? `/users/${dialogRef.value.data.userEdit.id}` : "/users"
);

onMounted(() => {
  if (dialogRef.value?.data?.userEdit) {
    name.value = dialogRef.value.data.userEdit.name;
    email.value = dialogRef.value.data.userEdit.email;
  }
});

const cleanForm = () => {
  name.value = "";
  email.value = "";
  password.value = "";
  confirmPassword.value = "";
};

const onFormSubmit = async () => {
  const formData = new FormData();
  formData.append("name", name.value);
  formData.append("password", password.value);
  formData.append("password_confirmation", confirmPassword.value);
  formData.append("email", email.value);

  if (dialogRef.value?.data?.userEdit) {
    formData.append("_method", "PUT");
  }

  try {
    const response = await axios.post(requestUrl.value, formData, {
      headers: { "Content-Type": "multipart/form-data" },
    });

    toast.add({ severity: "success", summary: "User Saved", detail: `User Name: ${response.data.name}`, life: 3000 });
    cleanForm();
    dialogRef.value.close();
  } catch (error: any) {
    let errorMessage = "An unknown error occurred.";
    if (error.response) {
      errorMessage = Object.values(error.response.data.errors).join("\n");
    }
    toast.add({ severity: "error", summary: "Error", detail: errorMessage, life: 5000 });
  }
};
</script>
