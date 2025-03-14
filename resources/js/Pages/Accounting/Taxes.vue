<template>
  
  <div>
    <section class="w-[90%] mx-auto mt-4">
      <AccountingTabs>
        <DataTable
          title="Expenses"
          :items="tableData"
          :headers="taxHeaders"
          :actions="actions"
          @update:selected="handleSelection"></DataTable>
      </AccountingTabs>
    </section>
  </div>
</template>

<script setup lang="ts">
import AccountingTabs from "@/Components/AccountingTabs.vue";
import DataTable, { ITableActions } from "@/Components/DataTable.vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import { Tax } from "@/Lib/types";
import { router } from "@inertiajs/vue3";
import { ConfirmDialog, useConfirm, useDialog } from "primevue";
import { onMounted, ref, Ref, watch } from "vue";
import { taxHeaders } from "./data";
import AddTaxes from "./Modals/AddTaxes.vue";
import axios from "axios";

const props = defineProps({
  items: Array<Tax>,
});

const dialog = useDialog();
const confirm = useConfirm();

const tableData: Ref<Tax[]> = ref([]);
const selectedTaxes: Ref<Tax[]> = ref([]);

const handleSelection = (selected: Tax[]) => {
  selectedTaxes.value = selected;
};

onMounted(() => {
  tableData.value = props.items!.map((item: Tax) => {
    return {
      ...item,
      percentage: item.percentage + " %",
    };
  });
});

const actions: ITableActions[] = [
  {
    label: "Add new tax",
    icon: "pi pi-plus",
    action: () => {
      dialog.open(AddTaxes, {
        props: { modal: true, header: "Add new tax" },
        onClose: () => {
          router.reload({ only: ["items"] });
        },
      });
    },
  },
  {
    label: "",
    icon: "pi pi-pencil",
    action: () => {
      dialog.open(AddTaxes, {
        data: { taxes: selectedTaxes.value },
        props: { modal: true, header: "Add new tax" },
        onClose: () => {
          router.reload({ only: ["items"] });
        },
      });
    },
  },
  {
    label: "",
    icon: "pi pi-trash",
    severity: "danger",
    action: removeTaxes,
  },
];

watch(
  () => props.items,
  () => {
    tableData.value = props.items!.map((item: Tax) => {
      return {
        ...item,
        percentage: item.percentage + " %",
        collected: "$ " + item.collected,
      };
    });
  }
);

function removeTaxes() {
  if (selectedTaxes.value.length === 0) {
    return;
  }
  confirm.require({
    message: "Are you sure you want to remove these taxes?",
    header: "Confirmation",
    icon: "pi pi-exclamation-triangle",
    accept: () => {
      axios.post(route("taxes.remove"), { taxes: selectedTaxes.value }).then(() => {
        router.reload({ only: ["items"] });
      });
    },
    reject: () => {},
  });
}

defineOptions({ layout: AppLayout });
</script>
