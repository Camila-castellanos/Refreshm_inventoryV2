# Archive Report: fix-payments-list-and-storage-flow-hardening

## Summary

Hardened four soft edges left by the previous storage-snapshot change
(`d913ce6`): the payments simple-list now surfaces RESERVED-only sales,
the `SaleController` auto-assign path catches and retries on
SQLSTATE 23000 unique-constraint violations, the load-bearing loop
order in `update` is documented in code, and per-item no-storage
warnings are now surfaced in a new `{url, warnings}` response shape
(breaking for `sales.store`) and consumed by the front-end as
non-blocking toasts. Two date-source inconsistencies surfaced during
the T15 manual verification (Payments page + Edit Sale modal) were
folded in as R9 and R10. Shipped as a single conventional commit
`9beb250` (8 files, +907/-15) and verified twice by the user.

## Artifacts

- Proposal: `openspec/changes/fix-payments-list-and-storage-flow-hardening/proposal.md`
- Spec: `openspec/changes/fix-payments-list-and-storage-flow-hardening/specs/sales/spec.md`
- Design: `openspec/changes/fix-payments-list-and-storage-flow-hardening/design.md`
- Tasks: `openspec/changes/fix-payments-list-and-storage-flow-hardening/tasks.md`
- This archive: `openspec/changes/fix-payments-list-and-storage-flow-hardening/archive.md`

## Commit
 
 - SHA: `58256fc`
 - Subject: `docs(spec): clarify consistent backend date formatting requirement`
 - Previous SHA: `85ed240` (fix), `9beb250` (original)
 - Files: 10 (+929/-27)


## Canonical spec

- Capability: `sales`
- Synced to: `openspec/specs/sales/spec.md`
- Source: `openspec/changes/fix-payments-list-and-storage-flow-hardening/specs/sales/spec.md`
- Status: the change ADDS R5–R10 to the existing R1–R4 contract. Final canonical
  contains 10 requirements and 22 scenarios.

## Spec requirements shipped

1. R1–R4 (from previous change, `d913ce6`): storage snapshot contract
2. R5: Payments simple list includes all sales regardless of item status
3. R6: Storage position auto-assign handles concurrent races gracefully
4. R7: Existing-items loop runs before new-items loop in `SaleController::update`
5. R8: Sale responses surface storage assignment warnings
6. R9 (user-verified during T15): Payments Page Date Source — `sales.date` as primary
7. R10 (user-verified during T15 second pass): Edit Sale Modal Initial Date
8. R11 (user-verified during investigation): Consistent Backend Date Representation — fixed `simpleList` discrepancy.

## Discovered during apply (follow-ups, not in scope)

- **Item boot hook event order bug** (engram topic
  `discovery/item-model-boot-hook-event-order`): `static::saving` is
  registered before `static::creating` in `app/Models/Item.php:322`
  and `:370`. Eloquent fires model events in registration order, so
  on `Item::create()` the `saving` hook nulls `storage_id` before the
  `creating` hook can use it to assign `position`. The previous change
  worked around this with explicit `position` assignment in the
  controllers. A proper fix would reorder the hook registration.

## Out of scope (not addressed, future changes)

- Durable race fix (`DB::transaction` + `lockForUpdate` on the storage
  row, mirroring `AppendIncomingRequestToInvoiceService:27-48`).
  Pragmatic catch-and-retry was the user-confirmed approach.
- Other SaleEdit modal bugs: `Number(balance_remaining)` coercion,
  hardcoded `discount: 0`, misleading `addItem` function name, no
  `newItems.*` validation rules in `SaleFormEdit.php`.
- Refactor the loop order into a shared `processExistingItems()` /
  `processNewItems()` helper.
- Add a UI storage picker to the new-item rows in `ItemsSell.vue` and
  `SaleEdit.vue`.
- Fix the pre-existing race condition on `getNextAvailablePosition` /
  `findFirstAvailablePosition`.
- Ecommerce-channel scope, date format search, `LIMIT=25` cap.
- Migrate the existing flat `warnings` shape in `getPaymentsData:115`
  to per-item (divergence is accepted and documented in AD-3).

## Test status

- PHP: 21 relevant tests all pass (16 in `PaymentCrudTest`, 5 in
  `PaymentSimpleListVisibilityTest`); 7 new tests in
  `SaleItemReservationTest` pass when run in isolation (the file
  triggers a pre-existing memory exhaustion when run as part of the
  full suite, unrelated to this change).
- JS: 469 tests pass, 2 skipped. `ItemsSell.spec.ts` updated to
  migrate the `axios.post` mock to the new `{url, warnings}` response
  shape.
- Linters: Pint clean on `SaleController.php` and `PaymentController.php`.
  `npm run lint` not configured.

## Engram topics referenced

- `sdd-init/refreshm_inventoryv2`
- `sdd/fix-payments-list-and-storage-flow-hardening/clarifications`
- `sdd/fix-payments-list-and-storage-flow-hardening/explore`
- `sdd/fix-payments-list-and-storage-flow-hardening/proposal`
- `sdd/fix-payments-list-and-storage-flow-hardening/spec`
- `sdd/fix-payments-list-and-storage-flow-hardening/design`
- `sdd/fix-payments-list-and-storage-flow-hardening/tasks`
- `sdd/fix-payments-list-and-storage-flow-hardening/apply-progress`
- `discovery/item-model-boot-hook-event-order` (follow-up)

## Closing notes

The change is complete and verified. The canonical `sales` spec now
contains 10 requirements and 22 scenarios. The pragmatic race fix and
the date-source alignment were both user-confirmed during manual
verification (T15 and T15 second pass). All in-scope work is shipped;
all out-of-scope work is documented as follow-ups.
