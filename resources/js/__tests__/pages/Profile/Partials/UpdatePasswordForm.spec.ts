import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, VueWrapper } from '@vue/test-utils';
import UpdatePasswordForm from '@/Pages/Profile/Partials/UpdatePasswordForm.vue';
import PrimeVue from 'primevue/config';

// Mocks
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

describe('Profile/Partials/UpdatePasswordForm.vue', () => {
  let wrapper: VueWrapper;

  const createWrapper = () => {
    return mount(UpdatePasswordForm, {
      global: {
        plugins: [PrimeVue],
        stubs: {
          Card: {
            template: '<div class="card"><slot name="content"/></div>'
          },
          Button: true,
          Password: {
            template: '<input />'
          }
        }
      }
    });
  };

  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('Rendering', () => {
    it('renders password update form', () => {
      wrapper = createWrapper();
      
      expect(wrapper.find('form').exists()).toBe(true);
      expect(wrapper.text()).toContain('Update Password');
      expect(wrapper.text()).toContain('Ensure your account is using a long, random password');
    });

    it('renders all password fields', () => {
      wrapper = createWrapper();
      
      expect(wrapper.text()).toContain('Current Password');
      expect(wrapper.text()).toContain('New Password');
      expect(wrapper.text()).toContain('Confirm Password');
    });
  });

  describe('Form Functionality', () => {
    it('initializes form with empty values', () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      expect(vm.form.current_password).toBe('');
      expect(vm.form.password).toBe('');
      expect(vm.form.password_confirmation).toBe('');
    });

    it('updates password successfully', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      vm.form.current_password = 'oldpassword123';
      vm.form.password = 'newpassword123';
      vm.form.password_confirmation = 'newpassword123';
      
      // Mock form.put
      const mockPut = vi.fn().mockImplementation((url, options) => {
        if (options.onSuccess) options.onSuccess();
      });
      vm.form.put = mockPut;
      
      await vm.updatePassword();
      
      expect(mockPut).toHaveBeenCalled();
      expect(mockToastAdd).toHaveBeenCalledWith(
        expect.objectContaining({
          severity: 'success',
          summary: 'Success',
          detail: 'Password updated successfully'
        })
      );
    });

    it('resets form on success', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      vm.form.current_password = 'oldpassword123';
      vm.form.password = 'newpassword123';
      vm.form.password_confirmation = 'newpassword123';
      
      const mockReset = vi.fn();
      vm.form.reset = mockReset;
      
      const mockPut = vi.fn().mockImplementation((url, options) => {
        if (options.onSuccess) options.onSuccess();
      });
      vm.form.put = mockPut;
      
      await vm.updatePassword();
      
      expect(mockReset).toHaveBeenCalled();
    });

    it('handles current password error', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      const mockFocus = vi.fn();
      vm.currentPasswordInput = { focus: mockFocus };
      
      vm.form.errors.current_password = 'Current password is incorrect';
      
      const mockPut = vi.fn().mockImplementation((url, options) => {
        if (options.onError) options.onError();
      });
      vm.form.put = mockPut;
      
      const mockReset = vi.fn();
      vm.form.reset = mockReset;
      
      await vm.updatePassword();
      
      expect(mockReset).toHaveBeenCalledWith('current_password');
      expect(mockFocus).toHaveBeenCalled();
    });

    it('handles new password error', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      const mockFocus = vi.fn();
      vm.passwordInput = { focus: mockFocus };
      
      vm.form.errors.password = 'Password must be at least 8 characters';
      
      const mockPut = vi.fn().mockImplementation((url, options) => {
        if (options.onError) options.onError();
      });
      vm.form.put = mockPut;
      
      const mockReset = vi.fn();
      vm.form.reset = mockReset;
      
      await vm.updatePassword();
      
      expect(mockReset).toHaveBeenCalledWith('password', 'password_confirmation');
      expect(mockFocus).toHaveBeenCalled();
    });
  });
});
