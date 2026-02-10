import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, VueWrapper } from '@vue/test-utils';
import Expenses from '@/Pages/Accounting/Expenses.vue';
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

// Mock PrimeVue services (module level)
const dialogOpenMock = vi.fn();
const confirmRequireMock = vi.fn();

vi.mock('primevue', async () => {
  const actual: any = await vi.importActual('primevue');
  return {
    ...actual,
    useDialog: () => ({ open: dialogOpenMock }),
    useConfirm: () => ({ require: confirmRequireMock }),
    useToast: () => ({ add: vi.fn() })
  };
});

const routeMock = vi.fn((name, params) => `/${name}`);
global.route = routeMock as any;

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
    </div>
  `
};

const MockAccountingTabs = { template: '<div><slot /></div>' };

describe('Accounting/Expenses.vue', () => {
  let wrapper: VueWrapper;

  const mockExpenses = [
    { id: 1, name: 'Office Supplies', total: 100, date: '2024-01-01' },
    { id: 2, name: 'Rent', total: 1000, date: '2024-01-05' }
  ];

  const createWrapper = () => {
    return mount(Expenses, {
      props: {
        items: mockExpenses
      },
      global: {
        plugins: [PrimeVue, ToastService, ConfirmationService, DialogService],
        mocks: { route: routeMock },
        stubs: {
          AccountingTabs: MockAccountingTabs,
          DataTable: MockDataTable,
          AppLayout: { template: '<div><slot /></div>' }
        }
      }
    });
  };

  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('Rendering', () => {
    it('initializes table data from props', async () => {
      wrapper = createWrapper();
      await nextTick();
      
      const vm = wrapper.vm as any;
      expect(vm.tableData).toHaveLength(2);
      expect(vm.tableData[0].name).toBe('Office Supplies');
    });
  });

  describe('CRUD Actions', () => {
    it('opens Add Expenses dialog', async () => {
      wrapper = createWrapper();
      
      const buttons = wrapper.findAll('.action-btn');
      const addBtn = buttons.find(b => b.text() === 'Add expenses');
      
      await addBtn?.trigger('click');
      
      expect(dialogOpenMock).toHaveBeenCalledWith(
        expect.anything(),
        expect.objectContaining({ props: expect.objectContaining({ header: 'Add new expense' }) })
      );
    });

    it('deletes selected expenses', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      // Select expenses
      vm.selectedItems = [mockExpenses[0]];
      await nextTick();
      
      const buttons = wrapper.findAll('.action-btn');
      const deleteBtn = buttons.find(b => b.text().includes('Delete'));
      
      expect(deleteBtn).toBeDefined();
      await deleteBtn?.trigger('click');
      
      expect(confirmRequireMock).toHaveBeenCalled();
      
      // Simulate accept
      const acceptCallback = confirmRequireMock.mock.calls[0][0].accept;
      
      vi.mocked(axios.delete).mockResolvedValue({ status: 200 });
      
      await acceptCallback();
      
      expect(axios.delete).toHaveBeenCalledWith(
        expect.stringContaining('expenses.obliterate'),
        expect.objectContaining({ data: [mockExpenses[0]] })
      );
      
      // Note: The delete action uses promise chaining for reload
      // We need to wait for promise chain
      await new Promise(resolve => setTimeout(resolve, 0));
      expect(inertiaMocks.reload).toHaveBeenCalled();
    });
  });
});
