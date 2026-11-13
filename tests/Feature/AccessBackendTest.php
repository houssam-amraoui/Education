<?php

namespace Tests\Feature;

use App\Actions\Rewards\UnlockWithPoints;
use App\Models\AccessRule;
use App\Models\Entitlement;
use App\Models\LessonProgress;
use App\Models\Level;
use App\Models\PointCost;
use App\Models\PointTransaction;
use App\Models\Resource;
use App\Models\Role;
use App\Models\StoredFile;
use App\Models\User;
use App\Models\Video;
use App\Support\RewardTarget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AccessBackendTest extends TestCase
{
    use RefreshDatabase;

    public function test_management_validates_targets_and_replaces_obsolete_requirement_fields(): void
    {
        [$level] = $this->curriculum();
        $store = route('admin.access-rules.store');
        $input = ['target_type' => 'ask_teacher', 'target_id' => 0, 'requirement' => 'level', 'required_level_id' => $level->id];

        $this->postJson($store, $input)->assertUnauthorized();
        $this->actingAs($this->user())->postJson($store, $input)->assertForbidden();
        $this->actingAs($this->user(Role::TEACHER));
        $this->postJson($store, array_replace($input, ['target_id' => 1]))->assertJsonValidationErrors('target_id');
        $this->postJson($store, array_replace($input, ['target_type' => 'video', 'target_id' => 999]))->assertJsonValidationErrors('target_id');
        $this->postJson($store, array_replace($input, ['required_level_id' => null]))->assertJsonValidationErrors('required_level_id');
        $id = $this->postJson($store, $input)->assertCreated()->json('id');
        $this->postJson($store, $input)->assertJsonValidationErrors('requirement');
        $this->putJson(route('admin.access-rules.update', $id), [
            'target_type' => 'ask_teacher', 'target_id' => 0, 'requirement' => 'public',
        ])->assertOk()->assertJsonPath('required_level_id', null);
    }

    public function test_lesson_access_combines_selected_level_and_completed_prerequisite(): void
    {
        [$level, $unit, $category, $lesson] = $this->curriculum();
        $prerequisite = $category->lessons()->forceCreate(['title' => 'Preparation', 'status' => 'published']);
        $this->rule('level', $level->id, 'level', ['required_level_id' => $level->id]);
        $this->rule('lesson', $lesson->id, 'prerequisite', ['required_lesson_id' => $prerequisite->id]);
        $this->rule('lesson', $lesson->id, 'public');
        $url = route('curriculum.lesson', [$level, $unit, $category, $lesson]);
        $student = $this->user();

        $this->get($url)->assertNotFound();
        $this->actingAs($this->user(Role::TEACHER))->get($url)->assertNotFound();
        $this->actingAs($student)->get($url)->assertNotFound();
        $student->selectedLevels()->attach($level);
        $this->get($url)->assertNotFound();
        LessonProgress::forceCreate(['student_id' => $student->id, 'lesson_id' => $prerequisite->id, 'completed_at' => now()]);
        $this->get($url)->assertOk()->assertSee($lesson->title);
        $prerequisite->forceFill(['status' => 'archived'])->save();
        $this->get($url)->assertNotFound();
    }

    public function test_protected_files_check_both_resource_and_lesson_and_premium_is_validity_scoped(): void
    {
        [, , , $lesson] = $this->curriculum();
        $file = StoredFile::create(['disk' => StoredFile::DISK, 'path' => 'guide.pdf', 'mime_type' => 'application/pdf', 'size' => 9]);
        Storage::fake(StoredFile::DISK)->put($file->path, '%PDF-1.7');
        $resource = Resource::forceCreate([
            'title' => 'Guide', 'type' => 'pdf', 'status' => 'published', 'access' => 'premium', 'stored_file_id' => $file->id,
        ]);
        $lesson->resources()->attach($resource);
        $this->rule('lesson', $lesson->id, 'registered');
        $direct = route('library.resources.file', [$resource, 'inline']);
        $nested = route('library.lessons.resources.file', [$lesson, $resource, 'attachment']);
        $student = $this->user();
        $grant = Entitlement::create([
            'student_id' => $student->id, 'target_type' => 'resource', 'target_id' => $resource->id, 'source' => 'premium',
            'granted_at' => now()->subMinute(), 'expires_at' => now()->addMinute(),
        ]);

        $this->get($direct)->assertNotFound();
        $this->actingAs($this->user())->get($direct)->assertNotFound();
        $this->actingAs($student)->get($direct)->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get($nested)->assertDownload('guide.pdf');
        $this->rule('lesson', $lesson->id, 'premium');
        $this->get($nested)->assertNotFound();
        $this->get($direct)->assertOk();
        $grant->update(['revoked_at' => now()]);
        $this->get($direct)->assertNotFound();
    }

    public function test_video_rules_filter_the_catalogue_and_watch_route(): void
    {
        [$level] = $this->curriculum();
        $video = Video::forceCreate([
            'title' => 'Members video', 'provider' => 'youtube', 'provider_id' => 'abcdefghijk', 'status' => 'published', 'access' => 'level',
        ]);
        $video->levels()->attach($level);
        $catalogue = route('library.videos.index');
        $watch = route('library.videos.show', $video);
        $student = $this->user();
        $student->selectedLevels()->attach($level);

        $this->actingAs($student)->get($watch)->assertNotFound();
        $this->get($catalogue)->assertDontSee($video->title);
        $this->rule('video', $video->id, 'level', ['required_level_id' => $level->id]);
        $this->get($watch)->assertOk();
        $this->get($catalogue)->assertSee($video->title);
        $level->forceFill(['status' => 'archived'])->save();
        $this->get($watch)->assertNotFound();
    }

    public static function assessments(): array
    {
        return [['exercise', 'exercises'], ['exam', 'exams']];
    }

    #[DataProvider('assessments')]
    public function test_assessment_requires_current_access_at_start_submission_and_review(string $type, string $plural): void
    {
        [$level, $unit] = $this->curriculum();
        $assessment = $unit->{$plural}()->forceCreate(['title' => 'Practice', 'status' => 'published', 'instructions' => 'Answer the question.']
            + ($type === 'exam' ? ['type' => 'interactive'] : []));
        $assessment->questions()->forceCreate([
            'position' => 1, 'type' => 'true_false', 'prompt' => 'Statement', 'content' => [], 'answer' => ['value' => true], 'points' => 1,
        ]);
        $this->rule($type, $assessment->id, 'level', ['required_level_id' => $level->id]);
        $prefix = "curriculum.{$plural}.attempts.";
        $student = $this->user();
        $this->actingAs($student)->postJson(route($prefix.'store', $assessment))->assertNotFound();
        $student->selectedLevels()->attach($level);
        $start = $this->postJson(route($prefix.'store', $assessment))->assertCreated()->json();
        $parameters = [$assessment, $start['id']];
        $answers = ['answers' => [$start['questions'][0]['id'] => true]];
        $student->selectedLevels()->detach($level);
        $this->postJson(route($prefix.'submit', $parameters), $answers)->assertNotFound();
        $student->selectedLevels()->attach($level);
        $this->postJson(route($prefix.'submit', $parameters), $answers)->assertOk();
        $this->getJson(route($prefix.'show', $parameters))->assertOk();
        $student->selectedLevels()->detach($level);
        $this->getJson(route($prefix.'show', $parameters))->assertNotFound();
    }

    public function test_ask_teacher_unlock_grants_only_the_purchaser_access(): void
    {
        $this->rule('ask_teacher', 0, 'points');
        $student = $this->user();
        PointCost::create(['target_type' => RewardTarget::AskTeacher, 'target_id' => 0, 'enabled' => true, 'points' => 5]);
        PointTransaction::create(['student_id' => $student->id, 'source_key' => 'earned', 'amount' => 5, 'recorded_at' => now()]);
        $input = ['subject' => 'A question', 'message' => 'Can you explain this?'];
        $url = route('account.messages.store');

        $this->actingAs($student)->postJson($url, $input)->assertNotFound();
        $unlock = app(UnlockWithPoints::class)->handle($student, RewardTarget::AskTeacher, 0);
        $this->assertDatabaseHas('entitlements', ['student_id' => $student->id, 'source' => 'points', 'point_unlock_id' => $unlock->id]);
        $this->post($url, $input)->assertStatus(303);
        $this->actingAs($this->user())->postJson($url, $input)->assertNotFound();
        $this->assertDatabaseCount('conversations', 1);
    }

    private function curriculum(): array
    {
        $level = Level::forceCreate(['title' => 'Path', 'status' => 'published']);
        $unit = $level->units()->forceCreate(['title' => 'Unit', 'status' => 'published']);
        $category = $unit->categories()->forceCreate(['title' => 'Grammar', 'status' => 'published']);
        $lesson = $category->lessons()->forceCreate(['title' => 'Restricted lesson', 'status' => 'published']);

        return [$level, $unit, $category, $lesson];
    }

    private function rule(string $type, int $id, string $requirement, array $conditions = []): AccessRule
    {
        return AccessRule::create(['target_type' => $type, 'target_id' => $id, 'requirement' => $requirement] + $conditions);
    }

    private function user(string $role = Role::STUDENT): User
    {
        return User::factory()->hasAttached(Role::where('key', $role)->sole())->create();
    }
}
