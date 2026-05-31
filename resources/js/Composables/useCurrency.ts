import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function useCurrency() {
    const page = usePage();

    const currentCurrency = computed(() => {
        // 1. Source of truth: Authenticated customer preference
        const userCurrency = page.props.customer_auth?.user?.currency;
        if (userCurrency) {
            return userCurrency;
        }

        // 2. Manual override: LocalStorage for guest users
        const localCurrency = localStorage.getItem('selected_currency');
        if (localCurrency) {
            return localCurrency;
        }

        // 3. Backend detection: Guest currency from GeoIP
        const guestCurrency = page.props.customer_auth?.guest_currency;
        if (guestCurrency) {
            return guestCurrency;
        }

        // 4. Ultimate fallback
        return 'CAD';
    });

    /**
     * Formats a numeric value as currency based on the current user preference.
     * @param value The amount to format
     * @param currencyCode Optional override for the currency code
     * @param showCode Whether to append the currency code (e.g. USD, CAD)
     */
    const formatCurrency = (value: number | string | null, currencyCode?: string, showCode: boolean = false) => {
        const amount = typeof value === 'string' ? parseFloat(value) : (value ?? 0);
        const currency = currencyCode || currentCurrency.value;

        // Use en-US for USD and en-CA for CAD to get standard formatting
        const locale = currency === 'USD' ? 'en-US' : 'en-CA';

        let formatted = new Intl.NumberFormat(locale, {
            style: 'currency',
            currency: currency,
        }).format(amount);

        if (showCode) {
            formatted += ` ${currency}`;
        }

        return formatted;
    };

    return {
        currentCurrency,
        formatCurrency,
    };
}
