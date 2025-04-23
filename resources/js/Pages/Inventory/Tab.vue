<template>
  <Dialog v-model:visible="showCustomFields" header="Edit fields" :modal="true">
    <CustomFields :headers="headers" :custom-headers="fields ?? []" @update-headers="updateTableHeaders" />
  </Dialog>
  <div>
    <section class="w-[95%] mx-auto mt-4">
      <ItemsTabs :custom-tabs="tabs">
        <DataTable
          :title="tab?.name ?? 'No tab'"
          @update:selected="handleSelection"
          :items="tableData"
          inventory
          :headers="allHeaders"
          :actions="tableActions"></DataTable>
      </ItemsTabs>
    </section>
  </div>
</template>

<script setup lang="ts">
import DataTable from "@/Components/DataTable.vue";
import ItemsTabs from "@/Components/ItemsTabs.vue";
import { CustomField, Field, Tab as ITab, Item, Tab } from "@/Lib/types";
import { router } from "@inertiajs/vue3";
import { defineProps, onMounted, ref, Ref, watchEffect } from "vue";
import { headers } from "./IndexData";
import ItemsSell from "./Modals/ItemsSell.vue";
import MoveItem from "./Modals/MoveItem.vue";
import { Dialog, useConfirm, useDialog, useToast } from "primevue";
import axios from "axios";
import CustomFields from "./Modals/CustomFields.vue";

const dialog = useDialog();
const confirm = useConfirm();
const toast = useToast();
const props = defineProps({
  items: Array<Item>,
  customers: Array,
  tabs: {
    type: Array<ITab>,
    required: true,
  },
  current_tab: Number,
  fields: Array<Field>,
});
let selectedItems: Ref<Item[]> = ref([]);
const showCustomFields = ref(false);
const allHeaders: Ref<CustomField[]> = ref([]);

const updateTableHeaders = (updatedHeaders: CustomField[]) => {
  allHeaders.value = updatedHeaders;
};

const handleSelection = (selected: Item[]) => {
  selectedItems.value = selected;
};

const tab: Ref<Tab | null> = ref(null);
  const tabs = ref(props.tabs);

const tableData: Ref<any[]> = ref([]);
function parseItemsData() {
  allHeaders.value = [
    ...headers.value,
    ...(props.fields?.filter((f) => f.active).map((field) => ({ name: field.value, label: field.text, type: field.type })) ?? []),
  ];
  tabs.value = props.tabs;
  const tabId = props.current_tab;
  tab.value = props.tabs?.find((tab) => tab.id == Number(tabId)) ?? null;
  tableData.value = props
    .items!.filter((item) => item.sold === null)
    .map((item: any) => {
      if ( !String(item.battery).endsWith("%")) {
        if(!isNaN(Number(item.battery)) && item.battery !== null) {
          console.log("batery", item.battery);
          item.battery = `${item.battery}%`;
        }
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
        vendor: item.vendor.vendor,
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

const onClickReturnMove = () => {
  confirm.require({
    message: "Are you sure you want to return these items to Active Inventory?",
    header: "Confirmation",
    icon: "pi pi-exclamation-triangle",
    accept: async () => {
      try {
        const itemsId = selectedItems.value.map((item) => item.id);
        await axios.post(route("tab.returnmove"), {
          tab_id: tab.value?.id,
          item_ids: itemsId,
        });
        toast.add({ severity: "success", summary: "Success", detail: "Items returned to Active Inventory!", life: 3000 });
        router.reload();
      } catch (error: any) {
        console.log(error);
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
    label: "Edit fields",
    icon: "pi pi-pen-to-square",
    action: () => {
      showCustomFields.value = true;
    },
    },
  {
    label: "Edit Items",
    icon: "pi pi-pencil",
    action: () => {
      onEdit();
    },
    disable: (selectedItems: Item[]) => selectedItems.length !== 1,
  },
  {
    label: "Return move",
    icon: "pi pi-undo",
    severity: "danger",
    action: () => {
      onClickReturnMove();
    },
    disable: (selectedItems: Item[]) => selectedItems.length == 0,
  },
  {
    label: "Sell",
    icon: "pi pi-dollar",
    action: () => {
      openSellItemsModal();
    },
    disable: (selectedItems: Item[]) => selectedItems.length == 0,
  },
  {
    label: "Move Tab",
    icon: "pi pi-arrow-right-arrow-left",
    extraClasses: "!font-black",
    action: () => {
      openMoveItemsModal();
    },
    disable: (selectedItems: Item[]) => selectedItems.length == 0,
  },
];

const onEdit = () => {
  const currentPaginate = document.getElementById("currentPaginate")?.getAttribute("data-id") || "";
  const filter = document.getElementsByClassName("filter--value")[0]?.value || "";

  document.cookie = `paginate=${currentPaginate}`;
  document.cookie = `pagefilter=${filter}`;

  let items = selectedItems.value.map((item: any) => item.id).join(";");

  router.get(route("items.edit", btoa(items)));
};

function openMoveItemsModal() {
  dialog.open(MoveItem, {
    data: {
      tabs: props.tabs.filter((customTab) => customTab.id !== tab.value!.id),
      items: selectedItems.value,
    },
    props: {
      modal: true,
      header: "Move items",
    },
    onClose: () => {
      router.reload({ only: ["items"] });
    },
  });
}
</script>
