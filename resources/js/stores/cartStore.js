import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useCartStore = defineStore('cart', () => {
    // State
    const items = ref([])
    const marketSlug = ref(null)

    // Getters (computed properties)
    const itemCount = computed(() => {
        return items.value.reduce((total, item) => total + item.quantity, 0)
    })

    const subtotal = computed(() => {
        return items.value.reduce((total, item) => 
            total + (item.selling_price * item.quantity), 0
        )
    })

    const total = computed(() => {
        // For now, total equals subtotal
        // In the future, you can add shipping, taxes, discounts here
        return subtotal.value
    })

    const isEmpty = computed(() => items.value.length === 0)

    // Actions
    const setMarket = (slug) => {
        if (marketSlug.value && marketSlug.value !== slug) {
            // If switching markets, clear the cart
            clearCart()
        }
        marketSlug.value = slug
    }

    const addItem = (product) => {
        // Check if item already exists
        const existingItem = items.value.find(item => item.id === product.id)
        
        if (existingItem) {
            // For refurbished items, we don't allow duplicates
            return false
        }
        
        // Add new item to cart
        const cartItem = {
            id: product.id,
            model: product.model,
            manufacturer: product.manufacturer,
            selling_price: product.selling_price,
            quantity: 1, // Always 1 for unique refurbished items
            type: product.type,
            imei: product.imei,
            issues: product.issues,
            addedAt: new Date().toISOString()
        }
        
        items.value.push(cartItem)
        
        // Dispatch global event
        window.dispatchEvent(new CustomEvent('cart-updated', {
            detail: { 
                action: 'added',
                itemId: product.id,
                totalCount: itemCount.value 
            }
        }))
        
        return true
    }

    const removeItem = (itemId) => {
        const index = items.value.findIndex(item => item.id === itemId)
        
        if (index !== -1) {
            items.value.splice(index, 1)
            
            // Dispatch global event
            window.dispatchEvent(new CustomEvent('cart-updated', {
                detail: { 
                    action: 'removed',
                    itemId: itemId,
                    totalCount: itemCount.value 
                }
            }))
        }
    }

    const updateQuantity = (itemId, quantity) => {
        // Note: For refurbished items, quantity should always be 1
        // This method is kept for future flexibility if needed
        if (quantity < 1) return
        
        const item = items.value.find(item => item.id === itemId)
        if (item) {
            item.quantity = quantity
            
            // Dispatch global event
            window.dispatchEvent(new CustomEvent('cart-updated', {
                detail: { 
                    action: 'updated',
                    itemId: itemId,
                    quantity: quantity,
                    totalCount: itemCount.value 
                }
            }))
        }
    }

    const clearCart = () => {
        items.value = []
        
        // Dispatch global event
        window.dispatchEvent(new CustomEvent('cart-updated', {
            detail: { 
                action: 'cleared',
                totalCount: 0 
            }
        }))
    }

    const hasItem = (productId) => {
        return items.value.some(item => item.id === productId)
    }

    const getItem = (productId) => {
        return items.value.find(item => item.id === productId)
    }

    // Backend sync methods (prepared for future implementation)
    const syncToBackend = async () => {
        if (!marketSlug.value) {
            return
        }

        try {
            // TODO: Uncomment when backend endpoint is ready
            /*
            const response = await axios.post(`/api/market/${marketSlug.value}/cart/sync`, {
                items: items.value.map(item => ({
                    id: item.id,
                    quantity: item.quantity
                }))
            })
            */
        } catch (error) {
            console.error('[CartStore] Failed to sync cart to backend:', error)
        }
    }

    const loadFromBackend = async (slug) => {
        try {
            // TODO: Uncomment when backend endpoint is ready
            /*
            const response = await axios.get(`/api/market/${slug}/cart`)
            if (response.data.items && Array.isArray(response.data.items)) {
                items.value = response.data.items
                marketSlug.value = slug
            }
            */
        } catch (error) {
            console.error('[CartStore] Failed to load cart from backend:', error)
        }
    }

    return {
        // State
        items,
        marketSlug,
        
        // Getters
        itemCount,
        subtotal,
        total,
        isEmpty,
        
        // Actions
        setMarket,
        addItem,
        removeItem,
        updateQuantity,
        clearCart,
        hasItem,
        getItem,
        syncToBackend,
        loadFromBackend
    }
}, {
    // Persistence configuration
    persist: {
        key: 'refreshm-ecommerce-cart', // LocalStorage key
        storage: localStorage,
        paths: ['items', 'marketSlug'], // Only persist these fields
        
        // Optional: Add serializer for better control
        serializer: {
            serialize: (value) => JSON.stringify(value),
            deserialize: (value) => JSON.parse(value)
        }
    }
})
