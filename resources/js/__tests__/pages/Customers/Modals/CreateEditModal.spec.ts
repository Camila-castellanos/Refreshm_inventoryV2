import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, VueWrapper } from '@vue/test-utils';
import CreateEditModal from '@/Pages/Customers/Modals/CreateEditModal.vue';
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

const mockDialogClose = vi.fn();

// Test Data
const mockProspect = {
  id: 1,
  first_name: 'John',
  last_name: 'Doe',
  email: 'john@example.com',
  company_name: 'Tech Corp',
  city: 'New York',
  state: 'NY',
  country: 'USA',
  address: '123 Main St',
  phone_number: '555-1234',
  contact_type: 'Buyer',
};

describe('Customers/Modals/CreateEditModal.vue', () => {
  let wrapper: VueWrapper;

  const createWrapper = (prospectData: any = null) => {
    return mount(CreateEditModal, {
      global: {
        plugins: [PrimeVue],
        provide: {
          dialogRef: {
            value: {
              data: prospectData ? { prospect: prospectData } : undefined,
              close: mockDialogClose,
            },
          },
        },
        stubs: {
          InputText: true,
          Select: true,
          Textarea: true,
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
      expect(wrapper.findAll('label').length).toBeGreaterThan(0);
    });

    it('renders contact type selector with options', () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      expect(vm.contactTypeOptions).toEqual([
        { label: 'Buyer', value: 'Buyer' },
        { label: 'Supplier', value: 'Supplier' },
      ]);
      expect(vm.form.contact_type).toBe('Buyer');
    });
  });

  describe('Data Loading', () => {
    it('loads prospect data when editing', async () => {
      wrapper = createWrapper(mockProspect);
      const vm = wrapper.vm as any;

      // Wait for onMounted to execute
      await vi.waitFor(() => {
        expect(vm.prospect.id).toBe(1);
      });

      expect(vm.form.first_name).toBe('John');
      expect(vm.form.last_name).toBe('Doe');
      expect(vm.form.email).toBe('john@example.com');
      expect(vm.form.company).toBe('Tech Corp');
      expect(vm.form.city).toBe('New York');
      expect(vm.form.state).toBe('NY');
      expect(vm.form.country).toBe('USA');
      expect(vm.form.address).toBe('123 Main St');
      expect(vm.form.phone).toBe('555-1234');
      expect(vm.form.contact_type).toBe('Buyer');
    });

    it('has empty form when creating new prospect', () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      // Form should have default values
      expect(vm.form.first_name).toBe('');
      expect(vm.form.last_name).toBe('');
      expect(vm.form.email).toBe('');
      expect(vm.form.company).toBe('');
      expect(vm.form.contact_type).toBe('Buyer');
    });
  });

  describe('Form Submission', () => {
    it('calls POST API when creating new prospect', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      // Fill form
      vm.form.first_name = 'Jane';
      vm.form.last_name = 'Smith';
      vm.form.email = 'jane@example.com';
      vm.form.company = 'New Company';
      vm.form.contact_type = 'Supplier';

      vi.mocked(axios.post).mockResolvedValueOnce({ status: 201 });

      await vm.submitForm();

      expect(axios.post).toHaveBeenCalledWith(
        expect.stringContaining('prospects.store'),
        expect.objectContaining({
          first_name: 'Jane',
          last_name: 'Smith',
          email: 'jane@example.com',
          company_name: 'New Company',
          contact_type: 'Supplier',
        })
      );

      const { router } = await import('@inertiajs/vue3');
      expect(router.reload).toHaveBeenCalled();
    });

    it('calls PUT API when updating prospect', async () => {
      wrapper = createWrapper(mockProspect);
      const vm = wrapper.vm as any;

      await vi.waitFor(() => {
        expect(vm.prospect.id).toBe(1);
      });

      // Modify form
      vm.form.first_name = 'Updated';
      vm.form.company = 'Updated Company';

      vi.mocked(axios.put).mockResolvedValueOnce({ status: 200 });

      await vm.submitForm();

      expect(axios.put).toHaveBeenCalledWith(
        expect.stringContaining('prospects.update'),
        expect.objectContaining({
          first_name: 'Updated',
          last_name: 'Doe',
          email: 'john@example.com',
          company_name: 'Updated Company',
          contact_type: 'Buyer',
        })
      );

      const { router } = await import('@inertiajs/vue3');
      expect(router.reload).toHaveBeenCalled();
    });

    it('reloads page after successful creation', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      vm.form.first_name = 'Test';
      vm.form.last_name = 'User';

      vi.mocked(axios.post).mockResolvedValueOnce({ status: 201 });

      await vm.submitForm();

      const { router } = await import('@inertiajs/vue3');
      expect(router.reload).toHaveBeenCalled();
    });

    it('handles API errors gracefully', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      vm.form.first_name = 'Test';

      const consoleError = vi.spyOn(console, 'error').mockImplementation(() => {});
      vi.mocked(axios.post).mockRejectedValueOnce(new Error('Network error'));

      await vm.submitForm();

      expect(consoleError).toHaveBeenCalled();
      consoleError.mockRestore();
    });
  });
});
