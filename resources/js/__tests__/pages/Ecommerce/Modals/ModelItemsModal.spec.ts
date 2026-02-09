import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, VueWrapper } from '@vue/test-utils';
import ModelItemsModal from '@/Pages/Ecommerce/Modals/ModelItemsModal.vue';
import PrimeVue from 'primevue/config';
import ToastService from 'primevue/toastservice';
import axios from 'axios';
import { nextTick } from 'vue';

// Mocks
vi.mock('axios');

// Route Mock
const routeMock = vi.fn((name, params) => `/${name}`);
global.route = routeMock as any;

describe('Ecommerce/Modals/ModelItemsModal.vue', () => {
  let wrapper: VueWrapper;
  let toastAddSpy: any;

  // Mock Data
  const mockMarket = { id: 1, name: 'Test Market' };
  const mockModel = { id: 101, model: 'iPhone 13', manufacturer: 'Apple' };
  const mockItems = [
    { 
      id: 1, 
      imei: '12345', 
      type: 'Phone', 
      colour: 'Black', 
      condition: 'A', 
      battery: 90, 
      market_price: 500,
      status: 'available', 
      is_visible: true,
      issues: '{}' 
    },
    { 
      id: 2, 
      imei: '67890', 
      type: 'Phone', 
      colour: 'White', 
      condition: 'B', 
      battery: 75, 
      market_price: 400,
      status: 'sold', 
      is_visible: false,
      issues: '{"screen": "scratch"}' 
    }
  ];

  const createWrapper = (props = {}) => {
    return mount(ModelItemsModal, {
      props: {
        visible: true,
        market: mockMarket,
        model: mockModel,
        items: JSON.parse(JSON.stringify(mockItems)), // Deep copy to prevent mutation pollution
        ...props
      },
      global: {
        plugins: [PrimeVue, ToastService],
        mocks: {
          route: routeMock
        },
        stubs: {
          Dialog: {
            template: '<div v-if="visible" class="dialog-stub"><slot /><slot name="header"></slot><slot name="footer"></slot></div>',
            props: ['visible']
          },
          Button: { template: '<button type="button" @click="$emit(\'click\')"><slot />{{ label }}</button>', props: ['label'] },
          Toast: { template: '<div></div>' },
          ItemDescriptionModal: { template: '<div></div>' },
          ItemPhotosModal: { template: '<div></div>' }
        },
        directives: {
          tooltip: {}
        }
      }
    });
  };

  beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(axios.post).mockResolvedValue({ data: { message: 'Success', is_visible: true } });
  });

  describe('Rendering', () => {
    it('renders model info and items table', () => {
      wrapper = createWrapper();
      
      expect(wrapper.text()).toContain('iPhone 13');
      expect(wrapper.text()).toContain('Apple');
      expect(wrapper.text()).toContain('12345'); // IMEI
      expect(wrapper.text()).toContain('Black'); // Color
      expect(wrapper.text()).toContain('90%'); // Battery
    });

    it('renders empty state when no items', () => {
      wrapper = createWrapper({ items: [] });
      
      expect(wrapper.text()).toContain('No items found');
    });
  });

  describe('Local Filtering', () => {
    it('filters items by search query', async () => {
      wrapper = createWrapper();
      const input = wrapper.find('input[type="text"]');
      
      // Search for "White" (Item 2)
      await input.setValue('White');
      
      const rows = wrapper.findAll('tbody tr');
      expect(rows.length).toBe(1);
      expect(rows[0].text()).toContain('White');
      expect(rows[0].text()).not.toContain('Black');
    });

    it('parses issues correctly', () => {
      wrapper = createWrapper();
      const rows = wrapper.findAll('tbody tr');
      
      // Item 1: No issues
      expect(rows[0].text()).toContain('Sin issues');
      
      // Item 2: Issues
      expect(rows[1].text()).toContain('scratch');
    });
  });

  describe('Price Update', () => {
    it('updates price on input blur', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      toastAddSpy = vi.spyOn(vm.toast, 'add');
      
      const input = wrapper.find('input[type="number"]'); // First item price input
      await input.setValue('550');
      await input.trigger('blur');
      
      expect(axios.post).toHaveBeenCalledWith(
        expect.stringContaining('ecommerce.items.update-price'),
        { price: 550 }
      );
      
      expect(toastAddSpy).toHaveBeenCalledWith(expect.objectContaining({ severity: 'success' }));
    });
  });

  describe('Visibility Toggling', () => {
    it('toggles single item visibility', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      toastAddSpy = vi.spyOn(vm.toast, 'add');
      
      // Find toggle button for Item 1 (Visible -> Hidden)
      // Item 1 is visible (eye icon). Button class bg-green-100
      const toggleBtn = wrapper.find('tbody tr:first-child button.bg-green-100');
      
      vi.mocked(axios.post).mockResolvedValueOnce({ 
        data: { message: 'Updated', is_visible: false } 
      });
      
      await toggleBtn.trigger('click');
      
      expect(axios.post).toHaveBeenCalledWith(
        expect.stringContaining('ecommerce.items.toggle-visibility'),
        { current_value: true }
      );
      
      // Check reactivity
      expect(vm.items[0].is_visible).toBe(false);
      expect(toastAddSpy).toHaveBeenCalled();
    });

    it('sets bulk visibility (Show All)', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      // Find Show All button
      const showAllBtn = wrapper.findAll('button').find(b => b.text().includes('Show All'));
      
      await showAllBtn?.trigger('click');
      
      expect(axios.post).toHaveBeenCalledWith(
        expect.stringContaining('ecommerce.items.set-bulk-visibility'),
        { item_ids: [1, 2], is_visible: true }
      );
      
      // Verify local update
      expect(vm.items[1].is_visible).toBe(true);
    });
  });

  describe('Child Modals', () => {
    it('opens description modal on edit click', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      // Find "Edit Description" button
      const editBtn = wrapper.findAll('button').find(b => b.text().includes('Edit Description'));
      
      await editBtn?.trigger('click');
      
      expect(vm.showDescriptionModal).toBe(true);
      expect(vm.descriptionItem.id).toBe(1);
    });
  });
});
