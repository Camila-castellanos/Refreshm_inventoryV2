<template>
  <div class="h-[100vh]">
    <DynamicDialog />
    <Toast />
    <ConfirmDialog/>

    <Menubar :model="navItems">
      <template #start>
        <svg width="35" height="40" viewBox="0 0 35 40" fill="none" xmlns="http://www.w3.org/2000/svg" class="h-8">
          <path d="..." fill="var(--p-primary-color)" />
          <path d="..." fill="var(--p-text-color)" />
        </svg>
      </template>
      <template #button>
        <Button icon="pi pi-bars" aria-label="Save" @click="toggleDrawer" class="hide-breakpoint" />
      </template>
      <template #item="{ item, props, hasSubmenu, root }">
        <a v-ripple class="flex items-center" v-bind="props.action" :href="item.url">
          <span>{{ item.label }}</span>
          <Badge v-if="item.badge" :class="{ 'ml-auto': !root, 'ml-2': root }" :value="item.badge" />
          <span v-if="item.shortcut" class="p-1 ml-auto text-xs border rounded border-surface bg-emphasis text-muted-color">{{
            item.shortcut
          }}</span>
          <i v-if="hasSubmenu" :class="['pi pi-angle-down ml-auto', { 'pi-angle-down': root, 'pi-angle-right': !root }]"></i>
        </a>
      </template>
      <template #end>
        <div class="flex items-center gap-2">
          <Button aria-label="Toggle light mode" icon="pi pi-sun" @click="toggleDarkMode()" />
          <Button aria-label="Logout" label="Logout" icon="pi pi-sign-out" @click="logout()" />
          <Menu ref="menu" :model="dropdownNavItems" popup />
          <Avatar
            :label="userInitial"
            class="mr-2 cursor-pointer"
            size="large"
            style="background-color: #ece9fc; color: #2a1261"
            @click="openMenu" />
        </div>
      </template>
    </Menubar>

    <!-- <header class="w-[100vw] flex gap-4 justify-end"> -->

    <!-- </header> -->

    <Drawer v-model:visible="drawerVisible" class="">
      <div class="flex flex-col items-center justify-start w-full h-full gap-8">
        <Button variant="outlined" class="w-full" severity="secondary" v-for="item in navItems" @click="router.visit(`${item.url}`); drawerVisible = false">
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
import { ref, onMounted, computed, onBeforeUnmount } from "vue";
import Button from "primevue/button";
import Drawer from "primevue/drawer";
import { Menubar, Toast, Menu, ConfirmDialog } from "primevue";
import Avatar from "primevue/avatar";
import { router } from "@inertiajs/vue3";
import { DynamicDialog } from "primevue";
import { usePage } from "@inertiajs/vue3";

const page = usePage();
const user = page.props.auth?.user;
const themeKey = "theme-mode";

const isDarkMode = ref(localStorage.getItem(themeKey) === "dark");

const menu = ref();

const userInitial = computed(() => {
  return user?.name ? user.name.charAt(0).toUpperCase() : "?";
});

function toggleDarkMode() {
  isDarkMode.value = !isDarkMode.value;
  localStorage.setItem(themeKey, isDarkMode.value ? "dark" : "light");
  applyTheme();
}

function applyTheme() {
  if (isDarkMode.value) {
    document.documentElement.classList.add("my-app-dark");
  } else {
    document.documentElement.classList.remove("my-app-dark");
  }
}

const drawerVisible = ref(false);

function toggleDrawer() {
  drawerVisible.value = !drawerVisible.value;
}

const logout = () => {
  isDarkMode.value = false;
  localStorage.setItem(themeKey, "light");
  applyTheme();

  router.post("/logout");
};

const navItems = ref([
  { label: "Dashboard", icon: "pi pi-chart-bar", url: "/dashboard", roles: ["OWNER", "USER", "ADMIN"] },
  { label: "Inventory", icon: "pi pi-box", url: "/inventory/items", roles: ["OWNER", "USER", "ADMIN"] },
  { label: "Accounting", icon: "pi pi-box", url: "/accounting/payments", roles: ["ADMIN", "OWNER"] },
  { label: "Contacts", icon: "pi pi-users", url: "/customer", roles: ["OWNER", "USER", "ADMIN"] },
  { label: "Vendors", icon: "pi pi-truck", url: "/vendor", roles: ["OWNER", "USER", "ADMIN"] },
  { label: "Stores", icon: "pi pi-store", url: "/stores", roles: ["OWNER"] },
  { label: "Users", icon: "pi pi-user", url: "/users", roles: ["OWNER"] },
]);

const dropdownNavItems = ref([
  { label: "Profile", icon: "pi pi-user", url: route("profile.show"), roles: ["OWNER", "USER", "ADMIN"] },
  { label: "Store", icon: "pi pi-home", url: user?.store_id ? route("stores.edit", user.store_id) : "#", roles: ["ADMIN"] },
  { label: "Locations", icon: "pi pi-map", url: route("locations.list"), roles: ["ADMIN"] },
  { label: "Users", icon: "pi pi-users", url: route("users.index"), roles: ["ADMIN"] },
]);

onMounted(() => {
  if (user && user.role) {
    navItems.value = navItems.value.filter((item) => item.roles.includes(user.role));
    dropdownNavItems.value = dropdownNavItems.value.filter((item) => item.roles.includes(user.role));
  }

  window.addEventListener("beforeunload", handleBeforeUnload);
});

const openMenu = (event) => {
  menu.value.toggle(event);
};

onBeforeUnmount(() => {
  window.removeEventListener("beforeunload", handleBeforeUnload);
});

function handleBeforeUnload(event) {
  // Solo si el usuario está autenticado (puedes agregar más lógica si es necesario)
  if (window.location.pathname !== '/login' && window.location.pathname !== '/register') {
    navigator.sendBeacon(route('logout'));
  }
}
</script>

<style scoped>
header {
  padding: 1rem;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.4s ease-in-out, transform 0.3s ease-in-out;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(10px);
}

@media screen and (min-width: 961px) {
  .hide-breakpoint {
    display: none;
  }
}
</style>
