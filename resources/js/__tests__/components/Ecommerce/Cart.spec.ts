import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, VueWrapper } from '@vue/test-utils';
import Cart from '@/Components/Ecommerce/Cart.vue';
import PrimeVue from 'primevue/config';
import { createTestingPinia } from '@pinia/testing';
import { useCartStore } from '@/stores/cartStore';
import { nextTick } from 'vue';

// Mock window location
const locationMock = { href: '' };
const originalLocation = window.location;

describe('Components/Ecommerce/Cart.vue', () => {
  let wrapper: VueWrapper;

  const mockMarket = {
    slug: 'tech-market',
    currency: 'USD'
  };

  const mockItem = {
    id: 1,
    model: 'iPhone 13',
    manufacturer: 'Apple',
    selling_price: 500,
    quantity: 1
  };

  const createWrapper = (initialState = {}) => {
    return mount(Cart, {
      props: {
        visible: true,
        market: mockMarket
      },
      global: {
        plugins: [
          PrimeVue,
          createTestingPinia({
            initialState: {
              cart: {
                items: [mockItem],
                marketSlug: 'tech-market',
                ...initialState
              }
            },
            stubActions: false, // Use real actions logic or spy manually? Let's use false to test integration with store logic we just proved
            createSpy: vi.fn // Automatically spy on actions
          })
        ],
        stubs: {
          Drawer: {
            template: '<div v-if="visible" class="drawer-stub"><slot /></div>',
            props: ['visible']
          },
          Dialog: {
            template: '<div v-if="visible" class="dialog-stub"><slot /></div>',
            props: ['visible']
          }
        },
        directives: {
          tooltip: {}
        }
      }
    });
  };

  beforeEach(() => {
    vi.clearAllMocks();
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

  describe('Rendering', () => {
    it('renders cart items correctly', () => {
      wrapper = createWrapper();
      const store = useCartStore();
      
      expect(wrapper.text()).toContain('Shopping Cart (1)');
      expect(wrapper.text()).toContain('iPhone 13');
      expect(wrapper.text()).toContain('$ 500'); // Formatted price
    });

    it('shows empty state when no items', () => {
      wrapper = createWrapper({ items: [] });
      
      expect(wrapper.text()).toContain('Your cart is empty');
      expect(wrapper.text()).toContain('Continue Shopping');
    });
  });

  describe('Interactions', () => {
    it('removes item when trash icon clicked', async () => {
      wrapper = createWrapper();
      const store = useCartStore();
      
      // Find remove button
      const removeBtn = wrapper.find('button i.pi-trash').element.parentElement;
      await wrapper.find('button:has(.pi-trash)').trigger('click');
      
      expect(store.removeItem).toHaveBeenCalledWith(1);
    });

    it('opens clear confirmation dialog', async () => {
      wrapper = createWrapper();
      
      // Click "Clear Cart" button
      const clearBtn = wrapper.findAll('button').find(b => b.text().includes('Clear Cart'));
      await clearBtn?.trigger('click');
      
      // Check if dialog is visible
      const dialog = wrapper.find('.dialog-stub');
      expect(dialog.exists()).toBe(true);
      expect(dialog.text()).toContain('Are you sure you want to remove all items');
    });

    it('clears cart on confirmation', async () => {
      wrapper = createWrapper();
      const store = useCartStore();
      
      // Open dialog
      const clearBtn = wrapper.findAll('button').find(b => b.text().includes('Clear Cart'));
      await clearBtn?.trigger('click');
      
      // Find Confirm button inside dialog
      // Dialog renders inside the stub in our test
      const confirmBtn = wrapper.find('.dialog-stub button.bg-red-500');
      await confirmBtn.trigger('click');
      
      expect(store.clearCart).toHaveBeenCalled();
    });
  });

  describe('Navigation', () => {
    it('redirects to checkout', async () => {
      wrapper = createWrapper();
      
      const checkoutBtn = wrapper.findAll('button').find(b => b.text().includes('Proceed to Checkout'));
      await checkoutBtn?.trigger('click');
      
      expect(locationMock.href).toBe('/market/tech-market/cart');
    });

    it('closes cart on close button click', async () => {
      wrapper = createWrapper();
      
      // Click X button
      const closeBtn = wrapper.find('button .pi-times').element.parentElement;
      await wrapper.find('button:has(.pi-times)').trigger('click');
      
      expect(wrapper.emitted('update:visible')).toBeTruthy();
      expect(wrapper.emitted('update:visible')![0]).toEqual([false]);
    });
  });
});
