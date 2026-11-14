<?php

namespace App\Support;

class Prototype
{
    public const ACCESS_PREVIEWS = [
        'exercise-attempt' => [
            'title' => 'Passive voice practice',
            'requirement' => 'Sign-in required',
            'description' => 'Sign in to start an attempt and keep a history of your practice.',
            'action' => ['G22', 'Preview sign in', ['return' => 'exercise']],
            'back' => ['G12', 'Back to exercise'],
        ],
        'unit-exam' => [
            'title' => 'Unit 1 · Practice exam',
            'requirement' => 'Learning level required',
            'description' => 'Choose 2nd Baccalaureate in your learning goals to access this exam.',
            'action' => ['S02', 'Choose learning goals', []],
            'back' => ['G15', 'Back to exam'],
        ],
        'passive-voice-practice' => [
            'title' => 'Passive voice practice',
            'requirement' => 'Prerequisite required',
            'description' => 'Complete the passive voice lesson before starting this practice exercise.',
            'action' => ['G07', 'Open prerequisite lesson', []],
            'back' => ['G12', 'Back to exercise'],
        ],
        'grammar-worksheet' => [
            'title' => 'Grammar revision worksheet',
            'requirement' => '100 points required',
            'description' => 'Use 100 points to unlock this worksheet.',
            'action' => ['S18', 'Preview points unlock', []],
            'back' => ['G16', 'Back to resources'],
        ],
        'revision-pack' => [
            'title' => 'Complete revision pack',
            'requirement' => 'Premium content',
            'description' => 'This revision pack requires premium access. This preview shows the access requirement.',
            'action' => null,
            'back' => ['G16', 'Back to resources'],
        ],
    ];

    public const QUESTIONS = [
        ['Choose the passive sentence', 'Multiple choice', 'Ready'],
        ['Select the participles', 'Multiple selection', 'Ready'],
        ['Passive statement', 'True / False', 'Draft'],
    ];

    public const UNIT_CATEGORIES = [
        'vocabulary' => 'Vocabulary',
        'functions' => 'Functions',
        'reading' => 'Reading',
        'grammar' => 'Grammar',
        'writing' => 'Writing',
    ];

    /** Static screen catalogue. Conditional and decision-gated features are excluded. */
    public static function screens(): array
    {
        $definitions = [
            ['G01', 'Home', '/', 'home', null],
            ['G02', 'Learning paths', '/learning-paths', 'paths', 'G01'],
            ['G03', '2nd Baccalaureate', '/learning-paths/2nd-baccalaureate', 'level', 'G02'],
            ['G04', 'Units', '/units', 'units', 'G03'],
            ['G05', 'Unit 1 · Gifts of youth', '/units/gifts-of-youth', 'unit', 'G04'],
            ['G06', 'Grammar lessons', '/units/gifts-of-youth/grammar', 'lessons', 'G05'],
            ['G07', 'The passive voice', '/lessons/passive-voice', 'lesson', 'G06'],
            ['G08', 'Search', '/search', 'search', 'G01'],
            ['G09', 'Topic index', '/topics', 'topics', 'G01'],
            ['G10', 'Passive voice · Topic', '/topics/passive-voice', 'topic', 'G09'],
            ['G11', 'Exercises', '/exercises', 'exercises', 'G01'],
            ['G12', 'Passive voice practice', '/exercises/passive-voice', 'instructions', 'G11'],
            ['G13', 'Exams', '/exams', 'exams', 'G01'],
            ['G14', 'National exam · Document', '/exams/national-document', 'document-exam', 'G13'],
            ['G15', 'Unit 1 · Practice exam', '/exams/unit-one', 'instructions', 'G13'],
            ['G16', 'Resource library', '/resources', 'resources', 'G01'],
            ['G17', 'Passive voice study guide', '/resources/passive-voice-guide', 'resource', 'G16'],
            ['G18', 'Document viewer', '/documents/study-guide', 'document', 'G17'],
            ['G19', 'Video library', '/videos', 'videos', 'G01'],
            ['G20', 'Passive voice · Video lesson', '/videos/passive-voice', 'video', 'G19'],
            ['G21', 'Create your account', '/register', 'auth', 'G01'],
            ['G22', 'Welcome back', '/login', 'auth', 'G01'],
            ['G23', 'Content access requirements', '/access', 'access', 'G01'],
            ['S01', 'Your learning dashboard', '/dashboard', 'dashboard', null],
            ['S02', 'Choose your learning goals', '/learning-goals', 'selection', 'S01'],
            ['S03', 'Recommended preparation', '/preparation', 'preparation', 'G07'],
            ['S04', 'Exercise · Passive voice', '/exercises/passive-voice/attempt', 'attempt', 'G12'],
            ['S05', 'Exercise results', '/exercises/passive-voice/results', 'results', 'S04'],
            ['S06', 'Exercise attempt history', '/exercises/passive-voice/history', 'history', 'G12'],
            ['S07', 'Exam · Unit 1', '/exams/unit-one/attempt', 'attempt', 'G15'],
            ['S08', 'Exam results', '/exams/unit-one/results', 'results', 'S07'],
            ['S09', 'Exam attempt history', '/exams/unit-one/history', 'history', 'G15'],
            ['S10', 'Your progress', '/progress', 'progress', 'S01'],
            ['S11', '2nd Baccalaureate progress', '/progress/2nd-baccalaureate', 'progress', 'S10'],
            ['S12', 'Learning activity', '/progress/activity', 'history', 'S10'],
            ['S13', 'Messages', '/messages', 'messages', 'S01'],
            ['S14', 'Ask your teacher', '/messages/new', 'compose', 'S13'],
            ['S15', 'Conversation with your teacher', '/messages/passive-voice', 'conversation', 'S13'],
            ['S16', 'Rewards & achievements', '/rewards', 'rewards', 'S01'],
            ['S17', 'Consistent learner', '/rewards/consistent-learner', 'achievement', 'S16'],
            ['S18', 'Unlock a study resource', '/rewards/unlock', 'unlock', 'S16'],
            ['S22', 'Your profile', '/profile', 'profile', 'S01'],
            ['S23', 'Learning preferences', '/settings', 'settings', 'S22'],
            ['A01', 'Administration overview', '/admin', 'admin-home', null],
            ['A02', 'Users', '/admin/users', 'admin-list', 'A01'],
            ['A03', 'User', '/admin/users/editor', 'admin-editor', 'A02'],
            ['A04', 'Student learning activity', '/admin/users/amina', 'student', 'A02'],
            ['A05', 'Roles', '/admin/roles', 'admin-list', 'A01'],
            ['A06', 'Role', '/admin/roles/editor', 'admin-editor', 'A05'],
            ['A07', 'Learning paths', '/admin/learning-paths', 'admin-list', 'A01'],
            ['A08', 'Learning path', '/admin/learning-paths/editor', 'admin-editor', 'A07'],
            ['A09', 'Units', '/admin/units', 'admin-list', 'A08'],
            ['A10', 'Unit', '/admin/units/editor', 'admin-editor', 'A09'],
            ['A11', 'Categories', '/admin/categories', 'admin-list', 'A10'],
            ['A12', 'Category', '/admin/categories/editor', 'admin-editor', 'A11'],
            ['A13', 'Lessons', '/admin/lessons', 'admin-list', 'A01'],
            ['A14', 'Lesson', '/admin/lessons/editor', 'admin-editor', 'A13'],
            ['A15', 'Relationships & prerequisites', '/admin/relationships', 'relationships', 'A14'],
            ['A16', 'Exercises', '/admin/exercises', 'admin-list', 'A01'],
            ['A17', 'Exercise', '/admin/exercises/editor', 'admin-editor', 'A16'],
            ['A18', 'Exercise questions', '/admin/exercises/questions', 'admin-list', 'A17'],
            ['A19', 'Exercise question', '/admin/exercises/questions/editor', 'question-editor', 'A18'],
            ['A20', 'Exams', '/admin/exams', 'admin-list', 'A01'],
            ['A21', 'Document exam', '/admin/exams/document/editor', 'admin-editor', 'A20'],
            ['A22', 'Interactive exam', '/admin/exams/interactive/editor', 'admin-editor', 'A20'],
            ['A23', 'Exam questions', '/admin/exams/questions', 'admin-list', 'A22'],
            ['A24', 'Exam question', '/admin/exams/questions/editor', 'question-editor', 'A23'],
            ['A25', 'Resources & files', '/admin/resources', 'admin-list', 'A01'],
            ['A26', 'Resource', '/admin/resources/editor', 'admin-editor', 'A25'],
            ['A27', 'Videos', '/admin/videos', 'admin-list', 'A01'],
            ['A28', 'Video', '/admin/videos/editor', 'admin-editor', 'A27'],
            ['A29', 'Topics', '/admin/topics', 'admin-list', 'A01'],
            ['A30', 'Topic', '/admin/topics/editor', 'admin-editor', 'A29'],
            ['A31', 'Student conversations', '/admin/messages', 'messages', 'A01'],
            ['A32', 'Reply to Amina', '/admin/messages/amina', 'conversation', 'A31'],
            ['A33', 'Points & rewards configuration', '/admin/points', 'admin-list', 'A01'],
            ['A34', 'Points rule', '/admin/points/editor', 'admin-editor', 'A33'],
            ['A35', 'Achievements', '/admin/achievements', 'admin-list', 'A33'],
            ['A36', 'Achievement', '/admin/achievements/editor', 'admin-editor', 'A35'],
            ['A37', 'Streak settings', '/admin/streak-settings', 'admin-editor', 'A33'],
            ['A38', 'Access rules', '/admin/access', 'admin-list', 'A01'],
            ['A39', 'Access rule', '/admin/access/editor', 'admin-editor', 'A38'],
            ['A43', 'Platform settings', '/admin/settings', 'settings', 'A01'],
            ['A44', 'Analytics overview', '/admin/analytics', 'analytics', 'A01'],
            ['A45', 'Acquisition analytics', '/admin/analytics/acquisition', 'analytics', 'A44'],
            ['A46', 'Engagement analytics', '/admin/analytics/engagement', 'analytics', 'A44'],
            ['A47', 'Learning analytics', '/admin/analytics/learning', 'analytics', 'A44'],
            ['A48', 'Communication analytics', '/admin/analytics/communication', 'analytics', 'A44'],
            ['A49', 'Business analytics', '/admin/analytics/business', 'analytics', 'A44'],
        ];

        $screens = [];
        foreach ($definitions as [$id, $title, $path, $template, $parent]) {
            $path = '/prototype'.rtrim($path, '/');
            $screens[$id] = compact('id', 'title', 'path', 'template', 'parent');
        }

        return $screens;
    }

    public const EXERCISE_ATTEMPTS = [
        2 => ['date' => '29 Oct 2026', 'score' => 16, 'answers' => [6 => 'The school is built.']],
        1 => ['date' => '26 Oct 2026', 'score' => 12, 'answers' => [
            2 => 'Written, Went',
            5 => 'are',
            6 => 'They built the school.',
        ]],
    ];

    public const EXAM_ATTEMPTS = [
        2 => ['date' => '27 Oct 2026', 'score' => 15, 'answers' => [6 => 'The school is built.']],
        1 => ['date' => '24 Oct 2026', 'score' => 10, 'answers' => [
            2 => 'Written, Went',
            5 => 'are',
            6 => 'They built the school.',
        ]],
    ];

    public const PRACTICE_CORRECTIONS = [
        1 => ['The report is written by them.', 'Use is + past participle for the present simple passive.'],
        2 => ['Written, Seen', 'Written and seen are past participles. Went is the simple past of go.'],
        3 => ['True', 'Was cleaned uses be and a past participle, which form the passive voice.'],
        4 => ['Present simple: is written. Past simple: was written. Future simple: will be written.', 'Change the tense of be and keep the past participle written.'],
        5 => ['is', 'English is a singular subject. Use is spoken.'],
        6 => ['The school was built.', 'Built is the past participle of build. Use was for a singular past subject.'],
        7 => ['The event is organized by students.', 'Start with the subject, then the passive verb, then the by-phrase.'],
    ];

    public static function routeName(string $id): string
    {
        return 'prototype.'.$id;
    }

    public static function url(string $id, array $parameters = []): string
    {
        // Carry the student preview into shared browsing pages without a session.
        $current = request()->route('screen') ?? '';
        if (str_starts_with($id, 'G') && (str_starts_with($current, 'S') || request('preview') === 'student')) {
            $parameters['preview'] = 'student';
        }

        return route(self::routeName($id), $parameters);
    }
}
