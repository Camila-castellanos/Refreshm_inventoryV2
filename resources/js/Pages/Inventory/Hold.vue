<template>
  <Dialog v-model:visible="showCustomFields" header="Edit fields" :modal="true">
    <CustomFields :headers="headers" :custom-headers="fields ?? []" @update-headers="updateTableHeaders" />
  </Dialog>
  <div>
    <section class="w-[95%] mx-auto mt-4">
      <ItemsTabs :custom-tabs="tabs">
        <DataTable
          title="On Hold"
          @update:selected="handleSelection"
          :actions="tableActions"
          :items="tableData"
          inventory
          :headers="allHeaders"></DataTable>
      </ItemsTabs>
    </section>
  </div>
</template>

<script setup lang="ts">
import DataTable from "@/Components/DataTable.vue";
import ItemsTabs from "@/Components/ItemsTabs.vue";
import { CustomField, Field, Tab as ITab, Item } from "@/Lib/types";
import axios from "axios";
import { Dialog, useConfirm, useDialog, useToast } from "primevue";
import { defineProps, onMounted, ref, Ref, watchEffect } from "vue";
import { headers } from "./IndexData";
import ItemsSell from "./Modals/ItemsSell.vue";
import CustomFields from "./Modals/CustomFields.vue";
import { router } from "@inertiajs/vue3";

const dialog = useDialog();
const confirm = useConfirm()
const toast = useToast();
const showCustomFields = ref(false);
const props = defineProps({
  items: Array<Item>,
  customers: Array,
  tabs: { type: Array<ITab>, required: true },
  fields: Array<Field>,
});

let selectedItems: Ref<Item[]> = ref([]);
const allHeaders: Ref<CustomField[]> = ref([]);
const tabs = ref(props.tabs);

const handleSelection = (selected: Item[]) => {
  selectedItems.value = selected;
};

const tableData: Ref<any[]> = ref([]);
function parseItemsData() {
  allHeaders.value = [
    ...headers.value,
    ...(props.fields?.filter((f) => f.active).map((field) => ({ name: field.value, label: field.text, type: field.type })) ?? []),
  ];
  tabs.value = props.tabs;
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

watchEffect(() => {
  if (props.items || props.fields || props.tabs) {
    parseItemsData();
  }
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
    onClose: () => {
      selectedItems.value = [];
      router.reload({ only: ["items"] });
    },
  });
}

const onClickReturn = () => {
  confirm.require({
    message: "Are you sure you want to return these items?", 
    header: "Confirmation",
    icon: "pi pi-exclamation-triangle",
    accept: async () => {
      try {
        await axios.put(route("items.unhold"), { data: selectedItems.value });
        toast.add({ severity: "success", summary: "Success", detail: "Items returned!", life: 3000 });
        location.reload();
      } catch (error: any) {
        toast.add({
          severity: "error",
          summary: "Error",
          detail: error.response?.data || "An error occurred",
          life: 5000,
        });
      }
    },
  });
};

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
    icon: "pi pi-undo",
    severity: "danger",
    action: () => {onClickReturn();},
    disable: (selectedItems: Item[]) => selectedItems.length == 0,
  },
];

const updateTableHeaders = (updatedHeaders: CustomField[]) => {
  allHeaders.value = updatedHeaders;
};
</script>
