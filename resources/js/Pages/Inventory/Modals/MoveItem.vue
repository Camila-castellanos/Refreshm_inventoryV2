<template>
  <div class="w-[300px]">
    <form @submit.prevent="moveTab" method="post" class="w-full flex justify-center items-center flex-col">
      <Select v-model="selectedTab" :options="tabs" optionLabel="name" placeholder="Select" option-value="id" class="w-full mb-5"> </Select>
      <Button type="submit" class="w-full">Move</Button>
    </form>
  </div>
</template>

<script setup>
import axios from "axios";
import { Button, Select } from "primevue";
import { inject, onMounted, ref } from "vue";

const dialogRef = inject("dialogRef");

const tabs = ref([]);
const item = ref({});

onMounted(() => {
  console.log(dialogRef.value);
  item.value = dialogRef.value.data.item;
  tabs.value = dialogRef.value.data.tabs;
});

const selectedTab = ref(null);

const moveTab = async () => {
  if (selectedTab.value) {
    try {
      const response = await axios.post(route("tab.move"), {
        tab: selectedTab.value,
        item: item.value.id,
      });
      dialogRef.value.close();
    } catch (error) {
      console.error(error);
    }
  }
};
</script>
