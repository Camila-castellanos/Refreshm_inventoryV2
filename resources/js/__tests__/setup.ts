import { config } from '@vue/test-utils';
import { vi } from 'vitest';
import PrimeVue from 'primevue/config';
import ToastService from 'primevue/toastservice';
import ConfirmationService from 'primevue/confirmationservice';

// Mock global route helper from Ziggy
global.route = vi.fn((name: string, params?: any) => {
    if (params) {
        const queryString = new URLSearchParams(params).toString();
        return `/${name}${queryString ? '?' + queryString : ''}`;
    }
    return `/${name}`;
});

// Mock axios
global.axios = {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn(),
};

// Configure Vue Test Utils
globalThis.ResizeObserver = class ResizeObserver {
    observe() {}
    unobserve() {}
    disconnect() {}
};

// Mock IntersectionObserver
globalThis.IntersectionObserver = class IntersectionObserver {
    constructor() {}
    disconnect() {}
    observe() {}
    takeRecords() {
        return [];
    }
    unobserve() {}
};

// Mock matchMedia
Object.defineProperty(window, 'matchMedia', {
    writable: true,
    value: vi.fn().mockImplementation(query => ({
        matches: false,
        media: query,
        onchange: null,
        addListener: vi.fn(),
        removeListener: vi.fn(),
        addEventListener: vi.fn(),
        removeEventListener: vi.fn(),
        dispatchEvent: vi.fn(),
    })),
});
