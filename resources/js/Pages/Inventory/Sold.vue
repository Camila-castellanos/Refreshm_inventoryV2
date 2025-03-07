<template>
  <div>
    <section class="w-[95%] mx-auto mt-4">
      <ItemsTabs :custom-tabs="tabs">
        <DataTable title="Sold" @update:selected="handleSelection" :items="tableData" :headers="soldHeaders">
          <div class="max-w-[400px] w-full">
            <div class="flex flex-row justify-around">
              <DatePicker v-model="dates" :max-date="new Date()" selectionMode="range" dateFormat="dd/mm/yy" class="w-full" id="date" placeholder="Date range for report"></DatePicker>
              <Button label="Generate" class="mx-2" type="submit" />
            </div>
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
import { Tab as ITab, Item } from "@/Lib/types";
import { soldHeaders } from "./IndexData";
import Card from "primevue/card";
import Button from "primevue/button";
import { DatePicker } from "primevue";
import { format} from "date-fns";
import axios from "axios";

const props = defineProps({
  items: Array<Item>,
  tabs: Array<ITab>,
});

const tabs: Ref<ITab[]> = ref([]);
const dates: Ref<Date | Date[] | (Date | null)[] | null | undefined> = ref([]);

let selectedItems: Ref<Item[]> = ref([]);

const handleSelection = (selected: Item[]) => {
  selectedItems.value = selected;
};

const tableData: Ref<any[]> = ref([]);
function parseItemsData() {
  console.log(props.items);
  props.tabs?.forEach((tab, i) => {
    tabs.value.push({ name: tab.name, order: tabs.value.length + i, id: tab.id });
  });
  tableData.value =
    props.items
      ?.filter((item) => item.sold === null)
      .map((item: any) => {
        if (item.id == 105) {
          console.log(item);
        }
        if (item.storage) {
          const { name, limit } = item.storage;
          const { position } = item;
          return {
            ...item,
            location: `${name} - ${position}/${limit}`,
            vendor: item.vendor.vendor,
          };
        }
        return {
          ...item,
          location: "No storage information",
        };
      }) ?? [];
}
onMounted(() => {
  parseItemsData();
});

async function onDateRangeSubmit() {
  // Se asume que dates.value es un array con dos fechas
  const start = format((dates.value as Date[])[0], "yyyy-MM-dd");
  const end = format((dates.value as Date[])[1], "yyyy-MM-dd");

  if (start && end) {
    try {
    const response = await axios.post(route("sales.generate_report", { start, end }));
    tableData.value = response.data.map((item: any) => {
      const customValues = JSON.parse(item.custom_values || "[]");
      const storages_var =
        item.sold_position && item.sold_storage_name
          ? { location: `${item.sold_storage_name} - (${item.sold_position})` }
          : {};
      const mergedItem = {
        ...storages_var,
        ...item,
        ...customValues.reduce((acc: any, field: any) => {
          const v = field.slug + "_" + field.id;
          acc[v] = field.value;
          return acc;
        }, {}),
        selected: false,
        vendor: item.vendor.vendor,
      };
      console.log(mergedItem)
      delete mergedItem.custom_values;
      return mergedItem;
    });
    } catch (error) {
      console.log(error);
    }
  }
}
</script>
