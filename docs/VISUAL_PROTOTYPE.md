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
C:/xampp/php/php.exe artisan serve --host=127.0.0.1 --port=8123
```

The PHP path above is the existing local XAMPP installation. On other machines,
use the installed PHP executable. The existing Laravel dependencies and `.env`
application key must be available. Database setup is not required.
Use the local application environment for the preview routes. The Artisan server
routes A44–A49 through Laravel even though their URLs start with `/prototype/`,
which also contains static files.

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

### RELEASE-01 navigation audit

The 12 November 2026 audit matched the rendered Screen directory against all 95
inventory IDs. The preview includes 23 guest/shared, 20 student, and 46 admin
destinations. The six absent IDs belong to the following backlog entries.

| Inventory ID | Destination outside the preview | Backlog |
| --- | --- | --- |
| S19 | Leaderboard | RANK-01 |
| S20 | Paid offer and access outcomes | PAID-01 |
| S21 | Subscription terms and access status | SUB-01 |
| A40 | Paid offer and teacher-service editors | PAID-02, SERVICE-02 |
| A41 | Subscription plan editor | SUB-02 |
| A42 | Advertising and promotional settings | ADS-01 |

G18 has a source-return gap. From G07, **Open the study guide** opens the
document viewer, whose **Back to resource** link leads to G17. The viewer has a
return path for G14 exam and correction previews, while the lesson attachment
has no return-to-lesson state. The destination itself renders.

G23 previews the premium access requirement and returns to G16. The offer and
fulfillment states belong to S20. A49 explicitly shows that business metrics
are unavailable.

The earlier RELEASE-01 note about missing file errors is stale. A26 displayed
feedback for both a missing file and a non-PDF file at 1440px and 320px.

The browser suite passed all 267 renders at 1440px, 768px, and 320px with the
Artisan server. The full Laravel suite passed 244 tests with 2,952 assertions,
and the production asset build passed.

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
