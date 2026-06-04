import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { mount, VueWrapper } from '@vue/test-utils';
import ItemsSell from '@/Pages/Inventory/Modals/ItemsSell.vue';
import PrimeVue from 'primevue/config';
import ToastService from 'primevue/toastservice';
import DialogService from 'primevue/dialogservice';
import { createTestingPinia } from '@pinia/testing';
import axios from 'axios';
import { nextTick } from 'vue';

// Mocks
vi.mock('axios');

// Mock route helper
const routeMock = vi.fn((name) => `/${name}`);
global.route = routeMock as any;

// Stubs
const MockDataTable = {
  name: 'DataTable',
  props: ['value'],
  template: `
    <div class="datatable-stub">
      <div v-for="(item, index) in value" :key="index" class="row">
        <!-- Render inputs for v-model binding tests -->
        <input class="selling-price-input" 
               :value="item.selling_price" 
               @input="item.selling_price = Number($event.target.value)" />
      </div>
      <slot></slot>
    </div>
  `
};

const MockDropdown = {
  name: 'Select',
  props: ['modelValue', 'options', 'optionLabel'],
  emits: ['update:modelValue', 'change'],
  template: `
    <select 
      :value="modelValue" 
      @change="$emit('update:modelValue', options[$event.target.selectedIndex]); $emit('change', options[$event.target.selectedIndex])">
      <option v-for="(opt, i) in options" :key="i" :value="opt">
        {{ opt[optionLabel] || opt }}
      </option>
    </select>
  `
};

describe('Inventory/Modals/ItemsSell.vue', () => {
  let wrapper: VueWrapper;
  let dialogRefMock: any;
  let toastAddSpy: any;

  // Mock Data
  const mockItems = [
    { id: 1, model: 'iPhone 13', selling_price: 500, cost: 300, isNew: false },
    { id: 2, model: 'Galaxy S21', selling_price: 400, cost: 250, isNew: false }
  ];

  const mockTaxes = [
    { id: 1, name: 'VAT', percentage: 10 },
    { id: 2, name: 'No Tax', percentage: 0 }
  ];

  const mockCustomers = [
    { id: 101, customer: 'John Doe', credit: 50 },
    { id: 102, customer: 'Jane Smith', credit: 0 }
  ];

  const createWrapper = () => {
    // Reset dialog ref mock state for each test
    dialogRefMock = {
      value: {
        data: {
          items: JSON.parse(JSON.stringify(mockItems)) // Deep copy
        },
        close: vi.fn()
      }
    };

    return mount(ItemsSell, {
      global: {
        plugins: [
          createTestingPinia(),
          PrimeVue,
          ToastService,
          DialogService
        ],
        stubs: {
          DataTable: MockDataTable,
          Column: true,
          Select: MockDropdown, // Use our mock dropdown for interaction
          DatePicker: true,
          Button: true,
          InputNumber: true,
          InputText: true,
          Textarea: true,
          CreditDialog: true
        },
        provide: {
          dialogRef: dialogRefMock
        }
      }
    });
  };

  beforeEach(() => {
    vi.clearAllMocks();
    
    // Setup Axios Mocks
    vi.mocked(axios.get).mockImplementation((url: string) => {
      if (url.includes('tax.list')) return Promise.resolve({ data: mockTaxes });
      if (url.includes('customer.list')) return Promise.resolve({ data: mockCustomers });
      return Promise.resolve({ data: [] });
    });

    vi.mocked(axios.post).mockResolvedValue({
      // SaleController::store response shape: {url, warnings[]}
      // (see openspec/changes/fix-payments-list-and-storage-flow-hardening)
      data: { url: 'http://example.com/sale/123/invoice.pdf', warnings: [] }
    });
  });

  describe('Initialization', () => {
    it('fetches taxes and customers on mount', async () => {
      wrapper = createWrapper();
      await nextTick(); // Wait for onMounted

      expect(axios.get).toHaveBeenCalledWith(expect.stringContaining('tax.list'));
      expect(axios.get).toHaveBeenCalledWith(expect.stringContaining('customer.list'));
    });

    it('initializes form with items passed via dialogRef', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      await nextTick();

      expect(vm.params.items).toHaveLength(2);
      expect(vm.params.items[0].model).toBe('iPhone 13');
    });
  });

  describe('Reactive Calculations', () => {
    it('calculates subtotal correctly', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      await nextTick();

      // 500 + 400
      expect(vm.subtotal).toBe(900);
    });

    it('updates total when tax is selected', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      await nextTick();

      // Select VAT (10%)
      // Simulate selection
      vm.form.tax = mockTaxes[0];
      await nextTick();

      // Subtotal: 900
      // Tax: 10% of 900 = 90
      // Total: 990
      expect(vm.taxAmount).toBe(90.00);
      expect(vm.total).toBe(990.00);
    });

    it('recalculates when item price changes', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      await nextTick();

      // Change first item price to 600
      vm.params.items[0].selling_price = 600;
      await nextTick();

      // Subtotal: 600 + 400 = 1000
      expect(vm.subtotal).toBe(1000);
    });
  });

  describe('Sale Submission', () => {
    it('submits unpaid sale (Save button)', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      await nextTick();

      // Setup minimal form data
      vm.form.customer = mockCustomers[0];
      vm.form.payment_method = { name: 'Cash' };
      vm.form.payment_account = { name: 'Cash on hand' };

      // Spy on toast
      const toastSpy = vi.spyOn(vm.toast, 'add');

      // Call submit with isConfirmed = false (Unpaid)
      // Note: function signature is submitForm(e, isConfirmed)
      await vm.submitForm(new Event('submit'), false);

      expect(axios.post).toHaveBeenCalledWith(
        expect.stringContaining('sales.store'),
        expect.objectContaining({
          paid: 0, // Unpaid
          amount_paid: 0,
          balance_remaining: 900, // Full amount remaining
          total: 900
          // Customer is inside items array, not root
        })
      );

      expect(toastSpy).toHaveBeenCalledWith(expect.objectContaining({ severity: 'success' }));
      expect(dialogRefMock.value.close).toHaveBeenCalledWith({ sold: true });
    });

    it('submits paid sale (Confirm button)', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      await nextTick();

      // Call submit with isConfirmed = true (Paid)
      await vm.submitForm(new Event('submit'), true);

      expect(axios.post).toHaveBeenCalledWith(
        expect.stringContaining('sales.store'),
        expect.objectContaining({
          paid: 1, // Paid
          amount_paid: 900, // Full amount paid
          balance_remaining: 0
        })
      );
    });

    it('validates empty items list', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      await nextTick();

      // Empty items
      vm.params.items = [];

      const alertSpy = vi.spyOn(window, 'alert').mockImplementation(() => {});

      await vm.submitForm(new Event('submit'), true);

      expect(alertSpy).toHaveBeenCalledWith(expect.stringContaining('No items'));
      expect(axios.post).not.toHaveBeenCalled();
    });
  });

  describe('New row template (Bug D)', () => {
    it('new row position field is null, not an empty string', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      await nextTick();

      // Call the same addNewRow the UI uses; the template is spread in.
      vm.addNewRow();
      await nextTick();

      const newRow = vm.params.items[vm.params.items.length - 1];
      expect(newRow.isNew).toBe(true);
      // Contract: payload position must be null so backend `numeric|nullable`
      // validation (SaleForm.php:44) accepts it. Empty string fails the rule.
      expect(newRow.position).toBeNull();
      expect(newRow.position).not.toBe('');
      expect(newRow.storage_id).toBeNull();
    });
  });
});
