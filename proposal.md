# Proposal: Request Drawer CSV Export

## Intent
The user needs a way to export incoming requests to CSV for external processing or record-keeping. Currently, there is no export functionality in the Incoming Requests drawer.

## Scope

### In Scope
- Add "Export to CSV" button in the Request Details dialog.
- Add "Export All to CSV" button in the Incoming Requests sidebar.
- Create a shared CSV utility `resources/js/Utils/csvExport.ts`.
- Integrate the shared utility in `IncomingRequestsDrawer.vue`.

### Out of Scope
- Server-side CSV generation.
- Exporting processed (deleted/sold) requests.
- Custom field selection for export (will use a default set of useful headers).

## Capabilities

### New Capabilities
- `incoming-request-export`: Provides methods to export request data to CSV from the drawer.

### Modified Capabilities
None.

## Approach
Extract the CSV generation logic from `useInventoryActions.ts` into a dedicated utility `resources/js/Utils/csvExport.ts`. This utility will handle header mapping and blob creation. Update `IncomingRequestsDrawer.vue` to import this utility and add UI buttons to trigger exports for single or multiple requests.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `resources/js/Components/IncomingRequestsDrawer.vue` | Modified | Add export buttons and integration. |
| `resources/js/Utils/csvExport.ts` | New | Shared CSV generation utility. |
| `resources/js/Composables/useInventoryActions.ts` | Modified | Use the new shared CSV utility. |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Header mismatch | Low | Carefully map request item fields to CSV headers. |
| Large data sets | Low | Incoming requests are typically few in number; memory issues with Blobs are unlikely. |

## Rollback Plan
Revert changes in `IncomingRequestsDrawer.vue` and `useInventoryActions.ts`. Delete `resources/js/Utils/csvExport.ts`.

## Dependencies
None.

## Success Criteria
- [ ] Users can download a CSV of a single request's items.
- [ ] Users can download a CSV of all pending requests' items.
- [ ] CSV contains relevant fields (Model, Manufacturer, IMEI, Price, Request Info).
