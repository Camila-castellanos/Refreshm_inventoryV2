import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, VueWrapper } from '@vue/test-utils';
import UpdateTimezoneForm from '@/Pages/Profile/Partials/UpdateTimezoneForm.vue';
import PrimeVue from 'primevue/config';
import axios from 'axios';

// Mocks
vi.mock('axios');

const mockToastAdd = vi.fn();

vi.mock('primevue', async () => {
  const actual = await vi.importActual('primevue');
  return {
    ...actual,
    useToast: () => ({
      add: mockToastAdd
    })
  };
});

describe('Profile/Partials/UpdateTimezoneForm.vue', () => {
  let wrapper: VueWrapper;

  const createWrapper = (timezone = 'UTC') => {
    return mount(UpdateTimezoneForm, {
      props: {
        timezone
      },
      global: {
        plugins: [PrimeVue],
        stubs: {
          Card: {
            template: '<div class="card"><slot name="content"/></div>'
          },
          Button: true,
          Select: {
            template: '<select><option v-for="opt in options" :value="opt.value">{{ opt.label }}</option></select>',
            props: ['options', 'optionLabel', 'optionValue', 'placeholder', 'modelValue']
          }
        }
      }
    });
  };

  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('Rendering', () => {
    it('renders timezone form', () => {
      wrapper = createWrapper();
      
      expect(wrapper.find('form').exists()).toBe(true);
      expect(wrapper.text()).toContain('Update Timezone');
      expect(wrapper.text()).toContain('Select your preferred timezone');
    });

    it('renders timezone selector', () => {
      wrapper = createWrapper();
      
      expect(wrapper.text()).toContain('Timezone');
    });

    it('displays all timezone options', () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      expect(vm.timezones.length).toBe(13);
      expect(vm.timezones).toContainEqual({ label: 'UTC', value: 'UTC' });
      expect(vm.timezones).toContainEqual({ label: 'America/New York', value: 'America/New_York' });
      expect(vm.timezones).toContainEqual({ label: 'Europe/London', value: 'Europe/London' });
    });
  });

  describe('Data Loading', () => {
    it('fetches current timezone on mount', async () => {
      vi.mocked(axios.get).mockResolvedValueOnce({ 
        data: { timezone: 'America/New_York' } 
      });
      
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      // Wait for onMounted
      await new Promise(resolve => setTimeout(resolve, 0));
      
      expect(axios.get).toHaveBeenCalled();
      expect(vm.form.timezone).toBe('America/New_York');
    });

    it('shows error on fetch failure', async () => {
      vi.mocked(axios.get).mockRejectedValueOnce(new Error('Network error'));
      
      wrapper = createWrapper();
      
      // Wait for onMounted
      await new Promise(resolve => setTimeout(resolve, 0));
      
      expect(mockToastAdd).toHaveBeenCalledWith(
        expect.objectContaining({
          severity: 'error',
          summary: 'Error'
        })
      );
    });
  });

  describe('Form Submission', () => {
    it('updates timezone successfully', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      vm.form.timezone = 'Europe/London';
      
      // Mock form.put
      const mockPut = vi.fn().mockImplementation((url, options) => {
        if (options.onSuccess) options.onSuccess();
      });
      vm.form.put = mockPut;
      
      await vm.updateTimezone();
      
      expect(mockPut).toHaveBeenCalled();
      expect(mockToastAdd).toHaveBeenCalledWith(
        expect.objectContaining({
          severity: 'success',
          summary: 'Success',
          detail: 'Timezone updated'
        })
      );
    });

    it('submits with correct timezone value', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      vm.form.timezone = 'Asia/Tokyo';
      
      const mockPut = vi.fn();
      vm.form.put = mockPut;
      
      await vm.updateTimezone();
      
      expect(vm.form._method).toBe('PUT');
      expect(vm.form.timezone).toBe('Asia/Tokyo');
    });
  });
});
