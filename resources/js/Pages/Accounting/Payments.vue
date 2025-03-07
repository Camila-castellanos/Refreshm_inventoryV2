<template>
  <div>
    <section class="w-[90%] mx-auto mt-4">
      <AccountingTabs>
        <div class="w-full flex justify-center bg-[var(--p-tabs-tablist-background)] pt-3">
          <Tabs value="?me=1">
            <TabList class="w-full flex !justify-center items-center">
              <Tab value="?me=1">All</Tab>
              <Tab value="?me=0">Paid</Tab>
              <Tab value="?me=-1">Unpaid</Tab>
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
import { PaymentResponse as IPaymentResponse, EmailTemplate } from "@/Lib/types";
import { onMounted, PropType, ref } from "vue";
import { headers } from "./data";
import { Tab, TabList, Tabs } from "primevue";

const props = defineProps({
  items: Array<IPaymentResponse>,
  data_status: String,
  email_templates: Array<EmailTemplate>,
});

const tableData = ref([]);

onMounted(() => {
  tableData.value = props.items!.map((item) => {
    return {
      ...item,
      amount: "$ " + item.amount_paid,
      total: "$ " + item.total,
      balance_remaining: "$" + item.balance_remaining,
      actions: [
        { outlined: true, label: "Invoice", icon: "", action: () => {} },
        { outlined: true, label: "View Payments", icon: "", action: () => {} },
        { outlined: true, label: "Edit", icon: "", severity: "info", action: () => {} },
        { outlined: true, label: "Send", icon: "", severity: "info", action: () => {} },
      ],
    };
  });
});

defineOptions({ layout: AppLayout });
</script>
