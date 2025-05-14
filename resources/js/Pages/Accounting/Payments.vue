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
import { onMounted, ref, computed } from "vue";
import axios from "axios";
import { headers } from "./data";
import ShowPayments from "./Modals/ShowPayments.vue";
import { router } from "@inertiajs/vue3";
import SaleEdit from "./Modals/SaleEdit.vue";
import SendEmail from "./Modals/SendEmail.vue";
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";

const props = defineProps({
  items: Array<IPaymentResponse>,
  data_status: String,
  email_templates: Array<EmailTemplate>,
});

const currentTab = ref("/payments");
const dialog = useDialog();
const confirm = useConfirm();
const toast = useToast();


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
        action: () => openInvoice(item.id),
      },
      {
        label: "Download Invoice",
        icon: "pi pi-download",
        action: () => openInvoice(item.id, true),
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
            onClose: () => {
              router.reload();
            },
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
            onClose: () => {
              router.reload();
            },
          });
        },
        disable: () => !(Array.isArray(item.customer_email) && item.customer_email.length > 0),
      },
      {
      label: "Delete and Return",
      icon: "pi pi-trash",
      severity: "danger",
      action: () => deleteAndReturn(item),
    },
    ];
  }

  return [
    {
      label: "Invoice",
      icon: "pi pi-receipt",
      action: () => openInvoice(item.id),
    },
    {
        label: "Download Invoice",
        icon: "pi pi-download",
        action: () => openInvoice(item.id, true),
      },
    {
      label: "Record / View Payments",
      icon: "pi pi-save",
      action: () => {
        console.log(item);
        dialog.open(ShowPayments, {
          data: {
            paidPayments: item.payments,
            paidId: item.id,
            view: "all",
            saleId: item.sale_id,
            paidAmount: item.balance_remaining,
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
          onClose: () => { router.reload(); },
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
          onClose: () => { router.reload(); },
        });
      },
      disable: () => !(Array.isArray(item.customer_email) && item.customer_email.length > 0),
    },
    {
      label: "Delete and Return",
      icon: "pi pi-trash",
      severity: "danger",
      action: () => deleteAndReturn(item),
    },
  ];
};

const deleteAndReturn = (item: IPaymentResponse) => {
  confirm.require({
    message: "Are you sure you want to delete this sale and return the items?",
    header: "Confirm Deletion",
    icon: "pi pi-exclamation-triangle",
    accept: () => {
      // request to controller
      axios
        .post(route("payments.delete", {invoices: [item]}))
        .then(() => {
          toast.add({
            severity: "success",
            summary: "Success",
            detail: "Sale deleted and items returned successfully.",
            life: 3000,
          });
          router.reload(); // reload the page after deletion 
        })
        .catch((error) => {
          console.error(error);
          toast.add({
            severity: "error",
            summary: "Error",
            detail: "An error occurred while deleting the sale.",
            life: 3000,
          });
        });
    },
    reject: () => {
      console.log("Deletion cancelled.");
    },
  });
};

const tableData = computed(() => {
  if (!props.items) {
    return [];
  }
  console.log(props.items);
  return props.items.map((item) => {
    return {
      ...item,
      amount_paid: "$ " + item.amount_paid,
      total: "$ " + item.total,
      balance_remaining: "$ " + item.balance_remaining,
      actions: getItemActions(item),
    };
  });
});

// obtain the headers from the data file to has the same file name that server send
function getFilenameFromDisposition(disposition: string): string {
  let fileName = 'download.pdf'
  const match = disposition.match(/filename\*?=(?:UTF-8'')?"?([^";]+)"?/)
  if (match && match[1]) {
    fileName = decodeURIComponent(match[1])
  }
  return fileName
}

/**
 * Download or open the PDF using the filename sent by the server
 */
async function openInvoice(id: number|string, download = false) {
  const url = route("reports.payments.invoice", id);

  if (!download) {
    // 1) vista inline con el filename que envía el servidor
    window.open(url, "_blank");
    return;
  }

  // 2) descarga forzada vía blob + <a download>
  try {
    const res = await axios.get(url, { responseType: "blob" });
    const disp = res.headers["content-disposition"] || "";
    const filename = getFilenameFromDisposition(disp);
    const blob = new Blob([res.data], { type: "application/pdf" });
    const blobUrl = URL.createObjectURL(blob);

    const a = document.createElement("a");
    a.href = blobUrl;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(blobUrl);
  } catch (e) {
    toast.add({ severity: "error", summary: "Error", detail: "No se pudo descargar la factura.", life: 3000 });
  }
}
defineOptions({ layout: AppLayout });
</script>
