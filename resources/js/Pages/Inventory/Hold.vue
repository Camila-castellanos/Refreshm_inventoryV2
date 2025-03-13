<template>
  <StoragesAssign :items="selectedItems" ref="assignStorageVisible"></StoragesAssign>
  <div>
    <section class="w-[90%] mx-auto mt-4">
      <ItemsTabs :custom-tabs="tabs">
        <DataTable
          title="Active Inventory"
          @update:selected="handleSelection"
          :actions="tableActions"
          :items="tableData"
          :headers="headers"></DataTable>
      </ItemsTabs>
    </section>
  </div>
</template>

<script setup lang="ts">
import DataTable from "@/Components/DataTable.vue";
import ItemsTabs from "@/Components/ItemsTabs.vue";
import { Tab as ITab, Item } from "@/Lib/types";
import { router } from "@inertiajs/vue3";
import { useDialog } from "primevue/usedialog";
import { defineProps, onMounted, ref, Ref } from "vue";
import StoragesAssign from "../Storages/StoragesAssign/StoragesAssign.vue";
import { headers } from "./IndexData";
import ItemsSell from "./Modals/ItemsSell.vue";

const dialog = useDialog();
const props = defineProps({
  items: Array<Item>,
  customers: Array,
  tabs: { type: Array<ITab>, required: true },
});

const assignStorageVisible: Ref<any> = ref(null);

const toggleAssignStorageVisible = () => {
  assignStorageVisible.value.openDialog();
};

let selectedItems: Ref<Item[]> = ref([]);

const handleSelection = (selected: Item[]) => {
  selectedItems.value = selected;
};

const tableData: Ref<any[]> = ref([]);
function parseItemsData() {
  tableData.value = props
    .items!.filter((item) => item.sold === null)
    .map((item: any) => {
      if (item.storage) {
        const { name, limit } = item.storage;
        const { position } = item;
        return {
          ...item,
          location: `${name} - ${position}/${limit}`,
          vendor: item.vendor.vendor,
          actions: [
            {
              label: "Label",
              icon: "pi pi-file",
              extraClasses: "!font-black",
              action: (item: Item) => {
                window.location.assign(route('items.label', item.id));
              },
            },
          ],
        };
      }
      return {
        ...item,
        location: "No storage information",
        vendor: item.vendor.vendor,
        actions: [
          {
            label: "Label",
            icon: "pi pi-file",
            action: (item: Item) => {
              window.location.assign(route('items.label', item.id));
            },
          }
        ],
      };
    });
}

onMounted(() => {
  parseItemsData();
});

function openSellItemsModal() {
  dialog.open(ItemsSell, {
    data: {
      items: selectedItems,
      customers: props.customers,
    },
    props: {
      modal: true,
    },
  });
}

const tableActions = [
  {
    label: "Sell",
    icon: "pi pi-dollar",
    action: () => {
      openSellItemsModal();
    },
    disable: (selectedItems: Item[]) => selectedItems.length == 0,
  },
  {
    label: "Return Items",
    icon: "pi pi-trash",
    severity: "danger",
    action: () => {},
    disable: (selectedItems: Item[]) => selectedItems.length == 0,
  },
];
</script>
