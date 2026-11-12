<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_and_logout_rotate_session_and_csrf_token(): void
    {
        $user = User::factory()->create();
        $this->withSession(['_token' => 'guest-token', 'sample' => 'session value']);
        $guestSession = session()->getId();

        $this->withCookie(config('session.cookie'), $guestSession)
            ->postJson(route('account.login'), ['email' => $user->email, 'password' => 'password'])
            ->assertNoContent()->assertSessionHas('sample', 'session value');
        $this->assertAuthenticatedAs($user, 'web');
        $this->assertNotSame($guestSession, session()->getId());
        $this->assertNotSame('guest-token', session()->token());

        $authenticatedSession = session()->getId();
        $authenticatedToken = session()->token();
        $this->withCookie(config('session.cookie'), $authenticatedSession)
            ->postJson(route('account.logout'))->assertNoContent()->assertSessionMissing('sample');
        $this->assertGuest('web');
        $this->assertNotSame($authenticatedSession, session()->getId());
        $this->assertNotSame($authenticatedToken, session()->token());
    }

    public function test_repeated_login_failures_are_throttled_until_the_window_expires(): void
    {
        $user = User::factory()->create();
        $credentials = ['email' => $user->email, 'password' => 'incorrect'];

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson(route('account.login'), $credentials)
                ->assertUnprocessable()->assertJsonValidationErrors('email');
        }

        $credentials['password'] = 'password';
        $this->postJson(route('account.login'), $credentials)->assertTooManyRequests()->assertHeader('Retry-After');
        $this->assertGuest('web');

        $this->travel(61)->seconds();
        $this->postJson(route('account.login'), $credentials)->assertNoContent();
        $this->assertAuthenticatedAs($user, 'web');
    }
}
