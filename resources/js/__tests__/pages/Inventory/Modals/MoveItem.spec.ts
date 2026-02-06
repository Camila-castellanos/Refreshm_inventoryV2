import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, VueWrapper } from '@vue/test-utils';
import MoveItem from '@/Pages/Inventory/Modals/MoveItem.vue';
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
const MockSelect = {
  name: 'Select',
  props: ['modelValue', 'options', 'optionLabel', 'optionValue'],
  emits: ['update:modelValue'],
  template: `
    <select 
      :value="modelValue" 
      @change="$emit('update:modelValue', $event.target.value)"
      class="stub-select"
    >
      <option v-for="opt in options" :key="opt[optionValue] || opt.id" :value="opt[optionValue] || opt.id">
        {{ opt[optionLabel] || opt.name }}
      </option>
    </select>
  `
};

describe('Inventory/Modals/MoveItem.vue', () => {
  let wrapper: VueWrapper;
  let dialogRefMock: any;
  let toastAddSpy: any;

  // Mock Data
  const mockItems = [
    { id: 101, name: 'Item 1' },
    { id: 102, name: 'Item 2' }
  ];

  const mockCustomTabs = [
    { id: 5, name: 'Repairs' },
    { id: 6, name: 'RMA' }
  ];

  const createWrapper = () => {
    // Reset dialog ref mock state for each test
    dialogRefMock = {
      value: {
        data: {
          items: mockItems,
          tabs: mockCustomTabs
        },
        close: vi.fn()
      }
    };

    return mount(MoveItem, {
      global: {
        plugins: [
          createTestingPinia(),
          PrimeVue,
          ToastService,
          DialogService
        ],
        stubs: {
          Select: MockSelect,
          Button: true,
          InputText: true
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
    vi.mocked(axios.put).mockResolvedValue({ data: { success: true } });
    vi.mocked(axios.post).mockResolvedValue({ data: { success: true } });
  });

  describe('Initialization', () => {
    it('initializes tabs with default and custom options', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      await nextTick(); // Wait for onMounted

      expect(vm.tabs).toHaveLength(4); // Active, Hold + 2 Custom
      
      const tabNames = vm.tabs.map((t: any) => t.name);
      expect(tabNames).toContain('Active Inventory');
      expect(tabNames).toContain('On Hold');
      expect(tabNames).toContain('Repairs');
    });
  });

  describe('Move to Active Inventory', () => {
    it('calls unhold API when Active Inventory is selected', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      await nextTick();

      // Spy on toast
      const toastSpy = vi.spyOn(vm.toast, 'add');

      // Select Active Inventory
      vm.selectedTab = 'Active Inventory';
      
      // Trigger move
      await vm.moveItems();

      expect(axios.put).toHaveBeenCalledWith(
        expect.stringContaining('items.unhold'),
        expect.objectContaining({
          data: mockItems
        })
      );

      expect(toastSpy).toHaveBeenCalledWith(expect.objectContaining({ severity: 'success' }));
      expect(dialogRefMock.value.close).toHaveBeenCalled();
    });
  });

  describe('Move to On Hold', () => {
    it('shows customer input and calls hold API', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      await nextTick();

      // Select On Hold
      vm.selectedTab = 'On Hold';
      await nextTick();

      // Verify input visibility
      const input = wrapper.find('input[type="text"]');
      expect(input.exists()).toBe(true);
      expect(input.attributes('placeholder')).toContain('Customer Name');

      // Enter customer name
      await input.setValue('John Doe');
      
      // Trigger move
      await vm.moveItems();

      expect(axios.put).toHaveBeenCalledWith(
        expect.stringContaining('items.hold'),
        expect.objectContaining({
          data: mockItems,
          customer: 'John Doe'
        })
      );
    });
  });

  describe('Move to Custom Tab', () => {
    it('calls move API for each item individually', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      await nextTick();

      // Select Custom Tab (ID 5: Repairs)
      vm.selectedTab = 5;
      
      // Trigger move
      await vm.moveItems();

      // Should call POST tab.move twice (once for each item)
      expect(axios.post).toHaveBeenCalledTimes(2);
      
      // Check first call
      expect(axios.post).toHaveBeenCalledWith(
        expect.stringContaining('tab.move'),
        expect.objectContaining({
          tab: 5,
          item: 101
        })
      );

      // Check second call
      expect(axios.post).toHaveBeenCalledWith(
        expect.stringContaining('tab.move'),
        expect.objectContaining({
          tab: 5,
          item: 102
        })
      );
    });
  });
});
