type UserLike = {
  role?: string | null;
  page_permissions?: unknown;
};

type AppendCounts = {
  appended?: number;
  skipped?: number;
};

type AppendResponseLike = {
  counts?: AppendCounts;
  state_transition?: 'full' | 'partial' | 'unchanged' | string;
  idempotent_replay?: boolean;
};

export function canUserAppendToInvoice(user?: UserLike | null): boolean {
  if (!user) {
    return false;
  }

  if (user.role === 'OWNER') {
    return true;
  }

  const perms = normalizePermissions(user.page_permissions);
  if (!perms || typeof perms !== 'object' || Array.isArray(perms)) {
    return true;
  }

  const inventoryPerms = (perms as Record<string, unknown>)['Inventory'];

  if (!Array.isArray(inventoryPerms)) {
    return true;
  }

  return inventoryPerms.includes('Active Inventory');
}

export function buildAppendOutcomeToast(response: AppendResponseLike): {
  severity: 'success' | 'warn' | 'info';
  summary: string;
  detail: string;
  life: number;
} {
  const appended = Number(response.counts?.appended ?? 0);
  const skipped = Number(response.counts?.skipped ?? 0);
  const replaySuffix = response.idempotent_replay ? ' (retried safely)' : '';

  if (response.state_transition === 'full') {
    return {
      severity: 'success',
      summary: 'Items appended',
      detail: `${appended} item(s) added to the invoice${replaySuffix}.`,
      life: 3000,
    };
  }

  if (response.state_transition === 'partial') {
    return {
      severity: 'warn',
      summary: 'Partially appended',
      detail: `${appended} item(s) added. Skipped: ${skipped}.${replaySuffix}`,
      life: 4200,
    };
  }

  return {
    severity: 'info',
    summary: 'Append result',
    detail: `${appended} item(s) added.${replaySuffix}`,
    life: 3000,
  };
}

export function buildAppendErrorToast(status?: number, data?: { message?: string }): {
  severity: 'warn' | 'error';
  summary: string;
  detail: string;
  life: number;
} {
  const backendMessage = data?.message;

  if (status === 409) {
    return {
      severity: 'warn',
      summary: 'Request state changed',
      detail: backendMessage ?? 'The incoming request was already processed. Refresh and try again.',
      life: 4500,
    };
  }

  if (status === 422) {
    return {
      severity: 'warn',
      summary: 'Unable to append items',
      detail: backendMessage ?? 'The selected invoice cannot accept these items.',
      life: 5000,
    };
  }

  return {
    severity: 'error',
    summary: 'Append failed',
    detail: backendMessage ?? 'Unexpected error while adding items to invoice.',
    life: 5000,
  };
}

function normalizePermissions(raw: unknown): Record<string, unknown> | null {
  if (!raw) {
    return null;
  }

  if (typeof raw === 'string') {
    try {
      const parsed = JSON.parse(raw);
      return parsed && typeof parsed === 'object' ? parsed : null;
    } catch {
      return null;
    }
  }

  if (typeof raw === 'object') {
    return raw as Record<string, unknown>;
  }

  return null;
}
