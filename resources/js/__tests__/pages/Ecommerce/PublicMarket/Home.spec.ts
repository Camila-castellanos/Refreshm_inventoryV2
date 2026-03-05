import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { mount, VueWrapper } from '@vue/test-utils';
import Home from '@/Pages/Ecommerce/PublicMarket/Home.vue';
import PrimeVue from 'primevue/config';
import { nextTick } from 'vue';

// Mocks
const inertiaMocks = vi.hoisted(() => ({
  visit: vi.fn(),
  get: vi.fn()
}));

vi.mock('@inertiajs/vue3', () => ({
  Head: { template: '<!-- Head -->' },
  Link: { template: '<a><slot /></a>', props: ['href'] },
  router: {
    visit: inertiaMocks.visit,
    get: inertiaMocks.get
  },
  usePage: () => ({ props: { value: {} } })
}));

// Mock Layout to avoid dependency issues
vi.mock('@/Layouts/Ecommerce/MarketLayout.vue', () => ({
  default: { template: '<div><slot /></div>' }
}));

// Stubs
const MockCarousel = {
  name: 'Carousel',
  props: ['value'],
  template: `
    <div class="carousel-stub">
      <div v-for="item in value" :key="item.title || Math.random()" class="carousel-item">
        <slot name="item" :data="item"></slot>
      </div>
    </div>
  `
};

const MockProductCard = {
  name: 'ProductCard',
  props: ['item'],
  template: '<div class="product-card-stub">{{ item.model }}</div>'
};

describe('Ecommerce/PublicMarket/Home.vue', () => {
  let wrapper: VueWrapper;

  const mockMarket = {
    id: 1,
    name: 'Tech Haven',
    slug: 'tech-haven',
    currency: 'USD',
    description: 'Best tech store',
    banners: [], // Empty to test fallback
    banner_url: 'default.jpg'
  };

  const mockItems = Array(10).fill(null).map((_, i) => ({
    id: i + 1,
    model: `Phone ${i + 1}`,
    manufacturer: 'Brand X',
    selling_price: 100 * (i + 1)
  }));

  const mockStats = {
    total_products: 50,
    categories_count: 5,
    price_range: { min: 100, max: 1000 }
  };

  const createWrapper = (props = {}) => {
    return mount(Home, {
      props: {
        market: mockMarket,
        initialItems: mockItems,
        categories: ['phone'],
        stats: mockStats,
        totalItems: 50,
        ...props
      },
      global: {
        plugins: [PrimeVue],
        stubs: {
          Carousel: MockCarousel,
          ProductCard: MockProductCard,
          // Inertia components are mocked at module level, but we can stub them too if needed
        }
      }
    });
  };

  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('Rendering', () => {
    it('renders hero section with market info', () => {
      wrapper = createWrapper();
      
      expect(wrapper.text()).toContain('Welcome to Tech Haven');
      expect(wrapper.text()).toContain('Best tech store');
      expect(wrapper.text()).toContain('50 Products');
    });

    it('renders featured products limited to 8', () => {
      wrapper = createWrapper();
      
      const cards = wrapper.findAll('.product-card-stub');
      // Should show slice(0, 8) of 10 items
      expect(cards.length).toBe(8);
      expect(cards[0].text()).toBe('Phone 1');
      expect(cards[7].text()).toBe('Phone 8');
    });

    it('renders quick access brands', () => {
      wrapper = createWrapper();
      
      expect(wrapper.text()).toContain('iPhone');
      expect(wrapper.text()).toContain('Samsung');
      expect(wrapper.text()).toContain('Google');
    });
  });

  describe('Navigation', () => {
    it('navigates to brand page when clicking brand button', async () => {
      wrapper = createWrapper();
      
      // Find Apple/iPhone button in Quick Access section
      // It's the first button in that grid
      const appleBtn = wrapper.findAll('button').find(b => b.text().includes('iPhone'));
      
      await appleBtn?.trigger('click');
      
      expect(inertiaMocks.visit).toHaveBeenCalledWith(
        expect.stringContaining('/market/tech-haven/products-list?brand=Apple')
      );
    });

    it('navigates to all products from "Shop Now" hero button', async () => {
      wrapper = createWrapper();
      
      // Find "Shop Now" button in Hero (Carousel item)
      const shopBtn = wrapper.findAll('button').find(b => b.text().includes('Shop Now'));
      
      await shopBtn?.trigger('click');
      
      expect(inertiaMocks.visit).toHaveBeenCalledWith(
        expect.stringContaining('/market/tech-haven/products-list')
      );
    });

    it('navigates to sorted deals from "Shop Deals" banner', async () => {
      wrapper = createWrapper();
      
      // Find "Shop Deals" button in promo banner
      const dealsBtn = wrapper.findAll('button').find(b => b.text().includes('Shop Deals'));
      
      await dealsBtn?.trigger('click');
      
      expect(inertiaMocks.visit).toHaveBeenCalledWith(
        expect.stringContaining('/market/tech-haven/products-list?sort=price_low')
      );
    });

// Test disabled since "Browse All Products" button was removed from footer
    // it('navigates to all products from "View All" footer', async () => {
    //   wrapper = createWrapper();
    //   
    //   const viewAllBtn = wrapper.findAll('button').find(b => b.text().includes('Browse All Products'));
    //   
    //   await viewAllBtn?.trigger('click');
    //   
    //   expect(inertiaMocks.visit).toHaveBeenCalledWith(
    //     expect.stringContaining('/market/tech-haven/products-list')
    //   );
    // });
  });
});
