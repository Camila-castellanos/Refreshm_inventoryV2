import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useCurrencyStore = defineStore('currency', () => {
    const currentCurrency = ref(localStorage.getItem('selected_currency') || 'CAD')
    const isInitialized = ref(false)

    const setCurrency = (val, force = true) => {
        if (!['USD', 'CAD'].includes(val)) return
        
        // If it's a manual set (force=true), we always update
        // If it's a sync from props (force=false), we only update if not already initialized
        if (force || !isInitialized.value) {
            currentCurrency.value = val
            localStorage.setItem('selected_currency', val)
            if (force) isInitialized.value = true
        }
    }

    return {
        currentCurrency,
        setCurrency,
        isInitialized
    }
})
