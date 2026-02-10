import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, VueWrapper } from '@vue/test-utils';
import CreateEdit from '@/Pages/Vendors/CreateEdit.vue';
import PrimeVue from 'primevue/config';
import axios from 'axios';

// Mocks
vi.mock('axios');
vi.mock('@inertiajs/vue3', () => ({
  router: {
    reload: vi.fn(),
  },
  useForm: (data: any) => ({
    ...data,
    processing: false,
    defaults: vi.fn(),
    reset: vi.fn(),
  }),
}));

vi.mock('primevue/usetoast', () => ({
  useToast: () => ({
    add: vi.fn(),
  }),
}));

const mockDialogClose = vi.fn();

// Test Data
const mockVendor = {
  id: 1,
  vendor: 'Tech Supplies Inc',
  first_name: 'John',
  last_name: 'Doe',
  email: 'john@techsupplies.com',
  phone: '123-456-7890',
  optional_phone: '098-765-4321',
  currency: 'USD',
  address: '123 Main St',
  country: 'USA',
  state: 'California',
  city: 'San Francisco',
  postal_code: '94102',
  website: 'https://techsupplies.com',
};

describe('Vendors/CreateEdit.vue', () => {
  let wrapper: VueWrapper;

  const createWrapper = (vendorData: any = null) => {
    return mount(CreateEdit, {
      global: {
        plugins: [PrimeVue],
        provide: {
          dialogRef: {
            value: {
              data: vendorData ? { vendor: vendorData } : undefined,
              close: mockDialogClose,
            },
          },
        },
        stubs: {
          InputText: true,
          Textarea: true,
          Dropdown: true,
          Button: true,
        },
      },
    });
  };

  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('Rendering', () => {
    it('renders form with all fields', () => {
      wrapper = createWrapper();

      expect(wrapper.find('form').exists()).toBe(true);
      expect(wrapper.find('h2').text()).toContain('Basic Information');
    });

    it('shows Edit title when formType is Edit', async () => {
      wrapper = createWrapper(mockVendor);
      const vm = wrapper.vm as any;
      
      // Manually set formType to Edit to test the title change
      vm.formType = 'Edit';
      await wrapper.vm.$nextTick();

      expect(wrapper.find('h2').text()).toContain('Edit Vendor Details');
    });
  });

  describe('Loading Data', () => {
    it('loads vendor data into form when editing', async () => {
      wrapper = createWrapper(mockVendor);
      const vm = wrapper.vm as any;

      // Wait for onMounted to execute
      await vi.waitFor(() => {
        expect(vm.vendor.id).toBe(1);
      });

      expect(vm.form.vendor).toBe('Tech Supplies Inc');
      expect(vm.form.first_name).toBe('John');
      expect(vm.form.last_name).toBe('Doe');
      expect(vm.form.email).toBe('john@techsupplies.com');
      expect(vm.form.currency).toBe('USD');
    });

    it('has empty form when creating new vendor', () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      expect(vm.form.vendor).toBe('');
      expect(vm.form.first_name).toBe('');
      expect(vm.form.email).toBe('');
    });
  });

  describe('Form Submission', () => {
    it('calls POST API when creating new vendor', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      // Fill form
      vm.form.vendor = 'New Vendor';
      vm.form.email = 'new@vendor.com';

      vi.mocked(axios.post).mockResolvedValueOnce({ status: 200 });

      await vm.submitForm();

      expect(axios.post).toHaveBeenCalledWith(
        expect.stringContaining('vendor.store'),
        expect.objectContaining({
          vendor: 'New Vendor',
          email: 'new@vendor.com',
        })
      );
    });

    it('calls PUT API when updating vendor', async () => {
      wrapper = createWrapper(mockVendor);
      const vm = wrapper.vm as any;

      // Wait for data to load
      await vi.waitFor(() => {
        expect(vm.vendor.id).toBe(1);
      });

      // Modify form
      vm.form.vendor = 'Updated Vendor Name';

      vi.mocked(axios.put).mockResolvedValueOnce({ status: 200 });

      await vm.submitForm();

      expect(axios.put).toHaveBeenCalledWith(
        expect.stringContaining('vendor.update'),
        expect.objectContaining({
          vendor: 'Updated Vendor Name',
          email: 'john@techsupplies.com',
        })
      );
    });

    it('closes dialog on successful submit', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      vm.form.vendor = 'Test Vendor';

      vi.mocked(axios.post).mockResolvedValueOnce({ status: 200 });

      await vm.submitForm();

      expect(mockDialogClose).toHaveBeenCalled();
    });

    it('reloads page after successful submit', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      vi.mocked(axios.post).mockResolvedValueOnce({ status: 200 });

      await vm.submitForm();

      const { router } = await import('@inertiajs/vue3');
      expect(router.reload).toHaveBeenCalled();
    });
  });
});
