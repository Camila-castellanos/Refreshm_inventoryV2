<template>
    <header>
        <Button icon="pi pi-home" aria-label="Save" @click="toggleDrawer"/>
        <Drawer v-model:visible="drawerVisible">

            <li v-for="item in navItems">
                {{ item.label }}
            </li>

        </Drawer>
        </header>
    <div>
        <h1>Inventory Items</h1>
        <p>Listado de items del inventario</p>
    </div>
</template>

<script setup>
import { ref } from 'vue'; 
import Button from 'primevue/button'; 
import Drawer from 'primevue/drawer'; 


        const drawerVisible = ref(false);

        function toggleDrawer() {
            drawerVisible.value = !drawerVisible.value;
        }

   
const navItems = [
    {
        label: 'Inventory',
        url: '/inventory/items'
    },
    {
        label: 'Accounting',
        url: '/inventory/items'
    },
    {
        label: 'Contacts',
        url: '/inventory/items'
    },
    {
        label: 'Vendors',
        url: '/inventory/items'
    },
    {
        label: 'Reports',
        url: '/inventory/items'
    },
    {
        label: 'Stores',
        url: '/inventory/items'
    },
    {
        label: 'Users',
        url: '/inventory/items'
    }
]
     

</script>