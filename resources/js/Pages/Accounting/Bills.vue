<template>
  <div>
    <section class="w-[90%] mx-auto mt-4">
      <AccountingTabs>
        <div class="w-full flex justify-center bg-[var(--p-tabs-tablist-background)] pt-3">
          <Tabs v-model:value="currentTab" @update:value="filterBills">
            <TabList class="w-full flex !justify-center items-center">
              <Tab value="/bills">All</Tab>
              <Tab value="/bills?status=paid">Paid</Tab>
              <Tab value="/bills?status=unpaid">Unpaid</Tab>
            </TabList>
          </Tabs>
        </div>
        <DataTable title="Bills" :items="tableData" :headers="billHeaders" :actions="actions" :selected="selectedItems" @update:selected="handleSelection"
        ></DataTable>
      </AccountingTabs>
    </section>
  </div>
</template>

<script setup lang="ts">
import AccountingTabs from "@/Components/AccountingTabs.vue";
import DataTable, { ITableActions } from "@/Components/DataTable.vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import { Bill, Expense } from "@/Lib/types";
import { Tab, TabList, Tabs, useDialog, useConfirm } from "primevue";
import { onMounted, ref, Ref, watch } from "vue";
import { billHeaders } from "./data";
import AddBills from "./Modals/AddBills.vue";
import ShowPayments from "./Modals/ShowPayments.vue";
import { router } from "@inertiajs/vue3";
import ShowBillPayments from "./Modals/ShowBillPayments.vue";
import axios from "axios";
import { useToast } from "primevue";

const dialog = useDialog();
const confirm = useConfirm();
const toast = useToast();
const props = defineProps({
  items: Array<Bill>,
  data_status: String,
});

const tableData: Ref<Bill[]> = ref([]);
const selectedItems: Ref<Bill[]> = ref([]);

const currentTab = ref("/bills");

// watcher to update table data when items prop changes
watch(
  () => props.items,
  newItems => {
    tableData.value = newItems.map(item => ({
      ...item,
      actions: getItemActions(item),
      status: item.status === 1 ? "Paid" : "Unpaid",
    }))
  }
)

onMounted(() => {
  currentTab.value = `/bills${props.data_status !== "all" ? `?status=${props.data_status}` : ""}`;
  console.log("bills mounted", props.items);
  selectedItems.value = [];
  tableData.value = props?.items?.map((item) => ({
    ...item,
    actions: getItemActions(item),
    status: item.status === 1 ? "Paid" : "Unpaid",
  })) ?? ([] as Bill[]);
});

const actions: ITableActions[] = [
  {
    label: "Add bills",
    icon: "pi pi-plus",
    action: () => {
      dialog.open(AddBills, { props: { modal: true, header: "Add new bills" } });
    },
  },
  {
    label: "Edit bill",
    icon: "pi pi-pencil",
    disable: (selectedItems) => selectedItems.length === 0,
    action: () => {
      dialog.open(AddBills, {data: {bills: selectedItems.value}, props: { modal: true, header: "Edit bills" } });
    },
  },
  {
    label: "Delete bills",
    icon: "pi pi-trash",
    severity: "danger",
    disable: (selectedItems) => selectedItems.length === 0,
    action: () => {
      deleteBills();
    },
  },
];

const filterBills = () => {
  window.location.assign(`/accounting${currentTab.value}`);
};

const handleSelection = (items: Bill[]) => {
  selectedItems.value = items;
};

const deleteBills = () => {
  confirm.require({
    message: `Are you sure you want to delete ${selectedItems.value.length} bill(s)?`,
    header: "Confirmation",
    icon: "pi pi-exclamation-triangle",
    rejectProps: {
      label: "Cancel",
      severity: "secondary",
      outlined: true,
    },
    acceptProps: {
      label: "Delete",
    },
    accept: async () => {
      try {
        const billIds = selectedItems.value.map(bill => bill.id);
        
        // Delete each bill
        for (const billId of billIds) {
          await axios.delete(route("bills.destroy", billId));
        }
        
        // Show success toast
        toast.add({ 
          severity: "success", 
          summary: "Success", 
          detail: `${billIds.length} bill(s) deleted successfully`, 
          life: 3000 
        });
        
        // Clear selected items
        selectedItems.value = [];
        
        // Reload the page
        router.reload({ only: ["items"] });
      } catch (error) {
        console.error("Error deleting bills:", error);
        toast.add({ 
          severity: "error", 
          summary: "Error", 
          detail: "Failed to delete bill(s)", 
          life: 3000 
        });
      }
    },
    reject: () => {},
  });
};

const getItemActions = (item: Bill) => {
  console.log(item.status);
  if (item.status === 1) {
    return [
      {
        label: "View Payments", icon: "pi pi-list", action: () => {
          dialog.open(ShowBillPayments, {
            data: {
              paidPayments: item.payments,
              view: "view"
            },
            props: {
              modal: true
            },
            onClose: () => {
              router.reload({ only: ["items"] });
            }
          })
        }
      },
    ];
  }

  return [
    {
      label: "Record / View Payments",
      icon: "pi pi-save",
      action: () => {
        dialog.open(ShowBillPayments, {
          data: {
            paidPayments: item.payments,
            paidId: item.id,
            view: "all",
            saleId: item.sale_id,
            paidAmount: item.balance_remaining
          },
          props: {
            modal: true
          },
          onClose: () => {
            router.reload({ only: ["items"] });
          }
        })
      }
    },
  ];
};

defineOptions({ layout: AppLayout });
</script>
