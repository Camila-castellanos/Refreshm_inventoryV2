import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { mount, VueWrapper } from '@vue/test-utils';
import MarketLayout from '@/Layouts/Ecommerce/MarketLayout.vue';
import { createPinia, setActivePinia } from 'pinia';
import PrimeVue from 'primevue/config';
import { router } from '@inertiajs/vue3';

// Mocks
vi.mock('@inertiajs/vue3', () => ({
  Link: { template: '<a><slot /></a>', props: ['href'] },
  usePage: () => ({ 
    url: '/test-url',
    props: { value: {} } 
  }),
  router: {
    on: vi.fn(() => vi.fn())
  }
}));

vi.mock('@/Components/Ecommerce/Cart.vue', () => ({
  default: { template: '<div class="cart-stub"></div>' }
}));

vi.mock('@/Components/Ecommerce/MarketSidebar.vue', () => ({
  default: { template: '<div class="sidebar-stub"></div>' }
}));

// Mock route function (Ziggy)
const route = vi.fn((name) => name);
(window as any).route = route;

describe('MarketLayout.vue - Google Tag Tracking', () => {
  let wrapper: VueWrapper;

  beforeEach(() => {
    setActivePinia(createPinia());
    document.head.innerHTML = '';
    // @ts-ignore
    delete window.gtag;
    // @ts-ignore
    delete window.dataLayer;
    vi.clearAllMocks();
  });

  const mockMarket = {
    id: 1,
    name: 'Test Market',
    slug: 'test-market',
    google_tag_id: null,
  };

  const createWrapper = (marketProps = {}) => {
    return mount(MarketLayout, {
      props: {
        market: { ...mockMarket, ...marketProps }
      },
      global: {
        plugins: [PrimeVue],
        stubs: {
          Link: true,
          Cart: true,
          MarketSidebar: true
        },
        mocks: {
          route: (name: string, params?: any) => `${name}/${params || ''}`
        }
      }
    });
  };

  it('does NOT inject GTAG script when google_tag_id is null', () => {
    wrapper = createWrapper({ google_tag_id: null });
    
    const scripts = document.head.querySelectorAll('script');
    const gtagScript = Array.from(scripts).find(s => s.src.includes('googletagmanager.com/gtag/js'));
    
    expect(gtagScript).toBeUndefined();
    expect(window.gtag).toBeUndefined();
  });

  it('injects GTAG script when google_tag_id is provided', () => {
    const tagId = 'G-TEST-123';
    wrapper = createWrapper({ google_tag_id: tagId });
    
    const scripts = document.head.querySelectorAll('script');
    const gtagScript = Array.from(scripts).find(s => s.src.includes(`id=${tagId}`));
    
    expect(gtagScript).toBeDefined();
    expect(gtagScript?.async).toBe(true);
    expect(window.gtag).toBeDefined();
    expect(window.dataLayer).toBeDefined();
  });

  it('tracks page view on navigation', async () => {
    const tagId = 'G-TEST-123';
    
    wrapper = createWrapper({ google_tag_id: tagId });
    
    // @ts-ignore
    const mockGtag = vi.fn();
    window.gtag = mockGtag;

    // Get the callback passed to router.on('finish', ...)
    const routerOnMock = vi.mocked(router.on);
    const finishCallback = routerOnMock.mock.calls.find(call => call[0] === 'finish')?.[1];
    
    expect(finishCallback).toBeDefined();
    
    // Trigger navigation
    if (finishCallback) {
        // @ts-ignore
        finishCallback();
    }
    
    expect(mockGtag).toHaveBeenCalledWith('config', tagId, expect.objectContaining({
        page_path: '/test-url'
    }));
  });

  it('updates tracking when google_tag_id prop changes', async () => {
    wrapper = createWrapper({ google_tag_id: null });
    
    expect(window.gtag).toBeUndefined();
    
    const newTagId = 'G-NEW-456';
    await wrapper.setProps({
        market: { ...mockMarket, google_tag_id: newTagId }
    });
    
    const scripts = document.head.querySelectorAll('script');
    const gtagScript = Array.from(scripts).find(s => s.src.includes(`id=${newTagId}`));
    
    expect(gtagScript).toBeDefined();
    expect(window.gtag).toBeDefined();
  });
});
