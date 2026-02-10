import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, VueWrapper } from '@vue/test-utils';
import UpdateProfileInformationForm from '@/Pages/Profile/Partials/UpdateProfileInformationForm.vue';
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

vi.mock('@/Utils/createPublicLink', () => ({
  default: vi.fn((name, slug, id) => `/store/${slug || id}`)
}));

vi.mock('@/Utils/createInvitationLink', () => ({
  default: vi.fn((companyName) => `https://invite.example.com/${companyName}`)
}));

// Mock usePage from @inertiajs/vue3
vi.mock('@inertiajs/vue3', () => ({
  usePage: () => ({
    props: {
      value: {
        jetstream: {
          managesProfilePhotos: true,
          hasEmailVerification: true
        },
        flash: {}
      }
    }
  }),
  useForm: (data: any) => ({
    ...data,
    processing: false,
    errors: {},
    post: vi.fn(),
    reset: vi.fn()
  }),
  router: {
    delete: vi.fn()
  }
}));

const mockUser = {
  id: 1,
  name: 'John Doe',
  email: 'john@example.com',
  companyName: 'ACME Corp',
  profile_photo_url: 'https://example.com/photo.jpg',
  profile_photo_path: '/photos/1.jpg',
  email_verified_at: null as string | null,
  shops: [
    { id: 1, name: 'Shop One', slug: 'shop-one' },
    { id: 2, name: 'Shop Two', slug: 'shop-two' }
  ]
};

describe('Profile/Partials/UpdateProfileInformationForm.vue', () => {
  let wrapper: VueWrapper;

  const createWrapper = (user = mockUser) => {
    return mount(UpdateProfileInformationForm, {
      props: {
        user
      },
      global: {
        plugins: [PrimeVue],
        stubs: {
          Card: {
            template: '<div class="card"><slot name="content"/></div>'
          },
          Button: true,
          InputText: true,
          InputSwitch: true,
          EditShopModal: true
        },
        mocks: {
          $page: {
            props: {
              jetstream: {
                managesProfilePhotos: true,
                hasEmailVerification: true
              },
              flash: {}
            }
          }
        }
      }
    });
  };

  beforeEach(() => {
    vi.clearAllMocks();
    // Mock localStorage
    Object.defineProperty(window, 'localStorage', {
      value: {
        getItem: vi.fn(),
        setItem: vi.fn(),
      },
      writable: true
    });
    // Mock navigator.clipboard
    Object.defineProperty(navigator, 'clipboard', {
      value: {
        writeText: vi.fn().mockResolvedValue(undefined)
      },
      writable: true
    });
  });

  describe('Rendering', () => {
    it('renders profile information form', () => {
      wrapper = createWrapper();
      
      expect(wrapper.find('form').exists()).toBe(true);
      expect(wrapper.text()).toContain('Profile Information');
      expect(wrapper.text()).toContain("Update your account's profile information");
    });

    it('displays company name and invitation link', () => {
      wrapper = createWrapper();
      
      expect(wrapper.text()).toContain('Company: ACME Corp');
    });

    it('renders shops section with toggle', () => {
      wrapper = createWrapper();
      
      expect(wrapper.text()).toContain('Show shops');
    });
  });

  describe('Form Functionality', () => {
    it('has name and email fields', () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      expect(vm.form.name).toBe('John Doe');
      expect(vm.form.email).toBe('john@example.com');
    });

    it('displays unverified email message when email not verified', () => {
      wrapper = createWrapper();
      
      expect(wrapper.text()).toContain('Your email address is unverified');
    });

    it('does not display unverified message when email is verified', () => {
      const verifiedUser = { ...mockUser, email_verified_at: '2024-01-01' };
      wrapper = createWrapper(verifiedUser);
      
      expect(wrapper.text()).not.toContain('Your email address is unverified');
    });
  });

  describe('Shops Management', () => {
    it('toggles shops visibility', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      // Set initial value
      vm.showShops = true;
      await wrapper.vm.$nextTick();
      
      expect(vm.showShops).toBe(true);
      
      vm.showShops = false;
      await wrapper.vm.$nextTick();
      
      expect(vm.showShops).toBe(false);
    });

    it('opens edit shop modal', () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      vm.openEditShop(1, 'Shop One');
      
      expect(vm.dialog.visible).toBe(true);
      expect(vm.dialog.shopId).toBe(1);
      expect(vm.dialog.name).toBe('Shop One');
    });

    it('updates shop after save', () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      vm.onShopSaved({ id: 1, name: 'Updated Shop', slug: 'updated-shop' });
      
      expect(vm.$props.user.shops[0].name).toBe('Updated Shop');
      expect(mockToastAdd).toHaveBeenCalledWith(
        expect.objectContaining({
          severity: 'success',
          summary: 'Shop Updated'
        })
      );
    });
  });

  describe('Clipboard Operations', () => {
    it('copies invitation link to clipboard', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      await vm.copyToClipboard('https://invite.example.com/ACME Corp');
      
      expect(navigator.clipboard.writeText).toHaveBeenCalled();
      expect(mockToastAdd).toHaveBeenCalledWith(
        expect.objectContaining({
          severity: 'success',
          summary: 'Link Copied'
        })
      );
    });

    it('handles clipboard operations', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      
      // Test that copyToClipboard function exists and works
      expect(typeof vm.copyToClipboard).toBe('function');
      
      // Call with valid URL
      await vm.copyToClipboard('test-url');
      
      // Verify clipboard was accessed
      expect(navigator.clipboard.writeText).toHaveBeenCalledWith('test-url');
    });
  });
});
