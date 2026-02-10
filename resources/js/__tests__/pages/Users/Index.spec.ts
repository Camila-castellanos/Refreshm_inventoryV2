import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, VueWrapper } from '@vue/test-utils';
import Index from '@/Pages/Users/Index.vue';
import PrimeVue from 'primevue/config';
import ToastService from 'primevue/toastservice';
import ConfirmationService from 'primevue/confirmationservice';
import DialogService from 'primevue/dialogservice';
import axios from 'axios';

// Mocks
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
const mockUsers = [
  {
    id: 1,
    name: 'John Doe',
    email: 'john@example.com',
    role: 'Admin',
  },
  {
    id: 2,
    name: 'Jane Smith',
    email: 'jane@example.com',
    role: 'User',
  }
];

describe('Users/Index.vue', () => {
  let wrapper: VueWrapper;

  const createWrapper = () => {
    return mount(Index, {
      props: {
        users: mockUsers
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
          SelectRole: true,
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

      expect(dataTable.props('title')).toBe('Users');
    });

    it('renders DataTable with user data', () => {
      wrapper = createWrapper();
      const dataTable = wrapper.findComponent({ name: 'DataTable' });

      expect(dataTable.props('items')).toHaveLength(2);
      expect(dataTable.props('items')[0].name).toBe('John Doe');
      expect(dataTable.props('items')[1].name).toBe('Jane Smith');
    });

    it('renders correct headers', () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      expect(vm.headers).toEqual([
        { label: 'Name', name: 'name', type: 'string' },
        { label: 'Email', name: 'email', type: 'string' },
        { label: 'Role', name: 'role', type: 'string' },
        { label: 'Actions', name: 'actions', type: 'any' },
      ]);
    });
  });

  describe('Data Parsing', () => {
    it('parses users with actions', () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      expect(vm.tableData).toHaveLength(2);
      expect(vm.tableData[0].actions).toBeDefined();
      expect(vm.tableData[0].actions.length).toBe(3); // Change Role, Edit, Delete
    });

    it('handles empty users array', () => {
      wrapper = mount(Index, {
        props: {
          users: []
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
            SelectRole: true,
            SelectUsers: true
          }
        }
      });

      const vm = wrapper.vm as any;
      expect(vm.tableData).toHaveLength(0);
    });
  });

  describe('Actions - Create User', () => {
    it('opens CreateEdit modal on Create user click', () => {
      wrapper = createWrapper();
      const dataTable = wrapper.findComponent({ name: 'DataTable' });
      const actions = dataTable.props('actions');
      const createUserAction = actions.find((a: any) => a.label === 'Create user');

      expect(createUserAction).toBeDefined();

      createUserAction.action();

      expect(mockDialogOpen).toHaveBeenCalledWith(
        expect.anything(),
        expect.objectContaining({
          props: expect.objectContaining({
            header: 'Add user'
          })
        })
      );
    });
  });

  describe('Actions - Row Actions', () => {
    it('opens SelectRole dialog on Change Role click', () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      const changeRoleAction = vm.tableData[0].actions.find((a: any) => a.label === 'Change Role');
      expect(changeRoleAction).toBeDefined();

      changeRoleAction.action();

      expect(mockDialogOpen).toHaveBeenCalledWith(
        expect.anything(),
        expect.objectContaining({
          data: expect.objectContaining({
            id: 1
          }),
          props: expect.objectContaining({
            header: 'Select role'
          })
        })
      );
    });

    it('opens CreateEdit dialog on Edit click', () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      const editAction = vm.tableData[0].actions.find((a: any) => a.label === 'Edit');
      expect(editAction).toBeDefined();

      editAction.action();

      expect(mockDialogOpen).toHaveBeenCalledWith(
        expect.anything(),
        expect.objectContaining({
          data: expect.objectContaining({
            userEdit: expect.objectContaining({
              id: 1,
              name: 'John Doe'
            })
          }),
          props: expect.objectContaining({
            header: 'Edit user'
          })
        })
      );
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
      dataTable.vm.$emit('update:selected', mockUsers);

      expect(vm.selectedItems).toEqual(mockUsers);
    });
  });
});
