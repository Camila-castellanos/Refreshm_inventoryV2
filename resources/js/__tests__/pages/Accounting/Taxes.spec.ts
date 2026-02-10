import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { mount, VueWrapper } from '@vue/test-utils';
import Taxes from '@/Pages/Accounting/Taxes.vue';
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

const routeMock = vi.fn((name, params) => `/${name}`);
global.route = routeMock as any;

// Stubs
const MockDataTable = {
  name: 'DataTable',
  props: ['items', 'headers', 'actions', 'selected'],
  emits: ['update:selected'],
  template: `
    <div class="datatable-stub">
      <div v-for="action in actions" :key="action.label">
        <button 
          @click="action.action()" 
          :disabled="action.disable && action.disable(selected || [])"
          class="action-btn"
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

describe('Accounting/Taxes.vue', () => {
  let wrapper: VueWrapper;

  const mockTaxes = [
    { id: 1, name: 'VAT', percentage: 10, collected: 100, paid: 50 },
    { id: 2, name: 'Sales Tax', percentage: 5, collected: 200, paid: 100 }
  ];

  const createWrapper = () => {
    return mount(Taxes, {
      props: {
        items: mockTaxes
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
          AppLayout: { template: '<div><slot /></div>' }
        }
      }
    });
  };

  beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(axios.post).mockResolvedValue({ data: { success: true } });
  });

  describe('Rendering', () => {
    it('formats taxes with percentage symbol', async () => {
      wrapper = createWrapper();
      await nextTick();
      
      const vm = wrapper.vm as any;
      expect(vm.tableData[0].percentage).toBe('10 %');
      expect(vm.tableData[1].percentage).toBe('5 %');
    });
  });

  describe('Date Filtering', () => {
    it('fetches date-wise data via axios when range selected', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      // Use noon to avoid timezone issues
      const start = new Date(2024, 4, 1, 12);
      const end = new Date(2024, 4, 31, 12);
      
      // Mock API response
      const filteredData = [
        { ...mockTaxes[0], collected: 500 } // Different value to verify update
      ];
      vi.mocked(axios.post).mockResolvedValue({ data: filteredData });
      
      const datePicker = wrapper.findComponent(MockDatePicker);
      datePicker.vm.$emit('update:modelValue', [start, end]);
      
      await nextTick();
      await new Promise(process.nextTick); // Wait for axios
      
      // Verify API call
      expect(axios.post).toHaveBeenCalledWith(
        expect.stringContaining('taxes.datewise'),
        expect.objectContaining({
          start: start,
          end: end
        })
      );
      
      // Verify Data Update
      expect(vm.tableData[0].collected).toBe(500);
    });
  });

  describe('CRUD Actions', () => {
    it('opens Add Tax dialog', async () => {
      wrapper = createWrapper();
      
      const buttons = wrapper.findAll('.action-btn');
      const addBtn = buttons.find(b => b.text() === 'Add new tax');
      
      await addBtn?.trigger('click');
      
      expect(dialogOpenMock).toHaveBeenCalledWith(
        expect.anything(),
        expect.objectContaining({ props: expect.objectContaining({ header: 'Add new tax' }) })
      );
    });

    it('opens Edit Tax dialog with selection', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      // Select tax
      vm.selectedTaxes = [mockTaxes[0]];
      await nextTick();
      
      const buttons = wrapper.findAll('.action-btn');
      const editBtn = buttons.find(b => b.text() === 'Edit Tax');
      
      // Ensure button is not disabled
      expect(editBtn?.attributes('disabled')).toBeUndefined();
      
      await editBtn?.trigger('click');
      
      expect(dialogOpenMock).toHaveBeenCalledWith(
        expect.anything(),
        expect.objectContaining({
          data: expect.objectContaining({ taxes: expect.arrayContaining([expect.objectContaining({ id: 1 })]) })
        })
      );
    });

    it('deletes selected taxes', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      // Select tax
      vm.selectedTaxes = [mockTaxes[0]];
      await nextTick();
      
      const buttons = wrapper.findAll('.action-btn');
      const deleteBtn = buttons.find(b => b.text() === 'Delete Taxes');
      
      await deleteBtn?.trigger('click');
      
      expect(confirmRequireMock).toHaveBeenCalled();
      
      // Simulate accept
      const acceptCallback = confirmRequireMock.mock.calls[0][0].accept;
      
      vi.mocked(axios.post).mockResolvedValue({ status: 200 });
      
      await acceptCallback();
      
      expect(axios.post).toHaveBeenCalledWith(
        expect.stringContaining('taxes.remove'),
        expect.objectContaining({ taxes: [mockTaxes[0]] })
      );
      
      // Verify reload
      await new Promise(process.nextTick);
      expect(inertiaMocks.reload).toHaveBeenCalled();
    });
  });
});
