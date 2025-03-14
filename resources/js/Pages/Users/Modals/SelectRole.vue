<script setup lang="ts">
import { User } from "@/Lib/types";
import { usePage } from "@inertiajs/vue3";
import axios from "axios";
import { Button, FloatLabel, Select, useToast } from "primevue";
import { DynamicDialogInstance } from "primevue/dynamicdialogoptions";
import { inject, onMounted, Ref, ref } from "vue";

const roles = ref([
  { name: "OWNER", value: "OWNER" },
  { name: "ADMIN", value: "ADMIN" },
  { name: "USER", value: "USER" },
]);

const page = usePage();
const user = (page.props.auth as any).user as User;

const toast = useToast();
const dialogRef = inject("dialogRef") as Ref<DynamicDialogInstance>;
const selected: Ref<string> = ref("");

onMounted(() => {
    if (user.role === "ADMIN") {
      roles.value = roles.value.filter((role) => role.value !== "OWNER");
    }
  })

const formSubmit = async () => {
  try {
    const response = await axios.post(route("users.updateRole", dialogRef.value.data.id), { role: selected.value });
    toast.add({ severity: "success", summary: "Users edited successfully!", detail: "The users selected are added to the location" });
    dialogRef.value.close();
  } catch (e: any) {
    console.error(e);
    toast.add({ severity: "error", summary: "Something was wrong", detail: "Please try again later" });
  }
};
</script>

<template>
  <form @submit.prevent="formSubmit" method="post">
    <FloatLabel class="w-full my-2" variant="on">
      <Select v-model="selected" :options="roles" optionLabel="name" class="w-full md:w-56" id="roles" option-value="value" />
      <label for="roles">Roles</label>
    </FloatLabel>
    <Button type="submit" size="large" label="Confirm" class="!block ml-auto" />
  </form>
</template>
