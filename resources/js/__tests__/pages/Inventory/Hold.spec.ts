import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { mount, VueWrapper } from '@vue/test-utils';
import Hold from '@/Pages/Inventory/Hold.vue';
import PrimeVue from 'primevue/config';
import ToastService from 'primevue/toastservice';
import ConfirmationService from 'primevue/confirmationservice';
import DialogService from 'primevue/dialogservice';
import { createTestingPinia } from '@pinia/testing';
import axios from 'axios';
import { router } from '@inertiajs/vue3';

// Mocks
vi.mock('axios');
vi.mock('@inertiajs/vue3', () => ({
  router: {
    reload: vi.fn(),
  },
}));

// Mock PrimeVue useDialog (imported from 'primevue' in component)
const mockDialogOpen = vi.fn();
vi.mock('primevue', async () => {
  const actual: any = await vi.importActual('primevue');
  return {
    ...actual,
    useDialog: () => ({
      open: mockDialogOpen
    })
  };
});

// Mock PrimeVue useDialog (imported from 'primevue/usedialog' in composables)
vi.mock('primevue/usedialog', () => ({
  useDialog: () => ({
    open: mockDialogOpen
  })
}));

// Mock window.location
const originalLocation = window.location;
const mockLocation = {
  reload: vi.fn(),
  assign: vi.fn(),
};

// Components Stubs
const MockDataTable = {
  name: 'DataTable',
  props: ['items', 'headers', 'actions', 'title', 'inventory', 'selection'],
  template: `
    <div>
      <slot></slot>
      <!-- Expose selection update for testing -->
      <div class="selection-trigger" @click="$emit('update:selected', [items[0]])"></div>
    </div>
  `,
  emits: ['update:selection', 'update:selected'],
};

const MockItemsTabs = { template: '<div><slot></slot></div>' };

// Test Data
const mockTabs = [{ id: 3, name: 'Hold', route: 'inventory.hold' }];
const mockItems = [
  {
    id: 201,
    manufacturer: 'Apple',
    model: 'iPhone 11',
    battery: '85',
    sold: null,
    storage: { name: 'Storage B', limit: 10, items_count: 2 },
    position: 2,
    vendor: { vendor: 'Supplier Z' }
  },
  {
    id: 202,
    manufacturer: 'Samsung',
    model: 'Note 20',
    battery: null, // Null battery case
    sold: null,
    storage: null,
    position: null,
    vendor: { vendor: 'Supplier W' }
  }
];

describe('Inventory/Hold.vue', () => {
  let wrapper: VueWrapper;
  let confirmRequireSpy: any;
  let toastAddSpy: any;

  const createWrapper = () => {
    return mount(Hold, {
      props: {
        tabs: mockTabs,
        items: mockItems,
        fields: [],
        customers: [{ id: 1, name: 'Customer A' }]
      },
      global: {
        plugins: [
          createTestingPinia(),
          PrimeVue,
          ToastService,
          ConfirmationService,
          // DialogService removed as we mock useDialog directly
        ],
        stubs: {
          DataTable: MockDataTable,
          ItemsTabs: MockItemsTabs,
          Dialog: true,
          CustomFields: true,
          ItemsSell: true 
        }
      }
    });
  };

  beforeEach(() => {
    vi.clearAllMocks();
    // Setup window.location mock
    Object.defineProperty(window, 'location', {
      configurable: true,
      value: mockLocation,
    });
  });

  afterEach(() => {
    // Restore window.location
    Object.defineProperty(window, 'location', {
      configurable: true,
      value: originalLocation,
    });
  });

  describe('Rendering and Data Parsing', () => {
    it('formats item data correctly', () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      const data = vm.tableData;

      const item1 = data.find((i: any) => i.id === 201);
      expect(item1.location).toBe('Storage B - 2/10');
      expect(item1.battery).toBe('85 %');
      expect(item1.vendor).toBe('Supplier Z');

      const item2 = data.find((i: any) => i.id === 202);
      expect(item2.location).toBe('N/A');
      expect(item2.battery).toBe(''); // Null battery becomes empty string
    });

    it('adds row actions (Label) to each item', () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      const item = vm.tableData[0];
      
      expect(item.actions).toBeDefined();
      const labelAction = item.actions.find((a: any) => a.label === 'Label');
      expect(labelAction).toBeDefined();
      
      // Test action execution
      labelAction.action(item);
      // Adjusted expectation to match route mock output
      expect(window.location.assign).toHaveBeenCalledWith(expect.stringContaining('items.label'));
    });
  });

  describe('Unhold Logic (Return Items)', () => {
    it('calls API and reloads on successful return', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      // Select items
      vm.selectedItems = [mockItems[0]];
      
      // Mock confirmation acceptance
      const confirmSpy = vi.spyOn(wrapper.vm.confirm, 'require').mockImplementation((options: any) => {
        options.accept();
      });
      
      vi.mocked(axios.put).mockResolvedValueOnce({ status: 200 });
      
      await vm.onClickReturn();
      
      expect(confirmSpy).toHaveBeenCalled();
      expect(axios.put).toHaveBeenCalledWith(expect.stringContaining('items.unhold'), {
        data: [mockItems[0]]
      });
      expect(mockLocation.reload).toHaveBeenCalled();
    });

    it('shows error toast on API failure', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      // Select items
      vm.selectedItems = [mockItems[0]];
      
      // Mock confirmation acceptance
      vi.spyOn(wrapper.vm.confirm, 'require').mockImplementation((options: any) => {
        options.accept();
      });
      
      // Mock toast
      const toastSpy = vi.spyOn(wrapper.vm.toast, 'add');
      
      vi.mocked(axios.put).mockRejectedValueOnce(new Error('API Error'));
      
      await vm.onClickReturn();
      
      expect(toastSpy).toHaveBeenCalledWith(expect.objectContaining({ severity: 'error' }));
      expect(mockLocation.reload).not.toHaveBeenCalled();
    });
  });

  describe('Sell from Hold', () => {
    it('opens ItemsSell modal with selected items', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      vm.selectedItems = [mockItems[0], mockItems[1]];
      
      vm.openSellItemsModal();
      
      // Check basic structure first
      expect(mockDialogOpen).toHaveBeenCalledWith(
        expect.anything(), // Component (ItemsSell)
        expect.objectContaining({
          data: expect.objectContaining({
            customers: vm.props.customers
          })
        })
      );

      // Manually verify items to handle Ref wrapping complexity
      const callArgs = mockDialogOpen.mock.calls[0];
      const dataItems = callArgs[1].data.items;
      
      // Items should be passed, either as raw array or Ref
      const itemsValue = dataItems.value || dataItems;
      expect(itemsValue).toHaveLength(2);
      expect(itemsValue[0].id).toBe(201);
    });

    it('clears selection and reloads router on modal close', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      vm.selectedItems = [mockItems[0]];
      
      // Trigger modal open to get access to onClose callback
      vm.openSellItemsModal();
      
      const openCall = mockDialogOpen.mock.calls[0];
      const config = openCall[1];
      
      // Simulate close
      config.onClose();
      
      expect(vm.selectedItems.length).toBe(0);
      expect(router.reload).toHaveBeenCalledWith({ only: ['items'] });
    });
  });
});
