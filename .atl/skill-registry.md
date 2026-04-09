# Skill Registry

**Delegator use only.** Any agent that launches sub-agents reads this registry to resolve compact rules, then injects them directly into sub-agent prompts. Sub-agents do NOT read this registry or individual SKILL.md files.

See `_shared/skill-resolver.md` for the full resolution protocol.

## User Skills

| Trigger | Skill | Path |
|---------|-------|------|
| When creating a pull request, opening a PR, or preparing changes for review. | branch-pr | /home/johiny/.config/opencode/skills/branch-pr/SKILL.md |
| When writing Go tests, using teatest, or adding test coverage. | go-testing | /home/johiny/.config/opencode/skills/go-testing/SKILL.md |
| When creating a GitHub issue, reporting a bug, or requesting a feature. | issue-creation | /home/johiny/.config/opencode/skills/issue-creation/SKILL.md |
| When user says "judgment day", "judgment-day", "review adversarial", "dual review", "doble review", "juzgar", "que lo juzguen". | judgment-day | /home/johiny/.config/opencode/skills/judgment-day/SKILL.md |
| When user asks to create a new skill, add agent instructions, or document patterns for AI. | skill-creator | /home/johiny/.config/opencode/skills/skill-creator/SKILL.md |
| When working on frontend components (Vue 3, Inertia.js, PrimeVue, Tailwind CSS). | vue-inertia | .atl/skills/vue-inertia/SKILL.md |
| When working on Laravel controllers, models, or backend logic for Inertia.js. | laravel-inertia | .atl/skills/laravel-inertia/SKILL.md |

## Compact Rules

Pre-digested rules per skill. Delegators copy matching blocks into sub-agent prompts as `## Project Standards (auto-resolved)`.

### branch-pr
- Every PR MUST link an approved issue (Closes #N) and have exactly one `type:*` label.
- Branch naming: `type/description` (lowercase, hyphens/dots).
- PR body MUST follow template: summary, changes table, test plan, and checklist.
- Automated checks (issue link, approved status, labels, shellcheck) MUST pass.
- Conventional commits required: `type(scope): description`.

### go-testing
- Use Table-Driven Tests for multiple cases (pure functions, success/error).
- For Bubbletea TUI: test `Update()` directly for state changes; use `teatest.NewTestModel` for full flows.
- Visual output: compare against "golden" files in `testdata/`.
- Mock system/exec using interfaces + mocks; use `t.TempDir()` for file ops.
- Commands: `go test ./...`, `go test -update` (to refresh golden files).

### issue-creation
- MUST use Bug Report or Feature Request template; blank issues are disabled.
- Every issue starts as `status:needs-review`; MUST reach `status:approved` before PR implementation.
- Bug Report: includes pre-flight checks, reproduction steps, environment details (OS, Shell, Agent).
- Feature Request: identifies pain point, proposed solution, and affected area.
- Questions belong in Discussions, not Issues.

### judgment-day
- Parallel Blind Review: Launch 2 independent sub-agents via `delegate` simultaneously.
- Verdict Synthesis: Compare results for confirmed (both), suspect (one), or contradictions.
- Severity: CRITICAL | WARNING (real) | WARNING (theoretical) | SUGGESTION.
- Fix & Re-judge: Fix confirmed issues and re-launch judges until both pass (max 2 iterations before escalation).
- Approved criteria: 0 confirmed CRITICALs + 0 confirmed real WARNINGs.

### skill-creator
- Create skills for repeatable patterns, project-specific conventions, or complex workflows.
- Skill structure: `SKILL.md` (required), `assets/` (templates/schemas), `references/` (local docs).
- SKILL.md MUST include frontmatter (name, description/trigger, license, metadata), When to Use, Critical Patterns, Examples, and Commands.
- Naming: `{technology}`, `{project}-{component}`, `{action}-{target}`.
- Register by adding to `AGENTS.md`.

### vue-inertia
- Use PrimeVue for complex UI (`Dialog`, `DataTable`). Use Tailwind for layout and custom styling.
- **Reactivity Pattern**: Synchronize props into local `ref` variables in `updateDashboardStats` to ensure child components update correctly when filters change.
- All types MUST be in `resources/js/Lib/types.ts`.
- Command: `npm run test:run` (Vitest).

### laravel-inertia
- Use `DB::raw` for efficient grouping and summation of financial data.
- Gracefully handle `null` categories; default to 0 for empty results.
- Always filter by `company_id` and `user_id`.
- Command: `php artisan test` (PHPUnit).

## Project Conventions

| File | Path | Notes |
|------|------|-------|
| Frontend Types | resources/js/Lib/types.ts | Source of truth for shared interfaces. |
| Dashboard Logic | app/Http/Controllers/DashboardController.php | Primary financial data source. |
| Dashboard UI | resources/js/Pages/Dashboard.vue | Central UI for metrics and filters. |

Read the convention files listed above for project-specific patterns and rules. All referenced paths have been extracted — no need to read index files to discover more.
