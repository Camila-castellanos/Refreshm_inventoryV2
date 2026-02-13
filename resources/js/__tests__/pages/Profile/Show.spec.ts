import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, VueWrapper } from '@vue/test-utils';
import Show from '@/Pages/Profile/Show.vue';
import PrimeVue from 'primevue/config';

// Mock components
const MockUpdateProfileInformationForm = {
  name: 'UpdateProfileInformationForm',
  template: '<div>UpdateProfileInformationForm</div>',
  props: ['user']
};

const MockStorage = {
  name: 'Storage',
  template: '<div>Storage</div>'
};

const MockPrintableLabelActiveFields = {
  name: 'PrintableLabelActiveFields',
  template: '<div>PrintableLabelActiveFields</div>'
};

const MockPrintableInvoiceActiveFields = {
  name: 'PrintableInvoiceActiveFields',
  template: '<div>PrintableInvoiceActiveFields</div>'
};

const MockUpdateTimezoneForm = {
  name: 'UpdateTimezoneForm',
  template: '<div>UpdateTimezoneForm</div>',
  props: ['timezone']
};

const MockUpdatePasswordForm = {
  name: 'UpdatePasswordForm',
  template: '<div>UpdatePasswordForm</div>'
};

const MockTwoFactorAuthenticationForm = {
  name: 'TwoFactorAuthenticationForm',
  template: '<div>TwoFactorAuthenticationForm</div>',
  props: ['requiresConfirmation']
};

const MockLogoutOtherBrowserSessionsForm = {
  name: 'LogoutOtherBrowserSessionsForm',
  template: '<div>LogoutOtherBrowserSessionsForm</div>',
  props: ['sessions']
};

const MockDeleteUserForm = {
  name: 'DeleteUserForm',
  template: '<div>DeleteUserForm</div>'
};

const MockSectionBorder = {
  name: 'SectionBorder',
  template: '<hr/>'
};

const MockAppLayout = {
  name: 'AppLayout',
  template: '<div><slot/></div>',
  props: ['title']
};

describe('Profile/Show.vue', () => {
  let wrapper: VueWrapper;

  const createWrapper = (props = {}, pageProps = {}) => {
    return mount(Show, {
      props: {
        confirmsTwoFactorAuthentication: false,
        sessions: [],
        ...props
      },
      global: {
        plugins: [PrimeVue],
        stubs: {
          UpdateProfileInformationForm: MockUpdateProfileInformationForm,
          Storage: MockStorage,
          PrintableLabelActiveFields: MockPrintableLabelActiveFields,
          PrintableInvoiceActiveFields: MockPrintableInvoiceActiveFields,
          UpdateTimezoneForm: MockUpdateTimezoneForm,
          UpdatePasswordForm: MockUpdatePasswordForm,
          TwoFactorAuthenticationForm: MockTwoFactorAuthenticationForm,
          LogoutOtherBrowserSessionsForm: MockLogoutOtherBrowserSessionsForm,
          DeleteUserForm: MockDeleteUserForm,
          SectionBorder: MockSectionBorder,
          AppLayout: MockAppLayout
        },
        mocks: {
          $page: {
            props: {
              jetstream: {
                canUpdateProfileInformation: true,
                canUpdatePassword: true,
                canManageTwoFactorAuthentication: true,
                hasAccountDeletionFeatures: true,
                managesProfilePhotos: true,
                hasEmailVerification: true,
                ...pageProps
              },
              auth: {
                user: {
                  id: 1,
                  name: 'Test User',
                  email: 'test@example.com',
                  timezone: 'UTC'
                }
              }
            }
          }
        }
      }
    });
  };

  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('Rendering', () => {
    it('renders AppLayout with Profile title', () => {
      wrapper = createWrapper();
      const appLayout = wrapper.findComponent({ name: 'AppLayout' });
      
      expect(appLayout.exists()).toBe(true);
      expect(appLayout.props('title')).toBe('Profile');
    });

    it('renders UpdateProfileInformationForm when canUpdateProfileInformation', () => {
      wrapper = createWrapper();
      
      expect(wrapper.findComponent({ name: 'UpdateProfileInformationForm' }).exists()).toBe(true);
    });

    it('renders Storage component', () => {
      wrapper = createWrapper();
      
      expect(wrapper.findComponent({ name: 'Storage' }).exists()).toBe(true);
    });

    it('renders PrintableLabelActiveFields', () => {
      wrapper = createWrapper();
      
      expect(wrapper.findComponent({ name: 'PrintableLabelActiveFields' }).exists()).toBe(true);
    });

    it('renders PrintableInvoiceActiveFields', () => {
      wrapper = createWrapper();
      
      expect(wrapper.findComponent({ name: 'PrintableInvoiceActiveFields' }).exists()).toBe(true);
    });

    it('renders UpdateTimezoneForm', () => {
      wrapper = createWrapper();
      
      expect(wrapper.findComponent({ name: 'UpdateTimezoneForm' }).exists()).toBe(true);
    });

    it('renders UpdatePasswordForm when canUpdatePassword', () => {
      wrapper = createWrapper();
      
      expect(wrapper.findComponent({ name: 'UpdatePasswordForm' }).exists()).toBe(true);
    });

    it('renders TwoFactorAuthenticationForm when canManageTwoFactorAuthentication', () => {
      wrapper = createWrapper();
      
      expect(wrapper.findComponent({ name: 'TwoFactorAuthenticationForm' }).exists()).toBe(true);
    });

    it('renders LogoutOtherBrowserSessionsForm', () => {
      wrapper = createWrapper();
      
      expect(wrapper.findComponent({ name: 'LogoutOtherBrowserSessionsForm' }).exists()).toBe(true);
    });

    it('renders DeleteUserForm when hasAccountDeletionFeatures', () => {
      wrapper = createWrapper();
      
      expect(wrapper.findComponent({ name: 'DeleteUserForm' }).exists()).toBe(true);
    });

    it('hides UpdateProfileInformationForm when canUpdateProfileInformation is false', () => {
      wrapper = createWrapper({}, { canUpdateProfileInformation: false });
      
      expect(wrapper.findComponent({ name: 'UpdateProfileInformationForm' }).exists()).toBe(false);
    });

    it('hides UpdatePasswordForm when canUpdatePassword is false', () => {
      wrapper = createWrapper({}, { canUpdatePassword: false });
      
      expect(wrapper.findComponent({ name: 'UpdatePasswordForm' }).exists()).toBe(false);
    });

    it('hides DeleteUserForm when hasAccountDeletionFeatures is false', () => {
      wrapper = createWrapper({}, { hasAccountDeletionFeatures: false });
      
      expect(wrapper.findComponent({ name: 'DeleteUserForm' }).exists()).toBe(false);
    });
  });
});
