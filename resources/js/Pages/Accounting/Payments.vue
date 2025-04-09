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
        <DataTable title="Payments" :items="tableData" :headers="headers" :actions="tableActions"></DataTable>
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
import SaleEdit from "./Modals/SaleEdit.vue";
import SendEmail from "./Modals/SendEmail.vue";

const props = defineProps({
  items: Array<IPaymentResponse>,
  data_status: String,
  email_templates: Array<EmailTemplate>,
});

const tableData: any = ref([]);
const currentTab = ref("/payments");
const dialog = useDialog();


const tableActions = [
  {
    label: "Export CSV",
    important: true,
    icon: "pi pi-file-export",
    action: (callback) => {
      callback()
    },
  },
]
onMounted(() => {
  currentTab.value = `/payments${props.data_status !== "all" ? `?status=${props.data_status}` : ""}`;

  tableData.value = props.items!.map((item) => {
    return {
      ...item,
      amount_paid: "$ " + item.amount_paid,
      total: "$ " + item.total,
      balance_remaining: "$ " + item.balance_remaining,
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
        label: "Invoice",
        icon: "pi pi-receipt",
        action: () => {
          window.location.assign(route("reports.payments.invoice", item.id));
        },
      },
      {
        label: "View Payments",
        icon: "pi pi-list",
        action: () => {
          dialog.open(ShowPayments, {
            data: {
              paidPayments: item.payments,
              view: "view",
              saleId: item.sale_id,
            },
            props: {
              modal: true,
            },
          });
        },
      },
      {
        label: "Edit",
        icon: "pi pi-pencil",
        severity: "info",
        action: () => {
          console.log(item);
          dialog.open(SaleEdit, {
            data: { saleId: item.sale_id, payment: item },
            props: { modal: true, header: "Edit sale" },
            onClose: () => router.reload(),
          });
        },
      },
      {
        label: "Send",
        icon: "pi pi-envelope",
        severity: "info",
        action: () => {
          dialog.open(SendEmail, {
            data: { customer_id: item.customer_id, invoice_id: item.id, templates: props.email_templates },
            props: { modal: true },
            onClose: () => router.reload(),
          });
        },
        disable: () => !(Array.isArray(item.customer_email) && item.customer_email.length > 0),
      },
    ];
  }

  return [
    {
      label: "Invoice",
      icon: "pi pi-receipt",
      action: () => {
        window.location.assign(route("reports.payments.invoice", item.id));
      },
    },
    {
      label: "Record / View Payments",
      icon: "pi pi-save",
      action: () => {
        dialog.open(ShowPayments, {
          data: {
            paidPayments: item.payments,
            paidId: item.id,
            view: "all",
            saleId: item.sale_id,
          },
          props: {
            modal: true,
          },
          onClose: () => {
            router.reload();
          },
        });
      },
    },
    {
      label: "Edit",
      icon: "pi pi-pencil",
      severity: "info",
      action: () => {
        dialog.open(SaleEdit, {
          data: { saleId: item.sale_id, payment: item },
          props: { modal: true, header: "Edit sale" },
          onClose: () => router.reload(),
        });
      },
    },
    {
      label: "Send",
      icon: "pi pi-envelope",
      severity: "info",
      action: () => {
        dialog.open(SendEmail, {
          data: { customer_id: item.customer_id, invoice_id: item.id, templates: props.email_templates },
          props: { modal: true },
          onClose: () => router.reload(),
        });
      },
      disable: () => !(Array.isArray(item.customer_email) && item.customer_email.length > 0),
    },
  ];
};

defineOptions({ layout: AppLayout });
</script>
