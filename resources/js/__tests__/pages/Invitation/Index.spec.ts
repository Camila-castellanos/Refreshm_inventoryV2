import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, VueWrapper } from '@vue/test-utils';
import Index from '@/Pages/Invitation/Index.vue';
import PrimeVue from 'primevue/config';
import ToastService from 'primevue/toastservice';
import Button from 'primevue/button';

vi.mock('@inertiajs/vue3', () => ({
  router: {
    post: vi.fn(),
  },
  route: vi.fn((name) => `/invitation`),
}));

describe('Invitation/Index.vue', () => {
  let wrapper: VueWrapper;

  const createWrapper = () => {
    return mount(Index, {
      global: {
        plugins: [
          PrimeVue,
          ToastService,
        ],
        components: {
          Button,
        }
      }
    });
  };

  beforeEach(() => {
    vi.clearAllMocks();
    vi.resetAllMocks();
  });

  describe('Rendering', () => {
    it('renders invitation message', () => {
      wrapper = createWrapper();
      const heading = wrapper.find('h1');
      
      expect(heading.exists()).toBe(true);
      expect(heading.text()).toContain("You've been invited to join");
    });

    it('renders Join button', () => {
      wrapper = createWrapper();
      const button = wrapper.findComponent(Button);
      
      expect(button.exists()).toBe(true);
      expect(button.props('label')).toBe('Join');
    });
  });

  describe('Accept Invitation', () => {
    it('has acceptInvitation method', () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      expect(typeof vm.acceptInvitation).toBe('function');
    });

    it('calls router.post when button is clicked', async () => {
      const router = await import('@inertiajs/vue3');
      
      wrapper = createWrapper();
      const button = wrapper.findComponent(Button);
      
      await button.trigger('click');
      
      expect(router.router.post).toHaveBeenCalled();
    });
  });

  describe('Company Name Extraction', () => {
    it('has companyName ref', () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      expect(vm.companyName).toBeDefined();
    });

    it('companyName is defined', () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      expect(vm.companyName).toBeDefined();
    });
  });
});
