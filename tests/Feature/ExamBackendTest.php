<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\Level;
use App\Models\Role;
use App\Models\StoredFile;
use App\Models\Unit;
use App\Models\User;
use App\Queries\VisibleExams;
use App\Support\CurriculumStatus;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ExamBackendTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_exam_requires_management_access_and_two_valid_pdfs(): void
    {
        $disk = Storage::fake(StoredFile::DISK);
        $store = route('admin.curriculum.exams.store');
        $this->postJson($store)->assertUnauthorized();
        $this->actingAs(User::factory()->create())->postJson($store)->assertForbidden();
        $this->signInAsTeacher();

        $data = ['unit_id' => $this->unit()->id, 'type' => 'document', 'title' => 'Sample paper'];
        $this->postJson($store, $data + ['exam_file' => UploadedFile::fake()->createWithContent('exam.txt', 'Text')])
            ->assertUnprocessable()->assertJsonValidationErrors(['exam_file', 'correction_file']);
        $this->postJson($store, $data + [
            'available_from' => '2026-11-12T12:00:00+01:00',
            'available_until' => '2026-11-12T11:00:00+00:00',
            'exam_file' => $this->pdf(),
            'correction_file' => $this->pdf(),
        ])->assertUnprocessable()->assertJsonValidationErrors('available_until');
        $this->assertSame([], $disk->allFiles());

        $id = $this->postJson($store, $data + [
            'available_from' => '2026-11-12T12:00:00+01:00',
            'exam_file' => $this->pdf(),
            'correction_file' => $this->pdf(),
            'status' => 'published',
        ])->assertCreated()->assertJsonPath('status', 'draft')->json('id');
        $exam = Exam::findOrFail($id);
        $paper = $exam->examFile;
        $correction = $exam->correctionFile;
        $this->assertSame('2026-11-12 11:00:00', $exam->available_from->toDateTimeString());
        $this->assertNotSame($paper->id, $correction->id);
        $disk->assertExists([$paper->path, $correction->path]);

        Exam::updating(function (Exam $exam): void {
            if ($exam->title === 'Reject save') {
                throw ValidationException::withMessages(['title' => 'Rejected by test.']);
            }
        });
        $update = route('admin.curriculum.exams.update', $exam);
        $this->putJson($update, ['title' => 'Reject save'] + $data + [
            'exam_file' => $this->pdf(),
            'correction_file' => $this->pdf(),
        ])->assertUnprocessable();
        $this->assertCount(2, $disk->allFiles());
        $this->assertDatabaseCount('stored_files', 2);

        $this->putJson($update, $data + ['exam_file' => $this->pdf(), 'correction_file_id' => $paper->id])
            ->assertOk()->assertJsonPath('correction_file_id', $correction->id);
        $disk->assertMissing($paper->path);
        $disk->assertExists($exam->refresh()->examFile->path);
        $this->assertDatabaseCount('stored_files', 2);
    }

    public function test_interactive_exam_accepts_timed_and_untimed_configuration(): void
    {
        $this->signInAsTeacher();
        $store = route('admin.curriculum.exams.store');
        $data = ['unit_id' => $this->unit()->id, 'type' => 'interactive', 'title' => 'Unit test exam'];
        $this->postJson($store, $data)->assertUnprocessable()
            ->assertJsonValidationErrors(['instructions', 'duration_minutes']);

        $data['instructions'] = 'Answer each question.';
        $this->postJson($store, $data + ['duration_minutes' => 0, 'exam_file' => $this->pdf()])
            ->assertUnprocessable()->assertJsonValidationErrors(['duration_minutes', 'exam_file']);
        $id = $this->postJson($store, $data + ['duration_minutes' => 30])
            ->assertCreated()->assertJsonPath('duration_minutes', 30)->json('id');

        $update = route('admin.curriculum.exams.update', $id);
        $this->putJson($update, ['type' => 'document'] + $data + ['duration_minutes' => null])
            ->assertUnprocessable()->assertJsonValidationErrors('type');
        $this->putJson($update, $data + ['duration_minutes' => null])
            ->assertOk()->assertJsonPath('duration_minutes', null);
        $this->assertDatabaseHas('exams', ['id' => $id, 'type' => 'interactive', 'duration_minutes' => null]);
        $this->assertDatabaseCount('stored_files', 0);
    }

    public function test_lifecycle_and_reordering_are_scoped_to_the_bound_unit(): void
    {
        $this->signInAsTeacher();
        $unit = $this->unit();
        $first = $this->interactiveExam($unit);
        $second = $this->interactiveExam($unit);
        $other = $this->interactiveExam($this->unit());
        $status = route('admin.curriculum.exams.status', [$unit, $first]);
        $reorder = route('admin.curriculum.exams.reorder', [$unit, $second]);

        $this->patchJson(route('admin.curriculum.exams.reorder', [$unit, $other]), ['position' => 1])->assertNotFound();
        $this->patchJson($reorder, ['position' => 3])->assertUnprocessable()->assertJsonValidationErrors('position');
        $this->patchJson($reorder, ['position' => 1])->assertNoContent();
        $this->assertSame([$second->id, $first->id], $unit->exams()->ordered()->pluck('id')->all());
        $this->assertNull($other->refresh()->position);

        foreach (['published', 'draft', 'archived'] as $state) {
            $this->patchJson($status, ['status' => $state])->assertNoContent();
            $this->assertSame($state, $first->refresh()->status->value);
        }
        $this->patchJson($status, ['status' => 'published'])->assertUnprocessable()->assertJsonValidationErrors('status');

        $this->actingAs(User::factory()->create());
        $this->patchJson($reorder, ['position' => 1])->assertForbidden();
        $this->patchJson($status, ['status' => 'published'])->assertForbidden();
    }

    public function test_catalogue_and_detail_apply_availability_and_ancestry_before_pagination(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 12)->setTime(12, 0));
        $unit = $this->unit();
        $other = $this->unit();
        $hidden = $this->unit();
        $hidden->level->forceFill(['status' => CurriculumStatus::Draft])->save();
        $visible = $this->interactiveExam($unit, ['available_from' => now(), 'available_until' => now()->addMinute()]);
        $this->interactiveExam($unit, ['available_from' => now()->addSecond()]);
        $expired = $this->interactiveExam($unit, ['available_until' => now()]);
        $this->interactiveExam($unit, ['access' => 'registered']);
        $this->interactiveExam($unit, ['status' => CurriculumStatus::Draft]);
        $this->interactiveExam($hidden);
        $this->interactiveExam($other);

        $catalogue = app(VisibleExams::class);
        $this->assertSame(2, $catalogue->paginate(perPage: 1)->total());
        $this->assertSame([$visible->id], $catalogue->paginate(levelId: $unit->level_id)->pluck('id')->all());
        $this->assertTrue($catalogue->findOrFail($visible->id)->is($visible));
        $this->assertTrue(Gate::allows('view', $visible));
        $this->assertSame(404, Gate::inspect('view', $expired)->status());
        $this->assertSame(3, $catalogue->paginate(User::factory()->create())->total());

        $this->expectException(ModelNotFoundException::class);
        $catalogue->findOrFail($expired->id);
    }

    public function test_pdf_delivery_requires_visibility_and_uses_the_bound_exam_files(): void
    {
        $disk = Storage::fake(StoredFile::DISK);
        $this->signInAsTeacher();
        $unit = $this->unit();
        $id = $this->postJson(route('admin.curriculum.exams.store'), [
            'unit_id' => $unit->id,
            'type' => 'document',
            'title' => 'Sample',
            'access' => 'registered',
            'exam_file' => $this->pdf(),
            'correction_file' => $this->pdf(),
        ])->assertCreated()->json('id');

        $paper = route('curriculum.exams.file', [$id, 'paper', 'inline']);
        $correction = route('curriculum.exams.file', [$id, 'correction', 'attachment']);
        $this->get($paper)->assertNotFound();
        $this->patchJson(route('admin.curriculum.exams.status', [$unit, $id]), ['status' => 'published'])->assertNoContent();
        auth('web')->logout();
        $this->get($paper)->assertNotFound();
        $this->get($correction)->assertNotFound();
        $this->actingAs(User::factory()->create());

        $exam = Exam::findOrFail($id);
        $response = $this->get($paper)->assertOk()->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'inline; filename=sample-paper.pdf');
        $this->assertSame($disk->path($exam->examFile->path), $response->baseResponse->getFile()->getPathname());
        $response = $this->get($correction)->assertOk()->assertDownload('sample-correction.pdf');
        $this->assertSame($disk->path($exam->correctionFile->path), $response->baseResponse->getFile()->getPathname());
        $disk->delete($exam->correctionFile->path);
        $this->get($correction)->assertNotFound();
        $unit->forceFill(['status' => CurriculumStatus::Draft])->save();
        $this->get($paper)->assertNotFound();
    }

    public function test_database_rejects_duplicate_exam_positions_within_a_unit(): void
    {
        $unit = $this->unit();
        $this->interactiveExam($unit, ['position' => 1]);
        $this->interactiveExam($this->unit(), ['position' => 1]);

        $this->expectException(QueryException::class);
        $this->interactiveExam($unit, ['position' => 1]);
    }

    private function interactiveExam(Unit $unit, array $attributes = []): Exam
    {
        $exam = $unit->exams()->make([
            'type' => Exam::INTERACTIVE,
            'title' => 'Unit exam',
            'instructions' => 'Answer the questions.',
        ]);
        $exam->forceFill($attributes + ['status' => CurriculumStatus::Published])->save();

        return $exam;
    }

    private function unit(): Unit
    {
        $level = Level::create(['title' => 'Baccalaureate']);
        $level->forceFill(['status' => CurriculumStatus::Published])->save();
        $unit = $level->units()->create(['title' => 'Unit one']);
        $unit->forceFill(['status' => CurriculumStatus::Published])->save();

        return $unit;
    }

    private function signInAsTeacher(): void
    {
        $teacher = User::factory()->create();
        $teacher->roles()->attach(Role::where('key', Role::TEACHER)->sole());
        $this->actingAs($teacher);
    }

    private function pdf(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('paper.pdf', "%PDF-1.4\n%%EOF\n");
    }
}
