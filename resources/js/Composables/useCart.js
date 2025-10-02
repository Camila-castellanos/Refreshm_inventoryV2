/**
 * Cart Composable
 * 
 * Provides easy access to cart functionality throughout the application
 * This is a convenience wrapper around the Pinia cart store
 */

import { useCartStore } from '@/stores/cartStore'
import { computed } from 'vue'

export function useCart() {
    const cartStore = useCartStore()

    return {
        // State
        items: computed(() => cartStore.items),
        marketSlug: computed(() => cartStore.marketSlug),

        // Getters
        itemCount: computed(() => cartStore.itemCount),
        subtotal: computed(() => cartStore.subtotal),
        total: computed(() => cartStore.total),
        isEmpty: computed(() => cartStore.isEmpty),

        // Actions
        setMarket: cartStore.setMarket,
        addItem: cartStore.addItem,
        removeItem: cartStore.removeItem,
        updateQuantity: cartStore.updateQuantity,
        clearCart: cartStore.clearCart,
        hasItem: cartStore.hasItem,
        isItemInCart: cartStore.hasItem, // Alias for better readability
        getItem: cartStore.getItem,
        
        // Backend sync (prepared for future)
        syncToBackend: cartStore.syncToBackend,
        loadFromBackend: cartStore.loadFromBackend,
    }
}
