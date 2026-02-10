import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, VueWrapper } from '@vue/test-utils';
import Index from '@/Pages/Customers/Index.vue';
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

// Mock date-fns
vi.mock('date-fns', () => ({
  format: vi.fn((date) => {
    if (!date) return '';
    return new Date(date).toISOString().split('T')[0];
  }),
}));

vi.mock('@/Utils/FormatUtils', () => ({
  formatPercentage: vi.fn((val) => `${val}%`),
}));

// Mock PrimeVue module - intercept useConfirm and useDialog exports
const mockConfirmRequire = vi.fn();
const mockDialogOpen = vi.fn();

vi.mock('primevue', async () => {
  const actual = await vi.importActual('primevue');
  return {
    ...actual,
    useConfirm: () => ({
      require: mockConfirmRequire
    }),
    useDialog: () => ({
      open: mockDialogOpen
    })
  };
});

// Mock Components
const MockDataTable = {
  name: 'DataTable',
  props: ['items', 'headers', 'actions', 'title'],
  template: '<div><slot></slot></div>',
  emits: ['update:selection', 'update:selected'],
};

const MockContactTabs = {
  name: 'ContactTabs',
  template: '<div><slot></slot></div>',
};

// Test Data
const mockCustomers = [
  {
    id: 1,
    name: 'John Doe',
    email: 'john@example.com',
    phone: '123-456-7890',
    revenue: 1000,
    profit: 200,
    margin: 0.2,
    balance: 50,
    credit: 100,
  },
  {
    id: 2,
    name: 'Jane Smith',
    email: 'jane@example.com',
    phone: '098-765-4321',
    revenue: 2000,
    profit: 500,
    margin: 0.25,
    balance: 0,
    credit: 0,
  }
];

describe('Customers/Index.vue', () => {
  let wrapper: VueWrapper;

  const createWrapper = () => {
    return mount(Index, {
      props: {
        customers: mockCustomers
      },
      global: {
        plugins: [
          PrimeVue,
          ToastService,
          ConfirmationService,
          DialogService
        ],
        stubs: {
          DataTable: MockDataTable,
          ContactTabs: MockContactTabs,
          DatePicker: true,
          Button: true,
          CreateEdit: true
        }
      }
    });
  };

  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('Rendering and Data Parsing', () => {
    it('parses customers correctly and formats fields', () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      const data = vm.tableData;

      expect(data.length).toBe(2);
      expect(data[0].name).toBe('John Doe');
      expect(data[0].margin).toBe('0.2%');

      expect(data[0].actions).toBeDefined();
      expect(data[0].actions.length).toBe(2);
    });
  });

  describe('Date Filtering', () => {
    it('updates data when date range is submitted', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      const newData = [
        {
            id: 3,
            name: 'New Customer',
            email: 'new@example.com',
            phone: '111-222-3333',
            revenue: 500,
            profit: 100,
            margin: 0.2,
            balance: 0,
            credit: 0,
            actions: []
        }
      ];

      vi.mocked(axios.post).mockResolvedValueOnce({ data: newData });

      vm.dates = ['2023-01-01', '2023-12-31'];

      await vm.onDateRangeSubmit();

      expect(axios.post).toHaveBeenCalledWith(
        expect.stringContaining('customer.datewise'),
        expect.objectContaining({ startDate: '2023-01-01', endDate: '2023-12-31' })
      );

      await nextTick();
      expect(vm.tableData.length).toBe(1);
      expect(vm.tableData[0].name).toBe('New Customer');
    });
  });

  describe('Add Customer', () => {
    it('opens CreateEdit modal when Add customer action is triggered', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      const dataTable = wrapper.findComponent({ name: 'DataTable' });
      const actions = dataTable.props('actions');
      const addAction = actions.find((a: any) => a.label === 'Add customer');

      expect(addAction).toBeDefined();

      await addAction.action();

      expect(mockDialogOpen).toHaveBeenCalled();
    });
  });

  describe('Delete Functionality', () => {
    it('calls delete API when action is triggered and confirmed', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      const deleteAction = vm.tableData[0].actions.find((a: any) => a.label === 'Delete');

      expect(deleteAction).toBeDefined();

      await deleteAction.action();

      expect(mockConfirmRequire).toHaveBeenCalled();

      const callArgs = mockConfirmRequire.mock.calls[0][0];

      vi.mocked(axios.delete).mockResolvedValueOnce({ status: 200 });

      await callArgs.accept();

      expect(axios.delete).toHaveBeenCalledWith(
        expect.stringContaining('customer.destroy')
      );

      const { router } = await import('@inertiajs/vue3');
      expect(router.reload).toHaveBeenCalled();
    });
  });
});
