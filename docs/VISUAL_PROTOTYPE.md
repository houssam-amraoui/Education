# Visual prototype review

This phase implements static Laravel pages for visual validation only. All learners,
content records, scores, balances, reports, conversations, and outcomes are fixtures.
There are no new migrations, database queries, authentication handlers, APIs, grading,
payment flows, message delivery, uploads, or persisted changes.

## Run and review

From the project directory:

```powershell
npm.cmd install
npm.cmd run build
C:/xampp/php/php.exe -S 127.0.0.1:8123 -t public
```

The PHP path above is the existing local XAMPP installation. On other machines,
use the installed PHP executable. The existing Laravel dependencies and `.env`
application key must be available. Database setup is not required.

- Public home: <http://127.0.0.1:8123/>
- Student dashboard: <http://127.0.0.1:8123/dashboard>
- Administration: <http://127.0.0.1:8123/admin>
- Use **Screen directory** on any page to find each inventory ID.
- Use **Explore / Student / Admin** to switch preview roles. This is not authentication.
- Shared learning links retain the student preview through a query parameter, with
  no session or authentication state.

## Coverage

The catalogue in `app/Support/Prototype.php` contains 89 destinations:

- G01–G23: public discovery, learning hierarchy, lessons, search/topics, practice and
  exam instructions, resources/documents, videos, account forms, and access states.
- S01–S18 and S22–S23: dashboard, learning goals, prerequisites, exercise/exam attempts,
  fixed results and history, progress, messages, rewards, points-unlock preview,
  profile, and preferences.
- A01–A39 and A43–A49: administration entry, records/editors, question authoring,
  relationships, communication, points/rewards, access, settings, and reports.

Create/edit variants use the same editor with `?mode=edit`. Nested editor navigation
retains the inventory's parent relationships through breadcrumbs. Catalogue variants
explicitly reuse one representative detail page rather than inventing more content.

Per the backlog, S19–S21 and A40–A42 remain outside this phase: leaderboard,
subscriptions, commercial fulfillment, and advertising. A37 previews streak settings
only. Premium content is classified in G23/A38–A39, without a purchase process.
Business analytics A49 shows an unavailable state instead of invented revenue metrics.

## Interaction and presentation

- Shared Blade components provide links/buttons, cards, badges, labeled fields,
  progress, and outline icons. Public, student, and admin navigation share a shell.
- Responsive grids collapse to single columns; dense tables scroll inside labeled,
  keyboard-focusable regions. The mobile navigation and screen directory use native
  modal dialogs with Escape handling, focus containment, and focus restoration.
- Search/filter controls operate on the current static fixtures. No-match and
  empty-history states can be previewed locally.
- Account, editor, preference, and message forms show local validation, a brief busy
  state, and explicitly simulated success. No form has a write endpoint.
- Seven question formats use native accessible controls, including selects for
  matching and ordering. Submission opens a fixed sample result. The exam timer is
  an explicitly labeled static timer; no scoring or timed-exam logic is present.
- Publication, archival, row ordering, lesson completion, and points unlocking only
  change the current presentation. Reloading resets every state.
- Ask Teacher from the lesson attaches visible lesson context. General composition
  has no fabricated lesson attachment.
- Documents use an HTML sample sheet, a printable layout, and a downloadable sample
  text file. The viewer returns to its source exam/resource. No original PDF is
  supplied. The video player is a labeled visual placeholder, without external media.
- Instrument Sans and Noto Sans Arabic are bundled locally through Vite. The language
  control previews translated navigation and LTR/RTL direction; full page-copy
  localization remains outside this visual pass. English teaching passages remain LTR.

## Verification

```powershell
C:/xampp/php/php.exe artisan test
npm.cmd run build
npm.cmd run test:prototype
```

The browser check expects the local server above and an installed Chrome at
`C:/Program Files/Google/Chrome/Application/chrome.exe`. Override `PROTOTYPE_URL`
and `CHROME_PATH` for another environment.

Laravel tests render every catalogue screen with an unusable default database
connection and database session configuration, verify contextual links and sample
states, and check that prototype routes accept only GET/HEAD. The browser check
renders every screen at 1440, 768, and 320px; audits page headings, internal targets,
and page overflow; exercises navigation, dialogs, filtering, validation, attempts,
archive/reorder, insufficient points, and RTL; and records browser errors.

Review screenshots are generated in `storage/app/prototype-review/`. Automated
checks complement visual review; they do not constitute a complete accessibility
audit or full localization validation.

Verified on 29 October 2026: 96 Laravel tests passed (464 assertions); all 89
screens passed at 1440, 768, and 320px (267 browser renders); interaction checks
passed with no browser errors; the production asset build and targeted PHP
formatting checks passed. No test failures remain.

## Files added or changed

- `routes/web.php`: named read-only prototype routes without database-backed sessions.
- `app/Support/Prototype.php`: screen IDs, destinations, templates, and hierarchy.
- `app/Http/Controllers/PrototypeController.php`: fixture page selection and breadcrumbs.
- `resources/views/components/prototype/`: badge, card, field, icon, link, progress.
- `resources/views/prototype/`: shared layout, page dispatcher, and feature partials.
- `resources/css/app.css`: design tokens, components, responsive and RTL styling.
- `resources/js/app.js`: temporary UI state and simulated interactions.
- `public/prototype/study-guide.txt`: downloadable sample educational content.
- `package.json`, `package-lock.json`: bundled fonts, browser test dependency and script.
- `tests/Feature/VisualPrototypeTest.php`: rendering and prototype boundary checks.
- `tests/Browser/visual-prototype.cjs`: responsive browser and interaction checks.
- `docs/VISUAL_PROTOTYPE.md`: scope, limitations, run instructions, and review guide.

Built Vite assets are emitted to the existing ignored `public/build/` directory.
The workspace has no Git metadata, so changes cannot be reported as a Git diff.
