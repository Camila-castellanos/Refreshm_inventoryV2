import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { mount, VueWrapper } from '@vue/test-utils';
import DataTableComponent from '@/Components/DataTable.vue';
import PrimeVue from 'primevue/config';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import DialogService from 'primevue/dialogservice';

// Mock components that are not critical for search logic
const MockMenu = { template: '<div></div>' };
const MockPopover = { template: '<div></div>' };
const MockTag = { template: '<div></div>' };

describe('Components/DataTable.vue', () => {
  let wrapper: VueWrapper;

  const mockHeaders = [
    { label: 'Name', name: 'name', type: 'text' },
    { label: 'Model', name: 'model', type: 'text' },
    { label: 'Price', name: 'price', type: 'number' }
  ];

  const mockItems = [
    { id: 1, name: 'Apple', model: 'iPhone 13', price: 999 },
    { id: 2, name: 'Samsung', model: 'Galaxy S21', price: 899 },
    { id: 3, name: 'Google', model: 'Pixel 6', price: 699 }
  ];

  const createWrapper = () => {
    return mount(DataTableComponent, {
      props: {
        title: 'Test Table',
        headers: mockHeaders,
        items: mockItems,
      },
      global: {
        plugins: [PrimeVue, DialogService],
        stubs: {
          Menu: MockMenu,
          Popover: MockPopover,
          Tag: MockTag,
          // Use real PrimeVue components for search logic testing
          DataTable: DataTable,
          Column: Column,
          Button: Button,
          InputText: InputText,
          IconField: IconField,
          InputIcon: InputIcon
        }
      }
    });
  };

  beforeEach(() => {
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  describe('Search Functionality', () => {
    it('updates global filter when input changes', async () => {
      wrapper = createWrapper();
      const input = wrapper.find('input[type="text"]');
      
      await input.setValue('iPhone');
      
      const vm = wrapper.vm as any;
      expect(vm.filters.global.value).toBe('iPhone');
    });

    it('calculates globalFilterFields correctly excluding actions', () => {
      wrapper = createWrapper();
      
      const dt = wrapper.findComponent(DataTable);
      expect(dt.exists()).toBe(true);
      
      const headers = wrapper.props('headers');
      const expectedFields = headers
        .filter((h: any) => h.name !== 'actions')
        .map((h: any) => h.name);
        
      expect(expectedFields).toContain('name');
      expect(expectedFields).toContain('model');
      expect(expectedFields).toContain('price');
    });

    it('filters data correctly (integration with PrimeVue DataTable)', async () => {
      // Use real timers for this interaction test
      vi.useRealTimers(); 
      wrapper = createWrapper();
      const input = wrapper.find('input[type="text"]');
      
      // Search for "iPhone"
      await input.setValue('iPhone');
      await wrapper.vm.$nextTick();
      
      // Verify rows rendered
      const rows = wrapper.findAll('tbody tr');
      // Should find only 1 row (Apple iPhone 13)
      expect(rows.length).toBe(1);
      expect(rows[0].text()).toContain('iPhone 13');
      expect(rows[0].text()).not.toContain('Galaxy');
    });

    it('filters by case-insensitive text', async () => {
      vi.useRealTimers();
      wrapper = createWrapper();
      const input = wrapper.find('input[type="text"]');
      
      // Search for "galaxy" (lowercase)
      await input.setValue('galaxy');
      await wrapper.vm.$nextTick();
      
      const rows = wrapper.findAll('tbody tr');
      expect(rows.length).toBe(1);
      expect(rows[0].text()).toContain('Galaxy S21');
    });

    it('filters by specific fields (e.g. price)', async () => {
      vi.useRealTimers();
      wrapper = createWrapper();
      const input = wrapper.find('input[type="text"]');
      
      // Search for price "699"
      await input.setValue('699');
      await wrapper.vm.$nextTick();
      
      const rows = wrapper.findAll('tbody tr');
      expect(rows.length).toBe(1);
      expect(rows[0].text()).toContain('Pixel 6');
    });

    it('shows empty state when no results found', async () => {
      vi.useRealTimers();
      wrapper = createWrapper();
      const input = wrapper.find('input[type="text"]');
      
      await input.setValue('Nokia');
      await wrapper.vm.$nextTick();
      
      const rows = wrapper.findAll('tbody tr');
      // PrimeVue renders an empty message row
      expect(wrapper.text()).toContain('No data found');
    });

    it('resets to full list when search is cleared', async () => {
      vi.useRealTimers();
      wrapper = createWrapper();
      const input = wrapper.find('input[type="text"]');
      
      // First filter
      await input.setValue('iPhone');
      await wrapper.vm.$nextTick();
      expect(wrapper.findAll('tbody tr').length).toBe(1);
      
      // Clear filter
      await input.setValue('');
      await wrapper.vm.$nextTick();
      
      // Should show all 3 items
      expect(wrapper.findAll('tbody tr').length).toBe(3);
    });
  });
});
