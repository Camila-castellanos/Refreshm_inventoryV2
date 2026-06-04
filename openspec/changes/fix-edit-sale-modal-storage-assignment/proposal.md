# Proposal: fix-edit-sale-modal-storage-assignment

> See explore findings at engram topic `sdd/fix-edit-sale-modal-storage-assignment/explore`
> (observation #5: "Bug surface is 3 controller locations, not 1").

## Why

When a sale is finalized as paid, each `Item` transitions to `SOLD`. The system
intentionally loses the active `storage_id` / `position` at that moment (a sold
item no longer occupies a physical slot) — but it must preserve the **last
physical location** as `sold_storage_id` / `sold_position` / `sold_storage_name`
so the Sold page (`resources/js/Pages/Inventory/Sold.vue:117`) can still show
"this device used to live in Storage X, position N". The Item `saving` boot
hook (`app/Models/Item.php:347-356`) is the single source of truth for that
snapshot: it copies the active fields into the `sold_storage_*` triplet **only
if `storage_id` is non-null at the moment the hook runs**, then nulls the
active fields.

Three code paths short-circuit that hook by passing `storage_id => null`
(directly in `create` data, or in the `update` payload) **before** the
snapshot block can fire, and a fourth path sends `position: ""` from the
frontend, which then trips backend validation. Net effect: any item added to
an already-paid sale (or created with a paid sale via the ItemsSell modal)
loses its historical location reference forever, and the Sold page shows `N/A`
even when the item physically existed in a storage unit.

This change restores the snapshot for the four affected paths and aligns the
ItemsSell and SaleEdit modals so they share a single, correct contract.

## What changes

Four surgical fixes, all governed by the user's pre-confirmed decisions
(SCOPE = Option 2, BUG B = B.1, snapshot pattern stays, hard rule: new items
in paid sales must have a real storage_id and position).

- **Bug A** — `app/Http/Controllers/SaleController.php:341-366`
  (`update` method, new-items branch). Replace
  `'position' => null, 'storage_id' => null` with auto-assignment
  via `Storage::findFirstAvailablePosition()` **before** marking the new
  item as SOLD. The Item `saving` hook then snapshots the real
  storage/position, then nulls the active fields. The Sold page reads
  the snapshot, not the active fields.

- **Bug B** — `app/Http/Controllers/SaleController.php:312-321`
  (`update` method, existing-items paid branch). Capture the snapshot
  explicitly **before** nulling the active fields, copying
  `storage_id → sold_storage_id`, `position → sold_position`, and
  `storage.name → sold_storage_name` — the exact same pattern already
  used at `app/Http/Controllers/SaleController.php:126-128`. The boot
  hook's idempotent guard (`! is_null($item->sold_storage_id)`) makes
  this safe to combine: the explicit copy wins, the hook no-ops.

- **Bug C** — `app/Http/Controllers/SaleController.php:170-222`
  (`store` method, new-items branch). Same shape as Bug A but in the
  `store` method that backs the `ItemsSell.vue` modal. Auto-assign
  storage and position via `Storage::findFirstAvailablePosition()` for
  paid sales; for unpaid sales, the existing reserved flow works once
  storage_id is set (the Item `creating` hook auto-fills `position`
  from `getNextAvailablePosition`).

- **Bug D** — `resources/js/Pages/Inventory/Modals/ItemsSell.vue:297`
  (`newRowTemplate`). Change `position: ""` to `position: null` so
  the payload passes the `SaleForm.php:44` rule (`'numeric|nullable'`)
  and matches the null-as-omitted contract used by the backend. The
  `Item` `creating` hook (`:370-377`) auto-assigns a real position when
  storage_id is set.

## Out of scope

- No UI changes. No new storage dropdown, picker, or modal redesign.
- Other SaleEdit modal bugs already identified by the user but deferred:
  - `Number(balance_remaining)` truncation / coercion in
    `resources/js/Pages/Accounting/Modals/SaleEdit.vue`
  - Hardcoded discount value in the same modal
  - Misleading `addItem` function name in SaleEdit.vue
  - No `newItems.*` validation in `SaleFormEdit.php`
- Refactoring the snapshot logic into a single shared helper. The
  existing precedent at `:126-128` is the helper for now; consolidation
  is a follow-up.
- Adding a storage picker to the new-item rows. A future change can
  surface `Storage::findFirstAvailablePosition()` to the user; for this
  PR the system continues to auto-assign.
- Pre-existing race conditions on `getNextAvailablePosition` /
  `findFirstAvailablePosition`. Not introduced by this change.
- `PaymentController::addNewItems` / `paid` / `update` and
  `AppendItemsToSaleAction` — verified correct at the explore phase;
  no change needed.

## Approach

### Bug A — `update` method, new-items branch (paid)

- **File / lines**: `app/Http/Controllers/SaleController.php:341-366`
- **Change shape**: At `:356-362`, when `$paid == 1`, call
  `Storage::findFirstAvailablePosition()` once before the `if/else`. If it
  returns `['storage_id' => X, 'position' => Y]`, set both keys on
  `$newItemData`. Do **not** set the `sold_storage_*` fields here — the
  boot hook does that after the SOLD transition. If it returns `null`
  (no storage rows exist or all are full), fall through to the existing
  null path so the item still saves; the snapshot is impossible in that
  case, and the Sold page will show `N/A` (existing behavior, not a
  regression).
- **Why not a model observer / dedicated service?**: A new observer or
  helper for a single field-set change is over-engineering. The
  precedent is inline assignment in the controller. Reuse it.
- **Edge case**: `Storage::findFirstAvailablePosition()` returns `null`
  when no storage row exists. The user can still complete a paid sale
  in that scenario today; we keep that behavior. Log a `warning` so
  the missing-storage condition is observable.

### Bug B.1 — `update` method, existing-items branch (paid)

- **File / lines**: `app/Http/Controllers/SaleController.php:312-321`
- **Change shape**: Mirror the `:126-128` pattern. Capture from the
  **in-memory** model (which is the pre-update state) before calling
  `->update()`:
  - `sold_storage_id` ← `$sale_item->storage_id`
  - `sold_position` ← `$sale_item->position`
  - `sold_storage_name` ← `$sale_item->storage?->name`
  Then call `->update([...])` with the original `position => null,
  storage_id => null` removed and the snapshot fields added to the
  payload. The boot hook's idempotent `! is_null(sold_storage_id)` check
  (`:349`) means re-saves are safe.
- **Why explicit copy over relying on the boot hook alone?**: Because
  the same `->update()` call also writes `position => null,
  storage_id => null`. Without the explicit copy the hook's own null
  guard (`:349`) sees `storage_id` and `position` as the **incoming**
  null values, but the snapshot fields were never assigned, so the
  `sold_storage_*` triplet stays null. The race is the bug.
- **Edge case**: `$sale_item` has no `storage_id` (e.g., was already
  sold, then re-edited). The `if (! is_null($item->storage_id) ...)`
  in the boot hook already handles this — nothing to snapshot because
  nothing was there. The new explicit copy must guard with
  `if (! is_null($sale_item->storage_id))` to avoid clobbering
  pre-existing snapshot data with a null.
- **Idempotency**: If `sold_storage_id` is already populated (item
  was sold before, just being re-edited), the explicit copy must NOT
  overwrite it with the current `storage_id` — which is null because
  the item is sold. Guard with
  `is_null($sale_item->sold_storage_id)`.

### Bug C — `store` method, new-items branch (paid)

- **File / lines**: `app/Http/Controllers/SaleController.php:170-222`
- **Change shape**: Same as Bug A. At `:191-199`, when `$request->paid`,
  resolve a real `storage_id` / `position` from
  `Storage::findFirstAvailablePosition()` and assign to `$itemData`.
  For the unpaid branch, do the same so the item reserves a real slot
  (the existing flow already works for items that arrive with
  `storage_id` set; the bug is the *omission* of the assignment).
- **Why this is identical to A and not a separate refactor**: The two
  branches live in two different methods (`store` vs `update`) and the
  call sites have different surrounding logic (create vs update). A
  shared helper would help long-term, but extracting it now means a
  bigger diff. Inline the call in both, log a follow-up issue.
- **Edge case**: Unpaid branch — the `Item::STATUS_RESERVED` transition
  needs a real `storage_id` to occupy. The `creating` hook
  (`app/Models/Item.php:370-377`) auto-fills `position` from
  `getNextAvailablePosition` once `storage_id` is set, so the controller
  only needs to set `storage_id`.

### Bug D — `ItemsSell.vue` newRowTemplate

- **File / lines**: `resources/js/Pages/Inventory/Modals/ItemsSell.vue:297`
- **Change shape**: `position: ""` → `position: null`. One-line change.
- **Why null over omitted**: Explicit null matches the contract used by
  every other row template in the modal (`storage_id: null`,
  `id: null`, etc.) and the backend's `nullable` validation.
- **Edge case**: `items.*.position` is `numeric|nullable` in
  `SaleForm.php:44` — `null` passes. `SaleFormEdit.php` does not have
  the rule, so the update path is unaffected by this Vue fix; the bug
  was store-side only.

## Affected files

- `app/Http/Controllers/SaleController.php` — +18 / -8 (store `:170-222`,
  update `:312-321`, update `:341-366`)
- `resources/js/Pages/Inventory/Modals/ItemsSell.vue` — +1 / -1 (`:297`)

Test files (new or extended):

- `tests/Feature/Controllers/SaleItemReservationTest.php` — extend with
  two new test cases (Bug A + Bug B) — ~80-110 lines added
- `tests/Feature/Controllers/SaleControllerStoreTest.php` — new file
  covering Bug C (store path with newItems + paid) — ~50-70 lines
- `resources/js/__tests__/pages/Inventory/Modals/ItemsSell.spec.ts` —
  extend with one assertion (Bug D) — ~15-25 lines added

## Risks and mitigations

- **Snapshot is the only historical location reference**. Once a sale
  is paid, the active `storage_id` is gone; the `sold_storage_*`
  fields are the **only** record. A regression here is permanent data
  loss. **Mitigation**: A PHP feature test MUST assert
  `sold_storage_id`, `sold_position`, and `sold_storage_name` are
  populated end-to-end for every bug (A, B, C).
- **The Item boot hook's snapshot block is only entered if
  `storage_id` is non-null at hook time** (`Item.php:349`). Any future
  controller that nulls `storage_id` before the SOLD transition
  re-introduces this bug silently. **Mitigation**: The new test for
  Bug B explicitly captures the `$sale_item` state before update; the
  pattern documentation lives in the comment at `:126-128` and will be
  re-referenced in the controller diff.
- **Boot hook idempotency (`! is_null(sold_storage_id)`)** is the
  safety net that lets the explicit copy at `:312-321` and the hook
  coexist. **Mitigation**: A unit test for `Item::saving` on a
  pre-sold item (re-save) MUST assert the snapshot is not clobbered
  with null. Add to `tests/Feature/Models/StorageOccupancyTest.php`
  or a new `tests/Unit/Models/ItemSavingHookTest.php`.
- **Auto-assign may surprise users** — selecting a new item in a paid
  sale will now place it in a storage slot the user did not pick.
  **Mitigation**: Documented in the Out-of-scope section as a
  follow-up UI change (explicit storage picker). The user has
  already accepted this trade-off for the current change.
- **Fix to `newRowTemplate.position`** is a contract change. If any
  other component or test asserted `position === ""`, it will break.
  **Mitigation**: Search the codebase for `position: ""` outside
  `ItemsSell.vue:297` before apply; the explore phase did not find any
  other occurrence, but the apply phase MUST re-verify.
- **`Storage::findFirstAvailablePosition()` returning `null`** (no
  storage rows, or all full) is a graceful-degrade path, not an
  error. The item still saves without a snapshot. **Mitigation**: Log
  a `warning` event with the user_id and item count so the missing
  storage condition is observable in production.
- **Race condition on `getNextAvailablePosition` /
  `findFirstAvailablePosition`**: these are not transactional; two
  concurrent sales can claim the same position. **Mitigation**: This
  is a pre-existing condition; not introduced by this change.
  Documented as a follow-up; a unique index on
  `(storage_id, position)` for non-null positions is the durable
  fix.

## Open questions

None — all product questions resolved by the user before this phase.

## Test plan

- **Bug A** — Extend `tests/Feature/Controllers/SaleItemReservationTest.php`
  with `test_update_paid_sale_with_new_item_populates_sold_storage_snapshot`.
  Setup: paid sale, one new item posted to `update()` with empty
  `storage_id` / `position`. Assert: `Item::create` was called (or the
  item exists in DB), `status = SOLD`, `storage_id` and `position` are
  null, AND `sold_storage_id` / `sold_position` /
  `sold_storage_name` are populated with the auto-assigned values.
- **Bug B** — Same test file, add
  `test_update_paid_sale_existing_item_populates_sold_storage_snapshot`.
  Setup: paid sale with an existing item that has a real `storage_id`
  and `position`. Post to `update()` and assert the snapshot fields are
  populated post-update. A second sub-case: pre-sold item re-edited —
  assert snapshot is not clobbered.
- **Bug C** — New file
  `tests/Feature/Controllers/SaleControllerStoreTest.php`. Test
  `test_store_paid_sale_with_new_items_populates_sold_storage_snapshot`.
  Post to `store()` with `paid = 1` and a `newItems` array. Assert
  each new item has the snapshot populated. Mirror test for `paid = 0`
  to assert `storage_id` is set and `position` is auto-assigned (the
  unpaid branch).
- **Bug D** — Extend
  `resources/js/__tests__/pages/Inventory/Modals/ItemsSell.spec.ts`
  with an assertion on the exported / observable template. The
  current spec already mounts the modal; add a snapshot of
  `newRowTemplate` if accessible, or assert the new-row's submitted
  payload position is `null` (not `""`).

A unit test for the Item `saving` hook
(`tests/Unit/Models/ItemSavingHookTest.php` or
`tests/Feature/Models/StorageOccupancyTest.php` extension) is
**strongly recommended** to lock in the idempotency contract
(`sold_storage_id` is never overwritten with null on re-save). Not
strictly required by the bugs, but cheap insurance.

## Review workload forecast

- Controller changes: +18 / -8 = **26 net changed lines** in PHP
- Vue change: +1 / -1 = **2 net changed lines**
- Test additions: ~150-205 lines across 2-3 files

**Total estimated changed lines: ~180-230** (well under the 400-line
budget).

- **Chained PRs recommended: No** — a single PR is small enough to
  review end-to-end in one pass.
- **400-line budget risk: Low** — even with generous test coverage the
  delta stays under 60% of the budget.
- **Decision needed before apply: No** — all product questions are
  resolved. The apply phase can proceed directly to implementation.

## Skill resolution

`paths-injected` — orchestrator provided exact paths. Loaded:

- `/Users/johiny/Code_Library/Refreshm_inventoryV2/.atl/skills/laravel-inertia/SKILL.md`
- `/Users/johiny/Code_Library/Refreshm_inventoryV2/.atl/skills/vue-inertia/SKILL.md`
- `/Users/johiny/.config/opencode/skills/_shared/sdd-phase-common.md`
  (plus its companions: `openspec-convention.md`,
  `persistence-contract.md`, `skill-resolver.md`)

## Capabilities

This change is a bug fix. No new capability is introduced; no
existing capability's requirements change at the spec level. The
relevant behavior is owned by the `sales` capability (to be
specified by the sdd-spec phase if not yet defined).

### New Capabilities
None.

### Modified Capabilities
- `sales` (if the spec exists) — clarify that the
  `sold_storage_*` snapshot MUST be populated for any item that
  transitions to SOLD, regardless of whether the item is being
  created or updated. If no `sales` spec exists yet, the
  sdd-spec phase will create one and seed it with this contract.
