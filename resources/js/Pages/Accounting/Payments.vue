<template>
  <div>
    <section class="w-[90%] mx-auto mt-4">
      <AccountingTabs>
        <div class="w-full flex justify-center bg-[var(--p-tabs-tablist-background)] pt-3">
          <Tabs v-model:value="currentTab" @update:value="filterPayments">
            <TabList class="w-full flex !justify-center items-center">
              <Tab value="/payments">All</Tab>
              <Tab value="/payments?status=paid">Paid</Tab>
              <Tab value="/payments?status=unpaid">Unpaid</Tab>
            </TabList>
          </Tabs>
        </div>
        <DataTable title="Payments" :items="tableData" :headers="headers"></DataTable>
      </AccountingTabs>
    </section>
  </div>
</template>

<script setup lang="ts">
import AccountingTabs from "@/Components/AccountingTabs.vue";
import DataTable from "@/Components/DataTable.vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import { EmailTemplate, PaymentResponse as IPaymentResponse } from "@/Lib/types";
import { Tab, TabList, Tabs, useDialog } from "primevue";
import { onMounted, ref } from "vue";
import { headers } from "./data";
import ShowPayments from "./Modals/ShowPayments.vue";
import { router } from "@inertiajs/vue3";

const props = defineProps({
  items: Array<IPaymentResponse>,
  data_status: String,
  email_templates: Array<EmailTemplate>,
});

const tableData: any = ref([]);
const currentTab = ref("/payments");
const dialog = useDialog();

onMounted(() => {
  currentTab.value = `/payments${props.data_status !== "all" ? `?status=${props.data_status}` : ""}`;

  tableData.value = props.items!.map((item) => {
    return {
      ...item,
      amount: "$ " + item.amount_paid,
      total: "$ " + item.total,
      balance_remaining: "$" + item.balance_remaining,
      /* 
       * Actions
       * if item is paid, show: invoice, view payments, edit and send
       * if item is unpaid, show: invoice, record / view payments, edit, send
       */
      actions: getItemActions(item),
    };
  });
});

const filterPayments = () => {
  window.location.assign(`/accounting${currentTab.value}`);
};

const getItemActions = (item: IPaymentResponse) => {
  if (item.status === "Paid") {
    return [
      {
        outlined: true,
        label: "Invoice",
        icon: "",
        action: () => {
          window.location.assign(route("reports.payments.invoice", item.id));
        },
      },
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
      { outlined: true, label: "Edit", icon: "", severity: "info", action: () => { } },
      { outlined: true, label: "Send", icon: "", severity: "info", action: () => { } },
    ];
  }

  return [
    {
      outlined: true,
      label: "Invoice",
      icon: "",
      action: () => {
        window.location.assign(route("reports.payments.invoice", item.id));
      },
    },
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
    { outlined: true, label: "Edit", icon: "", severity: "info", action: () => { } },
    { outlined: true, label: "Send", icon: "", severity: "info", action: () => { } },
  ];
};

defineOptions({ layout: AppLayout });
</script>
