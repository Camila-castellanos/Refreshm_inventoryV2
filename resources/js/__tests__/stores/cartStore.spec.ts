import { describe, it, expect, beforeEach, vi } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useCartStore } from '@/stores/cartStore';

describe('Stores/CartStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    // Clear localStorage mock before each test if needed, 
    // though Pinia persist plugin handling depends on setup.
    // We assume clean state due to createPinia()
  });

  const mockProduct = {
    id: 101,
    model: 'iPhone 13',
    manufacturer: 'Apple',
    selling_price: 500,
    type: 'Phone',
    imei: '123456789',
    issues: 'None'
  };

  const mockProduct2 = {
    id: 102,
    model: 'Samsung S21',
    manufacturer: 'Samsung',
    selling_price: 400,
    type: 'Phone',
    imei: '987654321',
    issues: 'None'
  };

  describe('Item Management', () => {
    it('adds an item to the cart', () => {
      const store = useCartStore();
      
      const success = store.addItem(mockProduct);
      
      expect(success).toBe(true);
      expect(store.items).toHaveLength(1);
      expect(store.items[0].id).toBe(101);
      expect(store.items[0].quantity).toBe(1);
    });

    it('prevents adding duplicate items (unique inventory)', () => {
      const store = useCartStore();
      
      store.addItem(mockProduct);
      const success = store.addItem(mockProduct); // Try adding again
      
      expect(success).toBe(false);
      expect(store.items).toHaveLength(1);
    });

    it('removes an item from the cart', () => {
      const store = useCartStore();
      
      store.addItem(mockProduct);
      store.addItem(mockProduct2);
      expect(store.items).toHaveLength(2);
      
      store.removeItem(101); // Remove iPhone
      
      expect(store.items).toHaveLength(1);
      expect(store.items[0].id).toBe(102);
    });

    it('clears the cart completely', () => {
      const store = useCartStore();
      
      store.addItem(mockProduct);
      store.addItem(mockProduct2);
      
      store.clearCart();
      
      expect(store.items).toHaveLength(0);
      expect(store.isEmpty).toBe(true);
    });
  });

  describe('Calculations', () => {
    it('calculates total item count', () => {
      const store = useCartStore();
      
      store.addItem(mockProduct);
      store.addItem(mockProduct2);
      
      // 1 + 1
      expect(store.itemCount).toBe(2);
    });

    it('calculates subtotal correctly', () => {
      const store = useCartStore();
      
      store.addItem(mockProduct);  // 500
      store.addItem(mockProduct2); // 400
      
      // 500 + 400 = 900
      expect(store.subtotal).toBe(900);
      expect(store.total).toBe(900);
    });
  });

  describe('Market Logic', () => {
    it('clears cart when switching markets', () => {
      const store = useCartStore();
      
      store.setMarket('tech-store');
      store.addItem(mockProduct);
      expect(store.items).toHaveLength(1);
      
      // Switch market
      store.setMarket('fashion-outlet');
      
      // Cart should be empty now
      expect(store.items).toHaveLength(0);
      expect(store.marketSlug).toBe('fashion-outlet');
    });

    it('keeps cart when setting same market', () => {
      const store = useCartStore();
      
      store.setMarket('tech-store');
      store.addItem(mockProduct);
      
      // Set same market
      store.setMarket('tech-store');
      
      // Cart should stay
      expect(store.items).toHaveLength(1);
    });
  });
  
  describe('Getters helpers', () => {
    it('checks if item exists', () => {
      const store = useCartStore();
      store.addItem(mockProduct);
      
      expect(store.hasItem(101)).toBe(true);
      expect(store.hasItem(999)).toBe(false);
    });
    
    it('gets item by id', () => {
      const store = useCartStore();
      store.addItem(mockProduct);
      
      const item = store.getItem(101);
      expect(item.model).toBe('iPhone 13');
    });
  });
});
