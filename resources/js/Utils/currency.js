/**
 * Convert currency code to symbol
 * @param {string} currencyCode - Currency code (e.g., 'USD', 'CAD', 'EUR')
 * @returns {string} Currency symbol (e.g., '$', '€', '£')
 */
export function getCurrencySymbol(currencyCode) {
    const currencySymbols = {
        'USD': '$',
        'CAD': '$',
        'EUR': '€',
        'GBP': '£',
        'JPY': '¥',
        'AUD': 'A$',
        'NZD': 'NZ$',
        'CHF': 'CHF',
        'CNY': '¥',
        'INR': '₹',
        'MXN': '$',
        'BRL': 'R$',
        'ZAR': 'R',
        'SGD': 'S$',
        'HKD': 'HK$',
    }
    
    return currencySymbols[currencyCode?.toUpperCase()] || currencyCode || '$'
}

/**
 * Format price with currency symbol
 * @param {number} price - Price to format
 * @param {string} currencyCode - Currency code
 * @returns {string} Formatted price (e.g., '$1,234.56')
 */
export function formatCurrencyPrice(price, currencyCode = 'USD') {
    const symbol = getCurrencySymbol(currencyCode)
    const formatted = new Intl.NumberFormat().format(price)
    return `${symbol}${formatted}`
}
