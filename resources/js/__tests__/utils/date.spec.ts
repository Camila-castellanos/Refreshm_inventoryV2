import { describe, it, expect } from 'vitest';
import { formatDate } from '@/Utils/date';

describe('date utils', () => {
  it('extracts date from ISO format string', () => {
    expect(formatDate('2026-05-26T18:43:56')).toBe('2026-05-26');
    expect(formatDate('2026-01-15T00:00:00')).toBe('2026-01-15');
  });

  it('extracts date from space-separated string', () => {
    expect(formatDate('2026-05-26 18:43:56')).toBe('2026-05-26');
    expect(formatDate('2026-01-15 00:00:00')).toBe('2026-01-15');
  });

  it('returns date-only string unchanged', () => {
    expect(formatDate('2026-05-26')).toBe('2026-05-26');
    expect(formatDate('2026-01-15')).toBe('2026-01-15');
  });

  it('returns empty string for null', () => {
    expect(formatDate(null)).toBe('');
  });

  it('returns empty string for undefined', () => {
    expect(formatDate(undefined)).toBe('');
  });

  it('returns empty string for empty string', () => {
    expect(formatDate('')).toBe('');
  });
});