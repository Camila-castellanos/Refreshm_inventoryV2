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
              <DataTable title="" :items="customerTableData" :headers="createMailListHeaders"></DataTable>
            </TabPanel>
            <TabPanel value="1">
              <DataTable title="" :items="prospectTableData" :headers="createMailListHeaders"></DataTable>
            </TabPanel>
            <TabPanel value="2">
              <DataTable title="" :items="customContactTableData" :headers="createMailListHeaders" :actions="customActions"></DataTable>
            </TabPanel>
          </TabPanels>
        </Tabs>
      </div>
    </div>
  </form>
  <Dialog v-model:visible="addContact" header="Add Custom Contact" modal>
    <form method="post" @submit.prevent="addCustomContact" class="p-fluid">
      <div class="flex flex-col py-3">
        <InputLabel for="name">Name</InputLabel>
        <InputText id="name" v-model="customContact.name" />
        <Message severity="error" text="" v-if="nameEmpty" >This is a required field</Message>
      </div>
      <div class="flex flex-col py-3">
        <InputLabel for="email">Name</InputLabel>
        <InputText id="email" v-model="customContact.email" type="email" />
        <Message severity="error" v-if="emailEmpty">This is a required field</Message>
      </div>
      <div class="w-full mt-5">
        <Button label="Add" type="submit" class="w-full" />
      </div>
    </form>
  </Dialog>
</template>
<script setup lang="ts">
import DataTable from "@/Components/DataTable.vue";
import InputLabel from "@/Components/InputLabel.vue";
import { Contact } from "@/Lib/types";
import { Dialog, InputText, Message, Tab, TabList, TabPanel, TabPanels, Tabs } from "primevue";
import { inject, onMounted, reactive, ref, Ref, watch } from "vue";
import { createMailListHeaders } from "../IndexData";

interface AllData {
  customers: Contact[];
  prospects: Contact[];
  custom: Contact[];
}

const form = reactive({
  title: "",
});

const dialogRef: any = inject("dialogRef");

const customerTableData: Ref<Contact[]> = ref([]);
const prospectTableData: Ref<Contact[]> = ref([]);
const customContactTableData: Ref<Contact[]> = ref([]);
const allData: Ref<AllData> = ref({ customers: [], prospects: [], custom: [] });
  const customContact = reactive({
    name: "",
    email: "",
  });
const addContact = ref(false);
const nameEmpty = ref(false);
const emailEmpty = ref(false);
onMounted(() => {
  console.log(dialogRef.value.data);
  dialogRef.value.data.contacts.forEach((customer: Contact) => {
    if (customer.type == "customer") {
      customerTableData.value.push(customer);
    } else if (customer.type == "prospect") {
      prospectTableData.value.push(customer);
    } else {
      customContactTableData.value.push(customer);
    }
    
  })
  allData.value = {
    customers: customerTableData.value,
    prospects: prospectTableData.value,
    custom: customContactTableData.value
  }
})

const customActions = [
  {
    label: "",
    icon: "pi pi-plus",
    action: () => {addContact.value = true;},
  }
]

const submitForm = () => {
  console.log(form);
};

const addCustomContact = () => {
  if (customContact.name == "") {
    nameEmpty.value = true;
  }
  if (customContact.email == "") {
    emailEmpty.value = true;
  }
  if( nameEmpty.value || emailEmpty.value ){
    return
  }
  addContact.value = false;
  customContactTableData.value.push({name: customContact.name, email: customContact.email});
}

watch(customContact, (value) => {
  if (value.name != "" && nameEmpty.value) {
    nameEmpty.value = false;
  }
  if (value.email != "" && emailEmpty.value) {
    emailEmpty.value = false;
  }
})
</script>
