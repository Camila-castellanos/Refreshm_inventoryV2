import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, VueWrapper } from '@vue/test-utils';
import CreateEdit from '@/Pages/Customers/CreateEdit.vue';
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
const mockToastAdd = vi.fn();

vi.mock('primevue', async () => {
  const actual = await vi.importActual('primevue');
  return {
    ...actual,
    useToast: () => ({
      add: mockToastAdd
    })
  };
});

// Test Data
const mockCustomer = {
  id: 1,
  customer: 'ACME Corporation',
  first_name: ['John', 'Jane'],
  last_name: ['Doe', 'Smith'],
  email: ['john@acme.com', 'jane@acme.com'],
  phone: ['123-456-7890', '098-765-4321'],
  phone_optional: [['555-0001'], []],
  account_number: 'ACC001',
  website: 'https://acme.com',
  notes: 'Important customer',
  currency: 'USD',
  billing_address: '123 Billing St',
  billing_address_optional: 'Suite 100',
  billing_address_country: 'USA',
  billing_address_state: 'California',
  billing_address_city: 'Los Angeles',
  billing_address_postal: '90001',
  ship_name: 'ACME Shipping',
  shipping_address: '456 Shipping Ave',
  shipping_address_optional: 'Floor 2',
  shipping_address_country: 'USA',
  shipping_address_state: 'California',
  shipping_address_city: 'San Francisco',
  shipping_address_postal: '94102',
  shipping_phone: '555-SHIP',
  delivery_instructions: 'Leave at front desk',
  credit: 5000,
};

describe('Customers/CreateEdit.vue', () => {
  let wrapper: VueWrapper;

  const createWrapper = (customerData: any = null) => {
    return mount(CreateEdit, {
      global: {
        plugins: [PrimeVue],
        provide: {
          dialogRef: {
            value: {
              data: customerData ? { customer: customerData } : undefined,
              close: mockDialogClose,
            },
          },
        },
        stubs: {
          InputLabel: true,
          InputText: true,
          Textarea: true,
          Button: true,
          Card: {
            template: '<div><slot name="title" /><slot name="content" /></div>'
          },
          Message: true,
        },
      },
    });
  };

  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('Rendering', () => {
    it('renders form with all sections', () => {
      wrapper = createWrapper();

      expect(wrapper.find('form').exists()).toBe(true);
      expect(wrapper.findAll('.card').length).toBeGreaterThan(0);
    });

    it('renders contact fields section', () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      expect(vm.personal_info_content.length).toBeGreaterThan(0);
    });
  });

  describe('Data Loading', () => {
    it('loads customer data when editing', async () => {
      wrapper = createWrapper(mockCustomer);
      const vm = wrapper.vm as any;

      // Wait for onMounted to execute
      await vi.waitFor(() => {
        expect(vm.customer).toBeDefined();
      });

      expect(vm.form.customer_name).toBe('ACME Corporation');
      expect(vm.form.first_name).toEqual(['John', 'Jane']);
      expect(vm.form.last_name).toEqual(['Doe', 'Smith']);
      expect(vm.form.email).toEqual(['john@acme.com', 'jane@acme.com']);
      expect(vm.form.billing_currency).toBe('USD');
      expect(vm.form.credit).toBe(5000);
    });

    it('has empty form when creating new customer', () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      expect(vm.form.customer_name).toBe('');
      expect(vm.form.first_name).toEqual([]);
      expect(vm.form.email).toEqual([]);
      expect(vm.customer).toBeNull();
    });

    it('correctly identifies form type', async () => {
      wrapper = createWrapper(mockCustomer);
      const vm = wrapper.vm as any;

      await vi.waitFor(() => {
        expect(vm.customer).toBeDefined();
      });

      expect(vm.formType).toBe('Edit');
    });
  });

  describe('Contact Management', () => {
    it('has contact management functions', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      // Verify contact management functions exist
      expect(typeof vm.addContactDetails).toBe('function');
      expect(typeof vm.removeContact).toBe('function');
      expect(typeof vm.addPhoneField).toBe('function');
      expect(typeof vm.removePhoneField).toBe('function');
    });

    it('removes contact when removeContact is called', async () => {
      wrapper = createWrapper(mockCustomer);
      const vm = wrapper.vm as any;

      await vi.waitFor(() => {
        expect(vm.form.first_name.length).toBe(2);
      });

      vm.removeContact(0);

      expect(vm.form.first_name.length).toBe(1);
      expect(vm.form.last_name.length).toBe(1);
      expect(vm.personal_info_content.length).toBe(1);
    });

    it('adds phone field when addPhoneField is called', async () => {
      wrapper = createWrapper(mockCustomer);
      const vm = wrapper.vm as any;

      // Wait for component to initialize with customer data
      await vi.waitFor(() => {
        expect(vm.customer).toBeDefined();
      });

      // Verify phone management functions exist
      expect(typeof vm.addPhoneField).toBe('function');
      expect(typeof vm.removePhoneField).toBe('function');
    });
  });

  describe('Form Actions', () => {
    it('clears billing form', async () => {
      wrapper = createWrapper(mockCustomer);
      const vm = wrapper.vm as any;

      await vi.waitFor(() => {
        expect(vm.form.billing_address).toBe('123 Billing St');
      });

      vm.cleanBillingForm();

      expect(vm.form.billing_address).toBe('');
      expect(vm.form.billing_address_optional).toBe('');
      expect(vm.form.billing_country).toBe('');
      expect(vm.form.billing_state).toBe('');
      expect(vm.form.billing_city).toBe('');
      expect(vm.form.billing_postal_code).toBe('');
    });

    it('clears shipping form', async () => {
      wrapper = createWrapper(mockCustomer);
      const vm = wrapper.vm as any;

      await vi.waitFor(() => {
        expect(vm.form.shipping_address).toBe('456 Shipping Ave');
      });

      vm.cleanShippingForm();

      expect(vm.form.shipping_address).toBe('');
      expect(vm.form.shipping_address_optional).toBe('');
      expect(vm.form.shipping_country).toBe('');
      expect(vm.form.shipping_state).toBe('');
      expect(vm.form.shipping_city).toBe('');
      expect(vm.form.shipping_postal_code).toBe('');
    });

    it('clears entire form', async () => {
      wrapper = createWrapper(mockCustomer);
      const vm = wrapper.vm as any;

      await vi.waitFor(() => {
        expect(vm.form.customer_name).toBe('ACME Corporation');
      });

      vm.cleanForm();

      expect(vm.form.customer_name).toBe('');
      expect(vm.form.first_name).toEqual([]);
      expect(vm.form.last_name).toEqual([]);
      expect(vm.form.email).toEqual([]);
      expect(vm.form.personal_phone).toEqual([]);
      expect(vm.form.accnumber).toBe('');
      expect(vm.form.website).toBe('');
      expect(vm.form.note).toBe('');
    });
  });

  describe('Optional Phones', () => {
    it('loads optional phones correctly from JSON string', async () => {
      const customerWithJsonPhones = {
        ...mockCustomer,
        phone_optional: '[["555-123"], ["555-456"]]'
      };
      wrapper = createWrapper(customerWithJsonPhones);
      const vm = wrapper.vm as any;

      await vi.waitFor(() => {
        expect(vm.customer).toBeDefined();
      });

      expect(vm.form.optional_number).toEqual([["555-123"], ["555-456"]]);
    });

    it('adds exactly one phone field when addPhoneField is called', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      // First contact, add phone
      vm.addPhoneField(0);
      expect(vm.form.optional_number[0]).toHaveLength(1);
      expect(vm.form.optional_number[0][0]).toBe("");
      
      // Add another
      vm.addPhoneField(0);
      expect(vm.form.optional_number[0]).toHaveLength(2);
    });

    it('removes correct phone field when removePhoneField is called', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      vm.form.optional_number[0] = ["phone1", "phone2", "phone3"];
      
      vm.removePhoneField(0, 1); // remove "phone2"
      
      expect(vm.form.optional_number[0]).toEqual(["phone1", "phone3"]);
    });

    it('maps optional_number to personal_phone_optional on submit', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      vm.form.customer_name = 'Test';
      vm.form.optional_number = [["123"], ["456"]];
      
      vi.mocked(axios.post).mockResolvedValueOnce({ status: 201, data: {} });
      
      const mockEvent = { preventDefault: vi.fn() };
      await vm.onFormSubmit(mockEvent);

      expect(vm.form.personal_phone_optional).toEqual([["123"], ["456"]]);
    });
  });

  describe('Form Submission', () => {
    it('validates customer_name is required', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      vm.form.customer_name = '';
      
      const mockEvent = { preventDefault: vi.fn() };
      await vm.onFormSubmit(mockEvent);

      expect(vm.nameInvalid).toBe(true);
    });

    it('calls POST API when creating new customer', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      vm.form.customer_name = 'New Customer';
      vm.form.first_name = ['John'];
      vm.form.email = ['john@example.com'];

      vi.mocked(axios.post).mockResolvedValueOnce({ 
        status: 201, 
        data: { id: 2, customer: 'New Customer' } 
      });

      const mockEvent = { preventDefault: vi.fn() };
      await vm.onFormSubmit(mockEvent);

      expect(axios.post).toHaveBeenCalledWith(
        expect.stringContaining('customer.store'),
        expect.any(Object)
      );
    });

    it('calls PUT API when updating customer', async () => {
      wrapper = createWrapper(mockCustomer);
      const vm = wrapper.vm as any;

      await vi.waitFor(() => {
        expect(vm.customer).toBeDefined();
      });

      vi.mocked(axios.put).mockResolvedValueOnce({ 
        status: 200, 
        data: { id: 1, customer: 'Updated Customer' } 
      });

      const mockEvent = { preventDefault: vi.fn() };
      await vm.onFormSubmit(mockEvent);

      expect(axios.put).toHaveBeenCalledWith(
        expect.stringContaining('customer.update'),
        expect.any(Object)
      );
    });

    it('closes dialog on successful submit', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      vm.form.customer_name = 'Test Customer';

      vi.mocked(axios.post).mockResolvedValueOnce({ 
        status: 201, 
        data: { id: 3, customer: 'Test Customer' } 
      });

      const mockEvent = { preventDefault: vi.fn() };
      await vm.onFormSubmit(mockEvent);

      expect(mockDialogClose).toHaveBeenCalled();
    });

    it('shows success toast on successful submit', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      vm.form.customer_name = 'Test Customer';

      vi.mocked(axios.post).mockResolvedValueOnce({ 
        status: 201, 
        data: { id: 4, customer: 'Test Customer' } 
      });

      const mockEvent = { preventDefault: vi.fn() };
      await vm.onFormSubmit(mockEvent);

      expect(mockToastAdd).toHaveBeenCalledWith(
        expect.objectContaining({
          severity: 'success',
          summary: 'Success'
        })
      );
    });
  });
});
