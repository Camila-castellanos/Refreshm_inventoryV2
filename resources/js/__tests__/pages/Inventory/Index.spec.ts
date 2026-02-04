import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, VueWrapper } from '@vue/test-utils';
import Index from '@/Pages/Inventory/Index.vue';
import { createTestingPinia } from '@pinia/testing';
import PrimeVue from 'primevue/config';
import ToastService from 'primevue/toastservice';
import ConfirmationService from 'primevue/confirmationservice';
import DialogService from 'primevue/dialogservice';
import axios from 'axios';
import { nextTick } from 'vue';

// Mocks
vi.mock('axios');
vi.mock('@inertiajs/vue3', () => ({
  router: {
    reload: vi.fn(),
    visit: vi.fn(),
  },
}));

// Mock window functions
const mockOpen = vi.fn();
const mockURL = {
  createObjectURL: vi.fn(() => 'blob:url'),
  revokeObjectURL: vi.fn(),
};
global.window.open = mockOpen;
global.URL.createObjectURL = mockURL.createObjectURL;
global.URL.revokeObjectURL = mockURL.revokeObjectURL;

// Mocks Components
const MockDataTable = {
  name: 'DataTable',
  props: ['items', 'headers', 'actions', 'title', 'inventory', 'selection'],
  template: '<div><slot></slot></div>',
  emits: ['update:selection', 'update:selected'],
};

const MockItemsTabs = {
  name: 'ItemsTabs',
  props: ['custom-tabs'],
  template: '<div><slot></slot></div>',
};

const MockStoragesAssign = {
  name: 'StoragesAssign',
  props: ['items'],
  template: '<div></div>',
};

const MockAddItemsToSale = {
  name: 'AddItemsToSale',
  props: ['visible'],
  emits: ['update:visible', 'add'],
  template: '<div></div>',
};

// Test Data
const mockTabs = [
  { id: 1, name: 'Active Inventory', route: 'inventory.index' },
  { id: 2, name: 'Sold', route: 'inventory.sold' }
];

const mockFields = [
  { value: 'custom_1', text: 'Custom 1', type: 'text', active: true },
  { value: 'custom_2', text: 'Custom 2', type: 'text', active: false }
];

const mockItems = [
  {
    id: 1,
    manufacturer: 'Apple',
    model: 'iPhone 13',
    grade: 'A',
    battery: '95',
    selling_price: 500,
    sold: null,
    storage: { name: 'Storage A', limit: 20, items_count: 5 },
    position: 5,
    vendor: { vendor: 'Supplier X' }
  },
  {
    id: 2,
    manufacturer: 'Samsung',
    model: 'Galaxy S21',
    grade: 'B',
    battery: '85 %', // Already formatted
    selling_price: 400,
    sold: null,
    storage: null, // No storage
    position: null,
    vendor: { vendor: 'Supplier Y' }
  },
  {
    id: 3,
    manufacturer: 'Google',
    model: 'Pixel 6',
    grade: 'C',
    battery: null, // Null battery
    selling_price: 300,
    sold: '2024-01-01', // Sold item (should be filtered out)
    storage: null,
    position: null,
    vendor: null
  }
];

describe('Inventory/Index.vue', () => {
  let wrapper: VueWrapper;

  const createWrapper = () => {
    return mount(Index, {
      props: {
        tabs: mockTabs,
        items: mockItems,
        fields: mockFields,
        customers: []
      },
      global: {
        plugins: [
          createTestingPinia(),
          PrimeVue,
          ToastService,
          ConfirmationService,
          DialogService
        ],
        stubs: {
          DataTable: MockDataTable,
          ItemsTabs: MockItemsTabs,
          StoragesAssign: MockStoragesAssign,
          AddItemsToSale: MockAddItemsToSale,
          CustomFields: true,
          ItemsSell: true,
          MoveItem: true
        },
        provide: {
          // Mocking PrimeVue useDialog
          'dialog': {
            open: vi.fn(),
          }
        }
      }
    });
  };

  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('Rendering and Data Parsing', () => {
    it('parses items correctly: filters sold items and formats fields', () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      const data = vm.tableData;

      // Should filter out item id 3 (sold)
      expect(data.length).toBe(2);
      expect(data.find((i: any) => i.id === 3)).toBeUndefined();

      // Check battery formatting
      const item1 = data.find((i: any) => i.id === 1);
      expect(item1.battery).toBe('95 %'); // Added %

      const item2 = data.find((i: any) => i.id === 2);
      expect(item2.battery).toBe('85 %'); // Kept %

      // Check location formatting
      expect(item1.location).toBe('Storage A - 5/20');
      expect(item2.location).toBe('N/A');
    });

    it('combines default headers with active custom fields', () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      const headers = vm.allHeaders;

      // Should contain custom_1 but NOT custom_2 (active: false)
      expect(headers.some((h: any) => h.name === 'custom_1')).toBe(true);
      expect(headers.some((h: any) => h.name === 'custom_2')).toBe(false);
    });
  });

  describe('Selection Handling', () => {
    it('updates selectedItems when DataTable emits update', async () => {
      wrapper = createWrapper();
      // Find component by name to ensure we get the Vue component wrapper
      const dataTable = wrapper.findComponent({ name: 'DataTable' });
      
      const selection = [mockItems[0]];
      await dataTable.vm.$emit('update:selected', selection);
      
      // Using vm to access selectedItems ref
      expect((wrapper.vm as any).selectedItems).toEqual(selection);
    });
  });

  describe('Global Actions', () => {
    it('refreshTableData calls API and updates tableData', async () => {
      wrapper = createWrapper();
      const newItems = [
        { ...mockItems[0], model: 'Updated iPhone' }
      ];
      
      vi.mocked(axios.get).mockResolvedValueOnce({ data: newItems });
      
      // Call the function directly (it's exposed to template via emit/ref but we test the function)
      const vm = wrapper.vm as any;
      await vm.refreshTableData();
      
      // The route mock returns '/items.getItems' for route('items.getItems')
      expect(axios.get).toHaveBeenCalledWith('/items.getItems'); 
      expect(vm.tableData[0].model).toBe('Updated iPhone');
    });

    it('openLabels generates PDF for selected items', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      // Select items
      vm.selectedItems = [mockItems[0], mockItems[1]];
      
      vi.mocked(axios.get).mockResolvedValueOnce({ data: 'fake-pdf-blob' });
      
      await vm.openLabels();
      
      // Expect API call with joined IDs
      expect(axios.get).toHaveBeenCalledWith(expect.stringContaining('items=1%2C2'), expect.any(Object));
      // Expect window.open
      expect(mockOpen).toHaveBeenCalled();
    });

    it('does not open labels if no selection', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      vm.selectedItems = [];
      
      await vm.openLabels();
      expect(axios.get).not.toHaveBeenCalled();
    });
  });

  describe('Modals Integration', () => {
    it('opens CustomFields modal when action triggered', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      // Trigger via table action logic or direct access
      vm.showCustomFields = true;
      await nextTick();
      
      expect(wrapper.findComponent({ name: 'CustomFields' }).exists()).toBe(true); // It's inside a Dialog
    });

    it('addItemsToSale calls API and reloads on success', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      const { router } = await import('@inertiajs/vue3');
      
      vm.selectedItems = [mockItems[0]];
      const saleId = 123;
      
      vi.mocked(axios.post).mockResolvedValueOnce({ status: 200 });
      
      await vm.addItemsToSale(saleId);
      
      // Verify payload
      expect(axios.post).toHaveBeenCalledWith(
        expect.stringContaining('payments'),
        expect.objectContaining({
          sale_id: 123,
          items: expect.arrayContaining([
            expect.objectContaining({ id: 1 })
          ])
        })
      );
      
      // Verify reload
      expect(router.reload).toHaveBeenCalledWith({ only: ['items'] });
      // Verify selection cleared
      expect(vm.selectedItems.length).toBe(0);
    });
  });
});
