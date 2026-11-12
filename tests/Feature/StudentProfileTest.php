<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_update_only_their_own_profile_fields(): void
    {
        $student = $this->student();
        $other = $this->student();
        $url = route('account.profile.update');
        $password = $student->password;

        $this->actingAs($student)->putJson($url, [
            'id' => $other->id,
            'user_id' => $other->id,
            'name' => 'Updated Student',
            'email' => 'updated@example.test',
            'city' => 'Rabat',
            'password' => 'forged-password',
            'roles' => [Role::ADMINISTRATOR],
        ])->assertNoContent();

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'name' => 'Updated Student',
            'email' => 'updated@example.test',
            'city' => 'Rabat',
            'email_verified_at' => null,
            'password' => $password,
        ]);
        $this->assertDatabaseHas('users', $other->only(['id', 'name', 'email', 'city']));
        $this->assertSame([Role::STUDENT], $student->roles()->pluck('key')->all());

        $this->putJson($url, ['name' => 'Updated Student', 'email' => 'updated@example.test', 'city' => null])
            ->assertNoContent();
        $this->assertNull($student->fresh()->city);
    }

    public function test_profile_update_requires_student_access_and_valid_identity(): void
    {
        $student = $this->student();
        $other = User::factory()->create();
        $url = route('account.profile.update');
        $this->putJson($url)->assertUnauthorized();
        $this->actingAs($other)->putJson($url)->assertForbidden();

        $this->actingAs($student)->putJson($url, ['name' => [], 'email' => $other->email, 'city' => str_repeat('a', 256)])
            ->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'city']);
        $this->assertDatabaseHas('users', $student->only(['id', 'name', 'email', 'city']));
    }

    public function test_student_can_save_and_clear_learning_preferences_on_their_account(): void
    {
        $student = $this->student();
        $other = $this->student();
        $url = route('account.preferences.update');

        $this->actingAs($student)->putJson($url, [
            'locale' => 'ar',
            'daily_study_goal_minutes' => '30',
            'learning_focus' => 'Exam preparation',
            'user_id' => $other->id,
        ])->assertNoContent();

        $this->assertSame([
            'locale' => 'ar',
            'daily_study_goal_minutes' => 30,
            'learning_focus' => 'Exam preparation',
        ], $student->fresh()->only(['locale', 'daily_study_goal_minutes', 'learning_focus']));
        $this->assertNull($other->fresh()->locale);

        $this->putJson($url, ['locale' => 'fr', 'daily_study_goal_minutes' => 15, 'learning_focus' => null])
            ->assertNoContent();
        $this->assertDatabaseHas('users', ['id' => $student->id, 'locale' => 'fr', 'learning_focus' => null]);
    }

    public function test_preferences_require_student_access_and_supported_values(): void
    {
        $student = $this->student();
        $url = route('account.preferences.update');
        $this->putJson($url)->assertUnauthorized();
        $this->actingAs(User::factory()->create())->putJson($url)->assertForbidden();

        $this->actingAs($student)->putJson($url, [
            'locale' => 'de',
            'daily_study_goal_minutes' => 60,
            'learning_focus' => ['Exam preparation'],
        ])->assertUnprocessable()->assertJsonValidationErrors(['locale', 'daily_study_goal_minutes', 'learning_focus']);
        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'locale' => null,
            'daily_study_goal_minutes' => null,
            'learning_focus' => null,
        ]);
    }

    private function student(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('key', Role::STUDENT)->sole());

        return $user;
    }
}
