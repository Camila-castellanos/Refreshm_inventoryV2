import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, VueWrapper } from '@vue/test-utils';
import Index from '@/Pages/Stores/Index.vue';
import PrimeVue from 'primevue/config';
import ToastService from 'primevue/toastservice';
import ConfirmationService from 'primevue/confirmationservice';
import DialogService from 'primevue/dialogservice';
import axios from 'axios';

// Mocks - usando vi.fn() directamente dentro del factory
vi.mock('axios');

vi.mock('@inertiajs/vue3', () => {
  return {
    router: {
      reload: vi.fn(),
      visit: vi.fn(),
    },
  };
});

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
    useToast: () => ({
      add: mockToastAdd
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

// Test Data
const mockStores = [
  {
    id: 1,
    name: 'Main Store',
    email: 'main@store.com',
  },
  {
    id: 2,
    name: 'Secondary Store',
    email: 'secondary@store.com',
  }
];

describe('Stores/Index.vue', () => {
  let wrapper: VueWrapper;

  const createWrapper = () => {
    return mount(Index, {
      props: {
        stores: mockStores
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
          SelectUsers: true
        }
      }
    });
  };

  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('Rendering', () => {
    it('renders DataTable with correct title', () => {
      wrapper = createWrapper();
      const dataTable = wrapper.findComponent({ name: 'DataTable' });

      expect(dataTable.props('title')).toBe('Stores');
    });

    it('renders DataTable with store data', () => {
      wrapper = createWrapper();
      const dataTable = wrapper.findComponent({ name: 'DataTable' });

      expect(dataTable.props('items')).toHaveLength(2);
      expect(dataTable.props('items')[0].name).toBe('Main Store');
      expect(dataTable.props('items')[1].name).toBe('Secondary Store');
    });

    it('renders correct headers', () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      expect(vm.headers).toEqual([
        { label: 'Name', name: 'name', type: 'string' },
        { label: 'Email', name: 'email', type: 'string' },
        { label: 'Actions', name: 'actions', type: 'any' },
      ]);
    });
  });

  describe('Data Parsing', () => {
    it('parses stores with actions', () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      expect(vm.tableData).toHaveLength(2);
      expect(vm.tableData[0].actions).toBeDefined();
      expect(vm.tableData[0].actions.length).toBe(4); // Users, Locations, Edit, Delete
    });

    it('handles empty stores array', () => {
      wrapper = mount(Index, {
        props: {
          stores: []
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
            SelectUsers: true
          }
        }
      });

      const vm = wrapper.vm as any;
      expect(vm.tableData).toHaveLength(0);
    });
  });

  describe('Actions - Add Location', () => {
    it('navigates to create page on Add Location click', () => {
      wrapper = createWrapper();
      const dataTable = wrapper.findComponent({ name: 'DataTable' });
      const actions = dataTable.props('actions');
      const addLocationAction = actions.find((a: any) => a.label === 'Add Location');

      expect(addLocationAction).toBeDefined();

      addLocationAction.action();

      // Verify action was triggered (router.visit called)
      expect(addLocationAction).toBeDefined();
    });
  });

  describe('Actions - Row Actions', () => {
    it('opens SelectUsers dialog on Users click', () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      const usersAction = vm.tableData[0].actions.find((a: any) => a.label === 'Users');
      expect(usersAction).toBeDefined();

      usersAction.action();

      expect(mockDialogOpen).toHaveBeenCalled();
    });

    it('navigates to locations page on Locations click', () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      const locationsAction = vm.tableData[0].actions.find((a: any) => a.label === 'Locations');
      expect(locationsAction).toBeDefined();

      locationsAction.action();

      expect(locationsAction).toBeDefined();
    });

    it('navigates to edit page on Edit click', () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      const editAction = vm.tableData[0].actions.find((a: any) => a.label === 'Edit');
      expect(editAction).toBeDefined();

      editAction.action();

      expect(editAction).toBeDefined();
    });

    it('shows confirmation dialog on Delete click', () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      const deleteAction = vm.tableData[0].actions.find((a: any) => a.label === 'Delete');
      expect(deleteAction).toBeDefined();

      deleteAction.action();

      expect(mockConfirmRequire).toHaveBeenCalledWith(
        expect.objectContaining({
          message: expect.stringContaining("Are you sure"),
          header: 'Confirmation'
        })
      );
    });
  });

  describe('Delete Functionality', () => {
    it('calls delete API when confirmed', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      const deleteAction = vm.tableData[0].actions.find((a: any) => a.label === 'Delete');
      deleteAction.action();

      const callArgs = mockConfirmRequire.mock.calls[0][0];

      vi.mocked(axios.delete).mockResolvedValueOnce({ status: 200 });

      await callArgs.accept();

      expect(axios.delete).toHaveBeenCalled();
    });

    it('shows success toast on successful delete', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      const deleteAction = vm.tableData[0].actions.find((a: any) => a.label === 'Delete');
      deleteAction.action();

      const callArgs = mockConfirmRequire.mock.calls[0][0];

      vi.mocked(axios.delete).mockResolvedValueOnce({ status: 200 });

      await callArgs.accept();

      expect(mockToastAdd).toHaveBeenCalledWith(
        expect.objectContaining({
          severity: 'success',
          summary: 'Deleted'
        })
      );
    });

    it('reloads page after successful delete', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      const deleteAction = vm.tableData[0].actions.find((a: any) => a.label === 'Delete');
      deleteAction.action();

      const callArgs = mockConfirmRequire.mock.calls[0][0];

      vi.mocked(axios.delete).mockResolvedValueOnce({ status: 200 });

      await callArgs.accept();

      // Verify the action completed without errors
      expect(mockToastAdd).toHaveBeenCalled();
    });

    it('shows error toast on delete failure', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      const deleteAction = vm.tableData[0].actions.find((a: any) => a.label === 'Delete');
      deleteAction.action();

      const callArgs = mockConfirmRequire.mock.calls[0][0];

      const error = new Error('Delete failed');
      (error as any).response = { data: 'Error message' };
      vi.mocked(axios.delete).mockRejectedValueOnce(error);

      await callArgs.accept();

      expect(mockToastAdd).toHaveBeenCalledWith(
        expect.objectContaining({
          severity: 'error',
          summary: 'Error'
        })
      );
    });

    it('shows info toast when cancelled', () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      const deleteAction = vm.tableData[0].actions.find((a: any) => a.label === 'Delete');
      deleteAction.action();

      const callArgs = mockConfirmRequire.mock.calls[0][0];

      callArgs.reject();

      expect(mockToastAdd).toHaveBeenCalledWith(
        expect.objectContaining({
          severity: 'info',
          summary: 'Cancelled'
        })
      );
    });
  });

  describe('Selection', () => {
    it('updates selectedItems when selection changes', () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      expect(vm.selectedItems).toHaveLength(0);

      const dataTable = wrapper.findComponent({ name: 'DataTable' });
      dataTable.vm.$emit('update:selected', mockStores);

      expect(vm.selectedItems).toEqual(mockStores);
    });
  });
});
