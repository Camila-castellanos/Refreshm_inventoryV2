<template>
  
  <div>
    <section class="w-[90%] mx-auto mt-4">
      <AccountingTabs>
        <DataTable
          title="Expenses"
          :items="tableData"
          :headers="taxHeaders"
          :actions="actions"
          @update:selected="handleSelection">
                <DatePicker
                v-model="dateRange"
                :max-date="new Date()"
                selectionMode="range"
                dateFormat="dd/mm/yy"
                class="w-full"
                showIcon
                fluid
                iconDisplay="input"
                @update:model-value="handleDateUpdate"
                placeholder="Date range for report"></DatePicker>
        </DataTable>
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
import DatePicker from "primevue/datepicker";

const props = defineProps({
  items: Array<Tax>,
});

const dialog = useDialog();
const confirm = useConfirm();

const tableData = ref<any[]>([]);
const selectedTaxes: Ref<Tax[]> = ref([]);
const dateRange = ref<Date | Date[] | (Date | null)[] | null | undefined>(null);

const handleSelection = (selected: Tax[]) => {
  selectedTaxes.value = selected;
};

onMounted(() => {
  console.log("mounted", props.items);
  tableData.value = props.items!.map((item: Tax) => {
    return {
      ...item,
      percentage: item.percentage + " %",
      collected: item.collected,
      paid: item.paid,
      total_sales: item.total_sales,
      total_purchases: item.total_purchases,
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
    label: "Edit Tax",
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
    label: "Delete Taxes",
    icon: "pi pi-trash",
    severity: "danger",
    action: removeTaxes,
    disable: (selectedItems: Tax[]) => selectedItems.length === 0,
  },
];

watch(
  () => props.items,
  () => {
    tableData.value = props.items!.map((item: Tax) => {
      return {
        ...item,
        percentage: item.percentage + " %",
        collected: item.collected,
        paid:  item.paid,
        total_sales:item.total_sales,
        total_purchases: item.total_purchases,
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

function handleDateUpdate(value: Date | Date[] | (Date | null)[] | null | undefined): void {
  if (Array.isArray(value) && value.length == 2 && value[0] && value[1]) {
    fetchDateWiseData(value as Date[]);
    return;
  }
  dateRange.value = value;
}

function fetchDateWiseData(dateRange: Date[]) {
  const [startDate, endDate] = dateRange;
  axios
    .post(route("taxes.datewise"), {start: startDate, end: endDate })
    .then((response) => {
      tableData.value = response.data.map((item: Tax) => ({
        ...item,
        percentage: item.percentage + " %",
        collected: item.collected,
        paid:  item.paid,
        total_sales: item.total_sales,
        total_purchases: item.total_purchases,
      }));
    })
    .catch((error) => {
      console.error("Error fetching date-wise data:", error);
    });
}

defineOptions({ layout: AppLayout });
</script>
