import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import VariantCard from '../../../Components/Ecommerce/VariantCard.vue';
import PrimeVue from 'primevue/config';
import { createPinia, setActivePinia } from 'pinia';

// Mock useCart
vi.mock('@/composables/useCart', () => ({
    useCart: () => ({
        isItemInCart: vi.fn().mockReturnValue(false),
        addItem: vi.fn()
    })
}));

describe('Ecommerce/VariantCard.vue', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
    });

    const mockItem = {
        id: 101,
        model: 'Test Variant',
        selling_price: 100
    };

    it('renders placeholder when no image is provided', () => {
        const wrapper = mount(VariantCard, {
            props: { item: mockItem, currencySymbol: '$' },
            global: {
                plugins: [PrimeVue, createPinia()]
            }
        });
        
        expect(wrapper.find('img').exists()).toBe(false);
        expect(wrapper.find('svg').exists()).toBe(true);
    });

    it('renders placeholder when image fails to load', async () => {
        const itemWithPhoto = { ...mockItem, main_photo_thumb: 'broken.jpg' };
        const wrapper = mount(VariantCard, {
            props: { item: itemWithPhoto, currencySymbol: '$' },
            global: {
                plugins: [PrimeVue, createPinia()]
            }
        });
        
        const img = wrapper.find('img');
        await img.trigger('error');
        
        expect(wrapper.find('img').exists()).toBe(false); // Should be hidden
        expect(wrapper.find('svg').exists()).toBe(true);
    });

    it('renders image when provided', () => {
        const itemWithPhoto = { ...mockItem, main_photo_thumb: 'valid.jpg' };
        const wrapper = mount(VariantCard, {
            props: { item: itemWithPhoto, currencySymbol: '$' },
            global: {
                plugins: [PrimeVue, createPinia()]
            }
        });
        
        expect(wrapper.find('img').exists()).toBe(true);
        expect(wrapper.find('img').attributes('src')).toBe('valid.jpg');
        expect(wrapper.find('svg').exists()).toBe(false);
    });
});
