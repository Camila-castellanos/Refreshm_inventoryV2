<template>
  <Dialog v-model:visible="showCustomFields" header="Edit fields" :modal="true">
    <CustomFields :headers="soldHeaders" :custom-headers="fields ?? []" @update-headers="updateTableHeaders" />
  </Dialog>
  <div>
    <section class="w-[95%] mx-auto mt-4">
      <ItemsTabs :custom-tabs="tabs">
        <DataTable
          title="Sold"
          @update:selected="handleSelection"
          :items="tableData"
          inventory
          :headers="allHeaders"
          :actions="tableActions"
          :sortField="'sold'"
          :sortOrder="-1">
          <div class="max-w-[400px] w-full">
            <form class="flex flex-row justify-around" @submit.prevent="onDateRangeSubmit">
              <DatePicker
                v-model="dates"
                :max-date="new Date()"
                selectionMode="range"
                dateFormat="dd/mm/yy"
                class="w-full"
                id="date"
                placeholder="Date range for report"></DatePicker>
              <Button label="Generate" class="mx-2" size="large" type="submit" />
            </form>
          </div>
        </DataTable>
      </ItemsTabs>
    </section>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, Ref } from "vue";
import DataTable from "@/Components/DataTable.vue";
import ItemsTabs from "@/Components/ItemsTabs.vue";
import { CustomField, Field, Tab as ITab, Item } from "@/Lib/types";
import { soldHeaders } from "./IndexData";
import Card from "primevue/card";
import Button from "primevue/button";
import { DatePicker, Dialog, useConfirm, useToast } from "primevue";
import { format } from "date-fns";
import axios from "axios";
import { router } from "@inertiajs/vue3";
import CustomFields from "./Modals/CustomFields.vue";

const confirm = useConfirm();
const toast = useToast();

const props = defineProps({
  tabs: { type: Array<ITab>, required: true },
  fields: Array<Field>,
  items: { type: Array<Item>, required: true },
});

const dates: Ref<Date | Date[] | (Date | null)[] | null | undefined> = ref([]);

let selectedItems: Ref<Item[]> = ref([]);
const showCustomFields = ref(false);
const allHeaders: Ref<CustomField[]> = ref([]);

const updateTableHeaders = (updatedHeaders: CustomField[]) => {
  allHeaders.value = updatedHeaders;
};

const handleSelection = (selected: Item[]) => {
  selectedItems.value = selected;
};

const tableData: Ref<any[]> = ref([]);

function parseItemsData() {
  allHeaders.value = [
    ...soldHeaders.value,
    ...(props.fields?.filter((f) => f.active).map((field) => ({ name: field.value, label: field.text, type: field.type })) ?? []),
  ];
  console.log(props.items);
  tableData.value = props
    .items!.filter((item) => item.sold != null)
    .map((item: any) => {
      return {
        ...item,
        cost: `$ ${item.cost}`,
        profit: `$ ${item.profit}`,
        selling_price: `$ ${item.selling_price}`,
        subtotal: `$ ${item.sale.subtotal ?? 'unknown'}`,
        total: `$  ${item.sale.total ?? 'unknown'}`,
        location: item.sold_storage_name || "unknown",
        vendor: item.vendor?.vendor,
        actions: [
            {
              label: "Edit Items",
              icon: "pi pi-pencil",
              action: () => {
                selectedItems.value = [item];
                onEdit();
              },
            },
            {
              label: "Return",
              icon: "pi pi-undo",
              severity: "danger",
              action: () => {
                onReturn(item);
              },
            },
          ],
      };
    });
}

async function onDateRangeSubmit() {
  const start = format((dates.value as Date[])[0], "yyyy-MM-dd");
  const end = format((dates.value as Date[])[1], "yyyy-MM-dd");

  if (start && end) {
    try {
      const response = await axios.post(route("sales.generate_report", { start, end }));
      tableData.value = response.data
        .map((item: any) => {
          const customValues = JSON.parse(item.custom_values || "[]");
          const storagesVar =
            item.sold_position && item.sold_storage_name ? { location: `${item.sold_storage_name} - (${item.sold_position})` } : {};
          return {
            ...storagesVar,
            ...item,
            ...Object.fromEntries(customValues.map((field: any) => [`${field.slug}_${field.id}`, field.value])),
            vendor: item.vendor?.vendor,
          };
        })
        .map((item: any) => ({
          ...item,
          actions: [
            {
              label: "Edit Items",
              icon: "pi pi-pencil",
              action: () => {
                selectedItems.value = [item];
                onEdit();
              },
            },
            {
              label: "Return",
              icon: "pi pi-undo",
              severity: "danger",
              action: () => {
                onReturn(item);
              },
            },
          ],
        }));
    } catch (error) {
      console.log(error);
    }
  }
}

const onEdit = () => {
  const currentPaginate = document.getElementById("currentPaginate")?.getAttribute("data-id") || "";
  const filter = document.getElementsByClassName("filter--value")[0]?.value || "";

  document.cookie = `paginate=${currentPaginate}`;
  document.cookie = `pagefilter=${filter}`;

  let items = selectedItems.value.map((item: any) => item.id).join(";");

  router.get(route("items.edit", btoa(items)));
};

const onReturn = (item: Item) => {
  confirm.require({
    message: "Are you sure you want to return this item?",
    header: "Confirmation",
    icon: "pi pi-exclamation-triangle",
    accept: async () => {
      try {
        const response = await axios.put(route("items.return"), { item });
        if (response.status >= 200 && response.status < 400) {
          toast.add({ severity: "success", summary: "Success", detail: "Item returned!", life: 3000 });
        }
        onDateRangeSubmit();
      } catch (error: any) {
        toast.add({
          severity: "error",
          summary: "Error",
          detail: error.response?.data || error.message || "An error occurred",
          life: 5000,
        });
      }
    },
  });
};

onMounted(() => {
  parseItemsData();
});

const tableActions = [
  {
    label: "Edit fields",
    icon: "pi pi-pen-to-square",
    action: () => {
      showCustomFields.value = true;
    },
  },
];
</script>
