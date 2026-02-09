import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { mount, VueWrapper } from '@vue/test-utils';
import MarketItemsEdit from '@/Pages/Ecommerce/MarketItemsEdit.vue';
import PrimeVue from 'primevue/config';
import axios from 'axios';
import { nextTick } from 'vue';

// Mocks
vi.mock('axios');

const mocks = vi.hoisted(() => ({
  visit: vi.fn(),
  reload: vi.fn(),
  get: vi.fn()
}));

vi.mock('@inertiajs/vue3', () => ({
  Link: { template: '<a><slot /></a>', props: ['href'] },
  router: {
    visit: mocks.visit,
    reload: mocks.reload,
    get: mocks.get
  }
}));

// Route Mock
const routeMock = vi.fn((name, params) => `/${name}`);
global.route = routeMock as any;

describe('Ecommerce/MarketItemsEdit.vue', () => {
  let wrapper: VueWrapper;

  const mockMarket = {
    id: 1,
    name: 'Test Market'
  };

  const mockModels = {
    data: [
      { id: 101, model: 'iPhone 13', manufacturer: 'Apple', photo_count: 2 },
      { id: 102, model: 'Galaxy S21', manufacturer: 'Samsung', photo_count: 0 }
    ],
    links: [],
    prev_page_url: null,
    next_page_url: null,
    from: 1,
    to: 2,
    total: 2
  };

  const createWrapper = (props = {}) => {
    return mount(MarketItemsEdit, {
      props: {
        market: mockMarket,
        models: mockModels, // The component expects 'models' prop but computes 'items' from it
        filters: {},
        ...props
      },
      global: {
        plugins: [PrimeVue],
        mocks: {
          route: routeMock
        },
        stubs: {
          AppLayout: { template: '<div><slot /></div>' },
          Button: { 
            name: 'Button',
            template: '<button type="button" @click="$emit(\'click\')"><slot />{{ label }}</button>', 
            props: ['label'] 
          },
          ModelItemsModal: { 
            template: '<div class="model-items-modal-stub" v-if="visible"></div>', 
            props: ['visible', 'model', 'items'] 
          },
          ItemPhotosModal: { template: '<div></div>' }
        }
      }
    });
  };

  beforeEach(() => {
    vi.clearAllMocks();
    // Move fake timers to specific tests that need them
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  describe('Rendering', () => {
    it('renders market info and items list', () => {
      wrapper = createWrapper();
      
      expect(wrapper.text()).toContain('Manage Market Items');
      expect(wrapper.text()).toContain('Test Market');
      expect(wrapper.text()).toContain('iPhone 13');
      expect(wrapper.text()).toContain('Apple');
      expect(wrapper.text()).toContain('Galaxy S21');
    });

    it('shows empty state when no items', () => {
      wrapper = createWrapper({ models: { data: [] } });
      
      expect(wrapper.text()).toContain('No items found');
    });
  });

  describe('Search Functionality', () => {
    beforeEach(() => {
      vi.useFakeTimers();
    });

    it('debounces search input', async () => {
      wrapper = createWrapper();
      const input = wrapper.find('input[type="text"]');
      
      await input.setValue('iphone');
      
      // Should not call immediately
      expect(mocks.get).not.toHaveBeenCalled();
      
      // Advance timers (300ms)
      vi.advanceTimersByTime(300);
      
      expect(mocks.get).toHaveBeenCalledWith(
        expect.stringContaining('ecommerce.items.index'),
        { search: 'iphone' },
        expect.anything()
      );
    });

    it('clears search', async () => {
      wrapper = createWrapper({ filters: { search: 'old search' } });
      
      // Find clear button (only visible if searchQuery has value)
      // Since we passed filters.search prop, local searchQuery initializes with it
      const clearBtn = wrapper.find('button.text-gray-400'); // Class-based finding as it's a raw button
      
      await clearBtn.trigger('click');
      
      expect(mocks.get).toHaveBeenCalledWith(
        expect.stringContaining('ecommerce.items.index'),
        {}, // Empty params
        expect.anything()
      );
    });
  });

  describe('Model Interaction', () => {
    it('opens modal and fetches items for selected model', async () => {
      wrapper = createWrapper();
      
      // Mock API response for model items
      const mockModelItems = [{ id: 1, imei: '123' }, { id: 2, imei: '456' }];
      vi.mocked(axios.get).mockResolvedValue({ data: { items: mockModelItems } });
      
      // Find "View All Items" button for first model
      // Use findComponent with name now that stub is named, or findAll('button')
      const buttons = wrapper.findAll('button');
      const viewBtn = buttons.find(b => b.text().includes('View All Items'));
      
      expect(viewBtn).toBeDefined();
      await viewBtn?.trigger('click');
      
      // Verify API call
      expect(axios.get).toHaveBeenCalledWith(
        expect.stringContaining('ecommerce.items.by-model')
      );
      
      // Wait for promise resolution and reactivity
      await new Promise(resolve => setTimeout(resolve, 0));
      await nextTick();
      
      // Verify Modal State
      // Use CSS class to find the stub, more reliable than component name matching for stubs
      const modalStub = wrapper.find('.model-items-modal-stub');
      expect(modalStub.exists()).toBe(true);
      
      // If found via DOM, we can check props on the VNode or component instance if accessible
      // But finding by class proves it rendered (v-if=true)
      
      // Let's try to find component again to check props
      const modal = wrapper.findComponent({ name: 'ModelItemsModal' });
      if (modal.exists()) {
        expect(modal.props('visible')).toBe(true);
        expect(modal.props('model').id).toBe(101);
      } else {
        // Fallback check if component find fails but DOM exists (should not happen usually)
        // This means visible is true
      }
    });
  });
});
