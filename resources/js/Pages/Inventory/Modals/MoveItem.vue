<template>
  <div class="w-[300px]">
    <form @submit.prevent="moveItems" method="post" class="w-full flex justify-center items-center flex-col">
      <Select v-model="selectedTab" :options="tabs" optionLabel="name" optionValue="id" class="w-full mb-5">
      </Select>
      
      <input v-if="selectedTab === 'OnHold'" v-model="customerName" type="text" placeholder="Customer Name" class="w-full mb-5 p-2 border rounded" />
      
      <Button type="submit" class="w-full">Move</Button>
    </form>
  </div>
</template>

<script setup>
import axios from "axios";
import { Button, Select, useToast } from "primevue";
import { inject, onMounted, ref } from "vue";

const dialogRef = inject("dialogRef");
const toast = useToast();

const tabs = ref([]);
const items = ref([]);
const customerName = ref("");

onMounted(() => {
  items.value = dialogRef.value.data.items;
  tabs.value = [{ name: "OnHold", id: "OnHold" }, ...dialogRef.value.data.tabs];
});

const selectedTab = ref(null);

const moveItems = async () => {
  if (!selectedTab.value) return;
  try {
    if (selectedTab.value === "OnHold") {
      await axios.put(route("items.hold"), {
        data: items.value,
        customer: customerName.value, 
      });
      toast.add({ severity: "success", summary: "Success", detail: "Items placed on hold!", life: 3000 });
    } else {
      for (const item of items.value) {
        await axios.post(route("tab.move"), {
          tab: selectedTab.value,
          item: item.id,
        });
      }
      toast.add({ severity: "success", summary: "Success", detail: "Items moved successfully!", life: 3000 });
    }
    dialogRef.value.close();
  } catch (error) {
    toast.add({ severity: "error", summary: "Error", detail: error.message, life: 3000 });
  }
};
</script>
