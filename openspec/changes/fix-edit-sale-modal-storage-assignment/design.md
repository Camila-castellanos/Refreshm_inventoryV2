# Design: fix-edit-sale-modal-storage-assignment

## Overview

Restore the `sold_storage_*` snapshot for items transitioning to `SOLD` from three controller paths where it is silently dropped (Bugs A, B, C), and fix a Vue payload contract mismatch (Bug D). See `openspec/changes/fix-edit-sale-modal-storage-assignment/proposal.md` and `specs/sales/spec.md`. **No new infrastructure**: three surgical fixes in `SaleController` plus a one-line Vue change, mirroring the snapshot precedent at `SaleController.php:126-128` and reusing `Storage::findFirstAvailablePosition()` (`Storage.php:222-257`).

## Architecture decisions

### AD-1: Inline auto-assign vs. shared service

| Option | Tradeoff | Decision |
| --- | --- | --- |
| Inline in each method | Smallest diff; surrounding code differs (create vs. update) | **Chosen** |
| Extract a service | Worthwhile at 3+ callsites | Rejected (out of scope); follow-up issue |

### AD-2: Explicit snapshot copy (B.1) vs. boot hook alone

The `Item::saving` hook snapshot block (`Item.php:347-356`) is gated on `! is_null($item->storage_id)` at hook time (`Item.php:349`). When the controller passes `storage_id => null` in the update array (`SaleController.php:320`), the hook sees null and skips. The explicit copy wins; the hook's `is_null($item->sold_storage_id)` guard makes the two coexist. Precedent: `SaleController.php:126-128` (store path).

### AD-3: Idempotency guard

Guard the snapshot copy with `is_null($sale_item->sold_storage_id)`. Re-saves must not clobber an existing snapshot with the current (null) `storage_id`. Mapped to R2.S2.

### AD-4: Graceful degradation when no storage exists

If `Storage::findFirstAvailablePosition()` returns `null`, the item still saves with `storage_id = null` and we log a `warning` with `user_id` + `sale_id`. Matches existing behavior. A missing storage is a configuration problem, not a per-item error.

### AD-5: Vue type fix shape

`position: ""` → `position: null` in `ItemsSell.vue:297`. `null` matches every other field in the same template and the backend's `nullable` validation (`SaleForm.php:44`).

## File-by-file change list

### `app/Http/Controllers/SaleController.php`

#### Change 1: `store`, new-items branch (Bug C) — lines `:170-222`

Insert between status/sold branch (`:191-199`) and `Item::create` (`:201`). Applies to BOTH paid and unpaid new items:

```php
$storageSlot = Storage::findFirstAvailablePosition();
if ($storageSlot !== null) {
    $itemData['storage_id'] = $storageSlot['storage_id'];
    $itemData['position'] = $storageSlot['position'];
} else {
    Log::warning('No storage available for new sale item', [
        'user_id' => Auth::id(), 'sale_id' => $sale->id,
    ]);
}
```

For unpaid: feeds `creating` hook (`Item.php:370-377`) a `storage_id` to fill `position` from. For paid: `saving` hook (`Item.php:347-356`) snapshots then nulls. **Maps to R1.S1**.

#### Change 2: `update`, existing-items paid branch (Bug B) — lines `:312-321`

Replace the `->update([...])` call:

```php
if ($paid == 1) {
    // Mirror SaleController.php:126-128: capture snapshot BEFORE nulling.
    // Hook's is_null($item->sold_storage_id) guard (Item.php:349) makes
    // the explicit copy + hook coexist safely.
    $snapshot = [];
    if (is_null($sale_item->sold_storage_id) && ! is_null($sale_item->storage_id)) {
        $snapshot = [
            'sold_storage_id' => $sale_item->storage_id,
            'sold_position' => $sale_item->position,
            'sold_storage_name' => $sale_item->storage?->name,
        ];
    }
    $sale_item->update(array_merge([
        'selling_price' => $item['selling_price'],
        'profit' => $item['selling_price'] - $item['cost'],
        'customer' => $request->customer,
        'status' => $itemStatus,
        'sold' => $request->date,
        'position' => null,
        'storage_id' => null,
    ], $snapshot));
}
```

**Maps to R2.S1, R2.S2, R3.S1, R3.S2, R4.S1.**

#### Change 3: `update`, new-items branch (Bug A) — lines `:341-366`

Same auto-assign block as Change 1, inserted before `Item::create` (`:364`):

```php
$storageSlot = Storage::findFirstAvailablePosition();
if ($storageSlot !== null) {
    $newItemData['storage_id'] = $storageSlot['storage_id'];
    // position: auto-assigned by Item::creating (Item.php:370-377); for
    // paid sales the saving hook (Item.php:347-356) snapshots and nulls.
} else {
    Log::warning('No storage available for new sale item', [
        'user_id' => $user->id, 'sale_id' => $request->id,
    ]);
}
```

**Maps to R1.S2.**

### `resources/js/Pages/Inventory/Modals/ItemsSell.vue`

#### Change 4: `newRowTemplate` (Bug D) — line `:297`

One-line: `position: ""` → `position: null`. Grep verified: no other `position: ""` occurrences.

## Test plan

| Spec | Test file | Test name | Type |
| --- | --- | --- | --- |
| R1.S1 | `SaleControllerStoreTest.php` (new) | `test_store_paid_sale_with_new_items_assigns_storage` | PHP Feature |
| R1.S2 | `SaleItemReservationTest.php` (extend) | `test_update_paid_sale_with_new_item_assigns_storage` | PHP Feature |
| R2.S1 | `SaleItemReservationTest.php` (extend) | `test_update_paid_sale_existing_item_populates_snapshot` | PHP Feature |
| R2.S2 | `SaleItemReservationTest.php` (extend) | `test_update_paid_sale_resold_item_preserves_snapshot` | PHP Feature |
| R3.S1, R3.S2, R4.S1 | folded into R2.S1 / R2.S2 | — | — |
| Bug D | `ItemsSell.spec.ts` (extend) | `test_new_row_template_position_is_null` | JS Component |

**Recommended extra**: `tests/Unit/Models/ItemSavingHookTest.php` (new) with `test_saving_hook_does_not_clobber_existing_sold_storage_id_on_resave` — locks the hook's idempotency contract (`Item.php:349`) independently from the controller.

## Work-unit commit plan

Following `work-unit-commits` (commit by deliverable behavior, tests with code, each commit PR-ready):

1. **`fix(sales): assign storage to new items in store paid/unpaid paths`** — Change 1 + new `SaleControllerStoreTest.php`.
2. **`fix(sales): capture sold_storage snapshot in update existing-items paid`** — Change 2 + 2 new `SaleItemReservationTest` cases.
3. **`fix(sales): assign storage to new items in update path`** — Change 3 + 1 new `SaleItemReservationTest` case.
4. **`fix(items-sell): change newRowTemplate.position to null`** — Change 4 + 1 JS spec assertion.
5. **`test(item): add idempotency test for saving hook`** — recommended additional test.

Each commit leaves the suite green. Ship 1-3 as one PR (~213 lines, well under 400) or split into chained PRs.

## Risks and mitigations

- **Snapshot is the only historical location reference** → R2/R3/R4 assertions.
- **Boot hook idempotency** (`Item.php:349`) → AD-3 guard + commit-5 unit test.
- **Auto-assign surprises users** → AD-4 graceful degradation; follow-up UI documented.
- **Vue `position` contract change** → commit-4 JS test; grep re-verified.
- **`findFirstAvailablePosition` returns `null`** → AD-4 (degrade + `Log::warning`).
- **Pre-existing race on `getNextAvailablePosition`** → out of scope; documented.
- **One-way snapshot valve** → R2.S2 re-save scenario + AD-3 guard.

## Open questions

None. Proposal and spec are both finalized.

## Affected lines summary

| File | Action | Lines |
| --- | --- | --- |
| `app/Http/Controllers/SaleController.php` | Modify | +28 / -8 (3 hunks) |
| `resources/js/Pages/Inventory/Modals/ItemsSell.vue` | Modify | +1 / -1 (line `:297`) |
| `tests/Feature/Controllers/SaleControllerStoreTest.php` | Create | ~60 |
| `tests/Feature/Controllers/SaleItemReservationTest.php` | Extend | ~95 |
| `resources/js/__tests__/pages/Inventory/Modals/ItemsSell.spec.ts` | Extend | ~20 |
| `tests/Unit/Models/ItemSavingHookTest.php` | Create (recommended) | ~30 |

**Total estimated changed lines: ~243** (under 60% of the 400-line budget).
**Chained PRs needed: No**.

## Skill resolution

`paths-injected`. Loaded: `laravel-inertia/SKILL.md`, `vue-inertia/SKILL.md`, `_shared/sdd-phase-common.md`, `work-unit-commits/SKILL.md`.
