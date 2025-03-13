<template>
  <ConfirmDialog></ConfirmDialog>
  <div>
    <section class="w-[90%] mx-auto mt-4">
      <AccountingTabs>
        <DataTable
          title="Expenses"
          :items="tableData"
          :headers="expensesHeaders"
          :actions="actions"
          @update:selected="handleSelection"></DataTable>
      </AccountingTabs>
    </section>
  </div>
</template>

<script setup lang="ts">
import AccountingTabs from "@/Components/AccountingTabs.vue";
import DataTable, { ITableActions } from "@/Components/DataTable.vue";
import { Expense } from "@/Lib/types";
import { onMounted, ref, Ref } from "vue";
import { expensesHeaders } from "./data";
import AppLayout from "@/Layouts/AppLayout.vue";
import { ConfirmDialog, useConfirm, useDialog } from "primevue";
import AddExpenses from "./Modals/AddExpenses.vue";
import axios from "axios";
import { router } from "@inertiajs/vue3";

const dialog = useDialog();
const confirm = useConfirm();
const props = defineProps({
  items: Array<Expense>,
});

const tableData: Ref<Expense[]> = ref([]);

const selectedItems: Ref<Expense[]> = ref([]);

const handleSelection = (selected: Expense[]) => {
  selectedItems.value = selected;
};

onMounted(() => {
  tableData.value = props!.items;
});

const actions: ITableActions[] = [
  {
    label: "Add expenses",
    icon: "pi pi-plus",
    action: () => {
      dialog.open(AddExpenses, {
        props: { modal: true, header: "Add new expense" },
        onClose: () => {
          router.reload({ only: ["items"] });
        },
      });
    },
  },
  {
    label: "",
    icon: "pi pi-pencil",
    disable: (selectedItems) => selectedItems.length === 0,
    action: () => {
      dialog.open(AddExpenses, {
        data: { expenses: selectedItems.value },
        props: { modal: true, header: "Edit expenses" },
        onClose: () => {
          router.reload({ only: ["items"] });
        },
      });
    },
  },
  {
    label: "",
    icon: "pi pi-trash",
    disable: (selectedItems) => selectedItems.length === 0,
    severity: "danger",
    action: () => {
      confirm.require({
        message: "Are you sure you want to delete the selected expenses?",
        header: "Delete expenses",
        icon: "pi pi-info-circle",
        accept: () => {
          axios
            .delete(route("expenses.obliterate"), {
              data: selectedItems.value,
            })
            .then(() => {
              router.reload();
            });
        },
        reject: () => {
          console.log("Reject");
        },
      });
    },
  },
];

defineOptions({ layout: AppLayout });
</script>
