<template>
  <AppLayout>
    <div class="p-6">
      <h1 class="text-2xl font-bold">Welcome, {{ user.name }}</h1>

      <!-- Filtro de fecha y opciones -->
      <div class="flex justify-between flex-wrap gap-4 items-center mt-4">
        <Dropdown v-model="selectedFilter" :options="filters" class="w-40" />
        <div class="flex gap-2 w-full max-w-md">
          <FloatLabel variant="over" class="w-full">
            <Calendar v-model="getSafeCalendarValue" :selectionMode="selectedFilter === 'Current' ? 'range' : 'single'"
              showIcon dateFormat="mm/dd/yy" class="w-full" id="date-filter" ref="calendarRef"
              @update:modelValue="handleCalendarChange" :showTime="false" />
            <label for="date-filter">{{ selectedFilter === "Current" ? "Start date - End date" : "Start date" }}</label>
          </FloatLabel>
          <Dropdown v-model="quickFilter" :options="quickFilterOptions" optionLabel="label" placeholder="Quick Filter"
            class="w-48" @change="handleQuickFilter" optionValue="value" />
        </div>
      </div>

      <!-- Secciones -->
      <div class="grid grid-cols-2 grow  md:grid-cols-3 lg:grid-cols-4 gap-4 mt-6">
        <StatCard v-for="stat in inventoryStats" :key="stat.label" :label="stat.label" :value="stat.value"
          :icon="getStatConfig(stat.label).icon" :color="getStatConfig(stat.label).color"
          :currency="stat.currency ? '$' : ''" />
      </div>

      <Divider />

      <div class="grid grid-cols-2 grow  md:grid-cols-3 lg:grid-cols-4 gap-4 mt-6">
        <StatCard v-for="stat in salesStats" :key="stat.label" :label="stat.label" :value="stat.value"
          :icon="getStatConfig(stat.label).icon" :color="getStatConfig(stat.label).color"
          :currency="stat.currency ? '$' : ''" />
      </div>

      <Divider />

      <div class="grid grid-cols-2 grow  md:grid-cols-3 lg:grid-cols-4 gap-4 mt-6">
        <StatCard v-for="stat in accountingStats" :key="stat.label" :label="stat.label" :value="stat.value"
          :currency="'$'" :icon="getStatConfig(stat.label).icon" :color="getStatConfig(stat.label).color"
          @update="handleCashOnHandUpdate" />
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { computed, onMounted, Ref, ref } from "vue";
import Dropdown from "primevue/dropdown";
import Calendar from "primevue/calendar";
import Button from "primevue/button";
import Card from "primevue/card";
import Divider from "primevue/divider";
import AppLayout from "@/Layouts/AppLayout.vue";
import { Dashboard, User } from "@/Lib/types";
import { FloatLabel, InputNumber, useToast } from "primevue";
import axios from "axios";
import { startOfMonth, startOfYear, subMonths, subYears } from "date-fns";
import StatCard from "@/Components/StatCard.vue";

const toast = useToast();

const filters = ref(["Current", "Historically"]);
const selectedFilter = ref("Current");
const calendarValue = ref<Date | Date[] | null>(null);

interface Stat {
  label: string;
  value: number;
  currency?: boolean;
}

const props = defineProps<{ auth: { user: User } } & Dashboard>();
const user = ref(props.auth.user);

const inventoryStats: Ref<Stat[]> = ref([]);
const salesStats: Ref<Stat[]> = ref([]);
const accountingStats: Ref<Stat[]> = ref([]);

const quickFilter: Ref<string | null> = ref(null);

const quickFilterOptions = ref([
  { label: "Start of the Month", value: "month" },
  { label: "Start of the Year", value: "year" },
  { label: "6 Months Ago", value: "6months" },
  { label: "1 Year Ago", value: "1year" },
  { label: "Today", value: "today" },
]);

const alterCashOnHand = ref(false);
const datacashOnHand = ref(parseFloat(props.cashOnHand));
const startDate = ref<Date | null>(null);
const endDate = ref<Date | null>(null);
const isLoading = ref(false);
const calendarRef = ref();

const getSafeCalendarValue = computed(() => {
  if (selectedFilter.value === "Current") {
    if (Array.isArray(calendarValue.value)) {
      const [start, end] = calendarValue.value;
      return start && end ? [start, end] : [];
    }
    return [];
  } else {
    return calendarValue.value instanceof Date ? calendarValue.value : null;
  }
});

async function editCashOnHand() {
  try {
    isLoading.value = true;
    const response = await axios.post(route("update.cash"), { balance: datacashOnHand.value });
    if (response.status >= 200 && response.status < 300) {
      toast.add({ severity: "success", summary: "Success", detail: "Cash on Hand updated!", life: 3000 });
      location.reload();
    }
  } catch (error) {
    toast.add({ severity: "error", summary: "Error", detail: "Failed to update Cash on Hand", life: 3000 });
  } finally {
    alterCashOnHand.value = false;
    isLoading.value = false;
  }
}

function closeCalendar() {
  if (calendarRef.value && calendarRef.value.overlayVisible) {
    calendarRef.value.overlayVisible = false;
  }
}

function handleCalendarChange(value: any) {
  if (selectedFilter.value === "Current") {
    if (Array.isArray(value)) {
      const [start, end] = value;

      if (start instanceof Date && end instanceof Date) {
        startDate.value = start;
        endDate.value = end;
        applyFilter();
        closeCalendar();
      } else {
        return;
      }
    }
  } else {
    if (value instanceof Date) {
      startDate.value = value;
      endDate.value = null;
      applyFilter();
      closeCalendar();
    }
  }
}

function handleQuickFilter() {
  const today = new Date();

  switch (quickFilter.value) {
    case "month":
      startDate.value = startOfMonth(today);
      endDate.value = selectedFilter.value === "Current" ? today : null;
      break;
    case "year":
      startDate.value = startOfYear(today);
      endDate.value = selectedFilter.value === "Current" ? today : null;
      break;
    case "6months":
      startDate.value = subMonths(today, 6);
      endDate.value = today;
      break;
    case "1year":
      startDate.value = subYears(today, 1);
      endDate.value = today;
      break;
    case "today":
      startDate.value = today;
      endDate.value = selectedFilter.value === "Current" ? today : null;
      break;
  }

  if (selectedFilter.value === "Current") {
    if (startDate.value && endDate.value) {
      calendarValue.value = [startDate.value, endDate.value];
    }
  } else {
    if (startDate.value) {
      calendarValue.value = startDate.value;
    }
  }

  applyFilter();
}

async function applyFilter() {
  try {
    isLoading.value = true;
    if (selectedFilter.value === "Current") {
      await getDashboardData();
    } else {
      await getDashboardDataByDate();
    }
    toast.add({ severity: "success", summary: "Success", detail: "Dashboard data updated!", life: 3000 });
  } catch (error) {
    toast.add({ severity: "error", summary: "Error", detail: "Failed to load dashboard data.", life: 3000 });
    console.error("applyFilter() error", error);
  } finally {
    isLoading.value = false;
  }
}

async function getDashboardData() {
  const formData = new FormData();
  formData.append("startDate", startDate.value?.toISOString().split("T")[0] || "");
  formData.append("endDate", endDate.value?.toISOString().split("T")[0] || "");

  const response = await axios.post(route("report.datewise"), formData, {
    headers: { "Content-Type": "multipart/form-data" },
  });

  updateDashboardStats(response.data);
}

async function getDashboardDataByDate() {
  const formData = new FormData();
  formData.append("startDate", startDate.value?.toISOString().split("T")[0] || "");

  const response = await axios.post(route("report.datewise.date"), formData, {
    headers: { "Content-Type": "multipart/form-data" },
  });

  updateDashboardStats(response.data);
}

function updateDashboardStats(data: Dashboard) {
  inventoryStats.value = [
    { label: "Devices in Inventory", value: data.devicesInInventory },
    { label: "Devices Added", value: data.tradesThisMonth },
    { label: "Devices Sold", value: data.soldThisMonth },
    { label: "Cost of Goods Sold", value: data.costSoldThisMonth },
    { label: "Inventory Value ($)", value: data.inventoryValue, currency: true },
    { label: "Est. Sale Value of Inventory ($)", value: data.saleValue, currency: true },
  ];

  salesStats.value = [
    { label: "Revenue ($)", value: data.soldValueThisMonth, currency: true },
    { label: "Net Profit ($)", value: data.profitThisMonth - data.expensesThisMonth, currency: true },
    { label: "Expenses ($)", value: data.expensesThisMonth, currency: true },
    { label: "Gross Profit ($)", value: data.profitThisMonth, currency: true },
  ];

  accountingStats.value = [
    { label: "Accounts Receivable ($)", value: data.accountsReceivableThisMonth, currency: true },
    { label: "Accounts Payable ($)", value: data.accountsPayableThisMonth, currency: true },
    { label: "Cash on Hand ($)", value: data?.cashOnHand ? parseFloat(props.cashOnHand) : 0, currency: true },
    { label: "Sales Tax Paid ($)", value: data.salesTaxPaid, currency: true },
    { label: "Sales Tax Collected ($)", value: data.salesTaxCollected, currency: true },
    { label: "Taxed Sales ($)", value: data.taxedSales, currency: true },
    { label: "Non-taxed Sales ($)", value: data.nonTaxedSales, currency: true },
  ];
}

onMounted(() => {
  updateDashboardStats(props);
});

function getStatConfig(label: string) {
  switch (label) {
    case "Devices in Inventory":
      return { icon: "pi-database", color: "blue" };
    case "Devices Added":
      return { icon: "pi-plus-circle", color: "green" };
    case "Devices Sold":
      return { icon: "pi-box", color: "purple" };
    case "Cost of Goods Sold":
      return { icon: "pi-money-bill", color: "orange" };
    case "Inventory Value ($)":
      return { icon: "pi-briefcase", color: "cyan" };
    case "Est. Sale Value of Inventory ($)":
      return { icon: "pi-tags", color: "indigo" };
    case "Revenue ($)":
      return { icon: "pi-dollar", color: "green" };
    case "Gross Profit ($)":
      return { icon: "pi-chart-line", color: "emerald" };
    case "Net Profit ($)":
      return { icon: "pi-wallet", color: "teal" };
    case "Expenses ($)":
      return { icon: "pi-arrow-down", color: "red" };
    case "Accounts Receivable ($)":
      return { icon: "pi-inbox", color: "amber" };
    case "Accounts Payable ($)":
      return { icon: "pi-send", color: "pink" };
    case "Cash on Hand ($)":
      return { icon: "pi-wallet", color: "blue" };
    case "Sales Tax Paid ($)":
      return { icon: "pi-percentage", color: "cyan" };
    case "Sales Tax Collected ($)":
      return { icon: "pi-percentage", color: "lime" };
    case "Taxed Sales ($)":
      return { icon: "pi-check-square", color: "teal" };
    case "Non-taxed Sales ($)":
      return { icon: "pi-times-circle", color: "gray" };
    default:
      return { icon: "pi-chart-bar", color: "gray" };
  }
}

function handleCashOnHandUpdate(newValue: number) {
  datacashOnHand.value = newValue;
  editCashOnHand(); // Reutilizas tu función existente
}
</script>
