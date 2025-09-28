<template>
  <div>
    <Menubar :model="navItems" class="flex menuBar !items-end">
      <template #start>
        <div>
          <img src="/images/swiftstock_logo.webp" draggable="false"
            class="h-28 pl-28 py-1 object-contain max-h-full max-w-full pointer-events-none"
            alt="Swiftstock Logo"
           />
        </div>
      </template>
      <template>
        <Button icon="p#buttoni pi-bars" aria-label="Menu" @click="toggleDrawer" class="hide-breakpoint" />
      </template>
      <template #item="{ item, props, hasSubmenu, root }">
        <a v-ripple :class="['flex items-center text-lg pb-0 border-b-0 transition-all hover:border-b-2 px-4 py-2', { 'black_border_current_page': isActive(item) }]" v-bind="props.action" :href="item.url">
          <i v-if="item.icon" :class="item.icon" aria-hidden="true"></i>
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
          <Menu ref="menu" :model="dropdownNavItems" popup />
          <Avatar :label="userInitial" class="mr-2 cursor-pointer" size="large"
            style="background-color: #ece9fc; color: #2a1261" @click="openMenu" />
        </div>
      </template>
    </Menubar>

  <Drawer v-model:visible="drawerVisible" class="">
      <div class="flex flex-col items-center justify-start w-full h-full gap-8">
        <Button variant="outlined" :class="['w-full flex items-center gap-3', { 'black_border_current_page-drawer': isActive(item) }]" severity="secondary" v-for="item in navItems" :key="item.label"
          @click="() => { router.visit(`${item.url}`); drawerVisible = false }">
          <i v-if="item.icon" :class="item.icon" aria-hidden="true"></i>
          {{ item.label }} 
        </Button>
      </div>
    </Drawer>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import Button from 'primevue/button';
import Drawer from 'primevue/drawer';
import Menubar from 'primevue/menubar';
import Menu from 'primevue/menu';
import Avatar from 'primevue/avatar';
import Badge from 'primevue/badge';
import { router, usePage } from '@inertiajs/vue3';
import { renewCsrfToken } from '@/Utils/csrf.js';

const page = usePage();
const user = page.props.auth?.user;
const themeKey = 'theme-mode';

const isDarkMode = ref(localStorage.getItem(themeKey) === 'dark');
const menu = ref();
const drawerVisible = ref(false);

// track current path so we can highlight active nav item
const currentPath = ref(window.location.pathname + window.location.search);

function updateCurrentPath() {
  currentPath.value = window.location.pathname + window.location.search;
}

const userInitial = computed(() => (user?.name ? user.name.charAt(0).toUpperCase() : '?'));

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

function toggleDrawer() {
  drawerVisible.value = !drawerVisible.value;
}

const logout = () => {
  isDarkMode.value = false;
  localStorage.setItem(themeKey, 'light');
  applyTheme();

  router.post('/logout', {}, {
    onSuccess: async () => {
      await renewCsrfToken();
      router.visit(route('login'));
    },
    onError: (errors) => {
      console.error('Logout failed:', errors);
    },
  });
};

const navItems = ref([
  { label: 'Dashboard', icon: 'pi pi-chart-bar', url: '/dashboard', roles: ['OWNER', 'ADMIN'] },
  { label: 'Inventory', icon: 'pi pi-warehouse', url: '/inventory/items', roles: ['OWNER', 'USER', 'ADMIN'] },
  { label: 'Markets', icon: 'pi pi-shopping-cart', url: route('ecommerce.markets.index'), roles: ['OWNER', 'ADMIN'] },
  { label: 'Accounting', icon: 'pi pi-calculator', url: '/accounting/payments', roles: ['ADMIN', 'OWNER'] },
  { label: 'Contacts', icon: 'pi pi-users', url: '/customer', roles: ['OWNER', 'USER', 'ADMIN'] },
  { label: 'Stores', icon: 'pi pi-shop', url: route('stores.index', { filter: 'all' }), roles: ['OWNER'] },
  { label: 'Users', icon: 'pi pi-user', url: route('users.index', { filter: 'all' }), roles: ['OWNER'] },
]);

const dropdownNavItems = ref([
  { label: 'Profile', icon: 'pi pi-user', url: route('profile.show'), roles: ['OWNER', 'USER', 'ADMIN'] },
  { label: 'Users', icon: 'pi pi-users', url: route('users.index'), roles: ['ADMIN', 'OWNER'] },
  { label: 'Locations', icon: 'pi pi-map', url: route('stores.index'), roles: ['ADMIN', 'OWNER'] },
  { label: 'Dark Mode', icon: 'pi pi-moon', command: toggleDarkMode, roles: ['OWNER', 'USER', 'ADMIN'] },
  { label: 'Logout', icon: 'pi pi-sign-out', command: logout, roles: ['OWNER', 'USER', 'ADMIN'] },
]);

onMounted(() => {
  if (user && user.role) {
    navItems.value = navItems.value.filter((item) => item.roles.includes(user.role));
    dropdownNavItems.value = dropdownNavItems.value.filter((item) => item.roles.includes(user.role));
  }

  window.addEventListener('popstate', updateCurrentPath);
  document.addEventListener('inertia:finish', updateCurrentPath);
});

onUnmounted(() => {
  window.removeEventListener('popstate', updateCurrentPath);
  document.removeEventListener('inertia:finish', updateCurrentPath);
});

function isActive(item) {
  if (!item || !item.url) return false;
  try {
    const url = new URL(item.url, window.location.origin);
    const itemPath = url.pathname + url.search;
    return currentPath.value === itemPath || currentPath.value.startsWith(url.pathname);
  } catch (e) {
    return false;
  }
}

const openMenu = (event) => {
  menu.value.toggle(event);
};
</script>

<style>
:root {
  /* reusable color variables for navbar */
  --nav-text: #858c93; /* default muted gray */
  --nav-text-dark: #292c31; /* darker text / black tone */
  --nav-border: var(--nav-text-dark);
}
</style>

<style scoped>
@media screen and (min-width: 961px) {
  .hide-breakpoint {
    display: none;
  }

  .menuBar :deep(.p-menubar-root-list) {
    display: flex;
    justify-content: flex-start;
    margin-left: 230px;
  }

  .menuBar :deep(.p-menubar-button),
  .menuBar :deep(.p-menubar-end) {
    margin-left: auto;
  }
}

/* remove bottom padding/margins so navbar is flush */
.menuBar,
.menuBar :deep(.p-menubar-root-list),
.menuBar :deep(.p-menubar-root-list) > li,
.menuBar :deep(.p-menuitem-link) {
  padding-bottom: 0 !important;
  margin-bottom: 0 !important;
  line-height: 1 !important;
  border-bottom-color: transparent;
  border-bottom-style: solid;
  border-bottom-width: 0;
  /* gap: 0; */
}

.menuBar :deep(.p-menubar-item-link):hover {
  border-bottom: 3px solid var(--nav-border);
  color: var(--nav-text-dark) !important; 
}

.menuBar :deep(.p-menubar-item-content){
  background-color: transparent !important;
}

.menuBar :deep(.p-menubar-item-content):hover {
  background-color: transparent !important;
}

/* style menu item text and icons */
.menuBar :deep(.p-menuitem-link),
.menuBar :deep(.p-menubar-item-link) {
  color: var(--nav-text) !important; /* muted text */
  font-weight: 500 !important; /* semi-bold */
}

.menuBar img {
  display: block;
}


.menuBar :deep(.black_border_current_page) {
  border-bottom-width: 3px !important;
  border-bottom-style: solid !important;
  border-bottom-color: var(--nav-border) !important;
  color: var(--nav-text-dark) !important;
}

/* drawer button active state */
.black_border_current_page-drawer {
  border-bottom: 3px solid var(--nav-border) !important;
}
</style>
