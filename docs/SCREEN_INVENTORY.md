# Screen Inventory

Source: [docs/PRD.md](docs/PRD.md). This inventory describes functional destinations and their hierarchy only. It contains no screen designs, routes, or implementation instructions.

## Reading the inventory

- Groups follow **user role → module → parent/child screen**. IDs identify screens; the parent column defines the canonical hierarchy. Cross-links may provide additional entry points.
- Guest and student browsing screens are shared, subject to configured access rules. Students inherit the shared screens below; they are not duplicated.
- A screen is a logical destination or task view, not necessarily a separate page. Create/edit variants share an entry but explicitly name both modes. Document viewing, access prompts, and result review can reuse their listed screen in multiple contexts.
- **Conditional** means the PRD explicitly makes the capability configurable or optional. It does not authorize building it ahead of a requested task.
- Teacher and administrator form one management role group in the PRD. Actual access depends on authorization; this inventory does not invent a separate permission model.
- Parent `—` marks an entry point. All screens support the configured languages and applicable desktop/mobile behavior; these are not separate screens.

## 1. Guest and shared browsing

### 1.1 Discovery and learning hierarchy

PRD §§3–5, 7, 12, 18, 20.

| ID | Screen name | Purpose | Parent screen | Main actions |
| --- | --- | --- | --- | --- |
| G01 | Home | Introduce the platform and available learning paths and public material. | — | Browse paths; discover content; search; register; log in. |
| G02 | Learning Paths / Levels | Browse available levels and learning goals. | G01 | Browse paths; open a level; select a path when signed in. |
| G03 | Level / Learning Path Detail | Explain a level and expose its learning content. | G02 | Open units, exercises, exams, resources, videos, or topic index; select level when signed in. |
| G04 | Units | List units within the selected level. | G03 | Browse units; open a unit. |
| G05 | Unit Detail | Present the unit's learning categories. | G04 | Open a category. |
| G06 | Category / Lessons | List lessons in Vocabulary, Functions, Reading, Grammar, Writing, or another configured category. | G05 | Browse lessons; open a lesson. |
| G07 | Lesson Detail / Study | Present text, images, video, attachments, prerequisites, and related learning content. | G06 | Study; watch; open documents; open related exercises/exams; open prerequisites or next lessons; mark completion where enabled; Ask Teacher when authorized. |
| G08 | Global Search / Results | Find lessons, exercises, exams, videos, resources, and topics with type and context. | G01 | Enter or change query; open a result. |
| G09 | Topic Index | Browse learning concepts globally or within a level. | G01 | Browse topics; open a topic. |
| G10 | Topic Detail / Related Content | Collect learning material connected to a topic. | G09 | Open related lessons, exercises, exams, resources, or videos. |

G04 and G09 also have direct student-navigation entry points; G09 can be scoped from G03. G08 results link to the canonical content screens without changing their hierarchy.

### 1.2 Practice and exam discovery

PRD §§4–5, 8–9, 17–18. Public visibility follows configured access rules; personalized attempts and history require authentication.

| ID | Screen name | Purpose | Parent screen | Main actions |
| --- | --- | --- | --- | --- |
| G11 | Exercises | Browse available exercises globally or within a level. | G01 | Browse exercises; open instructions/detail. |
| G12 | Exercise Detail / Instructions | Explain an exercise and its access conditions before an attempt. | G11 | Read instructions; open prerequisites; start an authorized attempt; sign in or satisfy access requirements. |
| G13 | Exams | Browse document-based and interactive exams globally or within a level. | G01 | Browse exams; open exam detail. |
| G14 | Document Exam Detail | Present an exam PDF, correction PDF, and related resources. | G13 | Open exam or correction document; open related resources. |
| G15 | Interactive Exam Detail / Instructions | Explain exam instructions, timing, availability, and access conditions. | G13 | Read instructions; open prerequisites; start an authorized exam; satisfy access requirements. |

G11 and G13 also open from G03 and student navigation. G12, G14, and G15 can be reached from lessons, search, and topics.

### 1.3 Resources and videos

PRD §§7, 9–12.

| ID | Screen name | Purpose | Parent screen | Main actions |
| --- | --- | --- | --- | --- |
| G16 | Resource Library | Browse PDFs, books, worksheets, study guides, and other supported files. | G01 | Search; filter; open a resource. |
| G17 | Resource Detail | Explain a resource and its learning context. | G16 | Open or download the file; open related learning content. |
| G18 | Document Viewer | Read an eligible PDF or supported document, including lesson attachments and exam/correction PDFs. | G17 | Read; download where permitted; return to the source content. |
| G19 | Video Library | Browse educational videos globally or within a level. | G01 | Search; filter; open a video. |
| G20 | Video Detail / Watch | Play a configured video and expose its related learning material. | G19 | Watch; open related lessons, topics, or resources. |

G16 and G19 also open from G03 and student navigation. G18 is shared by G07 and G14 and returns to whichever source opened it.

### 1.4 Authentication and access

PRD §§17–18, 20.

| ID | Screen name | Purpose | Parent screen | Main actions |
| --- | --- | --- | --- | --- |
| G21 | Register | Create a student account for personalized features. | G01 | Enter registration details; submit; open login. |
| G22 | Login | Authenticate an existing student, teacher, or administrator. | G01 | Enter credentials; sign in; open registration when applicable; continue to the permitted destination. |
| G23 | Content Access Requirements | Explain why requested content or a feature is restricted. | G01 | Sign in/register; open required level or prerequisite; open points unlock or paid access when configured; return to content. |

G23 is contextual to the requested destination. Authentication links may originate anywhere authentication is required.

## 2. Student

Students use the shared browsing screens with personalized completion, recommendations, and access behavior. Exercise/exam submissions, history, messages, rewards, and profile management are authenticated tasks.

### 2.1 Dashboard and learning selection

PRD §§5–7, 14, 18, 20.

| ID | Screen name | Purpose | Parent screen | Main actions |
| --- | --- | --- | --- | --- |
| S01 | Student Dashboard | Summarize selected paths, progress, recent/recommended learning, scores, streaks, points, and teacher replies. | — | Continue learning; open shared content; open progress, rewards, messages, or profile; change selected levels. |
| S02 | Learning Levels / Goals Selection | Select one or more levels and learning preferences. | S01 | Browse available paths; select or change levels/goals; save; continue to dashboard. |
| S03 | Prerequisites / Recommended Preparation | Explain recommended preparation for a lesson, exercise, or exam. | G07 | Open prerequisite lessons; return to the originating content; continue when access rules permit. |

S02 is also reachable from profile/settings and path selection. S03 is reused from G12 and G15; it may be a section of the source screen. Recommended next learning opens existing content screens from S01, G07, or results; it does not require a separate recommendation screen.

### 2.2 Exercises

PRD §§8, 13, 20.

| ID | Screen name | Purpose | Parent screen | Main actions |
| --- | --- | --- | --- | --- |
| S04 | Exercise Attempt | Answer an exercise using its configured question types. | G12 | Answer questions; submit for evaluation. |
| S05 | Exercise Results / Corrections | Show score, completion, submitted answers, corrections, and explanations. | S04 | Review answers; retry where permitted; continue to related or next content. |
| S06 | Exercise Attempt History | Review a student's previous attempts and scores for an exercise. | G12 | Browse attempts; open a previous attempt's results in S05. |

Multiple choice, multiple selection, true/false, matching, blanks, short answer, and ordering are variants within S04, not separate screens. Automatic evaluation is a process/state, not another destination.

### 2.3 Interactive exams

PRD §§9, 13, 20.

| ID | Screen name | Purpose | Parent screen | Main actions |
| --- | --- | --- | --- | --- |
| S07 | Interactive Exam Attempt | Complete a timed or untimed exam using its configured questions. | G15 | Read questions; answer; submit. |
| S08 | Exam Results / Corrections | Show score, completion, submitted answers, corrections, and explanations where supported. | S07 | Review results; open related learning; return to exam history. |
| S09 | Exam Attempt History | Review previous attempts and scores for an exam. | G15 | Browse attempts; open a previous attempt's results in S08. |

Document exams and corrections reuse G14 and G18. Timed and untimed exams are variants of S07.

### 2.4 Progress and learning history

PRD §§6, 13–14, 16.

| ID | Screen name | Purpose | Parent screen | Main actions |
| --- | --- | --- | --- | --- |
| S10 | Progress Overview | Show overall completion, scores, learning history, and streaks. | S01 | Open level progress; inspect activity; open exercise/exam history; continue learning. |
| S11 | Level Progress | Show progress and scores for one selected level. | S10 | Review unit/lesson completion; open learning content; inspect related attempts. |
| S12 | Learning Activity / Score History | Review viewed/completed lessons, exercise/exam activity, scores, and resource use where tracked. | S10 | Browse history; open source content; open S05/S08 for recorded attempts. |

### 2.5 Messages / Ask Teacher

PRD §§7, 15, 20.

| ID | Screen name | Purpose | Parent screen | Main actions |
| --- | --- | --- | --- | --- |
| S13 | Messages | List the student's teacher conversations and replies. | S01 | Open a conversation; start a teacher message. |
| S14 | Ask Teacher / New Message | Compose a message, automatically attaching lesson/content context when opened from learning material. | S13 | Write and send a question; review attached context; cancel. |
| S15 | Teacher Conversation | Read the student's messages and teacher replies with any linked learning context. | S13 | Read replies; send a follow-up; open linked content. |

S14 also opens directly from G07 or other eligible learning content. There is no student-to-student messaging.

### 2.6 Points, rewards, and access

PRD §§16–17. Conditional capabilities appear only when enabled and eligible.

| ID | Screen name | Purpose | Parent screen | Main actions |
| --- | --- | --- | --- | --- |
| S16 | Rewards / Achievements | Show points, streaks, achievements, and configured rewards or unlock opportunities. | S01 | Inspect achievements/rewards; open an eligible unlock; claim a daily reward if enabled. |
| S17 | Reward / Achievement Detail | Explain a configured achievement or reward and its earning/unlock requirements. | S16 | Review criteria and progress; open qualifying learning activity; claim when applicable. |
| S18 | Points Unlock / Outcome | Explain an eligible points cost and confirm the resulting access. | S16 | Review cost and balance; confirm spending; open unlocked content; return if ineligible. |
| S19 | Leaderboard — conditional | Show rankings where the administrator enables leaderboards. | S16 | View standings and the student's position. |
| S20 | Paid Content / Paid Lesson Access — conditional on a paid offering | Explain the configured offer and how eligible users obtain access. | G23 | Review price and terms; follow the configured purchase/access process; open content after access is granted. |
| S21 | Subscription Access — conditional | Explain subscription access where subscriptions are enabled. | S20 | Review configured subscription terms; follow the configured access process; inspect current access status. |

S18 also opens from G23 or restricted content. S20–S21 represent the PRD's access outcomes, not a defined payment implementation: checkout, payment provider, billing history, renewal, refund, and cancellation flows are not specified by the PRD and require separate requirements before detailed inventory expansion.

### 2.7 Profile and settings

PRD §§5, 18, 22.

| ID | Screen name | Purpose | Parent screen | Main actions |
| --- | --- | --- | --- | --- |
| S22 | Profile | View and manage student profile information. | S01 | Edit and save profile; open settings or learning selection. |
| S23 | Settings / Learning Preferences | Manage language, learning preferences, and selected levels. | S22 | Change language/preferences; open S02; save; sign out. |

## 3. Teacher / Administrator

PRD §§3, 19–23. The hierarchy is **administration entry → module list/settings → record editor/detail → nested content**, with shared editors for relationships and access rules. Create/edit modes apply only to the named entity; publishing, unpublishing, archiving, and reordering apply where relevant to learning content.

### 3.1 Administration entry and people

| ID | Screen name | Purpose | Parent screen | Main actions |
| --- | --- | --- | --- | --- |
| A01 | Administration Home | Provide entry to authorized management modules and analytics. | — | Open a management module; open analytics or student messages. |
| A02 | Users | Find and manage platform accounts. | A01 | Browse/search users; open a user; create a user. |
| A03 | Create User / Edit User | Maintain user information and assigned roles. | A02 | Enter/update account details; assign authorized roles; save. |
| A04 | Student Detail / Learning Activity | Inspect a student's profile, learning progress, scores, activity, and communication context. | A02 | Review history and learning difficulties; inspect attempt results; open a student conversation. |
| A05 | Roles | Manage platform roles. | A01 | Browse roles; create or edit a role. |
| A06 | Create Role / Edit Role | Maintain role definitions and associated access configuration supported by the application. | A05 | Define/update role; save. |

### 3.2 Learning structure

| ID | Screen name | Purpose | Parent screen | Main actions |
| --- | --- | --- | --- | --- |
| A07 | Levels / Learning Paths Management | Organize school levels and general learning paths. | A01 | Create; edit; publish/unpublish; archive; reorder paths. |
| A08 | Create Level / Edit Level | Maintain a level's details and learning structure. | A07 | Save details; open its units; configure publication and access. |
| A09 | Units Management | Organize units within a level. | A08 | Create; edit; publish/unpublish; archive; reorder units. |
| A10 | Create Unit / Edit Unit | Maintain a unit and its level relationship. | A09 | Save details; assign level; open categories; configure publication. |
| A11 | Categories Management | Organize learning categories and their unit relationships. | A10 | Create; edit; archive; reorder categories. |
| A12 | Create Category / Edit Category | Maintain a learning category and its placement. | A11 | Save details; assign unit; open lessons. |

A09 and A11 may also be reached directly from administration navigation while retaining their level/unit context.

### 3.3 Lessons and relationships

| ID | Screen name | Purpose | Parent screen | Main actions |
| --- | --- | --- | --- | --- |
| A13 | Lessons Management | Find and organize lessons across the learning hierarchy. | A01 | Create; edit; organize; publish/unpublish; archive; reorder lessons. |
| A14 | Create Lesson / Edit Lesson | Author lesson text, images, embedded video, documents, and placement. | A13 | Edit content; assign level/unit/category; attach files/media; configure relationships/access; save; publish/unpublish. |
| A15 | Learning Content Relationships / Prerequisites | Manage related content, topics, prerequisite lessons, and configured next-content links. | A14 | Link/unlink learning content; set prerequisites and relevant relationships; save. |

A13 also opens from A12. A15 is shared by exercise, exam, resource, video, and topic editors where those relationships apply. It does not imply a separately specified recommendation algorithm or recommendation-management module.

### 3.4 Exercises and question authoring

| ID | Screen name | Purpose | Parent screen | Main actions |
| --- | --- | --- | --- | --- |
| A16 | Exercises Management | Find and organize exercises and publication states. | A01 | Create; edit; publish/unpublish; archive; reorder exercises. |
| A17 | Create Exercise / Edit Exercise | Configure instructions, placement, relationships, and access. | A16 | Edit details; manage questions; configure prerequisites/access; save; publish/unpublish. |
| A18 | Exercise Questions | Organize the questions belonging to an exercise. | A17 | Add; edit; remove; reorder questions. |
| A19 | Create Exercise Question / Edit Exercise Question | Author a supported question type, answer options, correct answers, grading data, and explanations. | A18 | Select type; edit prompt/options/answers; configure correction; save. |

Question types are editor variants; future question formats are outside this inventory until requested.

### 3.5 Exams and corrections

| ID | Screen name | Purpose | Parent screen | Main actions |
| --- | --- | --- | --- | --- |
| A20 | Exams Management | Organize document-based and interactive exams. | A01 | Create; edit; publish/unpublish; archive; reorder exams. |
| A21 | Create Document Exam / Edit Document Exam | Maintain exam PDFs, correction PDFs, related resources, availability, and access. | A20 | Upload/replace exam and correction files; link resources; configure access/availability; save; publish/unpublish. |
| A22 | Create Interactive Exam / Edit Interactive Exam | Maintain instructions, timing, availability, relationships, and access rules. | A20 | Configure timed/untimed mode; manage questions; configure prerequisites/access; save; publish/unpublish. |
| A23 | Exam Questions | Organize questions within an interactive exam. | A22 | Add; edit; remove; reorder questions. |
| A24 | Create Exam Question / Edit Exam Question | Define question content, answers, supported grading, corrections, and explanations. | A23 | Edit prompt/options/answers; configure correction; save. |

### 3.6 Resources, files, and videos

| ID | Screen name | Purpose | Parent screen | Main actions |
| --- | --- | --- | --- | --- |
| A25 | Resources / Files Management | Organize educational PDFs, books, worksheets, guides, and files. | A01 | Browse; create; edit; organize; publish/unpublish; archive resources. |
| A26 | Create Resource / Edit Resource | Maintain file, type, metadata, learning relationships, and access. | A25 | Upload/replace file; edit details; link learning content; configure access; save; publish/unpublish. |
| A27 | Videos Management | Organize the educational video library. | A01 | Create; edit; publish/unpublish; archive; reorder videos. |
| A28 | Create Video / Edit Video | Maintain a supported video source and learning relationships. | A27 | Set YouTube or another supported source; edit details; link lessons/topics; configure access; save; publish/unpublish. |

Lesson attachments and exam/correction uploads are also managed in their owning editors. A separate general-purpose media asset manager is not required by the PRD.

### 3.7 Topics

| ID | Screen name | Purpose | Parent screen | Main actions |
| --- | --- | --- | --- | --- |
| A29 | Topics Management | Maintain the concepts used by the Topic Index. | A01 | Create; edit; organize; archive topics. |
| A30 | Create Topic / Edit Topic | Maintain topic details and connections to learning material. | A29 | Edit topic; link/unlink content through A15; save. |

### 3.8 Student communication

| ID | Screen name | Purpose | Parent screen | Main actions |
| --- | --- | --- | --- | --- |
| A31 | Student Conversations | Find and manage student messages and questions. | A01 | Browse; filter; open a conversation. |
| A32 | Student Conversation Detail / Reply | Read a student conversation and its attached learning context. | A31 | Read messages; open linked lesson/content; send a reply; manage the conversation as permitted. |

### 3.9 Points, rewards, and unlock configuration

| ID | Screen name | Purpose | Parent screen | Main actions |
| --- | --- | --- | --- | --- |
| A33 | Points / Rewards Configuration | Manage engagement rules and available rewards. | A01 | Open earning/spending rules; manage rewards and achievements; configure streak/daily-reward/leaderboard options where enabled. |
| A34 | Create Points Rule / Edit Points Rule | Define configured earning or spending conditions. | A33 | Select qualifying activity; set conditions and point values/costs; save. |
| A35 | Rewards / Achievements Management | Maintain configured rewards, badges, and achievement definitions. | A33 | Create; edit; enable/disable applicable rewards. |
| A36 | Create Reward or Achievement / Edit Reward or Achievement | Define criteria and the resulting reward or unlock. | A35 | Edit criteria; set reward; link eligible content/features; save. |
| A37 | Streak / Daily Reward / Leaderboard Settings — conditional where applicable | Configure the PRD's optional engagement mechanisms. | A33 | Edit supported streak/reward rules; enable/configure optional mechanisms; save. |

### 3.10 Access and monetization

| ID | Screen name | Purpose | Parent screen | Main actions |
| --- | --- | --- | --- | --- |
| A38 | Access Rules / Paid-Free Content Management | Inspect and maintain configured content and feature restrictions. | A01 | Find restricted content; open its editor; create/edit applicable access rules. |
| A39 | Create Access Rule / Edit Access Rule | Configure public, registered-user, points-unlocked, paid, level-based, or other supported restrictions. | A38 | Select target content/feature; set eligibility and configured cost/access conditions; save. |
| A40 | Paid Offer / Teacher-Service Configuration — where offered | Maintain the configured paid/free offer or teacher-service promotion required by the business model. | A38 | Edit offer/service information and supported access terms; associate eligible learning content; save. |
| A41 | Subscription Configuration — conditional | Maintain subscription offers and access where enabled. | A38 | Edit configured offer terms; associate eligible content/access; save. |
| A42 | Advertising / Promotional Configuration — where enabled | Maintain supported advertising or promotional settings, including eligible reward actions. | A01 | Edit configured placements/promotions; connect qualifying point rules through A34; save. |

A39 is shared by content editors and feature unlock configuration. A40–A42 are logical management surfaces inferred from the PRD's configurable monetization requirements (§§16–19); their exact fields and workflows are unspecified. They do not establish a billing, sales CRM, or advertising campaign product.

### 3.11 Platform settings

| ID | Screen name | Purpose | Parent screen | Main actions |
| --- | --- | --- | --- | --- |
| A43 | Platform Settings | Maintain the platform settings required to operate configured features and languages. | A01 | Review/edit supported settings; configure available languages/features; save. |

### 3.12 Analytics and student activity

PRD §§13, 19, 21. These are logical report views and may belong to a shared analytics destination.

| ID | Screen name | Purpose | Parent screen | Main actions |
| --- | --- | --- | --- | --- |
| A44 | Analytics Overview | Summarize student, learning, engagement, communication, and business activity. | A01 | Inspect aggregate metrics; open a report area. |
| A45 | Acquisition Analytics | Report visitors, registrations, and active students. | A44 | Review acquisition and activation measures. |
| A46 | Engagement Analytics | Report active users, lesson activity, exercise/exam activity, videos, resources, and streaks. | A44 | Review engagement; inspect available student activity through A04. |
| A47 | Learning Analytics | Report completion, exercise scores, exam performance, progress, and common learning difficulties. | A44 | Review learning outcomes; inspect available student progress/attempts through A04. |
| A48 | Communication Analytics | Report Ask Teacher requests, messages, and teacher response activity. | A44 | Review communication activity; open authorized conversations. |
| A49 | Business Analytics | Report paid-content conversions, service leads, and subscription/advertising results where applicable. | A44 | Inspect applicable conversion and business measures. |

## 4. Contextual states and unspecified flows

- Loading, empty results, validation errors, unavailable content, failed submissions, and successful saves are states of their owning screens, not additional modules. Access requirements and unlock outcomes are explicitly inventoried because they contain a user task.
- Registration/login are specified. Password recovery, email verification, social login, and account deletion are not explicitly required in this PRD; their screen requirements need clarification before inclusion.
- The PRD requires teacher-service promotion and lead generation but does not define booking, lead capture, checkout, or payment workflows. Relevant offer and analytics surfaces are included; additional transaction/contact screens depend on clarified requirements.
- No student chat rooms, peer messaging, future question formats, or separate mobile applications are included.

## 5. PRD coverage map

| Requirement area | Inventory coverage |
| --- | --- |
| Public discovery and learning hierarchy (§§3–5, 20) | G01–G07; S01–S02; A07–A14 |
| Dashboard, lessons, prerequisites, recommendations (§§6–7, 14) | S01, G07, S03; A14–A15 |
| Exercises and grading (§8) | G11–G12; S04–S06; A16–A19 |
| Document and interactive exams (§9) | G13–G15, G18; S07–S09; A20–A24 |
| Resources, videos, search, topics (§§10–12) | G08–G10, G16–G20; A25–A30 |
| Progress and history (§13) | S05–S12; A04, A44, A46–A47 |
| Student–teacher messages (§15) | S13–S15; A31–A32, A48 |
| Points, rewards, achievements, streaks (§16) | S16–S19; A33–A37 |
| Access, premium content, monetization (§17) | G23; S18, S20–S21; A38–A42, A49 |
| Authentication, profile, language, learning preferences (§18) | G21–G22; S02, S22–S23; A02–A06, A43 |
| Administration and content lifecycle (§19) | A01–A43 |
| Analytics and success metrics (§21) | A04, A44–A49 |
| Multilingual/responsive experience and expansion (§§22–23) | Cross-cutting requirements for applicable screens; reusable content management hierarchy |
| Acceptance criteria and learning journey (§§24–26) | End-to-end coverage across shared, student, and management groups above |
