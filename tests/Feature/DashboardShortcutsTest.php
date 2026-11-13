<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\Level;
use App\Models\Resource;
use App\Models\Role;
use App\Models\StoredFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardShortcutsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shortcuts_list_authorized_content_from_all_visible_selected_levels(): void
    {
        $student = User::factory()->create();
        $student->roles()->attach(Role::where('key', Role::STUDENT)->sole());
        $exams = route('curriculum.exams.index', ['selected' => 1]);
        $resources = route('library.resources.index', ['selected' => 1]);
        $this->actingAs($student)->get(route('account.home'))->assertOk()->assertSee($exams)->assertSee($resources);
        $this->get($exams)->assertOk()->assertSee('No exams available for your learning paths');
        $this->get($resources)->assertOk()->assertSee('No resources available for your learning paths');

        foreach (['First', 'Second', 'Unselected', 'Archived'] as $path) {
            $level = Level::forceCreate(['title' => "$path path", 'status' => $path === 'Archived' ? 'archived' : 'published']);
            $unit = $level->units()->forceCreate(['title' => "$path unit", 'status' => 'published']);
            $unit->exams()->forceCreate(['title' => "$path exam", 'type' => Exam::INTERACTIVE, 'status' => 'published']);
            $this->resource("$path worksheet")->levels()->attach($level);
            if ($path !== 'Unselected') {
                $student->selectedLevels()->attach($level);
            }
            if ($path === 'First') {
                $unit->exams()->forceCreate([
                    'title' => 'Expired exam', 'type' => Exam::INTERACTIVE, 'status' => 'published', 'available_until' => now()->subDay(),
                ]);
                $this->resource('Locked worksheet', 'premium')->levels()->attach($level);
            }
        }
        $this->resource('Standalone worksheet');

        $this->get($exams)->assertOk()->assertSee('First exam')->assertSee('Second exam')
            ->assertDontSee('Unselected exam')->assertDontSee('Archived exam')->assertDontSee('Expired exam');
        $this->get($resources)->assertOk()->assertSee('First worksheet')->assertSee('Second worksheet')
            ->assertDontSee('Unselected worksheet')->assertDontSee('Archived worksheet')
            ->assertDontSee('Locked worksheet')->assertDontSee('Standalone worksheet');
        $this->get(route('library.resources.index'))->assertOk()->assertSee('Standalone worksheet');

        foreach ([$exams, $resources] as $shortcut) {
            $this->get($shortcut.'&search=missing')->assertOk()->assertSee('No matching content')
                ->assertSee('name="selected" value="1"', false)->assertSee('href="'.$shortcut.'"', false);
            $this->get($shortcut.'&type=invalid')->assertRedirect($shortcut)->assertSessionHasErrors('type');
        }
    }

    public function test_selected_catalogues_require_a_student_account(): void
    {
        $guest = User::factory()->create();

        foreach (['curriculum.exams.index', 'library.resources.index'] as $catalogue) {
            $url = route($catalogue, ['selected' => 1]);
            $this->get($url)->assertForbidden();
            $this->actingAs($guest)->get($url)->assertForbidden();
            auth()->logout();
        }
    }

    private function resource(string $title, string $access = 'public'): Resource
    {
        $file = StoredFile::create([
            'disk' => StoredFile::DISK,
            'path' => 'resources/'.str()->slug($title).'.pdf',
            'mime_type' => 'application/pdf',
            'size' => 9,
        ]);

        return Resource::forceCreate([
            'title' => $title, 'type' => 'worksheet', 'access' => $access,
            'status' => 'published', 'stored_file_id' => $file->id,
        ]);
    }
}
