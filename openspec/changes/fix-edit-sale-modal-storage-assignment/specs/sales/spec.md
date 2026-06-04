# Sales Specification

## Purpose

The Sales capability governs creation, update, and finalization of
sales. This seed spec defines how items added to a sale obtain a
physical storage location and how that location is preserved as a
historical snapshot when an item transitions to `SOLD`. Future
changes can extend this capability without rewriting these contracts.

## Requirements

### Requirement: New Items in a Sale Receive a Real Storage Location

When a new item is added to a sale, the system MUST assign a real
`storage_id` and `position` before persistence. If the payload
carries a `storage_id`, use it; the `creating` hook auto-fills
`position`. Otherwise, call `Storage::findFirstAvailablePosition()`
and assign the returned values. If that returns `null`, the item MAY
still save without a location, and a `warning` MUST be logged.

#### Scenario: New item in a paid sale gets auto-assigned storage

- GIVEN a paid sale, `newItems` with one item carrying no
  `storage_id`, and a `Storage` row with capacity
- WHEN `SaleController::store` is called
- THEN the new item is persisted with `status = SOLD` and
  `storage_id` / `position` matching the auto-assigned values.

#### Scenario: New item added in SaleController::update is auto-assigned

- GIVEN a paid sale in `update`, `newItems` with one item carrying
  no `storage_id`, and a `Storage` row with capacity
- WHEN `SaleController::update` is called
- THEN the new item is persisted with `status = SOLD` and
  `storage_id` / `position` matching the auto-assigned values.

### Requirement: Items Transitioning to SOLD Preserve a Storage Snapshot

When an item transitions to `SOLD`, populate `sold_storage_id`,
`sold_position`, and `sold_storage_name` with the pre-transition
location, then null the active `storage_id` and `position`. The
snapshot is one-way: once `sold_storage_*` is populated, a subsequent
save MUST NOT overwrite it with null.

#### Scenario: Existing item transitions to SOLD with snapshot populated

- GIVEN a paid sale with an existing item at `storage_id = 5`,
  `position = 3`, `storage.name = "Shelf A"`
- WHEN `SaleController::update` is called
- THEN the item transitions to `SOLD` with active fields null and
  `sold_storage_id = 5`, `sold_position = 3`,
  `sold_storage_name = "Shelf A"`.

#### Scenario: Snapshot survives re-save of an already-sold item

- GIVEN an item already SOLD with `sold_storage_id = 5`,
  `sold_position = 3`, `sold_storage_name = "Shelf A"`, and active
  fields null
- WHEN any save is performed on that item
- THEN `sold_storage_id`, `sold_position`, and `sold_storage_name`
  remain populated and unchanged.

### Requirement: Snapshot Capture Is Explicit and Idempotent

For an existing item transitioning to SOLD, the controller MUST
copy `sold_storage_id`, `sold_position`, and `sold_storage_name`
from the in-memory model into the update payload, guarded by
`is_null($sale_item->sold_storage_id)`. The `saving` boot hook's
idempotent guard is a safety net only. See
`SaleController.php:126-128`.

#### Scenario: Controller copies snapshot fields into update payload

- GIVEN `$sale_item` at `storage_id = 9`, `position = 1`,
  `storage.name = "Vault B"`, with `sold_storage_id` null
- WHEN `SaleController::update` builds the update array
- THEN it contains `sold_storage_id = 9`, `sold_position = 1`,
  and `sold_storage_name = "Vault B"` keys.

#### Scenario: Snapshot copy is skipped when already populated

- GIVEN `$sale_item` with `sold_storage_id = 5` already set
- WHEN `SaleController::update` builds the update array
- THEN `sold_storage_*` fields are NOT overwritten with the
  current null active fields.

### Requirement: Snapshot Contract Applies to Create and Update Paths

The snapshot contract applies whether the item is being created in
a paid sale for the first time or transitioned from `RESERVED` to
`SOLD` via update. There MUST NOT be a code path that creates or
updates a `SOLD` item without populating `sold_storage_*` when a
real pre-transition location existed.

#### Scenario: SaleController::update transitions existing item with snapshot

- GIVEN `SaleController::update` invoked with a paid sale and an
  existing item with a real `storage_id` and `position`
- WHEN the request is processed
- THEN the existing item is updated with `status = SOLD` and
  `sold_storage_*` populated from the pre-update in-memory state.
