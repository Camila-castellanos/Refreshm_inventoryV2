<template>
  <ConfirmDialog></ConfirmDialog>
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
import { useDialog } from "primevue";
import { onMounted, Ref, ref } from "vue";
import { mailingListHeaders } from "../IndexData";
import MailList from "./MailList.vue";
import { Contact, MailingList } from "@/Lib/types";

const props = defineProps({
  contacts: Array<Contact>,
  email_templates: Array,
  mailing_lists: Array<MailingList>,
});

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

const selectedItems: Ref<any[]> = ref([]);

const handleSelection = (selected: any[]) => {
  selectedItems.value = selected;
};

function openAddMailList() {
  dialog.open(MailList, {
    data: { contacts: props.contacts },
    props: { modal: true },
  });
}

onMounted(() => {
  console.log(props);
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
            label: "",
            icon: "pi pi-pencil",
            outlined: true,
            action: () => console.log(list),
          },
          {
            label: "",
            icon: "pi pi-trash",
            severity: "danger",
            action: () => console.log(list),
          },
          {
            label: "",
            icon: "pi pi-envelope",
            severity: "info",
            action: () => console.log(list)
          }
        ],
      };
    }) ?? [];
});

defineOptions({ layout: AppLayout });
</script>
