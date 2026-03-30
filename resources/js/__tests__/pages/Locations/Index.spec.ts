import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, VueWrapper } from '@vue/test-utils';
import Index from '@/Pages/Locations/Index.vue';
import PrimeVue from 'primevue/config';
import ToastService from 'primevue/toastservice';
import ConfirmationService from 'primevue/confirmationservice';
import DialogService from 'primevue/dialogservice';
import axios from 'axios';

vi.mock('axios');

vi.mock('@inertiajs/vue3', () => {
  return {
    router: {
      reload: vi.fn(),
      visit: vi.fn(),
    },
  };
});

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

const MockDataTable = {
  name: 'DataTable',
  props: ['items', 'headers', 'actions', 'title'],
  template: '<div><slot></slot></div>',
  emits: ['update:selection', 'update:selected'],
};

const mockLocations = [
  {
    id: 1,
    name: 'Main Location',
    address: '123 Main St',
    store_id: 1,
  },
  {
    id: 2,
    name: 'Secondary Location',
    address: '456 Secondary Ave',
    store_id: 1,
  }
];

const mockStore = {
  id: 1,
  name: 'Test Store',
};

describe('Locations/Index.vue', () => {
  let wrapper: VueWrapper;

  const createWrapper = () => {
    return mount(Index, {
      props: {
        store: mockStore,
        locations: mockLocations
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
          CreateEdit: true,
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

      expect(dataTable.props('title')).toBe('Locations');
    });

    it('renders DataTable with location data', () => {
      wrapper = createWrapper();
      const dataTable = wrapper.findComponent({ name: 'DataTable' });

      expect(dataTable.props('items')).toHaveLength(2);
      expect(dataTable.props('items')[0].name).toBe('Main Location');
      expect(dataTable.props('items')[1].name).toBe('Secondary Location');
    });

    it('renders correct headers', () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      expect(vm.headers).toEqual([
        { label: 'Name', name: 'name', type: 'string' },
        { label: 'Address', name: 'address', type: 'string' },
        { label: 'Actions', name: 'actions', type: 'any' },
      ]);
    });

    it('handles empty locations array', () => {
      wrapper = mount(Index, {
        props: {
          store: mockStore,
          locations: []
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
            CreateEdit: true,
            SelectUsers: true
          }
        }
      });

      const vm = wrapper.vm as any;
      expect(vm.tableData).toHaveLength(0);
    });
  });

  describe('Data Parsing', () => {
    it('parses locations with actions', () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      expect(vm.tableData).toHaveLength(2);
      expect(vm.tableData[0].actions).toBeDefined();
      expect(vm.tableData[0].actions.length).toBe(3); // Users, Edit, Delete
    });
  });

  describe('Actions - Create Location', () => {
    it('opens CreateEdit dialog on Create Location click', () => {
      wrapper = createWrapper();
      const dataTable = wrapper.findComponent({ name: 'DataTable' });
      const actions = dataTable.props('actions');
      const createAction = actions.find((a: any) => a.label === 'Create location');

      expect(createAction).toBeDefined();

      createAction.action();

      expect(mockDialogOpen).toHaveBeenCalled();
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

    it('opens edit dialog on Edit click', () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      const editAction = vm.tableData[0].actions.find((a: any) => a.label === 'Edit');
      expect(editAction).toBeDefined();

      editAction.action();

      expect(mockDialogOpen).toHaveBeenCalled();
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

    it('does not show toast when cancelled (reject callback is empty)', () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      const deleteAction = vm.tableData[0].actions.find((a: any) => a.label === 'Delete');
      deleteAction.action();

      const callArgs = mockConfirmRequire.mock.calls[0][0];

      callArgs.reject();

      expect(mockToastAdd).not.toHaveBeenCalled();
    });
  });

  describe('Selection', () => {
    it('updates selectedItems when selection changes', () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      expect(vm.selectedItems).toHaveLength(0);

      const dataTable = wrapper.findComponent({ name: 'DataTable' });
      dataTable.vm.$emit('update:selected', mockLocations);

      expect(vm.selectedItems).toEqual(mockLocations);
    });
  });
});
