# Application Implementation Tasks

This backlog covers the application described in the [PRD](PRD.md) and [screen inventory](SCREEN_INVENTORY.md). Use the [design system](DESIGN_SYSTEM.md) for interface work.

## Scope and task rules

Start with the 2nd Baccalaureate learning journey and keep levels and categories configurable. Existing prototype checkmarks describe the earlier 89-screen review. The additional inventory screens have their own tasks below.

- **Conditional** modules apply when the capability is selected for the release. **Decision needed** tasks depend on the cited scope decision. Their mockups can use neutral fixtures while the live workflow is defined.
- Select one task ID at a time. For an integration task, finish the corresponding prototype and backend behavior first. Run focused tests as each behavior becomes available, then use the release tasks to check the assembled flows.
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
| Paid offers (`PAID`) | AUTH, ACCESS. Fulfillment requires SCOPE-01, and conversion reporting requires SCOPE-03. |
| Teacher services (`SERVICE`) | AUTH. Lead handling requires SCOPE-02, and reporting requires SCOPE-03. |
| Subscriptions (`SUB`) | ACCESS, PAID offer configuration, and SCOPE-04 |
| Advertising (`ADS`) | SETTINGS and SCOPE-05. Reward credit also requires REWARD. |
| Daily rewards (`DAILY`) | REWARD ledger, SETTINGS, and SCOPE-06 |
| Leaderboards (`RANK`) | REWARD activity, SETTINGS, and SCOPE-07 |
| Release verification (`RELEASE`) | Required integrations and focused tests for the selected release |

Build access policy primitives early, then connect each content type as it becomes available. Create exercise/exam ownership schemas before their question authoring tasks; connect attempt submission only after the relevant graders exist. Configure points access after the reward ledger exists. These are incremental dependencies, not whole-feature completion cycles.

## 1. Shared foundations — BASE

Screens: shared rules for all included screens; navigation entry points G01, S01, A01. PRD §§5, 22.

### 1. Prototype tasks

- [x] **BASE-01** — Define design-system color, typography, spacing, and breakpoint tokens in the existing styling setup; demonstrate their use in an isolated fixture view.
- [x] **BASE-02** — Prototype reusable button, link, badge, and feedback components with focus/disabled/loading variants.
- [x] **BASE-03** — Prototype reusable labeled field, checkbox/radio group, and validation-summary components.
- [x] **BASE-04** — Prototype reusable card, paginated table, and modal/drawer primitives with keyboard interactions.
- [x] **BASE-05** — Prototype the public navigation shell with language selection and mobile menu.
- [x] **BASE-06** — Prototype the student navigation shell with every included PRD destination reachable.
- [x] **BASE-07** — Prototype the administration navigation shell with links to included management modules.

### 2. Backend implementation tasks

- [x] **BASE-08** — Configure supported Arabic/French/English locales and locale validation; unsupported locale values fall back predictably.
- [x] **BASE-09** — Add shared translation keys and direction metadata for navigation, controls, and common feedback.

### 3. Integration tasks

- [x] **BASE-10** — Bind language switching to the supported locale mechanism and render correct `lang`/`dir` attributes.
- [x] **BASE-11** — Connect shell links to implemented routes, active navigation, and breadcrumbs; expose only authorized destinations as modules arrive.

### 4. Testing tasks

- [ ] **BASE-12** — Test locale validation, fallback, persistence, and translated shared labels.
- [ ] **BASE-13** — Verify shell keyboard access, drawer focus, 320px layout, text zoom, and Arabic UI containing LTR English text; record results.

## 2. Authentication, users, and roles — AUTH

Screens: G21–G22, A02–A06. PRD §§3, 18–19. Do not add social login or unspecified account workflows.

### 1. Prototype tasks

- [x] **AUTH-01** — Prototype registration with labeled fields, validation, and a simulated success state.
- [x] **AUTH-02** — Prototype login with validation, failed-login feedback, and return-destination behavior.
- [x] **AUTH-03** — Prototype the user list and reusable create/edit user form using fixtures.
- [x] **AUTH-04** — Prototype the role list and reusable create/edit role form for the PRD roles.

### 2. Backend implementation tasks

- [x] **AUTH-05** — Implement student registration with validated unique identity, hashed password, and safe student-role assignment.
- [x] **AUTH-06** — Implement session login/logout with session regeneration, invalidation, and login throttling.
- [x] **AUTH-07** — Persist role membership and define management authorization; public requests cannot grant management roles.
- [x] **AUTH-08** — Implement authorized paginated user listing and validated account creation/update actions.
- [x] **AUTH-09** — Implement authorized role definition/update and membership assignment without allowing unauthorized privilege changes.

### 3. Integration tasks

- [x] **AUTH-10** — Connect registration/login/logout UI to real session behavior and safe intended redirects.
- [x] **AUTH-11** — Connect user management list/form to persisted accounts and role assignment.
- [x] **AUTH-12** — Connect role list/form to role management; display authorization and validation failures.

### 4. Testing tasks

- [ ] **AUTH-13** — Test registration uniqueness, role injection rejection, login throttling, session authentication, logout, and unsafe redirect rejection.
- [ ] **AUTH-14** — Test user/role management for permitted managers and forbidden guest/student access, including direct requests.

## 3. Profile and learning preferences — PROFILE

Screens: S02, S22–S23. PRD §§18, 22.

### 1. Prototype tasks

- [x] **PROFILE-01** — Prototype the profile editor with saved/error states.
- [x] **PROFILE-02** — Prototype language/preferences settings and multi-level/goal selection using fixtures.

### 2. Backend implementation tasks

- [x] **PROFILE-03** — Implement validated self-profile updates; submitted IDs cannot modify another account.
- [x] **PROFILE-04** — Persist selected levels/goals with valid level relationships and no duplicate selections.
- [x] **PROFILE-05** — Persist supported language and learning preferences for the authenticated student.

### 3. Integration tasks

- [x] **PROFILE-06** — Bind profile/settings forms to the authenticated user's persisted data.
- [x] **PROFILE-07** — Connect level selection from path discovery, settings, and dashboard; saved selections survive a new session.

### 4. Testing tasks

- [ ] **PROFILE-08** — Test profile ownership, invalid locale/level rejection, multiple selected levels, and preference persistence.

## 4. Levels, units, and categories — STRUCT

Screens: G01–G06, A07–A12. PRD §§4, 19–20, 23.

### 1. Prototype tasks

- [x] **STRUCT-01** — Prototype Home and path listing using public-content fixtures.
- [x] **STRUCT-02** — Prototype level detail and unit listing with working fixture navigation.
- [x] **STRUCT-03** — Prototype unit detail and category lesson listing with empty-category states.
- [x] **STRUCT-04** — Prototype the level management list and create/edit form.
- [x] **STRUCT-05** — Prototype the unit management list and create/edit form.
- [x] **STRUCT-06** — Prototype the category management list and create/edit form.

### 2. Backend implementation tasks

- [x] **STRUCT-07** — Add level/path persistence, validated management create/update, and public listing queries.
- [x] **STRUCT-08** — Add unit persistence and validated management create/update with a required level relationship.
- [x] **STRUCT-09** — Add category persistence and validated management create/update with a required unit relationship.
- [x] **STRUCT-10** — Add level publish/unpublish/archive/reorder actions; nonpublic ancestors hide their descendants from public queries.
- [x] **STRUCT-11** — Add unit publish/unpublish/archive/reorder actions scoped to its level.
- [x] **STRUCT-12** — Add category archive/reorder actions scoped to its unit.

### 3. Integration tasks

- [x] **STRUCT-13** — Bind Home, paths, and level detail to authorized live level/unit queries.
- [x] **STRUCT-14** — Bind units, categories, and breadcrumbs to their real parent relationships.
- [x] **STRUCT-15** — Connect level management forms and lifecycle controls to their backend actions.
- [x] **STRUCT-16** — Connect unit management forms and lifecycle controls to their backend actions.
- [x] **STRUCT-17** — Connect category management forms and lifecycle controls to their backend actions.

### 4. Testing tasks

- [ ] **STRUCT-18** — Test hierarchical validation, scoped ordering, and public exclusion of unpublished/archived ancestors and descendants.
- [ ] **STRUCT-19** — Test management authorization and level → unit → category navigation with an empty category.

## 5. Lessons and related content — LESSON

Screens: G07, A13–A15. PRD §§7, 19–20.

### 1. Prototype tasks

- [x] **LESSON-01** — Prototype lesson reading with rich text, images, video, document links, and responsive English/Arabic content.
- [x] **LESSON-02** — Prototype lesson management listing and content editor using fixtures.
- [x] **LESSON-03** — Prototype the shared related-content selector for exercises, exams, resources, and next lessons.

### 2. Backend implementation tasks

- [x] **LESSON-04** — Add lesson persistence and validated create/update actions within a category; hierarchy remains consistent.
- [x] **LESSON-05** — Add safe rich-content processing and image handling; stored content cannot execute injected scripts or unsafe URLs.
- [x] **LESSON-06** — Add lesson attachment/video/resource relationships using existing media records and authorized file references.
- [x] **LESSON-07** — Add explicit related-exercise, related-exam, and next-lesson relationships with validated targets.
- [x] **LESSON-08** — Add lesson publish/unpublish/archive/reorder actions and a visibility-scoped lesson detail query.

### 3. Integration tasks

- [x] **LESSON-09** — Bind lesson listing/reading to real content, safe rendering, and authorized media links.
- [x] **LESSON-10** — Connect lesson management listing/editor and publication/order controls to real actions.
- [x] **LESSON-11** — Connect the related-content selector to persisted relationships and navigable learner links.

### 4. Testing tasks

- [ ] **LESSON-12** — Test authoring validation, unsafe rich content rejection/sanitization, relationship validation, and publication visibility.
- [ ] **LESSON-13** — Verify mixed-language lesson reading and related-content/media navigation without broken links.

Completion, prerequisites, and Ask Teacher are separate PROGRESS, RECOMMEND, and MESSAGE tasks.

## 6. Resources and documents — RES

Screens: G16–G18, A25–A26; document use from G07/G14. PRD §§9–10.

### 1. Prototype tasks

- [x] **RES-01** — Prototype resource listing/search/filter and resource detail using fixtures.
- [x] **RES-02** — Prototype document viewing with a download fallback when inline viewing is unavailable.
- [x] **RES-03** — Prototype resource management list and create/edit upload form with file errors.

### 2. Backend implementation tasks

- [x] **RES-04** — Add resource metadata/type persistence and validated create/update/list/filter queries.
- [x] **RES-05** — Implement validated resource upload/replacement with allowed file types, size limits, and protected storage for restricted files.
- [ ] **RES-06** — Implement authorized inline/download responses for resources, lesson attachments, and exam/correction PDFs; direct URLs cannot bypass access.
- [x] **RES-07** — Add resource publication/archive actions and learning-content relationships.

### 3. Integration tasks

- [x] **RES-08** — Connect resource catalog/detail/filter UI and document viewer to authorized live files.
- [x] **RES-09** — Connect management upload/edit/publication controls and existing attachment selectors to persisted resources.

### 4. Testing tasks

- [ ] **RES-10** — Test file type/size validation, replacement behavior, restricted-file authorization, and inline/download responses.
- [ ] **RES-11** — Verify PDF viewing, download fallback, and return-to-origin behavior from lesson, resource, and document exam contexts.

## 7. Video library — VIDEO

Screens: G19–G20, A27–A28. PRD §11.

### 1. Prototype tasks

- [x] **VIDEO-01** — Prototype video listing/filter/search and watch view with unavailable-media feedback.
- [x] **VIDEO-02** — Prototype video management list and create/edit source form.

### 2. Backend implementation tasks

- [x] **VIDEO-03** — Add video metadata/source persistence, lesson relationships, and validated YouTube URL normalization; reject arbitrary iframe HTML.
- [x] **VIDEO-04** — Add authorized video create/update and publish/unpublish/archive/reorder actions.
- [x] **VIDEO-05** — Add visibility-scoped video listing/detail/search/filter queries.

### 3. Integration tasks

- [x] **VIDEO-06** — Bind learner video views and related links to authorized records and safe embeds.
- [x] **VIDEO-07** — Connect video management forms, lifecycle actions, and lesson video selection to real data.

### 4. Testing tasks

- [ ] **VIDEO-08** — Test source validation, unsafe embed rejection, publication filtering, and protected video metadata access.

## 8. Shared questions and grading — QUESTION

Screens: question controls within S04/S07 and A18–A19/A23–A24. PRD §§8–9. Reuse supported question behavior between exercises and interactive exams. Each type task covers only that type; no future plugin engine is required.

### 1. Prototype tasks

- [x] **QUESTION-01** — Prototype single-choice answer and authoring controls with selected/error states.
- [x] **QUESTION-02** — Prototype multiple-selection answer and authoring controls.
- [x] **QUESTION-03** — Prototype true/false answer and authoring controls.
- [x] **QUESTION-04** — Prototype matching answer and authoring controls with a keyboard/touch alternative to dragging.
- [x] **QUESTION-05** — Prototype fill-in-the-blanks answer and accepted-answer authoring controls.
- [x] **QUESTION-06** — Prototype short-answer and accepted-answer authoring controls.
- [x] **QUESTION-07** — Prototype ordering answer and authoring controls with keyboard/touch reordering.
- [x] **QUESTION-08** — Prototype reusable question list with add/edit/remove/reorder interactions using fixtures.

### 2. Backend implementation tasks

- [x] **QUESTION-09** — Add owned question/answer-definition persistence and ordered exercise/exam associations; learner queries never expose correct-answer definitions before evaluation.
- [x] **QUESTION-10** — Implement single-choice definition validation and deterministic grading with explanation output.
- [x] **QUESTION-11** — Implement multiple-selection validation and grading; document the chosen exact-match scoring rule.
- [x] **QUESTION-12** — Implement true/false definition validation and grading.
- [x] **QUESTION-13** — Implement matching definition validation and grading with stable pair identifiers.
- [x] **QUESTION-14** — Implement blanks validation and grading with explicit accepted answers and documented normalization.
- [x] **QUESTION-15** — Implement short-answer validation and grading against explicit accepted answers; document normalization without implying free-form AI grading.
- [x] **QUESTION-16** — Implement ordering validation and grading against the configured sequence.
- [x] **QUESTION-17** — Implement authorized question add/update/remove/reorder actions with type-specific validation and ownership checks.

Each question awards its configured integer points for an exact match and zero otherwise. Multiple selection compares the full set of distinct option IDs in any order. Matching compares a complete one-to-one mapping of left IDs to right IDs. Ordering compares the full sequence of item IDs. The order of display items is authored separately from the answer definition.

Blanks map each `___` marker to its slot in display order and require a configured accepted answer for every slot. Blanks and short answers compare responses with explicit accepted alternatives after collapsing Unicode whitespace, trimming, and applying Unicode case folding. Punctuation and accents remain significant. The comparison applies no Unicode composition normalization or free-form evaluation.

### 3. Integration tasks

- [x] **QUESTION-18** — Bind question management lists to real exercise/exam ownership and ordering actions.
- [x] **QUESTION-19** — Connect single-choice and true/false authoring forms to their validated definitions.
- [x] **QUESTION-20** — Connect multiple-selection authoring to its validated definition.
- [x] **QUESTION-21** — Connect matching authoring to its validated definition.
- [x] **QUESTION-22** — Connect blanks and short-answer authoring to explicit accepted-answer definitions.
- [x] **QUESTION-23** — Connect ordering authoring to its validated definition.
- [x] **QUESTION-24** — Connect the shared learner question renderer and single-choice control to the attempt payload. Check that the response omits answer keys before submission.
- [x] **QUESTION-30** — Connect multiple-selection rendering and answer serialization to the learner renderer from QUESTION-24.
- [x] **QUESTION-31** — Connect true/false rendering and answer serialization to the learner renderer.
- [x] **QUESTION-32** — Connect matching rendering and pair-ID serialization to the learner renderer.
- [x] **QUESTION-33** — Connect fill-in-the-blanks rendering and slot answer serialization to the learner renderer.
- [x] **QUESTION-34** — Connect short-answer rendering and text answer serialization to the learner renderer.
- [x] **QUESTION-35** — Connect ordering rendering and ordered-ID serialization to the learner renderer.

### 4. Testing tasks

- [ ] **QUESTION-25** — Add table-driven validation/grading tests for single choice, multiple selection, and true/false, including malformed answers.
- [ ] **QUESTION-26** — Test matching and ordering grading for missing, repeated, and foreign identifiers.
- [ ] **QUESTION-27** — Test blanks/short-answer normalization, accepted alternatives, and deliberately incorrect answers.
- [ ] **QUESTION-28** — Test question ownership, management authorization, scoped reordering, and pre-submission answer-key secrecy.
- [ ] **QUESTION-29** — Verify matching/ordering and all other answer controls with keyboard, touch, and RTL content.

Saved submitted question and scoring snapshots are owned by the attempt tasks below.

## 9. Exercise attempts and history — EXERCISE

Screens: G11–G12, S04–S06, A16–A17. PRD §8.

### 1. Prototype tasks

- [x] **EXERCISE-01** — Prototype exercise catalog and instructions with prerequisites/access feedback.
- [x] **EXERCISE-02** — Prototype exercise attempt submission using fixture questions and simulated results.
- [x] **EXERCISE-03** — Prototype results/corrections and attempt history with retry navigation.
- [x] **EXERCISE-04** — Prototype exercise management list and create/edit instructions/placement form.

### 2. Backend implementation tasks

- [x] **EXERCISE-05** — Add exercise ownership schema and validated management create/update with learning placement and instructions.
- [x] **EXERCISE-06** — Add exercise publish/unpublish/archive/reorder actions and visibility-scoped catalog/detail queries.
- [x] **EXERCISE-07** — Add authenticated attempt creation with immutable question/scoring snapshots and per-student ownership.
- [x] **EXERCISE-08** — Implement atomic submit/grade/finalize with server-computed totals; duplicate submissions cannot create duplicate outcomes.
- [x] **EXERCISE-09** — Add owned attempt-history/result queries with saved answers, explanations, and a retry creating a separate attempt.

### 3. Integration tasks

- [x] **EXERCISE-10** — Connect exercise management forms and lifecycle controls, linking to question authoring.
- [x] **EXERCISE-11** — Bind learner catalog/instructions to live records and authorized attempt creation.
- [x] **EXERCISE-12** — Connect answer submission, correction review, history, and retry to owned persisted attempts.

### 4. Testing tasks

- [ ] **EXERCISE-13** — Test scoring, forged totals, duplicate submission, cross-student access, retry separation, and historical results after question edits.
- [ ] **EXERCISE-14** — Verify lesson → exercise → submission → corrections → retry end to end.

## 10. Document and interactive exams — EXAM

Screens: G13–G15, S07–S09, A20–A24. PRD §9.

### 1. Prototype tasks

- [x] **EXAM-01** — Prototype exam catalog and document-exam detail with exam/correction file links.
- [x] **EXAM-02** — Prototype interactive-exam instructions and timed/untimed attempt states.
- [x] **EXAM-03** — Prototype exam results/corrections and attempt history.
- [x] **EXAM-04** — Prototype document-exam management list and create/edit form.
- [x] **EXAM-05** — Prototype interactive-exam create/edit form with timing, availability, and question-management entry.

### 2. Backend implementation tasks

- [x] **EXAM-06** — Add exam ownership/type schema, learning placement, availability, and authorized document-exam create/update with protected PDF references.
- [x] **EXAM-07** — Add validated interactive-exam create/update with timing configuration and question ownership.
- [x] **EXAM-08** — Add exam publish/unpublish/archive/reorder actions and availability-aware catalog/detail queries.
- [x] **EXAM-09** — Add owned exam attempt creation with server start/deadline and immutable question/scoring snapshots.
- [x] **EXAM-10** — Implement atomic exam submission/grading with server-enforced deadline and duplicate-finalization protection; document expiration behavior.
- [x] **EXAM-11** — Add owned exam result/history queries returning stored answers, scores, corrections, and completion state.

### 3. Integration tasks

- [x] **EXAM-12** — Connect document-exam management and learner detail to real authorized exam/correction files.
- [x] **EXAM-13** — Connect interactive-exam management to timing/availability actions and shared question authoring.
- [x] **EXAM-14** — Connect instructions, attempt start, timer, answer submission, and server expiration feedback.
- [x] **EXAM-15** — Bind results/corrections/history to the student's persisted attempts.

### 4. Testing tasks

- [ ] **EXAM-16** — Test availability, file access, timed/untimed behavior, deadline boundaries, duplicate submission, and result ownership.
- [ ] **EXAM-17** — Verify document exam/correction viewing and interactive exam → score → history persistence across sessions.

## 11. Topics and global search — SEARCH

Screens: G08–G10, A29–A30. PRD §12.

### 1. Prototype tasks

- [x] **SEARCH-01** — Prototype topic index/detail and topic management list/form using fixture relationships.
- [x] **SEARCH-02** — Prototype global results with type, title, context, destination, and no-match state.

### 2. Backend implementation tasks

- [x] **SEARCH-03** — Add topic persistence, validated management actions, archive behavior, and learning-content associations.
- [x] **SEARCH-04** — Add global/level topic queries returning only visible related content.
- [x] **SEARCH-05** — Implement the paginated global search query with lessons as the first result type. Include title, type, context, canonical destination, and the existing visibility scope.
- [x] **SEARCH-10** — Add authorized exercise results to the shared query from SEARCH-05.
- [x] **SEARCH-11** — Add authorized document and interactive exam results to the shared query.
- [x] **SEARCH-12** — Add authorized resource results to the shared query.
- [x] **SEARCH-13** — Add authorized video results to the shared query.
- [x] **SEARCH-14** — Add visible topic results to the shared query.

### 3. Integration tasks

- [x] **SEARCH-06** — Bind topic management, relationship selection, and learner topic navigation to live records.
- [x] **SEARCH-07** — Connect global search form/results and canonical destination links; preserve the query during pagination.

### 4. Testing tasks

- [ ] **SEARCH-08** — Test all six result types, level context, no matches, pagination, and exclusion of inaccessible or unpublished content bodies.
- [ ] **SEARCH-09** — Test topic association validation and navigation to visible content after publish/archive changes.

## 12. Progress, activity, and dashboard — PROGRESS

Screens: S01, S10–S12, A04. PRD §§6, 13.

### 1. Prototype tasks

- [x] **PROGRESS-01** — Prototype overview and per-level progress with completion, scores, and empty-history fixtures.
- [x] **PROGRESS-02** — Prototype activity/score history and administrator student-detail reading.
- [x] **PROGRESS-03** — Prototype dashboard learning/progress sections with continue-learning, selected levels, and recent scores.

### 2. Backend implementation tasks

- [x] **PROGRESS-04** — Persist lesson views and explicit completion per student; repeating completion does not duplicate completed records.
- [x] **PROGRESS-05** — Expose finalized exercise activity from persisted attempts while keeping the attempt as the source of score and completion.
- [x] **PROGRESS-14** — Expose exam attempt and completion activity from persisted exam attempts while keeping the attempt as the source of score and completion.
- [x] **PROGRESS-06** — Add overall/per-level completion and history queries with documented denominators and zero-content behavior.
- [x] **PROGRESS-07** — Add continue-learning/recent-content queries scoped to current visibility and selected levels.
- [x] **PROGRESS-08** — Add authorized manager queries for a student's progress, activity, and owned attempt review.

### 3. Integration tasks

- [x] **PROGRESS-09** — Connect lesson viewed/completed behavior and progress/history views to persisted activity.
- [x] **PROGRESS-10** — Bind the dashboard's selected levels, continue-learning, progress, recent lessons, and scores to live queries.
- [x] **PROGRESS-11** — Connect administrator student detail to authorized progress/history and attempt review.

### 4. Testing tasks

- [ ] **PROGRESS-12** — Test completion idempotency, empty/multiple-level denominators, activity ownership, and persistence after logout/login.
- [ ] **PROGRESS-13** — Test that archived/restricted content cannot be opened through continue-learning, progress, or administrator links without authorization.

## 13. Prerequisites and basic recommendations — RECOMMEND

Screens: S03, A15; sections within G07/G12/G15/S01. PRD §14.

### 1. Prototype tasks

- [x] **RECOMMEND-01** — Prototype prerequisite guidance and its editor with incomplete/complete preparation states.
- [x] **RECOMMEND-02** — Prototype recommended-next-content sections with fixture reasons and an empty state. Append `?recommendations=empty` to the dashboard, lesson, exercise result, or exam result preview to inspect the empty state.

### 2. Backend implementation tasks

- [x] **RECOMMEND-03** — Persist lesson prerequisite links for lessons/exercises/exams; reject self-links, invalid targets, and cycles.
- [x] **RECOMMEND-04** — Add student-specific prerequisite completion queries; distinguish recommendation from configured access restriction.
- [x] **RECOMMEND-05** — Select accessible next lessons using selected levels, completion, and configured next-lesson relationships. Document the ranking and fallback.
- [x] **RECOMMEND-09** — Add recent activity as a documented tie-breaker for next-lesson ordering.
- [x] **RECOMMEND-10** — Add pending or recommended exercises based on the learner's saved exercise performance.
- [x] **RECOMMEND-11** — Use exam performance to surface accessible review content related to the exam.

Prerequisite links are advisory by default. If the caller supplies an explicit prerequisite access requirement, it is satisfied only when at least one lesson is configured and every linked lesson is currently visible and explicitly completed by the student. Hidden prerequisites are omitted from guidance. This result covers the prerequisite requirement alone. Content access-rule persistence and enforcement belong to ACCESS.

Next lessons come from the student's published selected levels and exclude completed lessons and the source lesson. Eligible configured links from the source rank first, followed by other eligible lessons. With no explicit source, the most recent view or completion in the selected levels supplies the source, with the progress ID breaking timestamp ties. Within each tier, most recently viewed lessons come first, then curriculum position and lesson ID. With no eligible source, the catalogue supplies all candidates. An empty level selection or candidate set returns no recommendations.

Exercise suggestions require visible exercises with questions in selected levels. The latest scored submission is selected by submission time and attempt ID, using saved awarded points divided by positive possible points. Scores below 70% produce retry suggestions ordered by lowest percentage. Exercises with no scored submission follow as pending, with unit ID and curriculum order breaking ties. Scores of 70% or higher omit the exercise.

Exam review uses the requested owned, currently accessible, submitted interactive attempt. For a saved score below 70%, visible configured prerequisite lessons rank first, followed by visible lessons from the exam's unit, using curriculum order within each tier. The exam defines the scope, including for students with no selected levels. An unscored or expired attempt, a score of at least 70%, or an empty set of review lessons returns no suggestions.

### 3. Integration tasks

- [x] **RECOMMEND-06** — Connect prerequisite editing and learner preparation guidance to live relationships/progress.
- [x] **RECOMMEND-07** — Bind dashboard/lesson/result next-learning sections to the shared recommendation query.

### 4. Testing tasks

- [ ] **RECOMMEND-08** — Test cycle rejection, completion guidance, deterministic ranking, performance inputs, and visibility/access filtering.

## 14. Student–teacher messaging — MESSAGE

Screens: S13–S15, A31–A32; entry from G07. PRD §15.

### 1. Prototype tasks

- [x] **MESSAGE-01** — Prototype student inbox and teacher conversation using fixture messages.
- [x] **MESSAGE-02** — Prototype new-message/Ask Teacher composition with visible source-content context.
- [x] **MESSAGE-03** — Prototype teacher conversation list/filter and reply view.

### 2. Backend implementation tasks

- [x] **MESSAGE-04** — Add conversation/message persistence and participant policies; students cannot address or read other students' conversations.
- [x] **MESSAGE-05** — Implement validated student conversation creation and follow-ups with authorized content context attached from the source.
- [x] **MESSAGE-06** — Implement authorized teacher conversation queries, filtering, and reply actions.
- [x] **MESSAGE-07** — Add student conversation/reply queries and recent-teacher-reply dashboard data.

### 3. Integration tasks

- [x] **MESSAGE-08** — Connect Ask Teacher entry and new-message submission to saved conversations with source context.
- [x] **MESSAGE-13** — Bind the student inbox to owned conversations and their latest replies.
- [x] **MESSAGE-14** — Bind the conversation detail and follow-up form to the owned thread.
- [x] **MESSAGE-09** — Connect teacher list/filter/reply actions and valid source-content links.
- [x] **MESSAGE-10** — Bind recent teacher replies on the dashboard to authorized conversations.

### 4. Testing tasks

- [ ] **MESSAGE-11** — Test participant isolation, rejected student-to-student messages, context tampering, restricted Ask Teacher access, and teacher reply ownership.
- [ ] **MESSAGE-12** — Verify lesson → question → teacher reply → student conversation end to end.

## 15. Points, streaks, and achievements — REWARD

Screens: S16–S18, A33–A37 excluding optional daily rewards/leaderboards. PRD §16.

### 1. Prototype tasks

- [x] **REWARD-01** — Prototype rewards overview/achievement detail and dashboard reward summary with fixtures.
- [x] **REWARD-02** — Prototype points-rule management list/form for earning and spending rules.
- [x] **REWARD-03** — Prototype achievement management and streak settings forms.
- [x] **REWARD-04** — Prototype points unlock confirmation/outcome including insufficient-balance feedback.

### 2. Backend implementation tasks

- [ ] **REWARD-05** — Add immutable point-ledger persistence and balance queries with source-event deduplication.
- [x] **REWARD-06** — Add validated management of earning rules for lesson completion, exercise results, exam completion, and streak milestones.
- [x] **REWARD-07** — Award configured points from finalized learning events exactly once; replays and retries follow explicit rule eligibility.
- [x] **REWARD-08** — Add learning-day/streak calculation and configurable streak rules using an explicit timezone/day-boundary policy.
- [x] **REWARD-09** — Add achievement definitions, criteria management, and unique student achievement awards from qualifying events.
- [ ] **REWARD-10** — Add configured spending costs and atomic point debit/unlock records; concurrent requests cannot overspend or charge twice for one unlock.

### 3. Integration tasks

- [x] **REWARD-11** — Connect points-rule management and streak settings forms to persisted configuration.
- [x] **REWARD-12** — Connect achievement management and student reward/detail views to live awards and criteria.
- [x] **REWARD-13** — Bind dashboard points/streak/achievement summaries to real balances and awards.
- [ ] **REWARD-14** — Connect unlock confirmation to atomic spending and the resulting access entitlement.

### 4. Testing tasks

- [ ] **REWARD-15** — Test award replay, retry eligibility, disabled rules, achievement uniqueness, and timezone/day-boundary streak cases.
- [ ] **REWARD-16** — Test insufficient balance, concurrent spending, duplicate unlock requests, and owner isolation.

## 16. Access rules and premium classification — ACCESS

Screens: G23, S18, premium requirement portion of S20, A38–A39. PRD §§17, 19. Commercial fulfillment remains decision-gated below.

### 1. Prototype tasks

- [x] **ACCESS-01** — Prototype contextual access requirements for guest, level, prerequisite, points, and premium restrictions.
- [x] **ACCESS-02** — Prototype access-rule listing/editor with clear free/restricted classification and feature targets such as Ask Teacher.

### 2. Backend implementation tasks

- [x] **ACCESS-03** — Add access-rule/entitlement persistence and a policy contract for public, registered, level, prerequisite, points, and premium access.
- [x] **ACCESS-04** — Implement consistent guest/student/manager evaluation with explicit rule-combination semantics; deny access when required configuration is missing.
- [x] **ACCESS-05** — Implement authorized rule create/update and target validation for supported content and Ask Teacher.
- [x] **ACCESS-06** — Enforce access policies on level and lesson reads, including direct links and ancestor visibility.
- [x] **ACCESS-14** — Enforce access policies on resource reads and direct protected file delivery.
- [x] **ACCESS-15** — Enforce access policies on video listings and direct watch requests.
- [x] **ACCESS-07** — Enforce exercise policy and attempt ownership at start, submission, and result review.
- [x] **ACCESS-16** — Enforce exam policy and attempt ownership at start, submission, and result review.
- [x] **ACCESS-17** — Enforce configured Ask Teacher access on the message creation action.
- [x] **ACCESS-08** — Connect persisted points-unlock entitlements and premium entitlement lookups to policy decisions; no payment grants are invented.

### 3. Integration tasks

- [x] **ACCESS-09** — Connect management access-rule editing from the central list and individual content editors.
- [x] **ACCESS-10** — Bind content labels, access-requirement feedback, and sign-in/level/prerequisite recovery links to real policy outcomes.
- [x] **ACCESS-11** — Connect points-restricted destinations to the unlock flow and re-evaluate access after a successful unlock.

### 4. Testing tasks

- [ ] **ACCESS-12** — Add a policy matrix covering roles, publication, rule combinations, missing configuration, and granted/missing entitlements.
- [ ] **ACCESS-13** — Test direct route/file/submission access and search/recommendation leakage for unauthorized users.

## 17. Platform settings and administration entry — SETTINGS

Screens: A01, A43. PRD §§19, 22–23.

### 1. Prototype tasks

- [x] **SETTINGS-01** — Prototype administration home with included management destinations and platform settings form.

### 2. Backend implementation tasks

- [x] **SETTINGS-02** — Add validated storage/read behavior for supported platform settings and enabled interface languages; accept only explicitly supported keys.
- [x] **SETTINGS-03** — Add authorized settings update actions and configuration fallback behavior.

### 3. Integration tasks

- [x] **SETTINGS-04** — Connect settings form/read behavior and administration entry to real authorized module routes.

### 4. Testing tasks

- [ ] **SETTINGS-05** — Test settings authorization, invalid/unknown key rejection, language fallback, and persistence.

## 18. Platform analytics — ANALYTICS

Screens: A44–A49; student drill-down A04. PRD §21. Included reports use actual recorded events; undefined commercial metrics remain unavailable rather than fabricated.

### 1. Prototype tasks

- [x] **ANALYTICS-01** — Prototype analytics overview plus acquisition/engagement report views with clearly labeled fixture metrics.
- [x] **ANALYTICS-02** — Prototype learning/communication reports and business-report unavailable state.

### 2. Backend implementation tasks

- [x] **ANALYTICS-03** — Document visitor/active-user/event definitions and add minimal deduplicated acquisition/activity recording consistent with those definitions.
- [x] **ANALYTICS-04** — Add acquisition aggregates for visitors, registrations, and active students over an explicit reporting period.
- [x] **ANALYTICS-05** — Record authorized resource opens and expose a period-scoped usage count.
- [x] **ANALYTICS-14** — Record video usage using an explicit playback signal and expose a period-scoped count.
- [x] **ANALYTICS-15** — Aggregate lesson opens and completions from existing activity.
- [x] **ANALYTICS-16** — Aggregate exercise starts, completions, and repeat attempts from attempt records.
- [x] **ANALYTICS-17** — Aggregate exam attempts and completions from exam activity.
- [x] **ANALYTICS-18** — Aggregate learning streak activity using the REWARD day-boundary rules.
- [x] **ANALYTICS-06** — Add learning aggregates for completion rates, scores, progress, and question-level difficulty with documented denominators.
- [x] **ANALYTICS-07** — Add communication aggregates for Ask Teacher requests, messages, and teacher response activity.
- [x] **ANALYTICS-08** — Add authorized report/overview queries with shared reporting-period validation and explicit unavailable business metrics.

### 3. Integration tasks

- [x] **ANALYTICS-09** — Bind overview/acquisition/engagement views to live aggregates and reporting-period controls.
- [x] **ANALYTICS-10** — Bind learning/communication reports and authorized student/conversation drill-down links.
- [x] **ANALYTICS-11** — Connect business-report availability to actual configured event sources; display missing-source explanations instead of zero conversions.

### 4. Testing tasks

- [ ] **ANALYTICS-12** — Test aggregate denominators, empty periods, event deduplication, period boundaries, and active-student definitions.
- [ ] **ANALYTICS-13** — Test report authorization, valid drill-down access, and unavailable business metrics when commercial sources are absent.

## 19. Release verification — RELEASE

PRD §§20, 22, 24. These tasks verify completed integrations; they do not add new features.

### 1. Prototype tasks

- [x] **RELEASE-01** — Audit included prototype navigation against inventory IDs; record missing destinations or states without implementing them in this task.

The earlier screen catalogue review covered 89 inventory IDs. The modules below cover the remaining S19–S21 and A40–A42 screens. The browser checks for the original catalogue are recorded in the [visual prototype review](VISUAL_PROTOTYPE.md).

The following prototype tasks have partial coverage and remain open.

- **RES-03** still needs file error feedback.
- Matching and ordering use native selects for learners and text mapping in the shared editor.

The broader keyboard, text zoom, reduced motion, and multilingual checks remain open under **BASE-13** and **RELEASE-08**.

### 2. Backend implementation tasks

- [x] **RELEASE-02** — Add a local/testing-only demonstration seed for one coherent 2nd Baccalaureate learning journey using implemented factories; do not create production credentials.

### 3. Integration tasks

- [x] **RELEASE-03** — Check integrated navigation for remaining fixture bindings and dead links; create individually scoped follow-up tasks for defects rather than fixing unrelated modules in this task.
- [x] **RELEASE-04** — Connect the dashboard's exam/resource shortcuts to authorized selected-level queries; other dashboard sections are integrated by their owning feature tasks.

#### Integrated navigation audit follow-ups

Reviewed the live shared shell, destination mappings, and public, student, and admin page links for fixture URLs, dead ends, and placeholder states. Browser checks exercised the student dashboard shortcuts at desktop and mobile widths.

- [ ] **RELEASE-NAV-01** — A01. The “Create a lesson” action opens the lessons list. Route it to category selection for lesson creation and verify the empty-curriculum path.
- [ ] **RELEASE-NAV-02** — A15. The Topics fieldset displays “Unavailable” even when topics can be associated through A30. Connect the lesson-side fieldset to existing topic associations with curriculum authorization.
- [ ] **RELEASE-NAV-03** — A01. The Active students and Average score tiles always display “Unavailable.” Connect them to the existing authorized analytics with an explicit reporting period and empty state.
- [ ] **RELEASE-NAV-04** — S10/S11. The Learning streak tile displays “Unavailable” even when the student dashboard shows a streak. Use the existing streak query on the progress overview.

### 4. Testing tasks

- [x] **RELEASE-05** — Verify guest browse → registration → level selection → lesson → exercise → correction → persisted progress with an automated journey where supported.
- [x] **RELEASE-06** — Verify manager authoring/publication → student access → document/interactive exam → results/history with representative permissions.
- [x] **RELEASE-07** — Verify lesson Ask Teacher → teacher reply and earned points → unlock → restricted content with no duplicate charges.
- [x] **RELEASE-08** — Audit included public/student/admin flows in Arabic, French, and English at mobile/desktop widths with keyboard, zoom, and reduced motion; record defects individually.
- [x] **RELEASE-09** — Run the full relevant automated suite and production asset build; report results and unresolved defects against PRD acceptance criteria.
- [ ] **RELEASE-10** — Verify the configured paid offer through entitlement activation and access to its protected content after PAID integration.
- [ ] **RELEASE-11** — Check navigation to S19–S21 and A40–A42 for the features enabled in the release. Record missing destinations or permission mismatches by screen ID.

See the [release verification report](RELEASE_VERIFICATION.md) for test results,
individual defects, and the prerequisites still pending for RELEASE-10 and RELEASE-11.

## 20. Paid content and paid lessons — PAID

Screens S20 and the paid offer portion of A40. PRD §§17, 19, 21. ACCESS owns the entitlement check. SCOPE-01 defines how real paid access is granted.

### 1. Prototype tasks

- [ ] **PAID-01** — Prototype the S20 offer view with fixture terms and pending, granted, and unavailable access states.
- [ ] **PAID-02** — Prototype the A40 paid offer editor with a linked content selector and validation feedback.

### 2. Backend implementation tasks

- [ ] **PAID-03** — Store offer title, price, currency, terms, publication state, and association to eligible content. Validate editor permissions and inputs.
- [ ] **PAID-04** — Add an eligible-offer query for a restricted target that excludes unpublished and unrelated offers.
- [ ] **PAID-05** — **Decision needed · SCOPE-01.** Implement the authorized fulfillment action that creates the student's ACCESS entitlement from a verified source. Replays reuse the grant.
- [ ] **PAID-06** — **Decision needed · SCOPE-01.** Apply the agreed expiration or reversal behavior to a previously granted entitlement.
- [ ] **PAID-07** — **Decision needed · SCOPE-03.** Record one paid conversion per verified fulfillment event for A49.

### 3. Integration tasks

- [ ] **PAID-08** — Connect the A40 paid offer editor to persisted offers and content associations.
- [ ] **PAID-09** — Connect G23 to the applicable S20 offer and render its real terms and current access state.
- [ ] **PAID-10** — **Decision needed · SCOPE-01.** Connect the agreed purchase or fulfillment entry point to S20 and route a student with confirmed access back to the protected content.
- [ ] **PAID-11** — **Decision needed · SCOPE-03.** Connect the paid conversion count to the A49 reporting period.

### 4. Testing tasks

- [ ] **PAID-12** — Test offer validation and selection for unpublished, unrelated, and visible offers.
- [ ] **PAID-13** — Test verified grant, duplicate fulfillment, expiration or reversal, and access isolation across accounts.
- [ ] **PAID-14** — Test that a repeated fulfillment event counts as one conversion in the correct reporting period.

## 21. Teacher-service promotion and leads — SERVICE

Screens G01 and the teacher-service portion of A40, with results in A49. PRD §§2, 17, 21. Confirm the placement and lead action under SCOPE-02.

### 1. Prototype tasks

- [ ] **SERVICE-01** — Prototype a teacher-service promotion with fixture copy and a sample action within an existing discovery screen.
- [ ] **SERVICE-02** — Prototype the A40 service editor with preview and publish states.

### 2. Backend implementation tasks

- [ ] **SERVICE-03** — Store service offer details and publication state. Permit authorized managers to create and update offers.
- [ ] **SERVICE-04** — **Decision needed · SCOPE-02.** Implement the agreed lead action and its validation for an active offer.
- [ ] **SERVICE-05** — **Decision needed · SCOPE-03.** Record the qualifying lead event and expose its deduplicated count by reporting period.

### 3. Integration tasks

- [ ] **SERVICE-06** — Connect the A40 service editor to real create, update, and publication actions.
- [ ] **SERVICE-07** — **Decision needed · SCOPE-02.** Render the published promotion in the agreed placement and connect its lead action and outcome feedback.
- [ ] **SERVICE-08** — **Decision needed · SCOPE-03.** Bind the lead count to A49.

### 4. Testing tasks

- [ ] **SERVICE-09** — Test manager authorization, publication visibility, and rejection of lead actions for inactive offers.
- [ ] **SERVICE-10** — Test the agreed lead action and its deduplication and reporting behavior.

## 22. Subscriptions — SUB

Conditional. Screens S21 and A41, with results in A49. PRD §§17, 21. SCOPE-04 defines the lifecycle and how it affects ACCESS.

### 1. Prototype tasks

- [ ] **SUB-01** — Prototype S21 with fixture terms and active, inactive, and unavailable access states.
- [ ] **SUB-02** — Prototype A41 with plan terms and eligible content selection.

### 2. Backend implementation tasks

- [ ] **SUB-03** — **Decision needed · SCOPE-04.** Persist plan terms, enabled state, and eligible content. Validate manager writes.
- [ ] **SUB-04** — **Decision needed · SCOPE-04.** Map verified subscription state to an ACCESS entitlement and handle repeated lifecycle events safely.
- [ ] **SUB-05** — **Decision needed · SCOPE-03 and SCOPE-04.** Record the agreed subscription conversion event once per qualifying activation.

### 3. Integration tasks

- [ ] **SUB-06** — Connect A41 to saved plans and eligible content associations.
- [ ] **SUB-07** — Connect S21 to eligible plans, current status, and the agreed subscription entry point.
- [ ] **SUB-08** — Connect subscription conversion reporting to A49 when event recording is active.

### 4. Testing tasks

- [ ] **SUB-09** — Test disabled plans, invalid content associations, and access transitions from verified lifecycle events.
- [ ] **SUB-10** — Test repeat lifecycle delivery and conversion deduplication.

## 23. Advertising and promotional rewards — ADS

Conditional. Screen A42, point rule links in A34, configured learner placements, and results in A49. PRD §§16–17, 21.

### 1. Prototype tasks

- [ ] **ADS-01** — Prototype A42 with fixture placement settings, enabled state, and an optional points rule selector.
- [ ] **ADS-02** — Prototype one learner placement with clear promotion labeling and optional reward outcome feedback.

### 2. Backend implementation tasks

- [ ] **ADS-03** — **Decision needed · SCOPE-05.** Persist and validate the supported placement settings and manager authorization.
- [ ] **ADS-04** — **Decision needed · SCOPE-05.** Add a placement eligibility query for the agreed audience and destination.
- [ ] **ADS-05** — **Decision needed · SCOPE-03 and SCOPE-05.** Record verified promotion engagement with a stable event identifier for reporting.
- [ ] **ADS-06** — **Decision needed · SCOPE-05.** Pass a verified qualifying action to the existing points ledger with replay protection.

### 3. Integration tasks

- [ ] **ADS-07** — Connect A42 to saved settings and the supported points rule association.
- [ ] **ADS-08** — Render an enabled promotion in the configured learner placement and show the confirmed reward outcome when applicable.
- [ ] **ADS-09** — Bind the advertising engagement count to A49 when a verified event source is configured.

### 4. Testing tasks

- [ ] **ADS-10** — Test disabled placement, audience eligibility, and manager authorization.
- [ ] **ADS-11** — Test rejection of forged actions and verify repeated events cannot duplicate credit or report counts.

## 24. Daily rewards — DAILY

Conditional. Daily reward action in S16 and settings in A37. PRD §16.

### 1. Prototype tasks

- [ ] **DAILY-01** — Prototype the S16 claim control with eligible, already claimed, and unavailable fixtures.
- [ ] **DAILY-02** — Prototype the A37 daily reward settings section.

### 2. Backend implementation tasks

- [ ] **DAILY-03** — **Decision needed · SCOPE-06.** Persist the validated reward amount and enabled state under management authorization.
- [ ] **DAILY-04** — **Decision needed · SCOPE-06.** Implement one atomic claim per eligible student and reward day using the REWARD ledger.

### 3. Integration tasks

- [ ] **DAILY-05** — Bind daily reward settings to A37 and show the claim control in S16 when enabled.
- [ ] **DAILY-06** — Connect claim submission and refresh the resulting eligibility and point balance.

### 4. Testing tasks

- [ ] **DAILY-07** — Test the configured day boundary, disabled setting, and duplicate or concurrent claims.

## 25. Leaderboards — RANK

Conditional. Screens S19 and the leaderboard settings in A37. PRD §16.

### 1. Prototype tasks

- [ ] **RANK-01** — Prototype S19 standings with fixture ties, the student's position, and an empty state.
- [ ] **RANK-02** — Prototype the A37 leaderboard settings section.

### 2. Backend implementation tasks

- [ ] **RANK-03** — **Decision needed · SCOPE-07.** Persist enabled leaderboard settings and validate the supported audience and period.
- [ ] **RANK-04** — **Decision needed · SCOPE-07.** Query eligible standings and the viewer's position with deterministic ties and only the agreed public identity fields.

### 3. Integration tasks

- [ ] **RANK-05** — Connect leaderboard settings in A37 to saved configuration.
- [ ] **RANK-06** — Bind S19 to standings and expose its navigation entry to eligible users when enabled.

### 4. Testing tasks

- [ ] **RANK-07** — Test disabled access, audience boundaries, ties, and the absence of private account details in standings.

## Product decisions

Record each decision before starting the dependent live tasks. Split any agreed provider-specific work into separate tasks once the integration contract is known.

- [ ] **SCOPE-01** — Define paid-content/paid-lesson fulfillment: entitlement source, purchase process, provider if any, access duration, failure/reversal behavior, and administrative responsibility. Output: agreed scope for S20/A40 and an implementable entitlement-grant contract.
- [ ] **SCOPE-02** — Define teacher-service promotion and lead capture: published offer information, student action, destination, and measurable lead event. Output: agreed scope for A40 and business conversion events.
- [ ] **SCOPE-03** — Define acquisition/business measurement requirements, including consent/retention decisions where applicable and what constitutes paid conversion or service lead. Output: approved event definitions and bounded follow-up tasks for A45/A49.

- [ ] **SCOPE-04** — Decide whether subscriptions are enabled. If enabled, specify the entitlement source, plan periods, lifecycle transitions, and access changes.
- [ ] **SCOPE-05** — Define the supported promotion placement, source, qualifying action, and evidence required to award points or count engagement.
- [ ] **SCOPE-06** — Confirm daily reward eligibility, day boundary, and allowed reward amounts.
- [ ] **SCOPE-07** — Confirm leaderboard metric, reporting period, eligible audience, tie handling, and displayed student identity.

## Release completion

Use PRD §24 and the selected release scope to verify each required flow through its real entry point, with persisted data, correct permissions, and user feedback. Record the state of each conditional module and each unresolved decision in the release report. Add new task IDs for future question formats or video providers when their requirements are defined.
