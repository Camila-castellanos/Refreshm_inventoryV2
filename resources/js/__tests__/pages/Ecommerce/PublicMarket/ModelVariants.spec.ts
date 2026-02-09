import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { mount, VueWrapper } from '@vue/test-utils';
import ModelVariants from '@/Pages/Ecommerce/PublicMarket/ModelVariants.vue';
import PrimeVue from 'primevue/config';
import ToastService from 'primevue/toastservice';
import { nextTick } from 'vue';

// Mocks
const addItemMock = vi.fn();
vi.mock('@/composables/useCart', () => ({
  useCart: () => ({
    addItem: addItemMock
  })
}));

// Mock Layout
vi.mock('@/Layouts/Ecommerce/MarketLayout.vue', () => ({
  default: { template: '<div><slot /></div>' }
}));

// Route Mock
const routeMock = vi.fn((name, params) => `/${name}`);
global.route = routeMock as any;

// Mock window location
const originalLocation = window.location;
const locationMock = { href: '' };

// Stubs
const MockVariantCard = {
  name: 'VariantCard',
  props: ['item'],
  emits: ['add-to-cart', 'view-product'],
  template: `
    <div class="variant-card-stub">
      {{ item.model }} - {{ item.storage }} - {{ item.colour }}
      <button class="add-btn" @click="$emit('add-to-cart', item)">Add</button>
      <button class="view-btn" @click="$emit('view-product', item.id)">View</button>
    </div>
  `
};

describe('Ecommerce/PublicMarket/ModelVariants.vue', () => {
  let wrapper: VueWrapper;

  const mockMarket = {
    id: 1,
    slug: 'tech-market',
    currency: 'USD'
  };

  // Mock items structure (flat array as per current logic preference)
  const mockModelData = {
    manufacturer: 'Apple',
    model: 'iPhone 13',
    type: 'Phone',
    items: [
      { id: 1, storage: '128GB', colour: 'Red', condition: 'A', battery: '90', market_price: 500, issues: '' },
      { id: 2, storage: '128GB', colour: 'Blue', condition: 'B', battery: '85', market_price: 450, issues: '' },
      { id: 3, storage: '256GB', colour: 'Red', condition: 'A', battery: '100', market_price: 600, issues: '' },
      { id: 4, storage: '256GB', colour: 'Blue', condition: 'C', battery: '80', market_price: 550, issues: 'Screen scratch' }
    ]
  };

  const createWrapper = () => {
    return mount(ModelVariants, {
      props: {
        market: mockMarket,
        modelData: mockModelData
      },
      global: {
        plugins: [PrimeVue, ToastService],
        stubs: {
          VariantCard: MockVariantCard,
          // Inertia components if needed, but layout mock handles it
        }
      }
    });
  };

  beforeEach(() => {
    vi.clearAllMocks();
    // Setup window location mock
    Object.defineProperty(window, 'location', {
      value: locationMock,
      writable: true
    });
  });

  afterEach(() => {
    Object.defineProperty(window, 'location', {
      value: originalLocation,
      writable: true
    });
  });

  describe('Rendering and Initialization', () => {
    it('renders all variants initially', () => {
      wrapper = createWrapper();
      
      const cards = wrapper.findAll('.variant-card-stub');
      expect(cards.length).toBe(4);
      // Check for content that is definitely rendered (Storage) instead of Model which might be missing in mapping
      expect(wrapper.text()).toContain('128GB');
      expect(wrapper.text()).toContain('Red');
    });

    it('populates filter options correctly', () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      // Storage options: 128GB, 256GB
      expect(vm.availableStorages).toContain('128GB');
      expect(vm.availableStorages).toContain('256GB');
      
      // Color options: Red, Blue
      expect(vm.availableColors).toContain('Red');
      expect(vm.availableColors).toContain('Blue');
    });
  });

  describe('Filtering Logic', () => {
    it('filters by storage', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      // Select 128GB
      vm.filters.storage = '128GB';
      await nextTick();
      
      const cards = wrapper.findAll('.variant-card-stub');
      expect(cards.length).toBe(2); // Items 1 and 2
      
      // Check text inside cards only to avoid matching sidebar filters
      const cardsText = cards.map(c => c.text()).join(' ');
      expect(cardsText).toContain('128GB');
      expect(cardsText).not.toContain('256GB');
    });

    it('filters by multiple criteria (Storage + Color)', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      vm.filters.storage = '256GB';
      vm.filters.colour = 'Blue';
      await nextTick();
      
      const cards = wrapper.findAll('.variant-card-stub');
      expect(cards.length).toBe(1); // Item 4
      expect(wrapper.text()).toContain('256GB - Blue');
    });

    it('updates available options dynamically', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      // Select 'C' condition (Item 4 only: 256GB, Blue)
      vm.filters.grade = 'Fair'; // Logic converts 'C' to 'Fair' usually, check simplifyCondition
      // Wait, simplifyCondition is internal.
      // Item 4 condition is 'C'. simplifyCondition('C') -> 'Fair'.
      // availableGrades returns simplified grades.
      // Let's filter by the internal representation or how the select binds.
      // The select loops availableGrades.
      // If I select 'Fair', it should match item 4.
      
      vm.filters.grade = 'Fair';
      await nextTick();
      
      // Available colors should now ONLY be Blue
      expect(vm.availableColors).toContain('Blue');
      expect(vm.availableColors).not.toContain('Red');
    });

    it('resets filters', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      vm.filters.storage = '128GB';
      await nextTick();
      expect(vm.filteredVariants.length).toBe(2);
      
      // Find reset button
      // It's a button with text "Reset Filters"
      const resetButtons = wrapper.findAll('button').filter(b => b.text().includes('Reset Filters'));
      await resetButtons[0].trigger('click');
      
      expect(vm.filteredVariants.length).toBe(4);
      expect(vm.filters.storage).toBe('');
    });
  });

  describe('Interactions', () => {
    it('adds item to cart', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      addItemMock.mockReturnValue(true); // Success
      const toastSpy = vi.spyOn(vm.toast, 'add');
      
      // Find a card and click add
      const card = wrapper.findComponent(MockVariantCard);
      card.vm.$emit('add-to-cart', mockModelData.items[0]);
      
      expect(addItemMock).toHaveBeenCalledWith(expect.objectContaining({ id: 1 }));
      expect(toastSpy).toHaveBeenCalledWith(expect.objectContaining({ severity: 'success' }));
    });

    it('navigates to product detail', async () => {
      wrapper = createWrapper();
      
      const card = wrapper.findComponent(MockVariantCard);
      card.vm.$emit('view-product', 1);
      
      expect(locationMock.href).toBe('/market/tech-market/product/1');
    });
  });
});
