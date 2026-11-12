<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ManagedUsersTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $key): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('key', $key)->sole());

        return $user;
    }

    public function test_listing_requires_user_management_and_paginates_search_results(): void
    {
        $this->getJson(route('admin.accounts.users.index'))->assertUnauthorized();
        foreach ([Role::STUDENT, Role::TEACHER] as $key) {
            $this->actingAs($this->userWithRole($key))
                ->getJson(route('admin.accounts.users.index'))->assertForbidden();
        }

        User::factory()->count(22)->create(['name' => 'Searchable Learner']);
        $this->actingAs($this->userWithRole(Role::ADMINISTRATOR))
            ->getJson(route('admin.accounts.users.index', ['search' => 'searchable', 'page' => 2]))
            ->assertOk()->assertJsonPath('total', 22)->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Searchable Learner')
            ->assertJsonMissingPath('data.0.password');
    }

    public function test_account_writes_validate_identity_and_leave_role_membership_intact(): void
    {
        $administrator = $this->userWithRole(Role::ADMINISTRATOR);
        $identity = [
            'name' => 'Amina Bennani',
            'email' => 'amina@example.test',
            'password' => 'sample-password',
            'password_confirmation' => 'sample-password',
            'role_ids' => [$administrator->roles()->sole()->id],
        ];

        $this->actingAs($this->userWithRole(Role::TEACHER))
            ->postJson(route('admin.accounts.users.store'), $identity)->assertForbidden();
        $id = $this->actingAs($administrator)
            ->postJson(route('admin.accounts.users.store'), $identity)->assertCreated()->json('id');
        $user = User::findOrFail($id);
        $this->assertSame(['student'], $user->roles()->pluck('key')->all());
        $this->assertTrue(Hash::check('sample-password', $user->password));

        $user->forceFill(['email_verified_at' => now()])->save();
        $url = route('admin.accounts.users.update', $user);
        $this->putJson($url, ['name' => 'Amina', 'email' => $administrator->email])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->putJson($url, ['name' => 'Amina', 'email' => 'updated@example.test', 'role_ids' => []])
            ->assertOk()->assertJsonPath('email', 'updated@example.test');
        $this->assertNull($user->refresh()->email_verified_at);
        $this->assertTrue(Hash::check('sample-password', $user->password));
        $this->assertSame(['student'], $user->roles()->pluck('key')->all());
    }
}
