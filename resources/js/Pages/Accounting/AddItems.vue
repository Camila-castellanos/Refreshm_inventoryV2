<template>
  <div>
    <section class="w-[90%] mx-auto mt-4">
      <DataTable
        title="Add Items to sale"
        @update:selected="handleSelection"
        :actions="tableActions"
        :items="tableData"
        :headers="itemHeaders"></DataTable>
    </section>
  </div>
</template>

<script setup lang="ts">
import DataTable from "@/Components/DataTable.vue";
import { Customer, Tab as ITab, Item } from "@/Lib/types";
import { router } from "@inertiajs/vue3";
import { useDialog } from "primevue/usedialog";
import { defineProps, onMounted, ref, Ref } from "vue";
import { itemHeaders } from "./data";
import AppLayout from "@/Layouts/AppLayout.vue";
import SelectedItems from "./Modals/SelectedItems.vue";

const dialog = useDialog();
const props = defineProps<{
  saleId: number;
  saleDate: string;
  customer: string | null;
  items: Item[];
}>();

let selectedItems: Ref<Item[]> = ref([]);

const handleSelection = (selected: Item[]) => {
  selectedItems.value = selected;
};

const tableData: Ref<any[]> = ref([]);
function parseItemsData() {
  console.log("data que viene del controller: ", props.items);
  tableData.value = props
    .items!.filter((item) => item.sold === null)
    .map((item: any) => {
      if (item.storage) {
        const { name, limit } = item.storage;
        const { position } = item;
        return {
          ...item,
          location: `${name} - ${position}/${limit}`,
          vendor: item.vendor?.vendor == 'Unknown' ? "N/A" : item.vendor?.vendor,
        };
      }
      return {
        ...item,
        location: "No storage information",
        vendor: item.vendor?.vendor == 'Unknown' ? "N/A" : item.vendor?.vendor,
      };
    });
  console.log("data despues de filtrar: ", tableData.value);
}

onMounted(() => {
  parseItemsData();
});

const tableActions = [
  {
    label: "Add Items",
    icon: "pi pi-plus",
    action: () => {
      dialog.open(SelectedItems, {
        data: {
          items: selectedItems.value,
          saleId: props.saleId,
          customer: props.customer,
          saleDate: props.saleDate
        },
        props: {
          modal: true,
        },
        onClose: (props) => {
          if (props?.data) {
            router.visit("/accounting/payments");
          }
        },
      });
    },
  },
];

defineOptions({ layout: AppLayout });
</script>
