import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { mount, VueWrapper } from '@vue/test-utils';
import AddItemsToSale from '@/Pages/Inventory/Modals/AddItemsToSale.vue';
import PrimeVue from 'primevue/config';
import axios from 'axios';
import { nextTick } from 'vue';

// Mocks
vi.mock('axios');

// Mock route helper
const routeMock = vi.fn((name) => `/${name}`);
global.route = routeMock as any;

// Stubs
const MockDropdown = {
  name: 'Dropdown',
  props: ['modelValue', 'options', 'loading', 'disabled'],
  emits: ['update:modelValue', 'filter'],
  template: `
    <div class="dropdown-stub">
      <div v-for="opt in options" :key="opt.value" 
           @click="$emit('update:modelValue', opt.value)"
           class="option">
        {{ opt.label }}
      </div>
      <input class="filter-input" @input="$emit('filter', { filter: $event.target.value })" />
    </div>
  `
};

describe('Inventory/Modals/AddItemsToSale.vue', () => {
  let wrapper: VueWrapper;

  // Mock Data
  const mockSales = [
    { sale_id: 101, date: '2024-01-15T10:00:00Z', customer: 'John Doe', total: 500 },
    { sale_id: 102, date: '2024-02-20T14:30:00Z', customer: 'Jane Smith', total: 1200 }
  ];

  const createWrapper = (props = { visible: true }) => {
    return mount(AddItemsToSale, {
      props,
      global: {
        plugins: [PrimeVue],
        stubs: {
          Dialog: {
            template: '<div v-if="visible"><slot /><slot name="footer"></slot></div>',
            props: ['visible']
          },
          Dropdown: MockDropdown,
          Button: true
        }
      }
    });
  };

  beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(axios.get).mockResolvedValue({ data: mockSales });
  });

  // Remove global fake timers setup
  // afterEach(() => { vi.useRealTimers(); });

  describe('Initialization and Data Loading', () => {
    it('fetches sales when opened', async () => {
      // Start hidden
      wrapper = createWrapper({ visible: false });
      await nextTick();
      
      // Trigger open
      await wrapper.setProps({ visible: true });
      
      // Wait for watcher and async fetch
      await nextTick();
      // Wait for promise resolution chain
      await new Promise(resolve => setImmediate(resolve));
      
      expect(axios.get).toHaveBeenCalledWith(
        expect.stringContaining('payments.simpleList'),
        expect.anything()
      );
      
      // Verify formatting
      const vm = wrapper.vm as any;
      expect(vm.options.length).toBe(2);
      expect(vm.options[0].label).toContain('John Doe');
    });

    it('handles API errors gracefully', async () => {
      vi.mocked(axios.get).mockRejectedValueOnce(new Error('Network Error'));
      
      wrapper = createWrapper({ visible: false });
      await nextTick();
      
      await wrapper.setProps({ visible: true });
      
      await nextTick();
      await new Promise(resolve => setImmediate(resolve));
      
      const vm = wrapper.vm as any;
      expect(vm.error).toBe(true);
      expect(wrapper.text()).toContain('Error loading sales');
    });
  });

  describe('Search and Filtering', () => {
    beforeEach(() => {
        vi.useFakeTimers();
    });
    
    afterEach(() => {
        vi.useRealTimers();
    });

    it('debounces search requests (300ms)', async () => {
      wrapper = createWrapper();
      await nextTick();
      
      // Clear initial call
      vi.mocked(axios.get).mockClear();
      
      const dropdown = wrapper.findComponent(MockDropdown);
      
      // Type 'inv'
      dropdown.vm.$emit('filter', { filter: 'inv' });
      
      // Should not call immediately
      expect(axios.get).not.toHaveBeenCalled();
      
      // Advance 200ms
      vi.advanceTimersByTime(200);
      expect(axios.get).not.toHaveBeenCalled();
      
      // Advance 100ms more (total 300ms)
      vi.advanceTimersByTime(100);
      
      expect(axios.get).toHaveBeenCalledWith(
        expect.stringContaining('payments.simpleList'),
        expect.objectContaining({ params: expect.objectContaining({ q: 'inv' }) })
      );
    });
  });

  describe('Selection and Submission', () => {
    it('emits "add" event with selected sale ID', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      await nextTick();
      await new Promise(process.nextTick);
      
      // Select Sale 101
      vm.selectedSale = 101;
      
      // Click Add
      const addButton = wrapper.findAllComponents({ name: 'Button' })
        .find(b => b.attributes('label') === 'Add');
        
      expect(addButton?.exists()).toBe(true);
      await addButton?.trigger('click');
      
      expect(wrapper.emitted('add')).toBeTruthy();
      expect(wrapper.emitted('add')![0]).toEqual([101]);
      
      // Should close dialog
      expect(wrapper.emitted('update:visible')![0]).toEqual([false]);
    });

    it('disables Add button if no sale selected', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      await nextTick();
      
      vm.selectedSale = null;
      await nextTick();
      
      const addButton = wrapper.findAllComponents({ name: 'Button' })
        .find(b => b.attributes('label') === 'Add');
        
      expect(addButton?.attributes('disabled')).toBeDefined();
    });
  });
});
