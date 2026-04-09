import { describe, it, expect, vi } from 'vitest';
import { mount, VueWrapper } from '@vue/test-utils';
import ExpenseBreakdownModal from '@/Components/ExpenseBreakdownModal.vue';
import PrimeVue from 'primevue/config';

// Mock formatting function if needed
const formatCurrency = (value: number) => `$${value.toFixed(2)}`;

describe('Components/ExpenseBreakdownModal.vue', () => {
  let wrapper: VueWrapper;

  const mockBreakdown = [
    { category: 'Utilities', total: 150.50 },
    { category: 'Rent', total: 1200.00 },
    { category: null, total: 50.00 }
  ];

  const createWrapper = (props = {}) => {
    return mount(ExpenseBreakdownModal, {
      props: {
        modelValue: true,
        breakdown: mockBreakdown,
        ...props
      },
      global: {
        plugins: [PrimeVue],
        stubs: {
          Dialog: {
            template: '<div v-if="visible" class="dialog-stub"><slot name="header" /><slot /></div>',
            props: ['visible']
          },
          DataTable: {
            name: 'DataTable',
            props: ['value'],
            template: '<div class="datatable-stub"><slot /></div>'
          },
          Column: {
            name: 'Column',
            props: ['field', 'header', 'body'],
            template: '<div class="column-stub">{{ field }}: {{ header }}</div>'
          },
          Button: {
            name: 'Button',
            template: '<button class="button-stub"><slot /></button>'
          }
        }
      }
    });
  };

  it('renders categories and totals when data is provided', async () => {
    wrapper = createWrapper();
    
    // Verifying it uses DataTable with the correct breakdown data
    const dataTable = wrapper.find('.datatable-stub');
    expect(dataTable.exists()).toBe(true);
    
    // Checking if the breakdown is correctly passed to the DataTable
    const dataTableComponent = wrapper.findComponent({ name: 'DataTable' });
    expect(dataTableComponent.props('value')).toEqual(mockBreakdown);
    
    // Check that we have columns for category and total
    const columns = wrapper.findAll('.column-stub');
    const categoryCol = columns.find(c => c.text().includes('category'));
    const totalCol = columns.find(c => c.text().includes('total'));
    
    expect(categoryCol).toBeDefined();
    expect(totalCol).toBeDefined();
  });

  it('shows empty state when data is empty', async () => {
    wrapper = createWrapper({ breakdown: [] });
    expect(wrapper.text()).toContain('No expenses found for this period');
  });

  it('is hidden when modelValue is false', async () => {
    wrapper = createWrapper({ modelValue: false });
    expect(wrapper.find('.dialog-stub').exists()).toBe(false);
  });
});
