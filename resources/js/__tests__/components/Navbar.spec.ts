import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, VueWrapper } from '@vue/test-utils';
import Navbar from '@/Components/Navbar.vue';
import PrimeVue from 'primevue/config';
import { router, usePage } from '@inertiajs/vue3';
import { nextTick } from 'vue';

// Mock inertia router
vi.mock('@inertiajs/vue3', async () => {
  return {
    router: {
      visit: vi.fn(),
      post: vi.fn(),
    },
    usePage: vi.fn(),
  };
});

// Mock csrf utils
vi.mock('@/Utils/csrf.js', () => ({
  renewCsrfToken: vi.fn(),
}));

// Mock PrimeVue components
const stubs = {
  Menubar: {
    template: '<div class="menubar"><slot name="start" /><div v-for="item in model" class="nav-item">{{ item.label }}</div><slot name="end" /></div>',
    props: ['model']
  },
  Button: true,
  Drawer: {
    template: '<div class="drawer"><slot /></div>',
    props: ['modelValue']
  },
  Menu: true,
  Avatar: true,
  Badge: true,
};

describe('Components/Navbar.vue', () => {
  let wrapper: VueWrapper;

  const createWrapper = (user = {}, permissions_defaults = {}) => {
    vi.mocked(usePage).mockReturnValue({
      props: {
        auth: {
          user: {
            id: 1,
            name: 'Test User',
            role: 'USER',
            page_permissions: null,
            ...user
          }
        },
        permissions_defaults: {
          'Dashboard': [],
          'Inventory': ['Active Inventory', 'On Hold', 'Sold'],
          'Accounting': ['Payments', 'Expenses', 'Bills', 'Taxes'],
          'Contacts': ['Customers', 'Prospects', 'Vendors', 'Mailing list', 'Email editor'],
          ...permissions_defaults
        }
      }
    } as any);

    return mount(Navbar, {
      global: {
        plugins: [PrimeVue],
        stubs: stubs,
        directives: {
          ripple: {}
        }
      }
    });
  };

  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders all items for OWNER role even if permissions are null', async () => {
    wrapper = createWrapper({ role: 'OWNER', page_permissions: null });
    await nextTick();
    const items = wrapper.findAll('.nav-item');
    const labels = items.map(i => i.text());
    
    expect(labels).toContain('Dashboard');
    expect(labels).toContain('Inventory');
    expect(labels).toContain('Accounting');
    expect(labels).toContain('Contacts');
    expect(labels).toContain('Stores');
    expect(labels).toContain('Company');
    expect(labels).toContain('Users');
  });

  it('renders default items for USER role when permissions are null', async () => {
    wrapper = createWrapper({ role: 'USER', page_permissions: null });
    await nextTick();
    const items = wrapper.findAll('.nav-item');
    const labels = items.map(i => i.text());
    // console.log('USER default labels:', labels);
    
    expect(labels).toContain('Dashboard');
    expect(labels).toContain('Inventory');
    expect(labels).toContain('Accounting');
    expect(labels).toContain('Contacts');
    
    // Stores, Company, Users require specific roles or permissions not in defaults
    expect(labels).not.toContain('Stores');
    expect(labels).not.toContain('Company');
    expect(labels).not.toContain('Users');
  });

  it('filters items based on specific page_permissions for ADMIN role', async () => {
    wrapper = createWrapper({ 
      role: 'ADMIN', 
      page_permissions: { 'Inventory': [], 'Dashboard': [] } 
    });
    await nextTick();
    const items = wrapper.findAll('.nav-item');
    const labels = items.map(i => i.text());
    
    expect(labels).toContain('Dashboard');
    expect(labels).toContain('Inventory');
    expect(labels).not.toContain('Accounting');
    expect(labels).not.toContain('Contacts');
  });

  it('renders Markets if permitted for USER', async () => {
    wrapper = createWrapper({ 
      role: 'USER', 
      page_permissions: { 'Markets': [] } 
    });
    await nextTick();
    const items = wrapper.findAll('.nav-item');
    const labels = items.map(i => i.text());
    
    expect(labels).toContain('Markets');
  });
});
