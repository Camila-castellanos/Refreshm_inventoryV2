<template>
  <div class="h-[100vh]">
    <DynamicDialog />
    <Toast />
    <ConfirmDialog />
    <SessionExpiredDialog />

    <Menubar :model="navItems">
      <template #start>
        <div class="h-16 mr-12">
          <img src="/images/swiftstock_logo.jpeg" draggable="false"
            class="object-contain max-h-full max-w-full pointer-events-none" alt="" />
        </div>
      </template>
      <template >
        <Button icon="p#buttoni pi-bars" aria-label="Save" @click="toggleDrawer" class="hide-breakpoint" />
      </template>
      <template #item="{ item, props, hasSubmenu, root }">
        <a v-ripple class="flex items-center" v-bind="props.action" :href="item.url">
          <span>{{ item.label }}</span>
          <Badge v-if="item.badge" :class="{ 'ml-auto': !root, 'ml-2': root }" :value="item.badge" />
          <span v-if="item.shortcut"
            class="p-1 ml-auto text-xs border rounded border-surface bg-emphasis text-muted-color">{{
              item.shortcut
            }}</span>
          <i v-if="hasSubmenu"
            :class="['pi pi-angle-down ml-auto', { 'pi-angle-down': root, 'pi-angle-right': !root }]"></i>
        </a>
      </template>

      <template #end>
        <div class="flex items-center gap-2">
          <!-- <Button aria-label="Toggle light mode" icon="pi pi-sun" @click="toggleDarkMode()" /> -->

          <Menu ref="menu" :model="dropdownNavItems" popup />
          <Avatar :label="userInitial" class="mr-2 cursor-pointer" size="large"
            style="background-color: #ece9fc; color: #2a1261" @click="openMenu" />
        </div>
      </template>
    </Menubar>

    <!-- <header class="w-[100vw] flex gap-4 justify-end"> -->

    <!-- </header> -->

    <Drawer v-model:visible="drawerVisible" class="">
      <div class="flex flex-col items-center justify-start w-full h-full gap-8">
        <Button variant="outlined" class="w-full" severity="secondary" v-for="item in navItems"
          @click="router.visit(`${item.url}`); drawerVisible = false">
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
import SessionExpiredDialog from "@/Components/SessionExpiredDialog.vue";
import { renewCsrfToken } from '@/Utils/csrf.js';
import { getItemByPosition } from "@revolist/vue3-datagrid";

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

  router.post('/logout', {}, {
    onSuccess: async () => {
      await renewCsrfToken();
      router.visit(route('login'));
    },
    onError: (errors) => {
      console.error('Logout failed:', errors);
      // Handle logout errors
    },
  });
};

const navItems = ref([
  { label: "Dashboard", icon: "pi pi-chart-bar", url: "/dashboard", roles: ["OWNER", "ADMIN"] },
  { label: "Inventory", icon: "pi pi-box", url: "/inventory/items", roles: ["OWNER", "USER", "ADMIN"] },
  { label: "Accounting", icon: "pi pi-box", url: "/accounting/payments", roles: ["ADMIN", "OWNER"] },
  { label: "Contacts", icon: "pi pi-users", url: "/customer", roles: ["OWNER", "USER", "ADMIN"] },
  { label: "Stores", icon: "pi pi-store", url: route("stores.index", {filter: "all"}), roles: ["OWNER"] },
  { label: "Users", icon: "pi pi-user", url: route("users.index", {filter: "all"}), roles: ["OWNER"] },
]);

const dropdownNavItems = ref([
  { label: "Profile", icon: "pi pi-user", url: route("profile.show"), roles: ["OWNER", "USER", "ADMIN"] },
  { label: "Users", icon: "pi pi-users", url: route("users.index"), roles: ["ADMIN", "OWNER"] },
  // { label: "Store", icon: "pi pi-home", url: user?.store_id ? route("stores.edit", user.store_id) : "#", roles: ["ADMIN"] },
  { label: "Locations", icon: "pi pi-map", url: route("stores.index"), roles: ["ADMIN", "OWNER"] },
  { label: "Dark Mode", icon: "pi pi-moon", command: toggleDarkMode, roles: ["OWNER", "USER", "ADMIN"] },
  { label: "Logout", icon: "pi pi-sign-out", command: logout, roles: ["OWNER", "USER", "ADMIN"] }
]);

onMounted(() => {
  if (user && user.role) {
    navItems.value = navItems.value.filter((item) => item.roles.includes(user.role));
    dropdownNavItems.value = dropdownNavItems.value.filter((item) => item.roles.includes(user.role));
  }
});

const openMenu = (event) => {
  menu.value.toggle(event);
};
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
