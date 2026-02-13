import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, VueWrapper } from '@vue/test-utils';
import Payments from '@/Pages/Accounting/Payments.vue';
import PrimeVue from 'primevue/config';
import ToastService from 'primevue/toastservice';
import ConfirmationService from 'primevue/confirmationservice';
import DialogService from 'primevue/dialogservice';
import axios from 'axios';
import { nextTick } from 'vue';

// Mocks
vi.mock('axios');

const inertiaMocks = vi.hoisted(() => ({
  reload: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({
  router: {
    reload: inertiaMocks.reload,
  }
}));

// Mock window functions
const mockOpen = vi.fn();
const mockURL = {
  createObjectURL: vi.fn(() => 'blob:url'),
  revokeObjectURL: vi.fn(),
};
global.window.open = mockOpen;
global.URL.createObjectURL = mockURL.createObjectURL;
global.URL.revokeObjectURL = mockURL.revokeObjectURL;

// Mock PrimeVue services (module level)
const dialogOpenMock = vi.fn();
const confirmRequireMock = vi.fn();

vi.mock('primevue', async () => {
  const actual: any = await vi.importActual('primevue');
  return {
    ...actual,
    useDialog: () => ({ open: dialogOpenMock }),
    useConfirm: () => ({ require: confirmRequireMock }),
    useToast: () => ({ add: vi.fn() })
  };
});

vi.mock('primevue/useconfirm', () => ({
  useConfirm: () => ({ require: confirmRequireMock })
}));

vi.mock('primevue/usedialog', () => ({
  useDialog: () => ({ open: dialogOpenMock })
}));

vi.mock('primevue/usetoast', () => ({
  useToast: () => ({ add: vi.fn() })
}));

const routeMock = vi.fn((name, params) => {
  if (params) return `/${name}/${params}`;
  return `/${name}`;
});
global.route = routeMock as any;

// Stubs
const MockDataTable = {
  name: 'DataTable',
  props: ['items', 'headers', 'actions'],
  template: `
    <div class="datatable-stub">
      <div v-for="item in items" :key="item.id" class="row">
        <!-- Render actions for each item to test row actions -->
        <button 
          v-for="action in item.actions" 
          :key="action.label"
          @click="action.action()"
          :class="'action-' + action.label.replace(/ /g, '-').toLowerCase()"
        >
          {{ action.label }}
        </button>
      </div>
      <slot></slot>
    </div>
  `
};

const MockDatePicker = {
  name: 'DatePicker',
  props: ['modelValue'],
  emits: ['update:modelValue'],
  template: '<input class="date-picker-stub" :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />'
};

const MockAccountingTabs = { template: '<div><slot /></div>' };
const MockTabs = { template: '<div><slot /></div>' };
const MockTabList = { template: '<div><slot /></div>' };
const MockTab = { template: '<div></div>' };

describe('Accounting/Payments.vue (Invoices)', () => {
  let wrapper: VueWrapper;

  const mockItems = [
    { id: 1, customer: 'John Doe', total: 500, status: 'Paid', sale_id: 101, payments: [], customer_email: [] },
    { id: 2, customer: 'Jane Smith', total: 1200, status: 'Unpaid', sale_id: 102, payments: [], customer_email: [] }
  ];

  const createWrapper = (propsOverride = {}) => {
    return mount(Payments, {
      props: {
        items: mockItems,
        data_status: 'all',
        data_range: { start: null, end: null },
        email_templates: [],
        ...propsOverride
      },
      global: {
        plugins: [PrimeVue, ToastService, ConfirmationService, DialogService],
        mocks: {
          route: routeMock
        },
        stubs: {
          AccountingTabs: MockAccountingTabs,
          DataTable: MockDataTable,
          DatePicker: MockDatePicker,
          Tabs: MockTabs,
          TabList: MockTabList,
          Tab: MockTab,
          AppLayout: { template: '<div><slot /></div>' }
        }
      }
    });
  };

  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('Rendering', () => {
    it('renders invoice table', () => {
      wrapper = createWrapper();
      expect(wrapper.findComponent(MockDataTable).exists()).toBe(true);
    });
  });

  describe('Date Filtering', () => {
    it('initializes date range from props', () => {
      wrapper = createWrapper({
        items: [],
        data_range: { start: '2024-01-01', end: '2024-01-31' }
      });

      const vm = wrapper.vm as any;
      expect(vm.dateRange).toHaveLength(2);
      expect(vm.dateRange[0]).toBeInstanceOf(Date);
    });

    it('reloads page with date params when range selected', async () => {
      wrapper = createWrapper();
      
      const start = new Date('2024-05-01T12:00:00');
      const end = new Date('2024-05-15T12:00:00');
      const newRange = [start, end];
      
      const datePicker = wrapper.findComponent(MockDatePicker);
      datePicker.vm.$emit('update:modelValue', newRange);
      
      await nextTick();
      
      expect(inertiaMocks.reload).toHaveBeenCalledWith(
        expect.objectContaining({
          data: expect.objectContaining({
            start_date: '2024-05-01',
            end_date: '2024-05-15'
          })
        })
      );
    });
  });

  describe('Invoice Actions', () => {
    it('opens invoice in new tab (View)', async () => {
      wrapper = createWrapper();
      await nextTick();
      
      // Find view invoice button for first item (ID 1)
      const btn = wrapper.find('.action-invoice');
      await btn.trigger('click');
      
      expect(window.open).toHaveBeenCalledWith(
        expect.stringContaining('reports.payments.invoice/1'), 
        '_blank'
      );
    });

    it('downloads invoice as PDF', async () => {
      wrapper = createWrapper();
      await nextTick();
      
      // Mock DOM
      const linkSpy = { click: vi.fn(), remove: vi.fn() };
      const createElementSpy = vi.spyOn(document, 'createElement').mockReturnValue(linkSpy as any);
      const appendSpy = vi.spyOn(document.body, 'appendChild').mockImplementation(() => null as any);
      
      vi.mocked(axios.get).mockResolvedValue({ 
        data: 'pdf-blob', 
        headers: { 'content-disposition': 'filename="inv-101.pdf"' } 
      });
      
      const btn = wrapper.find('.action-download-invoice');
      await btn.trigger('click');
      
      // Wait for async download logic
      await new Promise(process.nextTick);
      
      expect(axios.get).toHaveBeenCalledWith(
        expect.stringContaining('reports.payments.invoice/1'),
        expect.objectContaining({ responseType: 'blob' })
      );
      
      expect(linkSpy.click).toHaveBeenCalled();
      
      createElementSpy.mockRestore();
      appendSpy.mockRestore();
    });
  });

  describe('Record/View Payments', () => {
    it('opens ShowPayments in view-only mode for Paid items', async () => {
      // Item 1 is Paid
      wrapper = createWrapper();
      await nextTick();
      
      const buttons = wrapper.findAll('button');
      const btn = buttons.find(b => b.text() === 'View Payments');
      expect(btn).toBeDefined();
      
      await btn?.trigger('click');
      
      expect(dialogOpenMock).toHaveBeenCalledWith(
        expect.anything(),
        expect.objectContaining({
          data: expect.objectContaining({ view: 'view' }) 
        })
      );
    });

    it('opens ShowPayments in record mode for Unpaid items', async () => {
      // Item 2 is Unpaid
      wrapper = createWrapper();
      await nextTick();
      
      const buttons = wrapper.findAll('button');
      const btn = buttons.find(b => b.text() === 'Record / View Payments');
      expect(btn).toBeDefined();
      
      await btn?.trigger('click');
      
      expect(dialogOpenMock).toHaveBeenCalledWith(
        expect.anything(),
        expect.objectContaining({
          data: expect.objectContaining({ view: 'all' })
        })
      );
    });
  });

  describe('Edit Sale', () => {
    it('opens SaleEdit modal', async () => {
      wrapper = createWrapper();
      await nextTick();
      
      const buttons = wrapper.findAll('button');
      const btn = buttons.find(b => b.text() === 'Edit');
      expect(btn).toBeDefined();
      
      await btn?.trigger('click');
      
      expect(dialogOpenMock).toHaveBeenCalledWith(
        expect.anything(),
        expect.objectContaining({
          props: expect.objectContaining({ header: 'Edit sale' })
        })
      );
    });
  });

  describe('Delete and Return', () => {
    it('confirms and calls delete API', async () => {
      wrapper = createWrapper();
      await nextTick();
      
      const buttons = wrapper.findAll('button');
      const btn = buttons.find(b => b.text() === 'Delete and Return');
      expect(btn).toBeDefined();
      
      await btn?.trigger('click');
      
      // Force next tick to allow dialog to open
      await nextTick();
      
      expect(confirmRequireMock).toHaveBeenCalled();
      
      // Simulate accept
      const acceptCallback = confirmRequireMock.mock.calls[0][0].accept;
      
      vi.mocked(axios.post).mockResolvedValue({ status: 200 });
      
      await acceptCallback();
      
      expect(axios.post).toHaveBeenCalledWith(
        expect.stringContaining('payments.delete'),
        expect.objectContaining({ invoices: [mockItems[0]] }) // Passing the item object in array
      );
      
      // Note: deleteAndReturn uses router.reload() on success
      await new Promise(process.nextTick);
      expect(inertiaMocks.reload).toHaveBeenCalled();
    });
  });
});
