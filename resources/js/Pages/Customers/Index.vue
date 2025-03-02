<template>
    <AppLayout>
        <StoragesAssign
            :items="selectedItems"
            ref="assignStorageVisible"
        ></StoragesAssign>
        <div>
            <section class="w-[90%] mx-auto mt-4">
                <Tabs value="0">
                    <TabList>
                        <Tab value="0">Customers</Tab>
                        <Tab value="1">Prospects</Tab>
                        <Tab value="2">Mailing list</Tab>
                        <Tab value="3">Email editor</Tab>
                    </TabList>
                    <TabPanels>
                        <TabPanel value="0">
                            <DataTable
                                title="Customers"
                                @update:selected="handleSelection"
                                :actions="tableActions"
                                :items="tableData"
                                :headers="headers"
                                @edit="editCustomer"
                                @delete="deleteCustomer"
                            ></DataTable>
                        </TabPanel>
                        <TabPanel value="1">
                            <DataTable
                                title="Prospects"
                                @update:selected="handleSelection"
                                :items="[]"
                                :headers="[]"
                            ></DataTable>
                        </TabPanel>
                        <TabPanel value="2">
                            <DataTable
                                title="Mailing List"
                                @update:selected="handleSelection"
                                :items="[]"
                                :headers="[]"
                            ></DataTable>
                        </TabPanel>
                        <TabPanel value="3">
                            <DataTable
                                title="Email editor"
                                @update:selected="handleSelection"
                                :items="[]"
                                :headers="[]"
                            ></DataTable>
                        </TabPanel>
                    </TabPanels>
                </Tabs>
            </section>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import DataTable from "@/Components/DataTable.vue";
import { router } from "@inertiajs/vue3";
import Tab from "primevue/tab";
import TabList from "primevue/tablist";
import TabPanel from "primevue/tabpanel";
import TabPanels from "primevue/tabpanels";
import Tabs from "primevue/tabs";
import { defineProps, onMounted, ref } from "vue";
import StoragesAssign from "../Storages/StoragesAssign/StoragesAssign.vue";
import { headers } from "./IndexData";
import AppLayout from "@/Layouts/AppLayout.vue";

const props = defineProps({
    customers: Array,
});

const tableActions = [
    {
        label: "Add customer",
        icon: "pi pi-plus",
        action: () => {
            router.visit("/contacts/customers/create");
        },
    },
    {
        label: "Delete customer",
        icon: "pi pi-trash",
        severity: "danger",
        action: () => {},
        disable: (selectedItems) => selectedItems.length == 0,
    },
    {
        label: "Edit customers",
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
    console.log(props);
    if (!props.customers) {
        return;
    }

    tableData.value = props.customers.map((customer) => {
        return {
            ...customer,
            name: `${customer.first_name} ${customer.last_name}`,
        };
    });
}

const editCustomer = (customer: any) => {
    router.visit(route("customers.edit", { customer }));
};

const deleteCustomer = (customer: any) => {
    axios.delete(route("customers.destroy", { customer })).then(() => {
        router.reload();
    });
};
onMounted(() => {
    parseItemsData();
});
</script>
