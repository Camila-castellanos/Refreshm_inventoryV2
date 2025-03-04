<template>
  <form @submit.prevent="submitForm" class="w-full p-6 rounded-lg">
    <div class="grid w-full grid-cols-6 gap-4">
      <div class="col-span-6">
        <InputLabel for="title" value="Title" class="text-xl mb-1" />
        <InputText v-model="form.title" type="text" placeholder="Insert" inputId="title" class="w-full" />
      </div>
      <div class="flex w-full justify-center col-span-6 gap-2">
        <Tabs value="0">
          <TabList>
            <Tab value="0">Customers</Tab>
            <Tab value="1">Prospects</Tab>
            <Tab value="2">Custom</Tab>
          </TabList>
          <TabPanels>
            <TabPanel value="0">
              <DataTable title="" :items="[]" :headers="[]"></DataTable>
            </TabPanel>
            <TabPanel value="1">
              <DataTable title="" :items="[]" :headers="[]"></DataTable>
            </TabPanel>
            <TabPanel value="2">
              <DataTable title="" :items="[]" :headers="[]" :actions="customActions"></DataTable>
            </TabPanel>
          </TabPanels>
        </Tabs>
      </div>
    </div>
  </form>
</template>
<script setup lang="ts">
import DataTable from "@/Components/DataTable.vue";
import InputLabel from "@/Components/InputLabel.vue";
import { InputText, Tab, TabList, TabPanel, TabPanels, Tabs } from "primevue";
import { reactive } from "vue";

const form = reactive({
  title: "",
});

const customActions = [
  {
    label: "",
    icon: "pi pi-plus",
    action: () => {},
  }
]

const submitForm = () => {
  console.log(form);
};
</script>
