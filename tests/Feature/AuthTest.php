<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Mail\RegistrationVerificationCode;
use App\Models\User;
use App\Support\WebAuthn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')
            ->assertRedirect(route('login'));
    }

    public function test_register_creates_customer_and_sends_verification_code(): void
    {
        Mail::fake();

        $response = $this->post(route('register.store'), [
            'username' => 'kasirbaru',
            'email' => 'kasir@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('verification.notice'));

        $user = User::query()->where('email', 'kasir@example.com')->firstOrFail();

        $this->assertSame(UserRole::Customer, $user->role);
        $this->assertSame('kasirbaru', $user->name);
        $this->assertSame('kasirbaru', $user->username);
        $this->assertNull($user->email_verified_at);
        $this->assertNotNull($user->email_verification_code);
        $this->assertNotNull($user->email_verification_expires_at);

        Mail::assertSent(RegistrationVerificationCode::class, function (RegistrationVerificationCode $mail) use ($user) {
            return $mail->hasTo($user->email) && $mail->code === $user->email_verification_code;
        });
    }

    public function test_user_can_verify_registration_code_and_login(): void
    {
        $user = User::factory()->create([
            'email' => 'kasir@example.com',
            'email_verified_at' => null,
            'email_verification_code' => '123456',
            'email_verification_expires_at' => now()->addMinutes(10),
        ]);

        $this->post(route('verification.verify'), [
            'email' => $user->email,
            'code' => '123456',
        ])->assertRedirect(route('pos.index'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertNull($user->fresh()->email_verification_code);
    }

    public function test_unverified_user_cannot_login_before_verification(): void
    {
        $user = User::factory()->create([
            'username' => 'kasirlogin',
            'name' => 'kasirlogin',
            'password' => 'password123',
            'email_verified_at' => null,
        ]);

        $this->post(route('login.store'), [
            'username' => 'kasirlogin',
            'password' => 'password123',
        ])->assertRedirect(route('verification.notice'));

        $this->assertGuest();
    }

    public function test_verified_user_can_login_with_registered_fingerprint(): void
    {
        $credentialId = 'fingerprint-credential-123';
        $user = User::factory()->create([
            'username' => 'kasirfinger',
            'biometric_credential_id' => $credentialId,
            'biometric_registered_at' => now(),
        ]);

        $this->postJson(route('login.fingerprint.options'), [
            'username' => 'kasirfinger',
        ])->assertOk()
            ->assertJsonPath('options.allowCredentials.0.id', $credentialId);

        $challenge = session('fingerprint_login_challenge');

        $this->postJson(route('login.fingerprint'), [
            'username' => 'kasirfinger',
            'credential_id' => $credentialId,
            'client_data_json' => $this->clientDataJson('webauthn.get', $challenge),
        ])
            ->assertOk()
            ->assertJson(['redirect' => route('pos.index')]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_fingerprint_login_rejects_unregistered_or_mismatched_credential(): void
    {
        User::factory()->create([
            'username' => 'tanpafinger',
            'biometric_credential_id' => null,
        ]);

        $this->postJson(route('login.fingerprint.options'), [
            'username' => 'tanpafinger',
        ])->assertUnprocessable();

        User::factory()->create([
            'username' => 'fingerbeda',
            'biometric_credential_id' => 'credential-benar',
            'biometric_registered_at' => now(),
        ]);

        $this->postJson(route('login.fingerprint.options'), [
            'username' => 'fingerbeda',
        ])->assertOk();

        $challenge = session('fingerprint_login_challenge');

        $this->postJson(route('login.fingerprint'), [
            'username' => 'fingerbeda',
            'credential_id' => 'credential-salah',
            'client_data_json' => $this->clientDataJson('webauthn.get', $challenge),
        ])->assertUnprocessable();

        $this->assertGuest();
    }

    public function test_unverified_user_cannot_login_with_fingerprint_before_verification(): void
    {
        $credentialId = 'pending-fingerprint';
        $user = User::factory()->unverified()->create([
            'username' => 'fingerpending',
            'biometric_credential_id' => $credentialId,
            'biometric_registered_at' => now(),
        ]);

        $this->postJson(route('login.fingerprint.options'), [
            'username' => 'fingerpending',
        ])->assertOk();

        $challenge = session('fingerprint_login_challenge');

        $this->postJson(route('login.fingerprint'), [
            'username' => 'fingerpending',
            'credential_id' => $credentialId,
            'client_data_json' => $this->clientDataJson('webauthn.get', $challenge),
        ])->assertForbidden()
            ->assertJson(['redirect' => route('verification.notice')]);

        $this->assertGuest();
        $this->assertSame($user->id, session('pending_verification_user_id'));
    }

    private function clientDataJson(string $type, string $challenge): string
    {
        return WebAuthn::base64UrlEncode(json_encode([
            'type' => $type,
            'challenge' => $challenge,
            'origin' => 'http://localhost',
        ], JSON_THROW_ON_ERROR));
    }
}
