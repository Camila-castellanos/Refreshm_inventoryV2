<template>
  <AppLayout>
    <div class="p-6">
      <h1 class="text-2xl font-bold">Welcome, {{ user.name }}</h1>

      <!-- Filtro de fecha y opciones -->
      <div class="flex justify-between items-center mt-4">
        <Dropdown v-model="selectedFilter" :options="filters" class="w-40" />
        <div class="flex gap-2 w-full max-w-md">
          <FloatLabel variant="over" class="w-full">
            <Calendar
              v-model="getSafeCalendarValue"
              :selectionMode="selectedFilter === 'Current' ? 'range' : 'single'"
              showIcon
              dateFormat="mm/dd/yy"
              class="w-full"
              id="date-filter"
              ref="calendarRef"
              @update:modelValue="handleCalendarChange"
              :showTime="false" />
            <label for="date-filter">{{ selectedFilter === "Current" ? "Start date - End date" : "Start date" }}</label>
          </FloatLabel>
          <Dropdown
            v-model="quickFilter"
            :options="quickFilterOptions"
            optionLabel="label"
            placeholder="Quick Filter"
            class="w-48"
            @change="handleQuickFilter"
            optionValue="value" />
        </div>
      </div>

      <!-- Secciones -->
      <div class="grid grid-cols-3 gap-4 mt-6">
        <Card v-for="stat in inventoryStats" :key="stat.label" class="shadow-md">
          <template #title>
            <span class="font-bold">{{ stat.label }}</span>
          </template>
          <template #content>
            <p class="text-lg font-semibold">{{ stat.label.includes("$") ? `$${stat.value}` : stat.value }}</p>
          </template>
        </Card>
      </div>

      <Divider />

      <div class="grid grid-cols-3 gap-4 mt-6">
        <Card v-for="stat in salesStats" :key="stat.label" class="shadow-md">
          <template #title>
            <span class="font-bold">{{ stat.label }}</span>
          </template>
          <template #content>
            <p class="text-lg font-semibold">${{ stat.value }}</p>
          </template>
        </Card>
      </div>

      <Divider />

      <div class="grid grid-cols-3 gap-4 mt-6">
        <Card v-for="stat in accountingStats" :key="stat.label" class="shadow-md">
          <template #title>
            <span class="font-bold">{{ stat.label }}</span>
          </template>
          <template #content>
            <div v-if="stat.label === 'Cash on Hand ($)'">
              <div v-if="alterCashOnHand">
                <InputNumber v-model="datacashOnHand" mode="currency" currency="USD" :min="0" class="!w-full mb-2" size="small">
                  <template #incrementbutton>
                    <div class="flex">
                      <Button icon="pi pi-check" severity="success" size="small" variant="text" @click="editCashOnHand" />
                      <Button icon="pi pi-times" severity="secondary" size="small" variant="text" @click="cancelEditCashOnHand" />
                    </div>
                  </template>
                </InputNumber>
              </div>
              <div v-else class="flex items-center justify-between">
                <p class="text-lg font-semibold">${{ stat.value }}</p>
                <Button icon="pi pi-pencil" size="small" @click="alterCashOnHand = true" variant="text" />
              </div>
            </div>
            <div v-else>
              <p class="text-lg font-semibold">${{ stat.value }}</p>
            </div>
          </template>
        </Card>
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

const toast = useToast();

const filters = ref(["Current", "Historically"]);
const selectedFilter = ref("Current");
const calendarValue = ref<Date | Date[] | null>(null);

interface Stat {
  label: string;
  value: number;
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

function cancelEditCashOnHand() {
  datacashOnHand.value = parseFloat(props.cashOnHand);
  alterCashOnHand.value = false;
}

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
    { label: "Inventory Value ($)", value: data.inventoryValue },
    { label: "Est. Sale Value of Inventory ($)", value: data.saleValue },
  ];

  salesStats.value = [
    { label: "Revenue ($)", value: data.soldValueThisMonth },
    { label: "Gross Profit ($)", value: data.profitThisMonth },
    { label: "Net Profit ($)", value: data.profitThisMonth - data.expensesThisMonth },
    { label: "Expenses ($)", value: data.expensesThisMonth },
  ];

  accountingStats.value = [
    { label: "Accounts Receivable ($)", value: data.accountsReceivableThisMonth },
    { label: "Accounts Payable ($)", value: data.accountsPayableThisMonth },
    { label: "Cash on Hand ($)", value: parseFloat(props.cashOnHand) },
    { label: "Sales Tax Paid ($)", value: data.salesTaxPaid },
    { label: "Sales Tax Collected ($)", value: data.salesTaxCollected },
    { label: "Taxed Sales ($)", value: data.taxedSales },
    { label: "Non-taxed Sales ($)", value: data.nonTaxedSales },
  ];
}

onMounted(() => {
  updateDashboardStats(props);
});
</script>
