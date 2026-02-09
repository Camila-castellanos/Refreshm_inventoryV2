import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { mount, VueWrapper } from '@vue/test-utils';
// Move imports that depend on mocks AFTER the mocks
import PrimeVue from 'primevue/config';
import axios from 'axios';
import { nextTick } from 'vue';

// Mocks must be defined before importing the component
vi.mock('axios');

const inertiaMocks = vi.hoisted(() => ({
  visit: vi.fn(),
  get: vi.fn()
}));

vi.mock('@inertiajs/vue3', () => ({
  Head: { template: '<!-- Head -->' },
  Link: { template: '<a><slot /></a>' },
  router: {
    visit: inertiaMocks.visit,
    get: inertiaMocks.get
  },
  usePage: () => ({ props: { value: {} } })
}));

vi.mock('@/Layouts/Ecommerce/MarketLayout.vue', () => ({
  default: { template: '<div><slot /></div>' }
}));

// Now import the component
import ProductsList from '@/Pages/Ecommerce/PublicMarket/ProductsList.vue';

// Route Mock
const routeMock = vi.fn((name, params) => `/${name}`);
global.route = routeMock as any;

// Stubs
const MockDropdown = {
  name: 'Dropdown',
  props: ['modelValue', 'options', 'placeholder'],
  emits: ['update:modelValue', 'change'],
  template: `
    <select 
      :value="modelValue" 
      @change="$emit('update:modelValue', $event.target.value); $emit('change')"
      class="stub-dropdown"
    >
      <option v-for="opt in options" :key="opt.value" :value="opt.value">
        {{ opt.label }}
      </option>
    </select>
  `
};

const MockProductCard = {
  name: 'ProductCard',
  props: ['item'],
  template: '<div class="product-card-stub">{{ item.model }}</div>'
};

describe('Ecommerce/PublicMarket/ProductsList.vue', () => {
  let wrapper: VueWrapper;

  // Mock Data
  const mockMarket = { id: 1, name: 'Tech Market', slug: 'tech-market', currency: 'USD' };
  
  const mockItems = [
    { id: 101, model: 'iPhone 13', manufacturer: 'Apple', selling_price: 500 },
    { id: 102, model: 'Galaxy S21', manufacturer: 'Samsung', selling_price: 400 }
  ];

  const mockCategories = ['phone', 'tablet'];
  
  const mockStats = {
    total_products: 100,
    categories_count: 5,
    price_range: { min: 100, max: 1000 }
  };

  // Mock window history
  const pushStateSpy = vi.fn();
  const replaceStateSpy = vi.fn();
  
  // Mock window scroll
  const addEventListenerSpy = vi.spyOn(window, 'addEventListener');
  const removeEventListenerSpy = vi.spyOn(window, 'removeEventListener');

  const createWrapper = (props = {}) => {
    return mount(ProductsList, {
      props: {
        market: mockMarket,
        initialItems: mockItems,
        categories: mockCategories,
        stats: mockStats,
        totalItems: 100,
        filters: {},
        ...props
      },
      global: {
        plugins: [PrimeVue],
        mocks: {
          route: routeMock
        },
        stubs: {
          Head: { template: '<!-- Head -->' },
          Dropdown: MockDropdown,
          ProductCard: MockProductCard,
          Button: true
        }
      }
    });
  };

  beforeEach(() => {
    vi.clearAllMocks();
    
    // Setup window mocks
    Object.defineProperty(window, 'history', {
      value: {
        pushState: pushStateSpy,
        replaceState: replaceStateSpy,
      },
      writable: true
    });
    
    // Setup Axios default mock
    vi.mocked(axios.get).mockResolvedValue({ 
      data: { 
        data: mockItems,
        has_more_pages: true
      } 
    });
  });

  describe('Rendering and Initialization', () => {
    it('renders initial items', () => {
      wrapper = createWrapper();
      
      expect(wrapper.findAll('.product-card-stub').length).toBe(2);
      expect(wrapper.text()).toContain('iPhone 13');
      expect(wrapper.text()).toContain('Galaxy S21');
    });

    it('initializes filters from props', () => {
      wrapper = createWrapper({ 
        currentCategory: 'phone',
        currentBrand: 'Apple'
      });
      
      const vm = wrapper.vm as any;
      expect(vm.selectedCategory).toBe('phone');
      expect(vm.selectedBrand).toBe('Apple');
    });

    it('shows empty state when no items', () => {
      wrapper = createWrapper({ initialItems: [] });
      
      expect(wrapper.text()).toContain('No products found');
      // Default message when no category/search is selected
      expect(wrapper.text()).toContain('Check back later for new arrivals');
    });
  });

  describe('Filtering', () => {
    it('updates list and URL when brand changes', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      // Mock API response for filtered data
      const filteredItems = [{ id: 103, model: 'iPhone 14' }];
      vi.mocked(axios.get).mockResolvedValue({ 
        data: { data: filteredItems, has_more_pages: false } 
      });
      
      // Change Brand
      vm.selectedBrand = 'Apple';
      // Trigger update manually or via event if stub supports it
      await vm.updateFilters();
      
      // Verify Loading State
      expect(vm.loading).toBe(false); // Should be false after await
      
      // Verify API call
      expect(axios.get).toHaveBeenCalledWith(
        expect.stringContaining('/market/tech-market/products'),
        expect.objectContaining({
          params: expect.objectContaining({ brand: 'Apple' })
        })
      );
      
      // Verify Data Update
      expect(vm.items[0].model).toBe('iPhone 14');
      
      // Verify URL Update
      expect(pushStateSpy).toHaveBeenCalledWith(
        expect.anything(), 
        '', 
        expect.stringContaining('brand=Apple')
      );
    });

    it('clears filters correctly', async () => {
      wrapper = createWrapper({ currentBrand: 'Samsung' });
      const vm = wrapper.vm as any;
      
      expect(vm.selectedBrand).toBe('Samsung');
      
      await vm.clearFilters();
      
      expect(vm.selectedBrand).toBe('');
      expect(axios.get).toHaveBeenCalledWith(
        expect.any(String),
        expect.objectContaining({ params: { page: 1 } })
      );
    });
  });

  describe('View Mode', () => {
    it('toggles between individual and grouped view', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      // Initial is grouped
      expect(vm.viewMode).toBe('grouped');
      
      // Toggle to individual
      await vm.toggleViewMode('individual');
      
      expect(vm.viewMode).toBe('individual');
      expect(axios.get).toHaveBeenCalledWith(
        expect.any(String),
        expect.not.objectContaining({ 
          params: expect.objectContaining({ group_by_model: 'true' }) 
        })
      );
      
      // Toggle back to grouped
      await vm.toggleViewMode('grouped');
      
      expect(axios.get).toHaveBeenLastCalledWith(
        expect.any(String),
        expect.objectContaining({ 
          params: expect.objectContaining({ group_by_model: 'true' }) 
        })
      );
    });
  });

  describe('Infinite Scroll', () => {
    it('loads more items when scrolling to bottom', async () => {
      // Need enough items to enable infinite scroll (>= 24 items as per component logic)
      const manyItems = Array(24).fill(null).map((_, i) => ({ 
        id: i + 1000, 
        model: `Item ${i}`,
        manufacturer: 'Generic' 
      }));
      
      wrapper = createWrapper({ initialItems: manyItems });
      const vm = wrapper.vm as any;
      
      // Mock next page response
      const newItems = [{ id: 201, model: 'Pixel 6' }];
      vi.mocked(axios.get).mockResolvedValue({ 
        data: { data: newItems, has_more_pages: false } 
      });
      
      // Mock window dimensions to simulate bottom scroll
      Object.defineProperty(window, 'innerHeight', { value: 1000 });
      Object.defineProperty(window, 'scrollY', { value: 1000 });
      Object.defineProperty(document.documentElement, 'offsetHeight', { value: 2000 }); // 1000+1000 >= 2000-1000 (threshold)
      
      // Trigger scroll handler manually since we can't easily scroll jsdom
      vm.handleScroll();
      
      // Wait for async call
      await new Promise(process.nextTick);
      
      expect(axios.get).toHaveBeenCalledWith(
        expect.any(String),
        expect.objectContaining({ params: expect.objectContaining({ page: 2 }) })
      );
      
      // Verify items appended (24 initial + 1 new)
      expect(vm.items.length).toBe(25);
      expect(vm.items[24].model).toBe('Pixel 6');
    });
  });
});
