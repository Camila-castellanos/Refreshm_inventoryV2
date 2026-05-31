import { describe, it, expect, vi } from 'vitest';
import { useCurrency } from '@/Composables/useCurrency';

// Mock usePage from Inertia
vi.mock('@inertiajs/vue3', () => ({
    usePage: () => ({
        props: {
            customer_auth: {
                user: {
                    currency: 'USD'
                }
            }
        }
    })
}));

describe('useCurrency', () => {
    it('returns the currency from Inertia props when logged in', () => {
        const { currentCurrency } = useCurrency();
        expect(currentCurrency.value).toBe('USD');
    });

    it('formats USD correctly', () => {
        const { formatCurrency } = useCurrency();
        const formatted = formatCurrency(100, 'USD');
        // Standard US formatting for USD
        expect(formatted).toContain('$100.00');
    });

    it('formats CAD correctly', () => {
        const { formatCurrency } = useCurrency();
        const formatted = formatCurrency(100, 'CAD');
        // Standard CA formatting for CAD
        expect(formatted).toContain('$100.00');
    });
});
