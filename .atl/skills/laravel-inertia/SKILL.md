# Laravel & Inertia Backend

**Trigger**: When working on Laravel controllers, models, or backend logic for Inertia.js.

## When to Use
Use this skill for backend changes that serve data to the Inertia frontend.

## Critical Patterns

### Financial Metrics Grouping
- Use `DB::raw` for efficient grouping and summation.
- Handle `null` categories/values gracefully (default to 0 or 'Uncategorized').
- Always filter by `company_id` and `user_id` where applicable.

### Inertia Response Structure
- Controllers should return data in a consistent format that matching the `types.ts` on the frontend.
- Use `Inertia::render` with props that include necessary dashboard data (e.g., `expenseBreakdown`).

## Commands
- PHPUnit: `php artisan test`
