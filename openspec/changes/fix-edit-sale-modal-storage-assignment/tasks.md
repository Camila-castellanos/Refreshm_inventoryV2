# Tasks: fix-edit-sale-modal-storage-assignment

## Commit strategy

**Single final commit**, gated on user manual verification. No intermediate commits.

The apply phase makes all code + test changes in the working tree, runs the test suite to ensure it is green, and then **pauses** for the user to manually verify the bug is fixed. ONLY after the user confirms does the apply phase make ONE conventional commit with the consolidated diff.

## Pre-flight

- [ ] **T0 — Read pre-flight context**
  - Read `openspec/changes/fix-edit-sale-modal-storage-assignment/proposal.md`
  - Read `openspec/changes/fix-edit-sale-modal-storage-assignment/specs/sales/spec.md`
  - Read `openspec/changes/fix-edit-sale-modal-storage-assignment/design.md`
  - Confirm the file:line references in the design still match the live code (already verified: `:126-128`, `:170-222`, `:201`, `:312-321`, `:341-366`, `:364`, `Item.php:347-356`, `Item.php:349`, `Item.php:370-377`, `Storage.php:222-257`, `ItemsSell.vue:297` all match).
  - Done when: orchestrator has confirmed references match. If they have drifted, update the design or ask the user to re-spec.

## Phase 1: Implementation (no commits)

- [ ] **T1 — Fix Bug C: SaleController::store new-items branch (auto-assign storage)**
  - File: `app/Http/Controllers/SaleController.php`, between `:199` and `:201`
  - Action: insert the auto-assign block (design Change 1) AFTER the status/sold branch, BEFORE `Item::create`. Apply to BOTH paid and unpaid new items.
  - Add `use Illuminate\Support\Facades\Log;` and `use App\Models\Storage;` imports if not already present.
  - Done when: auto-assign block present in correct location, imports correct, `php -l app/Http/Controllers/SaleController.php` returns "No syntax errors detected".

- [ ] **T2 — Fix Bug B: SaleController::update existing-items paid branch (capture snapshot explicitly)**
  - File: `app/Http/Controllers/SaleController.php`, lines `:312-321`
  - Action: replace the `->update([...])` call with the `array_merge` pattern from design Change 2 verbatim, including the comment that references the `:126-128` precedent.
  - Done when: `$snapshot = []` guard and `array_merge([...], $snapshot)` present, no `position => null` and `storage_id => null` race the snapshot, `php -l` passes.

- [ ] **T3 — Fix Bug A: SaleController::update new-items branch (auto-assign storage)**
  - File: `app/Http/Controllers/SaleController.php`, between `:362` and `:364`
  - Action: insert the same auto-assign block as T1, before `Item::create` (`:364`). Use design Change 3 verbatim.
  - Done when: new-items branch contains the auto-assign block. `php -l` passes.

- [ ] **T4 — Fix Bug D: ItemsSell.vue newRowTemplate position type**
  - File: `resources/js/Pages/Inventory/Modals/ItemsSell.vue`, line `:297`
  - Action: change `position: ""` to `position: null`. One-line change.
  - Done when: line reads `position: null,` and grep confirms no other `position: ""` in `resources/js/**` (already verified: only match is this line).

## Phase 2: Test additions (no commits)

- [ ] **T5 — Create SaleControllerStoreTest.php (Bug C coverage)**
  - File: `tests/Feature/Controllers/SaleControllerStoreTest.php` (new)
  - Test: `test_store_paid_sale_with_new_items_assigns_storage`
  - Setup: paid sale POST to `SaleController::store` with `newItems` containing one item that has no `storage_id` and a `Storage` row with capacity.
  - Asserts: new item has `status = SOLD`, `storage_id` and `position` populated, and on a follow-up DB read `sold_storage_id`, `sold_position`, and `sold_storage_name` are populated.
  - Mirror the existing `SaleItemReservationTest.php` style (factories, refresh-database, auth).
  - Done when: `vendor/bin/phpunit tests/Feature/Controllers/SaleControllerStoreTest.php` passes.

- [ ] **T6 — Extend SaleItemReservationTest.php (Bug A, B coverage)**
  - File: `tests/Feature/Controllers/SaleItemReservationTest.php` (existing, 209 lines)
  - Add 3 test methods:
    - `test_update_paid_sale_with_new_item_assigns_storage` (Bug A)
    - `test_update_paid_sale_existing_item_populates_snapshot` (Bug B / R2.S1)
    - `test_update_paid_sale_resold_item_preserves_snapshot` (R2.S2 idempotency)
  - Done when: `vendor/bin/phpunit tests/Feature/Controllers/SaleItemReservationTest.php` passes with the 3 new tests green.

- [ ] **T7 — Extend ItemsSell.spec.ts (Bug D coverage)**
  - File: `resources/js/__tests__/pages/Inventory/Modals/ItemsSell.spec.ts` (existing, 250 lines)
  - Add a test (or assertion) that the newRowTemplate's `position` field is `null` (not `""`) when a new row is added.
  - Done when: `npm run test:run -- ItemsSell.spec.ts` passes.

- [ ] **T8 — Optional: ItemSavingHookTest.php (idempotency defense)**
  - File: `tests/Unit/Models/ItemSavingHookTest.php` (new)
  - Test: `test_saving_hook_does_not_clobber_existing_sold_storage_id_on_resave`
  - Setup: an item already SOLD with `sold_storage_id = 5`, `sold_position = 3`, `sold_storage_name = "Shelf A"`. Save again with no changes.
  - Assert: `sold_storage_*` triplet remains unchanged.
  - Done when: `vendor/bin/phpunit tests/Unit/Models/ItemSavingHookTest.php` passes.
  - **Note**: optional. Orchestrator MUST ask the user before skipping.

## Phase 3: Verification (no commits yet)

- [ ] **T9 — Run full PHP test suite**
  - Command: `vendor/bin/phpunit`
  - Done when: all tests pass, including the new/extended ones. Capture full output.

- [ ] **T10 — Run full JS test suite**
  - Command: `npm run test:run`
  - Done when: all JS tests pass. Capture output.

- [ ] **T11 — Run linters (if configured)**
  - PHP: `vendor/bin/pint` if present. Vue: `npm run lint` if configured.
  - Done when: no lint errors, or N/A if no linter.

- [ ] **T12 — Manual verification gate (PAUSE HERE)**
  - Apply phase MUST STOP and ask the user to verify manually.
  - Provide recipes for Bug B (existing-item), Bug A (update new-item), and Bug C parity (ItemsSell): open dev, create/edit a paid sale, add item, submit, then on `/inventory/report` confirm `sold_storage_name` and `sold_position` are populated (not "N/A").
  - Apply phase MUST NOT proceed to T13 until the user explicitly confirms "verified" or names what is still broken.
  - Done when: user has responded with confirmation or follow-up issues.

## Phase 4: Single final commit (gated on T12 confirmation)

- [ ] **T13 — Stage all changes**
  - Command: `git add -A`
  - Verify: `git status` shows only the expected files. NOTE: working tree currently has unrelated modifications (`.atl/skill-registry.md`, several other modals, `vite.config.js`, etc.) — these are NOT in scope and MUST NOT be staged. The apply phase MUST stage ONLY the 5 expected paths (+ optional 6th).
  - Done when: `git status` shows only expected files staged for commit.

- [ ] **T14 — Verify the diff is sensible**
  - Command: `git diff --cached --stat`
  - Verify: total changed lines ≤ 400. The expected files appear in the stat.
  - Done when: stat output is within budget.

- [ ] **T15 — Create the single conventional commit**
  - Subject: `fix(sales): preserve storage snapshot for new and transitioned sale items`
  - Use the multi-line body exactly as specified in the design handoff. Use `git commit` (not `-m`) to preserve the body. The first line of the body MUST be blank after the subject.
  - Done when: `git log -1` shows the commit with the subject and full body.

- [ ] **T16 — Post-commit verification**
  - Run `git log --oneline -5` to confirm only the one commit landed.
  - Run `git show --stat HEAD` to confirm the diff matches expected files.
  - Run `vendor/bin/phpunit` and `npm run test:run` ONE MORE TIME to confirm green.
  - Done when: all three commands succeed and outputs match expectations.

## Out-of-scope tasks (NOT to be done)

- **T-X1**: Do NOT create any new OpenSpec artifacts.
- **T-X2**: Do NOT touch any file outside the 5 expected (SaleController.php, ItemsSell.vue, SaleControllerStoreTest.php, SaleItemReservationTest.php, ItemsSell.spec.ts, optionally ItemSavingHookTest.php).
- **T-X3**: Do NOT fix the other SaleEdit modal bugs (Number(balance_remaining), hardcoded discount, misleading addItem, no newItems.* validation).
- **T-X4**: Do NOT add a UI storage picker to either modal. Auto-assign is the contract.
- **T-X5**: Do NOT extract a shared service for auto-assign. Inline the calls.

## Handoff

After T16 succeeds, the change is complete. Next phases (optional):
- sdd-verify: runs spec scenarios as adversarial review
- sdd-archive: closes the change and syncs the delta spec to canonical

Orchestrator should ask the user whether to run sdd-verify before sdd-archive.

## Workload forecast

- 16 tasks, 5 mechanical verification steps (T9-T12, T14, T16)
- ~243 estimated lines of change (well under 400)
- Single PR (when the user later requests one)
- Delivery strategy: `ask-on-risk` — T12 is the manual gate

Decision needed before apply: No (product questions resolved; only T12 manual gate is interactive)
Chained PRs recommended: No
Chain strategy: pending (single PR)
400-line budget risk: Low

## Skill resolution

`paths-injected` — orchestrator provided exact paths in this prompt; no additional skill load required.
