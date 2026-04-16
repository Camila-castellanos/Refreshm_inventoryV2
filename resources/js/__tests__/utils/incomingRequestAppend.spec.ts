import { describe, it, expect } from 'vitest';
import {
  canUserAppendToInvoice,
  buildAppendOutcomeToast,
  buildAppendErrorToast,
} from '@/Utils/incomingRequestAppend';

describe('incomingRequestAppend utils', () => {
  it('allows append for owner and users with Inventory Active Inventory tab', () => {
    expect(canUserAppendToInvoice({ role: 'OWNER', page_permissions: null })).toBe(true);
    expect(
      canUserAppendToInvoice({
        role: 'USER',
        page_permissions: { Inventory: ['Active Inventory'] },
      })
    ).toBe(true);
  });

  it('blocks append when Active Inventory permission is missing', () => {
    expect(
      canUserAppendToInvoice({
        role: 'USER',
        page_permissions: { Inventory: ['Sold'] },
      })
    ).toBe(false);
  });

  it('builds success and partial outcome toasts from append payload', () => {
    const full = buildAppendOutcomeToast({
      counts: { appended: 2, skipped: 0 },
      state_transition: 'full',
    });

    expect(full.severity).toBe('success');
    expect(full.detail).toContain('2 item');

    const partial = buildAppendOutcomeToast({
      counts: { appended: 1, skipped: 2 },
      state_transition: 'partial',
    });

    expect(partial.severity).toBe('warn');
    expect(partial.detail).toContain('Skipped: 2');
  });

  it('maps API errors to user-facing toasts', () => {
    const conflict = buildAppendErrorToast(409, { message: 'Conflict happened' });
    expect(conflict.severity).toBe('warn');
    expect(conflict.detail).toContain('Conflict happened');

    const validation = buildAppendErrorToast(422, { message: 'Zero eligible' });
    expect(validation.severity).toBe('warn');
    expect(validation.detail).toContain('Zero eligible');

    const generic = buildAppendErrorToast(500, {});
    expect(generic.severity).toBe('error');
  });
});
