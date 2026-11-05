# MVP Implementation Tasks

Sources: [PRD](docs/PRD.md), [screen inventory](SCREEN_INVENTORY.md), and [design system](DESIGN_SYSTEM.md). This is a planning backlog; no application work is included in creating this document.

## Scope and task rules

The PRD defines the complete product, without a separate MVP boundary. The working MVP below preserves its core learning journey: public discovery, accounts, structured lessons, all seven named question formats, exercises, document/interactive exams, resources, YouTube videos, search/topics, persistent progress, basic recommendations, teacher communication, configurable points/achievements/streaks, access controls, and administration/analytics for those capabilities. Start content with 2nd Baccalaureate; keep levels and categories configurable.

- Optional daily rewards, leaderboards, subscriptions, advertising integrations, additional video providers, and future question formats are outside this MVP backlog. Conditional inventory screens remain documented, not deleted.
- Premium classification and entitlement enforcement are included. Payment processing, paid-lesson fulfillment, teacher-service lead capture, and conversion tracking depend on unresolved product decisions listed at the end; the MVP must not be described as a complete commercial launch until those are resolved.
- Each checkbox is one independently requested implementation task. IDs remain stable; do not implement neighboring tasks automatically. If a task expands beyond its named outcome, split it before implementation.
- Each feature separates **1. Prototype**, **2. Backend implementation**, **3. Integration**, and **4. Testing**. Prototype means fixture-driven UI and local interactions without real persistence. Backend means migrations, models, validation, policies, and application behavior. Integration replaces fixtures with authorized live data and submits real forms. Testing adds focused behavioral coverage or records a focused manual check.
- Respect `AGENTS.md`: read `docs/` and relevant requirements first, preserve existing behavior, use Laravel conventions and existing patterns, run relevant tests after each change, and report changed files/results. Dedicated testing tasks do not postpone basic verification of implementation tasks.
- Use the design system for prototypes; include applicable empty, loading, error, restricted, and responsive states in the named component. Prototype success states must be clearly simulated.
- Backend tasks expose only the behavior needed by their named feature; do not add a separate API or framework unless the existing application needs it. Use Laravel policies/Form Requests and server-side validation where appropriate.
- A task is done when its stated outcome works, its relevant checks pass, and any limitation is reported. Do not mark dependent integration complete while its backend is absent.

## Dependency order

Prototype work can use fixtures independently after the shared foundations. Backend and integration work follow these prerequisites; security/access requirements apply before content is made reachable.

| Feature | Prerequisites for live integration |
| --- | --- |
| Foundation (`BASE`) | Existing project setup |
| Authentication / user roles (`AUTH`) | BASE |
| Profile / learning selection (`PROFILE`) | AUTH; level schema from STRUCT |
| Learning hierarchy (`STRUCT`) | BASE, AUTH, ACCESS baseline policies |
| Lessons (`LESSON`) | STRUCT, ACCESS; RES/VIDEO for real attachments/media |
| Resources (`RES`), videos (`VIDEO`) | STRUCT, AUTH, ACCESS |
| Question authoring/grading (`QUESTION`) | AUTH; EXERCISE/EXAM ownership schemas |
| Exercises (`EXERCISE`) | LESSON, QUESTION, ACCESS |
| Exams (`EXAM`) | QUESTION, RES, ACCESS |
| Topics / search (`SEARCH`) | Available content schemas and ACCESS visibility scopes |
| Progress (`PROGRESS`) | PROFILE, LESSON, EXERCISE, EXAM |
| Prerequisites / recommendations (`RECOMMEND`) | STRUCT, PROGRESS, ACCESS |
| Teacher messages (`MESSAGE`) | AUTH, LESSON, ACCESS |
| Points / rewards (`REWARD`) | AUTH, PROGRESS; ACCESS for unlock integration |
| Access / premium (`ACCESS`) | AUTH; add adapters as content schemas become available; REWARD for points unlocks |
| Platform settings (`SETTINGS`) | AUTH, BASE localization |
| Analytics (`ANALYTICS`) | Recorded events from implemented modules; authorized admin access |
| Release verification (`RELEASE`) | Included feature integrations and focused tests |

Build access policy primitives early, then connect each content type as it becomes available. Create exercise/exam ownership schemas before their question authoring tasks; connect attempt submission only after the relevant graders exist. Configure points access after the reward ledger exists. These are incremental dependencies, not whole-feature completion cycles.

## 1. Shared foundations — BASE

Screens: shared rules for all included screens; navigation entry points G01, S01, A01. PRD §§5, 22.

### 1. Prototype tasks

- [ ] **BASE-01** — Define design-system color, typography, spacing, and breakpoint tokens in the existing styling setup; demonstrate their use in an isolated fixture view.
- [ ] **BASE-02** — Prototype reusable button, link, badge, and feedback components with focus/disabled/loading variants.
- [ ] **BASE-03** — Prototype reusable labeled field, checkbox/radio group, and validation-summary components.
- [ ] **BASE-04** — Prototype reusable card, paginated table, and modal/drawer primitives with keyboard interactions.
- [ ] **BASE-05** — Prototype the public navigation shell with language selection and mobile menu.
- [ ] **BASE-06** — Prototype the student navigation shell with every included PRD destination reachable.
- [ ] **BASE-07** — Prototype the administration navigation shell with links to included management modules.

### 2. Backend implementation tasks

- [ ] **BASE-08** — Configure supported Arabic/French/English locales and locale validation; unsupported locale values fall back predictably.
- [ ] **BASE-09** — Add shared translation keys and direction metadata for navigation, controls, and common feedback.

### 3. Integration tasks

- [ ] **BASE-10** — Bind language switching to the supported locale mechanism and render correct `lang`/`dir` attributes.
- [ ] **BASE-11** — Connect shell links to implemented routes, active navigation, and breadcrumbs; expose only authorized destinations as modules arrive.

### 4. Testing tasks

- [ ] **BASE-12** — Test locale validation, fallback, persistence, and translated shared labels.
- [ ] **BASE-13** — Verify shell keyboard access, drawer focus, 320px layout, text zoom, and Arabic UI containing LTR English text; record results.

## 2. Authentication, users, and roles — AUTH

Screens: G21–G22, A02–A06. PRD §§3, 18–19. Do not add social login or unspecified account workflows.

### 1. Prototype tasks

- [ ] **AUTH-01** — Prototype registration with labeled fields, validation, and a simulated success state.
- [ ] **AUTH-02** — Prototype login with validation, failed-login feedback, and return-destination behavior.
- [ ] **AUTH-03** — Prototype the user list and reusable create/edit user form using fixtures.
- [ ] **AUTH-04** — Prototype the role list and reusable create/edit role form for the PRD roles.

### 2. Backend implementation tasks

- [ ] **AUTH-05** — Implement student registration with validated unique identity, hashed password, and safe student-role assignment.
- [ ] **AUTH-06** — Implement session login/logout with session regeneration, invalidation, and login throttling.
- [ ] **AUTH-07** — Persist role membership and define management authorization; public requests cannot grant management roles.
- [ ] **AUTH-08** — Implement authorized paginated user listing and validated account creation/update actions.
- [ ] **AUTH-09** — Implement authorized role definition/update and membership assignment without allowing unauthorized privilege changes.

### 3. Integration tasks

- [ ] **AUTH-10** — Connect registration/login/logout UI to real session behavior and safe intended redirects.
- [ ] **AUTH-11** — Connect user management list/form to persisted accounts and role assignment.
- [ ] **AUTH-12** — Connect role list/form to role management; display authorization and validation failures.

### 4. Testing tasks

- [ ] **AUTH-13** — Test registration uniqueness, role injection rejection, login throttling, session authentication, logout, and unsafe redirect rejection.
- [ ] **AUTH-14** — Test user/role management for permitted managers and forbidden guest/student access, including direct requests.

## 3. Profile and learning preferences — PROFILE

Screens: S02, S22–S23. PRD §§18, 22.

### 1. Prototype tasks

- [ ] **PROFILE-01** — Prototype the profile editor with saved/error states.
- [ ] **PROFILE-02** — Prototype language/preferences settings and multi-level/goal selection using fixtures.

### 2. Backend implementation tasks

- [ ] **PROFILE-03** — Implement validated self-profile updates; submitted IDs cannot modify another account.
- [ ] **PROFILE-04** — Persist selected levels/goals with valid level relationships and no duplicate selections.
- [ ] **PROFILE-05** — Persist supported language and learning preferences for the authenticated student.

### 3. Integration tasks

- [ ] **PROFILE-06** — Bind profile/settings forms to the authenticated user's persisted data.
- [ ] **PROFILE-07** — Connect level selection from path discovery, settings, and dashboard; saved selections survive a new session.

### 4. Testing tasks

- [ ] **PROFILE-08** — Test profile ownership, invalid locale/level rejection, multiple selected levels, and preference persistence.

## 4. Levels, units, and categories — STRUCT

Screens: G01–G06, A07–A12. PRD §§4, 19–20, 23.

### 1. Prototype tasks

- [ ] **STRUCT-01** — Prototype Home and path listing using public-content fixtures.
- [ ] **STRUCT-02** — Prototype level detail and unit listing with working fixture navigation.
- [ ] **STRUCT-03** — Prototype unit detail and category lesson listing with empty-category states.
- [ ] **STRUCT-04** — Prototype the level management list and create/edit form.
- [ ] **STRUCT-05** — Prototype the unit management list and create/edit form.
- [ ] **STRUCT-06** — Prototype the category management list and create/edit form.

### 2. Backend implementation tasks

- [ ] **STRUCT-07** — Add level/path persistence, validated management create/update, and public listing queries.
- [ ] **STRUCT-08** — Add unit persistence and validated management create/update with a required level relationship.
- [ ] **STRUCT-09** — Add category persistence and validated management create/update with a required unit relationship.
- [ ] **STRUCT-10** — Add level publish/unpublish/archive/reorder actions; nonpublic ancestors hide their descendants from public queries.
- [ ] **STRUCT-11** — Add unit publish/unpublish/archive/reorder actions scoped to its level.
- [ ] **STRUCT-12** — Add category archive/reorder actions scoped to its unit.

### 3. Integration tasks

- [ ] **STRUCT-13** — Bind Home, paths, and level detail to authorized live level/unit queries.
- [ ] **STRUCT-14** — Bind units, categories, and breadcrumbs to their real parent relationships.
- [ ] **STRUCT-15** — Connect level management forms and lifecycle controls to their backend actions.
- [ ] **STRUCT-16** — Connect unit management forms and lifecycle controls to their backend actions.
- [ ] **STRUCT-17** — Connect category management forms and lifecycle controls to their backend actions.

### 4. Testing tasks

- [ ] **STRUCT-18** — Test hierarchical validation, scoped ordering, and public exclusion of unpublished/archived ancestors and descendants.
- [ ] **STRUCT-19** — Test management authorization and level → unit → category navigation with an empty category.

## 5. Lessons and related content — LESSON

Screens: G07, A13–A15. PRD §§7, 19–20.

### 1. Prototype tasks

- [ ] **LESSON-01** — Prototype lesson reading with rich text, images, video, document links, and responsive English/Arabic content.
- [ ] **LESSON-02** — Prototype lesson management listing and content editor using fixtures.
- [ ] **LESSON-03** — Prototype the shared related-content selector for exercises, exams, resources, and next lessons.

### 2. Backend implementation tasks

- [ ] **LESSON-04** — Add lesson persistence and validated create/update actions within a category; hierarchy remains consistent.
- [ ] **LESSON-05** — Add safe rich-content processing and image handling; stored content cannot execute injected scripts or unsafe URLs.
- [ ] **LESSON-06** — Add lesson attachment/video/resource relationships using existing media records and authorized file references.
- [ ] **LESSON-07** — Add explicit related-exercise, related-exam, and next-lesson relationships with validated targets.
- [ ] **LESSON-08** — Add lesson publish/unpublish/archive/reorder actions and a visibility-scoped lesson detail query.

### 3. Integration tasks

- [ ] **LESSON-09** — Bind lesson listing/reading to real content, safe rendering, and authorized media links.
- [ ] **LESSON-10** — Connect lesson management listing/editor and publication/order controls to real actions.
- [ ] **LESSON-11** — Connect the related-content selector to persisted relationships and navigable learner links.

### 4. Testing tasks

- [ ] **LESSON-12** — Test authoring validation, unsafe rich content rejection/sanitization, relationship validation, and publication visibility.
- [ ] **LESSON-13** — Verify mixed-language lesson reading and related-content/media navigation without broken links.

Completion, prerequisites, and Ask Teacher are separate PROGRESS, RECOMMEND, and MESSAGE tasks.

## 6. Resources and documents — RES

Screens: G16–G18, A25–A26; document use from G07/G14. PRD §§9–10.

### 1. Prototype tasks

- [ ] **RES-01** — Prototype resource listing/search/filter and resource detail using fixtures.
- [ ] **RES-02** — Prototype document viewing with a download fallback when inline viewing is unavailable.
- [ ] **RES-03** — Prototype resource management list and create/edit upload form with file errors.

### 2. Backend implementation tasks

- [ ] **RES-04** — Add resource metadata/type persistence and validated create/update/list/filter queries.
- [ ] **RES-05** — Implement validated resource upload/replacement with allowed file types, size limits, and protected storage for restricted files.
- [ ] **RES-06** — Implement authorized inline/download responses for resources, lesson attachments, and exam/correction PDFs; direct URLs cannot bypass access.
- [ ] **RES-07** — Add resource publication/archive actions and learning-content relationships.

### 3. Integration tasks

- [ ] **RES-08** — Connect resource catalog/detail/filter UI and document viewer to authorized live files.
- [ ] **RES-09** — Connect management upload/edit/publication controls and existing attachment selectors to persisted resources.

### 4. Testing tasks

- [ ] **RES-10** — Test file type/size validation, replacement behavior, restricted-file authorization, and inline/download responses.
- [ ] **RES-11** — Verify PDF viewing, download fallback, and return-to-origin behavior from lesson, resource, and document exam contexts.

## 7. Video library — VIDEO

Screens: G19–G20, A27–A28. PRD §11.

### 1. Prototype tasks

- [ ] **VIDEO-01** — Prototype video listing/filter/search and watch view with unavailable-media feedback.
- [ ] **VIDEO-02** — Prototype video management list and create/edit source form.

### 2. Backend implementation tasks

- [ ] **VIDEO-03** — Add video metadata/source persistence, lesson relationships, and validated YouTube URL normalization; reject arbitrary iframe HTML.
- [ ] **VIDEO-04** — Add authorized video create/update and publish/unpublish/archive/reorder actions.
- [ ] **VIDEO-05** — Add visibility-scoped video listing/detail/search/filter queries.

### 3. Integration tasks

- [ ] **VIDEO-06** — Bind learner video views and related links to authorized records and safe embeds.
- [ ] **VIDEO-07** — Connect video management forms, lifecycle actions, and lesson video selection to real data.

### 4. Testing tasks

- [ ] **VIDEO-08** — Test source validation, unsafe embed rejection, publication filtering, and protected video metadata access.

## 8. Shared questions and grading — QUESTION

Screens: question controls within S04/S07 and A18–A19/A23–A24. PRD §§8–9. Reuse supported question behavior between exercises and interactive exams. Each type task covers only that type; no future plugin engine is required.

### 1. Prototype tasks

- [ ] **QUESTION-01** — Prototype single-choice answer and authoring controls with selected/error states.
- [ ] **QUESTION-02** — Prototype multiple-selection answer and authoring controls.
- [ ] **QUESTION-03** — Prototype true/false answer and authoring controls.
- [ ] **QUESTION-04** — Prototype matching answer and authoring controls with a keyboard/touch alternative to dragging.
- [ ] **QUESTION-05** — Prototype fill-in-the-blanks answer and accepted-answer authoring controls.
- [ ] **QUESTION-06** — Prototype short-answer and accepted-answer authoring controls.
- [ ] **QUESTION-07** — Prototype ordering answer and authoring controls with keyboard/touch reordering.
- [ ] **QUESTION-08** — Prototype reusable question list with add/edit/remove/reorder interactions using fixtures.

### 2. Backend implementation tasks

- [ ] **QUESTION-09** — Add owned question/answer-definition persistence and ordered exercise/exam associations; learner queries never expose correct-answer definitions before evaluation.
- [ ] **QUESTION-10** — Implement single-choice definition validation and deterministic grading with explanation output.
- [ ] **QUESTION-11** — Implement multiple-selection validation and grading; document the chosen exact-match scoring rule.
- [ ] **QUESTION-12** — Implement true/false definition validation and grading.
- [ ] **QUESTION-13** — Implement matching definition validation and grading with stable pair identifiers.
- [ ] **QUESTION-14** — Implement blanks validation and grading with explicit accepted answers and documented normalization.
- [ ] **QUESTION-15** — Implement short-answer validation and grading against explicit accepted answers; document normalization without implying free-form AI grading.
- [ ] **QUESTION-16** — Implement ordering validation and grading against the configured sequence.
- [ ] **QUESTION-17** — Implement authorized question add/update/remove/reorder actions with type-specific validation and ownership checks.

### 3. Integration tasks

- [ ] **QUESTION-18** — Bind question management lists to real exercise/exam ownership and ordering actions.
- [ ] **QUESTION-19** — Connect single-choice and true/false authoring forms to their validated definitions.
- [ ] **QUESTION-20** — Connect multiple-selection authoring to its validated definition.
- [ ] **QUESTION-21** — Connect matching authoring to its validated definition.
- [ ] **QUESTION-22** — Connect blanks and short-answer authoring to explicit accepted-answer definitions.
- [ ] **QUESTION-23** — Connect ordering authoring to its validated definition.
- [ ] **QUESTION-24** — Render the saved question type in learner attempts and serialize answers using the backend contract; keep answer keys absent from the pre-submission response.

### 4. Testing tasks

- [ ] **QUESTION-25** — Add table-driven validation/grading tests for single choice, multiple selection, and true/false, including malformed answers.
- [ ] **QUESTION-26** — Test matching and ordering grading for missing, repeated, and foreign identifiers.
- [ ] **QUESTION-27** — Test blanks/short-answer normalization, accepted alternatives, and deliberately incorrect answers.
- [ ] **QUESTION-28** — Test question ownership, management authorization, scoped reordering, and pre-submission answer-key secrecy.
- [ ] **QUESTION-29** — Verify matching/ordering and all other answer controls with keyboard, touch, and RTL content.

Initial scoring assumption: explicit configured question points; exact-match correctness per question without partial credit. Record these semantics during the type tasks and revisit if product requirements specify another rule. Saved submitted question/scoring snapshots are owned by attempt tasks below.

## 9. Exercise attempts and history — EXERCISE

Screens: G11–G12, S04–S06, A16–A17. PRD §8.

### 1. Prototype tasks

- [ ] **EXERCISE-01** — Prototype exercise catalog and instructions with prerequisites/access feedback.
- [ ] **EXERCISE-02** — Prototype exercise attempt submission using fixture questions and simulated results.
- [ ] **EXERCISE-03** — Prototype results/corrections and attempt history with retry navigation.
- [ ] **EXERCISE-04** — Prototype exercise management list and create/edit instructions/placement form.

### 2. Backend implementation tasks

- [ ] **EXERCISE-05** — Add exercise ownership schema and validated management create/update with learning placement and instructions.
- [ ] **EXERCISE-06** — Add exercise publish/unpublish/archive/reorder actions and visibility-scoped catalog/detail queries.
- [ ] **EXERCISE-07** — Add authenticated attempt creation with immutable question/scoring snapshots and per-student ownership.
- [ ] **EXERCISE-08** — Implement atomic submit/grade/finalize with server-computed totals; duplicate submissions cannot create duplicate outcomes.
- [ ] **EXERCISE-09** — Add owned attempt-history/result queries with saved answers, explanations, and a retry creating a separate attempt.

### 3. Integration tasks

- [ ] **EXERCISE-10** — Connect exercise management forms and lifecycle controls, linking to question authoring.
- [ ] **EXERCISE-11** — Bind learner catalog/instructions to live records and authorized attempt creation.
- [ ] **EXERCISE-12** — Connect answer submission, correction review, history, and retry to owned persisted attempts.

### 4. Testing tasks

- [ ] **EXERCISE-13** — Test scoring, forged totals, duplicate submission, cross-student access, retry separation, and historical results after question edits.
- [ ] **EXERCISE-14** — Verify lesson → exercise → submission → corrections → retry end to end.

## 10. Document and interactive exams — EXAM

Screens: G13–G15, S07–S09, A20–A24. PRD §9.

### 1. Prototype tasks

- [ ] **EXAM-01** — Prototype exam catalog and document-exam detail with exam/correction file links.
- [ ] **EXAM-02** — Prototype interactive-exam instructions and timed/untimed attempt states.
- [ ] **EXAM-03** — Prototype exam results/corrections and attempt history.
- [ ] **EXAM-04** — Prototype document-exam management list and create/edit form.
- [ ] **EXAM-05** — Prototype interactive-exam create/edit form with timing, availability, and question-management entry.

### 2. Backend implementation tasks

- [ ] **EXAM-06** — Add exam ownership/type schema, learning placement, availability, and authorized document-exam create/update with protected PDF references.
- [ ] **EXAM-07** — Add validated interactive-exam create/update with timing configuration and question ownership.
- [ ] **EXAM-08** — Add exam publish/unpublish/archive/reorder actions and availability-aware catalog/detail queries.
- [ ] **EXAM-09** — Add owned exam attempt creation with server start/deadline and immutable question/scoring snapshots.
- [ ] **EXAM-10** — Implement atomic exam submission/grading with server-enforced deadline and duplicate-finalization protection; document expiration behavior.
- [ ] **EXAM-11** — Add owned exam result/history queries returning stored answers, scores, corrections, and completion state.

### 3. Integration tasks

- [ ] **EXAM-12** — Connect document-exam management and learner detail to real authorized exam/correction files.
- [ ] **EXAM-13** — Connect interactive-exam management to timing/availability actions and shared question authoring.
- [ ] **EXAM-14** — Connect instructions, attempt start, timer, answer submission, and server expiration feedback.
- [ ] **EXAM-15** — Bind results/corrections/history to the student's persisted attempts.

### 4. Testing tasks

- [ ] **EXAM-16** — Test availability, file access, timed/untimed behavior, deadline boundaries, duplicate submission, and result ownership.
- [ ] **EXAM-17** — Verify document exam/correction viewing and interactive exam → score → history persistence across sessions.

## 11. Topics and global search — SEARCH

Screens: G08–G10, A29–A30. PRD §12.

### 1. Prototype tasks

- [ ] **SEARCH-01** — Prototype topic index/detail and topic management list/form using fixture relationships.
- [ ] **SEARCH-02** — Prototype global results with type, title, context, destination, and no-match state.

### 2. Backend implementation tasks

- [ ] **SEARCH-03** — Add topic persistence, validated management actions, archive behavior, and learning-content associations.
- [ ] **SEARCH-04** — Add global/level topic queries returning only visible related content.
- [ ] **SEARCH-05** — Implement a paginated global search query across lessons, exercises, exams, resources, videos, and topics using existing database capabilities.

### 3. Integration tasks

- [ ] **SEARCH-06** — Bind topic management, relationship selection, and learner topic navigation to live records.
- [ ] **SEARCH-07** — Connect global search form/results and canonical destination links; preserve the query during pagination.

### 4. Testing tasks

- [ ] **SEARCH-08** — Test all six result types, level context, no matches, pagination, and exclusion of inaccessible or unpublished content bodies.
- [ ] **SEARCH-09** — Test topic association validation and navigation to visible content after publish/archive changes.

## 12. Progress, activity, and dashboard — PROGRESS

Screens: S01, S10–S12, A04. PRD §§6, 13.

### 1. Prototype tasks

- [ ] **PROGRESS-01** — Prototype overview and per-level progress with completion, scores, and empty-history fixtures.
- [ ] **PROGRESS-02** — Prototype activity/score history and administrator student-detail reading.
- [ ] **PROGRESS-03** — Prototype dashboard learning/progress sections with continue-learning, selected levels, and recent scores.

### 2. Backend implementation tasks

- [ ] **PROGRESS-04** — Persist lesson views and explicit completion per student; repeating completion does not duplicate completed records.
- [ ] **PROGRESS-05** — Record finalized exercise/exam activity from existing attempt results without duplicating source scores.
- [ ] **PROGRESS-06** — Add overall/per-level completion and history queries with documented denominators and zero-content behavior.
- [ ] **PROGRESS-07** — Add continue-learning/recent-content queries scoped to current visibility and selected levels.
- [ ] **PROGRESS-08** — Add authorized manager queries for a student's progress, activity, and owned attempt review.

### 3. Integration tasks

- [ ] **PROGRESS-09** — Connect lesson viewed/completed behavior and progress/history views to persisted activity.
- [ ] **PROGRESS-10** — Bind the dashboard's selected levels, continue-learning, progress, recent lessons, and scores to live queries.
- [ ] **PROGRESS-11** — Connect administrator student detail to authorized progress/history and attempt review.

### 4. Testing tasks

- [ ] **PROGRESS-12** — Test completion idempotency, empty/multiple-level denominators, activity ownership, and persistence after logout/login.
- [ ] **PROGRESS-13** — Test that archived/restricted content cannot be opened through continue-learning, progress, or administrator links without authorization.

## 13. Prerequisites and basic recommendations — RECOMMEND

Screens: S03, A15; sections within G07/G12/G15/S01. PRD §14.

### 1. Prototype tasks

- [ ] **RECOMMEND-01** — Prototype prerequisite guidance and its editor with incomplete/complete preparation states.
- [ ] **RECOMMEND-02** — Prototype recommended-next-content sections with fixture reasons and an empty state.

### 2. Backend implementation tasks

- [ ] **RECOMMEND-03** — Persist lesson prerequisite links for lessons/exercises/exams; reject self-links, invalid targets, and cycles.
- [ ] **RECOMMEND-04** — Add student-specific prerequisite completion queries; distinguish recommendation from configured access restriction.
- [ ] **RECOMMEND-05** — Implement deterministic next-content selection from selected level, completion, configured relationships, recent activity, and exercise/exam performance; document ranking and exclude inaccessible targets.

### 3. Integration tasks

- [ ] **RECOMMEND-06** — Connect prerequisite editing and learner preparation guidance to live relationships/progress.
- [ ] **RECOMMEND-07** — Bind dashboard/lesson/result next-learning sections to the shared recommendation query.

### 4. Testing tasks

- [ ] **RECOMMEND-08** — Test cycle rejection, completion guidance, deterministic ranking, performance inputs, and visibility/access filtering.

## 14. Student–teacher messaging — MESSAGE

Screens: S13–S15, A31–A32; entry from G07. PRD §15.

### 1. Prototype tasks

- [ ] **MESSAGE-01** — Prototype student inbox and teacher conversation using fixture messages.
- [ ] **MESSAGE-02** — Prototype new-message/Ask Teacher composition with visible source-content context.
- [ ] **MESSAGE-03** — Prototype teacher conversation list/filter and reply view.

### 2. Backend implementation tasks

- [ ] **MESSAGE-04** — Add conversation/message persistence and participant policies; students cannot address or read other students' conversations.
- [ ] **MESSAGE-05** — Implement validated student conversation creation and follow-ups with authorized content context attached from the source.
- [ ] **MESSAGE-06** — Implement authorized teacher conversation queries, filtering, and reply actions.
- [ ] **MESSAGE-07** — Add student conversation/reply queries and recent-teacher-reply dashboard data.

### 3. Integration tasks

- [ ] **MESSAGE-08** — Connect Ask Teacher entry/composition, inbox, conversation reading, and student follow-ups.
- [ ] **MESSAGE-09** — Connect teacher list/filter/reply actions and valid source-content links.
- [ ] **MESSAGE-10** — Bind recent teacher replies on the dashboard to authorized conversations.

### 4. Testing tasks

- [ ] **MESSAGE-11** — Test participant isolation, rejected student-to-student messages, context tampering, restricted Ask Teacher access, and teacher reply ownership.
- [ ] **MESSAGE-12** — Verify lesson → question → teacher reply → student conversation end to end.

## 15. Points, streaks, and achievements — REWARD

Screens: S16–S18, A33–A37 excluding optional daily rewards/leaderboards. PRD §16.

### 1. Prototype tasks

- [ ] **REWARD-01** — Prototype rewards overview/achievement detail and dashboard reward summary with fixtures.
- [ ] **REWARD-02** — Prototype points-rule management list/form for earning and spending rules.
- [ ] **REWARD-03** — Prototype achievement management and streak settings forms.
- [ ] **REWARD-04** — Prototype points unlock confirmation/outcome including insufficient-balance feedback.

### 2. Backend implementation tasks

- [ ] **REWARD-05** — Add immutable point-ledger persistence and balance queries with source-event deduplication.
- [ ] **REWARD-06** — Add validated management of earning rules for lesson completion, exercise results, exam completion, and streak milestones.
- [ ] **REWARD-07** — Award configured points from finalized learning events exactly once; replays and retries follow explicit rule eligibility.
- [ ] **REWARD-08** — Add learning-day/streak calculation and configurable streak rules using an explicit timezone/day-boundary policy.
- [ ] **REWARD-09** — Add achievement definitions, criteria management, and unique student achievement awards from qualifying events.
- [ ] **REWARD-10** — Add configured spending costs and atomic point debit/unlock records; concurrent requests cannot overspend or charge twice for one unlock.

### 3. Integration tasks

- [ ] **REWARD-11** — Connect points-rule management and streak settings forms to persisted configuration.
- [ ] **REWARD-12** — Connect achievement management and student reward/detail views to live awards and criteria.
- [ ] **REWARD-13** — Bind dashboard points/streak/achievement summaries to real balances and awards.
- [ ] **REWARD-14** — Connect unlock confirmation to atomic spending and the resulting access entitlement.

### 4. Testing tasks

- [ ] **REWARD-15** — Test award replay, retry eligibility, disabled rules, achievement uniqueness, and timezone/day-boundary streak cases.
- [ ] **REWARD-16** — Test insufficient balance, concurrent spending, duplicate unlock requests, and owner isolation.

## 16. Access rules and premium classification — ACCESS

Screens: G23, S18, premium requirement portion of S20, A38–A39. PRD §§17, 19. Commercial fulfillment remains decision-gated below.

### 1. Prototype tasks

- [ ] **ACCESS-01** — Prototype contextual access requirements for guest, level, prerequisite, points, and premium restrictions.
- [ ] **ACCESS-02** — Prototype access-rule listing/editor with clear free/restricted classification and feature targets such as Ask Teacher.

### 2. Backend implementation tasks

- [ ] **ACCESS-03** — Add access-rule/entitlement persistence and a policy contract for public, registered, level, prerequisite, points, and premium access.
- [ ] **ACCESS-04** — Implement consistent guest/student/manager evaluation with explicit rule-combination semantics; deny access when required configuration is missing.
- [ ] **ACCESS-05** — Implement authorized rule create/update and target validation for supported content and Ask Teacher.
- [ ] **ACCESS-06** — Apply policies to level/lesson/resource/video reads and direct protected file delivery as each schema becomes available.
- [ ] **ACCESS-07** — Apply policies to exercise/exam start, submission/results where applicable, and messaging creation; UI-only checks are insufficient.
- [ ] **ACCESS-08** — Connect persisted points-unlock entitlements and premium entitlement lookups to policy decisions; no payment grants are invented.

### 3. Integration tasks

- [ ] **ACCESS-09** — Connect management access-rule editing from the central list and individual content editors.
- [ ] **ACCESS-10** — Bind content labels, access-requirement feedback, and sign-in/level/prerequisite recovery links to real policy outcomes.
- [ ] **ACCESS-11** — Connect points-restricted destinations to the unlock flow and re-evaluate access after a successful unlock.

### 4. Testing tasks

- [ ] **ACCESS-12** — Add a policy matrix covering roles, publication, rule combinations, missing configuration, and granted/missing entitlements.
- [ ] **ACCESS-13** — Test direct route/file/submission access and search/recommendation leakage for unauthorized users.

## 17. Platform settings and administration entry — SETTINGS

Screens: A01, A43. PRD §§19, 22–23.

### 1. Prototype tasks

- [ ] **SETTINGS-01** — Prototype administration home with included management destinations and platform settings form.

### 2. Backend implementation tasks

- [ ] **SETTINGS-02** — Add validated storage/read behavior for supported platform settings and enabled interface languages; accept only explicitly supported keys.
- [ ] **SETTINGS-03** — Add authorized settings update actions and configuration fallback behavior.

### 3. Integration tasks

- [ ] **SETTINGS-04** — Connect settings form/read behavior and administration entry to real authorized module routes.

### 4. Testing tasks

- [ ] **SETTINGS-05** — Test settings authorization, invalid/unknown key rejection, language fallback, and persistence.

## 18. Platform analytics — ANALYTICS

Screens: A44–A49; student drill-down A04. PRD §21. Included reports use actual recorded events; undefined commercial metrics remain unavailable rather than fabricated.

### 1. Prototype tasks

- [ ] **ANALYTICS-01** — Prototype analytics overview plus acquisition/engagement report views with clearly labeled fixture metrics.
- [ ] **ANALYTICS-02** — Prototype learning/communication reports and business-report unavailable state.

### 2. Backend implementation tasks

- [ ] **ANALYTICS-03** — Document visitor/active-user/event definitions and add minimal deduplicated acquisition/activity recording consistent with those definitions.
- [ ] **ANALYTICS-04** — Add acquisition aggregates for visitors, registrations, and active students over an explicit reporting period.
- [ ] **ANALYTICS-05** — Record resource/video usage and aggregate learning engagement from lesson/exercise/exam/streak source data.
- [ ] **ANALYTICS-06** — Add learning aggregates for completion rates, scores, progress, and question-level difficulty with documented denominators.
- [ ] **ANALYTICS-07** — Add communication aggregates for Ask Teacher requests, messages, and teacher response activity.
- [ ] **ANALYTICS-08** — Add authorized report/overview queries with shared reporting-period validation and explicit unavailable business metrics.

### 3. Integration tasks

- [ ] **ANALYTICS-09** — Bind overview/acquisition/engagement views to live aggregates and reporting-period controls.
- [ ] **ANALYTICS-10** — Bind learning/communication reports and authorized student/conversation drill-down links.
- [ ] **ANALYTICS-11** — Connect business-report availability to actual configured event sources; display missing-source explanations instead of zero conversions.

### 4. Testing tasks

- [ ] **ANALYTICS-12** — Test aggregate denominators, empty periods, event deduplication, period boundaries, and active-student definitions.
- [ ] **ANALYTICS-13** — Test report authorization, valid drill-down access, and unavailable business metrics when commercial sources are absent.

## 19. MVP release verification — RELEASE

PRD §§20, 22, 24. These tasks verify completed integrations; they do not add new features.

### 1. Prototype tasks

- [ ] **RELEASE-01** — Audit included prototype navigation against inventory IDs; record missing destinations or states without implementing them in this task.

### 2. Backend implementation tasks

- [ ] **RELEASE-02** — Add a local/testing-only demonstration seed for one coherent 2nd Baccalaureate learning journey using implemented factories; do not create production credentials.

### 3. Integration tasks

- [ ] **RELEASE-03** — Check integrated navigation for remaining fixture bindings and dead links; create individually scoped follow-up tasks for defects rather than fixing unrelated modules in this task.
- [ ] **RELEASE-04** — Connect the dashboard's exam/resource shortcuts to authorized selected-level queries; other dashboard sections are integrated by their owning feature tasks.

### 4. Testing tasks

- [ ] **RELEASE-05** — Verify guest browse → registration → level selection → lesson → exercise → correction → persisted progress with an automated journey where supported.
- [ ] **RELEASE-06** — Verify manager authoring/publication → student access → document/interactive exam → results/history with representative permissions.
- [ ] **RELEASE-07** — Verify lesson Ask Teacher → teacher reply and earned points → unlock → restricted content with no duplicate charges.
- [ ] **RELEASE-08** — Audit included public/student/admin flows in Arabic, French, and English at mobile/desktop widths with keyboard, zoom, and reduced motion; record defects individually.
- [ ] **RELEASE-09** — Run the full relevant automated suite and production asset build; report results and unresolved defects against PRD acceptance criteria.

## Decision-gated commercial work

These are requirement-resolution tasks, not permission to implement an unspecified commercial workflow. Once resolved, add separate prototype/backend/integration/test tasks with new IDs; preserve existing IDs.

- [ ] **SCOPE-01** — Define paid-content/paid-lesson fulfillment: entitlement source, purchase process, provider if any, access duration, failure/reversal behavior, and administrative responsibility. Output: agreed scope for S20/A40 and an implementable entitlement-grant contract.
- [ ] **SCOPE-02** — Define teacher-service promotion and lead capture: published offer information, student action, destination, and measurable lead event. Output: agreed scope for A40 and business conversion events.
- [ ] **SCOPE-03** — Define acquisition/business measurement requirements, including consent/retention decisions where applicable and what constitutes paid conversion or service lead. Output: approved event definitions and bounded follow-up tasks for A45/A49.

Subscription screens S21/A41, leaderboard S19, daily-reward portions of A37, and advertising configuration A42 remain conditional post-MVP scope. Do not generate implementation work for them until explicitly requested. General recovery/verification/account-deletion flows remain unspecified, as noted in the inventory.

## MVP completion boundary

The learning MVP is ready when its included feature tasks and relevant verification pass and each included PRD flow has real persistence, authorization, and usable feedback. Optional/decision-gated work must remain visibly unresolved. Passing this backlog's learning checks does not by itself satisfy the PRD's complete paid-content, teacher-service, or advertising business requirements.
