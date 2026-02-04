import { describe, it, expect } from 'vitest';

describe('Vitest Setup', () => {
    it('should work', () => {
        expect(true).toBe(true);
    });

    it('should have global route mock', () => {
        expect(typeof route).toBe('function');
        expect(route('test')).toBe('/test');
    });

    it('should have ResizeObserver mock', () => {
        expect(typeof ResizeObserver).toBe('function');
    });
});
