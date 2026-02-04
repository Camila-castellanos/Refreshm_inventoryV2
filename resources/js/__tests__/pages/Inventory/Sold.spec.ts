import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { mount, VueWrapper } from '@vue/test-utils';
import Sold from '@/Pages/Inventory/Sold.vue';
import PrimeVue from 'primevue/config';
import ToastService from 'primevue/toastservice';
import ConfirmationService from 'primevue/confirmationservice';
import { createTestingPinia } from '@pinia/testing';
import axios from 'axios';
import { router } from '@inertiajs/vue3';

// Mocks
vi.mock('axios');
vi.mock('@inertiajs/vue3', () => ({
  router: {
    visit: vi.fn(),
    get: vi.fn(),
    reload: vi.fn(),
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

// Components Stubs
const MockDataTable = {
  name: 'DataTable',
  props: ['items', 'headers', 'actions', 'title', 'inventory', 'selection'],
  template: `
    <div>
      <slot></slot>
      <div class="datatable-rows">
        <div v-for="item in items" :key="item.id" class="row">
          <!-- Render actions to test click handlers -->
          <button 
            v-for="action in item.actions" 
            :key="action.label" 
            @click="action.action(item)"
            :class="'action-' + action.label.replace(' ', '-').toLowerCase()">
            {{ action.label }}
          </button>
        </div>
      </div>
    </div>
  `,
  emits: ['update:selection', 'update:selected'],
};

const MockItemsTabs = { template: '<div><slot></slot></div>' };
const MockDatePicker = {
  name: 'DatePicker',
  props: ['modelValue'],
  emits: ['update:modelValue'],
  template: '<input :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />'
};

// Test Data
const mockTabs = [{ id: 2, name: 'Sold', route: 'inventory.sold' }];
const mockItems = [
  {
    id: 101,
    manufacturer: 'Apple',
    model: 'iPhone 12',
    battery: '88',
    cost: 200,
    profit: 100,
    selling_price: 300,
    sold_storage_name: 'Main Store',
    sold_position: 'A1',
    vendor: { vendor: 'Supplier X' }
  },
  {
    id: 102,
    manufacturer: 'Samsung',
    model: 'S20',
    battery: '90 %',
    cost: 150,
    profit: 50,
    selling_price: 200,
    sold_storage_name: null,
    sold_position: null,
    vendor: null
  }
];

describe('Inventory/Sold.vue', () => {
  let wrapper: VueWrapper;
  let confirmRequireSpy: any;
  let toastAddSpy: any;

  const createWrapper = () => {
    return mount(Sold, {
      props: {
        tabs: mockTabs,
        items: mockItems,
        fields: []
      },
      global: {
        plugins: [
          createTestingPinia(),
          PrimeVue,
          ToastService,
          ConfirmationService
        ],
        stubs: {
          DataTable: MockDataTable,
          ItemsTabs: MockItemsTabs,
          DatePicker: MockDatePicker,
          Button: true,
          Dialog: true,
          CustomFields: true,
          Card: true
        }
      }
    });
  };

  beforeEach(() => {
    vi.clearAllMocks();
    
    // Spy on PrimeVue services
    // We need to access the services from the wrapper or mock the hook usage
    // Since useConfirm is called in setup, we need to mock the service injection
  });

  // Mock document.cookie with appending behavior
  let cookieStore = '';
  Object.defineProperty(document, 'cookie', {
    get: () => cookieStore,
    set: (val) => {
      // Simple mock: just append for testing presence
      cookieStore += (cookieStore ? '; ' : '') + val;
    },
    configurable: true
  });

  afterEach(() => {
    cookieStore = ''; // Clear cookies
    vi.useRealTimers();
  });

  describe('Rendering and Data Parsing', () => {
    it('formats item data correctly for the table', () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      const data = vm.tableData;

      const item1 = data.find((i: any) => i.id === 101);
      expect(item1.cost).toBe('200'); 
      expect(item1.location).toBe('Main Store - (A1)');
      // Access value if it's a ref, otherwise check directly
      const bat1 = item1.battery?.value ?? item1.battery;
      expect(bat1).toBe('88%');

      const item2 = data.find((i: any) => i.id === 102);
      expect(item2.location).toBe('N/A - (N/A)');
      const bat2 = item2.battery?.value ?? item2.battery;
      expect(bat2).toBe('90 %'); 
    });
  });

  describe('Date Range Report', () => {
    it('submits date range to router.visit', async () => {
      // Fix timezone issues
      vi.useFakeTimers();
      const date = new Date(2024, 0, 1, 12); // Jan 1 2024, noon to avoid rollover
      vi.setSystemTime(date);

      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      const start = new Date(2024, 0, 1, 12); // Jan 1
      const end = new Date(2024, 0, 31, 12); // Jan 31
      vm.dates = [start, end];

      await vm.onDateRangeSubmit();

      expect(router.visit).toHaveBeenCalledWith(expect.stringContaining('sales.report'), expect.objectContaining({
        data: { start: '2024-01-01', end: '2024-01-31' },
        only: ['items']
      }));
    });

    it('manages loading state via router callbacks', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      // Setup dates to pass validation
      vm.dates = [new Date(), new Date()];
      
      await vm.onDateRangeSubmit();
      
      const visitCall = vi.mocked(router.visit).mock.calls[0][1] as any;
      
      // Test onStart
      expect(vm.isLoading).toBe(false);
      visitCall.onStart();
      expect(vm.isLoading).toBe(true);
      expect(vm.loadingMessage).toContain('Loading');

      // Test onProgress
      visitCall.onProgress({ percentage: 50 });
      expect(vm.loadingMessage).toContain('50%');

      // Test onFinish
      visitCall.onFinish();
      expect(vm.isLoading).toBe(false);
    });
  });

  describe('Row Actions', () => {
    it('edit action sets cookies and navigates', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      // Find item 1 action 'Edit Items'
      const item = vm.tableData[0];
      const editAction = item.actions.find((a: any) => a.label === 'Edit Items');
      
      // Trigger action
      editAction.action();
      
      expect(document.cookie).toContain('paginate=');
      // Just check the URL part since the second arg might be missing/undefined
      expect(router.get).toHaveBeenCalledWith(expect.stringContaining('items.edit'));
    });

    it('print label action calls openLabels', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      const item = vm.tableData[0];
      const printAction = item.actions.find((a: any) => a.label === 'Print Label');
      
      // Mock axios for label generation
      vi.mocked(axios.get).mockResolvedValueOnce({ data: 'pdf-blob' });
      
      await printAction.action();
      
      expect(axios.get).toHaveBeenCalledWith(expect.stringContaining('items=101'), expect.any(Object));
      expect(mockOpen).toHaveBeenCalled();
    });
  });

  describe('Return Functionality', () => {
    it('opens confirmation and calls API on acceptance', async () => {
      wrapper = createWrapper();
      // Access the useConfirm require method by mocking the PrimeVue service
      // Or we can simulate the onReturn call directly which uses confirm.require
      
      const vm = wrapper.vm as any;
      const itemToReturn = mockItems[0];
      
      // Mock confirmation acceptance
      const confirmSpy = vi.spyOn(wrapper.vm.confirm, 'require').mockImplementation((options: any) => {
        options.accept();
      });
      
      vi.mocked(axios.put).mockResolvedValueOnce({ status: 200 });
      
      await vm.onReturn(itemToReturn);
      
      expect(confirmSpy).toHaveBeenCalled();
      expect(vm.isLoading).toBe(true);
      expect(axios.put).toHaveBeenCalledWith(expect.stringContaining('items.return'), {
        selectedItems: [itemToReturn]
      });
      
      // Should reload data
      expect(router.visit).toHaveBeenCalledWith(expect.stringContaining('sales.report'), expect.any(Object));
    });

    it('handles bulk return correctly', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      const itemsToReturn = [mockItems[0], mockItems[1]];
      
      const confirmSpy = vi.spyOn(wrapper.vm.confirm, 'require').mockImplementation((options: any) => {
        expect(options.message).toContain('return 2 items'); // Plural check
        options.accept();
      });
      
      vi.mocked(axios.put).mockResolvedValueOnce({ status: 200 });
      
      await vm.onReturn(itemsToReturn);
      
      expect(axios.put).toHaveBeenCalledWith(expect.anything(), {
        selectedItems: itemsToReturn
      });
    });
  });
});
