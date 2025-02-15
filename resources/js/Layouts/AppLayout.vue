<template>
    <div class="h-[100vh]">


            <Menubar :model="navItems">
    <template #start>
        <svg width="35" height="40" viewBox="0 0 35 40" fill="none" xmlns="http://www.w3.org/2000/svg" class="h-8">
            <path d="..." fill="var(--p-primary-color)" />
            <path d="..." fill="var(--p-text-color)" />
        </svg>
    </template>
    <template #item="{ item, props, hasSubmenu, root }">
        <a v-ripple class="flex items-center" v-bind="props.action">
            <span>{{ item.label }}</span>
            <Badge v-if="item.badge" :class="{ 'ml-auto': !root, 'ml-2': root }" :value="item.badge" />
            <span v-if="item.shortcut" class="ml-auto border border-surface rounded bg-emphasis text-muted-color text-xs p-1">{{ item.shortcut }}</span>
            <i v-if="hasSubmenu" :class="['pi pi-angle-down ml-auto', { 'pi-angle-down': root, 'pi-angle-right': !root }]"></i>
        </a>
    </template>
    <template #end>
        <div class="flex items-center gap-2">
                        <Button icon="pi pi-bars" aria-label="Save" @click="toggleDrawer"/>
            <Button aria-label="Toggle light mode" icon="pi pi-sun" @click="toggleDarkMode()" />
            <Avatar icon="pi pi-user" class="mr-2" size="large" style="background-color: #ece9fc; color: #2a1261" />

        </div>
    </template>
</Menubar>

        <!-- <header class="w-[100vw] flex gap-4 justify-end"> -->
        
        <!-- </header> -->

        <Drawer v-model:visible="drawerVisible">
                <li v-for="item in navItems">
                    {{ item.label }}
                </li>
        </Drawer>

        <main>
            <slot /> <!-- Renderiza el contenido de la página -->
        </main>
    </div>
</template>

<script setup>

import { ref } from 'vue';
import Button from 'primevue/button'; 
import Drawer from 'primevue/drawer'; 
import { Menubar } from 'primevue';
import Avatar from 'primevue/avatar';

function toggleDarkMode() {
    document.documentElement.classList.toggle('my-app-dark');
}

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

<style scoped>
header {
   
    padding: 1rem;
}
</style>
