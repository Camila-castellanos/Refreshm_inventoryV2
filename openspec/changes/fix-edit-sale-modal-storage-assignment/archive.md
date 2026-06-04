# Archive Report: fix-edit-sale-modal-storage-assignment

## Summary

Restored the `sold_storage_*` snapshot for items transitioning to `SOLD` through three `SaleController` paths (store new-items, update new-items, update existing-items paid), fixed a Vue payload contract mismatch in `ItemsSell.vue` (`position: ""` → `position: null`), and added a Sold-page fallback so RESERVED items display their current rack location instead of "N/A". Implementation landed in commit `d913ce6` with 7 files and +484/-10 lines; PHP and JS test suites are green.

## Artifacts

- Proposal: `openspec/changes/fix-edit-sale-modal-storage-assignment/proposal.md`
- Spec: `openspec/changes/fix-edit-sale-modal-storage-assignment/specs/sales/spec.md`
- Design: `openspec/changes/fix-edit-sale-modal-storage-assignment/design.md`
- Tasks: `openspec/changes/fix-edit-sale-modal-storage-assignment/tasks.md`
- This archive: `openspec/changes/fix-edit-sale-modal-storage-assignment/archive.md`

## Commit

- SHA: `d913ce6`
- Subject: `fix(sales): preserve storage snapshot and show current location for reserved items`
- Files: 7 (1 controller, 2 Vue, 4 test files)
- Lines: +484/-10

## Canonical spec

- Capability: `sales`
- Synced to: `openspec/specs/sales/spec.md`
- Source: `openspec/changes/fix-edit-sale-modal-storage-assignment/specs/sales/spec.md`
- Status: was a new capability (seeded by this change). The canonical spec now contains the 4 requirements and 7 scenarios from the change.

## Spec requirements shipped

1. R1 — New items in a sale receive a real storage location
2. R2 — Items transitioning to SOLD preserve a storage snapshot
3. R3 — Snapshot capture is explicit and idempotent
4. R4 — Snapshot contract applies to create and update paths

## Discovered during apply (follow-ups, not in scope)

- **Item boot hook event order bug** (engram topic `discovery/item-model-boot-hook-event-order`): `static::saving` is registered before `static::creating` in `app/Models/Item.php:322` and `:370`. Eloquent fires model events in registration order, so on `Item::create()` the `saving` hook nulls `storage_id` before the `creating` hook can use it to assign `position`. The current change works around this by setting `position` explicitly in the controllers. A proper fix would reorder the hook registration.
- **Sold page shows RESERVED items** (engram topic `discovery/sold-page-shows-reserved-items-as-na`): the `orWhere` branch on `sales.created_at` in `SaleController::showReport` includes items from recently-created (and possibly unpaid) sales, including RESERVED items. The change adds a fallback in the Sold.vue display.

## Out of scope (not addressed, future changes)

- Other SaleEdit modal bugs: `Number(balance_remaining)` coercion, hardcoded `discount: 0`, misleading `addItem` function name, missing `newItems.*` validation rules in `SaleFormEdit.php`.
- Refactor the snapshot pattern into a shared service.
- Add a UI storage picker to the new-item rows in `ItemsSell.vue` and `SaleEdit.vue`.
- Fix the pre-existing race condition on `getNextAvailablePosition` / `findFirstAvailablePosition`.

## Test status

- PHP: 1154 tests, 2216 assertions, 30 pre-existing errors (AuthorizationTest, UserEdgeCaseTest, etc. — unrelated to sales/storage), 0 new failures. New/extended sales-related tests: 12 tests, 70 assertions, all green.
- JS: 52 test files, 469 tests, 2 skipped, 0 failed.
- Linters: Pint clean on SaleController.php. npm run lint not configured.

## Engram topics referenced

- `sdd-init/refreshm_inventoryv2`
- `sdd/fix-edit-sale-modal-storage-assignment/clarifications`
- `sdd/fix-edit-sale-modal-storage-assignment/explore`
- `sdd/fix-edit-sale-modal-storage-assignment/proposal`
- `sdd/fix-edit-sale-modal-storage-assignment/spec`
- `sdd/fix-edit-sale-modal-storage-assignment/design`
- `sdd/fix-edit-sale-modal-storage-assignment/tasks`
- `sdd/fix-edit-sale-modal-storage-assignment/apply-progress`
- `discovery/item-model-boot-hook-event-order` (follow-up)
- `discovery/sold-page-shows-reserved-items-as-na` (was fixed by Option A)
- `bugfix/edit-sale-modal-drops-storage-id-for-new-items` (original report)

## Closing notes

The change is complete and verified. The canonical `sales` spec is in place for future extensions (returns, discounts, partial payments). All in-scope work is shipped; all out-of-scope work is documented as follow-ups.
