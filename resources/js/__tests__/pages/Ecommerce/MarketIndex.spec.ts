import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, VueWrapper } from '@vue/test-utils';
import MarketIndex from '@/Pages/Ecommerce/MarketIndex.vue';
import PrimeVue from 'primevue/config';
import { createTestingPinia } from '@pinia/testing';

// Mocks
const mockDelete = vi.fn();
vi.mock('@inertiajs/vue3', () => ({
  Link: { template: '<a :href="href"><slot /></a>', props: ['href'] },
  useForm: () => ({
    delete: mockDelete,
    processing: false,
    reset: vi.fn()
  }),
  router: { visit: vi.fn() }
}));

// Route Mock
const routeMock = vi.fn((name, params) => {
  if (params) return `/${name}/${params}`;
  return `/${name}`;
});
global.route = routeMock as any;

describe('Ecommerce/MarketIndex.vue', () => {
  let wrapper: VueWrapper;

  const mockMarkets = {
    data: [
      {
        id: 1,
        name: 'Amazon US',
        slug: 'amazon-us',
        is_active: true,
        published_items_count: 150,
        created_at: '2024-01-01T12:00:00Z',
        shop: { name: 'My Tech Shop' }
      },
      {
        id: 2,
        name: 'eBay UK',
        slug: 'ebay-uk',
        is_active: false,
        published_items_count: 0,
        created_at: '2024-02-15T10:30:00Z',
        shop: { name: 'UK Outlet' }
      }
    ],
    links: [],
    prev_page_url: null,
    next_page_url: null
  };

  const createWrapper = (props = { markets: mockMarkets }) => {
    return mount(MarketIndex, {
      props,
      global: {
        plugins: [PrimeVue, createTestingPinia()],
        mocks: {
          route: routeMock
        },
        directives: {
          tooltip: {}
        },
        stubs: {
          AppLayout: { template: '<div><slot /><slot name="header" /></div>' },
          Button: { template: '<button><slot />{{ label }}</button>', props: ['label', 'icon', 'severity'] },
          Dialog: { 
            template: '<div v-if="visible" class="dialog-stub"><slot /><slot name="footer" /></div>', 
            props: ['visible', 'header'] 
          }
        }
      }
    });
  };

  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('Rendering', () => {
    it('renders the market list correctly', () => {
      wrapper = createWrapper();
      
      expect(wrapper.text()).toContain('Amazon US');
      expect(wrapper.text()).toContain('My Tech Shop');
      expect(wrapper.text()).toContain('150'); // Products count
      expect(wrapper.text()).toContain('Active');
      
      expect(wrapper.text()).toContain('eBay UK');
      expect(wrapper.text()).toContain('Inactive');
    });

    it('shows empty state when no markets exist', () => {
      wrapper = createWrapper({ markets: { data: [] } });
      
      expect(wrapper.text()).toContain('No markets yet');
      expect(wrapper.text()).toContain('Create your first market');
    });
  });

  describe('Actions', () => {
    it('generates correct links for actions', () => {
      wrapper = createWrapper();
      
      // Find links - we stub Link as 'a'
      const links = wrapper.findAll('a');
      
      // Check Edit Link for Amazon (ID 1)
      // routeMock returns /name/param -> /ecommerce.markets.edit/1
      const editLink = links.find(l => l.attributes('href')?.includes('ecommerce.markets.edit/1'));
      expect(editLink).toBeDefined();
      
      // Check Manage Items Link
      const manageLink = links.find(l => l.attributes('href')?.includes('ecommerce.items.index/1'));
      expect(manageLink).toBeDefined();
    });

    it('opens delete confirmation modal', async () => {
      wrapper = createWrapper();
      
      // Find delete button for first market
      // It's a button inside the last td
      // The button has @click="deleteMarket(market)"
      const rows = wrapper.findAll('tbody tr');
      const deleteBtn = rows[0].find('button'); // First button in actions is Visit? No, first is <a> tag. 
      // Let's find the specific delete button using icon class logic or order
      // In template: Visit (a), Manage (Link), Edit (Link), Delete (button)
      // So delete is the only <button> element in the actions cell? No, Manage/Edit are Links (stubbed as a).
      // So yes, Delete is the <button>.
      
      // Actually, my Button stub is <button> too. But the action Delete is a native <button>, NOT a PrimeVue <Button>.
      // Template: <button @click="deleteMarket(market)" ...>
      // The 'Create Market' button IS a PrimeVue Button.
      
      const deleteAction = rows[0].findAll('button').find(b => b.find('i.pi-trash').exists());
      
      await deleteAction?.trigger('click');
      
      expect(wrapper.find('.dialog-stub').exists()).toBe(true);
      expect(wrapper.text()).toContain('Are you sure you want to delete the market "Amazon US"?');
    });

    it('calls delete action on confirmation', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      // Manually trigger delete state to skip finding button again
      vm.deleteMarket(mockMarkets.data[0]);
      await wrapper.vm.$nextTick();
      
      // Find Confirm button in Dialog footer
      // It uses PrimeVue Button with label "Delete Market" or similar
      // My Button stub renders the label text.
      
      // But wait, the dialog footer has TWO buttons. Cancel and Delete Market.
      // We need to find the one that calls confirmDelete.
      
      // Let's access the method directly for robustness or find button by text
      const buttons = wrapper.findAll('button');
      const confirmBtn = buttons.find(b => b.text().includes('Delete Market'));
      
      await confirmBtn?.trigger('click');
      
      expect(mockDelete).toHaveBeenCalledWith(
        expect.stringContaining('ecommerce.markets.destroy/1'),
        expect.any(Object)
      );
    });
  });
});
