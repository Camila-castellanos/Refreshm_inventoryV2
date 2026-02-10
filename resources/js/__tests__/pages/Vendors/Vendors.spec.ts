import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, VueWrapper } from '@vue/test-utils';
import Vendors from '@/Pages/Vendors/Vendors.vue';
import PrimeVue from 'primevue/config';
import ToastService from 'primevue/toastservice';
import ConfirmationService from 'primevue/confirmationservice';
import DialogService from 'primevue/dialogservice';
import axios from 'axios';

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

// Mock PrimeVue module
const mockConfirmRequire = vi.fn();
const mockDialogOpen = vi.fn();
const mockToastAdd = vi.fn();

vi.mock('primevue', async () => {
  const actual = await vi.importActual('primevue');
  return {
    ...actual,
    useConfirm: () => ({
      require: mockConfirmRequire
    }),
    useDialog: () => ({
      open: mockDialogOpen
    }),
  };
});

// Mock useToast separately since it's imported from primevue/usetoast
vi.mock('primevue/usetoast', () => ({
  useToast: () => ({
    add: mockToastAdd
  })
}));

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
const mockVendors = [
  {
    id: 1,
    vendor: 'Tech Supplies Inc',
    email: 'contact@techsupplies.com',
    phone: '123-456-7890',
    revenue: 50000,
    profit: 10000,
    margin: 0.2,
    balance_payable: 5000,
    total_spend: 45000,
  },
  {
    id: 2,
    vendor: 'Electronics Corp',
    email: 'info@electronicscorp.com',
    phone: '098-765-4321',
    revenue: 75000,
    profit: 15000,
    margin: 0.2,
    balance_payable: 8000,
    total_spend: 67000,
  }
];

describe('Vendors/Vendors.vue', () => {
  let wrapper: VueWrapper;

  const createWrapper = () => {
    return mount(Vendors, {
      props: {
        vendors: mockVendors
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
    it('parses vendors correctly and formats fields', () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      const data = vm.vendorTableData;

      expect(data.length).toBe(2);
      expect(data[0].vendor).toBe('Tech Supplies Inc');
      expect(data[0].margin).toBe('0.2%');
      expect(data[1].vendor).toBe('Electronics Corp');
      expect(data[1].margin).toBe('0.2%');
    });

    it('renders DataTable with correct title', () => {
      wrapper = createWrapper();
      const dataTable = wrapper.findComponent({ name: 'DataTable' });

      expect(dataTable.props('title')).toBe('Vendors');
    });

    it('renders DataTable with vendor data', () => {
      wrapper = createWrapper();
      const dataTable = wrapper.findComponent({ name: 'DataTable' });

      expect(dataTable.props('items')).toHaveLength(2);
      expect(dataTable.props('items')[0].vendor).toBe('Tech Supplies Inc');
    });
  });

  describe('Date Filtering', () => {
    it('updates data when date range is submitted', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      const newData = [
        {
          id: 3,
          vendor: 'New Vendor Co',
          email: 'new@vendor.com',
          phone: '111-222-3333',
          revenue: 30000,
          profit: 6000,
          margin: 0.2,
          balance_payable: 2000,
          total_spend: 28000,
        }
      ];

      vi.mocked(axios.post).mockResolvedValueOnce({ data: newData });

      vm.dates = ['2023-01-01', '2023-12-31'];

      await vm.onDateRangeSubmit();

      expect(axios.post).toHaveBeenCalledWith(
        expect.stringContaining('vendor.datewise'),
        expect.objectContaining({ startDate: '2023-01-01', endDate: '2023-12-31' })
      );

      expect(vm.vendorTableData.length).toBe(1);
      expect(vm.vendorTableData[0].vendor).toBe('New Vendor Co');
    });
  });

  describe('New Vendor Action', () => {
    it('opens CreateEdit modal when New Vendor is clicked', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      const dataTable = wrapper.findComponent({ name: 'DataTable' });
      const actions = dataTable.props('actions');
      const newVendorAction = actions.find((a: any) => a.label === 'New Vendor');

      expect(newVendorAction).toBeDefined();

      await newVendorAction.action();

      expect(mockDialogOpen).toHaveBeenCalled();
    });
  });

  describe('Edit Vendor', () => {
    it('opens CreateEdit modal with vendor data when Edit is clicked', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      const editAction = vm.vendorTableData[0].actions.find((a: any) => a.label === 'Edit');

      expect(editAction).toBeDefined();

      await editAction.action();

      expect(mockDialogOpen).toHaveBeenCalled();
    });
  });

  describe('Delete Individual Vendor', () => {
    it('calls delete API when individual delete is confirmed', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      const deleteAction = vm.vendorTableData[0].actions.find((a: any) => a.label === 'Delete');

      expect(deleteAction).toBeDefined();

      await deleteAction.action();

      expect(mockConfirmRequire).toHaveBeenCalled();

      const callArgs = mockConfirmRequire.mock.calls[0][0];

      vi.mocked(axios.delete).mockResolvedValueOnce({ status: 200 });

      await callArgs.accept();

      expect(axios.delete).toHaveBeenCalledWith(
        expect.stringContaining('vendor.destroy')
      );

      const { router } = await import('@inertiajs/vue3');
      expect(router.reload).toHaveBeenCalled();
    });
  });

  describe('Bulk Delete', () => {
    it('disables Delete vendors button when no selection', () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      const dataTable = wrapper.findComponent({ name: 'DataTable' });
      const actions = dataTable.props('actions');
      const bulkDeleteAction = actions.find((a: any) => a.label === 'Delete vendors');

      expect(bulkDeleteAction).toBeDefined();
      expect(bulkDeleteAction.disable([])).toBe(true);
      expect(bulkDeleteAction.disable([mockVendors[0]])).toBe(false);
    });

    it('calls bulk delete API when multiple vendors selected and confirmed', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      vm.selectedVendors = [mockVendors[0], mockVendors[1]];

      const dataTable = wrapper.findComponent({ name: 'DataTable' });
      const actions = dataTable.props('actions');
      const bulkDeleteAction = actions.find((a: any) => a.label === 'Delete vendors');

      await bulkDeleteAction.action();

      expect(mockConfirmRequire).toHaveBeenCalled();

      const callArgs = mockConfirmRequire.mock.calls[0][0];

      vi.mocked(axios.delete).mockResolvedValue({ status: 200 });

      await callArgs.accept();

      expect(axios.delete).toHaveBeenCalledTimes(2);

      const { router } = await import('@inertiajs/vue3');
      expect(router.reload).toHaveBeenCalledWith({ only: ['vendors'] });
    });

    it('handles bulk delete success', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      vm.selectedVendors = [mockVendors[0]];

      const dataTable = wrapper.findComponent({ name: 'DataTable' });
      const actions = dataTable.props('actions');
      const bulkDeleteAction = actions.find((a: any) => a.label === 'Delete vendors');

      await bulkDeleteAction.action();

      const callArgs = mockConfirmRequire.mock.calls[0][0];

      vi.mocked(axios.delete).mockResolvedValueOnce({ status: 200 });

      await callArgs.accept();

      // Verify API was called and page reloaded
      expect(axios.delete).toHaveBeenCalled();
      const { router } = await import('@inertiajs/vue3');
      expect(router.reload).toHaveBeenCalledWith({ only: ['vendors'] });
    });

    it('handles bulk delete failure gracefully', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      vm.selectedVendors = [mockVendors[0]];

      const dataTable = wrapper.findComponent({ name: 'DataTable' });
      const actions = dataTable.props('actions');
      const bulkDeleteAction = actions.find((a: any) => a.label === 'Delete vendors');

      await bulkDeleteAction.action();

      const callArgs = mockConfirmRequire.mock.calls[0][0];

      vi.mocked(axios.delete).mockRejectedValueOnce(new Error('Delete failed'));

      // Should execute without throwing
      await callArgs.accept();
      
      // Verify API was called even though it failed
      expect(axios.delete).toHaveBeenCalled();
    });
  });
});
