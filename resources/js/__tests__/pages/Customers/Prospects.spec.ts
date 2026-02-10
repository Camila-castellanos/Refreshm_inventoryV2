import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, VueWrapper } from '@vue/test-utils';
import Prospects from '@/Pages/Customers/Prospects.vue';
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
  emits: ['update:selection', 'update:selected', 'edit', 'delete'],
};

const MockContactTabs = {
  name: 'ContactTabs',
  template: '<div><slot></slot></div>',
};

// Test Data
const mockProspects = [
  {
    id: 1,
    first_name: 'John',
    last_name: 'Doe',
    email: 'john@example.com',
    company_name: 'ACME Corp',
    phone_number: '123-456-7890',
    state: 'California',
    country: 'USA',
    contact_type: 'lead',
  },
  {
    id: 2,
    first_name: 'Jane',
    last_name: 'Smith',
    email: 'jane@example.com',
    company_name: 'Tech Inc',
    phone_number: '098-765-4321',
    state: 'New York',
    country: 'USA',
    contact_type: 'prospect',
  }
];

describe('Customers/Prospects.vue', () => {
  let wrapper: VueWrapper;

  const createWrapper = () => {
    return mount(Prospects, {
      props: {
        prospects: mockProspects
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
          CreateEditModal: true
        }
      }
    });
  };

  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('Rendering and Data Parsing', () => {
    it('parses prospects correctly and formats fields', () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      const data = vm.prospectTableData;

      expect(data.length).toBe(2);
      expect(data[0].name).toBe('John Doe');
      expect(data[0].contact_type).toBe('Lead'); // Capitalized
      expect(data[1].name).toBe('Jane Smith');
      expect(data[1].contact_type).toBe('Prospect'); // Capitalized
    });

    it('renders DataTable with correct title and items', () => {
      wrapper = createWrapper();
      const dataTable = wrapper.findComponent({ name: 'DataTable' });

      expect(dataTable.props('title')).toBe('Prospects');
      expect(dataTable.props('items')).toHaveLength(2);
      expect(dataTable.props('items')[0].name).toBe('John Doe');
      expect(dataTable.props('items')[1].name).toBe('Jane Smith');
    });
  });

  describe('Add Prospect', () => {
    it('opens CreateEditModal when Add prospect action is triggered', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      const dataTable = wrapper.findComponent({ name: 'DataTable' });
      const actions = dataTable.props('actions');
      const addAction = actions.find((a: any) => a.label === 'Add prospect');

      expect(addAction).toBeDefined();

      await addAction.action();

      expect(mockDialogOpen).toHaveBeenCalled();
    });
  });

  describe('Edit Prospect', () => {
    it('opens CreateEditModal with prospect data when Edit action is triggered', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      const editAction = vm.prospectTableData[0].actions.find((a: any) => a.label === 'Edit');

      expect(editAction).toBeDefined();

      await editAction.action();

      expect(mockDialogOpen).toHaveBeenCalled();
    });
  });

  describe('Delete Functionality', () => {
    it('calls delete API when action is triggered and confirmed', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      const deleteAction = vm.prospectTableData[0].actions.find((a: any) => a.label === 'Delete');

      expect(deleteAction).toBeDefined();

      await deleteAction.action();

      expect(mockConfirmRequire).toHaveBeenCalled();

      const callArgs = mockConfirmRequire.mock.calls[0][0];

      vi.mocked(axios.delete).mockResolvedValueOnce({ status: 200 });

      await callArgs.accept();

      expect(axios.delete).toHaveBeenCalledWith(
        expect.stringContaining('prospects.destroy')
      );

      const { router } = await import('@inertiajs/vue3');
      expect(router.reload).toHaveBeenCalled();
    });
  });

  describe('Bulk Actions', () => {
    it('disables Delete prospects button when no items selected', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      const dataTable = wrapper.findComponent({ name: 'DataTable' });
      const actions = dataTable.props('actions');
      const bulkDeleteAction = actions.find((a: any) => a.label === 'Delete prospects');

      expect(bulkDeleteAction).toBeDefined();
      
      // Should be disabled when no selection
      vm.selectedItems = [];
      expect(bulkDeleteAction.disable([])).toBe(true);
      
      // Should be enabled when items are selected
      expect(bulkDeleteAction.disable([mockProspects[0]])).toBe(false);
    });
  });
});
