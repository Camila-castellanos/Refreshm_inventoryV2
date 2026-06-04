# Tasks: fix-payments-list-and-storage-flow-hardening

## Commit strategy

**Single final commit**, gated on user manual verification. No intermediate commits.

The apply phase makes all code + test changes in the working tree, runs the test suite to ensure it is green, and then **pauses** for the user to manually verify the four items are fixed. ONLY after the user confirms does the apply phase make ONE conventional commit with the consolidated diff.

## Pre-flight

- [ ] **T0 — Read pre-flight context**
  - Read the proposal, spec, and design.
  - Verify the file:line references in the design still match the live code (cite the design's references).
  - Done when: orchestrator has confirmed references match. If they have drifted, update the design or ask the user to re-spec.

## Phase 1: Implementation (no commits)

- [ ] **T1 — Item 1: Remove the whereHas filter in getPaymentsSimpleList**
  - File: `app/Http/Controllers/PaymentController.php`, line `:613-614`
  - Action: delete the `->whereHas('items', fn ($q) => $q->whereNotNull('sold'))` clause. The line becomes:
    ```php
    $query = Sale::with('items');
    ```
  - Done when: `php -l app/Http/Controllers/PaymentController.php` returns "No syntax errors detected" and `grep` confirms the whereHas is gone.

- [ ] **T2 — Item 2a: Add try/catch race protection to the new-items loop in `store`**
  - File: `app/Http/Controllers/SaleController.php`, around lines `:201-214`
  - Action: wrap the `Storage::findFirstAvailablePosition()` + assignment + `Item::create($newItemData)` in a `while (true) { ... }` loop with try/catch. Use the code shape from design Change 2 verbatim. Accumulate `$warnings` and `$newItemIds` at the loop scope.
  - Add imports: `use Illuminate\Database\QueryException;` and `use Illuminate\Validation\ValidationException;` if not already present.
  - Done when: the loop has the catch-and-retry pattern, imports are correct, `php -l` passes.

- [ ] **T3 — Item 2b: Same try/catch for the new-items loop in `update`**
  - File: `app/Http/Controllers/SaleController.php`, around lines `:388-410`
  - Action: same code shape as T2. Accumulate `$warnings` and `$newItemIds`.
  - Done when: identical structure to T2; `php -l` passes.

- [ ] **T4 — Item 3: Comment block above the new-items loop in `update`**
  - File: `app/Http/Controllers/SaleController.php`, above line `:369`
  - Action: add a 5-8 line comment block explaining the load-bearing assumption: existing-items loop runs first because `findFirstAvailablePosition` reads the DB. Do NOT wrap both loops in a single transaction; do NOT reorder.
  - Done when: comment is present, addresses both the order and the "no transaction wrapper" rule.

- [ ] **T5 — Item 4a: Response shape change in `store`**
  - File: `app/Http/Controllers/SaleController.php`, line `:242`
  - Action: change `return response()->json($receiptUrl, 201);` to `return response()->json(['url' => $receiptUrl, 'warnings' => $warnings ?? []], 201);`. The `$warnings` array is the one accumulated in T2.
  - Done when: the response returns the new shape; `php -l` passes.

- [ ] **T6 — Item 4b: Response shape change in `update`**
  - File: `app/Http/Controllers/SaleController.php`, line `:435`
  - Action: change `return response()->json($request, 201);` to `return response()->json(array_merge($request->all(), ['warnings' => $warnings ?? []]), 201);`. The `$warnings` array is the one accumulated in T3.
  - Done when: response shape includes the `warnings` key; `php -l` passes.

- [ ] **T7 — Item 4 frontend: Migrate ItemsSell.vue to read `data.url` and toast warnings**
  - File: `resources/js/Pages/Inventory/Modals/ItemsSell.vue`, lines `:413-436`
  - Action: type the response as `{url: string, warnings: Warning[]}`. Read `response.data.url` instead of treating the response as a string. For each warning in `response.data.warnings`, emit a `toast.add({ severity: 'warn', ... })` call. Use the design Change 7 code shape verbatim.
  - Done when: the response handler reads `data.url`, the toast loop is in place, the existing download flow still works (link.href = data.url).

- [ ] **T8 — Item 4 frontend: Toast warnings from `update` response in SaleEdit.vue**
  - File: `resources/js/Pages/Accounting/Modals/SaleEdit.vue`, lines `:435-475`
  - Action: read `response.data.warnings` and emit a toast for each. The existing `response.status` check is preserved.
  - **NOTE**: This file has pre-existing modifications from the previous change (commit `d913ce6` left them uncommitted). T8 adds new lines on top. The apply phase will use `git add -p` or a patch-only staging in T16 if the user wants ONLY the T8 lines committed and the pre-existing modifications left for a follow-up. Otherwise the whole file's delta ships in this commit.
  - Done when: toast loop is in place; status check is preserved.

## Phase 2: Test additions (no commits)

- [ ] **T9 — Create PaymentSimpleListVisibilityTest.php (Item 1 coverage)**
  - File: `tests/Feature/Payments/PaymentSimpleListVisibilityTest.php` (new)
  - 5 test methods (R5.S1, R5.S2, R5.S3, R5.S4, R5.S5) per the design's test plan.
  - Setup: create a `User`, a few `Storage` rows, a few `Sale` rows with varying item statuses (all-sold, all-reserved, mixed, customer-named unpaid-only).
  - Action: `GET /accounting/payments/simple` (with and without `?q=`).
  - Asserts: the right sales appear, customer search finds the unpaid-only sale, ordering and limit are correct.
  - Done when: `vendor/bin/phpunit tests/Feature/Payments/PaymentSimpleListVisibilityTest.php` passes (all 5 methods green).

- [ ] **T10 — Extend SaleItemReservationTest.php (Items 2, 3, 4 coverage)**
  - File: `tests/Feature/Controllers/SaleItemReservationTest.php` (existing)
  - 8 new test methods per the design's test plan:
    - R6.S1: `test_store_auto_assign_retries_on_unique_violation` (use raw INSERT pre-occupation)
    - R6.S2: `test_store_auto_assign_returns_422_on_second_collision` (use two raw INSERTs in sequence — see below)
    - R6.S3: `test_store_auto_assign_no_retry_on_happy_path` (no pre-occupation; assert no exception)
    - R7.S1: `test_update_existing_then_new_items_no_position_collision` (mixed existing + new in one request)
    - R8.S1: `test_store_with_no_storage_returns_warnings_in_response`
    - R8.S2: `test_store_with_storage_returns_empty_warnings`
    - R8.S3: `test_update_with_mixed_storage_outcomes_returns_warnings`
    - R8.S4: `test_store_response_shape_is_url_plus_warnings` (assert response body keys are `url` and `warnings`)
  - For R6.S1 and R6.S2: the test pre-occupies a position with a raw `Item::create` (or directly via the model) and then calls the route. The first call's controller retry path is exercised.
  - For R6.S2 specifically, to assert the 422 on the second collision, use `$this->mock(Storage::class, ...)` to return a colliding position TWICE (mock the Storage facade). Or pre-occupy ALL positions in the storage.
  - Done when: `vendor/bin/phpunit tests/Feature/Controllers/SaleItemReservationTest.php` passes with the 8 new methods green.

## Phase 3: Verification (no commits yet)

- [ ] **T11 — Run full PHP test suite**
  - Command: `vendor/bin/phpunit`
  - Done when: all tests pass, including the 5+8=13 new/extended methods. The 30 pre-existing errors in `UserEdgeCaseTest.php` etc. are EXPECTED (not introduced by this change). Capture the full output.

- [ ] **T12 — Run full JS test suite**
  - Command: `npm run test:run`
  - Done when: all JS tests pass. The ItemsSell.spec.ts and AppendIncomingRequestToSale.spec.ts tests stub `axios.get` (not the new `axios.post` shape), so the migration to `data.url` may need a small spec update — apply phase MUST check this and add a `data.url` assertion if missing.

- [ ] **T13 — Run linters (if configured)**
  - PHP: `vendor/bin/pint` if present.
  - Vue: `npm run lint` if configured.
  - Done when: no lint errors, or N/A if no linter.

- [ ] **T14 — Grep audit for sales.store callers (CRITICAL for AD-4)**
  - Command: `grep -rn "sales\.store\|sales/store" resources/js/ app/ routes/`
  - Done when: the ONLY consumer of `sales.store` is `ItemsSell.vue`. If other consumers exist, the breaking change is wider than expected and the orchestrator MUST alert the user before T17.

- [ ] **T15 — Manual verification gate (PAUSE HERE)**
  - The apply phase MUST STOP and ask the user to manually verify the four items.
  - Provide the user with a concrete verification recipe for each item:
    - **Item 1**: Open `AddItemsToSale` modal in `inventory/items`. Confirm that a recently-created unpaid sale appears in the dropdown (it didn't before). Confirm customer search by partial name also works.
    - **Item 2**: Create a sale with one new item, submit. Confirm no 500 in the response. (For the race collision path, the user can manually test by opening two browser tabs and clicking submit simultaneously — a 422 with a clear message should appear, OR a successful submit on retry.)
    - **Item 3**: Read the comment in `SaleController.php` above the new-items loop. Confirm it explains the load-bearing assumption. Run `test_update_existing_then_new_items_no_position_collision` to confirm the contract holds.
    - **Item 4**: Disable all storages for the user (or set `LIMIT=0` and create a storage with limit 0). Create a sale with a new item. Submit. Confirm the response includes a warning AND the user sees a non-blocking toast. The item is saved with `storage_id = null`.
  - The apply phase MUST NOT proceed to T16 until the user explicitly confirms "verified" or names what is still broken.
  - Done when: the user has responded with confirmation, or with a list of issues that need a follow-up fix.

## Phase 4: Single final commit (gated on T15 confirmation)

- [ ] **T16 — Stage all changes (selectively)**
  - Command: `git add app/Http/Controllers/PaymentController.php app/Http/Controllers/SaleController.php resources/js/Pages/Inventory/Modals/ItemsSell.vue resources/js/Pages/Accounting/Modals/SaleEdit.vue tests/Feature/Payments/PaymentSimpleListVisibilityTest.php tests/Feature/Controllers/SaleItemReservationTest.php`
  - **SaleEdit.vue pre-existing modifications warning**: this file already has 12+ unstaged modifications from the previous change (`d913ce6`). `git add` on the full path stages the WHOLE file delta, which includes the pre-existing modifications plus the T8 changes. The apply phase MUST:
    1. Run `git diff -- resources/js/Pages/Accounting/Modals/SaleEdit.vue` BEFORE staging to see the full delta.
    2. Compare the delta to the T8-only intent (warning toast loop). If the pre-existing modifications are orthogonal (different lines), `git add -p` with a y/n per hunk can isolate T8.
    3. If the pre-existing modifications overlap with T8's lines, fall back to staging the whole file and ask the user whether to ship the pre-existing changes in this commit or split.
  - Verify: `git status` shows ONLY the 6 expected files staged (or only SaleEdit.vue partial-stage). The 12 other unrelated modifications + 2 untracked entries remain unstaged.
  - Done when: `git status` shows only expected files staged for commit, and the user has been informed about the SaleEdit.vue staging decision.

- [ ] **T17 — Verify the diff is sensible**
  - Command: `git diff --cached --stat`
  - Verify: total changed lines ≤ 400 (allowing for the pre-existing SaleEdit.vue modifications, the budget may tighten — flag to user if approaching 400). The 6 expected files appear in the stat.
  - Done when: stat output is within budget.

- [ ] **T18 — Create the single conventional commit**
  - Subject (use this exactly):
    ```
    fix(sales): surface reserved-only sales in payments list and harden auto-assign
    ```
  - Use the multi-line body (see below). Use `git commit` (NOT `-m` with a single string) so the body is preserved.
  - Body:
    ```
    The previous SDD change (d913ce6) extended the storage-snapshot contract
    but left four "soft edges" in the surrounding flow. This change closes them.

    - PaymentController::getPaymentsSimpleList now returns sales with any
      combination of SOLD and RESERVED items, not just sales with at least
      one SOLD item. This fixes the AddItemsToSale modal (from inventory/items)
      and the AppendIncomingRequestToSale modal (from incoming requests),
      both of which were hiding unpaid-only sales from their dropdowns.
      Customer search for unpaid-only sales now works as a side effect.

    - SaleController::store and SaleController::update new-items loops
      now wrap the Storage::findFirstAvailablePosition() + Item::create()
      in a try/catch that retries the auto-assign once on a unique
      constraint violation (SQLSTATE 23000, which the items_storage_position_unique
      index throws on a race with another concurrent request). On the second
      collision the controller returns a 422 with a clear user-facing
      message. This is a partial defense — the durable fix (DB::transaction
      + lockForUpdate on the storage row, mirroring
      AppendIncomingRequestToInvoiceService:27-48) is tracked as a follow-up.

    - SaleController::update now has a code comment above the new-items
      loop explaining the load-bearing assumption: the existing-items loop
      must run first because findFirstAvailablePosition reads the DB and
      the new-items loop needs to see the just-persisted existing-items'
      positions. A regression test (R7.S1) exercises the contract under
      mixed existing+new items in the same request.

    - SaleController::store response shape changes from a raw string
      (the receipt URL) to an object {url, warnings[]}. The warnings
      array contains per-item entries {item_id, model, type, reason:
      "no_storage_available"} for items whose auto-assign fell back to
      null storage_id. SaleController::update response gains a warnings
      key (preserving the existing body shape). ItemsSell.vue migrates
      from reading the response as a string to reading data.url; both
      ItemsSell.vue and SaleEdit.vue surface each warning as a non-blocking
      useToast() call with severity 'warn'. This is a breaking change to
      sales.store — apply phase audit (T14) confirmed ItemsSell.vue is the
      only consumer.

    - Date-source alignment (user-verified during T15): PaymentController::
      getPaymentsData now uses sales.date (the user-picked payment date
      in the form) as the PRIMARY source for the response's `date` field,
      with items.sold, items.partially_sold_at, and sales.created_at as
      fallbacks. SaleEdit.vue's initial date parsing updated to consume
      the now-correct date from the Payments page response and parse the
      yyyy-MM-dd string in local time (not UTC) to avoid DatePicker
      display shift for users in negative-UTC timezones. The same sale
      now shows the same date across the Payments page, the simpleList
      dropdown, and the Edit Sale modal.

    Tests:
    - New PaymentSimpleListVisibilityTest covers R5.S1, R5.S2, R5.S3, R5.S4, R5.S5.
    - SaleItemReservationTest extended with R6.S1, R6.S2, R6.S3 (race catch),
      R7.S1 (loop order), R8.S1, R8.S2, R8.S3, R8.S4 (warnings payload + breaking change).
    - New test in PaymentCrudTest (test_payments_page_uses_sale_date_not_partially_sold_at)
      locks the Payments page date ordering contract.
    - ItemsSell.spec.ts updated to migrate the axios.post mock to the new
      {url, warnings} response shape.

    Out of scope (follow-up):
    - Durable race fix (DB::transaction + lockForUpdate).
    - Other SaleEdit modal bugs (Number(balance_remaining), hardcoded discount,
      misleading addItem, no newItems.* validation).
    - Ecommerce-channel scope, date format search, LIMIT=25 cap.
    - Item boot hook event order bug in app/Models/Item.php:322 vs :370.
    ```
  - The first line of the commit body MUST be a blank line after the subject.
  - Use `git commit` (not `-m` with a single string) so the body is preserved.
  - Done when: `git log -1` shows the commit with the subject and body.

- [ ] **T19 — Post-commit verification**
  - Run `git log --oneline -5` to confirm ONLY this one commit landed (plus the previous d913ce6).
  - Run `git show --stat HEAD` to confirm the diff matches the 6 expected files.
  - Run `vendor/bin/phpunit` and `npm run test:run` ONE MORE TIME to confirm the committed state is green.
  - Done when: all three commands succeed and outputs match expectations.

## Out-of-scope tasks (NOT to be done)

- **T-X1**: Do NOT create any new OpenSpec artifacts. (The archive phase handles that.)
- **T-X2**: Do NOT touch any file outside the 6 expected paths.
- **T-X3**: Do NOT implement the durable race fix (`DB::transaction` + `lockForUpdate`). Tracked as a follow-up.
- **T-X4**: Do NOT fix the other SaleEdit modal bugs. Out of scope.
- **T-X5**: Do NOT refactor the loop order into helpers. Comment-only.
- **T-X6**: Do NOT migrate the existing flat `warnings` shape in `getPaymentsData:115` to per-item. Divergence is accepted.
- **T-X7**: Do NOT change the simpleList's `LIMIT=25` cap or the order by `date DESC`.

## Handoff to next phase

After T19 succeeds, the change is complete. The next phase would be:
- sdd-verify (optional, runs spec scenarios as adversarial review)
- sdd-archive (closes the change, syncs the delta spec to the canonical spec)

The orchestrator should ask the user whether to run sdd-verify before sdd-archive.

## Workload forecast

- 19 tasks, 4 mechanical verification steps (T11-T14)
- ~310 estimated lines of change (well under 400)
- Single PR (when the user later requests one)
- Delivery strategy: `ask-on-risk` — T15 is the manual gate

Decision needed before apply: No — all product questions resolved. T15 manual gate is the only interactive point.

Chained PRs recommended: No

400-line budget risk: Low

## Skill resolution

`paths-injected` — orchestrator provided exact paths in this prompt. Loaded `~/.config/opencode/skills/_shared/sdd-phase-common.md` (conventions), and the previous change's `tasks.md` as a style reference. No additional skill load required.
