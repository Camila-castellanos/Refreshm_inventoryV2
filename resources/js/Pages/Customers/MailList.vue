<template>
  
  <div>
    <section class="w-[90%] mx-auto mt-4">
      <ContactTabs>
        <DataTable
          title="Mailing List"
          @update:selected="handleSelection"
          :items="tableData"
          :headers="mailingListHeaders"
          :actions="mailingListActions"></DataTable>
      </ContactTabs>
    </section>
  </div>
</template>
<script setup lang="ts">
import ContactTabs from "@/Components/ContactTabs.vue";
import DataTable from "@/Components/DataTable.vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import { Contact, EmailTemplate, MailingList } from "@/Lib/types";
import { router } from "@inertiajs/vue3";
import { ConfirmDialog, useConfirm, useDialog, useToast } from "primevue";
import { onMounted, Ref, ref, watchEffect } from "vue";
import { mailingListHeaders } from "./IndexData";
import MailList from "./Modals/MailList.vue";
import SendEmail from "./Modals/SendMailList.vue";

const props = defineProps({
  contacts: Array<Contact>,
  email_templates: Array<EmailTemplate>,
  mailing_lists: Array<MailingList>,
});
const toast = useToast();
const confirm = useConfirm();
const dialog = useDialog();

const tableData: Ref<MailingList[]> = ref([]);

const mailingListActions = [
  {
    label: "Create new list",
    icon: "pi pi-plus",
    action: () => openAddMailList(),
  },
  {
    label: "Delete lists",
    icon: "pi pi-trash",
    severity: "danger",
    action: () => {},
    disable: (selectedItems: any[]) => selectedItems.length == 0,
  },
];

const selectedItems: Ref<MailingList[]> = ref([]);

const handleSelection = (selected: MailingList[]) => {
  selectedItems.value = selected;
};

function openAddMailList() {
  dialog.open(MailList, {
    data: { contacts: props.contacts },
    props: { modal: true },
    onClose: () => router.reload(),
  });
}

onMounted(() => {
  parseItemsData();
});

const parseItemsData = () => {
  tableData.value =
    props?.mailing_lists?.map((list) => {
      const emails: string[] = JSON.parse(list.emails);
      const names: string[] = JSON.parse(list.names);
      return {
        ...list,
        names: names.join(", "),
        emails: emails.join(", "),
        actions: [
          {
            label: "Edit",
            icon: "pi pi-pencil",
            outlined: true,
            action: () => {
              dialog.open(MailList, {
                data: { contacts: props.contacts, selected: list },
                props: { modal: true },
                onClose: () => router.reload(),
              });
            },
          },
          {
            label: "Delete",
            icon: "pi pi-trash",
            severity: "danger",
            action: () => onDelete(route("mailing_list.destroy", list.id)),
          },
          {
            label: "Send",
            icon: "pi pi-envelope",
            severity: "info",
            action: () => {
              dialog.open(SendEmail, {
                data: { contacts: emails, templates: props.email_templates },
                props: { modal: true },
                onClose: () => router.reload(),
              });
            }
          },
        ],
      };
    }) ?? [];
};

const onDelete = (url: string) => {
  confirm.require({
    message: "Are you sure? You won't be able to revert this!",
    header: "Confirmation",
    icon: "pi pi-exclamation-triangle",
    acceptClass: "p-button-danger",
    accept: async () => {
      try {
        const response = await axios.delete(url);
        
        if (response.status >= 200 && response.status < 400) {
          toast.add({ severity: "success", summary: "Deleted", detail: "The item has been deleted successfully.", life: 3000 });
          router.reload();
        }
      } catch (error: any) {
        let errorMessage = "An unknown error occurred.";
        
        if (error.response) {
          errorMessage = error.response.data;
        } else if (error.request) {
          errorMessage = "No response received from the server.";
        } else {
          errorMessage = error.message;
        }
        
        toast.add({ severity: "error", summary: "Error", detail: errorMessage, life: 5000 });
      }
    },
    reject: () => {
      toast.add({ severity: "info", summary: "Cancelled", detail: "Deletion cancelled.", life: 3000 });
    }
  });
};


watchEffect(() => {
  if (tableData.value) {
    parseItemsData();
  }
});

defineOptions({ layout: AppLayout });
</script>
