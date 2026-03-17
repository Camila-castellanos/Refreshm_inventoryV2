import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { mount, VueWrapper } from '@vue/test-utils';
import ExchangeRateToggle from '@/Components/ExchangeRateToggle.vue';

describe('Components/ExchangeRateToggle.vue', () => {
  let wrapper: VueWrapper;
  
  // Store original global fetch
  const originalFetch = global.fetch;

  beforeEach(() => {
    // Reset mocks before each test
    vi.clearAllMocks();
    
    // Mock the global fetch API to simulate both IPAPI and our internal API
    global.fetch = vi.fn((url: string | Request | URL) => {
      const urlStr = url.toString();
      
      // Mock GeoIP detection
      if (urlStr.includes('ipapi.co')) {
        return Promise.resolve({
          ok: true,
          json: () => Promise.resolve({ country_code: 'CA' })
        } as Response);
      }
      
      // Mock internal exchange rate API
      if (urlStr.includes('/api/exchange-rate')) {
        return Promise.resolve({
          ok: true,
          json: () => Promise.resolve({ success: true, rate: 1.35, cached: true })
        } as Response);
      }

      return Promise.reject(new Error(`Unhandled fetch mock: ${urlStr}`));
    });
  });

  afterEach(() => {
    // Restore global fetch
    global.fetch = originalFetch;
    if (wrapper) {
      wrapper.unmount();
    }
  });

  const createWrapper = () => {
    return mount(ExchangeRateToggle, {
      global: {
        // Any required stubs or plugins
      }
    });
  };

  it('renders default canadian flag initially when user is not from USA', async () => {
    wrapper = createWrapper();
    
    // Wait for onMounted lifecycle to complete
    await new Promise(resolve => setTimeout(resolve, 10)); // wait for microtasks (fetch promises)
    
    // Verify it renders the CA flag (by checking country ref or SVG title)
    expect(wrapper.html()).toContain('Flag of Canada');
    
    // The component shouldn't have emitted the activated state yet because the user is in CA
    const emitted = wrapper.emitted('exchangeToggled');
    expect(emitted).toBeFalsy(); 
  });

  it('detects USA user, fetches rate, and auto-activates USD exchange', async () => {
    // Override fetch mock for this specific test to return US
    global.fetch = vi.fn((url: string | Request | URL) => {
      const urlStr = url.toString();
      if (urlStr.includes('ipapi.co')) {
        return Promise.resolve({
          ok: true,
          json: () => Promise.resolve({ country_code: 'US' })
        } as Response);
      }
      if (urlStr.includes('/api/exchange-rate')) {
        return Promise.resolve({
          ok: true,
          json: () => Promise.resolve({ success: true, rate: 1.40, cached: false })
        } as Response);
      }
      return Promise.reject(new Error(''));
    });

    wrapper = createWrapper();
    
    await new Promise(resolve => setTimeout(resolve, 50)); // wait for all async onMounted
    
    // It should have switched to US flag internally
    expect(wrapper.html()).not.toContain('Flag of Canada');
    
    // It should have emitted the event automatically
    const emitted = wrapper.emitted('exchangeToggled');
    expect(emitted).toBeTruthy();
    if(emitted) {
      expect(emitted[0]).toEqual([true, 1.40, 'USD']); // isActive, rate, currency
    }
  });

  it('toggles exchange rate and emits event when clicked manually', async () => {
    wrapper = createWrapper();
    await new Promise(resolve => setTimeout(resolve, 20)); // let onMounted finish
    
    // Initially not emitted (CA user)
    expect(wrapper.emitted('exchangeToggled')).toBeFalsy();

    // Click the button
    await wrapper.find('button').trigger('click');
    
    // Check if event was emitted correctly
    const emitted = wrapper.emitted('exchangeToggled');
    expect(emitted).toBeTruthy();
    
    if(emitted) {
       // Since default mock rate is 1.35
      expect(emitted[0]).toEqual([true, 1.35, 'USD']);
    }

    // Click again to toggle back
    await wrapper.find('button').trigger('click');
    if(emitted) {
      expect(emitted[1]).toEqual([false, null, 'CAD']);
    }
  });
});
