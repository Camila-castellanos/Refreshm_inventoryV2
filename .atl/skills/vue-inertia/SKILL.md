# Vue & Inertia Patterns

**Trigger**: When working on frontend components (Vue 3, Inertia.js, PrimeVue, Tailwind CSS).

## When to Use
Use this skill for all frontend changes, especially those involving the Dashboard and reactive filters.

## Critical Patterns

### Reactivity Synchronizer (Prop to Ref)
For dashboard-like components with filters:
- **Problem**: Inertia props remain static after the first load. AJAX/Axios updates (filters) don't automatically update local UI state if bound directly to props.
- **Solution**: Synchronize props into local `ref` or `reactive` variables.
- **Pattern**: 
  1. Initialize `ref` from `props.value`.
  2. Update `ref.value` inside `updateDashboardStats` (or the equivalent filter response handler).
  3. Bind child components to the `ref`, NOT the prop.

### UI Library usage (PrimeVue + Tailwind)
- Use PrimeVue for complex components: `Dialog`, `DataTable`, `Button`, `Calendar`.
- Use Tailwind CSS for: Layout (`flex`, `grid`, `p-`, `m-`), custom styling, and responsive design.
- Typography: Use Tailwind's text classes (`text-xl`, `font-bold`, etc.).

### Type Safety
- All shared interfaces and types MUST be in `resources/js/Lib/types.ts`.
- Components MUST use TypeScript and define props properly (e.g., `defineProps<{ ... }>()`).

## Examples

### Good Reactivity Sync
```vue
<script setup lang="ts">
import { ref, onMounted } from 'vue';
const props = defineProps<{ expenseBreakdown: any[] }>();
const currentBreakdown = ref(props.expenseBreakdown);

function updateStats(data) {
  currentBreakdown.value = data.expenseBreakdown;
}
</script>
```

## Commands
- Vitest: `npm run test:run`
- Build: (Do NOT build unless requested)
