# Application Architecture

[Product requirements](PRD.md) · [Screen inventory](SCREEN_INVENTORY.md) · [Implementation tasks](TASKS.md) · [Prototype review](VISUAL_PROTOTYPE.md) · [Design system](DESIGN_SYSTEM.md)

## Application shape

Use one Laravel 12 application with server-rendered Blade, Vite, Eloquent, and a relational database. Public browsing, student workflows, and administration share models and UI components. The first learning path is 2nd Baccalaureate. Levels, units, and categories are editable records so additional paths use the same code.

The current prototype maps inventory IDs to static GET routes in `routes/web.php`. `PrototypeController`, `App\Support\Prototype`, and the prototype Blade views provide fixture data, navigation, and simulated outcomes. Preview roles and query parameters carry display state only. Reuse the shell and presentation components as live screens arrive. Replace fixture bindings with named routes, authorized model queries, and CSRF-protected forms under the relevant integration task. Keep unfinished previews separate from live routes and out of production navigation. Use screen IDs to verify coverage and task IDs to sequence work.

## Application modules

| Module and backlog | Responsibility |
| --- | --- |
| Foundations and settings · BASE, SETTINGS | Shared layouts, Blade components, locale selection, and validated platform settings. |
| Accounts and profiles · AUTH, PROFILE | Session authentication, student preferences and level selection, and authorized user and role administration. |
| Learning catalogue · STRUCT, LESSON, RES, VIDEO | The level hierarchy, lesson authoring, related materials, document delivery, video embeds, and publication lifecycle. |
| Assessments · QUESTION, EXERCISE, EXAM | Exercise and exam authoring, shared question validation and grading, document exams, owned attempts, results, and history. |
| Discovery · SEARCH, RECOMMEND | Topic associations, paginated search, prerequisite guidance, and next-content selection using authorized queries. |
| Learning progress · PROGRESS | Lesson activity, per-level and overall completion, score history, and dashboard summaries. |
| Teacher communication · MESSAGE | Student conversations and teacher replies, with authorized lesson or content context for Ask Teacher. |
| Rewards and access · REWARD, ACCESS | Points, streaks, achievements, unlocks, access requirements, and entitlement checks. |
| Analytics · ANALYTICS | Authorized period-based acquisition, engagement, learning, communication, and business reports based on recorded data. |

Administration uses the same feature models and policies through its own controllers and views. The dashboard composes queries from the owning modules. Build the access baseline before exposing live content. Add exercise and exam ownership before question authoring, grading before attempt submission, and the ledger before points unlocks.

Paid offers and teacher-service promotion belong to PAID and SERVICE. Offer configuration can follow its own backlog tasks. Verified fulfillment, expiry or reversal, lead handling, and business event definitions depend on SCOPE-01 through SCOPE-03. SUB, ADS, DAILY, and RANK are conditional and follow SCOPE-04 through SCOPE-07. ACCESS provides the entitlement check and REWARD provides the ledger used by enabled features. Treat metrics without a configured source as unavailable.

## Database entities and relationships

These are proposed tables. Add each through the migration for its owning task. The existing user model and baseline migrations are the starting point.

| Area | Entities and relationships |
| --- | --- |
| Identity | `users` holds credentials, name, locale, and supported learning preferences. Users belong to many `roles` through `role_user`. A role grants abilities from a code-defined list through `role_abilities`. `level_user` records the student's selected levels and any selection-specific preferences. |
| Curriculum | A `level` represents either a school level or a general learning path. Each level has many `units`, each unit has many `categories`, and each category has many `lessons`. A lesson's unit and level are derived through its category. Store ordering on siblings and lifecycle state where the editor supports publishing or archiving. |
| Library | `resources` store library metadata and reference `stored_files` with disk, storage key, media type, and size. Lessons link to attachments, resources, and `videos` through named associations. Videos store a supported provider and provider identifier. Document exams reference separate exam and correction files. Exercises, exams, resources, and videos have explicit level associations for global and level-filtered browsing. |
| Learning links | `topics` link to lessons, exercises, exams, resources, and videos via named pivots. Lessons link to related exercises and exams and to configured next lessons. Lessons, exercises, and exams can each reference prerequisite lessons through dedicated pivots. |
| Assessment definitions | `exercises` and `exams` own ordered `questions`. An exam is document-based or interactive. Exam availability belongs to the exam, with optional time limits for interactive exams. Each question belongs to exactly one exercise or interactive exam and stores its type, prompt, points, explanation, and type-specific option and answer data. |
| Attempt history | Each `attempt` belongs to one student and exactly one exercise or interactive exam. Store status, start/submission times, any server deadline, and computed totals. Its `attempt_items` preserve the ordered question and scoring snapshots captured at start, plus submitted answers and awarded points after finalization. A retry creates a new attempt. |
| Progress | `lesson_progress` has one row per student and lesson, with view and completion timestamps. Finalized attempts are the source of assessment scores and completion. Record distinct lesson-open, resource-open, and video-playback events where needed for period reports. |
| Communication | A `conversation` belongs to a student and can reference authorized source content. It has many `messages`, each with a sender from `users`. Students participate in their own threads. Teachers reply through the authorized management inbox. |
| Rewards | `point_rules` define qualifying events and costs. Append-only `point_transactions` belong to a student and carry a signed point amount and a stable event or unlock key. `achievements` have many `student_achievements`, unique per student and achievement. Derive streaks from qualifying learning dates under an explicit timezone and day boundary. |
| Access | `access_rules` target supported content or the named Ask Teacher feature. `entitlements` associate a student and target with a grant source and validity state. Points unlocks reference the debit and resulting grant. Premium grants use the same access check once their verified source is defined. |
| Settings and reporting | `platform_settings` stores supported settings keyed by name. Reports read operational tables wherever they already capture the measure. Add deduplicated acquisition or activity records only when a report needs a signal that is otherwise absent. |

Use foreign keys for concrete relationships and unique composite indexes for memberships, pivots, lesson progress, achievement awards, and replay keys. For shared question and attempt ownership, use concrete exercise/exam foreign keys with an exactly-one-owner constraint. Keep polymorphic references limited to cross-module targets such as access rules and message context. Use a fixed Eloquent morph map for supported content types, a separate allowlisted feature key, and validate targets on writes and resolution. Index published listings and time-ordered histories by their query scope. Archive authored records that have history and preserve attempt snapshots, conversation context, and ledger entries.

## Authentication and authorization

Use the existing `User` model and Laravel's session-backed `web` guard. Public registration validates a unique email, hashes the password, and assigns the student role server-side. Throttle login, regenerate the session on authentication, and invalidate the session and regenerate the CSRF token on logout. Return users to an internal intended destination and run its normal authorization check. Student preferences persist on the account. For guests, keep the locale in the session, resolve it against the enabled Arabic, French, and English locales, and use a configured fallback. Use Laravel translation files and Blade `lang` and `dir` attributes.

Use `auth` middleware for personalized routes and a management-entry gate for `admin.*`. Check specific abilities through policies on each management action, including user and role changes. Seed the baseline student, teacher, and administrator roles. Keep the assignable ability list in code and authorize the requested privilege changes as well as access to the editor. Public registration and profile changes accept no role or ability input.

For learner-facing content, use policies for individual records and reusable Eloquent query scopes for eligible listings. Both apply the same access semantics:

1. Check publication, ancestry, and applicable availability. An unavailable ancestor hides the descendant from learner discovery and access.
2. Public access needs no login. Registered, selected-level, prerequisite, points, and premium conditions require an authenticated student where applicable. Apply all required conditions across the target and its ancestors. Prerequisite links provide guidance unless a configured access rule makes completion mandatory.
3. A points or premium rule requires a valid entitlement of the corresponding kind. Missing required configuration denies access. Manager preview and editing use explicit management policies.
4. Check ownership independently for attempts, results, progress, and conversations. Manager drill-down and teacher replies require their own abilities. Check assessment access at attempt start, submission, and result review. Bind nested records to their real parents.

Apply these checks to canonical pages, direct file routes, search, topic results, recommendations, dashboards, and related-content links. Filter discovery before pagination. An access prompt may show only authorized requirement information for a visible target. Serve restricted files from a private Laravel filesystem disk through an authorized inline or download route, checking both the requested association and any separately protected resource.

### Access rule evaluation (ACCESS-03–08, ACCESS-14–17)

Rules target a level, lesson, resource, video, exercise, exam, or the Ask Teacher feature. Each target can have one rule per requirement. All applicable rules must pass. A public rule adds no restriction. It never overrides another rule or the existing content classification. A level requirement checks the student's selection of the configured published level. A prerequisite requirement checks the student's recorded completion of the configured lesson and that lesson's publication hierarchy. Existing prerequisite guidance remains independent of access requirements.

Registered access requires the student role. Managers use their authorized management routes for editing and preview. Resource and video placements retain their existing visibility semantics: standalone items can be accessed directly, and items assigned to levels require at least one accessible placement. A lesson-scoped resource route also checks its bound lesson and resource.

Points access checks the student's grant for the exact target and its associated unlock. Existing unlocks are backfilled, and new grants are written with their debit and unlock in one transaction. Changing a cost does not change an existing grant. Premium access reads grants already recorded for the student and target. Both grant types must have started and be unexpired and unrevoked.

## Validation and workflow boundaries

Use Form Requests for write operations and nontrivial filters. Authorize the action and validate shape, supported values, and references. Pass explicit validated fields to Eloquent. Validate relationship IDs within their parent scope and check state-dependent invariants in the write transaction. Enforce uniqueness and ownership in the database wherever the schema can express them.

- **Content authoring** uses scoped relationships and ordering, valid publication transitions, and prerequisite graph checks for self-links and cycles. Sanitize rich text with an allowlist before rendering it as HTML. Validate file type and size, and derive video embeds from a validated provider and identifier. On file replacement, keep the previous file until the new reference commits and clean up failed uploads.
- **Assessment input** uses a fixed list of the seven supported question types. Validate question definitions by type and validate learner answers against the attempt snapshot, including the allowed option, pair, and slot identifiers. Grade against configured points and answers on the server. Start with exact match per question and document text normalization and accepted alternatives in the applicable grading task. Render learner questions from an explicit allowlist that excludes answer keys and explanations until authorized result review.
- **Messaging and rewards** resolve the source and actor server-side. Validate Ask Teacher eligibility and linked content on conversation creation. Use saved reward costs, rule eligibility, and balances for unlocks. Settings accept only known keys with typed, bounded values.

Keep simple CRUD in resource controllers, using policies, Form Requests, and Eloquent. Extract an action class when a workflow has transactional rules or multiple callers:

| Workflow | Boundary |
| --- | --- |
| Start or submit an attempt | Start captures the current definitions and server timing. Submit locks the attempt, checks its state, enforces the saved deadline when present, grades through shared question-type logic, and writes a single final outcome. Repeated submissions return that outcome. Define and test expiration behavior in EXAM-10. |
| Record lesson completion | Persist completion once per student and lesson. Use a stable completion event key if reward evaluation is enabled. |
| Award points or unlock access | Serialize balance-changing writes for the student. Recheck rule, existing grant, and balance under the lock. Commit the debit and entitlement together. Deduplicate earning by rule and qualifying event, and apply the configured retry eligibility. |
| Open a conversation | Authorize the feature and source, then save the conversation and first message in one transaction. Replies use the thread policy and authenticated sender. |

Use deterministic PHP grading shared by exercise and exam actions. Put reusable read logic in Eloquent scopes or focused query classes for search, progress, recommendations, and reports. Build database search incrementally by content type. Rank recommendations from explicit next-lesson links, prerequisites, selected levels, completion, performance, and recent activity as those tasks land. Document ranking and progress/report denominators, including empty datasets, in the owning implementation tasks. Calculate reports from recorded activity with explicit event definitions, period boundaries, and deduplication.

### Exam expiration (EXAM-10)

The server captures start time in UTC at second precision. Timed attempts keep the deadline calculated from the duration at start. Untimed attempts have no deadline. Submission checks current exam access, then evaluates the server clock under the attempt lock. An eligible submission at or after the deadline records an expired outcome with the deadline as its expiration time. It stores no answers, points, or corrections. The first eligible late submission records the outcome, so an abandoned attempt has no finalized history entry. Completed or expired submissions replay their saved outcome while the student retains access. Exam publication, ancestry, availability, and student ownership continue to govern submission and review.

### Learning progress (PROGRESS-04–08, PROGRESS-14)

Overall completion divides the student's explicitly completed, currently visible lessons by all currently visible lessons across published levels. Per-level completion uses the same counts within one level. Percentages round to the nearest whole number. A scope with no eligible lessons returns zero completed, zero total, and 0%. Exercises and exams contribute score and activity history independently.

Activity is ordered newest first and filtered for current visibility before pagination. Each lesson contributes its latest view and first completion when present. Each submitted exercise attempt contributes its saved score. Each interactive exam attempt contributes a start and, when recorded, a completion with its saved score or an expiration without a score.

The dashboard reads the student's saved level selections from `level_user` and uses published selections for resume and recent lessons. Continue learning returns the most recently viewed incomplete lesson. An empty selection returns no recent lessons or resume target.

The dashboard and progress overview average percentages across visible submitted attempts with positive possible points. Each scored attempt counts equally. The weekly chart counts the recorded activity entries by UTC date from Monday through Sunday.

The manager policy requires administration entry and user management permissions for student drill-down. Attempt review also applies the selected student's existing ownership, finalization, and current assessment access rules.

### Reward eligibility (REWARD-05–10)

Enabled earning rules evaluate on the first lesson completion or a newly finalized exercise or interactive exam submission. An assessment rule with retries disabled considers only the first submitted attempt per student and assessment. Expired exams never count as submissions. Exercise score thresholds compare the exact saved score ratio. Multiple matching rules can award points for the same event, once per rule.

A learning day starts at midnight UTC. Lesson completions and submitted assessments count once per date. The current streak includes consecutive learning dates through today or yesterday, and a missed day resets it. An enabled milestone rule credits once per student, streak start date, and configured milestone length when a new finalization reaches that length.

Achievement criteria count distinct completed lessons, exercises, or exams, or consecutive learning days. Exercise achievements can require a minimum score. A new qualifying event can count prior activity toward a threshold. Each achievement and its optional point credit are awarded once per student.

Administrators manage earning rules, achievements, and target costs. A cost identifies a single exercise, exam, points-classified resource, or the Ask Teacher feature. The spending action saves the debit and unlock together. Replaying a purchase returns its saved unlock at the original cost. Access enforcement and the confirmation screen are covered by the access and reward integration tasks.

### Analytics definitions (ANALYTICS-03–08, ANALYTICS-14–18)

Reports require inclusive UTC dates `from` and `through`. Reads start at midnight and exclude midnight after the last date. Daily and monthly active-user series use UTC calendar buckets clipped to the requested period and omit empty buckets.

- Visitors are distinct browser sessions that successfully load a live public learning catalogue HTML page, including home and detail pages. Signed-in browsers count too. One visit per session per UTC day is stored using a hashed random token. A new session can count the same browser again.
- Registrations count committed public student signups recorded since deployment. Active students are distinct student accounts with a lesson open or completion, exercise or interactive exam start or submission, resource open, or video playback. Daily and monthly active users apply this student definition. Visits, signups, and messaging alone do not qualify.
- Resource opens count authorized GETs that resolve to an available PDF for display or download. Video playback counts the client signal from the embedded YouTube player entering its playing state, subject to current access. Each resource or video counts once per student account (or browser session for other viewers), target, and UTC day.
- Lesson opens count once per student, lesson, and UTC day. An existing progress row contributes its latest recorded view if that day has no matching open event. Views overwritten before event recording cannot be recovered. Lesson completions use the first saved completion timestamp.
- Assessment starts and completions are counted separately by their timestamps. An exercise repeat has an earlier attempt record for the same student and exercise, including attempts before the period. Exam counts cover interactive attempts. Expiration is not a completion.
- Streak reporting uses REWARD's distinct student-days of lesson completion or assessment submission at midnight UTC. It reports student-days, students with any qualifying day, and students with at least two consecutive qualifying days within the period.
- Assessment completion rates divide starts in the period submitted by its end by all starts in the period. Average scores give equal weight to submitted attempts with positive possible points, based on their saved percentages. Question difficulty divides incorrect results by all graded snapshot items from submissions in the period, grouped by source question and assessment type. Blank answers graded incorrect are included. Scores and rates with no denominator are null.
- Lesson progress divides completed student–lesson pairs through the end of the period by currently visible student–lesson pairs for active students who still have the student role. Visibility is evaluated at query time because historical eligibility is unavailable.
- Ask Teacher requests count conversations created in the period. Message totals include the initial request. Teacher replies use the saved sender classification. First reply latency averages elapsed seconds from conversation creation over conversations whose first teacher reply falls within the period.
- The backend report endpoint is `admin/analytics-data/{report?}`, with overview as the default. Access requires the administrator role and administration entry ability. Commercial measures are null with an explicit unavailable reason.

The live administration reports use `admin/analytics/{report?}` and default to the seven UTC dates ending today. Tabs preserve the selected dates. The chart shows the last seven dates or the full period if shorter. Communication reports show the current reply status of conversations active in the selected period. Student and conversation drill-downs require the destination policies. Business reporting explains which event sources are missing.

## Laravel folder organization


```text
app/
  Models/                  Eloquent models, relations, and local scopes
  Http/Controllers/        Shared catalogue and student entry points
    Auth/                  Registration, login, and logout
    Admin/                 Management and report controllers
  Http/Requests/           Form Requests grouped by feature as needed
  Http/Middleware/         Locale resolution and shared HTTP concerns
  Policies/                Record access, ownership, and management abilities
  Actions/                 Transactional workflows when extracted
  Queries/                 Cross-model reads when extracted
  Support/                 Focused utilities, including existing prototype fixtures
routes/web.php             Named web routes and public, student, and admin groups
resources/views/           Blade layouts, shared components, and feature views
resources/css/             Shared styles and design tokens
resources/js/              Browser behavior built through Vite
lang/{ar,fr,en}/           Interface translations
database/                 Migrations, factories, and seeders
tests/{Unit,Feature}/     PHPUnit suites
tests/Browser/             Browser journey and responsive checks
```

Organize folders as features land. Introduce an action or query class when it gives a real workflow or read model a clear home. Eloquent is the persistence boundary.

## Testing strategy

- **Feature tests** use Laravel HTTP tests, factories, and `RefreshDatabase` for persistence. Cover guest/student/manager policy combinations, privilege escalation, cross-student ownership, unpublished ancestors, protected file URLs, search and recommendation visibility, and locale fallback. Test the normal route for each write, including invalid and forged inputs.
- **Unit tests** cover deterministic grading for all supported question types, answer normalization, date and streak boundaries, and ranking rules when extracted. Use Laravel time controls for availability and exam deadlines in feature tests.
- **Data integrity tests** cover immutable history after authoring edits, duplicate submissions and completion, ledger replay, and atomic points spending and grants. Feature tests use the dedicated `laravel_test` database through the MySQL connection. Use separate connections when testing concurrent transactions. The Docker configuration uses MySQL 8.
- **Browser and release checks** retain fixture smoke coverage while preview routes exist. Add persisted journeys from RELEASE for guest-to-student learning, management publishing and exams, Ask Teacher replies, and points unlocks. Review desktop and mobile, keyboard and focus behavior, zoom, reduced motion, and Arabic RTL alongside French and English. Verify conditional flows included in the selected release.

Run `php artisan test` or `php artisan test --filter=...` for each implementation task. At release, run the full relevant suite, browser checks, and `npm run build`. Use local or testing seeders for the 2nd Baccalaureate demonstration journey.

### Local MySQL setup

Enable PHP's `pdo_mysql` extension and set the database host, port, name, and credentials in `.env`. Create the configured development database before running `composer setup` on a new checkout or `php artisan migrate` on an existing one.

Before running feature tests, create an empty `laravel_test` database on your development server.

```sql
CREATE DATABASE laravel_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

The tests use the database host and credentials from the local environment and rebuild the `laravel_test` schema. The test account needs schema, trigger, and data privileges on that database. Run `php artisan config:clear` before testing if Laravel has cached its configuration.

### Local demonstration data

With `APP_ENV=local` and a migrated development database, run `php artisan db:seed`.
Open `/learn/paths` to browse **2nd Baccalaureate · Demo**, or sign in at
`/account/login` as `demo.student@example.test` with the factory password
`password`. The student already has the demo level selected. Study Past
participles and The passive voice, then find the practice exercise and the
interactive grammar review through the Passive voice demo topic. The seed
starts the student with an empty activity history so the journey generates
its own progress and results.
