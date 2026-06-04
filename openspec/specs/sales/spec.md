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

### Requirement: Payments Simple List Includes All Sales With Items

The `payments.simpleList` endpoint MUST return every sale with at least
one item, regardless of `SOLD`/`RESERVED` mix. The endpoint MUST NOT
filter on `items.sold`. Customer-name search MUST match sales whose
only items are `RESERVED`. Both consuming modals
(`AddItemsToSale.vue`, `AppendIncomingRequestToSale.vue`) MUST see the
same set. Ordering: `date DESC`; cap: `take($limit)`.

#### Scenario: All-RESERVED sale appears in the list

- GIVEN a sale with two `RESERVED` items (`sold = null`)
- WHEN `payments.simpleList` is requested
- THEN the sale appears with `firstItem` populated.

#### Scenario: All-SOLD sale appears (regression)

- GIVEN a sale with two items, both `sold` populated
- WHEN `payments.simpleList` is requested
- THEN the sale appears. (Preserves prior behavior.)

#### Scenario: Mixed SOLD+RESERVED sale appears once

- GIVEN a sale with one `SOLD` and one `RESERVED` item
- WHEN `payments.simpleList` is requested
- THEN the sale appears exactly once.

#### Scenario: Customer search returns an unpaid-only sale

- GIVEN a `RESERVED`-only sale with customer "Acme"
- WHEN `payments.simpleList` is requested with search "Acme"
- THEN the sale appears. (Customer-search regression fix.)

#### Scenario: Ordering and limit cap

- GIVEN 30 sales, `take($limit)` = 25
- WHEN `payments.simpleList` is requested
- THEN ≤ 25 sales are returned, ordered by `date` DESC.

### Requirement: Storage Auto-Assign Retries Once On a Unique-Constraint Collision

When `Item::create` throws a `QueryException` with SQLSTATE 23000
(violation on `items_storage_position_unique`), the controller MUST
catch it, call `Storage::findFirstAvailablePosition()` again, and
retry `Item::create` once. If the retry also fails with SQLSTATE 23000,
the controller MUST return HTTP 422 with body
`{"error": "Storage is being modified concurrently, please retry."}`.
Non-23000 `QueryException` subclasses MUST re-throw unchanged. Both
`store` and `update` new-items loops MUST apply this pattern.

#### Scenario: First call collides; retry returns a free position

- GIVEN first `findFirstAvailablePosition` returns occupied, second
  returns free
- WHEN `SaleController::store` (or `update`) is called
- THEN the item persists at the second position; response is 201; no
  exception escapes.

#### Scenario: Both calls collide; controller returns 422

- GIVEN `findFirstAvailablePosition` returns a colliding position on
  BOTH calls
- WHEN `SaleController::store` (or `update`) is called
- THEN response is 422 with body
  `{"error": "Storage is being modified concurrently, please retry."}`.

#### Scenario: Happy path does not incur retry overhead

- GIVEN first `findFirstAvailablePosition` returns a free position
- WHEN `SaleController::store` (or `update`) is called
- THEN the item persists at the first position; no retry is attempted.

### Requirement: Existing-Items Loop Runs Before New-Items Loop in SaleController::update

The existing-items loop in `SaleController::update` (around
`:322-365`) MUST run before the new-items loop (around `:369-411`).
A code comment in the controller MUST explain this load-bearing
assumption: the new-items loop calls `findFirstAvailablePosition()`
which reads the items table for occupancy; if existing items persist
AFTER new items, the new-items loop picks colliding positions. Safe
today only because neither loop is wrapped in a transaction.

#### Scenario: Mixed existing + new items persist with no position collision

- GIVEN an `update` with `items: [existing_item_X]` at position 3,
  `newItems: [new_item_Y]` with no `storage_id`, position 3 free at
  request time
- WHEN `SaleController::update` is called
- THEN `existing_item_X` persists first at position 3; the new-items
  loop re-reads occupancy; `new_item_Y` persists at the next free
  position with no unique-constraint violation.

### Requirement: Sale Responses Surface Storage Assignment Warnings

When `Storage::findFirstAvailablePosition()` returns null, the
controller MUST accumulate a per-item warning of shape
`{"item_id": <id|null>, "model": <model>, "type": <type>, "reason": "no_storage_available"}`
in a `$warnings` array. The `store` response MUST be
`{"url": <receiptUrl>, "warnings": <array>}` (**breaking change** —
was a raw string). The `update` response MUST include a `warnings`
key (non-breaking — existing consumer only checks `response.status`).
The frontend modals (`ItemsSell.vue`, `SaleEdit.vue`) MUST read
`warnings` and surface each entry as a non-blocking toast of severity
`warn`.

#### Scenario: store with no storage returns 201 with populated warnings

- GIVEN no `Storage` rows (or all at capacity) and a paid sale with
  one new item
- WHEN `SaleController::store` is called
- THEN response is 201 with body
  `{"url": "<receiptUrl>", "warnings": [{"item_id": null, "model": "...", "type": "...", "reason": "no_storage_available"}]}`;
  the item persists with `storage_id = null`.

#### Scenario: store with available storage returns 201 with empty warnings

- GIVEN at least one `Storage` row with capacity, a paid sale with
  one new item carrying no `storage_id`
- WHEN `SaleController::store` is called
- THEN response is 201 with body
  `{"url": "<receiptUrl>", "warnings": []}`.

#### Scenario: update with mixed auto-assign outcomes reports fallback items only

- GIVEN an `update` with two new items — one auto-assigned
  successfully, one fell back to null
- WHEN `SaleController::update` is called
- THEN `warnings` contains exactly the fallback item; the successful
  item is NOT in `warnings`.

#### Scenario: ItemsSell.vue reads data.url and toasts warnings (breaking-shape lock-in)

- GIVEN `ItemsSell.vue` consuming
  `axios.post(route("sales.store"), payload)`
- WHEN the response arrives
- THEN the modal reads `data.url` (NOT `data` as a string) and toasts
  each `data.warnings` entry with severity `warn`. This locks in the
  breaking response shape for all current and future consumers of
  `sales.store`.

### Requirement: Payments Page Date Source
 
 The `date` field returned in the Payments page response (via
 `PaymentController::getPaymentsData`) MUST be sourced from
 `sales.date` (the user-picked payment date in the form) as the PRIMARY
 value. The fallback chain when `sales.date` is null is: `items.sold`
 (when the item is SOLD), then `items.partially_sold_at` (when the item
 is RESERVED), then `sales.created_at`. This aligns the Payments page
 with the `simpleList` endpoint (which already uses `sales.date`) so
 the same sale shows the same date in both views.
 
 BOTH endpoints MUST return the date as a plain `Y-m-d` string formatted
 to the user's timezone on the backend. Returning raw date objects or
 ISO strings with timezone suffixes (like `Z`) is FORBIDDEN as it causes
 inconsistent shifts in the frontend based on the browser's local time.
 
 #### Scenario: Unpaid sale with backdated payment_date shows that backdated date (not partially_sold_at)


- GIVEN a sale created today with `date = 2 days ago` (user backdated
  the payment date in the form) and an item with
  `status = RESERVED, partially_sold_at = today, sold = null`
- WHEN the Payments page is loaded (any status filter)
- THEN the response's `items[0].date` is the backdated `2 days ago`,
  NOT `today` (which is what `partially_sold_at` would yield
  pre-fix). This locks the date-ordering contract.

### Requirement: Edit Sale Modal Initial Date

The `Edit Sale` modal in `SaleEdit.vue` MUST initialize
`form.value.date` from the sale's actual date (sourced from the
Payments page response's `date` field, which is `sales.date` per the
previous requirement), NOT from `items[0].sold` (which is null for
RESERVED items and would force the fallback to `new Date()` = today).
The date string `yyyy-MM-dd` MUST be parsed in local time (split into
year/month/day and `new Date(y, m-1, d)`) so the PrimeVue `DatePicker`
does not shift the displayed date by one day for users in
negative-UTC timezones.

#### Scenario: Unpaid sale opened in Edit Sale modal shows the actual sale date (not today)

- GIVEN a sale with `date = 2026-06-02` and an item with
  `status = RESERVED, sold = null` (so `items[0].sold` is null and the
  old code fell back to `new Date()` = today)
- WHEN the Edit Sale modal is opened from the Payments page
- THEN the form's `date` field shows `2026-06-02` (the user-picked
  sale.date), NOT today. This locks the contract that the modal
  always shows the sale's actual date, not a fallback to "now".
