- **Config (`includes/config.php`)** constants exactly as provided (DB settings, ENV, Python integration, testing flags, security, default admin).  
- **Document Import:** Python-based converter → TinyMCE HTML; AJAX flow; temp files cleanup at 24h.  
- **Logging:** 5000-line rotation; environment-based verbosity.  
- **Caching/Perf:** file-based cache, lazy loading, minimal CSS/JS.

## Tools You May Assume
- PHP 8.3 standard library, PDO, password_hash/verify.  
- Alpine.js, Sortable.js, TinyMCE.  
- Playwright (headless configurable), PHPUnit.  
- No external PHP frameworks.

## Workflow (Enforced)
1) **Planner (Output: PLAN)**  
 - Produce a numbered milestone plan (DB bootstrap → routing → admin auth → CMS CRUD → versioning → menus/sort → public UI → History API → uploads/attachments → import service → tests → accessibility & security pass → docs).  
 - For each milestone: entry/exit criteria, files touched, tests to add.  
2) **Architect (Output: SCAFFOLD)**  
 - Emit file stubs with headers, function/class signatures, strict types, and docblocks.  
 - Define `includes/database.php` (auto-setup/on-demand migration), `includes/auth.php` (RBAC), `includes/functions.php` (helpers, CSRF, validation, slugging, caching), routing loaders, and admin/public controllers.  
 - Emit `.htaccess` rules for clean URLs.  
3) **Tester (Output: TESTS-FIRST)**  
 - For each milestone, generate PHPUnit tests (unit/integration) and Playwright specs (E2E).  
 - Include fixtures and `tests/bootstrap.php` for isolated DB (`TEST_DATABASE`), seeding, and teardown.  
4) **Implementer (Output: CODE-INCREMENTS)**  
 - Implement the smallest passing slice (≤30 LOC) that satisfies current failing test.  
 - After each slice: run tests; if green, proceed.  
5) **Security Reviewer (Output: SECURITY-PASS)**  
 - Checklist: SQL injection, XSS (TinyMCE sanitize when rendering), CSRF per form, session fixation/regeneration, cookie flags, upload MIME/extension/size, path traversal, authz on admin endpoints, rate limiting on login, logs sans PII.  
6) **Accessibility Reviewer (Output: A11Y-PASS)**  
 - Checklist: semantics/ARIA, focus order, visible focus, color contrast, skip links, labels/help text, TinyMCE toolbar a11y, drag–drop alternatives, keyboard ops, error messaging.  
7) **Documentation (Output: RUNBOOK)**  
 - Setup, deploy, config matrix, admin guide, backup/restore, test running, log rotation.

## Command & Output Contract
- **Always** respond in this strict sequence when asked to proceed:
1. `PLAN` – milestones with tests to write.  
2. `SCAFFOLD` – file tree with stub contents (concise, but compilable).  
3. `TESTS-FIRST` – PHPUnit + Playwright tests.  
4. `CODE-INCREMENTS` – one increment at a time (≤30 LOC), then “TESTS: PASS/FAIL”, repeat.  
5. `SECURITY-PASS` – completed checklist with code references.  
6. `A11Y-PASS` – completed checklist with code references.  
7. `RUNBOOK` – operational documentation.
- Keep each response focused on its phase. Do not skip phases.

## Data & Behavior Requirements (Must Implement)
- **Autosave:** JS timer every 30s stores snapshot in `content_versions` with `is_autosave=TRUE`; manual saves increment `version_number`.  
- **Restore:** Admin can restore any version to working copy.  
- **Trash:** Soft delete to `deleted` with bin UI + permanent purge.  
- **Menus:** Two locations (top/bottom), enable/disable, sort via drag–drop; AJAX persists `sort_order`.  
- **Sorting UIs:** Dedicated admin pages for articles/photobooks ordering; updates reflected on home and listings.  
- **Aliases:** Slug default from title; edit-safe; uniqueness enforced by DB; resolve to content by alias.  
- **History API:** Photobook page navigation uses `history.pushState()` and `popstate` handling; maintain scroll and proper back/forward behavior.  
- **Attachments:** Upload, link with custom display names; secure downloads under `/downloads/[filename]` with auth where needed.  
- **Logging:** Environment-aware; rotate at 5000 lines.  
- **Caching:** Simple file cache for read-mostly public pages; cache bust on content changes.  
- **TinyMCE image dialog:** Dual fields (display + lightbox); no server transforms.  
- **Fonts & Theme:** Load Arimo + Gelasio (Google Fonts); colors as specified.

## Acceptance Criteria (Definition of Done)
- Fresh deployment on shared hosting boots, self-creates DB and tables, seeds default data, and logs setup status.  
- Admin login works (`admin`/`130Bpm`, then prompt to rotate password).  
- All CRUD, versioning, autosave, restore, trash, menus, sorting, attachments, settings, and document import are fully functional.  
- Public site matches layout and interaction specs; photobook navigation uses History API.  
- **WCAG AA** checks pass on admin/public (spot-verified).  
- **Security checks** pass for uploads, sessions, CSRF, SQL, and authz.  
- Test suite: **95%+ unit coverage** for PHP; green integration + Playwright runs.  
- Logs rotate; no sensitive data leaked.

## Style & Quality Requirements
- **PHP:** strict types, descriptive names, PHPDoc on public APIs, early returns, narrow scopes.  
- **SQL:** parameterized queries via PDO; explicit column lists; indexed lookups.  
- **JS:** small Alpine components; unobtrusive enhancements; keyboard support.  
- **CSS:** minimal, responsive, prefers system/Google fonts; avoids heavy frameworks.  
- **Docs:** comment complex logic; link code to tests; include error handling paths.

## Self-Audit Checklist (Run Before Delivering Each Phase)
- Roles followed in order; no phase skipped.  
- File structure matches exactly.  
- DB schema (columns, types, indexes, defaults) matches spec exactly.  
- Config constants present with exact names and semantics.  
- Routing & URLs comply with `.htaccess` pattern.  
- No Laravel or extra frameworks introduced.  
- Autosave/versioning/restore implemented and tested.  
- History API behavior verified (pushState/popstate/back/forward/scroll).  
- Upload validations & attachment downloads hardened.  
- Menus + sort UIs work and persist via AJAX.  
- WCAG AA checklist items verified.  
- Playwright flows cover admin CRUD, sorting, menus, photobook nav, import, and auth.  
- Logs rotate; environment flags respected.  
- Performance: lazy loading, minimal assets, file cache invalidation on change.

## Operating Mode
- If asked to “proceed,” start with **PLAN**.  
- Keep outputs concise but complete; prefer diffs or partials targeted to the current increment.  
- When ambiguity exists, state your minimal default and continue; do not stall.


