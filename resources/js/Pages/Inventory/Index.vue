<template>
    <StoragesAssign
        :items="selectedItems"
        ref="assignStorageVisible"
    ></StoragesAssign>
    <div>
        <section class="w-[90%] mx-auto mt-4">
            <Tabs value="0">
                <TabList>
                    <Tab value="0">Active Inventory</Tab>
                    <Tab value="1">On Hold</Tab>
                    <Tab value="2">Sold</Tab>
                </TabList>
                <TabPanels>
                    <TabPanel value="0">
                        <DataTable
                            title="Active Inventory"
                            @update:selected="handleSelection"
                            :actions="tableActions"
                            :items="tableData"
                            :headers="headers"
                        ></DataTable>
                    </TabPanel>
                    <TabPanel value="1">
                        <DataTable
                            title="On Hold"
                            @update:selected="handleSelection"
                            :items="[]"
                            :headers="[]"
                        ></DataTable>
                    </TabPanel>
                    <TabPanel value="2">
                        <DataTable
                            title="Sold"
                            @update:selected="handleSelection"
                            :items="getSoldItems()"
                            :headers="headers"
                        ></DataTable>
                    </TabPanel>
                </TabPanels>
            </Tabs>
        </section>
    </div>
</template>

<script setup>
import { onMounted, ref } from "vue";
import DataTable from "@/Components/DataTable.vue";
import { headers } from "./IndexData";
import { data } from "./IndexData";
import { router } from "@inertiajs/vue3";
import { defineProps } from "vue";
import StoragesAssign from "../Storages/StoragesAssign/StoragesAssign.vue";
import { Dialog } from "primevue";
import Tabs from "primevue/tabs";
import TabList from "primevue/tablist";
import Tab from "primevue/tab";
import TabPanels from "primevue/tabpanels";
import TabPanel from "primevue/tabpanel";
import { useDialog } from "primevue/usedialog";
import ItemsSell from "./ItemsSell/ItemsSell.vue";

const dialog = useDialog();
const props = defineProps({
    items: Array,
    customers: Array,
});

const tabs = ref([
    { title: "Tab 1", content: "Tab 1 Content", value: "0" },
    { title: "Tab 2", content: "Tab 2 Content", value: "1" },
    { title: "Tab 3", content: "Tab 3 Content", value: "2" },
]);

const assignStorageVisible = ref(null);

const toggleAssignStorageVisible = () => {
    assignStorageVisible.value.openDialog();
};

let selectedItems = ref([]);

const handleSelection = (selected) => {
    selectedItems.value = selected;
};

const tableData = ref([]);
function parseItemsData() {
    tableData.value = props.items.filter((item) => item.sold === null).map((item) => {
        if (item.storage) {
            const { name, limit } = item.storage;
            const { position } = item;
            return {
                ...item,
                location: `${name} - ${position}/${limit}`,
            };
        }
        return {
            ...item,
            location: "No storage information",
        };
    });
}
onMounted(() => {
    parseItemsData();
});

function openSellItemsModal() {
    console.log(props.customers);
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
        label: "Add Items",
        icon: "pi pi-plus",
        action: () => {
            router.visit("/inventory/items/bulk");
        },
    },
    {
        label: "Reassign location",
        icon: "pi pi-arrow-up",
        action: () => {
            toggleAssignStorageVisible();
        },
        disable: (selectedItems) => selectedItems.length == 1,
    },
    {
        label: "Sell",
        icon: "pi pi-dollar",
        action: () => {
            openSellItemsModal();
        },
    },
    {
        label: "Delete Items",
        icon: "pi pi-trash",
        severity: "danger",
        action: () => {},
        disable: (selectedItems) => selectedItems.length == 0,
    },
    {
        label: "Edit Items",
        icon: "pi pi-pencil",
        action: () => {
            console.log("hi");
        },
        disable: (selectedItems) => selectedItems.length !== 1,
    },
    {
        label: "Export",
        icon: "pi pi-file-export",
        action: () => {
            console.log("hi");
        },
        disable: (selectedItems) => selectedItems.length !== 1,
    },
];

function getSoldItems() {
    console.log(props.items.filter((item) => item.sold !== null));
    return props.items.filter((item) => item.sold !== null);
}
</script>
