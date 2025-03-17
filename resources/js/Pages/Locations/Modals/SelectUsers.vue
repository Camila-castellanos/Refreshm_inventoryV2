<script setup lang="ts">
import axios from "axios";
import { Button, FloatLabel, MultiSelect, useToast } from "primevue";
import { DynamicDialogInstance } from "primevue/dynamicdialogoptions";
import { inject, onMounted, Ref, ref } from "vue";

const selectedUsers: Ref<any[]> = ref([]);
const users = ref([]);

const toast = useToast();
const dialogRef = inject("dialogRef") as Ref<DynamicDialogInstance>;

const formSubmit = async () => {
  try {
    const response = await axios.post(
      route(dialogRef.value.data.updateUsers, dialogRef.value.data.id),
      selectedUsers.value.map((selected) => selected.email)
    );
    toast.add({ severity: "success", summary: "Users edited successfully!", detail: "The users selected are added to the location" });
    dialogRef.value.close();
  } catch (e: any) {
    console.error(e);
    toast.add({ severity: "error", summary: "Something was wrong", detail: "Please try again later" });
  }
};

onMounted(async () => {
  const response = await axios.get(route(dialogRef.value.data.getUsers, dialogRef.value.data.id));
  users.value = response.data.userList;
});
</script>

<template>
  <form @submit.prevent="formSubmit" method="post">
    <FloatLabel variant="in" class="w-full my-2 relative">
      <MultiSelect
        v-model="selectedUsers"
        display="chip"
        :options="users"
        optionLabel="email"
        filter
        :maxSelectedLabels="1"
        inputId="users"
        class="w-full md:w-80 h-auto"
      />
      <label for="users">Users</label>
    </FloatLabel>
    <Button type="submit" size="large" label="Confirm" class="!block ml-auto"/>
  </form>
</template>
