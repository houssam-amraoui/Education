# Product Requirements Document (PRD)

## English Learning Platform — Laravel Web Application

**Status:** Development Ready  
**Platform:** Laravel Web Application  
**Primary Market:** Morocco  
**Languages:** Arabic, French, English

---

# 1. Product Vision

Build a complete web-based English learning platform for Moroccan students, starting with 2nd Baccalaureate and designed to support additional levels and general English learning.

The platform combines structured lessons, interactive exercises and exams, educational resources, videos, progress tracking, gamification, teacher communication, and administrative content management in one system.

It should also help the teacher promote educational services and convert engaged students into paid learners.

---

# 2. Product Goals

## Educational Goals

- Organize English learning into clear levels, units, categories, and lessons.
- Provide lessons using text, video, PDFs, and related resources.
- Provide interactive exercises and exams with automatic correction where possible.
- Track student progress, scores, activity, and learning achievements.
- Help students identify prerequisite lessons and recommended content.
- Allow students to search and quickly find learning material.
- Give students direct contextual access to teacher assistance.

## Business Goals

- Attract and retain students.
- Increase recurring learning activity.
- Promote teacher services and paid lessons.
- Generate qualified leads.
- Increase engagement through points, rewards, streaks, unlockable content, and advertising.
- Support free and paid educational content.

---

# 3. Target Users

## Guest

Can browse public content, levels, lessons, resources, videos, and available learning paths.

## Student

Can:

- Register and manage a profile.
- Select one or more learning levels/goals.
- Study lessons.
- Complete exercises and interactive exams.
- View scores, corrections, history, and progress.
- Search learning content.
- Access resources and PDFs.
- Watch educational videos.
- Earn and spend points.
- Maintain learning streaks and achievements.
- Unlock eligible content or features.
- Ask the teacher questions.
- Receive teacher replies.
- Receive recommendations based on learning activity.

## Teacher / Administrator

Can manage the complete platform without changing source code, including:

- Users and roles.
- Levels and learning paths.
- Units and categories.
- Lessons and lesson content.
- Exercises, questions, answers, and corrections.
- Exams and exam questions.
- PDFs, books, worksheets, and resources.
- Videos.
- Prerequisites.
- Points, rewards, unlock conditions, and access rules.
- Student messages and teacher replies.
- Paid/free content.
- Platform analytics and student activity.

---

# 4. Learning Structure

```text
Level / Learning Path
└── Units
    └── Unit
        ├── Vocabulary
        │   └── Lessons
        ├── Functions
        │   └── Lessons
        ├── Reading
        │   └── Lessons
        ├── Grammar
        │   └── Lessons
        └── Writing
            └── Lessons
```

Each level also provides:

```text
Level
├── Units
├── Exercises
├── Exams
├── Resources
├── Videos
└── Topic Index
```

The system must support adding new levels, units, categories, lessons, and learning paths from the administration interface.

---

# 5. Main Student Navigation

The student interface should provide direct access to:

1. Dashboard
2. Learning Paths / Levels
3. Units
4. Exercises
5. Exams
6. Resources
7. Videos
8. Search
9. Topic Index
10. Messages / Ask Teacher
11. Progress
12. Rewards / Achievements
13. Profile and Settings

Navigation must be simple, responsive, mobile-friendly, fast, and consistent.

---

# 6. Dashboard

The dashboard should summarize the student's current learning state and provide quick access to the next useful action.

It should display:

- Selected learning level/path.
- Continue Learning action.
- Overall and level-specific progress.
- Recent lessons.
- Recommended lessons.
- Pending or recommended exercises.
- Recent scores.
- Exams and resources.
- Learning streak.
- Points and achievements.
- Teacher messages or replies.

---

# 7. Lessons

A lesson can contain:

- Rich formatted text.
- Images.
- Embedded video.
- PDF attachments.
- Downloadable resources.
- Related exercises.
- Related exams.
- Prerequisite lessons.
- Recommended next lessons.

Students can:

- Study lesson content.
- Watch videos.
- Open attached documents.
- Start related exercises.
- Open related resources.
- Ask the teacher a lesson-specific question.
- Mark or automatically register lesson completion.

Lesson completion contributes to progress and recommendations.

---

# 8. Exercises

Exercises can be opened from a lesson or from the main Exercises section.

Supported question types should include:

- Multiple choice.
- Multiple selection.
- True / False.
- Matching.
- Fill in the blanks.
- Short answer.
- Ordering / sequencing.
- Other configurable question formats added later by administrators or development.

Exercise flow:

```text
Open Exercise
→ Read Instructions
→ Answer Questions
→ Submit
→ Automatic Evaluation
→ Score
→ Corrections / Explanations
→ Retry or Continue
→ Progress Updated
```

The platform should store attempts, scores, completion status, and relevant answer history.

---

# 9. Exams

The platform must support both:

## Document-Based Exams

- Exam PDF.
- Correction PDF.
- Related resources.

## Interactive Exams

- Timed or untimed exams.
- Multiple question types.
- Automatic correction where supported.
- Score calculation.
- Corrections and explanations.
- Attempt history.
- Completion tracking.

Administrators should be able to configure exam availability and access rules.

---

# 10. Resources and Documents

Resource types include:

- PDFs.
- Books.
- Worksheets.
- Study guides.
- Other educational files.

Students should be able to browse, search, filter, open, and download supported resources.

PDFs should be readable directly inside the web application when supported by the browser.

---

# 11. Videos

The platform should support a video library connected to lessons and learning topics.

Primary support:

- YouTube embeds.

The content model should also allow other supported video sources when needed.

Students can browse, search, filter, watch, and open related learning material.

---

# 12. Search and Topic Index

Global search should return relevant:

- Lessons.
- Exercises.
- Exams.
- Videos.
- Resources.
- Topics.

Each result should show its type, title, relevant context, and direct navigation link.

The Topic Index provides another navigation method for concepts such as:

- Passive Voice.
- Reported Speech.
- Vocabulary topics.
- Writing topics.

Selecting a topic opens its related learning content.

---

# 13. Progress Tracking

The platform should track:

- Lessons viewed and completed.
- Exercises completed.
- Exercise attempts and scores.
- Exams attempted and completed.
- Exam scores.
- Resources used where relevant.
- Learning streaks.
- Overall and per-level completion.

Students should see their progress through clear dashboards, percentages, history, and achievement indicators.

Administrators should have access to aggregate learning analytics.

---

# 14. Smart Prerequisites and Recommendations

Lessons, exercises, and exams may define prerequisite learning content.

Before entering advanced content, the student can be shown recommended prerequisite lessons.

The system should also recommend useful next content based on:

- Current level.
- Completed lessons.
- Exercise performance.
- Exam performance.
- Prerequisites.
- Recent activity.

---

# 15. Messaging and Ask Teacher

Communication is:

**Student ↔ Teacher**

There is no student-to-student messaging.

Students can:

- Send teacher messages.
- View conversations and replies.
- Ask questions directly from a lesson.

When Ask Teacher is opened from learning content, the relevant lesson or content context must be attached automatically.

Teachers can view, filter, reply to, and manage student conversations.

---

# 16. Points, Rewards, and Gamification

The platform should include a configurable engagement system.

Students can earn points from activities such as:

- Completing lessons.
- Completing exercises.
- Achieving exercise scores.
- Completing exams.
- Maintaining streaks.
- Selected promotional or advertising actions.

Points may unlock:

- Exercises.
- Exams.
- Resources.
- Ask Teacher access.
- Other configured features or content.

Gamification may include:

- Points.
- Learning streaks.
- Achievements / badges.
- Daily rewards.
- Leaderboards where enabled.

Administrators control earning rules, spending rules, rewards, and unlock conditions.

---

# 17. Access and Monetization

Content can be configured as:

- Public.
- Registered-user only.
- Points-unlocked.
- Paid / premium.
- Restricted by learning level or other access rules.

Monetization channels include:

- Advertising.
- Teacher-service promotion.
- Paid lessons.
- Premium educational content.
- Subscriptions where enabled.

The platform should clearly separate free and restricted content without disrupting the learning experience.

---

# 18. Authentication and Accounts

Guests should be able to browse public content without registration.

Authentication is required for personalized features such as:

- Progress tracking.
- Exercise and exam history.
- Messaging.
- Points and rewards.
- Achievements.
- Paid content.
- Personalized recommendations.

Registration and login should be simple and low-friction.

Students should be able to manage profile information, learning preferences, language, and selected levels.

---

# 19. Administration and Content Management

The Laravel application must provide an administrative interface that allows authorized users to manage the platform without editing application code.

Core management areas:

- Users and roles.
- Levels / learning paths.
- Units.
- Categories.
- Lessons.
- Exercises and questions.
- Exams and corrections.
- Resources and files.
- Videos.
- Topics.
- Prerequisites.
- Messages.
- Points and rewards.
- Access rules.
- Paid/free content.
- Platform settings.
- Analytics.

Administrators should be able to create, edit, organize, publish, unpublish, archive, and reorder relevant content.

---

# 20. Major User Flows

## Study a Lesson

```text
Home
→ Select Level / Learning Path
→ Dashboard
→ Unit
→ Category
→ Lesson
→ Study Content
→ Exercise / Next Lesson / Ask Teacher
```

## Complete an Exercise

```text
Lesson or Exercises
→ Exercise
→ Answer Questions
→ Submit
→ Score + Correction
→ Retry or Continue
→ Progress Updated
```

## Complete an Interactive Exam

```text
Exams
→ Select Exam
→ Start
→ Answer Questions
→ Submit
→ Score + Correction
→ Exam History Updated
```

## Find Content

```text
Search or Topic Index
→ Find Relevant Result
→ Open Lesson / Exercise / Exam / Video / Resource
```

## Ask Teacher

```text
Lesson
→ Ask Teacher
→ Lesson Context Attached
→ Send Question
→ Teacher Replies
→ Student Reads Reply
```

## Teacher Publishes Content

```text
Admin Panel
→ Select Content Type
→ Create / Edit Content
→ Configure Relationships and Access
→ Publish
→ Content Available to Eligible Students
```

---

# 21. Analytics and Success Metrics

The platform should measure:

## Acquisition

- Visitors.
- Registrations.
- Active students.

## Engagement

- Daily/monthly active users.
- Lessons opened and completed.
- Exercises started and completed.
- Repeat attempts.
- Exams attempted.
- Videos watched.
- Resources accessed.
- Learning streaks.

## Learning

- Exercise completion rate.
- Average scores.
- Exam performance.
- Progress completion.
- Common learning difficulties.

## Communication

- Ask Teacher requests.
- Messages sent.
- Teacher response activity.

## Business

- Paid-content conversions.
- Teacher-service leads.
- Subscription conversions where applicable.
- Advertising engagement where applicable.

---

# 22. Product Experience Requirements

The interface must be:

- Responsive and mobile-friendly.
- Clean and student-focused.
- Fast and easy to understand.
- Consistent across public, student, and admin areas.
- Accessible from modern desktop and mobile browsers.
- Designed to minimize unnecessary steps and distractions.

The application should support Arabic, French, and English interfaces and content where configured.

---

# 23. Scalability Requirements

The platform must allow normal expansion without redesigning the product.

Administrators should be able to add:

- New school levels.
- New English learning paths.
- New units and categories.
- New lessons.
- New exercise and exam content.
- New resources and videos.
- New access and reward configurations.

The product should support increasing numbers of students and educational content while preserving a consistent learning experience.

---

# 24. Core Acceptance Criteria

The product is functionally complete when:

- Guests can browse permitted public learning content.
- Students can register, log in, and manage their learning profile.
- Students can navigate from level → unit → category → lesson without broken flows.
- Lessons correctly display configured text, video, documents, resources, and relationships.
- Exercises support answer submission, grading, corrections, scores, attempts, and retries.
- Document-based and interactive exams work correctly.
- Student progress and score history persist between sessions.
- Search and Topic Index navigate to correct content.
- Resources and videos are accessible from the platform.
- Prerequisites and recommendations are displayed when configured.
- Messaging and lesson-linked Ask Teacher work end-to-end.
- Points, rewards, streaks, achievements, and configured unlock rules operate correctly.
- Free, restricted, and paid content follow configured access rules.
- Teachers and administrators can manage all core content without source-code changes.
- Platform analytics expose relevant student, learning, engagement, and business activity.
- Core flows work correctly on modern desktop and mobile browsers.

---

# 25. Product Development Focus

Development should preserve the complete learning journey:

```text
Student discovers the platform
→ Selects a learning path
→ Studies structured lessons
→ Practices exercises
→ Completes exams
→ Receives corrections and scores
→ Tracks progress
→ Receives recommendations
→ Earns rewards
→ Finds more content
→ Asks the teacher when needed
→ Continues learning
```

The administration experience must support the complete lifecycle of creating, organizing, publishing, restricting, monetizing, and analyzing this learning content.

---

# 26. Final Product Statement

The English Learning Platform is a complete Laravel web application for structured English education.

Students can learn through lessons, exercises, exams, resources, and videos; track their progress; receive recommendations; participate in a configurable reward system; access free or premium content; and communicate directly with a teacher.

Teachers and administrators can manage educational content, users, communication, access rules, rewards, monetization, and analytics from the application without modifying source code.
