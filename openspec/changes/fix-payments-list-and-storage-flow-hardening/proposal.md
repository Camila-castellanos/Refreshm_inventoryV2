# Proposal: fix-payments-list-and-storage-flow-hardening

> See explore findings at engram topic
> `sdd/fix-add-items-to-sale-missing-latest-sales/explore` (observation #15) and
> decisions at `sdd/fix-add-items-to-sale-missing-latest-sales/clarifications`
> (observation #16). The engram topic key stays as the legacy name for
> continuity even though this change directory uses a broader name.
>
> The canonical spec lives at `openspec/specs/sales/spec.md` (seeded by the
> previous change `fix-edit-sale-modal-storage-assignment`, commit `d913ce6`).
> This change is a delta against that spec — no new capability.

## Why

The previous SDD change (`d913ce6`) extended the storage-snapshot contract so
items transitioning to SOLD preserve a `sold_storage_*` triplet for the Sold
page. That change introduced two new code paths that rely on
`Storage::findFirstAvailablePosition()` to auto-assign slots: `store`
(`SaleController.php:201-212`) and `update` new-items loop
(`SaleController.php:388-410`). It also created a UX consistency gap
(`getPaymentsSimpleList` now filters out sales the `AddItemsToSale` modal
needs to see) and left the auto-assign path fragile in three distinct ways.

This change closes those soft edges:

1. The `getPaymentsSimpleList` filter at `PaymentController.php:613-614`
   drops RESERVED-only sales, breaking customer search for unpaid sales in
   the `AddItemsToSale.vue` and `AppendIncomingRequestToSale.vue` modals.
2. `Storage::findFirstAvailablePosition()` is a read-then-write that is
   not protected by a unique-index defense at the controller level (the
   `items_storage_position_unique` index does protect the DB, but a
   collision surfaces as a 500 today). Concurrent store/update calls can
   trip the unique constraint and produce an opaque error.
3. The `update` method runs existing-items before new-items in two
   separate loops. The order is load-bearing — the new-items loop calls
   `findFirstAvailablePosition()` and must see just-persisted
   existing-items — but the assumption is implicit. A future refactor
   could break it silently.
4. When no storage is available, `store`/`update` silently log a warning
   and save the item with null `storage_id` / `position`. The frontend
   never learns that auto-assign was skipped. Users see a "successful"
   sale with no storage trail.

## What changes

Four hardening items, all pre-confirmed by the user (see engram #16).

### Item 1 — `getPaymentsSimpleList` filters out RESERVED-only sales

- **File**: `app/Http/Controllers/PaymentController.php:613-614`
- **Change**: Remove the
  `->whereHas('items', fn ($q) => $q->whereNotNull('sold'))` clause. The
  outer query already constrains to sales-with-items via `->with('items')`
  consumption; removing the inner filter lets RESERVED-only sales through
  naturally. The customer search branch (`:617-623`) starts matching
  unpaid-only sales as a side effect.
- **Affects two modals**: `AddItemsToSale.vue` and
  `AppendIncomingRequestToSale.vue` both call this endpoint and both
  depend on RESERVED-only sales being visible.

### Item 2 — Race condition on `Storage::findFirstAvailablePosition` (PRAGMATIC)

- **Files**: `app/Http/Controllers/SaleController.php:201-212` (store),
  `:388-410` (update)
- **Change**: Wrap the `Item::create($newItemData)` call in
  `try { ... } catch (QueryException $e) { ... }`. On SQLSTATE 23000,
  retry once with a fresh `Storage::findFirstAvailablePosition()` call.
  On a second failure, return HTTP 422 with body
  `{"error": "Storage is being modified concurrently, please retry."}`.
- **Why pragmatic and not durable**: The durable fix
  (`DB::transaction` + `lockForUpdate` on the storage row, mirroring
  `AppendIncomingRequestToInvoiceService:27-48`) is explicitly deferred.
  The unique index `items_storage_position_unique`
  (`database/migrations/2025_02_20_125615_add_storages_fields_to_items_table.php:20`)
  is the real defense; the catch-and-retry just hides the common case
  from the user.
- **Affects two callsites**: Apply the same shape to both `store` and
  `update` new-items loops.

### Item 3 — Load-bearing loop order in `SaleController::update` (COMMENT-ONLY)

- **Files**: `app/Http/Controllers/SaleController.php:322-365` (existing-items
  loop), `:369-411` (new-items loop)
- **Change**: Add a comment in the code explaining the load-bearing
  assumption: existing-items must run before new-items because
  `findFirstAvailablePosition` reads the DB and the new-items loop needs
  to see the just-persisted existing-items' positions. No code change,
  no test.
- **Why comment-only**: The order is not strictly required for
  correctness today (the intra-batch read-after-write works because
  neither loop is wrapped in a transaction). The assumption is implicit.
  A future refactor that wraps both loops in a single transaction OR
  reorders them could break it. A comment preserves the knowledge.

### Item 4 — Silent fallback when no storage (BREAKING response shape)

- **Files**:
  - Backend: `SaleController.php:201-212` (store fallback),
    `:388-410` (update fallback), `:242` (store response),
    `:435` (update response)
  - Frontend: `resources/js/Pages/Inventory/Modals/ItemsSell.vue:413-436`
    (response handler),
    `resources/js/Pages/Accounting/Modals/SaleEdit.vue:435-475` (response
    handler)
- **Change**:
  - In both `store` and `update`, accumulate per-item warnings when
    `Storage::findFirstAvailablePosition()` returns null. Warning shape:
    `{"item_id": <id>, "model": <model>, "type": <type>, "reason": "no_storage_available"}`
    (per-item is the recommended shape; richer than flat reason codes).
  - Change `store` response from
    `return response()->json($receiptUrl, 201)` to
    `return response()->json(['url' => $receiptUrl, 'warnings' => $warnings], 201)`.
  - Change `update` response at `:435` from
    `return response()->json($request, 201)` to
    `return response()->json(['request' => $request, 'warnings' => $warnings], 201)`
    OR (preferred, breaking) `return response()->json([...request fields..., 'warnings' => $warnings], 201)`.
    The sdd-spec phase will choose between preserving the request-echo
    contract or migrating to a cleaner shape. **TODO sdd-spec**:
    confirm response shape for `update` since current consumer
    (`SaleEdit.vue:461`) only checks `response.status`, so it is
    forward-compatible with any object shape.
  - Update `ItemsSell.vue:415-426` to read `data.url` instead of
    `data` as a string, and to call `useToast` warn for each
    `data.warnings` entry.
  - Update `SaleEdit.vue:461` to read `response.data.warnings` and
    toast each entry with severity `warn`.
- **Convention precedent**: `getPaymentsData` at
  `PaymentController.php:651` already returns warnings-shape data; the
  precedent is established in this codebase.

## Out of scope

- The durable race fix (`DB::transaction` + `lockForUpdate` on the
  storage row) — tracked as a follow-up. This change uses the pragmatic
  catch-and-retry as a partial defense only.
- Other SaleEdit modal bugs: `Number(balance_remaining)` truncation,
  hardcoded `discount`, misleading `addItem` function name, missing
  `newItems.*` validation in `SaleFormEdit.php`. All remain follow-ups
  from the previous change.
- Refactoring the existing-items and new-items loops into
  `processExistingItems()` / `processNewItems()` helpers. The
  comment-only fix is sufficient for this change.
- Ecommerce-channel scope: the `exclude_ecommerce` global scope stays
  as-is.
- Date format search: `2026/06/04` LIKE dead-end — left as a UX
  nice-to-have.
- `LIMIT=25` cap on `getPaymentsSimpleList`: unchanged.

## Approach

### Item 1 — `getPaymentsSimpleList` filter

- **File / lines**: `app/Http/Controllers/PaymentController.php:613-614`
- **Code shape**:
  - Before: `->whereHas('items', fn ($q) => $q->whereNotNull('sold'));`
  - After: delete the line. The endpoint already requires
    `->with('items')` to map the first item, so sales with no items
    are naturally excluded by the rendering logic at `:632-639`.
- **Why not keep the filter and add an OR branch for RESERVED**: It
  would double the query complexity. The previous change's Sold-page
  display fix (see `archive.md` for `fix-edit-sale-modal-storage-assignment`)
  already covers the "this is a RESERVED item, show its current
  location" UX path. Surfacing RESERVED-only sales in the simple list
  is the correct behavior; the filter was an over-correction.
- **Edge cases**: Sales with zero items will not match
  `->get()` results because the `firstItem` mapping at `:633` would
  NPE. With the filter removed, the existing `->with('items')` is
  still a constraint because sales-with-items is what the
  `AddItemsToSale` modal searches. The apply phase MUST verify that
  the simple-list endpoint does not return sales with zero items; if
  it does, add a `->has('items')` constraint to preserve the
  invariant.

### Item 2 — Pragmatic race catch-and-retry

- **Files / lines**: `SaleController.php:214` (store create),
  `:410` (update create)
- **Code shape** (apply to BOTH callsites):
  ```php
  try {
      $item = Item::create($newItemData);
  } catch (QueryException $e) {
      if ($e->getCode() !== '23000') {
          throw $e; // not a unique violation — let it surface
      }
      // Retry once with a fresh position
      $storageSlot = Storage::findFirstAvailablePosition();
      if ($storageSlot === null) {
          return response()->json(
              ['error' => 'Storage is being modified concurrently, please retry.'],
              422
          );
      }
      $newItemData['storage_id'] = $storageSlot['storage_id'];
      $newItemData['position'] = $storageSlot['position'];
      try {
          $item = Item::create($newItemData);
      } catch (QueryException $e2) {
          return response()->json(
              ['error' => 'Storage is being modified concurrently, please retry.'],
              422
          );
      }
  }
  ```
- **Why catch SQLSTATE 23000 specifically**: It is the unique
  constraint violation class. Other QueryException subclasses (FK
  violations, deadlocks with code 40001) need different handling; the
  pragmatic fix targets the storage-collision case only.
- **Why 422 (not 500)**: 422 is the right code for "request is
  unprocessable because the system state conflicts". 500 would be
  misleading — the client did nothing wrong.
- **Why this is partial**: It hides the common case but does not
  prevent the collision. Two concurrent stores will still race; the
  second one re-reads and re-tries. If both retries pick the same
  slot, the user gets a 422. The durable fix is `DB::transaction` +
  `lockForUpdate` on the storage row, mirroring
  `AppendIncomingRequestToInvoiceService:27-48`. Tracked as a
  follow-up.
- **Edge cases**:
  - `Item::create` succeeds but a different QueryException fires
    later in the same loop iteration (e.g., FK violation on
    `customer_id`): the catch MUST re-throw. Guard with
    `$e->getCode() !== '23000'` check.
  - The retry may still pick the same position if the conflicting
    INSERT has not yet committed. The unique index will reject it
    again. The second `catch` returns 422. Acceptable behavior for
    the pragmatic fix.

### Item 3 — Loop-order comment

- **File / lines**: `SaleController.php:321` (between the two loops)
- **Code shape**: Insert a comment block before `:367`:
  ```php
  // LOAD-BEARING ORDER: existing-items loop MUST run before the
  // new-items loop. The new-items loop calls
  // Storage::findFirstAvailablePosition(), which reads the items
  // table for occupancy. If existing items are persisted AFTER new
  // items, the new-items loop will pick positions that collide
  // with the just-persisted existing items.
  // This is safe today only because neither loop is wrapped in a
  // transaction. A future refactor that wraps both loops in a
  // single transaction OR reorders them MUST verify that
  // findFirstAvailablePosition is re-called after each existing
  // item is persisted, OR call it inline per-iteration.
  ```
- **Why no test**: There is no behavior change. The comment is
  documentation. A test would test the absence of a regression that
  has not happened yet.
- **Edge case**: If a future refactor DOES break the order, the
  symptom is unique-constraint violations on
  `items_storage_position_unique`. The Item 2 catch-and-retry will
  catch the second-occurrence case but not the first. The Item 2
  durable follow-up will fix this properly.

### Item 4 — Visible no-storage warnings

- **Files / lines**:
  - `SaleController.php:201-212` (store fallback — accumulate into
    `$warnings[]`)
  - `SaleController.php:388-410` (update fallback — same)
  - `SaleController.php:242` (store response — shape change)
  - `SaleController.php:435` (update response — add warnings key)
  - `ItemsSell.vue:415-426` (read `data.url`, toast `data.warnings`)
  - `SaleEdit.vue:461-473` (toast `response.data.warnings`)
- **Code shape** (store, `:201-212`):
  ```php
  $warnings = [];
  $storageSlot = Storage::findFirstAvailablePosition();
  if ($storageSlot !== null) {
      $itemData['storage_id'] = $storageSlot['storage_id'];
      $itemData['position'] = $storageSlot['position'];
  } else {
      $warnings[] = [
          'item_id' => null, // new item has no id yet
          'model' => $new_item['model'] ?? null,
          'type' => $new_item['type'] ?? null,
          'reason' => 'no_storage_available',
      ];
      Log::warning('No storage available for new sale item', [
          'user_id' => Auth::id(),
          'sale_id' => $sale->id,
      ]);
  }
  ```
  Repeat in update with `$item['id']` (existing payload may carry it
  for newItems? — verify in apply).
- **Code shape** (store response, `:242`):
  ```php
  return response()->json([
      'url' => $receiptUrl,
      'warnings' => $warnings,
  ], 201);
  ```
- **Code shape** (update response, `:435`):
  ```php
  return response()->json([
      'warnings' => $warnings,
  ], 201);
  ```
  The `update` endpoint currently echoes the full `$request` object,
  which is a leaky abstraction. This change does NOT fix that
  long-standing leak — only adds the `warnings` key. The full fix
  is a follow-up.
- **Code shape** (ItemsSell.vue, `:415-426`):
  ```ts
  const { data } = await axios.post<{ url: string; warnings: Array<{...}> }>(route("sales.store"), salePayload);
  const link = document.createElement("a");
  link.href = data.url;
  // ... rest unchanged
  if (data.warnings?.length) {
      data.warnings.forEach(w => {
          toast.add({
              severity: "warn",
              summary: "Storage warning",
              detail: `Item ${w.model ?? ''} could not be auto-assigned to storage.`,
              life: 5000,
          });
      });
  }
  ```
- **Code shape** (SaleEdit.vue, `:461`):
  ```ts
  const response = await axios.post(route("sales.update"), sale);
  if (response.status >= 200 && response.status < 400) {
      const warnings = response.data?.warnings ?? [];
      warnings.forEach(w => {
          toast.add({
              severity: "warn",
              summary: "Storage warning",
              detail: `Item ${w.model ?? ''} could not be auto-assigned to storage.`,
              life: 5000,
          });
      });
      // ... rest unchanged
  }
  ```
- **Why per-item shape over flat reason codes**: The user-confirmed
  decision (engram #16) is per-item with `{item_id, model, type,
  reason}`. Per-item is more useful for the toast UX (the user can
  identify which item was not stored) and for future log analysis.
  Flat codes (e.g., `['no_storage_available']`) would require an
  additional query to know which items were affected.
- **Edge cases**:
  - `update` response currently echoes `$request` (the validated
    form data). Consumers like `SaleEdit.vue:461` only check
    `response.status`, so adding a `warnings` key is forward-compatible.
  - If `Item::create` fires the Item 2 catch (race), the retry may
    succeed and produce a normal `Item` with valid `storage_id`.
    The warnings array is for the "no_storage_available" case only;
    race-retry 422s use a different response shape (the 422 error
    envelope).
  - The `item_id` field is `null` for new items because the row
    has not been inserted yet. Frontend toasts should fall back to
    `model` / `type` for display.

## Affected files

- `app/Http/Controllers/PaymentController.php` — Item 1, **-2 / 0** lines
  (delete the `whereHas` filter)
- `app/Http/Controllers/SaleController.php` — Item 2 (+30 lines of
  try/catch per callsite, x2 = +60), Item 3 (+10 lines comment),
  Item 4 (+30 lines of `$warnings` accumulation, response shape).
  Net: **+100 / -2** lines
- `resources/js/Pages/Inventory/Modals/ItemsSell.vue` — Item 4,
  **+15 / -5** lines (read `data.url`, toast warnings)
- `resources/js/Pages/Accounting/Modals/SaleEdit.vue` — Item 4,
  **+15 / -5** lines (toast warnings)

Test files (new or extended):

- `tests/Feature/Payments/PaymentSimpleListVisibilityTest.php` — NEW,
  ~80 lines. Covers Item 1.
- `tests/Feature/Controllers/SaleItemReservationTest.php` — EXTEND,
  +60 lines. Covers Item 2 (race catch) and Item 4 (warnings payload).

**Total estimated changed lines: ~280-340** (PHP + Vue + tests).

## Risks and mitigations

- **Item 2 race fix is partial; durable is a follow-up**. Two
  concurrent stores can still race; the second one re-reads and
  re-tries. If both retries pick the same slot, the user gets a
  422. The unique index `items_storage_position_unique` is the real
  defense at the DB level. The catch-and-retry is a UX win only.
  **Mitigation**: Tracked as a follow-up. A test in this change
  asserts the catch-and-retry fires on a simulated collision.
- **Item 4 store response is a breaking change**.
  `ItemsSell.vue:415-426` types the response as `string` and uses it
  as a link `href`. Any other consumer of `sales.store` that
  assumes a string will break. **Mitigation**: A search of the
  codebase for callers of `sales.store` MUST be done in the apply
  phase. The only known caller is `ItemsSell.vue`; if another is
  found, it must be migrated in the same PR.
- **Item 4 warnings shape (per-item vs flat) is a design decision**.
  Per-item is more useful for UX but a future flat-reason consumer
  will break. **Mitigation**: Document the shape in the spec
  (sdd-spec phase). The shape is consistent with the existing
  warnings precedent in this codebase.
- **Item 3 comment-only fix is fragile to refactors**. A future
  refactor that wraps both loops in a transaction OR reorders them
  will break the read-after-write assumption silently. **Mitigation**:
  The comment makes the assumption explicit. The Item 2 catch will
  catch second-occurrence collisions. The durable fix (Item 2
  follow-up) will catch all.
- **Item 1 filter removal may surface sales with zero items**. If
  `getPaymentsSimpleList` returns a sale with no items, the
  `firstItem` mapping at `PaymentController.php:633` NPEs.
  **Mitigation**: Apply phase MUST verify by searching for sales
  with zero items. If the schema allows them, add
  `->has('items')` to preserve the invariant.
- **Item 2 retry may still collide**. Two concurrent stores that
  BOTH retry could pick the same fresh position. The unique index
  rejects the second. The 422 is acceptable for the pragmatic fix.
  **Mitigation**: Documented in the catch's response message ("please
  retry"). Durable fix is the follow-up.
- **Item 4 update response shape** is currently a leaky
  `response()->json($request, 201)`. This change adds a
  `warnings` key but does NOT fix the leak. **Mitigation**:
  Documented in the approach. Long-standing leak, follow-up.

## Open questions

None — all product questions resolved by the user before this phase.
The durable race fix is explicitly a follow-up.

## Test plan

- **Item 1** — New
  `tests/Feature/Payments/PaymentSimpleListVisibilityTest.php`:
  - `test_simple_list_includes_all_sold_sale`. Create a sale with
    one SOLD item, assert it appears in the response.
  - `test_simple_list_includes_all_reserved_sale`. Create a sale
    with one RESERVED item, assert it appears in the response. This
    is the regression test for Item 1.
  - `test_simple_list_includes_mixed_sale`. Create a sale with
    one SOLD and one RESERVED item, assert it appears once in the
    response.
  - `test_simple_list_customer_search_finds_unpaid_only_sale`.
    Create a sale with one RESERVED item and a customer name "X",
    search for "X", assert the sale appears. This is the
    UX-fixing assertion.
  - Maps to spec scenarios (sdd-spec phase to add):
    "Sale appears in getPaymentsSimpleList regardless of item
    status", "Customer search matches RESERVED-only sales".

- **Item 2** — Extend
  `tests/Feature/Controllers/SaleItemReservationTest.php`:
  - `test_store_retries_on_storage_unique_violation`. Mock
    `Storage::findFirstAvailablePosition` via a partial mock to
    return a colliding position first call, a free position
    second call. Wrap a real `Item::create` between the two calls
    to pre-occupy the first position. Assert no 500 escapes and
    the response is 201.
  - `test_update_retries_on_storage_unique_violation`. Mirror for
    the update path.
  - `test_store_returns_422_on_double_collision`. Mock
    `findFirstAvailablePosition` to return the SAME colliding
    position both times. Assert the response is 422 with the
    "concurrent modification" message.
  - Maps to spec scenarios (sdd-spec phase to add):
    "Concurrent storage claim triggers one retry, second
    collision returns 422".

- **Item 3** — No new test. The comment is documentation only. A
  test would test the absence of a regression that has not
  happened. The sdd-spec phase will note the load-bearing order
  contract in a requirement scenario.

- **Item 4** — Extend
  `tests/Feature/Controllers/SaleItemReservationTest.php`:
  - `test_store_response_includes_warnings_when_no_storage`.
    Create a `Storage` table with zero rows (or all full — verify
    in apply), post to `store` with a new item, assert the
    response is 201 and body is `{url: string, warnings:
    Array<{reason: 'no_storage_available'}>}`. Also assert the
    item is persisted with `storage_id` null.
  - `test_update_response_includes_warnings_when_no_storage`.
    Mirror for the update path. Assert body includes `warnings`
    key.
  - `test_update_response_keeps_warnings_key_empty_when_storage_available`.
    Assert the `warnings` key is `[]` in the normal case.
  - Maps to spec scenarios (sdd-spec phase to add): "Sale
    response includes warnings when auto-assign fails", "Sale
    response is shape `{url, warnings}` for store, `{warnings}`
    for update".

## Review workload forecast

- PHP changes: `PaymentController` -2 lines; `SaleController` +100 / -2.
  **Net PHP: +98 lines**.
- Vue changes: `ItemsSell.vue` +10 lines, `SaleEdit.vue` +10 lines.
  **Net Vue: +20 lines**.
- Test additions: ~140 lines across 2 files.
- **Total estimated changed lines: ~280-340** (well under the 400-line
  budget).

- **Chained PRs recommended: No** — a single PR is small enough to
  review end-to-end in one pass. Item 2 and Item 4 are both
  `SaleController` changes; splitting them would create artificial
  coupling.
- **400-line budget risk: Low** — even with generous test coverage the
  delta stays under 85% of the budget. The follow-up durable race
  fix will need its own PR.
- **Decision needed before apply: No** — all product questions are
  resolved. The sdd-spec phase will confirm the response shape for
  `update` (the `warnings` key is non-breaking because
  `SaleEdit.vue:461` only checks `response.status`).

## Skill resolution

`paths-injected` — orchestrator provided exact paths. Loaded:

- `/Users/johiny/Code_Library/Refreshm_inventoryV2/.atl/skills/laravel-inertia/SKILL.md`
- `/Users/johiny/Code_Library/Refreshm_inventoryV2/.atl/skills/vue-inertia/SKILL.md`
- `~/.config/opencode/skills/_shared/sdd-phase-common.md`
  (plus its companions: `openspec-convention.md`,
  `persistence-contract.md`, `skill-resolver.md`)

## Capabilities

This change extends the existing `sales` capability
(`openspec/specs/sales/spec.md`, seeded by
`fix-edit-sale-modal-storage-assignment`). It does not introduce a new
capability.

### New Capabilities
None.

### Modified Capabilities
- `sales` — add requirements for:
  - `getPaymentsSimpleList` MUST surface all sales with at least
    one item regardless of item status.
  - `SaleController::store` and `update` MUST retry once on a
    storage unique-constraint violation (SQLSTATE 23000) and return
    422 on a second failure.
  - `SaleController::update` MUST run the existing-items loop
    before the new-items loop (load-bearing for auto-assign
    correctness in the absence of a transaction wrapper).
  - `SaleController::store` response shape MUST be
    `{url: string, warnings: Array<{item_id, model, type, reason}>}`.
  - `SaleController::update` response shape MUST include
    `warnings: Array<{item_id, model, type, reason}>`.
  - The `warnings` array MUST be empty when auto-assign succeeded
    for every item.

  Each will be a delta in
  `openspec/changes/fix-payments-list-and-storage-flow-hardening/specs/sales/spec.md`.
