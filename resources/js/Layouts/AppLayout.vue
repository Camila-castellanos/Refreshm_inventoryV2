<template>
    <div class="h-[100vh]">
        <DynamicDialog />
        <Toast />

        <Menubar :model="navItems">
            <template #start>
                <svg width="35" height="40" viewBox="0 0 35 40" fill="none" xmlns="http://www.w3.org/2000/svg"
                    class="h-8">
                    <path d="..." fill="var(--p-primary-color)" />
                    <path d="..." fill="var(--p-text-color)" />
                </svg>
            </template>
            <template #item="{ item, props, hasSubmenu, root }">
                <a v-ripple class="flex items-center" v-bind="props.action">
                    <span>{{ item.label }}</span>
                    <Badge v-if="item.badge" :class="{ 'ml-auto': !root, 'ml-2': root }" :value="item.badge" />
                    <span v-if="item.shortcut"
                        class="p-1 ml-auto text-xs border rounded border-surface bg-emphasis text-muted-color">{{
                        item.shortcut }}</span>
                    <i v-if="hasSubmenu"
                        :class="['pi pi-angle-down ml-auto', { 'pi-angle-down': root, 'pi-angle-right': !root }]"></i>
                </a>
            </template>
            <template #end>
                <div class="flex items-center gap-2">
                    <Button icon="pi pi-bars" aria-label="Save" @click="toggleDrawer" />
                    <Button aria-label="Toggle light mode" icon="pi pi-sun" @click="toggleDarkMode()" />
                    <Button aria-label="Logout" label="Logout" icon="pi pi-sign-out" @click="logout()" />
                    <Avatar icon="pi pi-user" class="mr-2" size="large"
                        style="background-color: #ece9fc; color: #2a1261" />
                </div>
            </template>
        </Menubar>

        <!-- <header class="w-[100vw] flex gap-4 justify-end"> -->

        <!-- </header> -->

        <Drawer v-model:visible="drawerVisible" class="" >
            <div class="flex flex-col items-center justify-start w-full h-full gap-8">
                <Button variant="outlined" class="w-full" severity="secondary" v-for="item in navItems" @click="router.visit(`${item.url}`)">
                        {{ item.label }}
                </Button>
            </div>
             
        </Drawer>

        <main>
  
                <slot /> 
       
        </main>
    </div>
</template>

<script setup>

import { ref, onMounted } from 'vue';
import Button from 'primevue/button';
import Drawer from 'primevue/drawer';
import { Menubar, Toast } from 'primevue';
import Avatar from 'primevue/avatar';
import { router } from '@inertiajs/vue3';
import {DynamicDialog} from 'primevue';
const themeKey = 'theme-mode';

const isDarkMode = ref(localStorage.getItem(themeKey) === 'dark');

function toggleDarkMode() {
    isDarkMode.value = !isDarkMode.value;
    localStorage.setItem(themeKey, isDarkMode.value ? 'dark' : 'light');
    applyTheme();
}

function applyTheme() {
    if (isDarkMode.value) {
        document.documentElement.classList.add('my-app-dark');
    } else {
        document.documentElement.classList.remove('my-app-dark');
    }
}

const drawerVisible = ref(false);

function toggleDrawer() {
    drawerVisible.value = !drawerVisible.value;
}

const logout = () => {
    isDarkMode.value = false;
    localStorage.setItem(themeKey, 'light');
    applyTheme();

    router.post('/logout')
}

const navItems = ref([
{
        label: 'Dashboard',
        icon: 'pi pi-chart-bar',
        command: () => {
            router.visit('/dashboard');
        }
    },
    {
        label: 'Inventory',
        icon: 'pi pi-box',
        command: () => {
            router.visit('/inventory/items');
        }
    },
    {
        label: 'Accounting',
        icon: 'pi pi-box',
        command: () => {
            router.visit('/accounting/payments');
        }
    },
    {
        label: 'Contacts',
        icon: 'pi pi-users',
        command: () => {
            router.visit('/customer');
        }
    },
    {
        label: 'Vendors',
        icon: 'pi pi-truck',
        command: () => {
            router.visit('/inventory/vendors');
        }
    },
    // {
    //     label: 'Storages',
    //     icon: 'pi pi-box',
    //     command: () => {
    //         router.visit('/inventory/storages/index');
    //     }
    // },
   
   
    // {
    //     label: 'Reports',
    //     icon: 'pi pi-chart-line',
    //     command: () => {
    //         router.visit('/reports');
    //     }
    // },
    // {
    //     label: 'Stores',
    //     icon: 'pi pi-store',
    //     command: () => {
    //         router.visit('/stores');
    //     }
    // },
    {
        label: 'Users',
        icon: 'pi pi-user',
        command: () => {
            router.visit('/users');
        }
    }
]);

onMounted(() => {
    applyTheme();
});

</script>

<style scoped>
header {

    padding: 1rem;
}

.fade-enter-active, .fade-leave-active {
    transition: opacity 0.4s ease-in-out, transform 0.3s ease-in-out;
}
.fade-enter-from, .fade-leave-to {
    opacity: 0;
    transform: translateY(10px);
}
</style>
