import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, VueWrapper } from '@vue/test-utils';
import StoragesAssign from '@/Pages/Storages/StoragesAssign/StoragesAssign.vue';
import PrimeVue from 'primevue/config';
import ToastService from 'primevue/toastservice';
import { createTestingPinia } from '@pinia/testing';
import axios from 'axios';
import { nextTick } from 'vue';

// Mocks
vi.mock('axios');
vi.mock('@inertiajs/vue3', () => ({
  router: {
    reload: vi.fn(),
  },
}));

// Mock fetchStorages
vi.mock('@/Pages/Storages/StoragesIndexData', () => ({
  fetchStorages: vi.fn(),
}));
import { fetchStorages } from '@/Pages/Storages/StoragesIndexData';

// Global Route Mock
const routeMock = vi.fn((name) => `/${name}`);
global.route = routeMock as any;
// Attach to window as the component uses (window as any).route
Object.defineProperty(window, 'route', {
  value: routeMock,
  writable: true
});

describe('Inventory/Storages/StoragesAssign/StoragesAssign.vue', () => {
  let wrapper: VueWrapper;
  let toastAddSpy: any;

  // Mock Data
  const mockStorages = [
    { id: 1, name: 'Main Warehouse', limit: 100, occupied_count: 10 }, // Plenty of space
    { id: 2, name: 'Back Room', limit: 10, occupied_count: 8 },        // 2 slots left
    { id: 3, name: 'Showcase', limit: 5, occupied_count: 5 }           // Full
  ];

  const itemsToMove = [
    { id: 101, name: 'Item 1' },
    { id: 102, name: 'Item 2' },
    { id: 103, name: 'Item 3' }
  ]; // 3 items

  const createWrapper = () => {
    return mount(StoragesAssign, {
      props: {
        items: itemsToMove
      },
      global: {
        plugins: [
          createTestingPinia(),
          PrimeVue,
          ToastService
        ],
        stubs: {
          Dialog: {
            template: '<div class="dialog-stub" v-if="visible"><slot /><slot name="footer"></slot></div>',
            props: ['visible']
          },
          Chip: {
            props: ['label', 'disabled'],
            template: '<div class="chip-stub" :class="{ disabled: disabled }" @click="$emit(\'click\')">{{ label }}</div>'
          }
        }
      }
    });
  };

  beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(fetchStorages).mockResolvedValue({ data: mockStorages });
    vi.mocked(axios.post).mockResolvedValue({ data: { success: true } });
  });

  describe('Initialization', () => {
    it('fetches storages when opened', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      await vm.openDialog();
      
      expect(fetchStorages).toHaveBeenCalled();
      expect(vm.visible).toBe(true);
      expect(vm.storages).toHaveLength(3);
    });
  });

  describe('Capacity Logic', () => {
    it('disables storages that cannot fit the items', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      await vm.openDialog();
      
      // Wait for fetch promise and reactivity
      await new Promise(process.nextTick); 
      await nextTick();
      await nextTick(); 
      
      // Check if storages are actually loaded in VM
      expect(vm.storages.length).toBe(3);
      
      // Find chips using the CSS class from the stub template
      const chips = wrapper.findAll('.chip-stub');
      
      expect(chips.length).toBe(3);
      
      // Storage 1
      expect(chips[0].classes()).not.toContain('disabled');
      // For DOM elements/stubs, we check attributes or props depending on how stub renders
      // The stub renders :class="{ disabled: disabled }", so checking class is correct.
      // Checking prop on a DOM wrapper might not work as expected, but attributes work.
      
      // Storage 2
      expect(chips[1].classes()).toContain('disabled');
      
      // Storage 3
      expect(chips[2].classes()).toContain('disabled');
    });

    it('allows selection of valid storages', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      await vm.openDialog();
      await new Promise(process.nextTick);
      await nextTick();
      await nextTick();
      
      // Verify chips render
      const chips = wrapper.findAll('.chip-stub');
      expect(chips.length).toBe(3);
      
      // Get the storage object from VM directly
      const storage1 = vm.storages.find((s: any) => s.id === 1);
      expect(storage1).toBeDefined();
      
      // Call logic directly to verify business logic independent of DOM event propagation quirks
      vm.toggleSelection(storage1);
      
      expect(vm.selectedStorage).toBe(1);
    });

    it('prevents selection of invalid storages via click', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      await vm.openDialog();
      await new Promise(process.nextTick);
      await nextTick();
      await nextTick();
      
      const chips = wrapper.findAll('.chip-stub');
      expect(chips.length).toBe(3);
      
      // Click Storage 2 (Invalid)
      await chips[1].trigger('click');
      
      expect(vm.selectedStorage).toBeNull();
    });
  });

  describe('Assignment Execution', () => {
    it('calls API and emits refresh on assignment', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      // Spy on emit and toast
      toastAddSpy = vi.spyOn(vm.toast, 'add');
      
      await vm.openDialog();
      await nextTick();
      
      // Select Storage 1
      vm.selectedStorage = 1;
      
      // Click Assign
      await vm.assignLocation();
      
      // Verify API Call
      expect(axios.post).toHaveBeenCalledWith(
        expect.stringContaining('items.assign'),
        expect.objectContaining({
          storage_id: 1,
          items: [101, 102, 103]
        })
      );
      
      // Verify Success
      expect(toastAddSpy).toHaveBeenCalledWith(expect.objectContaining({ severity: 'success' }));
      expect(wrapper.emitted('refreshTable')).toBeTruthy();
      expect(vm.visible).toBe(false);
    });

    it('handles API error', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      toastAddSpy = vi.spyOn(vm.toast, 'add');
      
      await vm.openDialog();
      vm.selectedStorage = 1;
      
      vi.mocked(axios.post).mockRejectedValueOnce(new Error('Server Error'));
      
      await vm.assignLocation();
      
      expect(toastAddSpy).toHaveBeenCalledWith(expect.objectContaining({ severity: 'error' }));
      expect(vm.visible).toBe(true); // Should stay open
    });
  });
});
