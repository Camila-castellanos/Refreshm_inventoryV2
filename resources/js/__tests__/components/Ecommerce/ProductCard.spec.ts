import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import ProductCard from '@/Components/Ecommerce/ProductCard.vue';

describe('Ecommerce/ProductCard.vue', () => {
    const mockMarket = {
        id: 1,
        slug: 'test-market',
        currency: 'USD',
        show_inventory_count: true
    };

    const mockItem = {
        id: 101,
        model: 'Test Model',
        manufacturer: 'Test Manufacturer',
        selling_price: 100
    };

    it('renders placeholder when no image is provided', () => {
        const wrapper = mount(ProductCard, {
            props: { item: mockItem, market: mockMarket }
        });
        
        expect(wrapper.find('img').exists()).toBe(false);
        expect(wrapper.find('svg').exists()).toBe(true);
    });

    it('renders placeholder when image fails to load', async () => {
        const itemWithPhoto = { ...mockItem, photo: 'broken.jpg' };
        const wrapper = mount(ProductCard, {
            props: { item: itemWithPhoto, market: mockMarket }
        });
        
        const img = wrapper.find('img');
        await img.trigger('error');
        
        expect(wrapper.find('img').exists()).toBe(false); // Should be hidden
        expect(wrapper.find('svg').exists()).toBe(true);
    });

    it('renders image when provided', () => {
        const itemWithPhoto = { ...mockItem, photo: 'valid.jpg' };
        const wrapper = mount(ProductCard, {
            props: { item: itemWithPhoto, market: mockMarket }
        });
        
        expect(wrapper.find('img').exists()).toBe(true);
        expect(wrapper.find('img').attributes('src')).toBe('valid.jpg');
        expect(wrapper.find('svg').exists()).toBe(false);
    });
});
