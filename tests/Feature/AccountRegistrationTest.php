<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_only_a_student_with_a_hashed_password(): void
    {
        $this->postJson(route('account.register'), [
            'name' => 'Amina Bennani',
            'email' => 'amina@example.test',
            'password' => 'sample-password',
            'password_confirmation' => 'sample-password',
            'role' => 'administrator',
            'role_ids' => [2],
            'abilities' => ['admin.enter'],
            'email_verified_at' => now()->toISOString(),
        ])->assertCreated()->assertJsonMissingPath('password');

        $user = User::sole();
        $this->assertSame(['student'], $user->roles()->pluck('key')->all());
        $this->assertTrue(Hash::check('sample-password', $user->password));
        $this->assertNull($user->email_verified_at);
    }

    public function test_registration_rejects_duplicate_email_and_invalid_password(): void
    {
        User::factory()->create(['email' => 'taken@example.test']);

        $this->postJson(route('account.register'), [
            'name' => 'Amina Bennani',
            'email' => 'taken@example.test',
            'password' => 'short',
        ])->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('role_user', 0);
    }
}
