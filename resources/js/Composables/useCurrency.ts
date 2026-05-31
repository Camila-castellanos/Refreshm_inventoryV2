import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function useCurrency() {
    const page = usePage();

    const currentCurrency = computed(() => {
        // Source of truth: Authenticated customer preference
        const userCurrency = page.props.customer_auth?.user?.currency;
        if (userCurrency) {
            return userCurrency;
        }

        // Fallback to localStorage for guest users (volatile detection)
        return localStorage.getItem('selected_currency') || 'CAD';
    });

    /**
     * Formats a numeric value as currency based on the current user preference.
     * @param value The amount to format
     * @param currencyCode Optional override for the currency code
     */
    const formatCurrency = (value: number | string | null, currencyCode?: string) => {
        const amount = typeof value === 'string' ? parseFloat(value) : (value ?? 0);
        const currency = currencyCode || currentCurrency.value;

        // Use en-US for USD and en-CA for CAD to get standard formatting
        const locale = currency === 'USD' ? 'en-US' : 'en-CA';

        return new Intl.NumberFormat(locale, {
            style: 'currency',
            currency: currency,
        }).format(amount);
    };

    return {
        currentCurrency,
        formatCurrency,
    };
}
