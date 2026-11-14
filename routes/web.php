<?php

use App\Http\Controllers\Admin\AccessRuleController;
use App\Http\Controllers\AccessPageController;
use App\Http\Controllers\Admin\AchievementController;
use App\Http\Controllers\Admin\AnalyticsPageController;
use App\Http\Controllers\Admin\AnalyticsReportController;
use App\Http\Controllers\Admin\AdministrationController;
use App\Http\Controllers\Admin\AssessmentPrerequisiteController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ConversationController as AdminConversationController;
use App\Http\Controllers\Admin\CurriculumPageController;
use App\Http\Controllers\Admin\ExamController;
use App\Http\Controllers\Admin\ExerciseController;
use App\Http\Controllers\Admin\LessonController;
use App\Http\Controllers\Admin\LevelController;
use App\Http\Controllers\Admin\PlatformSettingsController;
use App\Http\Controllers\Admin\PointCostController;
use App\Http\Controllers\Admin\PointRuleController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\ResourceController;
use App\Http\Controllers\Admin\RewardPageController as AdminRewardPageController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\StudentProgressController as AdminStudentProgressController;
use App\Http\Controllers\Admin\TopicController;
use App\Http\Controllers\Admin\UnitController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserRoleController;
use App\Http\Controllers\Admin\VideoController;
use App\Http\Controllers\Auth\AccountPageController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\CurriculumController;
use App\Http\Controllers\DiscoveryController;
use App\Http\Controllers\ExamAttemptController;
use App\Http\Controllers\ExamFileController;
use App\Http\Controllers\ExamPageController;
use App\Http\Controllers\ExerciseAttemptController;
use App\Http\Controllers\LearningPathSelectionController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PrototypeController;
use App\Http\Controllers\ResourceFileController;
use App\Http\Controllers\ResourcePageController;
use App\Http\Controllers\RewardPageController;
use App\Http\Controllers\StudentProgressController;
use App\Http\Controllers\VideoPageController;
use App\Http\Middleware\RecordLearnerVisit;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\StartPreviewSession;
use App\Models\Conversation;
use App\Models\Exam;
use App\Models\Exercise;
use App\Queries\Analytics\AnalyticsReports;
use App\Support\Ability;
use App\Support\Prototype;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;

Route::name('account.')->middleware(SetLocale::class)->group(function (): void {
    Route::get('/dashboard', [AccountPageController::class, 'home'])->middleware('auth:web')->name('home');
    Route::middleware('guest:web')->group(function (): void {
        Route::get('/register', [AccountPageController::class, 'register'])->name('register.show');
        Route::post('/register', RegisterController::class)->name('register');
        Route::get('/login', [AccountPageController::class, 'login'])->name('login.show');
        Route::post('/login', [SessionController::class, 'store'])->name('login');
    });
});

foreach (['/account/login' => 'account.login.show', '/account/register' => 'account.register.show', '/account' => 'account.home'] as $path => $destination) {
    Route::get($path, function (\Illuminate\Http\Request $request) use ($destination) {
        $query = $request->getQueryString();

        return redirect()->to(route($destination).($query === null ? '' : '?'.$query), 301);
    });
}

Route::prefix('account')->name('account.')->middleware(SetLocale::class)->group(function (): void {
    Route::middleware('auth:web')->group(function (): void {
        Route::get('learning-goals', [LearningPathSelectionController::class, 'show'])->name('learning-goals.show');
        Route::put('learning-goals', [LearningPathSelectionController::class, 'update'])->name('learning-goals.update');
        Route::get('progress', [StudentProgressController::class, 'show'])->name('progress.show');
        Route::get('progress/activity', [StudentProgressController::class, 'history'])->name('progress.history');
        Route::get('progress/{level}', [StudentProgressController::class, 'show'])->whereNumber('level')->name('progress.level');
    });
    Route::get('profile', [ProfileController::class, 'show'])->middleware('auth:web')->name('profile.show');
    Route::put('profile', [ProfileController::class, 'update'])->middleware('auth:web')->name('profile.update');
    Route::get('preferences', [ProfileController::class, 'preferences'])->middleware('auth:web')->name('preferences.show');
    Route::put('preferences', [ProfileController::class, 'updatePreferences'])->middleware('auth:web')->name('preferences.update');
    Route::post('locale', [AccountPageController::class, 'locale'])->name('locale');
    Route::post('logout', [SessionController::class, 'destroy'])->middleware('auth:web')->name('logout');
});

Route::prefix('account/messages')->name('account.messages.')
    ->middleware([SetLocale::class, 'auth:web', 'can:create,'.Conversation::class])
    ->controller(ConversationController::class)->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('new', 'create')->name('create');
        Route::post('new', 'store')->name('store');
        Route::prefix('new/{type}/{source}')->where([
            'type' => implode('|', array_keys(Conversation::SOURCE_TYPES)), 'source' => '[0-9]+',
        ])->middleware('signed')->group(function (): void {
            Route::get('/', 'create')->name('source');
            Route::post('/', 'store')->name('source.store');
        });
        Route::get('{conversation}', 'show')->whereNumber('conversation')->name('show');
        Route::post('{conversation}', 'reply')->whereNumber('conversation')->name('reply');
    });

Route::prefix('admin/inbox')->name('admin.messages.')
    ->middleware([SetLocale::class, 'auth:web', 'can:manage,'.Conversation::class])
    ->controller(AdminConversationController::class)->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('{conversation}', 'show')->whereNumber('conversation')->name('show');
        Route::post('{conversation}', 'reply')->whereNumber('conversation')->name('reply');
    });

Route::prefix('admin/reward-configuration')->name('admin.reward-configuration.')
    ->middleware([SetLocale::class, 'auth:web', 'can:rewards.manage'])
    ->group(function (): void {
        Route::resource('point-rules', PointRuleController::class)
            ->parameters(['point-rules' => 'pointRule'])->only(['index', 'store', 'update']);
        Route::resource('achievements', AchievementController::class)->only(['index', 'store', 'update']);
        Route::resource('point-costs', PointCostController::class)
            ->parameters(['point-costs' => 'pointCost'])->only(['index', 'store', 'update']);
    });

Route::get('admin/analytics/{report?}', AnalyticsPageController::class)
    ->whereIn('report', AnalyticsReports::REPORTS)
    ->middleware([SetLocale::class, 'auth:web', 'cache.headers:private;no_store'])
    ->name('admin.analytics.show');

Route::get('admin/analytics-data/{report?}', AnalyticsReportController::class)
    ->whereIn('report', AnalyticsReports::REPORTS)
    ->middleware([SetLocale::class, 'auth:web', 'cache.headers:private;no_store'])
    ->name('admin.analytics.data');

Route::get('admin/overview', AdministrationController::class)
    ->middleware([SetLocale::class, 'auth:web', 'can:'.Ability::EnterAdministration->value])
    ->name('admin.overview');

Route::middleware([SetLocale::class, 'auth:web', 'can:settings.manage'])->group(function (): void {
    Route::get('admin/platform-settings', [PlatformSettingsController::class, 'show'])->name('admin.settings.show');
    Route::put('admin/platform-settings', [PlatformSettingsController::class, 'update'])->name('admin.settings.update');
});

Route::middleware([SetLocale::class, 'auth:web', 'can:access-rules.manage'])
    ->name('admin.')
    ->group(function (): void {
        Route::resource('admin/access-rules', AccessRuleController::class)
            ->parameters(['access-rules' => 'accessRule'])->only(['index', 'create', 'edit', 'store', 'update']);
    });

Route::prefix('admin/rewards')->name('admin.rewards.')
    ->middleware([SetLocale::class, 'auth:web', 'can:rewards.manage'])
    ->controller(AdminRewardPageController::class)->group(function (): void {
        Route::get('/', 'points')->name('points');
        Route::get('rules/create', 'rule')->name('rules.create');
        Route::get('rules/{pointRule}/edit', 'rule')->whereNumber('pointRule')->name('rules.edit');
        Route::get('costs/create', 'cost')->name('costs.create');
        Route::get('costs/{pointCost}/edit', 'cost')->whereNumber('pointCost')->name('costs.edit');
        Route::get('streak', 'streak')->name('streak.index');
        Route::get('streak/{pointRule}/edit', 'streak')->whereNumber('pointRule')->name('streak.edit');
        Route::get('achievements', 'achievements')->name('achievements.index');
        Route::get('achievements/create', 'achievement')->name('achievements.create');
        Route::get('achievements/{achievement}/edit', 'achievement')->whereNumber('achievement')->name('achievements.edit');
    });

Route::prefix('account/rewards')->name('account.rewards.')
    ->middleware([SetLocale::class, 'auth:web'])
    ->controller(RewardPageController::class)->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('achievements/{achievement}', 'show')->whereNumber('achievement')->name('show');
    });

Route::prefix('admin/accounts')->name('admin.accounts.')
    ->middleware([SetLocale::class, 'auth:web', 'can:'.Ability::EnterAdministration->value])
    ->group(function (): void {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::get('users/create', [UserController::class, 'create'])->name('users.create');
        Route::get('users/{user}/edit', [UserController::class, 'edit'])->whereNumber('user')->name('users.edit');
        Route::get('users/{user}/progress', [AdminStudentProgressController::class, 'show'])->whereNumber('user')->name('users.progress');
        Route::get('users/{user}/progress/{type}/{attempt}', [AdminStudentProgressController::class, 'review'])
            ->whereNumber(['user', 'attempt'])->whereIn('type', ['exercise', 'exam'])->name('users.progress.review');
        Route::put('users/{user}', [UserController::class, 'update'])->whereNumber('user')->name('users.update');
        Route::put('users/{user}/roles', UserRoleController::class)->whereNumber('user')->name('users.roles.update');
        Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
        Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
        Route::get('roles/create', [RoleController::class, 'create'])->name('roles.create');
        Route::get('roles/{role}/edit', [RoleController::class, 'edit'])->whereNumber('role')->name('roles.edit');
        Route::put('roles/{role}', [RoleController::class, 'update'])->whereNumber('role')->name('roles.update');
    });

Route::prefix('admin/curriculum')->name('admin.curriculum.')
    ->middleware([SetLocale::class, 'auth:web', 'can:'.Ability::EnterAdministration->value])
    ->scopeBindings()
    ->group(function (): void {
        Route::model('questionExercise', Exercise::class);
        Route::model('questionExam', Exam::class);

        foreach ([
            'exercises' => ['questionExercise', 'manage'],
            'exams' => ['questionExam', 'manageQuestions'],
        ] as $resource => [$owner, $ability]) {
            Route::prefix("{$resource}/{{$owner}}/questions")
                ->name("{$resource}.questions.")
                ->where([$owner => '[0-9]+', 'question' => '[0-9]+'])
                ->middleware("can:{$ability},{$owner}")
                ->controller(QuestionController::class)
                ->group(function (): void {
                    Route::get('/', 'index')->name('index');
                    Route::get('create', 'create')->name('create');
                    Route::get('{question}/edit', 'edit')->name('edit');
                    Route::post('/', 'store')->name('store');
                    Route::put('{question}', 'update')->name('update');
                    Route::delete('{question}', 'destroy')->name('destroy');
                    Route::patch('{question}/position', 'reorder')->name('reorder');
                });
        }

        Route::get('exercises/questions', [QuestionController::class, 'exercises'])->name('exercises.questions.owners');
        Route::get('exams/questions', [QuestionController::class, 'exams'])->name('exams.questions.owners');

        Route::get('levels', [CurriculumPageController::class, 'levels'])->name('levels.index');
        Route::get('levels/create', [CurriculumPageController::class, 'createLevel'])->name('levels.create');
        Route::get('levels/{level}/edit', [CurriculumPageController::class, 'editLevel'])->whereNumber('level')->name('levels.edit');
        Route::get('units', [CurriculumPageController::class, 'units'])->name('units.index');
        Route::get('units/create', [CurriculumPageController::class, 'createUnit'])->name('units.create');
        Route::get('units/{unit}/edit', [CurriculumPageController::class, 'editUnit'])->whereNumber('unit')->name('units.edit');
        Route::get('categories', [CurriculumPageController::class, 'categories'])->name('categories.index');
        Route::get('categories/create', [CurriculumPageController::class, 'createCategory'])->name('categories.create');
        Route::get('categories/{category}/edit', [CurriculumPageController::class, 'editCategory'])->whereNumber('category')->name('categories.edit');
        Route::post('exercises', [ExerciseController::class, 'store'])->name('exercises.store');
        Route::get('exercises', [ExerciseController::class, 'index'])->name('exercises.index');
        Route::get('exercises/create', [ExerciseController::class, 'create'])->name('exercises.create');
        Route::get('exercises/{exercise}/edit', [ExerciseController::class, 'edit'])->whereNumber('exercise')->name('exercises.edit');
        Route::get('exams', [ExamController::class, 'index'])->name('exams.index');
        Route::get('exams/create/{type}', [ExamController::class, 'create'])->whereIn('type', Exam::TYPES)->name('exams.create');
        Route::get('exams/{exam}/edit', [ExamController::class, 'edit'])->whereNumber('exam')->name('exams.edit');
        Route::post('exams', [ExamController::class, 'store'])->name('exams.store');
        Route::put('exams/{exam}', [ExamController::class, 'update'])->whereNumber('exam')->name('exams.update');
        Route::put('exams/{exam}/prerequisites', [AssessmentPrerequisiteController::class, 'exam'])->whereNumber('exam')->name('exams.prerequisites.update');
        Route::patch('units/{unit}/exams/{exam}/status', [ExamController::class, 'status'])->whereNumber(['unit', 'exam'])->name('exams.status');
        Route::patch('units/{unit}/exams/{exam}/position', [ExamController::class, 'reorder'])->whereNumber(['unit', 'exam'])->name('exams.reorder');
        Route::put('exercises/{exercise}', [ExerciseController::class, 'update'])->whereNumber('exercise')->name('exercises.update');
        Route::put('exercises/{exercise}/prerequisites', [AssessmentPrerequisiteController::class, 'exercise'])->whereNumber('exercise')->name('exercises.prerequisites.update');
        Route::patch('units/{unit}/exercises/{exercise}/status', [ExerciseController::class, 'status'])->whereNumber(['unit', 'exercise'])->name('exercises.status');
        Route::patch('units/{unit}/exercises/{exercise}/position', [ExerciseController::class, 'reorder'])->whereNumber(['unit', 'exercise'])->name('exercises.reorder');
        Route::post('levels', [LevelController::class, 'store'])->name('levels.store');
        Route::put('levels/{level}', [LevelController::class, 'update'])->whereNumber('level')->name('levels.update');
        Route::patch('levels/{level}/status', [LevelController::class, 'status'])->whereNumber('level')->name('levels.status');
        Route::patch('levels/{level}/position', [LevelController::class, 'reorder'])->whereNumber('level')->name('levels.reorder');
        Route::post('units', [UnitController::class, 'store'])->name('units.store');
        Route::put('units/{unit}', [UnitController::class, 'update'])->whereNumber('unit')->name('units.update');
        Route::patch('levels/{level}/units/{unit}/status', [UnitController::class, 'status'])->whereNumber(['level', 'unit'])->name('units.status');
        Route::patch('levels/{level}/units/{unit}/position', [UnitController::class, 'reorder'])->whereNumber(['level', 'unit'])->name('units.reorder');
        Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('categories/{category}', [CategoryController::class, 'update'])->whereNumber('category')->name('categories.update');
        Route::patch('units/{unit}/categories/{category}/archive', [CategoryController::class, 'archive'])
            ->whereNumber(['unit', 'category'])->can('manage', 'category')->name('categories.archive');
        Route::patch('units/{unit}/categories/{category}/position', [CategoryController::class, 'reorder'])->whereNumber(['unit', 'category'])->name('categories.reorder');
        Route::get('lessons', [CurriculumPageController::class, 'lessons'])->name('lessons.index');
        Route::get('categories/{category}/lessons/create', [CurriculumPageController::class, 'createLesson'])->whereNumber('category')->name('lessons.create');
        Route::get('categories/{category}/lessons/{lesson}/edit', [CurriculumPageController::class, 'editLesson'])->whereNumber(['category', 'lesson'])->name('lessons.edit');
        Route::get('categories/{category}/lessons/{lesson}/relationships', [CurriculumPageController::class, 'lessonRelationships'])->whereNumber(['category', 'lesson'])->name('lessons.relationships');
        Route::get('categories/{category}/lessons/{lesson}/preview', [CurriculumPageController::class, 'previewLesson'])->whereNumber(['category', 'lesson'])->name('lessons.preview');
        Route::post('categories/{category}/lessons', [LessonController::class, 'store'])->whereNumber('category')->name('lessons.store');
        Route::put('categories/{category}/lessons/{lesson}', [LessonController::class, 'update'])->whereNumber(['category', 'lesson'])->name('lessons.update');
        Route::put('categories/{category}/lessons/{lesson}/next-lessons', [LessonController::class, 'nextLessons'])->whereNumber(['category', 'lesson'])->name('lessons.next-lessons.update');
        Route::patch('categories/{category}/lessons/{lesson}/status', [LessonController::class, 'status'])->whereNumber(['category', 'lesson'])->name('lessons.status');
        Route::patch('categories/{category}/lessons/{lesson}/position', [LessonController::class, 'reorder'])->whereNumber(['category', 'lesson'])->name('lessons.reorder');
    });

Route::prefix('admin/library')->name('admin.library.')
    ->middleware([SetLocale::class, 'auth:web', 'can:'.Ability::EnterAdministration->value])
    ->group(function (): void {
        Route::get('topics', [TopicController::class, 'index'])->name('topics.index');
        Route::get('topics/create', [TopicController::class, 'create'])->name('topics.create');
        Route::get('topics/{topic}/edit', [TopicController::class, 'edit'])->whereNumber('topic')->name('topics.edit');
        Route::get('topics/{topic}/relationships', [TopicController::class, 'relationships'])->whereNumber('topic')->name('topics.relationships');
        Route::put('topics/{topic}/relationships', [TopicController::class, 'updateRelationships'])->whereNumber('topic')->name('topics.relationships.update');
        Route::post('topics', [TopicController::class, 'store'])->name('topics.store');
        Route::put('topics/{topic}', [TopicController::class, 'update'])->whereNumber('topic')->name('topics.update');
        Route::patch('topics/{topic}/status', [TopicController::class, 'status'])->whereNumber('topic')->name('topics.status');

        Route::get('resources', [ResourceController::class, 'index'])->name('resources.index');
        Route::post('resources', [ResourceController::class, 'store'])->name('resources.store');
        Route::get('resources/create', [ResourceController::class, 'create'])->name('resources.create');
        Route::get('resources/{resource}/edit', [ResourceController::class, 'edit'])->whereNumber('resource')->name('resources.edit');
        Route::put('resources/{resource}', [ResourceController::class, 'update'])->whereNumber('resource')->name('resources.update');
        Route::put('resources/{resource}/links', [ResourceController::class, 'links'])->whereNumber('resource')->name('resources.links');
        Route::patch('resources/{resource}/status', [ResourceController::class, 'status'])->whereNumber('resource')->name('resources.status');
        Route::get('videos', [VideoController::class, 'index'])->name('videos.index');
        Route::get('videos/create', [VideoController::class, 'create'])->name('videos.create');
        Route::get('videos/{video}/edit', [VideoController::class, 'edit'])->whereNumber('video')->name('videos.edit');
        Route::post('videos', [VideoController::class, 'store'])->name('videos.store');
        Route::put('videos/{video}', [VideoController::class, 'update'])->whereNumber('video')->name('videos.update');
        Route::patch('videos/{video}/status', [VideoController::class, 'status'])->whereNumber('video')->name('videos.status');
        Route::patch('videos/{video}/position', [VideoController::class, 'reorder'])->whereNumber('video')->name('videos.reorder');
    });

Route::prefix('access/{type}/{target}')->name('access.')
    ->where(['type' => implode('|', [...array_keys(\App\Support\AccessTarget::MODELS), \App\Support\AccessTarget::FEATURE]), 'target' => '[0-9]+'])
    ->middleware([SetLocale::class, 'cache.headers:private;no_store'])
    ->controller(AccessPageController::class)->group(function (): void {
        Route::get('/', 'show')->name('requirements');
        Route::get('unlock', 'unlock')->middleware('auth:web')->name('unlock.show');
        Route::post('unlock', 'spend')->middleware('auth:web')->name('unlock.store');
    });

Route::middleware([SetLocale::class, RecordLearnerVisit::class])->name('curriculum.')->group(function (): void {
    Route::get('/exams/{exam}/files/{document}/{disposition}', ExamFileController::class)
        ->whereNumber('exam')->whereIn('document', ['paper', 'correction'])
        ->whereIn('disposition', ['inline', 'attachment'])->name('exams.file');
    Route::get('/search', [DiscoveryController::class, 'search'])->name('search');
    Route::get('/topics', [DiscoveryController::class, 'topics'])->name('topics.index');
    Route::get('/topics/{topic}', [DiscoveryController::class, 'topic'])->whereNumber('topic')->name('topics.show');
    Route::get('/', [CurriculumController::class, 'home'])->name('home');
    Route::redirect('/home', '/', 301);
    Route::get('/paths', [CurriculumController::class, 'paths'])->name('paths');
    Route::get('/units', [CurriculumController::class, 'units'])->name('units');
    Route::get('/paths/{level}', [CurriculumController::class, 'level'])->whereNumber('level')->name('level');
    Route::get('/paths/{level}/units', [CurriculumController::class, 'units'])->whereNumber('level')->name('level.units');
    Route::get('/paths/{level}/units/{unit}', [CurriculumController::class, 'unit'])
        ->whereNumber(['level', 'unit'])->name('unit');
    Route::get('/paths/{level}/units/{unit}/categories/{category}', [CurriculumController::class, 'category'])
        ->whereNumber(['level', 'unit', 'category'])->name('category');
    Route::get('/paths/{level}/units/{unit}/categories/{category}/lessons/{lesson}', [CurriculumController::class, 'lesson'])
        ->whereNumber(['level', 'unit', 'category', 'lesson'])->name('lesson');
    Route::post('/paths/{level}/units/{unit}/categories/{category}/lessons/{lesson}/complete', [CurriculumController::class, 'completeLesson'])
        ->middleware('auth:web')->whereNumber(['level', 'unit', 'category', 'lesson'])->name('lesson.complete');
});

Route::middleware([SetLocale::class, RecordLearnerVisit::class])->name('library.')->scopeBindings()->group(function (): void {
    Route::get('/videos', [VideoPageController::class, 'index'])->name('videos.index');
    Route::get('/videos/{video}', [VideoPageController::class, 'show'])->whereNumber('video')->name('videos.show');
    Route::post('/videos/{video}/playback', [VideoPageController::class, 'playback'])
        ->whereNumber('video')->name('videos.playback');
    Route::get('/resources', [ResourcePageController::class, 'index'])->name('resources.index');
    Route::get('/resources/{resource}', [ResourcePageController::class, 'show'])->whereNumber('resource')->name('resources.show');
    Route::get('/resources/{resource}/document', [ResourcePageController::class, 'document'])->whereNumber('resource')->name('resources.document');
    Route::get('/resources/{resource}/file/{disposition}', [ResourceFileController::class, 'show'])
        ->whereNumber('resource')->whereIn('disposition', ['inline', 'attachment'])->name('resources.file');
    Route::get('/lessons/{lesson}/resources/{resource}/document', [ResourcePageController::class, 'lessonDocument'])
        ->whereNumber(['lesson', 'resource'])->name('lessons.resources.document');
    Route::get('/lessons/{lesson}/resources/{resource}/file/{disposition}', [ResourceFileController::class, 'lesson'])
        ->whereNumber(['lesson', 'resource'])->whereIn('disposition', ['inline', 'attachment'])->name('lessons.resources.file');
});

Route::middleware([SetLocale::class, RecordLearnerVisit::class])->group(function (): void {
    Route::get('exercises', [ExerciseAttemptController::class, 'catalogue'])->name('curriculum.exercises.index');
    Route::get('exercises/{exercise}', [ExerciseAttemptController::class, 'instructions'])
        ->whereNumber('exercise')->name('curriculum.exercises.instructions');
    Route::get('exams', [ExamPageController::class, 'index'])->name('curriculum.exams.index');
    Route::get('exams/{exam}/document/{document}', [ExamPageController::class, 'document'])
        ->whereNumber('exam')->whereIn('document', ['paper', 'correction'])->name('curriculum.exams.document');
    Route::get('exams/{exam}', [ExamPageController::class, 'show'])
        ->whereNumber('exam')->name('curriculum.exams.instructions');
});

Route::prefix('exercises/{exercise}/attempts')->name('curriculum.exercises.attempts.')
    ->middleware([SetLocale::class, 'auth:web', 'cache.headers:private;no_store'])
    ->scopeBindings()
    ->where(['exercise' => '[0-9]+'])
    ->group(function (): void {
        Route::get('/', [ExerciseAttemptController::class, 'index'])->name('index');
        Route::post('/', [ExerciseAttemptController::class, 'store'])->name('store');
        Route::get('{attempt}', [ExerciseAttemptController::class, 'show'])->whereNumber('attempt')->name('show');
        Route::post('{attempt}/submit', [ExerciseAttemptController::class, 'submit'])->whereNumber('attempt')->name('submit');
        Route::post('{attempt}/retry', [ExerciseAttemptController::class, 'retry'])->whereNumber('attempt')->name('retry');
    });

Route::prefix('exams/{exam}/attempts')->name('curriculum.exams.attempts.')
    ->middleware([SetLocale::class, 'auth:web', 'cache.headers:private;no_store'])
    ->scopeBindings()
    ->where(['exam' => '[0-9]+'])
    ->group(function (): void {
        Route::post('/', [ExamAttemptController::class, 'store'])->name('store');
        Route::get('/', [ExamAttemptController::class, 'index'])->name('index');
        Route::get('{attempt}', [ExamAttemptController::class, 'show'])->whereNumber('attempt')->name('show');
        Route::post('{attempt}/submit', [ExamAttemptController::class, 'submit'])->whereNumber('attempt')->name('submit');
    });

// Keep existing learning bookmarks working while named routes generate the shorter URLs.
Route::get('/learn/{section}/{path?}', function (\Illuminate\Http\Request $request, string $section, ?string $path = null) {
    $destination = '/'.$section.($path === null ? '' : '/'.$path);
    $query = $request->getQueryString();

    return redirect()->to($destination.($query === null ? '' : '?'.$query), 301);
})->whereIn('section', ['access', 'exams', 'exercises', 'lessons', 'paths', 'resources', 'search', 'topics', 'units', 'videos'])
    ->where('path', '.*');

// Keep the fixture catalogue separate from live routes.
if (app()->environment(['local', 'testing'])) {
    Route::withoutMiddleware(StartSession::class)
        ->middleware([StartPreviewSession::class, SetLocale::class])
        ->group(function (): void {
            Route::post('/prototype/locale', LocaleController::class)->name('locale.update');
            Route::view('/prototype/tokens', 'prototype.fixtures.tokens')->name('prototype.tokens');
            Route::get('/prototype/primitives', [PrototypeController::class, 'primitives'])->name('prototype.primitives');

            foreach (Prototype::screens() as $screen) {
                Route::get($screen['path'], [PrototypeController::class, 'show'])
                    ->defaults('screen', $screen['id'])
                    ->name(Prototype::routeName($screen['id']));
            }
        });
}
