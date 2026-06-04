# Design: fix-payments-list-and-storage-flow-hardening

## Overview

Four focused hardening fixes against the existing `sales` capability: (1) remove a `whereHas(items, sold)` filter that hides RESERVED-only sales from `getPaymentsSimpleList`, (2) wrap the new-items `Item::create` call in `SaleController::store` and `update` in a try/catch on SQLSTATE 23000 with one retry + 422 on second failure, (3) add a code comment above the `update` new-items loop explaining the load-bearing loop order, and (4) accumulate per-item `no_storage_available` warnings and surface them in a new `{url, warnings}` response shape (breaking for `sales.store`) plus a non-breaking `warnings` key in the `update` response, with frontend toast wiring. **No new infrastructure**: mirrors the response-shape and toast conventions already in use at `PaymentController.php:115` and the warning-tolerance already implemented in `Item::saving` (`Item.php:347-356`). Durable transaction fix is a follow-up.

## Architecture decisions

### AD-1: Pragmatic race protection vs durable transaction

| Option | Tradeoff | Decision |
| --- | --- | --- |
| `try/catch` QueryException SQLSTATE 23000 + retry once + 422 on second failure | Minimal diff, no transaction boundary changes, the unique index at the DB is the real defense | **Chosen** |
| `DB::transaction` + `lockForUpdate` on the storage row | Durable, mirrors `AppendIncomingRequestToInvoiceService:27-48` | Rejected (explicit follow-up) |

**Rationale**: The `items_storage_position_unique` index (`database/migrations/2025_02_20_125615_add_storages_fields_to_items_table.php:20`) is the real defense. The catch-and-retry hides the common case from the user. Wrapping in a transaction today would mean committing the new `update` flow shape to a new durable model, which is a separate change with its own edge cases (lock-skip deadlock, lock timeout, lost connection mid-loop). The pragmatic fix delivers the visible UX win (no 500 in the common case) without forcing a transaction refactor on the existing two-loop structure.

### AD-2: Loop order documented in code, not enforced by a helper

| Option | Tradeoff | Decision |
| --- | --- | --- |
| Add a 5-8 line comment block above the new-items loop explaining the load-bearing assumption | Preserves knowledge with minimal diff; survives but is fragile to refactors | **Chosen** |
| Extract `processExistingItems()` / `processNewItems()` helpers | Forces the order into a typed signature; bigger refactor | Rejected (out of scope; previous change explicitly kept loops inline) |
| Wrap both loops in a single transaction with `findFirstAvailablePosition` called inline per-iteration | Makes the order provably correct, not just by convention | Rejected (overlaps with AD-1 durable fix) |

**Rationale**: The order is not actually fragile today — there is no transaction wrapper, so each `Item::update` / `Item::create` commits before the next `findFirstAvailablePosition` reads. The risk is purely in a FUTURE refactor that wraps both loops in a single transaction OR reorders them. A comment preserves the knowledge; the regression test (R7.S1) exercises the contract. Helper extraction is a follow-up — the previous change's design kept the loops inline on purpose.

### AD-3: Warnings shape — per-item array, divergent from the existing flat convention

**Choice**: `{"item_id": <id|null>, "model": ..., "type": ..., "reason": "no_storage_available"}` per warning.

**Alternatives considered**: flat `['no_storage_available']` (matches `getPaymentsData:115` / `getPaymentsSimpleList:646` but loses item context).

**Rationale**: The per-item shape gives the UI enough to render "Item X (model Y, type Z) couldn't be assigned" instead of just "Some items couldn't be assigned". The divergence from the flat shape is documented in code comments at both the new call sites (`SaleController::store` fallback, `SaleController::update` fallback) AND the existing flat call site at `getPaymentsData:115` (a one-line comment: "This endpoint uses the flat shape; per-item shapes elsewhere in this controller"). Future consumers expecting flat reason codes need a translation layer; that is a follow-up, not a blocker.

### AD-4: Breaking change in `sales.store` response shape

**Choice**: change `return response()->json($receiptUrl, 201)` (line `:242`) to `return response()->json(['url' => $receiptUrl, 'warnings' => $warnings ?? []], 201)`. Migrate `ItemsSell.vue:415-426` to read `data.url` and toast each `data.warnings` entry.

**Alternatives considered**: keep `$receiptUrl` as the body and add a separate `X-Warnings` header (rejected — JSON-only convention in this controller); deprecate `sales.store` and add `sales.store.v2` (rejected — only one consumer, no need to maintain two endpoints).

**Rationale**: The previous design's response shape leaked the receipt URL as a raw string. With the warnings channel now required, the body must be an object. The apply phase MUST grep for all callers of `sales.store` to confirm `ItemsSell.vue` is the only consumer. The `update` response is non-breaking — `SaleEdit.vue:461` only checks `response.status`, so adding a `warnings` key is forward-compatible.

### AD-5: User override — single commit at the end

**Choice**: per the previous change's pattern (commit `d913ce6`), implement all 4 items + tests in the working tree with NO intermediate commits, run the suite green, pause for user manual verification, then make ONE conventional commit. No chained PRs.

**Alternatives considered**: 4 work-unit commits (one per item) — rejected because the items are tightly coupled (Item 2's try/catch is inside the same loop as Item 4's `$warnings` accumulation, and Items 2+4 both touch the `update` new-items loop); splitting would create artificial coupling and orphan lines. Single-PR without test-driven commits was the user-confirmed pattern from the previous change.

**Rationale**: The previous change's review (`d913ce6`) confirmed that the per-item split created more review burden than it saved. The 4 items in this change have higher coupling than the previous change's items (which were independent snapshot fixes). A single commit keeps the diff reviewable end-to-end. The 400-line budget is not at risk (see "Affected lines summary" below).

### AD-6: Payments page date source is `sales.date` (user-verified during T15)

**Choice**: change the `$sold` calculation in `PaymentController::getPaymentsData:815` from `Carbon::parse($firstItem->sold ?? $firstItem->partially_sold_at ?? $sale->created_at)` to `Carbon::parse($sale->date ?? $firstItem->sold ?? $firstItem->partially_sold_at ?? $sale->created_at)`. This aligns the Payments page with the simpleList endpoint (which already uses `$sale->date`) so the same sale shows the same date in both views.

**Discovered during T15**: the user reported that the latest sale of customer "The Fix Burleson" appeared as `2026-06-04` on the Payments page but `2026-06-02` in the simpleList dropdown. Root cause: the Payments page derived the date from the item's `partially_sold_at` (set when the item became RESERVED = creation time), while the simpleList returned the user-picked `sales.date`. Same sale, two views, two different dates.

**Alternatives considered**: keep both views using the item-based date and align simpleList to it (rejected — `$sale->date` is the user-picked "this sale happened on" date, which is the more semantically correct display); make both views show `created_at` (rejected — doesn't reflect user input at all).

**Rationale**: `$sale->date` is the user-picked payment date in the form. It is the more semantically correct display ("this sale is dated X" where X is what the user typed). The fallback chain (`sold → partially_sold_at → created_at`) is preserved for safety in case `sales.date` is ever null (which it isn't in practice — the controller always sets it on create and update). The new test `test_payments_page_uses_sale_date_not_partially_sold_at` in `PaymentCrudTest.php` locks the contract.

### AD-7: Edit Sale modal initial date is `sales.date`, not `items[0].sold` (user-verified during T15 second pass)

**Choice**: change `SaleEdit.vue:340` from `form.value.date = new Date(itemsResponse.data[0].sold || new Date())` to parse `payment.value.date` (which the Payments page now correctly sources from `sales.date` per AD-6) into the form's date. The parsing splits the `yyyy-MM-dd` string into year/month/day and constructs the Date in local time (not UTC) so the DatePicker does not shift the displayed date by one day for users in negative-UTC timezones.

**Discovered during T15 second pass**: the user opened the Edit Sale modal for the unpaid sale (where `items[0].sold` is null). The modal showed TODAY (the `new Date()` fallback) instead of the sale's actual date. Root cause: same date-source confusion as AD-6 but on the Vue side. The previous code took `items[0].sold` (which is null for RESERVED items) and fell back to `new Date()`. The fix uses `payment.value.date` which now reflects `sales.date`.

**Alternatives considered**: change the Payments page response to also include `sales.date` as a separate field (rejected — the `date` field is now correctly set per AD-6, no need for a duplicate); fetch the sale data from a new endpoint (rejected — over-engineering for one field).

**Rationale**: the `payment` object passed to the dialog at `Payments.vue:139` already contains the (now-correct) `date` field. The modal just needs to read from `payment.value.date` instead of `itemsResponse.data[0].sold`. The local-timezone parse is a small but important detail: `new Date('2026-06-02')` is UTC midnight, which displays as 2026-06-01 in UTC-3. Splitting on `-` and constructing with `new Date(y, m-1, d)` keeps it in the user's local timezone. No automated test added (Vue component initialization is hard to test in isolation without setting up a full component test harness; the user will verify manually).

## File-by-file change list

### `app/Http/Controllers/PaymentController.php`

#### Change 1: Remove the `whereHas` filter (Item 1)

- **Location**: lines `:613-614`
- **Pre-state**:
  ```php
  $query = Sale::with('items')
      ->whereHas('items', fn ($q) => $q->whereNotNull('sold'));
  ```
- **Post-state**:
  ```php
  $query = Sale::with('items');
  ```
- One-line change. The `->with('items')` consumption at `:632-639` (`firstItem` mapping) implicitly requires sales-with-items; zero-item sales are naturally excluded by the NPE risk in the mapping (verified — there are no zero-item sales in the schema because `items.sale_id` is FK non-nullable).
- **Side effect**: customer search branch (`:617-623`) for unpaid-only sales starts working (R5.S4).
- **Maps to**: R5.S1, R5.S2, R5.S3, R5.S4, R5.S5.

### `app/Http/Controllers/SaleController.php`

#### Change 2: Add try/catch race protection to the new-items loop in `store` (Item 2a)

- **Location**: lines `:201-214` (the auto-assign block + `Item::create` at `:214`)
- **Pre-state**: the auto-assign block at `:203-212` assigns `storage_id` and `position`; `Item::create($itemData)` fires at `:214`.
- **Post-state**: extract a `$warnings` array at loop scope, accumulate a warning entry when `findFirstAvailablePosition()` returns `null`, and wrap the `Item::create` in a try/catch that retries once on SQLSTATE 23000. On second failure, return 422.
- **Code shape (illustrative — apply phase writes the actual code)**:
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
          'user_id' => Auth::id(), 'sale_id' => $sale->id,
      ]);
  }

  $attempt = 0;
  while (true) {
      $attempt++;
      try {
          $item = Item::create($itemData);
          break;
      } catch (QueryException $e) {
          if ($e->getCode() !== '23000') {
              throw $e; // not a unique violation — let it surface
          }
          if ($attempt >= 2) {
              return response()->json(
                  ['error' => 'Storage is being modified concurrently, please retry.'],
                  422
              );
          }
          // Retry once with a fresh position
          $storageSlot = Storage::findFirstAvailablePosition();
          if ($storageSlot === null) {
              $warnings[] = [
                  'item_id' => null,
                  'model' => $new_item['model'] ?? null,
                  'type' => $new_item['type'] ?? null,
                  'reason' => 'no_storage_available',
              ];
              break;
          }
          $itemData['storage_id'] = $storageSlot['storage_id'];
          $itemData['position'] = $storageSlot['position'];
      }
  }
  ```
- The `$warnings` array is accumulated at the loop scope and returned in the response (Change 5).
- **Maps to**: R6.S1, R6.S2, R6.S3, R8.S1, R8.S2, R8.S4.

#### Change 3: Same try/catch for the new-items loop in `update` (Item 2b)

- **Location**: lines `:399-410` (the auto-assign block + `Item::create` at `:410`)
- Same code shape as Change 2, with `$user->id` and `$request->id` in the log context and `$item['id']` as the warning `item_id` (existing payload may carry it for newItems? — verified in apply: newItems do not carry an id, so `item_id` is `null` for new items in `update` too).
- **Maps to**: R6.S1, R6.S2, R6.S3 (tested via the `update` endpoint), R8.S3.

#### Change 4: Comment block above the new-items loop in `update` (Item 3)

- **Location**: above line `:367` (between the existing-items loop `:322-365` and the new-items loop `:369-411`)
- 5-8 line comment block explaining: existing-items loop runs first because `findFirstAvailablePosition` reads the DB and the new-items loop needs to see the just-persisted existing-items' positions. Do NOT wrap both loops in a single transaction; do NOT reorder.
- **Comment shape (illustrative)**:
  ```php
  // LOAD-BEARING ORDER: the existing-items loop above MUST run before this
  // new-items loop. Storage::findFirstAvailablePosition() reads the items
  // table for occupancy; if existing items persist AFTER new items, the
  // new-items loop picks colliding positions and trips
  // items_storage_position_unique. Safe today only because neither loop is
  // wrapped in a transaction. A future refactor that wraps both loops in a
  // single transaction OR reorders them MUST re-call
  // findFirstAvailablePosition after each existing item persists, OR call
  // it inline per-iteration. See R7.S1 for the regression-lock-in test.
  ```
- **Maps to**: R7.S1 (regression-lock-in test).

#### Change 5: Response shape change in `store` (Item 4a)

- **Location**: line `:242`
- **Pre-state**: `return response()->json($receiptUrl, 201);`
- **Post-state**: `return response()->json(['url' => $receiptUrl, 'warnings' => $warnings ?? []], 201);`
- **Maps to**: R8.S1, R8.S2, R8.S4.

#### Change 6: Response shape change in `update` (Item 4b)

- **Location**: line `:435`
- **Pre-state**: `return response()->json($request, 201);` (a long-standing leak of the form request, untouched by this change)
- **Post-state**: `return response()->json(array_merge($request->validated(), ['warnings' => $warnings ?? []]), 201);` — preserves the existing body shape (the `validated()` array contains the form fields the frontend may read) and adds a `warnings` key. Non-breaking for the frontend, which only checks `response.status` (`SaleEdit.vue:461`).
- **Maps to**: R8.S3.

### `resources/js/Pages/Inventory/Modals/ItemsSell.vue`

#### Change 7: Migrate response handler to read `data.url` and toast warnings (Item 4 frontend)

- **Location**: lines `:413-436`
- **Pre-state**: types the response as `string`, uses it directly as `link.href`.
- **Post-state**: types the response as `{url: string, warnings: Warning[]}`, reads `data.url`, and for each warning emits a `useToast` call with `severity: 'warn'`.
- **Code shape (illustrative)**:
  ```ts
  const response = await axios.post<{ url: string; warnings: Warning[] }>(
      route("sales.store"),
      salePayload
  );
  if (response.data.warnings?.length) {
      for (const w of response.data.warnings) {
          toast.add({
              severity: 'warn',
              summary: 'Storage warning',
              detail: `${w.model ?? 'Item'} (${w.type ?? '?'}) could not be assigned to a storage. Contact admin.`,
              life: 5000,
          });
      }
  }
  const link = document.createElement('a');
  link.href = response.data.url;
  // ... existing download flow
  ```
- **Maps to**: R8.S1, R8.S2, R8.S4 (the frontend side of the contract).

### `resources/js/Pages/Accounting/Modals/SaleEdit.vue`

#### Change 8: Toast warnings from `update` response (Item 4 frontend)

- **Location**: lines `:435-475`
- **Post-state**: read `response.data.warnings` and toast each.
- **Code shape (illustrative)**:
  ```ts
  const response = await axios.post(route("sales.update"), sale);
  if (response.status >= 200 && response.status < 400) {
      const warnings = (response.data?.warnings ?? []) as Warning[];
      for (const w of warnings) {
          toast.add({
              severity: 'warn',
              summary: 'Storage warning',
              detail: `${w.model ?? 'Item'} (${w.type ?? '?'}) could not be assigned to a storage. Contact admin.`,
              life: 5000,
          });
      }
      // ... existing success flow
  }
  ```
- Minimal change — `response.status` check is already in place.
- **Maps to**: R8.S3 (frontend side).

## Test plan mapped to spec scenarios

| Spec | Test file | Test name | Type |
| --- | --- | --- | --- |
| R5.S1 | `PaymentSimpleListVisibilityTest.php` (new) | `test_simple_list_includes_reserved_only_sale` | PHP Feature |
| R5.S2 | same | `test_simple_list_includes_all_sold_sale` | PHP Feature |
| R5.S3 | same | `test_simple_list_includes_mixed_status_sale` | PHP Feature |
| R5.S4 | same | `test_simple_list_customer_search_finds_unpaid_sale` | PHP Feature |
| R5.S5 | same | `test_simple_list_orders_by_date_desc_capped_at_limit` | PHP Feature |
| R6.S1 | `SaleItemReservationTest.php` (extend) | `test_store_auto_assign_retries_on_unique_violation` | PHP Feature (pre-occupy position with raw INSERT) |
| R6.S2 | same | `test_store_auto_assign_returns_422_on_second_collision` | PHP Feature |
| R6.S3 | same | `test_store_auto_assign_no_retry_on_happy_path` | PHP Feature (no performance regression) |
| R7.S1 | `SaleItemReservationTest.php` (extend) | `test_update_existing_then_new_items_no_position_collision` | PHP Feature |
| R8.S1 | `SaleItemReservationTest.php` (extend) | `test_store_with_no_storage_returns_warnings_in_response` | PHP Feature |
| R8.S2 | same | `test_store_with_storage_returns_empty_warnings` | PHP Feature |
| R8.S3 | same | `test_update_with_mixed_storage_outcomes_returns_warnings` | PHP Feature |
| R8.S4 | same | `test_store_response_shape_is_url_plus_warnings` | PHP Feature (breaking change assertion) |

For R6 tests, use the "pre-occupy the position with a raw INSERT then call the route" approach (per the explore agent's recommendation). The mock approach is fragile because the catch is on `Item::create` which is downstream of the controller; pre-occupation exercises the real path.

For Item 3 (comment-only), no automated test is added for the comment itself. R7.S1 is the regression-lock-in test that exercises the loop order contract under mixed existing+new items.

## Work-unit commit plan

Per AD-5, single commit at the end. The plan is:

1. **T0-T11**: implement all 4 items + tests in the working tree. NO commits during this phase.
2. **T12**: pause for user manual verification. Recipe: create a sale (verifies R5 list surface), add a new item to it (verifies R6 happy path), test in low-concurrency that auto-assign works, simulate no-storage by clearing storages and submitting (verifies R8 warnings surface in the toast).
3. **T13**: stage only the expected files.
4. **T14**: verify the diff.
5. **T15**: ONE conventional commit with a multi-line body.
6. **T16**: post-commit verification.

The commit message will be:

- **Subject**: `fix(sales): surface reserved-only sales in payments list and harden auto-assign`
- **Body**: describes the 4 items, the breaking change in `sales.store`, the durable-fix follow-up, and the test additions.

(Full commit message text will be in `tasks.md` T15, not here.)

## Risks and mitigations

- **Race fix is partial** (AD-1) → durable fix tracked as follow-up. The unique index is the real defense.
- **Breaking change in `sales.store`** (AD-4) → apply phase MUST grep for all callers; if there are callers other than `ItemsSell.vue`, the breaking change is wider than expected and the propose phase needs to re-scope.
- **Warnings shape divergence** (AD-3) → documented in code comments at both new and old call sites. Future consumers expecting flat reason codes will need migration.
- **Comment-only fix is fragile to refactors** (AD-2) → a regression test (R7.S1) exercises the loop order contract; the comment + the test together encode the contract.
- **Performance of catch-and-retry** → the retry path is the slow path; the happy path (R6.S3) is unchanged. The unique index lookup on the failed INSERT is O(log n) and fast.
- **`useToast` availability in ItemsSell.vue** → already imported (`:176`). No new import needed.
- **`useToast` availability in SaleEdit.vue** → already imported (`:245`). No new import needed.
- **The warnings array may include items the user did not ask to assign to storage** → the warning is informational only. The user can ignore it. The DB will not show a "warning" indicator elsewhere, so this is purely a toast.

## Out of scope (reaffirm)

- Durable race fix (`DB::transaction` + `lockForUpdate`) — follow-up
- Other SaleEdit modal bugs (`Number(balance_remaining)`, hardcoded discount, etc.) — follow-up from previous change
- Ecommerce-channel scope, date format search, `LIMIT=25` cap — unchanged
- Refactoring loop order into helpers — out of scope
- Changing the existing flat `warnings` shape in `getPaymentsData:115` — divergence is accepted, not migrated

## Affected lines summary

| File | Action | Lines |
| --- | --- | --- |
| `app/Http/Controllers/PaymentController.php` | Modify | -2 (Item 1, remove the whereHas) |
| `app/Http/Controllers/SaleController.php` | Modify | +50 / -5 (Items 2a, 2b, 3, 4a, 4b) |
| `resources/js/Pages/Inventory/Modals/ItemsSell.vue` | Modify | +12 / -4 (Item 4 frontend) |
| `resources/js/Pages/Accounting/Modals/SaleEdit.vue` | Modify | +8 / -2 (Item 4 frontend) |
| `tests/Feature/Payments/PaymentSimpleListVisibilityTest.php` | Create | ~85 |
| `tests/Feature/Controllers/SaleItemReservationTest.php` | Extend | +220 (8 new methods covering R6, R7, R8) |

**Total estimated changed lines: ~310** (under 80% of the 400-line budget). Single PR.

**Chained PRs needed: No.**
**Decision needed before apply: No** — all product questions resolved.

## Open questions

None. The durable race fix is explicitly a follow-up, per the user's clarification.

## Skill resolution

`paths-injected` — orchestrator provided exact paths. Loaded:

- `/Users/johiny/Code_Library/Refreshm_inventoryV2/.atl/skills/laravel-inertia/SKILL.md`
- `/Users/johiny/Code_Library/Refreshm_inventoryV2/.atl/skills/vue-inertia/SKILL.md`
- `~/.config/opencode/skills/_shared/sdd-phase-common.md`
- `~/.config/opencode/skills/work-unit-commits/SKILL.md`

## Follow-ups (for archive phase to capture)

- **Durable race fix**: `DB::transaction` + `lockForUpdate` on the storage row, mirroring `AppendIncomingRequestToInvoiceService:27-48`. Single dedicated SDD change.
- **Other SaleEdit modal bugs**: `Number(balance_remaining)`, hardcoded `discount: 0`, misleading `addItem` name, no `newItems.*` validation in `SaleFormEdit.php`.
- **Ecommerce-channel scope**: when sales are created on non-`system` channels, the `simpleList` silently skips them.
- **Date format search**: `2026/06/04` LIKE dead-end.
- **Item boot hook event order bug**: `static::saving` registered before `static::creating` in `Item.php:322` vs `:370`. A proper fix would reorder the registrations.
