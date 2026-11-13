# Release verification

Verified 12 November 2026 against an isolated, migrated SQLite database. The
HTTP feature tests exercise the live routes and the Chrome checks use the
built assets. Existing prototype and live UI files were preserved.

## RELEASE-05 through RELEASE-07

- **RELEASE-05** follows guest curriculum navigation, registration, sign-in,
  path selection, lesson completion, exercise submission, and correction.
  Progress and the correction remain available after sign-out and sign-in.
- **RELEASE-06** verifies teacher authoring and publication for both exam
  types, guest and draft restrictions, student PDF viewing and download,
  interactive results and history, and cross-student isolation.
- **RELEASE-07** verifies lesson context and a teacher reply, lesson-earned
  points, insufficient-balance handling, and restricted exercise access.
  Replaying completion and spending leaves one credit, one debit, and one
  entitlement. A second student remains restricted.

Run these journeys with `php artisan test --filter=ReleaseJourneysTest`.

## RELEASE-08

Chrome checked G02 learning paths, S01 dashboard, and A01 administration home
in English, French, and Arabic at 1440px and 320px (18 combinations).
The audit exercised locale switching, document direction, keyboard skip links,
mobile drawer keyboard focus and Escape, reduced-motion navigation transitions,
and reflow at 100% and 200% CSS zoom. CSS zoom probes layout and does not
replace a browser-zoom or assistive technology audit.

| Defect | Screen | Reproduction |
| --- | --- | --- |
| RELEASE-QA-01 | G02 | The English main heading inherits Arabic language metadata at both widths. |
| RELEASE-QA-02 | S01 | The English main heading inherits Arabic language metadata at both widths. |
| RELEASE-QA-03 | A01 | The English main heading inherits Arabic language metadata at both widths. |
| RELEASE-QA-04 | G02 | At 320px and 200% CSS zoom, the Arabic view overflows horizontally. |
| RELEASE-QA-05 | S01 | At 320px and 200% CSS zoom, the dashboard overflows in English, French, and Arabic. |
| RELEASE-QA-06 | A01 | At 320px and 200% CSS zoom, the administration home overflows in English, French, and Arabic. |
| RELEASE-QA-07 | S02 | The live progress browser suite stops at learning goals because long unbroken path descriptions overflow. Reproduced at 1440px and 320px at normal zoom after running the curriculum browser fixtures. |

The keyboard and reduced-motion checks passed in the tested scenarios. The
localized navigation and document direction were present, while reviewed
headings still use English copy in French and Arabic. The audit covers
representative entry screens and does not claim full localization or
accessibility coverage for the remaining inventory.

To repeat the matrix, run `node tests/Browser/release-accessibility.cjs`
against an isolated local application seeded with the demo data. Set
`RELEASE_TEST_URL`, `RELEASE_TEST_ADMIN_EMAIL`, and
`RELEASE_TEST_ADMIN_PASSWORD` using a local test administrator. Optionally
set `CHROME_PATH` to a different Chrome executable.

## RELEASE-09

| Check | Result |
| --- | --- |
| `php artisan test` | 251 passed, 3,122 assertions |
| `npm run build` | Passed |
| Targeted Pint and browser script syntax checks | Passed |
| Live account and curriculum Chrome suites | Passed at 1440px and 320px |
| Visual prototype Chrome suite | Passed 89 screens at 1440px, 768px, and 320px (267 renders) |
| Live locale and accessibility matrix | 18 combinations checked, with defects listed above |
| Live progress Chrome suite | Failed at S02. Later steps in that script were skipped. The RELEASE-05 feature test passed persisted progress and correction checks. |

The initial browser matrix run overlapped the asset rebuild and returned a
missing-manifest error. The rerun used the completed build. The initial
progress suite invocation lacked fixture parameters. The rerun with a
published lesson, learner accounts, and a completed attempt reached S02.

Against PRD §24, the automated journeys support guest browsing, accounts,
curriculum navigation, assessment grading and history, document delivery,
teacher messaging, points, and access enforcement. The browser findings
leave responsive layout and language accessibility unresolved. The
[integrated navigation follow-ups](TASKS.md#integrated-navigation-audit-follow-ups)
record remaining gaps in administration and progress. Commercial fulfillment
and business reporting lack the approved sources and flows listed below.
SQLite verifies sequential replay. MySQL-specific locking and concurrent
transactions were outside this run because a dedicated MySQL 8 test database
was unavailable.

## RELEASE-10

Blocked by SCOPE-01 and PAID-05/PAID-08–10. There is no configured paid offer
or authorized purchase-to-entitlement path to exercise. The existing premium
file test passed for entitlement validity and protected delivery. That test
starts with a stored grant and cannot verify activation from an offer.

## RELEASE-11

The inventory, route list, and shared navigation were reviewed for each
conditional destination. The current release has no enabled feature in
this group, so destination and permission verification remains pending.

| ID | Current navigation | Pending prerequisite |
| --- | --- | --- |
| S19 | Absent from student rewards | RANK-06 and SCOPE-07 |
| S20 | Access requirements show the premium restriction without an offer destination | PAID-09/10 and SCOPE-01 |
| S21 | No student subscription destination | SUB-07 and SCOPE-04 |
| A40 | No paid offer or teacher-service configuration destination | PAID-08 and SERVICE-06, with SCOPE-01/02 for fulfillment and lead handling |
| A41 | No subscription configuration destination | SUB-06 and SCOPE-04 |
| A42 | No advertising configuration destination | ADS-07 and SCOPE-05 |
