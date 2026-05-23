## Exploration: request-drawer-csv-export

### Current State
The "Incoming Requests" drawer (`IncomingRequestsDrawer.vue`) currently allows viewing, processing (creating/appending to invoice), and deleting requests. However, it lacks an export function. CSV export logic exists in other parts of the app:
- `useInventoryActions.ts` (manual generation)
- `ExportCSV.vue` / `DataTable.vue` (PrimeVue built-in export)

### Affected Areas
- `resources/js/Components/IncomingRequestsDrawer.vue` — Main component where the export buttons and logic will be added.
- `resources/js/Utils/csvExport.ts` — (New) A utility to centralize CSV generation logic to keep it DRY.

### Approaches
1. **Manual CSV Generation in Component** — Add helper functions directly in `IncomingRequestsDrawer.vue` to generate and download CSVs for the active request or all requests.
   - Pros: Simple, no external dependencies, fits existing UI.
   - Cons: Duplicates some logic found in `useInventoryActions.ts`.
   - Effort: Low

2. **Shared CSV Utility** — Extract CSV generation logic into a utility file and use it in both `useInventoryActions.ts` and `IncomingRequestsDrawer.vue`.
   - Pros: Clean, DRY, reusable.
   - Cons: Requires minor refactoring of existing code.
   - Effort: Medium

3. **Refactor Drawer to use `DataTable`** — Replace the custom list in the drawer with the `DataTable` component.
   - Pros: Consistent with other tables, "free" export.
   - Cons: Overkill for a narrow sidebar, might require significant UI rework.
   - Effort: High

### Recommendation
I recommend **Approach 2 (Shared CSV Utility)**. Extracting the logic into `resources/js/Utils/csvExport.ts` ensures consistency across the app while keeping `IncomingRequestsDrawer.vue` clean and maintaining its current lightweight UI.

### Risks
- Data mapping: Ensuring the CSV headers match the fields available in incoming request items.
- Browser compatibility: Standard `Blob` approach should work fine in modern browsers.

### Ready for Proposal
Yes — The user should proceed with the proposal.
