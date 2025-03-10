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
        <DataTable title="Bills" :items="tableData" :headers="billHeaders" :actions="actions" @update:selected="handleSelection"></DataTable>
      </AccountingTabs>
    </section>
  </div>
</template>

<script setup lang="ts">
import AccountingTabs from "@/Components/AccountingTabs.vue";
import DataTable, { ITableActions } from "@/Components/DataTable.vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import { Bill, Expense } from "@/Lib/types";
import { Tab, TabList, Tabs, useDialog } from "primevue";
import { onMounted, ref, Ref } from "vue";
import { billHeaders } from "./data";
import AddBills from "./Modals/AddBills.vue";
import ShowPayments from "./Modals/ShowPayments.vue";

const dialog = useDialog();
const props = defineProps({
  items: Array<Bill>,
  data_status: String,
});

const tableData: Ref<Bill[]> = ref([]);
const selectedItems: Ref<Bill[]> = ref([]);

const currentTab = ref("/bills");

onMounted(() => {
  currentTab.value = `/bills${props.data_status !== "all" ? `?status=${props.data_status}` : ""}`;
  tableData.value = props?.items?.map((item) => ({
    ...item,
    total: "$ " + item.total,
    amount_paid: "$ " + item.amount_paid,
    balance_remaining: "$ " + item.balance_remaining,
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
    label: "",
    icon: "pi pi-pencil",
    disable: (selectedItems) => selectedItems.length === 0,
    action: () => {
      dialog.open(AddBills, {data: {bills: selectedItems.value}, props: { modal: true, header: "Edit bills" } });
    },
  },
  {
    label: "",
    icon: "pi pi-trash",
    severity: "danger",
    disable: (selectedItems) => selectedItems.length === 0,
    action: () => {},
  },
];

const filterBills = () => {
  window.location.assign(`/accounting${currentTab.value}`);
};

const handleSelection = (items: Bill[]) => {
  selectedItems.value = items;
};

const getItemActions = (item: Bill) => {
  if (item.status === "Paid") {
    return [
      {
        outlined: true, label: "View Payments", icon: "", action: () => {
          dialog.open(ShowPayments, {
            data: {
              paidPayments: item.payments,
              view: "view"
            },
            props: {
              modal: true
            },
          })
        }
      },
    ];
  }

  return [
    {
      outlined: true,
      label: "Record / View Payments",
      icon: "",
      action: () => {
        dialog.open(ShowPayments, {
          data: {
            paidPayments: item.payments,
            paidId: item.id,
            view: "all",
            saleId: item.sale_id
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
