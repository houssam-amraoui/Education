# Project Guidelines

## Task scope

- Always check [TASKS.md](docs/TASKS.md) before starting any task.
- Read [PRD.md](docs/PRD.md), [ARCHITECTURE.md](docs/ARCHITECTURE.md), and the relevant [TASKS.md](docs/TASKS.md) entries before implementing. Use [SCREEN_INVENTORY.md](docs/SCREEN_INVENTORY.md) to identify affected screens.
- Implement only the requested task. For backlog work, take one task ID at a time unless explicitly asked to do more. Keep prototype, backend, and integration work in their respective scopes. Honor prerequisites and product decisions before enabling dependent features.
- Preserve existing behavior outside the request. Avoid unrelated refactors, formatting churn, new dependencies, and scaffolding for future tasks.

## Laravel conventions and boundaries

- Keep one Laravel application using Blade, Vite, Eloquent, and named web routes. Follow existing naming and folder conventions. Share feature models and policies across public, student, and admin screens. Add an API or architectural layer only when the task requires it.
- **Models** define relationships, casts, and local query scopes. Use migrations with foreign keys and unique constraints for data invariants. Preserve historical snapshots and ledger entries.
- **Controllers** handle HTTP concerns and simple CRUD. Use scoped route model binding, Form Requests for writes and nontrivial filters, and explicit validated fields for persistence.
- **Actions and services** use focused classes in `app/Actions` when a workflow needs a transaction or has multiple callers. Keep multi-record writes atomic and protect retryable or concurrent writes with constraints and locking where needed. Use scopes or `app/Queries` for shared reads. Use Eloquent directly for persistence.
- Use the session-backed `web` guard and CSRF-protected forms. Enforce access and ownership through server-side policies and visibility scopes across direct routes, listings, search, and private file delivery. Filter authorized results before pagination.

## UI freeze

- Treat the existing prototype and [DESIGN_SYSTEM.md](docs/DESIGN_SYSTEM.md) as the visual baseline. Preserve layouts, navigation, components, styling, and copy unless the task explicitly calls for a UI change or the scoped integration requires it.
- In backend tasks, leave Blade, CSS, and browser behavior alone. In integration tasks, replace fixtures with authorized data and real forms. Make only the UI changes needed to connect the flow and show its real states.
- Keep unfinished previews separate from live routes and production navigation. Preserve responsive layouts, keyboard access, and Arabic RTL alongside French and English.

## Testing and handoff

- Run `php artisan test` or a targeted filter after changes. Use feature tests for route behavior, validation, authorization, and persistence, and unit tests for deterministic logic. Use a dedicated MySQL test database for locking and database-specific constraints.
- Verify affected UI flows at relevant desktop and mobile widths. For release verification, run the full suite, browser checks, and `npm run build`.
- After completing a listed task and passing its relevant tests, mark its checkbox `[x]` in [TASKS.md](docs/TASKS.md). Keep incomplete or unverified tasks unchecked and leave unrelated entries untouched.
- Report changed files, checks run, and any failures or skipped checks with reasons.
