<template>
  <form @submit.prevent="submitForm" class="w-full p-6 rounded-lg">
    <div class="grid w-full grid-cols-6 gap-4">
      <div class="col-span-6">
        <InputLabel for="title" value="Title" class="text-xl mb-1" />
        <InputText v-model="form.title" type="text" placeholder="Insert" inputId="title" class="w-full" />
      </div>
      <Tabs value="0" class="col-span-6">
        <TabList>
          <Tab value="0">Customers</Tab>
          <Tab value="1">Prospects</Tab>
          <Tab value="2">Custom</Tab>
        </TabList>
        <TabPanels>
          <TabPanel value="0">
            <DataTable
              title=""
              :key="customerTableData.length"
              :items="customerTableData"
              :headers="createMailListHeaders"
              :selection="selectedCustomers"
              @update:selected="handleCustomerSelection"></DataTable>
          </TabPanel>
          <TabPanel value="1">
            <DataTable
              title=""
              :key="prospectTableData.length"
              :items="prospectTableData"
              :headers="createMailListHeaders"
              :selection="selectedProspects"
              @update:selected="handleProspectSelection"></DataTable>
          </TabPanel>
          <TabPanel value="2">
            <DataTable
              title=""
              :key="customContactTableData.length"
              :items="customContactTableData"
              :headers="createMailListHeaders"
              :actions="customActions"
              @update:selected="handleCustomSelection"></DataTable>
          </TabPanel>
        </TabPanels>
      </Tabs>
      <div class="col-span-6 grid grid-cols-2 w-full gap-x-4">
        <Button type="button" label="Reset" icon="pi pi-times" variant="text" class="col-span-1" />
        <Button type="submit" label="Submit" icon="pi pi-send" class="col-span-1" />
      </div>
    </div>
  </form>
  <Dialog v-model:visible="addContact" header="Add Custom Contact" modal>
    <form method="post" @submit.prevent="addCustomContact" class="p-fluid">
      <div class="flex flex-col py-3">
        <InputLabel for="name">Name</InputLabel>
        <InputText id="name" v-model="customContact.name" />
        <Message severity="error" text="" v-if="nameEmpty">This is a required field</Message>
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
import { Contact, MailingList } from "@/Lib/types";
import { Button, Dialog, InputText, Message, Tab, TabList, TabPanel, TabPanels, Tabs, useToast } from "primevue";
import { inject, onMounted, reactive, ref, Ref, watch } from "vue";
import { createMailListHeaders } from "../IndexData";
import axios from "axios";
import { DynamicDialogInstance } from "primevue/dynamicdialogoptions";

const toast = useToast();


const dialogRef = inject("dialogRef") as Ref<DynamicDialogInstance>;

const customerTableData: Ref<Contact[]> = ref([]);
const prospectTableData: Ref<Contact[]> = ref([]);
const customContactTableData: Ref<Contact[]> = ref([]);

const selectedCustomers: Ref<Contact[]> = ref([]);
const selectedProspects: Ref<Contact[]> = ref([]);
const selectedCustomContacts: Ref<Contact[]> = ref([]);

const addContact = ref(false);
const nameEmpty = ref(false);
const emailEmpty = ref(false);

const customContact = reactive({
  email: "",
  name: "",
});

const form = reactive({
  title: "",
  id: null as number | null, // Add this line to store list.id
});

// After the onMounted function declaration, add this code to parse selected contacts
onMounted(() => {
  dialogRef.value.data.contacts.forEach((customer: Contact) => {
    if (customer.type == "customer") {
      customerTableData.value.push(customer);
    } else if (customer.type == "prospect") {
      prospectTableData.value.push(customer);
    } else {
      customContactTableData.value.push(customer);
    }
  });

  // Parse selected contacts from the mailing list if it exists
  if (dialogRef.value.data.selected) {
    const list = dialogRef.value.data.selected as MailingList;
    form.title = list.title;
    form.id = list.id; // Store the list.id
    
    try {
      const emails = JSON.parse(list.emails);
      const names = JSON.parse(list.names);

      emails.forEach((email: string) => {
        const contact = dialogRef.value.data.contacts.find((c: Contact) => c.email === email);
        if (contact) {
          if (contact.type === "customer") {
            selectedCustomers.value.push(contact);
          } else if (contact.type === "prospect") {
            selectedProspects.value.push(contact);
          } else {
            selectedCustomContacts.value.push(contact);
          }
        }
      });
    } catch (e) {
      console.error("Error parsing mailing list data", e);
      toast.add({ severity: "error", summary: "Error", detail: "Could not parse mailing list data", life: 3000 });
    }
  }
});

// Update the submitForm function to include the list.id for updates
const submitForm = async () => {
  const selected = [...selectedCustomContacts.value, ...selectedCustomers.value, ...selectedProspects.value];
  const payload = {
    emails: JSON.stringify(selected.map((contact) => contact.email)),
    names: JSON.stringify(selected.map((contact) => contact.name)),
    title: form.title,
    id: form.id, // Include the id for updates
  };

  try {
    // Use PUT for updates, POST for new lists
    const endpoint = form.id 
      ? route("mailing_list.update", { id: form.id }) 
      : route("mailing_list.store");
    const method = form.id ? "put" : "post";
    
    await axios[method](endpoint, payload);
    toast.add({ 
      severity: "success", 
      summary: "Success", 
      detail: form.id ? "Mailing list updated successfully" : "Mailing list created successfully", 
      life: 3000 
    });
    dialogRef.value.close();
  } catch (e) {
    console.log(e);
    toast.add({ severity: "error", summary: "Error", detail: "Something went wrong! Please try again", life: 3000 });
  }
};

const customActions = [
  {
    label: "",
    icon: "pi pi-plus",
    action: () => {
      addContact.value = true;
    },
  },
];

const addCustomContact = async () => {
  if (customContact.name == "") {
    nameEmpty.value = true;
  }
  if (customContact.email == "") {
    emailEmpty.value = true;
  }
  if (nameEmpty.value || emailEmpty.value) {
    return;
  }

  axios.post(route("contact.store"), customContact).then((response) => {
    toast.add({ severity: "success", summary: "Success", detail: "Contact added successfully", life: 3000 });
    customContactTableData.value.push(response.data);
    addContact.value = false;
  })
};

const handleCustomerSelection = (selected: any[]) => {
  selectedCustomers.value = selected;
};

const handleProspectSelection = (selected: any[]) => {
  selectedProspects.value = selected;
};

const handleCustomSelection = (selected: any[]) => {
  selectedCustomContacts.value = selected;
};

watch(customContact, (value) => {
  if (value.name != "" && nameEmpty.value) {
    nameEmpty.value = false;
  }
  if (value.email != "" && emailEmpty.value) {
    emailEmpty.value = false;
  }
});
</script>
