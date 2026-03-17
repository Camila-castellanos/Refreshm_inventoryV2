import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { mount, VueWrapper } from '@vue/test-utils';
import InventoryList from '@/Pages/PublicInventory/InventoryList.vue';
import PrimeVue from 'primevue/config';
import ToastService from 'primevue/toastservice';
import DialogService from 'primevue/dialogservice';
import axios from 'axios';

// Mock ziggy route global function
(global as any).route = vi.fn().mockReturnValue('/mock-route');

// Mock child components to isolate testing logic to InventoryList
vi.mock('@/Components/GenericTabs.vue', () => ({
  default: {
    name: 'GenericTabs',
    template: '<div><slot></slot></div>',
    props: ['staticTabs', 'customTabs', 'modelValue'],
  }
}));

vi.mock('@/Components/ExchangeRateToggle.vue', () => ({
  default: {
    name: 'ExchangeRateToggle',
    template: '<button class="mock-exchange-toggle" @click="$emit(\'exchangeToggled\', true, 1.35, \'USD\')">Toggle</button>',
  }
}));

vi.mock('primevue/usetoast', () => ({
  useToast: () => ({
    add: vi.fn()
  })
}));

describe('Pages/PublicInventory/InventoryList.vue', () => {
  let wrapper: VueWrapper<any>;

  const mockItems = [
    { id: 1, manufacturer: 'Apple', model: 'iPhone 13', grade: 'A', issues: null, selling_price: 1000 },
    { id: 2, manufacturer: 'Samsung', model: 'Galaxy S21', grade: 'B', issues: 'Screen scratch', selling_price: 800 },
    { id: 3, manufacturer: 'Apple', model: 'iPhone 14', grade: 'A', issues: null, selling_price: 1200 },
  ];

  beforeEach(() => {
    vi.clearAllMocks();
    
    // Default Axios mocks
    vi.spyOn(axios, 'get').mockResolvedValue({
      data: {
        items: [
           { id: 4, manufacturer: 'Google', model: 'Pixel 6', grade: 'A', issues: null, selling_price: 700 }
        ]
      }
    });

    vi.spyOn(axios, 'post').mockImplementation((url) => {
      if (url === '/mock-route') {
        // If it's models fetch route
        return Promise.resolve({
          data: { models: ['iPhone 13', 'iPhone 14'] }
        });
      }
      return Promise.resolve({ data: { message: 'Success' }});
    });
  });

  afterEach(() => {
    if (wrapper) wrapper.unmount();
    vi.restoreAllMocks();
  });

  const createWrapper = () => {
    return mount(InventoryList, {
      props: {
        items: [...mockItems],
        shopName: 'Test Shop',
        shopSlug: 'test-shop',
        userTabs: [{ id: 10, name: 'Tab 1', order: 1 }]
      },
      global: {
        plugins: [PrimeVue, ToastService, DialogService],
        stubs: {
          Card: { template: '<div class="mock-card"><slot name="content"></slot></div>' },
          Tag: { template: '<span></span>' },
          Button: { template: '<button></button>' },
          IconField: { template: '<div><slot></slot></div>' },
          InputIcon: { template: '<span></span>' },
          InputText: { template: '<input />' },
          Checkbox: { template: '<input type="checkbox" />', props: ['modelValue', 'value'] },
          RadioButton: { template: '<input type="radio" />', props: ['modelValue', 'value'] },
          Dialog: { template: '<div><slot></slot></div>', props: ['visible'] },
          DataTable: { template: '<div><slot></slot></div>' },
          Column: { template: '<div></div>' },
          Message: { template: '<div></div>' },
          Textarea: { template: '<textarea></textarea>' },
          Dropdown: { template: '<div></div>' }
        }
      }
    });
  };

  it('renders all initial props items correctly without filters', () => {
    wrapper = createWrapper();
    const vm = wrapper.vm as any;
    expect(vm.processedItems.length).toBe(3);
    expect(vm.processedItems[0].selling_price).toBe(1000);
  });

  it('filters items based on manufacturer', async () => {
    wrapper = createWrapper();
    const vm = wrapper.vm as any;
    
    // Simulate checking the 'Apple' checkbox
    vm.filters.manufacturer = ['Apple'];
    
    // Since filters are deep watched and reactivity takes a moment, we use a simple getter check
    expect(vm.processedItems.length).toBe(2);
    expect(vm.processedItems[0].model).toBe('iPhone 13');
    expect(vm.processedItems[1].model).toBe('iPhone 14');
  });

  it('converts prices when exchange toggle event is emitted', async () => {
    wrapper = createWrapper();
    const vm = wrapper.vm as any;

    // Simulate the event from the child component (Active: true, Rate: 1.35, Currency: USD)
    vm.handleExchangeToggled(true, 1.35, 'USD');

    // Prices should now be converted (1000 / 1.35 = ~741)
    expect(vm.processedItems[0].selling_price).toBe(Math.round(1000 / 1.35));
    expect(vm.processedItems[1].selling_price).toBe(Math.round(800 / 1.35));
    expect(vm.processedItems[2].selling_price).toBe(Math.round(1200 / 1.35));
  });

  it('maintains currency conversion after switching tabs', async () => {
    wrapper = createWrapper();
    const vm = wrapper.vm as any;

    // 1. Activate Currency Exchange (USD, 1.35 rate)
    vm.handleExchangeToggled(true, 1.35, 'USD');

    // 2. Simulate Tab Click to fetch new items (mocked to return Pixel 6 for 700 CAD)
    await vm.onTabClick({ kind: 'custom', tab: { id: 10, name: 'Tab 1' } });

    // Expect items to be updated with the fetched ones
    expect(vm.selectedTabItems.length).toBe(1);
    expect(vm.selectedTabItems[0].model).toBe('Pixel 6');
    expect(vm.selectedTabItems[0].selling_price).toBe(700); // the raw fetched value

    // 3. Processed items should automatically have the conversion applied
    expect(vm.processedItems.length).toBe(1);
    expect(vm.processedItems[0].model).toBe('Pixel 6');
    // 700 CAD converted to USD with 1.35 rate = 519
    expect(vm.processedItems[0].selling_price).toBe(Math.round(700 / 1.35));
  });

  it('adds and removes items to/from selected list', async () => {
    wrapper = createWrapper();
    const vm = wrapper.vm as any;

    const item = vm.processedItems[0];
    
    // Add
    vm.addItem(item);
    expect(vm.selectedItems.length).toBe(1);
    expect(vm.selectedItems[0].id).toBe(1);
    expect(item.selected).toBe(true);

    // Remove
    vm.removeItem(item);
    expect(vm.selectedItems.length).toBe(0);
    expect(item.selected).toBeUndefined();
  });
});
