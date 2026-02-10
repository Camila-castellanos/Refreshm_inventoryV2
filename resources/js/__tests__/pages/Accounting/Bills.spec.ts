import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { mount, VueWrapper } from '@vue/test-utils';
import Bills from '@/Pages/Accounting/Bills.vue';
import PrimeVue from 'primevue/config';
import ToastService from 'primevue/toastservice';
import ConfirmationService from 'primevue/confirmationservice';
import DialogService from 'primevue/dialogservice';
import axios from 'axios';
import { nextTick } from 'vue';

// Mocks
vi.mock('axios');

const inertiaMocks = vi.hoisted(() => ({
  reload: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({
  router: {
    reload: inertiaMocks.reload,
  }
}));

// Mock window.location
const originalLocation = window.location;
const locationMock = { assign: vi.fn(), href: '' };

// Mock PrimeVue services (imported from 'primevue')
const dialogOpenMock = vi.fn();
const confirmRequireMock = vi.fn();

vi.mock('primevue', async () => {
  const actual: any = await vi.importActual('primevue');
  return {
    ...actual,
    useDialog: () => ({ open: dialogOpenMock }),
    useConfirm: () => ({ require: confirmRequireMock, close: vi.fn() }),
    useToast: () => ({ add: vi.fn() }) // Also needed as it's imported from primevue
  };
});

// Remove separate mocks for subpackages as they are not used
// vi.mock('primevue/usedialog', ...);
// vi.mock('primevue/useconfirm', ...);

// Stubs
const MockDataTable = {
  name: 'DataTable',
  props: ['items', 'headers', 'actions', 'selected'],
  emits: ['update:selected'],
  template: `
    <div class="datatable-stub">
      <div v-for="action in actions" :key="action.label">
        <button 
          @click="action.action()" 
          :disabled="action.disable && action.disable(selected || [])"
          class="action-btn"
        >
          {{ action.label }}
        </button>
      </div>
      <!-- Render row actions for first item to test row actions -->
      <div v-if="items.length > 0" class="row-0">
        <button 
          v-for="action in items[0].actions" 
          :key="action.label"
          @click="action.action()"
          class="row-action-btn"
        >
          {{ action.label }}
        </button>
      </div>
    </div>
  `
};

const MockAccountingTabs = { template: '<div><slot /></div>' };
const MockTabs = { template: '<div><slot /></div>' };
const MockTabList = { template: '<div><slot /></div>' };
const MockTab = { 
  props: ['value'], 
  template: '<div @click="$parent.$emit(\'update:value\', value)"></div>' 
};

// Route Mock
const routeMock = vi.fn((name, params) => `/${name}/${params || ''}`);
global.route = routeMock as any;

describe('Accounting/Bills.vue', () => {
  let wrapper: VueWrapper;

  const mockBills = [
    { 
      id: 1, 
      status: 1, // Paid
      vendor: 'Vendor A', 
      amount: 100,
      payments: [],
      balance_remaining: 0
    },
    { 
      id: 2, 
      status: 0, // Unpaid
      vendor: 'Vendor B', 
      amount: 200,
      payments: [],
      balance_remaining: 200
    }
  ];

  const createWrapper = (props = {}) => {
    return mount(Bills, {
      props: {
        items: mockBills,
        data_status: 'all',
        ...props
      },
      global: {
        plugins: [PrimeVue, ToastService, ConfirmationService, DialogService],
        stubs: {
          AccountingTabs: MockAccountingTabs,
          DataTable: MockDataTable,
          Tabs: MockTabs,
          TabList: MockTabList,
          Tab: MockTab,
          AppLayout: { template: '<div><slot /></div>' }
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
    it('processes items and adds status label', async () => {
      wrapper = createWrapper();
      await nextTick(); // Wait for onMounted
      
      const vm = wrapper.vm as any;
      expect(vm.tableData).toHaveLength(2);
      expect(vm.tableData[0].status).toBe('Paid');
      expect(vm.tableData[1].status).toBe('Unpaid');
    });
  });

  describe('Filtering', () => {
    it('navigates when filter tab changes', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      // Simulate filter change manually as Tabs stub might be tricky
      vm.currentTab = '/bills?status=paid';
      vm.filterBills();
      
      expect(locationMock.assign).toHaveBeenCalledWith('/accounting/bills?status=paid');
    });
  });

  describe('Global Actions', () => {
    it('opens Add Bills dialog', async () => {
      wrapper = createWrapper();
      
      // Find "Add bills" button
      const buttons = wrapper.findAll('.action-btn');
      const addBtn = buttons.find(b => b.text() === 'Add bills');
      
      await addBtn?.trigger('click');
      
      expect(dialogOpenMock).toHaveBeenCalledWith(
        expect.anything(), // Component
        expect.objectContaining({ props: expect.objectContaining({ header: 'Add new bills' }) })
      );
    });

    it('opens Edit Bill dialog with selection', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      // Select bill
      vm.selectedItems = [mockBills[0]];
      await nextTick();
      
      const buttons = wrapper.findAll('.action-btn');
      const editBtn = buttons.find(b => b.text() === 'Edit bill');
      
      expect(editBtn?.attributes('disabled')).toBeUndefined(); // Should be enabled
      
      await editBtn?.trigger('click');
      
      expect(dialogOpenMock).toHaveBeenCalledWith(
        expect.anything(),
        expect.objectContaining({ 
          data: expect.objectContaining({ bills: expect.arrayContaining([expect.objectContaining({ id: 1 })]) }),
          props: expect.objectContaining({ header: 'Edit bills' }) 
        })
      );
    });

    it('deletes selected bills', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      const toastSpy = vi.spyOn(vm.toast, 'add');
      
      // Select bills
      vm.selectedItems = [mockBills[0], mockBills[1]];
      await nextTick();
      
      const buttons = wrapper.findAll('.action-btn');
      const deleteBtn = buttons.find(b => b.text() === 'Delete bills');
      
      await deleteBtn?.trigger('click');
      
      // Verify confirmation
      expect(confirmRequireMock).toHaveBeenCalled();
      
      // Simulate accept
      const acceptCallback = confirmRequireMock.mock.calls[0][0].accept;
      
      vi.mocked(axios.delete).mockResolvedValue({ status: 200 });
      
      await acceptCallback();
      
      // Verify API calls (one per bill)
      expect(axios.delete).toHaveBeenCalledTimes(2);
      expect(axios.delete).toHaveBeenCalledWith(expect.stringContaining('bills.destroy/1'));
      
      // Verify success
      expect(toastSpy).toHaveBeenCalledWith(expect.objectContaining({ severity: 'success' }));
      expect(inertiaMocks.reload).toHaveBeenCalled();
      expect(vm.selectedItems).toHaveLength(0);
    });
  });

  describe('Row Actions', () => {
    it('shows View Payments for paid bills', async () => {
      // Mock item 0 is Paid
      wrapper = createWrapper();
      await nextTick();
      
      // Force render of first row actions in stub
      const vm = wrapper.vm as any;
      const actions = vm.getItemActions(mockBills[0]);
      expect(actions[0].label).toBe('View Payments');
      
      // Trigger action
      actions[0].action();
      
      expect(dialogOpenMock).toHaveBeenCalledWith(
        expect.anything(),
        expect.objectContaining({
          data: expect.objectContaining({ view: 'view' })
        })
      );
    });

    it('shows Record Payments for unpaid bills', async () => {
      // Mock item 1 is Unpaid
      wrapper = createWrapper();
      await nextTick();
      
      const vm = wrapper.vm as any;
      const actions = vm.getItemActions(mockBills[1]);
      expect(actions[0].label).toBe('Record / View Payments');
      
      // Trigger action
      actions[0].action();
      
      expect(dialogOpenMock).toHaveBeenCalledWith(
        expect.anything(),
        expect.objectContaining({
          data: expect.objectContaining({ view: 'all' })
        })
      );
    });
  });
});
