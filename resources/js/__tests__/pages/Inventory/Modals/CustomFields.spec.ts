import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, VueWrapper } from '@vue/test-utils';
import CustomFields from '@/Pages/Inventory/Modals/CustomFields.vue';
import PrimeVue from 'primevue/config';
import ToastService from 'primevue/toastservice';
import ConfirmationService from 'primevue/confirmationservice';
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

// Global Route Mock
const routeMock = vi.fn((name, params) => {
  if (typeof params === 'object' && params !== null) {
    // If params is object like { id: 1 }, extract values or serialize
    const values = Object.values(params).join('/');
    return `/${name}/${values}`;
  }
  return `/${name}${params ? '/' + params : ''}`;
});
global.route = routeMock as any;

// Component Stubs defined outside to reference in findComponent
const CheckboxStub = {
  name: 'Checkbox',
  props: ['modelValue', 'value', 'binary', 'inputId'],
  emits: ['update:modelValue', 'change'],
  template: '<input type="checkbox" class="stub-checkbox" :checked="modelValue === value || modelValue === 1 || (Array.isArray(modelValue) && modelValue.includes(value))" @change="onChange" />',
  methods: {
    onChange(e: any) {
      if (this.binary) {
        this.$emit('update:modelValue', e.target.checked ? 1 : 0);
      } else {
        const val = this.modelValue ? [...this.modelValue] : [];
        if (e.target.checked) val.push(this.value);
        else {
          const idx = val.indexOf(this.value);
          if (idx > -1) val.splice(idx, 1);
        }
        this.$emit('update:modelValue', val);
      }
      this.$emit('change', e);
    }
  }
};

describe('Inventory/Modals/CustomFields.vue', () => {
  let wrapper: VueWrapper;
  let confirmRequireSpy: any;
  let toastAddSpy: any;

  // Mock Data
  const defaultHeaders = [
    { name: 'model', label: 'Model', type: 'text' },
    { name: 'manufacturer', label: 'Manufacturer', type: 'text' },
    { name: 'actions', label: 'Actions', type: 'text' } // Should be filtered out in render logic usually
  ];

  const customHeaders = [
    { id: 1, text: 'IMEI 2', value: 'imei_2', type: 'text', active: 1, user_id: 1, created_at: '', updated_at: '' },
    { id: 2, text: 'Condition', value: 'condition', type: 'text', active: 0, user_id: 1, created_at: '', updated_at: '' }
  ];

  const createWrapper = () => {
    return mount(CustomFields, {
      props: {
        headers: defaultHeaders,
        customHeaders: customHeaders,
        show: true // Ensure visible if it relied on v-show/if
      },
      global: {
        plugins: [
          createTestingPinia(),
          PrimeVue,
          ToastService,
          ConfirmationService
        ],
        stubs: {
          Dialog: {
            template: '<div class="dialog-stub" v-if="visible"><slot /><slot name="header"></slot><slot name="footer"></slot></div>',
            props: ['visible']
          },
          Button: true,
          Checkbox: CheckboxStub,
          InputText: true,
          Select: true,
          FloatLabel: { template: '<div><slot /></div>' }
        }
      }
    });
  };

  beforeEach(() => {
    vi.clearAllMocks();
    
    // Setup Axios Mocks
    vi.mocked(axios.post).mockResolvedValue({ data: { success: true } });
    vi.mocked(axios.put).mockResolvedValue({ data: { success: true } });
    vi.mocked(axios.delete).mockResolvedValue({ data: { success: true } });
  });

  describe('Rendering and Interaction', () => {
    it('renders default and custom headers', () => {
      wrapper = createWrapper();
      
      expect(wrapper.text()).toContain('Default Fields');
      expect(wrapper.text()).toContain('Model');
      expect(wrapper.text()).toContain('Manufacturer');
      
      expect(wrapper.text()).toContain('Custom Fields');
      expect(wrapper.text()).toContain('IMEI 2');
      expect(wrapper.text()).toContain('Condition');
    });

    it('emits updateHeaders when toggling a default field', async () => {
      wrapper = createWrapper();
      
      // Find checkbox for 'model' (first default header)
      // Use the component definition to find it
      const checkboxes = wrapper.findAllComponents(CheckboxStub);
      
      // We expect at least one checkbox (default headers + custom headers)
      expect(checkboxes.length).toBeGreaterThan(0);
      
      const modelCheckbox = checkboxes[0]; 
      
      // Simulate uncheck (it starts checked because we pass selectedHeaders based on props)
      await modelCheckbox.vm.$emit('update:modelValue', ['manufacturer']); // Remove model
      await modelCheckbox.vm.$emit('change', { target: { checked: false } }); // Trigger change event
      
      // Check emit
      expect(wrapper.emitted('updateHeaders')).toBeTruthy();
      const emittedArgs = wrapper.emitted('updateHeaders')!.slice(-1)[0][0] as any[];
      
      // Should NOT contain model
      expect(emittedArgs.find((h: any) => h.name === 'model')).toBeUndefined();
      // Should contain manufacturer
      expect(emittedArgs.find((h: any) => h.name === 'manufacturer')).toBeDefined();
    });

    it('emits updateHeaders when toggling a custom field', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      // Find custom field checkbox (Condition is inactive/0)
      // We can manipulate the ref directly to avoid stub complexity if preferred
      const conditionField = vm.selectedCustomHeaders.find((h: any) => h.value === 'condition');
      
      // Toggle to active
      conditionField.active = 1;
      await nextTick();
      
      // watchEffect should trigger emit
      expect(wrapper.emitted('updateHeaders')).toBeTruthy();
      const lastEmit = wrapper.emitted('updateHeaders')!.slice(-1)[0][0] as any[];
      
      // Should now contain condition
      expect(lastEmit.find((h: any) => h.name === 'condition')).toBeDefined();
    });
  });

  describe('CRUD Operations', () => {
    it('creates a new custom field', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      // Setup spies
      toastAddSpy = vi.spyOn(vm.toast, 'add');
      const { router } = await import('@inertiajs/vue3');
      
      // Open dialog
      vm.openDialog();
      expect(vm.dlgVisible).toBe(true);
      expect(vm.editingField).toBeNull();
      
      // Fill form
      vm.newName = 'New Field';
      vm.newType = 'text';
      
      // Submit
      await vm.submitForm();
      
      // Verify API
      expect(axios.post).toHaveBeenCalledWith(
        expect.stringContaining('customfields.store'),
        expect.objectContaining({ text: 'New Field', type: 'text' })
      );
      
      // Verify Success
      expect(toastAddSpy).toHaveBeenCalledWith(expect.objectContaining({ severity: 'success' }));
      expect(vm.dlgVisible).toBe(false);
      expect(router.reload).toHaveBeenCalled();
      
      // Verify Added to list
      expect(vm.selectedCustomHeaders.find((h: any) => h.text === 'New Field')).toBeDefined();
    });

    it('updates an existing custom field', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      // Get existing field
      const field = customHeaders[0]; // IMEI 2
      
      // Open dialog for edit
      vm.openDialog({ name: field.value, label: field.text, type: field.type }, field.id);
      
      expect(vm.dlgVisible).toBe(true);
      expect(vm.editingField).not.toBeNull();
      expect(vm.newName).toBe('IMEI 2');
      
      // Change name
      vm.newName = 'IMEI 2 Updated';
      
      // Submit
      await vm.submitForm();
      
      // Verify API
      expect(axios.put).toHaveBeenCalledWith(
        expect.stringContaining(`customfields.update/${field.id}`),
        expect.objectContaining({ text: 'IMEI 2 Updated' })
      );
    });

    it('deletes a custom field', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      const fieldToDelete = customHeaders[0]; // IMEI 2
      
      // Setup Confirm Mock
      vi.spyOn(vm.confirm, 'require').mockImplementation((opts: any) => {
        opts.accept();
      });
      
      // Trigger delete
      vm.deleteCustomField(fieldToDelete);
      
      // Verify API
      // Since delete is async inside accept callback, we might need to wait
      await new Promise(process.nextTick);
      
      expect(axios.delete).toHaveBeenCalledWith(expect.stringContaining(`customfields.destroy/${fieldToDelete.id}`));
      
      // Verify Removed from list
      // Note: Since props are reactive but local ref is a copy, it updates local ref
      expect(vm.selectedCustomHeaders.find((h: any) => h.value === fieldToDelete.value)).toBeUndefined();
    });
  });
});
